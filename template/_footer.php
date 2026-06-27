<?php
/**
 * 模块：页脚 + 全局加载遮罩 + JS 数据注入
 */
?>
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
