<?php
/**
 * @author ryan<zer0131@vip.qq.com>
 * @desc
 * 基于redis锁实现
 * redis扩展使用：phpredis<https://github.com/phpredis/phpredis>
 */

namespace Onefox\Lib\Redis;

class Lock {

    const LOCK_PREFIX = 'lock:';
    const DEFAULT_TTL = 60;//默认锁时间

    protected $lockValue;
    private $_redis;

    public function __construct() {
        $this->_redis = new Redis();
    }

    /**
     * 创建锁
     * @param string $key 避免与其他类型锁重复,防止冲突
     * @param int $lockTime 锁时效时长
     * @param string $randomValue 为了防止锁中代码执行时间过长,直到锁过期。误删下一个锁的key
     * @return bool
     */
    public function create($key, $lockTime = self::DEFAULT_TTL, $randomValue = '1') {
        $retSet = $this->_redis->set(self::LOCK_PREFIX . $key, $randomValue, ['nx', 'ex' => $lockTime]);
        if (!$retSet) {//存在返回 NULL
            if ($this->_redis->ttl($key) == -1) {
                $this->_redis->expire($key, $lockTime);
            }
            return false;
        }
        $this->_redis->expire($key, $lockTime);
        return true;
    }

    /**
     * 释放锁
     * @param string $key key
     * @param string $randomValue 与create的传值一致,否则删失败。
     * @return bool
     */
    public function release($key, $randomValue = '1') {
        if ($this->_redis->get(self::LOCK_PREFIX . $key) == $randomValue) {
            return $this->_redis->del($key);
        }
        return true;
    }

    /**
     * lua脚本实现锁
     * @param string $key
     * @param int $ttl 超时,默认60秒
     * @param null $value
     * @return bool|string  如果返回false，则锁定不成功；如果返回string，则为锁定成功的value,用于解锁
     */
    public function lock($key,$ttl = self::DEFAULT_TTL, $value = null) {
        $lock_lua = <<<LOCK
local key=KEYS[1]
local value=ARGV[1]
local ttl=ARGV[2]
if ttl == nil then
    ttl = 60 
end

local ret = redis.call("setnx",key,value)

if (ret == 0) then
    local key_ttl = redis.call('ttl', key)
    if (key_ttl > 0 ) then
        return 0
    else
        redis.call('set', key, value)
    end
end

redis.call('expire', key, ttl)

return 1
LOCK;

        if( $value === null) {
            $value = uniqid().'_'.time();
        }

        $ret = $this->_redis->eval($lock_lua, [$key, $value, $ttl], 1);

        if($ret === 0 || $ret === false) {
            return false;
        }
        $this->lockValue = $value;
        return $value;
    }

    /**
     * lua脚本实现解锁
     * @param $key
     * @param null $value
     * @return bool
     */
    public function unlock($key, $value = null) {
        $unlock_lua = <<<UNLOCK
local key=KEYS[1]
local value=ARGV[1]

if key == nil or value == nil then
    return 0
end

if redis.call('exists', key) == 0 then
    return 1
end

if (redis.call('get', key) == value) then
    return redis.call('del', key)
else
    return 0
end
UNLOCK;

        if($value === null) {
            $value = $this->lockValue;
        }

        $ret = $this->_redis->eval($unlock_lua, [$key, $value], 1);

        if($ret === 0 || $ret === false) {
            return false;
        }

        $this->lockValue = null;
        return true;
    }

}
