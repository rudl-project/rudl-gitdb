<?php


namespace Rudl\GitDb;


use Brace\Auth\Basic\BasicAuthToken;
use Brace\Core\Base\BraceAbstractMiddleware;
use Brace\Router\Type\RouteParams;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rudl\GitDb\Ex\AccessDeniedException;

class AccessCheckerMiddleware extends BraceAbstractMiddleware
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Routes with no authentication
        if (str_starts_with($request->getUri()->getPath(), "/hooks/repo")) {
            return $handler->handle($request);
        }


        $routeParams = $this->app->routeParams;
        if ( ! $routeParams instanceof RouteParams)
            throw new \InvalidArgumentException("Middleware should be placed after RouteEval");
        $basicAuthToken = $this->app->basicAuthToken;
        if ( ! $basicAuthToken instanceof BasicAuthToken)
            throw new \InvalidArgumentException("Middleware should be placed after BasicAuthMiddleware");

        try {
            if ($routeParams->has("scope")) {
                $accessChecker = new AccessChecker($this->app->objectAccessor);
                if ($request->getMethod() === "GET") {
                    $accessChecker->validateReadAccess($basicAuthToken->user, $basicAuthToken->passwd, $routeParams->get("scope"));
                }
                if ($request->getMethod() === "POST") {
                    $accessChecker->validateWriteAccess($basicAuthToken->user, $basicAuthToken->passwd, $routeParams->get("scope"));
                }
                throw new AccessDeniedException("Invalid method");
            }
        } catch (AccessDeniedException $ex) {
            return new TextResponse("403 Access denied", 403);
        }

        return $handler->handle($request);
    }
}