<?php
namespace Bravo3\Orm\Drivers\Filesystem\Io;

use Bravo3\Orm\Drivers\Filesystem\Enum\ArchiveType;
use Bravo3\Orm\Drivers\Filesystem\Enum\Compression;
use Bravo3\Orm\Exceptions\NotSupportedException;

/**
 * Tar/zip I/O driver with optional compression support for tar databases
 */
class PharIoDriver extends AbstractIoDriver
{
    /**
     * @var \PharData
     */
    protected $archive;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var Compression
     */
    protected $compression;

    /**
     * Create a tar or zip I/O driver
     *
     * @param string      $filename     Path to database file
     * @param ArchiveType $archive_type Tar or zip archive
     * @param Compression $compression  Compression not supported by zip archives
     */
    public function __construct($filename, ArchiveType $archive_type, Compression $compression = null)
    {
        $this->filename    = $filename;
        $this->archive     = new \PharData($filename, null, null, $archive_type->value());
        $this->compression = $compression ?: Compression::NONE();

        if ($archive_type == ArchiveType::ZIP() && $this->compression != Compression::NONE()) {
            throw new NotSupportedException("You cannot use compression with zip databases");
        }
    }

    /**
     * Archive cleanup
     */
    public function __destruct()
    {
        // Compress the archive on exit
        if ($this->compression != Compression::NONE()) {
            // Due to the way Phar works, it MUST create a new archive with a different extension. We will allow this,
            // then rename the new archive back to the original filename
            $ext = uniqid();
            $fn  = substr($this->filename, 0, strrpos($this->filename, '.') ?: null);
            $this->archive->compress((int)$this->compression->value(), $ext);
            rename($fn.".".$ext, $this->filename);
        }
    }

    /**
     * Write raw data to the archive
     *
     * @param string $key
     * @param string $data
     */
    public function write($key, $data)
    {
        $this->archive[$key] = $data;
    }

    /**
     * Read raw data from the archive, returning null if the file is not readable
     *
     * @param string $key
     * @return string|null
     */
    public function read($key)
    {
        if ($this->archive->offsetExists($key)) {
            return $this->archive[$key]->getContent();
        } else {
            return null;
        }
    }

    /**
     * Delete a key from the archive
     *
     * @param string $key
     */
    public function delete($key)
    {
        unset($this->archive[$key]);
    }

    /**
     * Check if a key exists in the archive
     *
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->archive->offsetExists($key);
    }

    /**
     * Get a list of keys in the archive
     *
     * @param string $base   Base path to list all keys from
     * @param string $filter Key filter, @see docs/Queries.md - Wildcards
     * @return string[]
     */
    public function scan($base, $filter)
    {
        $root       = (string)$this->archive[$base];
        $key_filter = $this->getKeyFilter();

        $out = [];
        foreach (scandir($root) as $file) {
            if (is_file($root.DIRECTORY_SEPARATOR.$file) && $key_filter->match($file, $filter)) {
                $out[] = $base.DIRECTORY_SEPARATOR.$file;
            }
        }

        return $out;
    }
}
