<?php

/**
 * @author ryan<zer0131@vip.qq.com>
 * @desc
 * 基于redis实现session
 * 可用于客户端会话维护,sessionid可以作为token使用
 */

namespace Onefox\Lib\Redis;

class Session {

    const TTL = 604800;//默认有效期1周

    private $_prefix = 'sess:';//redis存储前缀
    private $_redis;

    public function __construct($prefix = '') {
        $prefix && $this->_prefix = $prefix;
        $this->_redis = new Redis();
    }

    /**
     * 生成session
     * @param array $data
     * @param int $ttl
     * @param string $suffix
     * @return string
     */
    public function generate(array $data, $ttl = self::TTL, $suffix = '') {
        if (!is_array($data) || empty($data)) {
            return '';
        }
        $sessionId = $this->generateSessionId($suffix);
        $key = $this->_prefix.$sessionId;
        if ($this->_redis->set($key, json_encode($data))) {
            if ($this->_redis->expireAt($key, time() + $ttl)) {
                return $sessionId;
            }
            $this->_redis->del($key);
        }
        return '';
    }

    //生成sessionid
    public function generateSessionId($suffix = '') {
        $sessionId = uniqid(microtime(true), true);
        $suffix && $sessionId .= '.'.$suffix;
        return md5($sessionId);
    }

    /**
     * 延长session有效期
     * @param string $sessionId
     * @param int $ttl
     * @return boolean
     */
    public function extendedTime($sessionId, $ttl = self::TTL) {
        $key = $this->_prefix.$sessionId;
        if ($this->_redis->exists($key)) {
            if ($this->_redis->expireAt($key, time() + $ttl)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取session数据
     * @param string $sessionId
     * @return boolean|array
     */
    public function getSessionData($sessionId) {
        $key = $this->_prefix.$sessionId;
        if ($this->_redis->exists($key)) {
            $ret = $this->_redis->get($key);
            return json_decode($ret, true);
        }
        return false;
    }

    /**
     * 判断session是否存在
     * @param string $sessionId
     * @return type
     */
    public function isExists($sessionId) {
        $key = $this->_prefix.$sessionId;
        return $this->_redis->exists($key);
    }
}
