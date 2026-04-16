<?php

function test($str = 'world')
{
    return 'Hello ' . $str;
}

function dda($model)
{
    if (method_exists($model, 'toArray')) {
        dd($model->toArray());
    } else {
        dd($model);
    }
}

function is_activity(array $array)
{
    $curRouteName = Illuminate\Support\Facades\Route::currentRouteName();
    $curRouteNameArray = explode('.', $curRouteName);
    $curRouteName = array_shift($curRouteNameArray);
    if (in_array($curRouteName, $array, true)) {
        return 'active';
    }
    return '';
}

// 返回软链接里的文件的绝对路径
function storageUrl($value, $way = 0)
{
    if (!$value || !is_string($value)) {
        return '';
    }

    if (Illuminate\Support\Str::startsWith($value, ['http://', 'https://'])) {
        return $value;
    }

    if (config('filesystems.use_oss') && $way == 1 && (preg_match("/meet\//i", $value) ||
        preg_match("/moment_video_thumbnail\//i", $value) || (preg_match("/moment\//i", $value)))) {
        return App\Services\Oss::signUrl($value);
    }

    return url($value);
}

// 逆向去掉文件的前缀
function reverseStorageUrl($value)
{
    if (!$value || !is_string($value)) {
        return false;
    }

    // 先去掉后边的时间戳参数 . 匹配除换行符\n以外的任何字符
    $value = preg_replace('/\?.*/', '', $value);

    $begin = strpos($value, 'storage/upload/');
    if ($begin && $begin > 0) {
        return substr($value, $begin);
    }
    return $value;
}

/**
 * @param string $sessionKey 小程序 sessionKey
 * @param string $appid 小程序 appid
 * @param string $iv 加密算法的初始向量
 * @param string $encryptedData 包括敏感数据在内的完整用户信息的加密数据
 * @return array
 * 参考：https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/signature.html#%E5%8A%A0%E5%AF%86%E6%95%B0%E6%8D%AE%E8%A7%A3%E5%AF%86%E7%AE%97%E6%B3%95
 * 参考：https://developers.weixin.qq.com/miniprogram/dev/api/wx.getUserInfo.html
 */
function decryptWXMiniData($sessionKey, $appid, $iv, $encryptedData)
{
    if (strlen($sessionKey) != 24) {
        return ['errors' => ['message' => -41001]];
    }
    $aesKey = base64_decode($sessionKey);

    if (strlen($iv) != 24) {
        return ['errors' => ['message' => -41002]];
    }
    $aesIV = base64_decode($iv);

    $aesCipher = base64_decode($encryptedData);

    $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

    $dataObj = json_decode($result, true);
    if ($dataObj  == null) {
        return ['errors' => ['message' => -41003]];
    }
    if ($dataObj['watermark']['appid'] != $appid) {
        return ['errors' => ['message' => -41004]];
    }
    return $dataObj;
}

// 微信小程序敏感字检测
function msgSecCheck($content, $id, $nickname)
{
    $access_token = getWxMiniAccessToken(config('auth.wxmini.appid'), config('auth.wxmini.secret'));

    $url = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=' . $access_token;

    $data = json_encode(array('content' => $content), JSON_UNESCAPED_UNICODE);

    $result = curl($url, $data, true, true);

    $result = json_decode($result, true);

    Illuminate\Support\Facades\Log::channel('words_check')->info('words_check', ['content' => $content, 'id' => $id, 'nickname' => $nickname, 'data' => $data, 'result' => $result]);

    if ($result['errcode'] != 0) {
        return false;
    }
    return true;
}

