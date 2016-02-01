<?php
namespace hzkoala\DevTool;

use Illuminate\Support\Facades\Cache;

final class IOTool {

    /**
     * 获取真实IP
     *
     * @return string|null
     */
    public static function getIP() {
        $dataList = [
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER["HTTP_CLIENT_IP"],
            $_SERVER['REMOTE_ADDR'],
        ];

        $ip = null;
        foreach ($dataList as $data) {
            if (isset($data) && $data && strcasecmp($data, 'unknown')) {
                if (strpos($data, ',') !== FALSE) {
                    $ip = explode(',', $data)[0];
                } else {
                    $ip = $data;
                }
                break;
            }
        }

        return $ip;
    }


    /**
     * Request By NameService
     *
     * @param string $system
     * @param string $service
     * @param array $request
     * @param int $retry
     * @param int $cacheTime
     * @param string $cacheKey
     * @return mixed
     */
    public static function requestByNameService($system, $service, $request = array(), $retry = 0, $cacheTime = 0, $cacheKey = '') {
        # cache
        // 有需要则取缓存
        if ($cacheTime) {
            $cacheKey = $cacheKey ?: "{$system}-{$service}-" . md5(json_encode($request));
            if ($cacheData = Cache::get($cacheKey)) {
                return $cacheData;
            }
        }

        # action
        $req = array(
            'params' => json_encode(array(
                'trace_id' => $GLOBALS['trace_id'] ?: GlobalTool::generateTraceId(),
                'data' => $request
            ))
        );

        $startTime = microtime(TRUE);
        do {
            $response = NameService::request($system, $service, $req);
            $isRetry = !$response && $retry--;
        } while ($isRetry);
        $endTime = microtime(TRUE);
        LogTool::logApi($system, $service, $endTime - $startTime, $request, $response);

        # return
        if (!$response) {
            return FALSE;
        }

        $response = json_decode($response, TRUE);
        if (!is_array($response) || !$response['status']) {
            return FALSE;
        } else {
            // 有需要则存储缓存
            if ($cacheTime) {
                Cache::put($cacheKey, $response['data'], $cacheTime);
            }
            return $response['data'] ?: TRUE;
        }
    }


    /**
     * Request By Http
     *
     * @param string $system
     * @param string $service
     * @param array $request
     * @param int $retry
     * @param int $cacheTime
     * @param string $cacheKey
     * @param string $method
     * @return mixed
     */
    public static function request($system, $service, $request = array(), $retry = 0, $cacheTime = 0, $cacheKey = '', $method = 'get') {
        # cache
        // 有需要则取缓存
        if ($cacheTime) {
            $cacheKey = $cacheKey ?: "{$system}-{$service}-" . md5(json_encode($request));
            if ($cacheData = Cache::get($cacheKey)) {
                return $cacheData;
            }
        }

        # data
        $startTime = microtime(TRUE);
        $url = \Config::get("api.http.{$system}.{$service}");
        do {
            $response = self::httpRequest($url, $method, $request);
            $isRetry = !$response && $retry--;
        } while ($isRetry);
        $endTime = microtime(TRUE);
        LogTool::logApi($system, $service, $endTime - $startTime, array(
            'url' => $url,
            'params' => $request
        ), $response);

        # return
        if (!$response) {
            return FALSE;
        } else {
            // 有需要则存储缓存
            if ($cacheTime) {
                Cache::put($cacheKey, $response, $cacheTime);
            }
            return $response;
        }
    }


    /**
     * HTTP请求
     *
     * @param string $url
     * @param string $method
     * @param array $fields
     * @param array $curlSets
     * @return mixed
     */
    public static function httpRequest($url, $method, $fields = [], $curlSets = []) {
        # action
        $ch = curl_init();

        if (strtolower($method) == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        } else {
            if (strpos($url, '?') !== FALSE) {
                foreach ($fields as $k => $v) {
                    $url .= "&{$k}={$v}";
                }
            } else {
                $url .= '?';
                foreach ($fields as $k => $v) {
                    $url .= "{$k}={$v}&";
                }
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0');

        // 额外参数设置
        foreach ($curlSets as $k => $v) {
            curl_setopt($ch, $k, $v);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        # return
        return $result;
    }


    /**
     * 标准Api返回参数
     *
     * @param mixed $status
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return array
     */
    public static function ApiReturn($status, $data = [], $msg = '', $code = null) {
        $status = boolval($status);

        return [
            'status' => $status,
            'code' => $status ? 0 : $code,
            'msg' => $status ? '成功' : $msg,
            'data' => $data,
        ];
    }


    /**
     * 表单输入片段
     *
     * @param array $attr
     * @param string $name
     * @param string|number $value
     * @return string
     */
    public static function InputSnippet($attr, $name, $value = null) {
        return view('module.input', ['attr' => $attr, 'name' => $name, 'value' => $value])->render();
    }


    /**
     * UDP通信
     *
     * @param string $ip
     * @param int $port
     * @param string $msg
     * @return mixed
     */
    public static function udp($ip, $port, $msg) {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 1, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 1, 'usec' => 0]);
        @socket_sendto($socket, $msg, strlen($msg), 0, $ip, $port);
        @socket_recvfrom($socket, $res, 1024, 0, $ip, $port);
        socket_close($socket);

        return $res;
    }


    /**
     * TCP通信
     *
     * @param string $ip
     * @param int $port
     * @param string $msg
     * @return mixed
     */
    public static function tcp($ip, $port, $msg) {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 3, 'usec' => 0]);
        socket_connect($socket, $ip, $port);
        socket_write($socket, $msg, strlen($msg));
        $res = socket_read($socket, 1024);
        socket_close($socket);

        return $res;
    }
}
