<?php
/**
 * 后台 - 顶部导航栏组件
 */

if (!defined('ADMIN_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

/**
 * 输出顶部导航栏
 * @param string $username 当前登录用户名
 */
function adminTopbar($username) {
    ?>
    <div class="topbar">
        <div class="logo">⚙️ PanBbs 管理面板</div>
        <div class="user-info">
            <span class="username">👤 <?php echo htmlspecialchars($username); ?></span>
            <a href="admin.php?logout=1" class="btn-sm">退出登录</a>
            <a href="index.php" target="_blank" class="btn-sm">查看前台</a>
        </div>
    </div>
    <?php
}
