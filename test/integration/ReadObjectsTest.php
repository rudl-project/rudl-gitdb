<?php


namespace Test;


use PHPUnit\Framework\TestCase;
use Rudl\LibGitDb\RudlGitDbClient;

class ReadObjectsTest extends TestCase
{

    public function testReadObjects()
    {
        $lib = new RudlGitDbClient();
        $lib->setEndpointDev("http://cert_issuer1:testtest@localhost");

        $objectsList = $lib->listObjects("ssl_certs");
        print_r ($objectsList);
    }

}