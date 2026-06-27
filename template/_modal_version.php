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
            <div class="version-card version-local">
                <div class="version-card-header">
                    <span class="version-card-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <span class="version-label">本地版本</span>
                </div>
                <span class="version-num" id="localVersion"><?= htmlspecialchars($localVersion) ?></span>
            </div>
            <div class="version-connector">
                <div class="version-connector-line"></div>
                <div class="version-connector-status" id="connectorStatus">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                </div>
                <div class="version-connector-line"></div>
            </div>
            <div class="version-card version-remote">
                <div class="version-card-header">
                    <span class="version-card-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    </span>
                    <span class="version-label">远程版本</span>
                </div>
                <span class="version-num" id="latestVersion">获取中...</span>
            </div>
        </div>
        <div class="version-status" id="updateHint" style="display:none;">
            <span class="version-dot"></span> 检测到版本差异，建议进入后台更新！
        </div>
        <p class="version-desc">基于 <a >PanBbs</a> 构建的网盘资源聚合前端</p>
    </div>
</div>
