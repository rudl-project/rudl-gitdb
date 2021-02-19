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
use Rudl\LibGitDb\Type\Transport\T_Object;
use Rudl\LibGitDb\Type\Transport\T_ObjectList;
use Rudl\Vault\Lib\Format\MultilineFormat;
use Rudl\Vault\Lib\KeyLoader\CallbackKeyLoader;
use Rudl\Vault\Lib\KeyVault;

AppLoader::extend(function (BraceApp $app) {
    $app->router->on("GET@/hooks/repo", function (VcsRepository $vcsRepository) {
        $vcsRepository->pull();
        return ["success" => true];
    });

    $app->router->on("GET|POST@/hooks/trigger", function (VcsRepository $vcsRepository, array $body) {
        phore_dir($vcsRepository->getLocalRepoPath())->withFileName("trigger_last.yml")->set_yaml([
            "date" => date("Y-m-d H:i:s"),
            "body" => $body
        ]);
        $vcsRepository->commit("Trigger pulled.");
        if ( ! DEV_SKIP_PUSH) {
            $vcsRepository->push();
        }
    });

    $app->router->on("GET@/api/revision", function (VcsRepository $vcsRepository) {
        return new Response\TextResponse($vcsRepository->getRev());
    });

    $app->router->on(
        "GET@/api/o/:scopeName",
        function(ObjectAccessor $objectAccessor, RouteParams $routeParams, VcsRepository $vcsRepository, KeyVault $keyVault) {

            if ( ! $vcsRepository->exists())
                throw new \InvalidArgumentException("Repository not cloned");

            $reqScope = $routeParams->get("scopeName");

            $objectList = $objectAccessor->getObjectList($reqScope);


            $filter = new MultilineFormat($keyVault, new CallbackKeyLoader(function (string $keyId, KeyVault $keyVault) {
                $keyVault->unlockKey($keyId, KEYVAULT_SECRET);
            }));
            $objectList->objects = array_filter($objectList->objects, function (T_Object $in) use ($filter) {
                $in->content = $filter->decode($in->content);
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
                $in->content = $filter->encode($in->content, KEYVAULT_KEY_ID);
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