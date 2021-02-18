<?php


namespace Test;


use PHPUnit\Framework\TestCase;
use Rudl\LibGitDb\RudlGitDbClient;

class GetRevisionTest extends TestCase
{

    public function testGetRevision()
    {
        $lib = new RudlGitDbClient("http://cert_issuer:test@localhost");
        $this->assertEquals(40, strlen ($lib->getRevision()));
    }


}