<?php
/**
 * 模块：卡片容器 + 空状态 + 浮动按钮组
 */
?>
    <!-- 卡片容器 -->
    <div class="card-list-wrapper">
        <div class="card-list" id="cardList"></div>
    </div>

    <!-- 空状态 -->
    <div class="empty" id="emptyBox" style="display:none;">
        <div class="empty-icon">📭</div>
        <p id="emptyMsg">暂无数据，请先刷新获取</p>
    </div>

    <?php require __DIR__ . '/_fab.php'; ?>
</div>
