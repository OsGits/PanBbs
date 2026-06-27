<?php
/**
 * 后台 - 版本更新页面模板
 */

if (!defined('ADMIN_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

/**
 * 渲染版本更新页面
 * @param string $username       当前登录用户名
 * @param string $localVersion   本地版本号
 * @param string|null $remoteVersion 远程版本号
 * @param string|null $remoteZipUrl  远程版本压缩包下载地址
 */
function adminShowVersion($username, $localVersion, $remoteVersion = null, $remoteZipUrl = null) {
    require_once __DIR__ . '/layout_head.php';
    require_once __DIR__ . '/layout_topbar.php';
    require_once __DIR__ . '/layout_sidebar.php';

    adminHead('版本更新');
    ?>
<body>
    <?php adminTopbar($username); ?>
    <div class="app-layout">
        <?php adminSidebar('version'); ?>
        <main class="main-content">
            <div class="container">
                <h2 class="page-title">📦 版本更新</h2>

                <!-- 版本信息 -->
                <div class="panel">
                    <h3>版本信息</h3>
                    <div class="version-card">
                        <div class="ver-info">
                            <span class="ver-tag"><?php echo htmlspecialchars($localVersion); ?></span>
                            <span class="ver-date">本地版本</span>
                        </div>
                        <span class="ver-badge current">当前运行</span>
                    </div>
                    <div class="version-card" style="margin-top:12px;">
                        <div class="ver-info">
                            <span class="ver-tag"><?php echo $remoteVersion ? htmlspecialchars($remoteVersion) : '获取失败'; ?></span>
                            <span class="ver-date">发布版本（<a href="https://github.com/OsGits/PanBbs/releases" target="_blank" style="color:#0f3460;">GitHub Releases</a>）</span>
                        </div>
                        <?php if ($remoteVersion && $remoteVersion !== $localVersion): ?>
                            <span class="ver-badge" style="background:#ff9800;color:#fff;">可更新</span>
                        <?php elseif ($remoteVersion && $remoteVersion === $localVersion): ?>
                            <span class="ver-badge" style="background:#4caf50;color:#fff;">已是最新</span>
                        <?php else: ?>
                            <span class="ver-badge" style="background:#999;color:#fff;">未知</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($remoteVersion && $remoteVersion !== $localVersion): ?>
                    <div style="margin-top:16px; display:flex; gap:12px;">
                        <button class="btn btn-primary" onclick="onlineUpdate()">在线更新</button>
                        <a href="<?php echo htmlspecialchars($remoteZipUrl ?: 'https://github.com/OsGits/PanBbs/releases/latest'); ?>" target="_blank" class="btn" style="border-color:#ff9800;color:#ff9800;background:#fff;text-decoration:none;display:inline-block;padding:8px 20px;border-radius:4px;border:1px solid;cursor:pointer;">离线更新</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 更新日志 -->
                <div class="panel">
                    <h3>更新日志</h3>
                    <?php
                    $logDir = __DIR__ . '/../log';
                    $logFiles = [];
                    if (is_dir($logDir)) {
                        $files = glob($logDir . '/*.md');
                        if ($files) {
                            rsort($files);
                            $logFiles = array_slice($files, 0, 10);
                        }
                    }
                    if (!empty($logFiles)):
                        foreach ($logFiles as $logFile):
                            $logName = basename($logFile, '.md');
                    ?>
                    <div class="version-card">
                        <div class="ver-info">
                            <span class="ver-tag"><?php echo htmlspecialchars($logName); ?></span>
                            <span class="ver-date"><?php echo htmlspecialchars(date('Y-m-d', filemtime($logFile))); ?></span>
                        </div>
                        <a href="https://github.com/OsGits/PanBbs/blob/main/log/<?php echo urlencode(basename($logFile)); ?>" target="_blank" class="btn-sm" style="color:#0f3460;border-color:#0f3460;">查看</a>
                    </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <p style="color: #888; text-align: center; padding: 20px;">暂无更新日志</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>


</body>
</html>
    <?php
}
