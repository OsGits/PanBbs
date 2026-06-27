<?php
/**
 * 网盘资源聚合 - 触发脚本
 * 访问此文件触发从远程API拉取数据并存储到JSON文件
 * 循环覆盖存储，每种类型最多保留100条记录
 *
 * 调试模式：URL加 ?debug=1 可查看逐步排查信息
 */

header('Content-Type: application/json; charset=utf-8');

// 引入工具函数库
require_once __DIR__ . '/api.php';

// ========== 配置 ==========
$remoteApi   = 'http://127.0.0.1:8010/api/search?kw=&conc=30&res=all';
$dataDir     = __DIR__ . '/data';
// 从配置文件读取缓存网盘类型和最大记录数
define('SEO_ACCESS', true);
$config      = require __DIR__ . '/data/data.php';
$cachePans   = isset($config['cache_pans']) ? $config['cache_pans'] : '115,guangya,quark';
$targetTypes = array_filter(array_map('trim', explode(',', $cachePans)));
if (empty($targetTypes)) {
    $targetTypes = ['115', 'guangya', 'quark'];
}
$maxRecords  = isset($config['max_records']) ? (int)$config['max_records'] : 100;
if ($maxRecords < 1) $maxRecords = 100;

// ========== 调试模式 ==========
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($debug) {
    $steps = [];

    // 第1步：获取远程数据
    $rawData = fetchRemoteData($remoteApi);
    if ($rawData === null) {
        echo json_encode(['code' => -1, 'msg' => '无法获取远程数据'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $steps[] = ['step' => 1, 'desc' => '远程数据获取成功', 'length' => strlen($rawData)];

    // 第2步：JSON解码
    $decoded = json_decode($rawData, true);
    $jsonError = json_last_error();
    if ($decoded === null) {
        $steps[] = ['step' => 2, 'desc' => 'JSON解码失败', 'json_error' => json_last_error_msg()];
        echo json_encode([
            'code' => -1,
            'msg'  => '返回数据非有效JSON',
            'steps'=> $steps,
            'raw_head' => mb_substr($rawData, 0, 500),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    $steps[] = ['step' => 2, 'desc' => 'JSON解码成功', 'top_keys' => array_keys($decoded)];

    // 第3步：检查 data.results
    if (isset($decoded['data']['results'])) {
        $steps[] = ['step' => 3, 'desc' => 'data.results 存在', 'count' => count($decoded['data']['results'])];
    } else {
        $steps[] = ['step' => 3, 'desc' => 'data.results 不存在', 'data_keys' => isset($decoded['data']) ? array_keys($decoded['data']) : 'no data key'];
    }

    // 第4步：调用 parseRemoteData
    $parsed = parseRemoteData($rawData);
    $steps[] = ['step' => 4, 'desc' => 'parseRemoteData 结果', 'is_null' => ($parsed === null), 'is_empty' => empty($parsed), 'count' => is_array($parsed) ? count($parsed) : 'not_array'];

    // 第5步：如果成功，展示展平前2条
    if (is_array($parsed) && !empty($parsed)) {
        $steps[] = ['step' => 5, 'desc' => '前2条原始数据', 'sample' => array_slice($parsed, 0, 2)];
        $flat = flattenRecord($parsed[0], $targetTypes);
        $steps[] = ['step' => 6, 'desc' => '第1条展平结果', 'count' => count($flat), 'sample' => $flat];
    }

    echo json_encode($steps, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// ========== 执行（覆盖模式：完全替换旧数据） ==========
$result = fetchAndStore($remoteApi, $dataDir, $targetTypes, $maxRecords, true);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
