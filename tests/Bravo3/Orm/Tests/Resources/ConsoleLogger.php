<?php
namespace Bravo3\Orm\Tests\Resources;

use Psr\Log\AbstractLogger;

class ConsoleLogger extends AbstractLogger
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        echo $message."\n";
    }
}
