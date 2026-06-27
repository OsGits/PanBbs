<?php
/**
 * 静态资源版本控制
 * 统一管理 CSS/JS 版本号，更新版本号即可刷新浏览器缓存
 */

define('PANBBS_LOCAL_VERSION', 'v0.0.3.4');

/**
 * 获取远程最新版本号
 * 优先从 GitHub Releases 页面解析，失败则返回 null
 * 
 * 如果服务器无法访问 GitHub，可以手动设置以下常量：
 *   define('PANBBS_LATEST_VERSION', 'v0.0.3.5');
 * 取消上面注释并设为最新版本号即可，此时不会发起远程请求。
 * 
 * @return string|null 最新版本号，失败返回 null
 */
function getRemoteLatestVersion() {
    // 如果手动指定了最新版本，直接返回
    if (defined('PANBBS_LATEST_VERSION')) {
        return PANBBS_LATEST_VERSION;
    }

    $url = 'https://github.com/OsGits/PanBbs/releases/latest';

    $fetch = function($url) {
        // 优先用 cURL
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: PanBbs/1.0',
                    'Accept: text/html,application/xhtml+xml',
                ],
            ]);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            if ($code === 200 && $data !== false && strlen($data) > 100) {
                return $data;
            }
        }

        // 回退 file_get_contents
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: PanBbs/1.0\r\nAccept: text/html\r\n",
                'timeout' => 12,
                'follow_location' => 1,
                'max_redirects' => 3,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data !== false && strlen($data) > 100) {
            return $data;
        }

        return null;
    };

    $html = $fetch($url);
    if ($html === null) return null;

    // 从 GitHub Releases 页面提取版本号
    $patterns = [
        '#/releases/tag/(v[\d.]+)#i',
        '#/releases/expanded_assets/(v[\d.]+)#i',
        '#Release\s+(v[\d.]+)#i',
        '#tag/v([\d.]+)#i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $m)) {
            return 'v' . $m[1];
        }
    }

    return null;
}

return PANBBS_LOCAL_VERSION;