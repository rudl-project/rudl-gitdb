<?php


namespace Rudl\GitDb;


use Rudl\LibGitDb\Type\Conf\T_GitDbConfig;
use Rudl\LibGitDb\Type\Transport\T_File;
use Rudl\LibGitDb\Type\Transport\T_Object;
use Rudl\LibGitDb\Type\Transport\T_ObjectList;
use Rudl\Vault\Lib\KeyVault;

class ObjectAccessor
{

    const CONFIG_NAME = "gitdb.conf.yml";

    private $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = phore_dir($rootDir)->assertDirectory();
    }

    public function getObjectList($scope) : T_ObjectList
    {
        $fileList = [];
        $scopeDir = $this->rootDir->withSubPath($scope);
        if ( ! $scopeDir->exists())
            return new T_ObjectList();
        $scopeDir = $scopeDir->assertDirectory();

        foreach ($scopeDir->genWalk() as $file) {
            $fileList[] = $file;
        }

        uasort($fileList, function ($a, $b) {
            if ($a == $b)
                return 0;
            return ($a < $b) ? -1 : 1;
        });

        $response = new T_ObjectList();
        foreach ($fileList as $file) {
            $respFile = new T_Object();
            $respFile->name = $file->getBasename();
            $respFile->content = $file->assertFile()->get_contents();
            $response->objects[] = $respFile;
        }
        return $response;
    }

    public function writeFileList(string $scope, T_ObjectList $objectList) : void
    {
        $scopeDir = $this->rootDir->withSubPath($scope)->assertDirectory(true);
        foreach ($objectList->objects as $object) {
            $curFile = $scopeDir->withFileName($object->name);
            $curFile->set_contents($object->content);
        }
    }

    public function getFile($scope, $object) : string
    {

    }

    public function writeFile($scope, $object, $data) : void
    {

    }

    public function loadConfig() : T_GitDbConfig
    {
        return phore_hydrate(
            $this->rootDir->withFileName(self::CONFIG_NAME)->get_yaml(),
            T_GitDbConfig::class
        );
    }

}