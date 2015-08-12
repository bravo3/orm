<?php
namespace Bravo3\Orm\Drivers\Filesystem\Io;

use Bravo3\Orm\Drivers\Common\KeyFilter;
use Bravo3\Orm\Exceptions\NotSupportedException;

abstract class AbstractIoDriver implements IoDriverInterface
{
    private $key_filter = null;

    /**
     * Get a lazy-loaded key filter
     *
     * @return KeyFilter
     * @throws NotSupportedException in the event 'fnmatch' is not supported by the platform
     */
    protected function getKeyFilter()
    {
        if ($this->key_filter === null) {
            $this->key_filter = new KeyFilter();
        }

        return $this->key_filter;
    }
}
