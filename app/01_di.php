<?php
namespace App;


use Brace\Auth\Basic\AuthBasicMiddleware;
use Brace\Body\BodyMiddleware;
use Brace\Core\AppLoader;
use Brace\Core\Base\ExceptionHandlerMiddleware;
use Brace\Core\Base\JsonReturnFormatter;
use Brace\Core\Base\NotFoundMiddleware;
use Brace\Core\BraceApp;
use Brace\Mod\Request\Zend\BraceRequestLaminasModule;
use Brace\Router\RouterDispatchMiddleware;
use Brace\Router\RouterEvalMiddleware;
use Brace\Router\RouterModule;
use Phore\Di\Container\Producer\DiService;
use Phore\Di\Container\Producer\DiValue;
use Phore\VCS\VcsFactory;
use Rudl\GitDb\AccessChecker;
use Rudl\GitDb\AccessCheckerMiddleware;
use Rudl\GitDb\ObjectAccessor;
use Rudl\Vault\Lib\Config;
use Rudl\Vault\Lib\KeyVault;


AppLoader::extend(function (BraceApp $app) {
    $app->addModule(new BraceRequestLaminasModule());
    $app->addModule(new RouterModule());

    $app->define("objectAccessor", new DiValue(new ObjectAccessor(DATA_PATH)));

    $app->define("vcsFactory", new DiService(function () {
        $vcsF =  new VcsFactory();
        $vcsF->setCommitUser("rudl-gitdb", "rudl@infracamp.org");
        return $vcsF;
    }));

    $app->define("vcsRepository", new DiService(function (VcsFactory $vcsFactory) {
        $repo = $vcsFactory->repository(DATA_PATH, REPO_GIT_URL);
        return $repo;
    }));

    $app->define("keyVault", new DiService(function () {
        $rudlVaultConfig = new Config();
        $rudlVaultConfig->load( DATA_PATH . "/.rudl-vault.json");
        $keyVault =  new KeyVault($rudlVaultConfig);
        return $keyVault;
    }));

    $app->define("accessChecker", new DiService(function (ObjectAccessor $objectAccessor) {
        return new AccessChecker($objectAccessor->loadConfig());
    }));



});