<?php
namespace Bravo3\Orm\Drivers\Filesystem\Io;

class NativeIoDriver extends AbstractIoDriver
{
    /**
     * @var string
     */
    protected $data_dir;

    /**
     * @var int
     */
    protected $umask;

    /**
     * @param string $data_dir Base directory or filename for database
     * @param int    $umask    Filesystem umask
     */
    public function __construct($data_dir, $umask = 0660)
    {
        $this->setDataDir($data_dir);
        $this->umask = $umask;
    }

    /**
     * Get DataDir
     *
     * @return string
     */
    public function getDataDir()
    {
        return $this->data_dir;
    }

    /**
     * Set DataDir
     *
     * @param string $data_dir
     * @return $this
     */
    public function setDataDir($data_dir)
    {
        if (DIRECTORY_SEPARATOR == '/') {
            $data_dir = str_replace('\\', '/', $data_dir);
        } else {
            $data_dir = str_replace('/', '\\', $data_dir);
        }

        if (substr($data_dir, -1) != DIRECTORY_SEPARATOR) {
            $data_dir .= DIRECTORY_SEPARATOR;
        }

        $this->data_dir = $data_dir;
        return $this;
    }

    /**
     * Set Umask
     *
     * @param int $umask
     * @return $this
     */
    public function setUmask($umask)
    {
        $this->umask = $umask;
        return $this;
    }

    /**
     * Get Umask
     *
     * @param bool $directory True if you need a umask for a directory (executable)
     * @return int
     */
    public function getUmask($directory = false)
    {
        if ($directory) {
            return $this->addExecuteBit($this->umask);
        } else {
            return $this->umask;
        }
    }

    /**
     * Adds the execute bit (001) to an octal trio of RWX bits where each trio has the read (100) bit
     *
     * @param $umask
     * @return int
     */
    private function addExecuteBit($umask)
    {
        for ($trio = 0; $trio < 3; $trio++) {
            $r = 4 * pow(8, $trio);
            $x = 1 * pow(8, $trio);

            if (($umask | $r) == $umask) {
                $umask = $umask | $x;
            }
        }

        return $umask;
    }

    /**
     * Get a filename for a key, validating the directory exists
     *
     * @param string $key          Object key
     * @param bool   $validate_dir Set to false to skip directory creation
     * @return string
     */
    private function keyToFilename($key, $validate_dir = true)
    {
        $fn = $this->data_dir.$key;

        if ($validate_dir) {
            $dir = dirname($fn);

            if (!is_dir($dir)) {
                mkdir(dirname($fn), $this->getUmask(true), true);
            }
        }

        return $fn;
    }

    /**
     * Write raw data to the filesystem
     *
     * @param string $key
     * @param string $data
     */
    public function write($key, $data)
    {
        $filename = $this->keyToFilename($key);

        if (file_exists($filename)) {
            // Write file, maintaining permissions
            file_put_contents($filename, $data);
        } else {
            // Write file and set the requested umask
            file_put_contents($filename, $data);
            chmod($filename, $this->getUmask());
        }
    }

    /**
     * Read raw data from the filesystem, returning null if the file is not readable
     *
     * @param string $key
     * @return string|null
     */
    public function read($key)
    {
        $filename = $this->keyToFilename($key, false);

        if ($this->isReadableFile($filename)) {
            return file_get_contents($filename);
        } else {
            return null;
        }
    }

    /**
     * Check if a key exists on the interface
     *
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        return $this->isReadableFile($this->keyToFilename($key, false));
    }

    /**
     * Test if $filename is a file and is readable
     *
     * @param string $filename
     * @return bool
     */
    private function isReadableFile($filename)
    {
        return is_readable($filename) && !is_dir($filename);
    }

    /**
     * Delete a key
     *
     * @param string $key
     */
    public function delete($key)
    {
        $filename = $this->keyToFilename($key);

        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * Get a list of keys on the filesystem
     *
     * @param string $base   Base path to list all keys from
     * @param string $filter Key filter, @see docs/Queries.md - Wildcards
     * @return \string[]
     */
    public function scan($base, $filter)
    {
        $file_base  = $this->data_dir.$base;
        $key_filter = $this->getKeyFilter();

        $out = [];
        foreach (scandir($file_base) as $file) {
            if (is_file($file_base.DIRECTORY_SEPARATOR.$file) && $key_filter->match($file, $filter)) {
                $out[] = $base.DIRECTORY_SEPARATOR.$file;
            }
        }

        return $out;
    }
}
