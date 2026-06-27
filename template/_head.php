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
$assetVer = require __DIR__ . '/../version.php';
$localVersion = defined('PANBBS_LOCAL_VERSION') ? PANBBS_LOCAL_VERSION : 'unknown';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网盘资源聚合</title>
    <link rel="stylesheet" href="<?= $basePath ?>/style.css?<?= $assetVer ?>">
</head>
<body>
