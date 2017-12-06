<?php

namespace PE\Component\CacheFile\Resource;

use PE\Component\CacheFile\Exception\ExceptionInterface;
use PE\Component\CacheFile\Exception\InvalidArgumentException;

class FileResource implements ResourceInterface, \Serializable
{
    /**
     * @var string[]
     */
    private $files = [];

    /**
     * @param string|string[] $files
     *
     * @throws ExceptionInterface
     */
    public function __construct($files)
    {
        if (!is_array($files)) {
            $files = (array) $files;
        }

        foreach ((array) $files as $file) {
            $file = realpath($file);

            if (false === $file || !file_exists($file)) {
                throw new InvalidArgumentException(sprintf('The file "%s" does not exist.', $file));
            }

            $this->files[] = (string) $file;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return md5(serialize($this->files));
    }

    /**
     * @inheritdoc
     */
    public function isFresh($timestamp)
    {
        foreach ($this->files as $file) {
            if (file_exists($file) && @filemtime($file) <= $timestamp) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->files);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->files = unserialize($serialized);
    }
}