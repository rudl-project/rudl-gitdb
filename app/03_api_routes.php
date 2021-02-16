<?php
namespace App;

use Brace\Auth\Basic\BasicAuthToken;
use Brace\Core\AppLoader;
use Brace\Core\BraceApp;
use Brace\Router\Type\RouteParams;
use Brace\UiKit\CoreUi\Template\Page;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ResponseFactory;
use Phore\VCS\VcsFactory;
use Phore\VCS\VcsRepository;
use Psr\Http\Message\RequestInterface;
use Rudl\GitDb\AccessChecker;
use Rudl\GitDb\ObjectAccessor;

AppLoader::extend(function (BraceApp $app) {
    $app->router->onGet("/hooks/repo", function (VcsRepository $vcsRepository) {
        $vcsRepository->pull();
        return ["success" => true];
    });

    $app->router->onGet(
        "/api/o/:scopeName",
        function(ObjectAccessor $objectAccessor, RouteParams $routeParams, BasicAuthToken $basicAuthToken) {
            $validator = new AccessChecker($objectAccessor->loadConfig());
            $reqScope = $routeParams->get("scopeName");

            $validator->validateSystem($basicAuthToken->user , $basicAuthToken->passwd);
            $validator->hasReadAccess($basicAuthToken->user, $reqScope);

            return new Response\JsonResponse(
                $objectAccessor->getFileList($reqScope)
            );
        }
    );

    $app->router->onPost(
        "/api/o/:scopeName",
        function (ObjectAccessor $objectAccessor, RouteParams $routeParams, BasicAuthToken $basicAuthToken) {
            $validator = new AccessChecker($objectAccessor->loadConfig());
        });
});