<?php

namespace hzkoala\DevTool;
final class GlobalTool {

    /**
     * 检查异常
     *
     * @param mixed $item
     * @param string $msg
     * @param int $code
     * @throws \Exception
     */
    public static function checkException($item, $msg = '未定义错误', $code = 0) {
        if(!$item) {
            throw new \Exception($msg, $code);
        }
    }


    /**
     * 生成TraceId
     *
     * @return string
     */
    public static function generateTraceId() {
        return md5(microtime(TRUE));
    }


    /**
     * Debug变量
     *
     * @param mixed $data
     * @param string $debugMode
     * @return bool
     */
    public static function debugData($data, $debugMode = 'print_r') {
        // 线上只能使用log模式
        if($GLOBALS['env'] == 'online' && $debugMode != 'log') {
            return FALSE;
        }

        // 不同类型
        switch($debugMode) {
            case 'var_dump':
                {
                    var_dump($data);
                    break;
                }
            case 'print_r':
                {
                    print_r($data);
                    break;
                }
            case 'log':
            default:
                {
                    LogTool::log('debug/debug', $data);
                    break;
                }
        }
    }


    /**
     * 设置数组Key
     *
     * @param $array
     * @param $key
     * @param bool $isSort
     * @return mixed
     */
    public static function setArrayKey($array, $key, $isSort = FALSE) {
        $arr = array();
        foreach($array as $v) {
            $arr[trim($v[$key])] = $v;
        }
        if($isSort) {
            ksort($arr);
        }

        return $arr ?: $array;
    }


    /**
     * 从Url中截取域名
     *
     * @param string $url
     * @return string
     */
    public static function getDomainFromUrl($url) {
        $ret = preg_match('~(^|/{2})([\w\.]+)($|/)~', $url, $match);

        return $ret ? $match[2] : FALSE;
    }


    /**
     * 按数组键值过滤唯一
     *
     * @param array $array
     * @param string $key
     * @return array
     */
    public static function uniqueArrayByKey($array, $key) {
        $existList = [];

        foreach($array as $k => &$v) {
            if(in_array($v[$key], $existList)) {
                unset($array[$k]);
            } else {
                $existList[] = $v[$key];
            }
        }

        return $array;
    }
}