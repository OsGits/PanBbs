<?php
/**
 * 模块：工具栏（搜索触发 + 骨架屏 + 加载指示器）
 */
?>
<div class="container">
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
