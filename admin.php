<?php
/**
 * 网盘资源聚合 - 后台管理入口
 * 纯路由分发，具体逻辑和模板分散在 admin/ 目录下
 *
 * 路由规则：
 *   /admin.php              → 后台首页（控制面板）
 *   /admin.php?a=settings   → 系统设置
 *   /admin.php?a=version    → 版本更新
 *   /admin.php?logout=1     → 退出登录
 */

// ========== 基础初始化 ==========
ob_start();
session_start();
define('ADMIN_ACCESS', true);

$dataConfig    = require __DIR__ . '/data/data.php';
$adminAccounts = $dataConfig['accounts'];
$seoConfig     = $dataConfig['seo'];
$apiBaseUrl    = isset($dataConfig['api_base_url']) ? $dataConfig['api_base_url'] : 'http://127.0.0.1:8010';
$searchTypes   = isset($dataConfig['search_types']) ? $dataConfig['search_types'] : 'baidu,aliyun,quark,guangya,tianyi,uc,mobile,115,pikpak,xunlei,123,magnet,ed2k';
$cachePans     = $dataConfig['cache_pans'];
$maxRecords    = isset($dataConfig['max_records']) ? $dataConfig['max_records'] : 100;
$defaultTheme  = isset($dataConfig['default_theme']) ? $dataConfig['default_theme'] : 'light';
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/version.php';

// ========== 加载后台模块 ==========
require_once __DIR__ . '/admin/auth.php';
require_once __DIR__ . '/admin/actions.php';
require_once __DIR__ . '/admin/page_login.php';
require_once __DIR__ . '/admin/page_dashboard.php';
require_once __DIR__ . '/admin/page_settings.php';
require_once __DIR__ . '/admin/page_version.php';

// ========== 登出处理 ==========
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ========== 登录验证 ==========
$loginResult = adminCheckLogin($adminAccounts);

if ($loginResult !== true) {
    $errorMsg = is_array($loginResult) ? $loginResult['error'] : '';
    adminShowLoginPage($errorMsg);
    // adminShowLoginPage 内部已 exit
}

// ========== 处理 AJAX 操作请求 ==========
$ossFile = __DIR__ . '/data/oss.json';
adminHandleActions($ossFile, $adminAccounts);
// 如果是 AJAX 请求，adminHandleActions 内部已 exit

// ========== 读取公共数据 ==========
$localVersion  = PANBBS_LOCAL_VERSION;
$remoteVersion = PANBBS_REMOTE_VERSION;
$remoteZipUrl  = defined('PANBBS_REMOTE_ZIP_URL') ? PANBBS_REMOTE_ZIP_URL : null;
$releaseBody   = defined('PANBBS_RELEASE_BODY') ? PANBBS_RELEASE_BODY : null;
$currentUser   = $_SESSION['admin_username'];

// ========== 获取远程 README 内容 ==========
function fetchRemoteReadme() {
    $url = 'https://raw.githubusercontent.com/OsGits/PanBbs/main/README.md';
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'PanBbs-Admin/1.0']]);
    $content = @file_get_contents($url, false, $ctx);
    if ($content === false && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'PanBbs-Admin/1.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $content = curl_exec($ch);
        curl_close($ch);
    }
    return $content !== false ? $content : null;
}

/**
 * 简易 Markdown → HTML 转换
 */
