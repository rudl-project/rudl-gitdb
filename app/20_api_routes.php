<?php
namespace App;

use Brace\Auth\Basic\BasicAuthToken;
use Brace\Core\AppLoader;
use Brace\Core\BraceApp;
use Brace\Router\Type\RouteParams;
use Laminas\Diactoros\Response;
use Phore\VCS\VcsRepository;
use Psr\Http\Message\RequestInterface;
use Rudl\GitDb\ObjectAccessor;
use Rudl\GitDb\State;
use Rudl\LibGitDb\Type\Transport\T_Log;
use Rudl\LibGitDb\Type\Transport\T_Object;
use Rudl\LibGitDb\Type\Transport\T_ObjectList;
use Rudl\Vault\Lib\Config;
use Rudl\Vault\Lib\Format\MultilineFormat;
use Rudl\Vault\Lib\Format\StringFormat;
use Rudl\Vault\Lib\KeyLoader\CallbackKeyLoader;
use Rudl\Vault\Lib\KeyVault;

AppLoader::extend(function (BraceApp $app) {

    // This route is used by internal startup method
    $app->router->on("GET@/hooks/startup", $pullFn = function (VcsRepository $vcsRepository) use ($app) {
        $vcsRepository->pull();
        $rudlVaultFile = DATA_PATH . "/.rudl-vault.json";
        if ( ! file_exists($rudlVaultFile)) {
            $rudlVault = new KeyVault($config = new Config());
            $config->createNew($rudlVaultFile);
            $rudlVault->createKeyPair(RUDL_VAULT_KEY_ID, $app->rudlVaultSecret);
            $config->save();
            $vcsRepository->commit("created .rudl-vault.json");
            if ( ! DEV_SKIP_PUSH) {
                $vcsRepository->push();
            }
            return ["success" => true, "msg" => "created and pushed .rudl-vault.json (init dir)"];
        }
        return ["success" => true];
    });

    // And this one is for external requests
    $app->router->on("GET|POST@/hooks/repo", $pullFn);

    $app->router->on("GET|POST@/hooks/trigger", function (VcsRepository $vcsRepository, string $body) {
        phore_dir($vcsRepository->getLocalRepoPath())->withFileName("trigger_last.yml")->set_yaml([
            "date" => date("Y-m-d H:i:s"),
            "body" => $body
        ]);
        $vcsRepository->commit("Trigger pulled.");
        if ( ! DEV_SKIP_PUSH) {
            $vcsRepository->push();
        }
        return ["success" => true, "msg" => "update triggered"];
    });

    $app->router->on("GET@/api/revision", function (VcsRepository $vcsRepository, State $state, BasicAuthToken $basicAuthToken) {
        $state->set(["update", $basicAuthToken->user, "last_rev"], date("Y-m-d H:i:s"));

        return new Response\TextResponse($vcsRepository->getRev());
    });

    $app->router->on("POST@/api/log", function (State $state, T_Log $body, BasicAuthToken $basicAuthToken) {
        $state->set(["update", $basicAuthToken->user, "last_log"], date ("Y-m-d H:i:s"));
        $state->set(["update", $basicAuthToken->user, "last_log_type"], $body->type);
        $state->set(["update", $basicAuthToken->user, "last_log_msg"], $body->msg);
        return ["success" => true, "msg" => "logged"];
    });

    $app->router->on(
        "GET@/api/o/:scopeName",
        function(ObjectAccessor $objectAccessor, RouteParams $routeParams, VcsRepository $vcsRepository, KeyVault $keyVault) use ($app) {

            if ( ! $vcsRepository->exists())
                throw new \InvalidArgumentException("Repository not cloned");

            $reqScope = $routeParams->get("scopeName");

            $objectList = $objectAccessor->getObjectList($reqScope);
            $keyLoader = new CallbackKeyLoader(function (string $keyId, KeyVault $keyVault) use ($app) {
                $keyVault->unlockKey($keyId, $app->rudlVaultSecret);
            });

            $filterMl = new MultilineFormat($keyVault, $keyLoader);
            $filterSl = new StringFormat($keyVault, $keyLoader);

            $objectList->objects = array_filter($objectList->objects, function (T_Object $in) use ($filterMl, $filterSl) {
                $in->content = $filterMl->decode($in->content);
                $in->content = $filterSl->decode($in->content);
                return $in;
            });
            $objectList->rev = $vcsRepository->getRev();
            return (array)$objectList;
        }
    );

    $app->router->on(
        "POST@/api/o/:scopeName",
        function (RequestInterface $request, ObjectAccessor $objectAccessor, RouteParams $routeParams, BasicAuthToken $basicAuthToken, T_ObjectList $body, VcsRepository $vcsRepository, KeyVault $keyVault) {
            if ( ! $vcsRepository->exists())
                throw new \InvalidArgumentException("Repository not cloned");
            $reqScope = $routeParams->get("scopeName");

            $filter = new MultilineFormat($keyVault, new CallbackKeyLoader(function (string $keyId, KeyVault $keyVault) {
            }));

            $body->objects = array_filter($body->objects, function (T_Object $in) use ($filter) {
                if ($in->encrypted === true) {
                    $in->content = $filter->encode($in->content, RUDL_VAULT_KEY_ID);
                }
                return $in;
            });

            if ($request->getUri()->getQuery() !== "simulate") {
                $objectAccessor->writeFileList($reqScope, $body);
                $vcsRepository->commit("Scope update '$reqScope' from system '{$basicAuthToken->user}'");
                if ( ! DEV_SKIP_PUSH) {
                    $vcsRepository->pull();
                    $vcsRepository->push();
                }
            }

            return ["success" => true, "written_files" => count($body->objects)];
        });
});