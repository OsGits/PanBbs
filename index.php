<?php
/**
 * 网盘资源聚合 - 首页路由入口
 * 仅作为模块转接接口，不包含数据抓取逻辑
 *
 * 路由规则：
 *   /                      → 默认展示模板页面
 *   /?a=api&type=xxx       → API接口返回JSON数据（本地存储）
 *   /?a=search&kw=xxx      → 搜索接口，直接拉取远程API数据并返回（不存储）
 */

// 引入工具函数库
require_once __DIR__ . '/api.php';

// 获取请求参数
$action = isset($_GET['a']) ? $_GET['a'] : 'home';

switch ($action) {
    // API接口：返回oss.json中的数据
    case 'api':
        header('Content-Type: application/json; charset=utf-8');
        $ossFile = __DIR__ . '/data/oss.json';
        $type    = isset($_GET['type']) ? trim($_GET['type']) : '';

        if (!file_exists($ossFile)) {
            echo json_encode(['code' => -1, 'msg' => '暂无数据'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = loadJsonFile($ossFile);

        if ($type !== '') {
            $result = isset($data[$type]) ? $data[$type] : [];
            echo json_encode([
                'code'  => 0,
                'msg'   => 'success',
                'type'  => $type,
                'count' => count($result),
                'data'  => $result,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $summary = [];
            foreach ($data as $t => $items) {
                $summary[$t] = count($items);
            }
            echo json_encode([
                'code'    => 0,
                'msg'     => 'success',
                'summary' => $summary,
                'data'    => $data,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        break;

    // 搜索接口：直接拉取远程API数据并返回（不存储到本地JSON）
    case 'search':
        header('Content-Type: application/json; charset=utf-8');

        $searchKw    = isset($_GET['kw']) ? trim($_GET['kw']) : '';
        $remoteApi   = 'http://127.0.0.1:8010/api/search?kw=' . urlencode($searchKw) . '&conc=30&res=all';

        // 支持前端传入 types 参数筛选网盘类型
        $allTypes = ['baidu','aliyun','quark','guangya','tianyi','uc','mobile','115','pikpak','xunlei','123','magnet','ed2k'];
        if (isset($_GET['types']) && $_GET['types'] !== '') {
            $targetTypes = array_intersect($allTypes, explode(',', $_GET['types']));
            $targetTypes = array_values($targetTypes); // 重置键名
        } else {
            $targetTypes = $allTypes; // 未指定 = 搜索全部网盘
        }
        if (empty($targetTypes)) {
            $targetTypes = $allTypes;
        }

        // 获取远程数据
        $rawData = fetchRemoteData($remoteApi);
        if ($rawData === null) {
            echo json_encode(['code' => -1, 'msg' => '无法获取远程数据'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 解析数据
        $allItems = parseRemoteData($rawData);
        if ($allItems === null) {
            echo json_encode(['code' => -1, 'msg' => '数据解析失败'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 展平并分类（不存储）
        $results = [];
        foreach ($allItems as $item) {
            $flatRecords = flattenRecord($item, $targetTypes);
            foreach ($flatRecords as $record) {
                $results[] = $record;
            }
        }

        // 按时间排序
        usort($results, function($a, $b) {
            return strcmp($b['add_time'], $a['add_time']);
        });

        echo json_encode([
            'code'    => 0,
            'msg'     => 'success',
            'kw'      => $searchKw,
            'count'   => count($results),
            'data'    => $results,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    // 版本接口：仅返回本地版本（最新版本由前端浏览器直接从 GitHub raw 获取）
    case 'version':
        header('Content-Type: application/json; charset=utf-8');
        $localVer = defined('PANBBS_LOCAL_VERSION') ? PANBBS_LOCAL_VERSION : 'unknown';
        echo json_encode([
            'code' => 0,
            'msg'  => 'success',
            'data' => [
                'local' => $localVer,
            ],
        ], JSON_UNESCAPED_UNICODE);
        break;

    // 默认：展示前端模板
    default:
        $templateFile = __DIR__ . '/template/home.php';
        if (file_exists($templateFile)) {
            require $templateFile;
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<h2>模板文件不存在</h2><p>请创建 template/home.php</p>';
        }
        break;
}
