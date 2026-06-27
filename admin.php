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
session_start();
define('ADMIN_ACCESS', true);

$dataConfig    = require __DIR__ . '/data/data.php';
$adminAccounts = $dataConfig['accounts'];
$seoConfig     = $dataConfig['seo'];
$apiBaseUrl    = isset($dataConfig['api_base_url']) ? $dataConfig['api_base_url'] : 'http://127.0.0.1:8010';
$searchTypes   = isset($dataConfig['search_types']) ? $dataConfig['search_types'] : 'baidu,aliyun,quark,guangya,tianyi,uc,mobile,115,pikpak,xunlei,123,magnet,ed2k';
$cachePans     = $dataConfig['cache_pans'];
$maxRecords    = isset($dataConfig['max_records']) ? $dataConfig['max_records'] : 100;
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
$currentUser   = $_SESSION['admin_username'];

// ========== 页面路由分发 ==========
$page = isset($_GET['a']) ? $_GET['a'] : 'dashboard';

switch ($page) {
    case 'settings':
        adminShowSettings($currentUser, $localVersion, $seoConfig, $apiBaseUrl, $searchTypes, $cachePans, $maxRecords);
        break;

    case 'version':
        adminShowVersion($currentUser, $localVersion);
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

        adminShowDashboard($dataStats, $totalRecords, $localVersion, $remoteVersion, $currentUser);
        break;
}

