<?php

namespace App;

use Brace\Assets\AssetsMiddleware;
use Brace\Assets\AssetsModule;
use Brace\Auth\Basic\AuthBasicMiddleware;
use Brace\Auth\Basic\BasicAuthToken;
use Brace\Body\BodyMiddleware;
use Brace\Connection\ConnectionInfo;
use Brace\Core\AppLoader;
use Brace\Core\Base\ExceptionHandlerMiddleware;
use Brace\Core\Base\JsonReturnFormatter;
use Brace\Core\Base\NotFoundMiddleware;
use Brace\Core\BraceApp;
use Brace\Firewall\FirewallMiddleware;
use Brace\Mod\Request\Zend\BraceRequestLaminasModule;
use Brace\Router\RouterDispatchMiddleware;
use Brace\Router\RouterEvalMiddleware;
use Brace\Router\RouterModule;
use Brace\Router\Type\RouteParams;
use Brace\UiKit\Base\Template\UiKitPageReturnFormatter;
use Brace\UiKit\Bootstrap\BootstrapConfig;
use Brace\UiKit\Bootstrap\BootstrapModule;
use Phore\Di\Container\Producer\DiService;
use Psr\Http\Message\ServerRequestInterface;
use Rudl\GitDb\AccessChecker;

AppLoader::extend(function (BraceApp $app) {

    $firewall = new FirewallMiddleware(
        [
            "@/hooks/startup" => function(ConnectionInfo $connectionInfo) {
                if ($connectionInfo->remoteAddrMatchCidr(["127.0.0.0/24"]))
                    return true;
                return false;
            },
            "@/hooks/repo" => function (AccessChecker $accessChecker, ConnectionInfo $connectionInfo, ServerRequestInterface $request) {
                $token = $request->getQueryParams()["token"] ?? null;
                if ($token === null)
                    throw new \InvalidArgumentException("Missing token query parameter ?token=");
                $accessChecker->validateRepoHookToken($request->getQueryParams()["token"]);
                return true;
            },
            "@/hooks/trigger" => function (AccessChecker $accessChecker, ServerRequestInterface $request) {
                $token = $request->getQueryParams()["token"];
                if ($token === null)
                    throw new \InvalidArgumentException("Missing token query parameter ?token=");
                $accessChecker->validateTriggerHookToken($token);
                return true;
            },
            "@/api/*" => function (AccessChecker $accessChecker, BasicAuthToken $basicAuthToken) {
                $basicAuthToken->validate();
                $accessChecker->validateSystem($basicAuthToken->user, $basicAuthToken->passwd);
                return null; // Check next rules
            },
            "POST@/api/log" => true,
            "@/api/revision" => true,
            "POST@/api/o/:scopeName" => function(AccessChecker $accessChecker, BasicAuthToken $basicAuthToken, RouteParams $routeParams) {
                $basicAuthToken->validate();
                $accessChecker->validateWriteAccess($basicAuthToken->user, $basicAuthToken->passwd, $routeParams->get("scopeName"));
                return true;
            },
            "GET@/api/o/:scopeName" => function(AccessChecker $accessChecker, BasicAuthToken $basicAuthToken, RouteParams $routeParams) {
                $basicAuthToken->validate();
                $accessChecker->validateReadAccess($basicAuthToken->user, $basicAuthToken->passwd, $routeParams->get("scopeName"));
                return true;
            },
            "@/" => true,
            "@/state.json" => true
        ]
    );


    $app->setPipe([
        new ExceptionHandlerMiddleware(),
        new RouterEvalMiddleware(),
        new AuthBasicMiddleware(),
        new BodyMiddleware(),
        new AssetsMiddleware(["/assets/"]),
        $firewall,
        new RouterDispatchMiddleware([
            new UiKitPageReturnFormatter($app, "bootstrapConfig"),
            new JsonReturnFormatter($app)
        ]),
        new NotFoundMiddleware()
    ]);
});