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
 */
function adminShowVersion($username, $localVersion, $remoteVersion = null) {
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
                            <span class="ver-date">远程版本（GitHub Releases）</span>
                        </div>
                        <?php if ($remoteVersion && $remoteVersion !== $localVersion): ?>
                            <span class="ver-badge" style="background:#ff9800;color:#fff;">可更新</span>
                        <?php elseif ($remoteVersion && $remoteVersion === $localVersion): ?>
                            <span class="ver-badge" style="background:#4caf50;color:#fff;">已是最新</span>
                        <?php else: ?>
                            <span class="ver-badge" style="background:#999;color:#fff;">未知</span>
                        <?php endif; ?>
                    </div>
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
