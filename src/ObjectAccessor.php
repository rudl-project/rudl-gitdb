<?php


namespace Rudl\GitDb;


use Rudl\LibGitDb\Type\Conf\T_GitDbConfig;
use Rudl\LibGitDb\Type\Transport\T_File;
use Rudl\LibGitDb\Type\Transport\T_FileList;
use Rudl\Vault\Lib\KeyVault;

class ObjectAccessor
{

    const CONFIG_NAME = "gitdb.conf.yml";

    private $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = phore_dir($rootDir)->assertDirectory();
    }

    public function getFileList($scope) : T_FileList
    {
        $fileList = [];
        $scopeDir = $this->rootDir->withSubPath($scope)->assertDirectory();
        foreach ($scopeDir->genWalk() as $file) {
            $fileList[] = $file;
        }

        uasort($fileList, function ($a, $b) {
            if ($a == $b)
                return 0;
            return ($a < $b) ? -1 : 1;
        });

        $response = new T_FileList();
        foreach ($fileList as $file) {
            $respFile = new T_File();
            $respFile->filename = $file->getFilename();
            $respFile->content = $file->assertFile()->get_contents();
            $response->files[] = $respFile;
        }
        return $response;
    }

    public function writeFileList(string $scope, T_FileList $fileList) : void
    {

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