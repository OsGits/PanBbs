<?php
/**
 * 后台 - 后台首页模板
 * 仅包含：数据统计
 */

if (!defined('ADMIN_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

/**
 * 渲染后台首页
 * @param array       $dataStats     各类型记录数 ['type' => count, ...]
 * @param int         $totalRecords  总记录数
 * @param string      $localVersion  本地版本号
 * @param string|null $remoteVersion 远程最新版本号
 * @param string      $username      当前登录用户名
 */
function adminShowDashboard($dataStats, $totalRecords, $localVersion, $remoteVersion, $username) {
    require_once __DIR__ . '/layout_head.php';
    require_once __DIR__ . '/layout_topbar.php';
    require_once __DIR__ . '/layout_sidebar.php';

    adminHead('后台首页');
    ?>
<body>
    <?php adminTopbar($username); ?>
    <div class="app-layout">
        <?php adminSidebar('dashboard'); ?>
        <main class="main-content">
            <div class="container">
                <h2 class="page-title">📊 数据统计</h2>

                <!-- 数据统计 -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-label">缓存数</div>
                        <div class="stat-value"><?php echo $totalRecords; ?></div>
                    </div>
                    <div class="stat-card version">
                        <div class="stat-label">本地版本</div>
                        <div class="stat-value"><?php echo htmlspecialchars($localVersion); ?></div>
                    </div>
                    <div class="stat-card version">
                        <div class="stat-label">远程版本</div>
                        <div class="stat-value"><?php echo $remoteVersion ? htmlspecialchars($remoteVersion) : '<span style="font-size:16px;color:#999;">获取中...</span>'; ?></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
    <?php
}
