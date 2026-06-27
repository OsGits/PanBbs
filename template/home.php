<?php
/**
 * 网盘资源聚合 - 前端展示页面
 * 全量数据写入 JS 变量，由前端控制分批加载（每次10条）
 * 到底自动触发 ting.php 刷新并重置
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

<!-- Header -->
<header class="header">
    <div class="header-inner">
        <a href="/" class="header-btn" title="首页">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            首页
        </a>
        <button class="header-btn" id="headerSearchBtn" title="搜索">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            搜索
        </button>
    </div>
</header>

<div class="container">
    <!-- 工具栏：搜索 -->
    <div class="toolbar">
        <button class="inline-search-trigger" id="inlineSearchBtn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <span id="searchPlaceholder">搜索要寻找的影片名...</span>
        </button>
        <?php if ($keyword): ?>
        <button class="search-clear" id="searchClearBtn">✕ 清除筛选</button>
        <?php endif; ?>
    </div>

    <!-- 骨架屏加载动画 -->
    <div class="card-list" id="skeletonBox">
        <div class="skeleton"><div class="skeleton-line w60"></div><div class="skeleton-line w100"></div><div class="skeleton-line w80"></div></div>
        <div class="skeleton"><div class="skeleton-line w40"></div><div class="skeleton-line w100"></div><div class="skeleton-line w60"></div></div>
        <div class="skeleton"><div class="skeleton-line w80"></div><div class="skeleton-line w100"></div><div class="skeleton-line w40"></div></div>
    </div>

    <!-- 进度指示器 -->
    <div class="load-status" id="loadStatus">
        <span class="load-dot"></span> 加载中...
    </div>

    <!-- 卡片容器 -->
    <div class="card-list" id="cardList"></div>

    <!-- 空状态 -->
    <div class="empty" id="emptyBox" style="display:none;">
        <div class="empty-icon">📭</div>
        <p id="emptyMsg">暂无数据，请先刷新获取</p>
    </div>
</div>

<!-- 右下角浮动按钮 -->
<button class="fab fab-copyright" id="fabCopyright" title="关于">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M14.31 8.5c-.51-.47-1.22-.75-2-.75-1.66 0-3 1.34-3 3s1.34 3 3 3c.78 0 1.49-.28 2-.75"/><line x1="12" y1="16.5" x2="12" y2="16.51"/></svg>
</button>
<button class="fab fab-search" id="fabSearch" title="搜索">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
</button>
<button class="back-to-top" id="backToTop" title="回到顶部">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
</button>

<!-- 版权弹窗遮罩 -->
<div class="search-overlay" id="copyrightOverlay">
    <div class="search-modal">
        <button class="search-modal-close" id="copyrightModalClose" title="关闭">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <h2 class="search-modal-title">关于 PanBbs</h2>
        <div class="copyright-info">
            <div class="copyright-row">
                <span class="copyright-label">当前版本</span>
                <span class="copyright-value" id="localVersion"><?= htmlspecialchars($localVersion) ?></span>
            </div>
            <div class="copyright-row">
                <span class="copyright-label">最新版本</span>
                <span class="copyright-value" id="latestVersion">获取中...</span>
            </div>
            <div class="copyright-row">
                <span class="copyright-label">项目地址</span>
                <a class="copyright-link" href="https://github.com/OsGits/PanBbs" target="_blank" rel="nofollow noopener">github.com/OsGits/PanBbs</a>
            </div>
            <div class="copyright-row">
                <span class="copyright-label">后端引擎</span>
                <a class="copyright-link" href="https://github.com/fish2018/pansou" target="_blank" rel="nofollow noopener">PanSo by fish2018</a>
            </div>
            <div class="copyright-row" id="updateHint" style="display:none;">
                <span class="copyright-label"></span>
                <span class="copyright-value" style="color:var(--orange);">有新版本可用！</span>
            </div>
        </div>
    </div>
</div>

<!-- 搜索弹窗遮罩 -->
<div class="search-overlay" id="searchOverlay">
    <div class="search-modal">
        <button class="search-modal-close" id="searchModalClose" title="关闭">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <h2 class="search-modal-title">搜索资源</h2>
        <form class="search-modal-form" onsubmit="return false;">
            <input type="text" id="modalSearchInput" placeholder="输入关键词搜索标题 / 内容 / 标签..." autocomplete="off">
            <button type="submit" id="modalSearchBtn" class="search-modal-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                搜索
            </button>
        </form>
    </div>
</div>

<footer class="footer">Powered by PanBbs</footer>

<!-- 全局加载遮罩 -->
<div class="global-loading-overlay" id="globalLoading">
    <div class="global-loading-box">
        <span class="global-loading-dot"></span>
        <span class="global-loading-text">引擎重新获取中...</span>
    </div>
</div>

<!-- 将全量数据注入 JS -->
<script>
var PANBBS_DATA = <?= json_encode($allList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var PANBBS_KEYWORD = <?= json_encode($keyword) ?>;
</script>
<script src="<?= $basePath ?>/app.js?<?= $assetVer ?>"></script>
</body>
</html>
