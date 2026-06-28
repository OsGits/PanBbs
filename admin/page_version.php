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
 * @param string $username       当前登录用户名
 * @param string $localVersion   本地版本号
 * @param string|null $remoteVersion 远程版本号
 * @param string|null $remoteZipUrl  远程版本压缩包下载地址
 */
function adminShowVersion($username, $localVersion, $remoteVersion = null, $remoteZipUrl = null, $releaseBody = null) {
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

                <!-- 版本信息 -->
                <div class="panel">
                    <h3>版本信息</h3>
                    <div class="version-card">
                        <div class="ver-info">
                            <span class="ver-tag"><?php echo htmlspecialchars($localVersion); ?></span>
                            <span class="ver-date">本地版本</span>
                        </div>
                        <span class="ver-badge current">当前运行</span>
                    </div>
                    <div class="version-card" style="margin-top:12px;">
                        <div class="ver-info">
                            <span class="ver-tag"><?php echo $remoteVersion ? htmlspecialchars($remoteVersion) : '获取失败'; ?></span>
                            <span class="ver-date">发布版本（<a href="https://github.com/OsGits/PanBbs/releases" target="_blank" style="color:#0f3460;">GitHub Releases</a>）</span>
                        </div>
                        <?php if ($remoteVersion && $remoteVersion !== $localVersion): ?>
                            <span class="ver-badge" style="background:#ff9800;color:#fff;">可更新</span>
                        <?php elseif ($remoteVersion && $remoteVersion === $localVersion): ?>
                            <span class="ver-badge" style="background:#4caf50;color:#fff;">已是最新</span>
                        <?php else: ?>
                            <span class="ver-badge" style="background:#999;color:#fff;">未知</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($remoteVersion && $remoteVersion !== $localVersion): ?>
                    <div style="margin-top:16px; display:flex; gap:12px;">
                        <button class="btn btn-primary" onclick="onlineUpdate()">在线更新</button>
                        <a href="<?php echo htmlspecialchars($remoteZipUrl ?: 'https://github.com/OsGits/PanBbs/releases/latest'); ?>" target="_blank" class="btn" style="border-color:#ff9800;color:#ff9800;background:#fff;text-decoration:none;display:inline-block;padding:8px 20px;border-radius:4px;border:1px solid;cursor:pointer;">离线更新</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- GitHub Releases 更新日志 -->
                <div class="panel">
                    <h3>📋 最新发布更新日志</h3>
                    <?php if ($releaseBody): ?>
                    <div class="release-body readme-body">
                        <?php echo simpleMarkdownToHtml($releaseBody); ?>
                    </div>
                    <div style="margin-top:14px;">
                        <a href="https://github.com/OsGits/PanBbs/releases" target="_blank" style="color:#4f6ef7;font-size:13px;">查看全部 Releases →</a>
                    </div>
                    <?php else: ?>
                    <p style="color:#888;text-align:center;padding:20px;">无法获取 GitHub Releases 更新日志</p>
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
            t._timer = setTimeout(function() { t.style.display = 'none'; }, 5000);
        }

        function postAction(data, callback) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'admin.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                try { var res = JSON.parse(xhr.responseText); callback(res); }
                catch(e) { showToast('服务器响应异常: ' + xhr.responseText.substring(0, 200), 'error'); }
            };
            xhr.onerror = function() { showToast('网络请求失败', 'error'); };
            var params = [];
            for (var key in data) {
                params.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
            xhr.send(params.join('&'));
        }

        function onlineUpdate() {
            if (!confirm('确定要在线更新吗？\n\n此操作将从 GitHub 下载最新版本并覆盖当前文件。')) return;

            var btn = document.querySelector('.btn-primary');
            btn.disabled = true;
            btn.textContent = '更新中...';
            showToast('正在下载更新包，请稍候...', 'info');

            postAction({ action: 'online_update' }, function(res) {
                btn.disabled = false;
                btn.textContent = '在线更新';
                if (res.code === 0) {
                    showToast(res.msg, 'success');
                    setTimeout(function() { location.reload(); }, 3000);
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