function simpleMarkdownToHtml($md) {
    $html = htmlspecialchars($md, ENT_QUOTES, 'UTF-8');
    $lines = explode("\n", $html);

    $out = '';
    $inCodeBlock = false;
    $inTable = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // 代码块
        if (preg_match('/^```/', $trimmed)) {
            if ($inCodeBlock) {
                $out .= "</code></pre>\n";
                $inCodeBlock = false;
            } else {
                $out .= "<pre><code>";
                $inCodeBlock = true;
            }
            continue;
        }
        if ($inCodeBlock) {
            $out .= $line . "\n";
            continue;
        }

        // 表格
        if (preg_match('/^\|(.+)\|$/', $trimmed)) {
            if (!$inTable) {
                $out .= "<table>\n";
                $inTable = true;
            }
            $cells = array_map('trim', explode('|', trim($trimmed, '|')));
            $isHeader = preg_match('/^[\s\-:]+$/', implode('', $cells));
            if ($isHeader) {
                // 分隔行，跳过
                continue;
            }
            // 判断是否为表头行（下一个有效行是分隔行）
            $tag = 'td';
            $out .= "<tr>";
            foreach ($cells as $cell) {
                $out .= "<{$tag}>" . trim($cell) . "</{$tag}>";
            }
            $out .= "</tr>\n";
            continue;
        } elseif ($inTable) {
            $out .= "</table>\n";
            $inTable = false;
        }

        // 标题
        if (preg_match('/^######\s+(.+)/', $trimmed, $m)) {
            $out .= "<h6>{$m[1]}</h6>\n";
        } elseif (preg_match('/^#####\s+(.+)/', $trimmed, $m)) {
            $out .= "<h5>{$m[1]}</h5>\n";
        } elseif (preg_match('/^####\s+(.+)/', $trimmed, $m)) {
            $out .= "<h4>{$m[1]}</h4>\n";
        } elseif (preg_match('/^###\s+(.+)/', $trimmed, $m)) {
            $out .= "<h3>{$m[1]}</h3>\n";
        } elseif (preg_match('/^##\s+(.+)/', $trimmed, $m)) {
            $out .= "<h2>{$m[1]}</h2>\n";
        } elseif (preg_match('/^#\s+(.+)/', $trimmed, $m)) {
            $out .= "<h1>{$m[1]}</h1>\n";
        }
        // 无序列表
        elseif (preg_match('/^[\-\*]\s+(.+)/', $trimmed, $m)) {
            $out .= "<li>{$m[1]}</li>\n";
        }
        // 空行
        elseif ($trimmed === '') {
            $out .= "<br>\n";
        }
        // 水平线
        elseif (preg_match('/^[\-\*_]{3,}$/', $trimmed)) {
            $out .= "<hr>\n";
        }
        // 普通段落
        else {
            $out .= "<p>{$trimmed}</p>\n";
        }
    }

    if ($inCodeBlock) $out .= "</code></pre>\n";
    if ($inTable) $out .= "</table>\n";

    // 后处理：行内格式
    // 粗体 **text** 或 __text__
    $out = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $out);
    $out = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $out);
    // 行内代码 `code`
    $out = preg_replace('/`([^`]+)`/', '<code class="inline-code">$1</code>', $out);
    // 链接 [text](url)
    $out = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $out);

    return $out;
}

// ========== 获取远程 README 内容（实时拉取） ==========
$readmeHtml = null;
$readmeContent = fetchRemoteReadme();
if ($readmeContent !== null) {
    $readmeHtml = simpleMarkdownToHtml($readmeContent);
}

// ========== 页面路由分发 ==========
$page = isset($_GET['a']) ? $_GET['a'] : 'dashboard';

switch ($page) {
    case 'settings':
        adminShowSettings($currentUser, $localVersion, $seoConfig, $apiBaseUrl, $searchTypes, $cachePans, $maxRecords, $defaultTheme);
        break;

    case 'version':
        adminShowVersion($currentUser, $localVersion, $remoteVersion, $remoteZipUrl, $releaseBody);
        break;

    case 'dashboard':
    default:
        $ossData = loadJsonFile($ossFile);
        $dataStats = [];
        $totalRecords = 0;
        foreach ($ossData as $type => $records) {
            $count = is_array($records) ? count($records) : 0;
            $dataStats[$type] = $count;
            $totalRecords += $count;
        }

        adminShowDashboard($dataStats, $totalRecords, $localVersion, $remoteVersion, $currentUser, $readmeHtml);
        break;
}

