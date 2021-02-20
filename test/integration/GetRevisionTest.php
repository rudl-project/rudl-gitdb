<?php


namespace Test;


use PHPUnit\Framework\TestCase;
use Rudl\LibGitDb\RudlGitDbClient;

class GetRevisionTest extends TestCase
{

    public function testGetRevision()
    {
        $lib = new RudlGitDbClient();
        $lib->setEndpointDev("http://cert_issuer:testtest@localhost");
        $this->assertEquals(40, strlen ($lib->getRevision()));
    }


}