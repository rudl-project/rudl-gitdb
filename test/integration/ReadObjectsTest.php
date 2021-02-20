<?php


namespace Test;


use PHPUnit\Framework\TestCase;
use Rudl\LibGitDb\RudlGitDbClient;

class ReadObjectsTest extends TestCase
{

    public function testReadObjects()
    {
        $lib = new RudlGitDbClient();
        $lib->setEndpointDev("http://cert_issuer:testtest@localhost");

        $objectsList = $lib->listObjects("certs");
    }

}