<?php
/**
在github上学习的代码
 **/
namespace Loquat\Util;

class DistributedRecord
{//redis实现的分布式锁

    private $config;
    private $servers = array();
    private $instances = array();
    function __construct($config)
    {
        $this->config = $config;
    }
    public function set($resource, $val)
    {
        $this->initInstances();

        do {

            foreach ($this->instances as $instance) {
                if ($this->lockInstance($instance, $resource, $token, $ttl)) {
                    $n++;
                }
            }
            # Add 2 milliseconds to the drift to account for Redis expires
            # precision, which is 1 millisecond, plus 1 millisecond min drift
            # for small TTLs.
            $drift = ($ttl * $this->clockDriftFactor) + 2;
            $validityTime = $ttl - (microtime(true) * 1000 - $startTime) - $drift;
            if ($n >= $this->quorum && $validityTime > 0) {
                return [
                    'validity' => $validityTime,
                    'resource' => $resource,
                    'token'    => $token,
                ];
            } else {
                foreach ($this->instances as $instance) {
                    $this->unlockInstance($instance, $resource, $token);
                }
            }
            // Wait a random delay before to retry
            $delay = mt_rand(floor($this->retryDelay / 2), $this->retryDelay);
            usleep($delay * 1000);
            $retry--;
        } while ($retry > 0);
        return false;
    }
    public function get(array $lock)
    {
        $this->initInstances();
        $resource = $lock['resource'];
        $token    = $lock['token'];
        foreach ($this->instances as $instance) {
            $this->unlockInstance($instance, $resource, $token);
        }
    }
    private function initInstances()
    {
        if (empty($this->instances)) {
            foreach ($this->servers as $server) {
                $redis = new Redis($server);
                $redis->connect($host, $port, $timeout);
                $this->instances[] = $redis;
            }

        }
    }

}
