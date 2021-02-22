<?php
namespace App;


use Brace\Auth\Basic\AuthBasicMiddleware;
use Brace\Body\BodyMiddleware;
use Brace\Connection\ConnectionInfoModule;
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
use Phore\VCS\Git\SshGitRepository;
use Phore\VCS\VcsFactory;
use Rudl\GitDb\AccessChecker;
use Rudl\GitDb\AccessCheckerMiddleware;
use Rudl\GitDb\ObjectAccessor;
use Rudl\Vault\Lib\Config;
use Rudl\Vault\Lib\KeyVault;


AppLoader::extend(function (BraceApp $app) {
    $app->addModule(new BraceRequestLaminasModule());
    $app->addModule(new ConnectionInfoModule());
    $app->addModule(new RouterModule());

    $app->define("objectAccessor", new DiValue(new ObjectAccessor(DATA_PATH)));


    $app->define("rudlVaultSecret", new DiService(function () {
        $secret = RUDL_VAULT_SECRET;
        if (preg_match ("|^file:(.*)?|", $secret, $matches)) {
            $secretFile = $matches[1];
            if ( ! file_exists($secretFile) || ! is_readable($secretFile))
                throw new \InvalidArgumentException("Cannot read rudl vault secret file: '$secretFile'.");
            $secret = trim(file_get_contents($secretFile));
        }
        return $secret;
    }));

    $app->define("vcsFactory", new DiService(function () {
        $vcsF =  new VcsFactory();
        $vcsF->setCommitUser("rudl-gitdb", "rudl@infracamp.org");
        return $vcsF;
    }));

    $app->define("vcsRepository", new DiService(function (VcsFactory $vcsFactory) {
        $repo = $vcsFactory->repository(DATA_PATH, GIT_REPO_URL);
        if ($repo instanceof SshGitRepository) {
            $repo->setSshPrivateKey(GIT_REPO_SSH_KEY);
        }
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