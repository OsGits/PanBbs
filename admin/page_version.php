<?php
/**
 * 后台 - 版本更新页面模板
 */

if (!defined('ADMIN_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

/**
 * 渲染版本更新页面
 * @param string $username      当前登录用户名
 * @param string $localVersion  本地版本号
 */
function adminShowVersion($username, $localVersion) {
    require_once __DIR__ . '/layout_head.php';
    require_once __DIR__ . '/layout_topbar.php';
    require_once __DIR__ . '/layout_sidebar.php';

    adminHead('版本更新');
    ?>
<body>
    <?php adminTopbar($username); ?>
    <div class="app-layout">
        <?php adminSidebar('version'); ?>
        <main class="main-content">
            <div class="container">
                <div id="toast" class="toast" style="display:none;"></div>
                <h2 class="page-title">📦 版本更新</h2>

                <!-- 当前版本 -->
                <div class="panel">
                    <h3>版本信息</h3>
                    <div class="version-card">
                        <div class="ver-info">
                            <span class="ver-tag"><?php echo htmlspecialchars($localVersion); ?></span>
                            <span class="ver-date">当前运行版本</span>
                        </div>
                        <span class="ver-badge current">当前版本</span>
                    </div>
                </div>

                <!-- 版本更新操作 -->
                <div class="panel">
                    <h3>更新操作</h3>
                    <p style="color: #888; margin-bottom: 16px; font-size: 14px;">
                        点击下方按钮可修改本地版本号。实际代码部署仍需手动操作。
                    </p>
                    <div class="form-row">
                        <div class="form-field wide">
                            <label>新版本号</label>
                            <input type="text" id="newVersion" placeholder="例如: v0.0.7.0" value="<?php echo htmlspecialchars($localVersion); ?>">
                        </div>
                        <button class="btn btn-primary" onclick="updateVersion()" style="height:38px;">更新版本号</button>
                    </div>
                </div>

                <!-- 更新日志 -->
                <div class="panel">
                    <h3>更新日志</h3>
                    <?php
                    $logDir = __DIR__ . '/../log';
                    $logFiles = [];
                    if (is_dir($logDir)) {
                        $files = glob($logDir . '/*.md');
                        if ($files) {
                            rsort($files);
                            $logFiles = array_slice($files, 0, 10);
                        }
                    }
                    if (!empty($logFiles)):
                        foreach ($logFiles as $logFile):
                            $logName = basename($logFile, '.md');
                    ?>
                    <div class="version-card">
                        <div class="ver-info">
                            <span class="ver-tag"><?php echo htmlspecialchars($logName); ?></span>
                            <span class="ver-date"><?php echo htmlspecialchars(date('Y-m-d', filemtime($logFile))); ?></span>
                        </div>
                        <a href="https://github.com/OsGits/PanBbs/blob/main/log/<?php echo urlencode(basename($logFile)); ?>" target="_blank" class="btn-sm" style="color:#0f3460;border-color:#0f3460;">查看</a>
                    </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <p style="color: #888; text-align: center; padding: 20px;">暂无更新日志</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showToast(msg, type) {
            var t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'toast ' + (type || 'success');
            t.style.display = 'block';
            clearTimeout(t._timer);
            t._timer = setTimeout(function() { t.style.display = 'none'; }, 3000);
        }

        function postAction(data, callback) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'admin.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                try { var res = JSON.parse(xhr.responseText); callback(res); }
                catch(e) { showToast('服务器响应异常', 'error'); }
            };
            xhr.onerror = function() { showToast('网络请求失败', 'error'); };
            var params = [];
            for (var key in data) {
                params.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
            xhr.send(params.join('&'));
        }

        function updateVersion() {
            var newVer = document.getElementById('newVersion').value.trim();
            if (!newVer) {
                showToast('请输入版本号', 'error'); return;
            }
            postAction({ action: 'update_version', new_version: newVer }, function(res) {
                if (res.code === 0) {
                    showToast(res.msg, 'success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    showToast(res.msg, 'error');
                }
            });
        }
    </script>
</body>
</html>
    <?php
}
