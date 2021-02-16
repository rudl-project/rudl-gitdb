<?php


namespace Tests;


use PHPUnit\Framework\TestCase;
use Rudl\GitDb\ObjectAccessor;

class GitDbObjectAccessorTest extends TestCase
{


    public function testLoadConfig()
    {
        $accessor = new ObjectAccessor(__DIR__ . "/../mock/repo");
        $config = $accessor->loadConfig();

    }


}