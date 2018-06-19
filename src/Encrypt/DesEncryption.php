<?php

/**
 * @author ryan
 * @desc Des加解密类
 */

namespace Onefox\Lib\Encrypt;

class DesEncryption {


    /**
     * 加密
     * @param $text string 文本内容
     * @param $key string 秘钥 max 24
     * @return string
     */
    public static function encrypt($text,$key) {
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_TRIPLEDES,MCRYPT_MODE_ECB), MCRYPT_RAND);
        $text = self::_pkcs5Pad($text);
        $td = mcrypt_module_open(MCRYPT_3DES,'',MCRYPT_MODE_ECB,'');

        @mcrypt_generic_init($td,$key,$iv);
        $data = base64_encode(mcrypt_generic($td, $text));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $data;
    }

    /**
     * 解密
     * @param  [type] $text [description]
     * @param  [type] $key  [description]
     * @return [type]       [description]
     */
    public static function decrypt($text,$key) {
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_TRIPLEDES,MCRYPT_MODE_ECB), MCRYPT_RAND);
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
        @mcrypt_generic_init($td, $key, $iv);
        $data = self::_pkcs5UnPad(mdecrypt_generic($td, base64_decode($text)));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $data;
    }

    /**
     * @return string
     * @param  [type] $text [description]
     * @return [type]       [description]
     */
    private static function _pkcs5Pad($text) {
        $pad = 8 - (strlen($text) % 8);
        return $text . str_repeat(chr($pad), $pad);
    }
    /**
     * @param $text
     * @return bool|string
     */
    private static function _pkcs5UnPad($text) {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)) return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
        return substr($text, 0, -1 * $pad);
    }
}