// 获取 AccessToken (小程序被刷新的危险)
function getWxMiniAccessToken($appid, $appsecret)
{
    $key = sprintf('accesstoken-%s', $appid);

    if (Illuminate\Support\Facades\Redis::exists($key)) {
        return Illuminate\Support\Facades\Redis::get($key);
    }

    $url = sprintf('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s', $appid, $appsecret);

    $res = file_get_contents($url);
    if ($res === false) {
        Illuminate\Support\Facades\Log::error(sprintf('appid:%s, 获取accessToken 失败。', $appid));
        return false;
    }

    $res = json_decode($res, true);
    if (isset($res['access_token'])) {
        Illuminate\Support\Facades\Redis::setex($key, $res['expires_in'] - 200, $res['access_token']);
        return $res['access_token'];
    }

    if (is_array($res)) {
        Illuminate\Support\Facades\Log::error(sprintf('appid:%s,获取accessToken 失败。 errmsg:%d, errmsg:%s', $appid, $res['errcode'], $res['errmsg']));
        return false;
    }
}

function cleanWxMiniAccessToken($appid)
{
    $key = sprintf('accesstoken-%s', $appid);

    Illuminate\Support\Facades\Redis::del($key);
}

// 获取微信短链接
// https://api.weixin.qq.com/wxa/genwxashortlink?access_token=ACCESS_TOKEN
function getWxMiniShortLink($url, $title = '', $is_permanent = false)
{
    $access_token = getWxMiniAccessToken(config('auth.wxmini.appid'), config('auth.wxmini.secret'));
    if (!$access_token) {
        return null;
    }

    $data = [
        'page_url' => $url,
        'page_title' => $title,
        'is_permanent' => $is_permanent,

    ];

    $url = 'https://api.weixin.qq.com/wxa/genwxashortlink?access_token=' . $access_token;

    $result = curl($url, json_encode($data), true, true);

    if ($result) {
        $result = json_decode($result, true);
        if (isset($result['link'])) {
            return $result['link'];
        } else {
            return null;
        }
    }
    return null;
}

/**
 * @param string $url 请求网址
 * @param bool $params 请求参数
 * @param bool $post 请求方式，是否是post
 * @param bool $https 请求http协议，是否是https
 * @return bool|mixed
 */
