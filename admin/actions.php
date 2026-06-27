<?php
/**
 * 后台 - AJAX 操作处理模块
 * 被 admin.php 引用，不直接访问
 */

if (!defined('ADMIN_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

/**
 * 处理所有管理操作的 AJAX 请求
 * @param string $ossFile     oss.json 文件路径
 * @param array  $accounts    管理员账号列表（用于修改密码时验证）
 */
function adminHandleActions($ossFile, $accounts) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
        return;
    }

    // 清除之前可能产生的任何输出（如 PHP warning），确保 JSON 纯净
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    switch ($_POST['action']) {
        // 清空某类型数据
        case 'clear_type':
            $type = isset($_POST['type']) ? trim($_POST['type']) : '';
            if ($type === '') {
                echo json_encode(['code' => -1, 'msg' => '未指定类型'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $data = loadJsonFile($ossFile);
            if (isset($data[$type])) {
                unset($data[$type]);
                $jsonStr = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                file_put_contents($ossFile, $jsonStr, LOCK_EX);
                echo json_encode(['code' => 0, 'msg' => "已清空 {$type} 类型数据"], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['code' => -1, 'msg' => "{$type} 类型不存在"], JSON_UNESCAPED_UNICODE);
            }
            exit;

        // 清空全部数据
        case 'clear_all':
            file_put_contents($ossFile, '{}', LOCK_EX);
            echo json_encode(['code' => 0, 'msg' => '已清空全部数据'], JSON_UNESCAPED_UNICODE);
            exit;

        // 手动触发数据抓取
        case 'fetch_data':
            // 从配置文件读取 API 地址、缓存网盘类型和最大记录数
            $config      = require __DIR__ . '/../data/data.php';
            $apiBaseUrl  = isset($config['api_base_url']) ? $config['api_base_url'] : 'http://127.0.0.1:8010';
            $remoteApi   = $apiBaseUrl . '/api/search?kw=&conc=30&res=all';
            $dataDir     = dirname($ossFile);
            $cachePans   = isset($config['cache_pans']) ? $config['cache_pans'] : '115,guangya,quark';
            $targetTypes = array_filter(array_map('trim', explode(',', $cachePans)));
            if (empty($targetTypes)) {
                $targetTypes = ['115', 'guangya', 'quark'];
            }
            $maxRecords  = isset($config['max_records']) ? (int)$config['max_records'] : 100;
            if ($maxRecords < 1) $maxRecords = 100;

            $result = fetchAndStore($remoteApi, $dataDir, $targetTypes, $maxRecords);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;

        // 修改密码
        case 'change_password':
            adminHandleChangePassword($accounts);
            exit;

        // 在线更新
        case 'online_update':
            adminHandleOnlineUpdate();
            exit;

        // 保存 SEO 配置
        case 'save_seo':
            adminHandleSaveSeo();
            exit;

        // 保存接口设置
        case 'save_api':
            adminHandleSaveApi();
            exit;

        // 保存缓存设置
        case 'save_cache':
            adminHandleSaveCache();
            exit;

        default:
            echo json_encode(['code' => -1, 'msg' => '未知操作'], JSON_UNESCAPED_UNICODE);
            exit;
    }
}

/**
 * 处理密码修改
 */
function adminHandleChangePassword($accounts) {
    $oldPwd     = isset($_POST['old_password']) ? trim($_POST['old_password']) : '';
    $newPwd     = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirmPwd = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    $currentUser = $_SESSION['admin_username'];

    if ($oldPwd === '' || $newPwd === '' || $confirmPwd === '') {
        echo json_encode(['code' => -1, 'msg' => '请填写所有密码字段'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($accounts[$currentUser] !== $oldPwd) {
        echo json_encode(['code' => -1, 'msg' => '原密码不正确'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($newPwd !== $confirmPwd) {
        echo json_encode(['code' => -1, 'msg' => '两次输入的新密码不一致'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (strlen($newPwd) < 6) {
        echo json_encode(['code' => -1, 'msg' => '新密码长度不能少于6位'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 更新配置文件中的密码
    $configFile    = __DIR__ . '/../data/data.php';
    $configContent = file_get_contents($configFile);
    $escapedUser   = preg_quote($currentUser, '/');
    $pattern       = "/('{$escapedUser}'\s*=>\s*)'[^']*'/";
    $replacement   = "$1'{$newPwd}'";
    $newContent    = preg_replace($pattern, $replacement, $configContent);

    if ($newContent !== null && $newContent !== $configContent) {
        file_put_contents($configFile, $newContent, LOCK_EX);
        echo json_encode(['code' => 0, 'msg' => '密码修改成功，下次登录时生效'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['code' => -1, 'msg' => '密码修改失败，请手动修改 data/data.php'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 处理版本号更新
 */
function adminHandleUpdateVersion() {
    $newVersion = isset($_POST['new_version']) ? trim($_POST['new_version']) : '';

    if ($newVersion === '') {
        echo json_encode(['code' => -1, 'msg' => '版本号不能为空'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 版本号格式简单校验
    if (!preg_match('/^v?\d+\.\d+\.\d+/', $newVersion)) {
        echo json_encode(['code' => -1, 'msg' => '版本号格式不正确，示例: v0.0.7.0'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $versionFile = __DIR__ . '/../version.php';
    $content = file_get_contents($versionFile);
    $pattern = "/define\('PANBBS_LOCAL_VERSION',\s*'[^']*'\);/";
    $replacement = "define('PANBBS_LOCAL_VERSION', '{$newVersion}');";
    $newContent = preg_replace($pattern, $replacement, $content);

    if ($newContent !== null && $newContent !== $content) {
        file_put_contents($versionFile, $newContent, LOCK_EX);
        echo json_encode(['code' => 0, 'msg' => "版本号已更新为 {$newVersion}"], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['code' => -1, 'msg' => '版本号更新失败，请手动修改 version.php'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 处理 SEO 配置保存
 */
function adminHandleSaveSeo() {
    $title       = isset($_POST['seo_title']) ? trim($_POST['seo_title']) : '';
    $keywords    = isset($_POST['seo_keywords']) ? trim($_POST['seo_keywords']) : '';
    $description = isset($_POST['seo_description']) ? trim($_POST['seo_description']) : '';

    if ($title === '') {
        echo json_encode(['code' => -1, 'msg' => 'Title 不能为空'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $configFile    = __DIR__ . '/../data/data.php';
    $configContent = @file_get_contents($configFile);

    if ($configContent === false) {
        echo json_encode(['code' => -1, 'msg' => '无法读取配置文件'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 安全转义：防止单引号破坏 PHP 语法
    $safeTitle       = str_replace("'", "\\'", $title);
    $safeKeywords    = str_replace("'", "\\'", $keywords);
    $safeDescription = str_replace("'", "\\'", $description);

    // 替换 title（匹配 $seo 数组中 'title' => '...' 的值部分）
    $count = 0;
    $configContent = preg_replace(
        "/('title'\s*=>\s*)'[^']*'/",
        "\$1'{$safeTitle}'",
        $configContent,
        -1,
        $count
    );
    if ($configContent === null || $count === 0) {
        echo json_encode(['code' => -1, 'msg' => 'Title 替换失败，请检查配置文件格式'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 替换 keywords
    $configContent = preg_replace(
        "/('keywords'\s*=>\s*)'[^']*'/",
        "\$1'{$safeKeywords}'",
        $configContent
    );
    if ($configContent === null) {
        echo json_encode(['code' => -1, 'msg' => 'Keywords 替换失败'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 替换 description
    $configContent = preg_replace(
        "/('description'\s*=>\s*)'[^']*'/",
        "\$1'{$safeDescription}'",
        $configContent
    );
    if ($configContent === null) {
        echo json_encode(['code' => -1, 'msg' => 'Description 替换失败'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $writeResult = @file_put_contents($configFile, $configContent, LOCK_EX);
    if ($writeResult !== false) {
        echo json_encode(['code' => 0, 'msg' => 'SEO 配置已保存'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['code' => -1, 'msg' => '文件写入失败，请检查 data/ 目录权限'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 处理缓存网盘类型配置保存
 */
function adminHandleSaveCache() {
    $cachePans  = isset($_POST['cache_pans']) ? trim($_POST['cache_pans']) : '';
    $maxRecords = isset($_POST['max_records']) ? trim($_POST['max_records']) : '';

    if ($cachePans === '') {
        echo json_encode(['code' => -1, 'msg' => '请输入至少一个网盘类型'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 校验格式：半角逗号分隔的字母数字标识
    if (!preg_match('/^[a-z0-9]+(,[a-z0-9]+)*$/i', $cachePans)) {
        echo json_encode(['code' => -1, 'msg' => '格式错误，请使用半角逗号分隔，例如: 115,guangya,quark'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($maxRecords === '' || !ctype_digit($maxRecords) || (int)$maxRecords < 1) {
        echo json_encode(['code' => -1, 'msg' => '最大缓存数必须为正整数'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $maxRecordsInt = (int)$maxRecords;

    $configFile    = __DIR__ . '/../data/data.php';
    $configContent = @file_get_contents($configFile);

    if ($configContent === false) {
        echo json_encode(['code' => -1, 'msg' => '无法读取配置文件'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $safePans = str_replace("'", "\\'", $cachePans);

    // 替换 $cachePans = '...' 的值部分
    $count = 0;
    $configContent = preg_replace(
        "/\\\$cachePans\s*=\s*'[^']*'/",
        "\$cachePans = '{$safePans}'",
        $configContent,
        -1,
        $count
    );

    if ($configContent === null || $count === 0) {
        echo json_encode(['code' => -1, 'msg' => '缓存网盘类型替换失败，请检查配置文件格式'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 替换 $maxRecords = ... 的值部分
    $count2 = 0;
    $configContent = preg_replace(
        "/\\\$maxRecords\s*=\s*\d+/",
        "\$maxRecords = {$maxRecordsInt}",
        $configContent,
        -1,
        $count2
    );

    if ($configContent === null || $count2 === 0) {
        echo json_encode(['code' => -1, 'msg' => '最大缓存数替换失败，请检查配置文件格式'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $writeResult = @file_put_contents($configFile, $configContent, LOCK_EX);
    if ($writeResult !== false) {
        echo json_encode(['code' => 0, 'msg' => '缓存设置已保存'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['code' => -1, 'msg' => '文件写入失败，请检查 data/ 目录权限'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 处理接口设置保存
 */
function adminHandleSaveApi() {
    $apiBaseUrl  = isset($_POST['api_base_url']) ? trim($_POST['api_base_url']) : '';
    $searchTypes = isset($_POST['search_types']) ? trim($_POST['search_types']) : '';

    if ($apiBaseUrl === '') {
        echo json_encode(['code' => -1, 'msg' => 'API 接口地址不能为空'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!preg_match('#^https?://.+#', $apiBaseUrl)) {
        echo json_encode(['code' => -1, 'msg' => 'API 接口地址格式错误，需以 http:// 或 https:// 开头'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (substr($apiBaseUrl, -1) === '/') {
        echo json_encode(['code' => -1, 'msg' => 'API 接口地址结尾请不要加斜杠'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($searchTypes === '') {
        echo json_encode(['code' => -1, 'msg' => '网盘类型不能为空'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!preg_match('/^[a-z0-9]+(,[a-z0-9]+)*$/i', $searchTypes)) {
        echo json_encode(['code' => -1, 'msg' => '网盘类型格式错误，请使用半角逗号分隔'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $configFile    = __DIR__ . '/../data/data.php';
    $configContent = @file_get_contents($configFile);

    if ($configContent === false) {
        echo json_encode(['code' => -1, 'msg' => '无法读取配置文件'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $safeUrl   = str_replace("'", "\\'", $apiBaseUrl);
    $safeTypes = str_replace("'", "\\'", $searchTypes);

    // 替换 $apiBaseUrl = '...' 的值部分
    $count = 0;
    $configContent = preg_replace(
        "/\\\$apiBaseUrl\s*=\s*'[^']*'/",
        "\$apiBaseUrl = '{$safeUrl}'",
        $configContent,
        -1,
        $count
    );

    if ($configContent === null || $count === 0) {
        echo json_encode(['code' => -1, 'msg' => 'API 接口地址替换失败，请检查配置文件格式'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 替换 $searchTypes = '...' 的值部分
    $count2 = 0;
    $configContent = preg_replace(
        "/\\\$searchTypes\s*=\s*'[^']*'/",
        "\$searchTypes = '{$safeTypes}'",
        $configContent,
        -1,
        $count2
    );

    if ($configContent === null || $count2 === 0) {
        echo json_encode(['code' => -1, 'msg' => '网盘类型替换失败，请检查配置文件格式'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $writeResult = @file_put_contents($configFile, $configContent, LOCK_EX);
    if ($writeResult !== false) {
        echo json_encode(['code' => 0, 'msg' => '接口设置已保存'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['code' => -1, 'msg' => '文件写入失败，请检查 data/ 目录权限'], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 处理在线更新：下载远程 zip、解压覆盖、更新本地版本号
 */
function adminHandleOnlineUpdate() {
    try {
        // 检查 ZipArchive
        if (!class_exists('ZipArchive')) {
            echo json_encode(['code' => -1, 'msg' => '服务器未安装 php-zip 扩展，无法解压更新包，请使用离线更新'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        set_time_limit(300);

        $remoteVersion = PANBBS_REMOTE_VERSION;
        $remoteZipUrl  = PANBBS_REMOTE_ZIP_URL;

        if (!$remoteVersion || !$remoteZipUrl) {
            echo json_encode(['code' => -1, 'msg' => '无法获取远程版本信息，请稍后重试'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $localVersion = PANBBS_LOCAL_VERSION;
        if ($remoteVersion === $localVersion) {
            echo json_encode(['code' => -1, 'msg' => '已是最新版本，无需更新'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rootDir = dirname(__DIR__);
        $tmpZip  = $rootDir . '/data/update_tmp.zip';
        $tmpDir  = $rootDir . '/data/update_tmp';

        // 1. 下载 zip（优先用 curl，因为它能正确处理重定向和 HTTPS）
        $zipData = false;

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $remoteZipUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PanBbs/1.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $zipData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || $zipData === false) {
                $zipData = false;
            }
        }

        if ($zipData === false) {
            // fallback: file_get_contents
            $zipData = @file_get_contents($remoteZipUrl, false, stream_context_create([
                'http' => [
                    'timeout'    => 120,
                    'user_agent' => 'PanBbs/1.0',
                    'follow_location' => 1,
                    'max_redirects'    => 5,
                ],
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ]));
        }

        if ($zipData === false) {
            echo json_encode(['code' => -1, 'msg' => '下载更新包失败，请检查网络或使用离线更新'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (file_put_contents($tmpZip, $zipData, LOCK_EX) === false) {
            echo json_encode(['code' => -1, 'msg' => '写入临时文件失败，请检查 data/ 目录权限'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 2. 解压
        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            @unlink($tmpZip);
            echo json_encode(['code' => -1, 'msg' => '无法打开更新包，文件可能已损坏'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 清理旧的临时目录
        deleteDir($tmpDir);
        @mkdir($tmpDir, 0755, true);

        if (!$zip->extractTo($tmpDir)) {
            $zip->close();
            @unlink($tmpZip);
            deleteDir($tmpDir);
            echo json_encode(['code' => -1, 'msg' => '解压失败'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $zip->close();
        @unlink($tmpZip);

        // GitHub zipball 解压后有一个顶层目录 OsGits-PanBbs-xxxxx
        $innerDirs = glob($tmpDir . '/*', GLOB_ONLYDIR);
        if (empty($innerDirs)) {
            deleteDir($tmpDir);
            echo json_encode(['code' => -1, 'msg' => '更新包结构异常，未找到内容目录'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $srcDir = $innerDirs[0];

        // 3. 覆盖文件（排除本地配置目录和 Git 相关文件）
        $exclude = ['.vscode', '.git', '.gitignore', '.gitattributes', 'data'];
        $copiedCount = copyDir($srcDir, $rootDir, $exclude);

        // 4. 更新 version.php 中的本地版本号
        $versionFile = $rootDir . '/version.php';
        $content = file_get_contents($versionFile);
        $pattern = "/define\('PANBBS_LOCAL_VERSION',\s*'[^']*'\);/";
        $replacement = "define('PANBBS_LOCAL_VERSION', '{$remoteVersion}');";
        $newContent = preg_replace($pattern, $replacement, $content);

        if ($newContent !== null && $newContent !== $content) {
            file_put_contents($versionFile, $newContent, LOCK_EX);
        }

        // 清理临时目录
        deleteDir($tmpDir);

        echo json_encode(['code' => 0, 'msg' => "在线更新成功！已更新至 {$remoteVersion}，共覆盖 {$copiedCount} 个文件，请刷新页面。"], JSON_UNESCAPED_UNICODE);

    } catch (\Throwable $e) {
        echo json_encode(['code' => -1, 'msg' => '更新异常: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * 递归删除目录
 */
function deleteDir($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        is_dir($path) ? deleteDir($path) : unlink($path);
    }
    rmdir($dir);
}

/**
 * 递归复制目录（排除指定目录/文件）
 * @return int 复制的文件数
 */
function copyDir($src, $dst, $exclude = []) {
    $count = 0;
    if (!is_dir($src)) return $count;

    $items = scandir($src);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        if (in_array($item, $exclude)) continue;

        $srcPath = $src . '/' . $item;
        $dstPath = $dst . '/' . $item;

        if (is_dir($srcPath)) {
            if (!is_dir($dstPath)) {
                @mkdir($dstPath, 0755, true);
            }
            $count += copyDir($srcPath, $dstPath, $exclude);
        } else {
            // 覆盖前先尝试删除目标文件（避免权限问题）
            if (file_exists($dstPath)) {
                @unlink($dstPath);
            }
            if (@copy($srcPath, $dstPath)) {
                $count++;
            }
        }
    }
    return $count;
}
