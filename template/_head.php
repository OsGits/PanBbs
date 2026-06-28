<?php
/**
 * 模块：HTML头部 + 数据准备
 * 由 home.php 引用
 */

$ossFile = __DIR__ . '/../data/oss.json';
$ossData = file_exists($ossFile) ? loadJsonFile($ossFile) : [];

// 合并 + 排序
$allList = [];
foreach ($ossData as $typeItems) {
    foreach ($typeItems as $item) {
        $allList[] = $item;
    }
}
usort($allList, function($a, $b) {
    $ta = isset($a['add_time']) ? $a['add_time'] : '';
    $tb = isset($b['add_time']) ? $b['add_time'] : '';
    return strcmp($tb, $ta);
});

$keyword = isset($_GET['kw']) ? trim($_GET['kw']) : '';

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/template';
if (!defined('PANBBS_LOCAL_VERSION')) {
    $assetVer = require __DIR__ . '/../version.php';
} else {
    $assetVer = PANBBS_LOCAL_VERSION;
}
$localVersion = PANBBS_LOCAL_VERSION;

// 读取 SEO 配置（前端页面也需要访问）
define('SEO_ACCESS', true);
$seoConfig = require __DIR__ . '/../data/data.php';
$seoTitle       = isset($seoConfig['seo']['title']) ? $seoConfig['seo']['title'] : '网盘资源聚合';
$seoKeywords    = isset($seoConfig['seo']['keywords']) ? $seoConfig['seo']['keywords'] : '';
$seoDescription = isset($seoConfig['seo']['description']) ? $seoConfig['seo']['description'] : '';
$defaultTheme   = isset($seoConfig['default_theme']) ? $seoConfig['default_theme'] : 'light';
?>
<!DOCTYPE html>
<html lang="zh-CN" class="theme-<?= $defaultTheme === 'dark' ? 'dark' : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seoTitle) ?></title>
    <meta name="keywords" content="<?= htmlspecialchars($seoKeywords) ?>">
    <meta name="description" content="<?= htmlspecialchars($seoDescription) ?>">
    <link rel="stylesheet" href="<?= $basePath ?>/style.css?<?= $assetVer ?>">
</head>
<body>
