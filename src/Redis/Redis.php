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
    private $_options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'ctimeout' => 1000,//超时时间单位ms
    ];

    public function __construct($conf = []) {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('The redis extension must be loaded.');
        }
        if (!is_array($conf)) {
            throw new \RuntimeException('The config parameter must be array.');
        }
        $conf && $this->_options = array_merge($this->_options, $conf);
        $this->_connect();
    }

    private function _connect() {
        $this->_redis = new \Redis();
        $this->_redis->connect($this->_options['host'], $this->_options['port'], $this->_options['ctimeout'] / 1000);
    }

    public function __call($funcName, $arguments) {
        if (!$this->_redis) {
            $this->_connect();
        }
        $res = call_user_func_array([$this->_redis, $funcName], $arguments);
        return $res;
    }

    public function __destruct() {
        if ($this->_redis) {
            $this->_redis->close();
            $this->_redis = NULL;
        }
    }
}
