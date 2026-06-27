<?php
/**
 * 后台 - 系统设置页面模板
 * 卡片式分页标签：SEO设置 | 修改密码
 */

if (!defined('ADMIN_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

/**
 * 渲染系统设置页面
 * @param string $username     当前登录用户名
 * @param string $version      当前版本号
 * @param array  $seo          当前 SEO 配置 ['title','keywords','description']
 * @param string $apiBaseUrl   API 接口地址
 * @param string $searchTypes  搜索页网盘类型，半角逗号分隔
 * @param string $cachePans    缓存网盘类型，半角逗号分隔
 * @param int    $maxRecords   每种类型最大缓存记录数
 */
function adminShowSettings($username, $version, $seo, $apiBaseUrl, $searchTypes, $cachePans, $maxRecords) {
    require_once __DIR__ . '/layout_head.php';
    require_once __DIR__ . '/layout_topbar.php';
    require_once __DIR__ . '/layout_sidebar.php';

    adminHead('系统设置');
    ?>
<body>
    <?php adminTopbar($username); ?>
    <div class="app-layout">
        <?php adminSidebar('settings'); ?>
        <main class="main-content">
            <div class="container">
                <div id="toast" class="toast" style="display:none;"></div>
                <h2 class="page-title">⚙️ 系统设置</h2>

                <!-- 标签导航 -->
                <div class="tab-nav">
                    <button class="tab-btn active" data-tab="tab-seo">前端 SEO 设置</button>
                    <button class="tab-btn" data-tab="tab-api">接口设置</button>
                    <button class="tab-btn" data-tab="tab-cache">缓存设置</button>
                    <button class="tab-btn" data-tab="tab-password">修改密码</button>
                </div>

                <!-- SEO 设置面板 -->
                <div class="panel tab-panel active" id="tab-seo">
                    <h3>前端 SEO 设置</h3>
                    <div class="form-field" style="margin-bottom:14px;">
                        <label>Title（页面标题）</label>
                        <input type="text" id="seoTitle" value="<?php echo htmlspecialchars($seo['title']); ?>" placeholder="网盘资源聚合">
                    </div>
                    <div class="form-field" style="margin-bottom:14px;">
                        <label>Keywords（关键词）</label>
                        <input type="text" id="seoKeywords" value="<?php echo htmlspecialchars($seo['keywords']); ?>" placeholder="网盘,资源,聚合">
                    </div>
                    <div class="form-field" style="margin-bottom:14px;">
                        <label>Description（页面描述）</label>
                        <textarea id="seoDescription" rows="3" style="width:100%;padding:8px 12px;border:1px solid #d0d5dd;border-radius:6px;font-size:14px;resize:vertical;" placeholder="网盘资源聚合搜索平台"><?php echo htmlspecialchars($seo['description']); ?></textarea>
                    </div>
                    <button class="btn btn-primary" onclick="saveSeo()">保存 SEO 设置</button>
                </div>

                <!-- 接口设置面板 -->
                <div class="panel tab-panel" id="tab-api" style="display:none;">
                    <h3>接口设置</h3>
                    <p class="hint-text" style="color:#667085;font-size:13px;margin-bottom:14px;">
                        配置全局 API 接口地址和搜索页可使用的网盘类型。
                    </p>
                    <div class="form-field" style="margin-bottom:14px;">
                        <label>API 接口地址</label>
                        <input type="text" id="apiBaseUrl" value="<?php echo htmlspecialchars($apiBaseUrl); ?>" placeholder="http://127.0.0.1:8010" style="width:100%;font-family:monospace;">
                        <small style="color:#999;">结尾不加斜杠，例如：http://127.0.0.1:8010</small>
                    </div>
                    <div class="form-field" style="margin-bottom:14px;">
                        <label>网盘类型（搜索页可用）</label>
                        <input type="text" id="searchTypes" value="<?php echo htmlspecialchars($searchTypes); ?>" placeholder="baidu,aliyun,quark,guangya,tianyi,uc,mobile,115,pikpak,xunlei,123,magnet,ed2k" style="width:100%;font-family:monospace;">
                        <small style="color:#999;">半角逗号分隔，例如：baidu,aliyun,quark</small>
                    </div>
                    <button class="btn btn-primary" onclick="saveApi()">保存接口设置</button>
                </div>

                <!-- 缓存设置面板 -->
                <div class="panel tab-panel" id="tab-cache" style="display:none;">
                    <h3>缓存设置</h3>
                    <p class="hint-text" style="color:#667085;font-size:13px;margin-bottom:14px;">
                        设置从远程 API 缓存到本地 oss.json 的网盘类型，使用<strong>半角逗号</strong>分隔。
                    </p>
                    <div class="form-field" style="margin-bottom:14px;">
                        <label>缓存网盘类型</label>
                        <input type="text" id="cachePans" value="<?php echo htmlspecialchars($cachePans); ?>" placeholder="115,guangya,quark" style="width:100%;font-family:monospace;">
                    </div>
                    <div class="form-field" style="margin-bottom:14px;">
                        <label>每种类型最大缓存数</label>
                        <input type="number" id="maxRecords" value="<?php echo (int)$maxRecords; ?>" placeholder="100" min="1" max="99999" style="width:100%;">
                    </div>
                    <button class="btn btn-primary" onclick="saveCache()">保存缓存设置</button>
                </div>

                <!-- 修改密码面板 -->
                <div class="panel tab-panel" id="tab-password" style="display:none;">
                    <h3>修改密码</h3>
                    <div class="form-row">
                        <div class="form-field">
                            <label>原密码</label>
                            <input type="password" id="oldPwd" placeholder="输入原密码">
                        </div>
                        <div class="form-field">
                            <label>新密码</label>
                            <input type="password" id="newPwd" placeholder="至少6位">
                        </div>
                        <div class="form-field">
                            <label>确认新密码</label>
                            <input type="password" id="confirmPwd" placeholder="再次输入新密码">
                        </div>
                        <button class="btn btn-primary" onclick="changePassword()" style="height:38px;">确认修改</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // ===== 标签切换 =====
        (function() {
            var tabs = document.querySelectorAll('.tab-btn');
            var panels = document.querySelectorAll('.tab-panel');
            tabs.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var targetId = this.getAttribute('data-tab');
                    tabs.forEach(function(b) { b.classList.remove('active'); });
                    this.classList.add('active');
                    panels.forEach(function(p) {
                        p.style.display = p.id === targetId ? '' : 'none';
                    });
                });
            });
        })();

        // ===== 通用工具 =====
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
                if (xhr.status !== 200) {
                    showToast('服务器错误 (HTTP ' + xhr.status + ')', 'error');
                    return;
                }
                try { var res = JSON.parse(xhr.responseText); callback(res); }
                catch(e) { showToast('服务器响应异常: ' + xhr.responseText.substring(0, 100), 'error'); }
            };
            xhr.onerror = function() { showToast('网络请求失败', 'error'); };
            var params = [];
            for (var key in data) {
                params.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
            }
            xhr.send(params.join('&'));
        }

        // ===== 接口设置保存 =====
        function saveApi() {
            var apiBaseUrl = document.getElementById('apiBaseUrl').value.trim();
            var searchTypes = document.getElementById('searchTypes').value.trim();
            if (!apiBaseUrl) {
                showToast('请输入 API 接口地址', 'error'); return;
            }
            if (!/^https?:\/\/.+/.test(apiBaseUrl)) {
                showToast('API 接口地址格式错误，需以 http:// 或 https:// 开头', 'error'); return;
            }
            if (apiBaseUrl.slice(-1) === '/') {
                showToast('API 接口地址结尾请不要加斜杠', 'error'); return;
            }
            if (!searchTypes) {
                showToast('请输入网盘类型', 'error'); return;
            }
            if (!/^[a-z0-9]+(,[a-z0-9]+)*$/i.test(searchTypes)) {
                showToast('网盘类型格式错误，请使用半角逗号分隔', 'error'); return;
            }
            postAction({
                action: 'save_api',
                api_base_url: apiBaseUrl,
                search_types: searchTypes
            }, function(res) {
                if (res.code === 0) {
                    showToast(res.msg, 'success');
                } else { showToast(res.msg, 'error'); }
            });
        }

        // ===== 缓存设置保存 =====
        function saveCache() {
            var cachePans = document.getElementById('cachePans').value.trim();
            var maxRecords = document.getElementById('maxRecords').value.trim();
            if (!cachePans) {
                showToast('请输入至少一个网盘类型', 'error'); return;
            }
            // 校验半角逗号分隔格式
            if (!/^[a-z0-9]+(,[a-z0-9]+)*$/i.test(cachePans)) {
                showToast('格式错误，请使用半角逗号分隔，例如: 115,guangya,quark', 'error'); return;
            }
            if (!maxRecords || !/^\d+$/.test(maxRecords) || parseInt(maxRecords) < 1) {
                showToast('最大缓存数必须为正整数', 'error'); return;
            }
            postAction({
                action: 'save_cache',
                cache_pans: cachePans,
                max_records: maxRecords
            }, function(res) {
                if (res.code === 0) {
                    showToast(res.msg, 'success');
                } else { showToast(res.msg, 'error'); }
            });
        }

        // ===== SEO 保存 =====
        function saveSeo() {
            var title = document.getElementById('seoTitle').value.trim();
            var keywords = document.getElementById('seoKeywords').value.trim();
            var description = document.getElementById('seoDescription').value.trim();
            if (!title) {
                showToast('Title 不能为空', 'error'); return;
            }
            postAction({
                action: 'save_seo',
                seo_title: title,
                seo_keywords: keywords,
                seo_description: description
            }, function(res) {
                if (res.code === 0) {
                    showToast(res.msg, 'success');
                } else { showToast(res.msg, 'error'); }
            });
        }

        // ===== 修改密码 =====
        function changePassword() {
            var oldPwd = document.getElementById('oldPwd').value.trim();
            var newPwd = document.getElementById('newPwd').value.trim();
            var confirmPwd = document.getElementById('confirmPwd').value.trim();
            if (!oldPwd || !newPwd || !confirmPwd) {
                showToast('请填写所有密码字段', 'error'); return;
            }
            postAction({
                action: 'change_password',
                old_password: oldPwd, new_password: newPwd, confirm_password: confirmPwd
            }, function(res) {
                if (res.code === 0) {
                    showToast(res.msg, 'success');
                    document.getElementById('oldPwd').value = '';
                    document.getElementById('newPwd').value = '';
                    document.getElementById('confirmPwd').value = '';
                } else { showToast(res.msg, 'error'); }
            });
        }
    </script>
</body>
</html>
    <?php
}
