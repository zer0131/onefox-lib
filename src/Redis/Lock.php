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
    public function create($key, $lockTime = 3, $randomValue = '1') {
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

}
