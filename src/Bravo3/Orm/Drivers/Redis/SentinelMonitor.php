<?php
namespace Bravo3\Orm\Drivers\Redis;

use Predis\Client;

class SentinelMonitor
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * Create a new Sentinel Monitor.
     *
     * If more than one sentinel server parameters are provided, the first
     * sentinel that connects will be used for retrival of information.
     *
     * @param mixed $connection_params
     */
    public function __construct($connection_params = null)
    {
        foreach ($connection_params as $connection) {
            $this->client = new Client($connection);

            // Usable connection is found
            if ($this->client->isConnected()) {
                break;
            }
        }
    }

    /**
     * Return an array of SENTINEL servers in the form that can be
     * passed to Predis Client.
     *
     * @param  string $master_name
     *
     * @return array
     */
    public function findSentinels($master_name = 'mymaster')
    {
        return $this->client->sentinel('sentinels', $master_name);
    }

    /**
     * Return an array of SLAVE servers in the form that can be
     * passed to Predis Client.
     *
     * If $master_name parameter is not set this function will return
     * slaves attached to all connected master servers.
     *
     * @param  string $master_name
     *
     * @return array
     */
    public function findSlaves($master_name = null)
    {
        $slaves = [];

        if (null !== $master_name) {
            $slaves = $this->client->sentinel('slaves', $master_name);
        } else {
            // Find out slaves attached to each master instance
            foreach ($this->findMasters() as $master) {
                $name   = $master['name'];
                $slaves = array_merge(
                    $slaves,
                    $this->client->sentinel('slaves', $name)
                );
            }
        }

        return $this->getConnectionParams($slaves, 'slave');
    }

    /**
     * Return an array of MASTER servers in the form that can be
     * passed to Predis Client.
     *
     * @return array
     */
    public function findMasters()
    {
        return $this->getConnectionParams(
            $this->client->sentinel('masters'),
            'master'
        );
    }

    /**
     * Return an array of formatted output from Sentinel to be used
     * as Redis connection parameters for the Predis client.
     *
     * Inactive connection parameters will be removed if sentinel reports
     * services as inactive.
     *
     * @param  array  $sentinel_output
     * @param  string $alias            Redis DB connection type "master" or "slave"
     *
     * @return array|null
     */
    protected function getConnectionParams($sentinel_output, $alias = null)
    {
        $connections = [];

        foreach ($sentinel_output as $params) {
            $connection = [
                'name' => $params['name'],
                'host' => $params['ip'],
                'port' => $params['port'],
            ];

            if (!empty($alias)) {
                $connection['alias'] = $alias;
            }

            // Validate connection params adding
            if ($this->validateConnection($params)) {
                $connections[] = $connection;
            }
        }

        return $connections;
    }

    /**
     * Function returns true if connection to redis host is
     * accessible based on the output received from Sentinel.
     *
     * @param  array $host_info
     *
     * @return bool
     */
    protected function validateConnection($host_info)
    {
        if ('master' === $host_info['role-reported']) {
            // Verify reported master is up based on parameters discovered
            // by sentinel
            $down_after_interval = (int) $host_info['down-after-milliseconds'];
            $last_ok_since       = (int) $host_info['last-ok-ping-reply'];

            if ($last_ok_since > $down_after_interval) {
                return false;
            }
        } elseif ('slave' === $host_info['role-reported']) {

            // If slave status is disconnected ignore
            if (!(false === strpos($host_info['flags'], 'disconnected'))) {
                return false;
            }

            // If slave is subjectively down ignore
            // Reference: http://redis.io/topics/sentinel#pubsub-messages
            if (!(false === strpos($host_info['flags'], 's_down'))) {
                return false;
            }

            // If slave is objectively down ignore
            if (!(false === strpos($host_info['flags'], 'o_down'))) {
                return false;
            }
        }

        return true;
    }
}
