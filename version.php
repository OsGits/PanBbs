<?php
/**
 * 版本信息 - 统一入口
 * 定义本地版本和远程版本常量，所有页面统一从此文件获取版本号
 */

// ========== 本地版本 ==========
define('PANBBS_LOCAL_VERSION', 'V2606.2720.5633');

// ========== 远程版本（从 GitHub Releases 获取，1小时缓存） ==========
$remoteVersion = null;
$cacheFile = __DIR__ . '/data/version_cache.txt';
$cacheTime = 3600; // 缓存1小时

// 先尝试读取缓存
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    $cached = @file_get_contents($cacheFile);
    if ($cached !== false && trim($cached) !== '') {
        $remoteVersion = trim($cached);
    }
}

// 缓存过期或不存在，从 GitHub API 获取
if ($remoteVersion === null) {
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
        // 写入缓存
        if ($remoteVersion !== null) {
            @file_put_contents($cacheFile, $remoteVersion, LOCK_EX);
        }
    }
}

define('PANBBS_REMOTE_VERSION', $remoteVersion);

return PANBBS_LOCAL_VERSION;
