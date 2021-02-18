<?php


namespace Test;


use PHPUnit\Framework\TestCase;
use Rudl\LibGitDb\RudlGitDbClient;

class ReadObjectsTest extends TestCase
{

    public function testReadObjects()
    {
        $lib = new RudlGitDbClient("http://cert_issuer:test@localhost");
        $objectsList = $lib->listObjects("certs");
    }

}