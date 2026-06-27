<?php
/**
 * 模块：搜索弹窗（含网盘类型筛选复选框）
 */

// 网盘类型配置
$diskTypes = [
    'baidu'   => '百度网盘',
    'aliyun'  => '阿里云盘',
    'quark'   => '夸克网盘',
    'guangya' => '光鸭云盘',
    'tianyi'  => '天翼云盘',
    'uc'      => 'UC网盘',
    'mobile'  => '移动云盘',
    '115'     => '115网盘',
    'pikpak'  => 'PikPak',
    'xunlei'  => '迅雷网盘',
    '123'     => '123网盘',
    'magnet'  => '磁力链接',
    'ed2k'    => '电驴链接',
];
?>
<div class="search-overlay" id="searchOverlay">
    <div class="search-modal">
        <button class="search-modal-close" id="searchModalClose" title="关闭">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <h2 class="search-modal-title">搜索资源</h2>
        <form class="search-modal-form" onsubmit="return false;">
            <input type="text" id="modalSearchInput" placeholder="输入影视名称搜索..." autocomplete="off">
            <button type="submit" id="modalSearchBtn" class="search-modal-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                搜索
            </button>
        </form>
        <div class="search-type-filter" id="searchTypeFilter">
            <div class="search-type-header">
                <span class="search-type-title">网盘类型筛选</span>
                <button type="button" class="search-type-toggle" id="searchTypeToggle">全选</button>
            </div>
            <div class="search-type-list">
                <?php foreach ($diskTypes as $key => $label): ?>
                <label class="search-type-item">
                    <input type="checkbox" name="searchType" value="<?= $key ?>">
                    <span><?= $label ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
