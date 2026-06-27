<?php
/**
 * 网盘资源聚合 - 接口工具文件
 * 提供数据获取、解析、存储等工具函数
 * 不直接触发，由 ting.php 调用
 */

/**
 * 修复非法UTF-8字符
 */
function fixUtf8($data) {
    // 方案1：用UTF-8方式编码，忽略/替换非法字符
    $fixed = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
    if ($fixed !== false) {
        return $fixed;
    }
    // 方案2：iconv 转换
    if (function_exists('iconv')) {
        $fixed = @iconv('UTF-8', 'UTF-8//IGNORE', $data);
        if ($fixed !== false) {
            return $fixed;
        }
    }
    return $data;
}

/**
 * 从远程API获取数据（自动修复UTF-8编码问题）
 */
function fetchRemoteData($url) {
    $data = @file_get_contents($url);
    if ($data === false) {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $data = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || $data === false) {
                return null;
            }
        } else {
            return null;
        }
    }

    // 修复非法UTF-8字符
    if ($data !== null) {
        $data = fixUtf8($data);
    }

    return $data;
}

/**
 * 解析远程返回的数据，兼容多种JSON结构
 * 支持 data.results / data.list / list 等结构
 */
function parseRemoteData($rawData) {
    $json = json_decode($rawData, true);
    if ($json === null) {
        return null;
    }

    // data.results
    if (isset($json['data']['results']) && is_array($json['data']['results'])) {
        return $json['data']['results'];
    }
    // data.list
    if (isset($json['data']['list']) && is_array($json['data']['list'])) {
        return $json['data']['list'];
    }
    // list
    if (isset($json['list']) && is_array($json['list'])) {
        return $json['list'];
    }
    // data (可能直接是数组)
    if (isset($json['data']) && is_array($json['data'])) {
        $dataVal = $json['data'];
        if (array_keys($dataVal) === range(0, count($dataVal) - 1)) {
            return $dataVal;
        }
    }
    // 根级就是数组
    if (is_array($json) && array_keys($json) === range(0, count($json) - 1)) {
        return $json;
    }

    return [];
}

/**
 * 清除字符串中的URL链接，只保留纯文本
 * 支持 http/https 链接，以及纯域名形式的链接
 */
