<?php
/**
 * 模块：版本信息弹窗
 */
?>
<div class="search-overlay" id="copyrightOverlay">
    <div class="search-modal">
        <button class="search-modal-close" id="copyrightModalClose" title="关闭">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <h2 class="search-modal-title">关于 PanBbs</h2>
        <div class="version-compare">
            <div class="version-item version-local">
                <span class="version-label">当前版本</span>
                <span class="version-num" id="localVersion"><?= htmlspecialchars($localVersion) ?></span>
            </div>
            <div class="version-divider">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </div>
            <div class="version-item version-remote">
                <span class="version-label">最新版本</span>
                <span class="version-num" id="latestVersion">获取中...</span>
            </div>
        </div>
        <div class="version-status" id="updateHint" style="display:none;">
            <span class="version-dot"></span> 与云端版本不一致，建议同步更新！
        </div>
        <p class="version-desc">基于 <a >PanBbs</a> 构建的网盘资源聚合前端</p>
    </div>
</div>
