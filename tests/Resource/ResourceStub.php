<?php

namespace PETest\Component\CacheFile\Resource;

use PE\Component\CacheFile\Resource\ResourceInterface;

class ResourceStub implements ResourceInterface
{
    private $fresh = true;

    public function setFresh($isFresh)
    {
        $this->fresh = $isFresh;
    }

    public function __toString()
    {
        return 'stub';
    }

    public function isFresh($timestamp)
    {
        return $this->fresh;
    }
}