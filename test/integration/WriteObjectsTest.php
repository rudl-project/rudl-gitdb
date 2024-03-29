<?php


namespace Test;


use PHPUnit\Framework\TestCase;
use Rudl\LibGitDb\RudlGitDbClient;
use Rudl\LibGitDb\Type\Transport\T_Object;
use Rudl\LibGitDb\Type\Transport\T_ObjectList;

class WriteObjectsTest extends TestCase
{

    public function testWriteFiles()
    {
        $lib = new RudlGitDbClient();
        $lib->setEndpointDev("http://cert_issuer1:testtest@localhost");

        $objectList = new T_ObjectList([
            new T_Object("some.crt.pem", "ABC"),
            new T_Object("other.object", "DEF", true)
        ]);

        $lib->writeObjects("certs", $objectList);
    }

}