function curl($url, $params = false, $post = false, $https = false)
{
    $httpInfo = array();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($post === true) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
        if (gettype($params) == 'string') {
            $header = array('Content-Type: application/json', 'Content-Length: ' . strlen($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
    } else {
        // 临时加的，后边删掉这个玩意
        if (isset($params['sign']) && isset($params['time'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['sign' . ':' . $params['sign'], 'time' . ':' . $params['time']]);
        }

        if ($params === false) {
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if (is_array($params)) {
                $params = http_build_query($params);
            }
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        }
    }

    if ($https === true) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
    }
    $response = curl_exec($ch);
    if ($response === false) {
        Illuminate\Support\Facades\Log::error(sprintf('curl 错误。 url:%s, error:%s', $url, curl_error($ch)));
        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
    curl_close($ch);
    return $response;
}

// 通过经纬度获取城市信息
function getCityByLL($longitude, $latitude)
{
    $url = "https://apis.map.qq.com/ws/geocoder/v1/?location={$latitude},{$longitude}&key=DGDBZ-VGPK6-2HWSO-EMCWA-OZDP6-BSFTF&get_poi=1";
    $res = curl($url, false, false, true);
    if ($res) {
        $res = json_decode($res, true);
    }
    $data = [];
    if ($res && $res['status'] == 0 && $res['result'] && $res['result']['ad_info']) {
        $data['latitude'] =  $latitude;
        $data['longitude'] =  $longitude;
        $data['province'] =  $res['result']['ad_info']['province'] ?? '';
        $data['city'] =  $res['result']['ad_info']['city'] ?? '';
        $data['district'] =  $res['result']['ad_info']['district'] ?? '';
        $data['citycode'] =  $res['result']['ad_info']['adcode'] ?? '';

        if ($res['result']['address_reference']) {
            if (isset($res['result']['address_reference']['landmark_l2']) && isset($res['result']['address_reference']['landmark_l2']['title'])) {
                $data['address'] = $res['result']['address_reference']['landmark_l2']['title'];
            } elseif ($res['result']['address']) {
                $data['address'] = $res['result']['address'];
            }
        }
    }
    return $data;
}

// 通过 ip 获取城市信息
function getCityByIp($ip)
{
    $url = "https://apis.map.qq.com/ws/location/v1/ip?ip={$ip}&key=DGDBZ-VGPK6-2HWSO-EMCWA-OZDP6-BSFTF";

    $res = curl($url, false, false, true);
    if ($res) {
        $res = json_decode($res, true);
    }
    $data = [];
    if ($res && $res['status'] == 0 && $res['result']) {
        $data['latitude'] =  $res['result']['location']['lat'];
        $data['longitude'] =  $res['result']['location']['lng'];

        if (isset($res['result']['ad_info']) && $res['result']['ad_info']) {
            $data['province'] =  $res['result']['ad_info']['province'] ?? '';
            $data['city'] =  $res['result']['ad_info']['city'] ?? '';
            $data['district'] =  $res['result']['ad_info']['district'] ?? '';
            $data['citycode'] =  $res['result']['ad_info']['adcode'] ?? '';
        }

        if (isset($res['result']['address_reference']) && isset($res['result']['address_reference']['town'])) {
            $data['towncode'] =  $res['result']['address_reference']['town']['id'];
        }

        if (isset($res['result']['address_reference'])) {
            if (isset($res['result']['address_reference']['landmark_l2']) && isset($res['result']['address_reference']['landmark_l2']['title'])) {
                $data['address'] = $res['result']['address_reference']['landmark_l2']['title'];
            } elseif ($res['result']['address']) {
                $data['address'] = $res['result']['address'];
            }
        }
    }
    return $data;
}

// 生成随机字符串
function randStr($len)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
    $string = "";
    for ($i = 0; $i < $len; $i++) {
        $string .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $string;
}

// 将百分比转换成小数
function percentageToFloat($percentage)
{
    // 移除百分号并转换为浮点数
    $number = floatval(str_replace("%", "", $percentage));
    // 检查是否为有效数字
    if ($number === 0 && $percentage !== "0%") {
        throw new InvalidArgumentException("Invalid percentage format");
    }
    return $number / 100;
}

function getAccessToken($appid, $appsecret)
{
    $key = sprintf('accesstoken-%s', $appid);

    if (Illuminate\Support\Facades\Redis::exists($key)) {
        return Illuminate\Support\Facades\Redis::get($key);
    }

    $url = sprintf('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s', $appid, $appsecret);

    try {
        $response = Illuminate\Support\Facades\Http::timeout(10)->get($url);
        if (!$response->successful()) {
            Illuminate\Support\Facades\Log::error(sprintf('appid:%s, 获取accessToken 接口请求失败，状态码：%d', $appid, $response->status()));
            return false;
        }
        $res = $response->json();
    } catch (\Exception $e) {
        Illuminate\Support\Facades\Log::error(sprintf('appid:%s, 获取accessToken 网络请求异常：%s', $appid, $e->getMessage()));
        return false;
    }

    if (!is_array($res)) {
        Illuminate\Support\Facades\Log::error(sprintf('appid:%s, 获取accessToken 返回非JSON格式：%s', $appid, $response->body()));
        return false;
    }

    if (isset($res['access_token'])) {
        Illuminate\Support\Facades\Redis::setex($key, $res['expires_in'] - 200, $res['access_token']);
        return $res['access_token'];
    }
    return false;
}

function ossToPath($url)
{
    if (!$url) return '';
    $parse = parse_url($url);
    return $parse['path'] ?? ''; // 直接返回 /zhitanbaoguan/...
}

function pathToOss($url)
{
    if (empty($path)) return '';
    // 如果已经是完整 URL，直接返回
    if (str_starts_with($path, 'http')) {
        return $path;
    }
    return 'https://ztbg-oss.17vision.com' . ltrim($path, '/');
}
