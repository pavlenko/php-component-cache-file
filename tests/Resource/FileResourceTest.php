<?php

namespace PETest\Component\CacheFile\Resource;

use PE\Component\CacheFile\Exception\InvalidArgumentException;
use PE\Component\CacheFile\Resource\FileResource;

class FileResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileResource
     */
    protected $resource;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var int
     */
    protected $time;

    protected function setUp()
    {
        $this->file = sys_get_temp_dir().'/tmp.xml';
        $this->time = time();

        touch($this->file, $this->time);

        $this->resource = new FileResource($this->file);
    }

    protected function tearDown()
    {
        if (!file_exists($this->file)) {
            return;
        }

        unlink($this->file);
    }

    public function testToString()
    {
        $this->assertSame(md5(serialize([realpath($this->file)])), (string) $this->resource);
    }

    public function testResourceDoesNotExist()
    {
        $this->expectException(InvalidArgumentException::class);
        new FileResource('/____foo/foobar'.mt_rand(1, 999999));
    }

    public function testIsFresh()
    {
        $this->assertTrue($this->resource->isFresh($this->time), '->isFresh() returns true if the resource has not changed in same second');
        $this->assertTrue($this->resource->isFresh($this->time + 10), '->isFresh() returns true if the resource has not changed');
        $this->assertFalse($this->resource->isFresh($this->time - 86400), '->isFresh() returns false if the resource has been updated');
    }

    public function testIsFreshForDeletedResources()
    {
        unlink($this->file);

        $this->assertFalse($this->resource->isFresh($this->time), '->isFresh() returns false if the resource does not exist');
    }

    public function testSerializeUnserialize()
    {
        $unserialized = unserialize(serialize($this->resource));

        $this->assertEquals($unserialized, $this->resource);
    }
}
