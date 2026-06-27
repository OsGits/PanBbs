<?php
/**
 * 版本信息 - 统一入口
 * 定义本地版本和远程版本常量，所有页面统一从此文件获取版本号
 */

// ========== 本地版本 ==========
define('PANBBS_LOCAL_VERSION', 'V2606.2721.5038');

// ========== 远程版本（从 GitHub Releases 实时获取） ==========
$remoteVersion = null;

$url = 'https://api.github.com/repos/OsGits/PanBbs/releases/latest';
$ctx = stream_context_create([
    'http' => [
        'timeout'    => 10,
        'user_agent' => 'PanBbs/1.0',
    ],
]);
$data = @file_get_contents($url, false, $ctx);
if ($data === false && function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PanBbs/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);
}

if ($data !== false) {
    $json = json_decode($data, true);
    $remoteVersion = isset($json['tag_name']) ? $json['tag_name'] : null;
}

define('PANBBS_REMOTE_VERSION', $remoteVersion);

return PANBBS_LOCAL_VERSION;
