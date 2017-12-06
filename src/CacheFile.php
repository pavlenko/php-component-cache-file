<?php

namespace PE\Component\CacheFile;

use PE\Component\CacheFile\Exception\ExceptionInterface;
use PE\Component\CacheFile\Exception\RuntimeException;
use PE\Component\CacheFile\Exception\UnexpectedValueException;
use PE\Component\CacheFile\Resource\ResourceInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class CacheFile
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $meta;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var callable
     */
    private $errorHandler;

    /**
     * @param string $path
     * @param bool   $debug
     */
    public function __construct($path, $debug)
    {
        $this->path  = (string) $path;
        $this->meta  = $this->path . '.meta';
        $this->debug = (bool) $debug;
    }

    /**
     * Checks if the cache is still fresh.
     *
     * This check should take the metadata passed to the write() method into consideration.
     *
     * @return bool Whether the cache is still fresh
     *
     * @throws ExceptionInterface
     */
    public function isFresh()
    {
        if (!$this->debug && is_file($this->path)) {
            return true;
        }

        if (!is_file($this->path) || !is_file($this->meta)) {
            return false;
        }

        $time = filemtime($this->path);

        try {
            $resources = $this->load();
        } catch (\Exception $ex) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
            // @codeCoverageIgnoreEnd
        }

        if (false === $resources) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        foreach ($resources as $resource) {
            if (!$resource->isFresh($time)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Writes the given content into the cache file. Metadata will be stored
     * independently and can be used to check cache freshness at a later time.
     *
     * @param string              $content   The content to write into the cache
     * @param ResourceInterface[] $resources An array of ResourceInterface instances
     *
     * @throws ExceptionInterface
     */
    public function write($content, array $resources = [])
    {
        foreach ($resources as $resource) {
            if (!($resource instanceof ResourceInterface)) {
                throw new UnexpectedValueException($resource, ResourceInterface::class);
            }
        }

        $mode = 0666;
        $mask = umask();

        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->path, (string) $content, null);

        try {
            $filesystem->chmod($this->path, $mode, $mask);
        } catch (IOException $e) {
            // discard chmod failure (some filesystem may not support it)
        }

        $filesystem->dumpFile($this->meta, serialize($resources), null);

        try {
            $filesystem->chmod($this->meta, $mode, $mask);
        } catch (IOException $e) {
            // discard chmod failure (some filesystem may not support it)
        }
    }

    /**
     * Try to load resources
     *
     * @return array
     *
     * @throws \UnexpectedValueException
     * @throws \Error
     * @throws \Exception
     *
     * @codeCoverageIgnore
     */
    private function load()
    {
        $ex                 = null;
        $resources          = false;
        $exception          = new \UnexpectedValueException();
        $unserializeHandler = ini_set('unserialize_callback_func', '');

        $this->errorHandler = null;
        $this->errorHandler = set_error_handler(function ($type, $msg, $file, $line, $context) use ($exception) {
            if (E_WARNING === $type && 'Class __PHP_Incomplete_Class has no unserializer' === $msg) {
                throw $exception;
            }

            return $this->errorHandler ? call_user_func($this->errorHandler, $type, $msg, $file, $line, $context) : false;
        });

        try {
            /* @var $resources ResourceInterface[] */
            $resources = unserialize(file_get_contents($this->meta));
        } catch (\Error $ex) {
        } catch (\Exception $ex) {
        }

        restore_error_handler();
        ini_set('unserialize_callback_func', $unserializeHandler);

        if (null !== $ex && $ex !== $exception) {
            throw $ex;
        }

        return $resources ?: [];
    }
}