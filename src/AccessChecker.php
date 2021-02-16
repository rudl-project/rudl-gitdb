<?php


namespace Rudl\GitDb;


use Rudl\GitDb\Ex\AccessDeniedException;
use Rudl\GitDb\Ex\AuthFailedException;
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

    public function getSystemConfig(string $name) : T_SystemConfig
    {
        foreach ($this->config->systems as $system) {
            if ($system->name === $name)
                return $system;
        }
        throw new \InvalidArgumentException("System '$name' not found in config.");
    }

    public function validateSystem(string $systemName, string $authToken) : void
    {
        $system = $this->getSystemConfig($systemName);
        foreach ($system->accessKeysHash as $hash) {
            if (password_verify($authToken, $hash)) {
                return;
            }
        }
        throw new AuthFailedException("Invalid token for system '$systemName'");
    }

    public function hasReadAccess(string $systemName, string $scopeName) : bool
    {
        $scope = $this->getScopeConfig($scopeName);
        return in_array($systemName, $scope->allowRead);
    }

    public function hasWriteAccess(string $systemName, string $scopeName) : bool
    {
        $scope = $this->getScopeConfig($scopeName);
        return in_array($systemName, $scope->allowWrite);
    }

    public function validateReadAccess(string $systemName, string $authToken, string $scopeName)
    {
        $this->validateSystem($systemName, $authToken);
        if ($this->hasReadAccess($systemName, $scopeName))
            return true;
        throw new AccessDeniedException("Read access denied for system '$systemName' to scope '$scopeName'");
    }

    public function validateWriteAccess(string $systemName, string $authToken, string $scopeName)
    {
        $this->validateSystem($systemName, $authToken);
        if ($this->hasWriteAccess($systemName, $scopeName))
            return true;
        throw new AccessDeniedException("Write access denied for system '$systemName' to scope '$scopeName'");
    }
}