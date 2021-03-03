<?php


namespace Test;


use PHPUnit\Framework\TestCase;
use Rudl\LibGitDb\RudlGitDbClient;

class LogTest extends TestCase
{
    public function testWriteFiles()
    {
        $lib = new RudlGitDbClient();
        $lib->setEndpointDev("http://cert_issuer1:testtest@localhost");

        $lib->logError("Update successful");
    }
}