function stripUrls($text) {
    if (!is_string($text) || $text === '') {
        return $text;
    }
    // 移除 http:// 或 https:// 开头的链接
    $text = preg_replace('/https?:\/\/[^\s]*/i', '', $text);
    // 清理多余空格
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

/**
 * 将原始记录展平为多条链接记录
 * 一条原始记录可能有多个links，每个link生成一条独立记录
 *
 * @param array $item  原始记录（含title/content/tags/links）
 * @param array $targetTypes 需要保留的type列表
 * @return array 展平后的记录数组
 */
function flattenRecord($item, $targetTypes) {
    $records = [];
    $title   = isset($item['title']) ? stripUrls(fixUtf8($item['title'])) : '';
    $content = isset($item['content']) ? stripUrls(fixUtf8($item['content'])) : '';
    $tags    = isset($item['tags']) ? $item['tags'] : [];
    // tags转为逗号分隔的字符串，并清除其中的链接
    if (is_array($tags)) {
        $tags = implode(',', $tags);
    }
    $tags = stripUrls($tags);
    $links = isset($item['links']) ? $item['links'] : [];

    if (empty($links) || !is_array($links)) {
        return $records;
    }

    foreach ($links as $link) {
        if (!is_array($link)) continue;
        $linkType = isset($link['type']) ? strtolower(trim($link['type'])) : '';
        // 只保留目标类型
        if (!in_array($linkType, $targetTypes)) continue;

        $records[] = [
            'title'    => $title,
            'url'      => isset($link['url']) ? $link['url'] : '',
            'password' => isset($link['password']) ? $link['password'] : '',
            'tags'     => $tags,
            'content'  => $content,
            'type'     => $linkType,
            'add_time' => date('Y-m-d H:i:s'),
        ];
    }

    return $records;
}

/**
 * 加载已有的JSON数据，文件不存在或为空则返回空数组
 */
function loadJsonFile($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    $content = @file_get_contents($filePath);
    if ($content === false || trim($content) === '') {
        return [];
    }
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * 循环覆盖保存数据到JSON文件
 * 新数据插入到前面，超出maxRecords则截断最早的记录
 * 自动创建不存在的目录和文件
 */
function saveJsonFile($filePath, $newRecords, $existingRecords, $maxRecords) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    // 仅按url去重
    $existingUrls = [];
    foreach ($existingRecords as $record) {
        if (!empty($record['url'])) {
            $existingUrls[$record['url']] = true;
        }
    }

    $uniqueNewRecords = [];
    foreach ($newRecords as $record) {
        $url = isset($record['url']) ? $record['url'] : '';
        if ($url === '' || isset($existingUrls[$url])) {
            continue; // 空url或已存在则跳过
        }
        $uniqueNewRecords[] = $record;
        $existingUrls[$url] = true; // 防止新数据内部重复
    }

    // 新数据在前，旧数据在后（最新在前）
    $merged = array_merge($uniqueNewRecords, $existingRecords);

    // 只保留最新的maxRecords条，丢弃最早的记录
    if (count($merged) > $maxRecords) {
        $merged = array_slice($merged, 0, $maxRecords);
    }

    $jsonStr = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $result = file_put_contents($filePath, $jsonStr, LOCK_EX);
    if ($result !== false) {
        @chmod($filePath, 0777);
    }
    return $result;
}

/**
 * 从远程API获取并存储数据到统一的 oss.json
 * 每个type各自保留maxRecords条，合并存储到单个文件
 *
 * @param string $remoteApi   远程API地址
 * @param string $dataDir     数据存储目录
 * @param array  $targetTypes 目标分类类型
 * @param int    $maxRecords  每种类型最大记录数
 * @return array 执行结果
 */
function fetchAndStore($remoteApi, $dataDir, $targetTypes, $maxRecords) {
    // 确保数据目录存在
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
    }

    // 获取远程数据
    $rawData = fetchRemoteData($remoteApi);
    if ($rawData === null) {
        return ['code' => -1, 'msg' => '无法获取远程数据'];
    }

    // 解析数据
    $allItems = parseRemoteData($rawData);
    if ($allItems === null) {
        return ['code' => -1, 'msg' => '数据解析失败'];
    }

    // 展平 + 按类型分类
    $classified = [];
    foreach ($targetTypes as $type) {
        $classified[$type] = [];
    }

    $otherCount = 0;
    foreach ($allItems as $item) {
        $flatRecords = flattenRecord($item, $targetTypes);
        if (empty($flatRecords)) {
            $otherCount++;
            continue;
        }
        foreach ($flatRecords as $record) {
            $classified[$record['type']][] = $record;
        }
    }

    // 加载已有的 oss.json
    $ossFile = $dataDir . '/oss.json';
    $existingOss = loadJsonFile($ossFile);
    if (!is_array($existingOss)) {
        $existingOss = [];
    }

    $saveResult = [];

    // 对每个type做去重合并
    foreach ($targetTypes as $type) {
        $newRecords     = $classified[$type];
        $existingRecords = isset($existingOss[$type]) ? $existingOss[$type] : [];

        $saveResult[$type] = [
            'new_count'    => count($newRecords),
            'before_count' => count($existingRecords),
        ];

        // 仅按url去重
        $existingUrls = [];
        foreach ($existingRecords as $record) {
            if (!empty($record['url'])) {
                $existingUrls[$record['url']] = true;
            }
        }

        $uniqueNew = [];
        foreach ($newRecords as $record) {
            $url = isset($record['url']) ? $record['url'] : '';
            if ($url === '' || isset($existingUrls[$url])) {
                continue;
            }
            $uniqueNew[] = $record;
            $existingUrls[$url] = true;
        }

        // 新数据在前 + 旧数据在后，截断到maxRecords
        $merged = array_merge($uniqueNew, $existingRecords);
        if (count($merged) > $maxRecords) {
            $merged = array_slice($merged, 0, $maxRecords);
        }

        $existingOss[$type] = $merged;
        $saveResult[$type]['write_success'] = true;
        $saveResult[$type]['after_count']  = count($merged);
    }

    // 写入 oss.json
    $jsonStr = json_encode($existingOss, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $writeResult = file_put_contents($ossFile, $jsonStr, LOCK_EX);
    if ($writeResult !== false) {
        @chmod($ossFile, 0777);
    }

    return [
        'code' => 0,
        'msg'  => 'success',
        'data' => [
            'total_fetched'   => count($allItems),
            'other_types'     => $otherCount,
            'classify_result' => $saveResult,
            'max_records'     => $maxRecords,
        ],
    ];
}
