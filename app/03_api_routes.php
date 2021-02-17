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
use Rudl\LibGitDb\Type\Transport\T_Object;
use Rudl\LibGitDb\Type\Transport\T_ObjectList;

AppLoader::extend(function (BraceApp $app) {
    $app->router->onGet("/hooks/repo", function (VcsRepository $vcsRepository) {
        $vcsRepository->pull();
        return ["success" => true];
    });

    $app->router->onGet(
        "/api/o/:scopeName",
        function(ObjectAccessor $objectAccessor, RouteParams $routeParams, VcsRepository $vcsRepository) {
            if ( ! $vcsRepository->exists())
                throw new \InvalidArgumentException("Repository not cloned");

            $reqScope = $routeParams->get("scopeName");

            return (array)$objectAccessor->getFileList($reqScope);
        }
    );

    $app->router->onPost(
        "/api/o/:scopeName",
        function (ObjectAccessor $objectAccessor, RouteParams $routeParams, T_ObjectList $body, VcsRepository $vcsRepository) {
            if ( ! $vcsRepository->exists())
                throw new \InvalidArgumentException("Repository not cloned");
            $reqScope = $routeParams->get("scopeName");

            $body->objects = array_filter($body->objects, function (T_Object $in) {

            });

            $objectAccessor->writeFileList($reqScope, $body);

            return ["success" => true, "written_files" => count($body->objects)];
        });
});