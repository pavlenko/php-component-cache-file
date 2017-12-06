<?php

namespace PE\Component\CacheFile\Resource;

use PE\Component\CacheFile\Exception\ExceptionInterface;
use PE\Component\CacheFile\Exception\InvalidArgumentException;

class DirectoryResource implements ResourceInterface, \Serializable
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var string|null
     */
    private $pattern;

    /**
     * @param string      $directory
     * @param null|string $pattern
     *
     * @throws ExceptionInterface
     */
    public function __construct($directory, $pattern = null)
    {
        $directory = realpath($directory);

        if (false === $directory || !file_exists($directory) || !is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('The directory "%s" does not exist.', $directory));
        }

        $this->directory = $directory;
        $this->pattern   = $pattern;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return md5(serialize([$this->directory, $this->pattern]));
    }

    /**
     * @inheritdoc
     */
    public function isFresh($timestamp)
    {
        if (!is_dir($this->directory)) {
            return false;
        }

        if ($timestamp < filemtime($this->directory)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            // if regex filtering is enabled only check matching files
            if ($this->pattern && $file->isFile() && !preg_match($this->pattern, $file->getBasename())) {
                continue;
            }

            // always monitor directories for changes, except the .. entries
            // (otherwise deleted files wouldn't get detected)
            if ($file->isDir() && '/..' === substr($file, -3)) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            // early return if a file's mtime exceeds the passed timestamp
            if ($timestamp < $file->getMTime()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize([$this->directory, $this->pattern]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list($this->directory, $this->pattern) = unserialize($serialized);
    }
}