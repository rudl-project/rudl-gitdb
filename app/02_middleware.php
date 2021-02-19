<?php

namespace App;

use Brace\Auth\Basic\AuthBasicMiddleware;
use Brace\Auth\Basic\BasicAuthToken;
use Brace\Body\BodyMiddleware;
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
use Rudl\GitDb\AccessChecker;

AppLoader::extend(function (BraceApp $app) {

    $firewall = new FirewallMiddleware(
        [
            "@/hooks/repo" => true,
            "@/hooks/trigger" => true,
            "@/api/*" => function (AccessChecker $accessChecker, BasicAuthToken $basicAuthToken) {
                $accessChecker->validateSystem($basicAuthToken->user, $basicAuthToken->passwd);
                return null; // Check next rules
            },
            "@/api/revision" => true,
            "POST@/api/o/:scopeName" => function(AccessChecker $accessChecker, BasicAuthToken $basicAuthToken, RouteParams $routeParams) {
                $accessChecker->validateWriteAccess($basicAuthToken->user, $basicAuthToken->passwd, $routeParams->get("scopeName"));
                return true;
            },
            "GET@/api/o/:scopeName" => function(AccessChecker $accessChecker, BasicAuthToken $basicAuthToken, RouteParams $routeParams) {
                $accessChecker->validateReadAccess($basicAuthToken->user, $basicAuthToken->passwd, $routeParams->get("scopeName"));
                return true;
            }
        ]
    );

    $app->setPipe([
        new ExceptionHandlerMiddleware(),
        new RouterEvalMiddleware(),
        new AuthBasicMiddleware(),
        new BodyMiddleware(),
        $firewall,
        new RouterDispatchMiddleware([
            new JsonReturnFormatter($app)
        ]),
        new NotFoundMiddleware()
    ]);
});