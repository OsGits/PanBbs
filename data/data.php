<?php
/**
 * =============================================
 *  安全警告：此文件包含敏感配置信息
 *  请勿将此文件提交到公开版本控制系统
 * =============================================
 *
 * 后台管理员账号配置 & 前端 SEO 配置
 * 部署后请立即修改默认账号密码
 */

// 防止直接访问：仅允许后台(ADMIN_ACCESS)或前端SEO读取(SEO_ACCESS)
if (!defined('ADMIN_ACCESS') && !defined('SEO_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

// ========== 管理员账号配置 ==========
// 支持多个账号，格式：'用户名' => '密码'
// 部署后请务必修改默认密码！
$accounts = [
    'admin' => 'admin123',  // 默认账号，请修改
    // 如需添加更多账号，取消下面注释：
    // 'user2' => 'password2',
];

// ========== 前端 SEO 配置 ==========
$seo = [
    'title'       => '网盘资源聚合',
    'keywords'    => '网盘,资源,聚合,PanBbs',
    'description' => '网盘资源聚合搜索平台，快速发现您需要的资源',
];

// ========== 接口设置 ==========
// API 接口地址，结尾不加斜杠
$apiBaseUrl = 'http://127.0.0.1:8010';
// 搜索页可搜索的网盘类型，半角逗号分隔
$searchTypes = 'baidu,aliyun,quark,guangya,tianyi,uc,mobile,115,pikpak,xunlei,123,magnet,ed2k';

// ========== 前端默认色彩 ==========
// 'light' = 日间模式, 'dark' = 夜间模式
$defaultTheme = 'light';

// ========== 缓存设置 ==========
// 需要从远程API缓存到本地json的网盘类型，半角逗号分隔
$cachePans = '115,guangya,quark';
// 每种类型最大缓存记录数
$maxRecords = 100;

return [
    'accounts'      => $accounts,
    'seo'           => $seo,
    'api_base_url'  => $apiBaseUrl,
    'search_types'  => $searchTypes,
    'cache_pans'    => $cachePans,
    'max_records'   => $maxRecords,
    'default_theme' => $defaultTheme,
];
