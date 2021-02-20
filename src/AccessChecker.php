<?php


namespace Rudl\GitDb;


use Rudl\GitDb\Ex\AccessDeniedException;
use Rudl\GitDb\Ex\AuthFailedException;
use Rudl\LibGitDb\Type\Conf\T_ClientConfig;
use Rudl\LibGitDb\Type\Conf\T_GitDbConfig;
use Rudl\LibGitDb\Type\Conf\T_ScopeConfig;
use Rudl\LibGitDb\Type\Conf\T_SystemConfig;

class AccessChecker
{

    public function __construct(
        private T_GitDbConfig $config
    ) {}

    public function getScopeConfig(string $name) : T_ScopeConfig
    {
        foreach ($this->config->scopes as $scope) {
            if ($scope->name === $name)
                return $scope;
        }
        throw new \InvalidArgumentException("Scope '$name' not found in config.");
    }

    public function getClientConfig(string $name) : T_ClientConfig
    {
        foreach ($this->config->clients as $client) {
            if ($client->name === $name)
                return $client;
        }
        throw new \InvalidArgumentException("ClientId '$name' not found in config.");
    }

    public function validateSystem(string $clientId, string $clientSecret) : void
    {
        $clientConfig = $this->getClientConfig($clientId);
        foreach ($clientConfig->accessKeysHash as $hash) {
            if (password_verify($clientSecret, $hash)) {
                return;
            }
        }
        throw new AuthFailedException("Invalid token for clientId '$clientId'");
    }

    public function hasReadAccess(string $clientId, string $scopeName) : bool
    {
        $scope = $this->getScopeConfig($scopeName);
        return in_array($clientId, $scope->allowRead);
    }

    public function hasWriteAccess(string $clientId, string $scopeName) : bool
    {
        $scope = $this->getScopeConfig($scopeName);
        return in_array($clientId, $scope->allowWrite);
    }

    public function validateReadAccess(string $clientId, string $authToken, string $scopeName)
    {
        $this->validateSystem($clientId, $authToken);
        if ($this->hasReadAccess($clientId, $scopeName))
            return true;
        throw new AccessDeniedException("Read access denied for clientId '$clientId' to scope '$scopeName'");
    }

    public function validateWriteAccess(string $clientId, string $authToken, string $scopeName)
    {
        $this->validateSystem($clientId, $authToken);
        if ($this->hasWriteAccess($clientId, $scopeName))
            return true;
        throw new AccessDeniedException("Write access denied for clientId '$clientId' to scope '$scopeName'");
    }
}