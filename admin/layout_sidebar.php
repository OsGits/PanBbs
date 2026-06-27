<?php
/**
 * 后台 - 左侧导航栏组件
 */

if (!defined('ADMIN_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

/**
 * 输出左侧导航栏
 * @param string $currentPage 当前页面标识: dashboard | settings | version
 */
function adminSidebar($currentPage = 'dashboard') {
    $navItems = [
        'dashboard' => ['icon' => '📊', 'text' => '后台首页'],
        'settings'  => ['icon' => '⚙️', 'text' => '系统设置'],
        'version'   => ['icon' => '📦', 'text' => '版本更新'],
    ];
    ?>
    <aside class="sidebar">
        <div class="nav-title">导航菜单</div>
        <nav>
            <?php foreach ($navItems as $key => $item): ?>
            <a href="admin.php?a=<?php echo $key; ?>"
               class="nav-item<?php echo ($currentPage === $key) ? ' active' : ''; ?>">
                <span class="nav-icon"><?php echo $item['icon']; ?></span>
                <span class="nav-text"><?php echo $item['text']; ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <?php
}
