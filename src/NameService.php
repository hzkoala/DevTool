<?php
namespace hzkoala\DevTool;
class NameService {
    const CacheTime = 15; // 缓存10分钟


    /**
     * 通过名字服务进行请求
     *
     * @param string $system
     * @param string $service
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public static function request($system, $service, $params) {
        # check
        GlobalTool::checkException($system, '必须参数: $system');
        GlobalTool::checkException($service, '必须参数: $service');

        # data
        $cacheKey = "NameServiceCache-{$system}";

        // 获取ServiceList
        if(!$serviceList = \Cache::get($cacheKey)) {
            $serviceList = self::getRemoteServiceList($system);
            GlobalTool::checkException($serviceList, '获取ServiceList失败');
            \Cache::put($cacheKey, $serviceList, self::CacheTime);
        }
        $serviceUrl = self::getRemoteUrl($system) . '/' . $serviceList[$service];

        # return
        return IOTool::httpRequest($serviceUrl, 'POST', $params);
    }


    protected static function getRemoteServiceList($system) {
        $url = self::getRemoteUrl($system);
        $url .= '/api/list';
        $ret = file_get_contents($url);
        GlobalTool::checkException($ret, '请求ServiceList失败');

        return json_decode($ret, TRUE);
    }


    /**
     * 获取本地服务列表
     *
     * @return array
     */
    public static function getLocalServiceList() {
        $routes = \Route::getRoutes();
        foreach($routes as $r) {
            $ret[$r->getName()] = $r->uri();
        }

        return $ret;
    }


    protected static function getRemoteUrl($system) {
        $config = \Config::get('api.ns');
        GlobalTool::checkException($config, 'NameService配置文件不存在');

        return $config[$system];
    }
}