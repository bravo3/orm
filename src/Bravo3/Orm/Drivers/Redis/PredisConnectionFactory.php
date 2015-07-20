<?php
namespace Bravo3\Orm\Drivers\Redis;

use Predis\Client;

class PredisConnectionFactory
{
    static $sentinel = null;

    /**
     * Build and return an instance of Predis Client.
     * If sentinel parameters are specified, a connection to Sentinel would be initialised
     * first and then find master slave configuration to be used for the Client.
     *
     * Note: First connection made to the Sentinel will be persisted and used for all
     * consequent function calls.
     *
     * @param  mixed  $params
     * @param  mixed  $options
     * @param  mixed  $sentinel_params
     * @return Client
     */
    public static function create($params = null, $options = null, $sentinel_params = null)
    {
        // If sentinel params are defined use sentinel to find out about
        // redis servers.
        if (!empty($sentinel_params)) {
            $slaves = [];

            if (null === self::$sentinel) {
                self::$sentinel = new SentinelMonitor($sentinel_params);
            }

            $masters = self::$sentinel->findMasters();

            if (!empty($masters)) {
                $slaves = self::$sentinel->sentinel->findSlaves();
            }

            // List of possible connections to redis instances
            $redis_connections = [];

            // Merge fixed connections to redis with discovered masters
            if (is_array($params)) {
                $redis_connections = array_merge(
                    $params,
                    $masters
                );
            }

            // Merge additional slaves discovered
            $redis_connections = array_merge(
                $redis_connections,
                $slaves
            );

            // Enable replication if slave can be found within the Redis configuration
            foreach ($redis_connections as $connection) {
                if (isset($connection['alias']) && 'slave' === $connection['alias']) {
                    $options = array_merge($options ?: [], ['replication' => true]);

                    break;
                }
            }

            return new Client($redis_connections, $options);
        } else {
            return new Client($params, $options);
        }

    }
}
