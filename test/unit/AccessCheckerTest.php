<?php


namespace Tests;


use PHPUnit\Framework\TestCase;
use Rudl\GitDb\AccessChecker;
use Rudl\LibGitDb\Type\Conf\T_GitDbConfig;

class AccessCheckerTest extends TestCase
{

    public function testReadAccess()
    {
        $loader = new AccessChecker(
            phore_hydrate(phore_file(__DIR__ . "/../mock/repo/gitdb.conf.yml")->get_yaml(), T_GitDbConfig::class)
        );

        $this->assertEquals(true, $loader->hasReadAccess("cert_issuer", "certs"));
        $this->assertEquals(true, $loader->hasWriteAccess("cert_issuer", "certs"));
        $this->assertEquals(false, $loader->hasWriteAccess("cert_reader", "certs"));
    }

}