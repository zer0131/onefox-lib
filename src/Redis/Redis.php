<?php

/**
 * @author ryan<zer0131@vip.qq.com>
 * @desc 
 * redis缓存类
 * 基于phpredis<https://github.com/phpredis/phpredis>
 */

namespace Onefox\Lib\Redis;

class Redis {

    private $_redis;
    private $options = [
        'expire' => 0,
        'server' => [
            'host' => '127.0.0.1',
            'port' => 6379
        ]
    ];

    public function __construct($conf = []) {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('The redis extension must be loaded.');
        }
        if (!is_array($conf)) {
            throw new \RuntimeException('The config parameter must be array.');
        }
        $conf && $this->options = array_merge($this->options, $conf);
        $this->_connect();
    }

    private function _connect() {
        $this->_redis = new \Redis();
        $this->_redis->connect($this->options['server']['host'], $this->options['server']['port']);
    }

    public function get($key) {
        if (!$this->_redis) {
            $this->_connect();
        }
        return $this->_redis->get($key);
    }

    public function set($key, $value, $expire = NULL) {
        if ($this->_redis) {
            $this->_connect();
        }
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        if (intval($expire) === 0) {
            return $this->_redis->set($key, $value);
        } else {
            return $this->_redis->setEx($key, intval($expire), $value);
        }
    }

    public function rm($key, $ttl = 0) {
        if (!$this->_redis) {
            $this->_connect();
        }
        return $this->_redis->delete($key);
    }

    public function clear() {
        if (!$this->_redis) {
            $this->_connect();
        }
        return $this->_redis->flushAll();
    }

    public function __call($funcName, $arguments) {
        if (!$this->_redis) {
            $this->_connect();
        }
        $res = call_user_func_array([
            $this->_redis,
            $funcName
        ], $arguments);
        return $res;
    }

    public function __destruct() {
        if ($this->_redis) {
            $this->_redis->close();
            $this->_redis = NULL;
        }
    }
}
