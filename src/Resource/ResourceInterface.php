<?php

namespace PE\Component\CacheFile\Resource;

interface ResourceInterface
{
    /**
     * Returns a string representation of the Resource.
     *
     * This method is necessary to allow for resource de-duplication, for example by means
     * of array_unique(). The string returned need not have a particular meaning, but has
     * to be identical for different ResourceInterface instances referring to the same
     * resource; and it should be unlikely to collide with that of other, unrelated
     * resource instances.
     *
     * @return string A string representation unique to the underlying Resource
     */
    public function __toString();

    /**
     * @param int $timestamp
     *
     * @return bool
     */
    public function isFresh($timestamp);
}