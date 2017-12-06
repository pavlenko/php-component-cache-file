<?php

namespace PETest\Component\CacheFile;

use PE\Component\CacheFile\CacheFile;
use PE\Component\CacheFile\Exception\UnexpectedValueException;
use PETest\Component\CacheFile\Resource\ResourceStub;

class CacheFileTest extends \PHPUnit_Framework_TestCase
{
    private $cacheFile;

    protected function setUp()
    {
        $this->cacheFile = tempnam(sys_get_temp_dir(), 'config_');
    }

    protected function tearDown()
    {
        $files = array($this->cacheFile, $this->cacheFile . '.meta');

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @dataProvider provideDebugModes
     */
    public function testCacheIsNotValidIfNothingHasBeenCached($debug)
    {
        unlink($this->cacheFile); // remove tempnam() side effect
        $cache = new CacheFile($this->cacheFile, $debug);

        $this->assertFalse($cache->isFresh());
    }

    public function testIsAlwaysFreshInProduction()
    {
        $staleResource = new ResourceStub();
        $staleResource->setFresh(false);

        $cache = new CacheFile($this->cacheFile, false);
        $cache->write('', array($staleResource));

        $this->assertTrue($cache->isFresh());
    }

    /**
     * @dataProvider provideDebugModes
     */
    public function testIsFreshWhenNoResourceProvided($debug)
    {
        $cache = new CacheFile($this->cacheFile, $debug);
        $cache->write('', array());
        $this->assertTrue($cache->isFresh());
    }

    public function testFreshResourceInDebug()
    {
        $freshResource = new ResourceStub();
        $freshResource->setFresh(true);

        $cache = new CacheFile($this->cacheFile, true);
        $cache->write('', array($freshResource));

        $this->assertTrue($cache->isFresh());
    }

    public function testStaleResourceInDebug()
    {
        $staleResource = new ResourceStub();
        $staleResource->setFresh(false);

        $cache = new CacheFile($this->cacheFile, true);
        $cache->write('', array($staleResource));

        $this->assertFalse($cache->isFresh());
    }

    public function testThrowsExceptionIfInvalidResourcePassedToWrite()
    {
        $this->expectException(UnexpectedValueException::class);
        $cache = new CacheFile($this->cacheFile, true);
        $cache->write('', array('foo'));
    }

    public function provideDebugModes()
    {
        return array(
            array(true),
            array(false),
        );
    }
}
