<?php
/**
 * CURL 工具类
 * Http.php
 * User: wlq314@qq.com
 * Date: 16/8/26 Time: 13:51
 */

class Http{

    /**
     * @param $url
     * @param array $header
     * @return mixed
     */
    public static function get($url, $header = []){
        $ch = curl_init();
        $opt = array(
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 0,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => $header,
        );
        curl_setopt_array($ch, $opt);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * @param $url
     * @param $data
     * @param array $header
     * @return mixed
     */
    public static function post($url, $data, $header = []){
        $ch = curl_init();
        $opt = array(
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 0,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_HEADER => 0
        );
        curl_setopt_array($ch, $opt);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

}
