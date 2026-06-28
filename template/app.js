/**
 * PanBbs - 前端交互脚本
 * 流程：首屏10条 → 滚动追加 → 到底自动拉新数据 → 追加显示 → 继续滚动
 */
(function () {
    'use strict';

    var PAGE_SIZE = 10;
    var allItems = [];
    var rendered = 0;
    var isLoading = false;      // 正在追加渲染
    var refreshing = false;     // 正在从服务器拉新数据
    var isEnd = false;          // 当前数据已全部渲染完
    var lastRefreshTime = 0;    // 上次刷新时间戳，防止死循环
    var REFRESH_COOLDOWN = 5000; // 刷新冷却时间 5 秒

    var cardList, emptyBox, emptyMsg, loadStatus, skeletonBox, backToTop;
    var fabSearch, fabCopyright, searchOverlay, modalSearchInput, modalSearchBtn, searchModalClose, globalLoading;
    var copyrightOverlay, copyrightModalClose, localVersionEl, latestVersionEl, updateHint, connectorStatus;
    var detailOverlay, detailModalClose, detailIcon, detailTitle, detailType, detailImages, detailContent, detailMeta, detailActions;
    var searchPlaceholder, searchClearBtn;
    var searchTypeCheckboxes, searchTypeToggle;

    // ============ 安全解析 PANBBS_DATA ============
    try {
        if (typeof PANBBS_DATA !== 'undefined' && Array.isArray(PANBBS_DATA)) {
            allItems = PANBBS_DATA;
        }
    } catch (e) {
        allItems = [];
    }

    // ============ Toast ============
    var toastTimer = null;
    function showToast(msg) {
        var toast = document.getElementById('toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast';
            toast.className = 'toast';
            document.body.appendChild(toast);
        }
        if (toastTimer) clearTimeout(toastTimer);
        toast.textContent = msg;
        toast.classList.add('show');
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2000);
    }

    // ============ 图片 CDN 前缀 ============
    var IMG_CDN = '//apis.cnp.cc/?img=';

    // ============ 渲染图片区域（通用） ============
    function imagesHTML(images) {
        if (!images || !Array.isArray(images) || images.length === 0) return '';
        var html = '<div class="card-images">';
        for (var i = 0; i < images.length; i++) {
            var src = String(images[i] || '');
            if (!src) continue;
            html += '<div class="card-image-item">' +
                '<img src="' + escAttr(IMG_CDN + src) + '" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.parentElement.style.display=\'none\'" />' +
            '</div>';
        }
        html += '</div>';
        return html;
    }

    // ============ 渲染单张卡片 ============
    function cardHTML(item) {
        var type = String(item.type || '');
        var icon = type === 'quark' ? 'Q' : (type === 'guangya' ? 'G' : '1');
        var tags = '';
        if (item.tags) {
            var arr = String(item.tags).split(',');
            for (var ti = 0; ti < arr.length; ti++) {
                var t = arr[ti].trim();
                if (t) tags += '<span class="tag">#' + esc(t) + '</span>';
            }
        }
        var pwdBlock = '';
        if (item.password) {
            pwdBlock = '<span class="pwd-text" data-pwd="' + escAttr(item.password) + '" title="点击复制密码">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> ' +
                '密码：<strong>' + esc(item.password) + '</strong></span>';
        }
        var cardBgStyle = '';
        if (item.images && Array.isArray(item.images) && item.images.length > 0) {
            cardBgStyle = ' style="--card-bg-image:url(' + escAttr(IMG_CDN + String(item.images[0] || '')) + ')"';
        }
        return '<div class="card type-' + escAttr(type) + (cardBgStyle ? ' has-bg-image' : '') + '" data-idx="' + (allItems.indexOf(item)) + '"' + cardBgStyle + '>' +
            '<div class="card-header">' +
                '<div class="card-icon type-' + escAttr(type) + '">' + icon + '</div>' +
                '<div class="card-title">' + esc(item.title) + '</div>' +
            '</div>' +
            (item.content ? '<div class="card-content">' + esc(item.content) + '</div>' : '') +
            '<div class="card-meta">' +
                '<span class="type-badge type-' + escAttr(type) + '">' + esc(item.type) + '</span>' +
                tags +
            '</div>' +
            '<div class="card-actions">' +
                '<button class="link-btn detail-trigger">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg> ' +
                    '查看详情</button>' +
                pwdBlock +
                '<span class="card-time">' + esc(item.add_time || '') + '</span>' +
            '</div>' +
        '</div>';
    }

    function esc(s) {
        s = String(s || '');
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function escAttr(s) {
        return esc(s).replace(/'/g, '&#39;');
    }

    // ============ 追加一批卡片（从内存 allItems 中取） ============
    function loadBatch() {
        // 已经在渲染或正在刷新，跳过
        if (isLoading || refreshing) return;

        // 数据全部渲染完了，不继续追加，等用户滚动触发刷新
        if (isEnd) return;

        var batch = allItems.slice(rendered, rendered + PAGE_SIZE);
        if (batch.length === 0) {
            isEnd = true;
            return;
        }

        isLoading = true;
        loadStatus.style.display = '';

        var html = '';
        for (var i = 0; i < batch.length; i++) {
            html += cardHTML(batch[i]);
        }

        if (rendered === 0) {
            cardList.innerHTML = html;
        } else {
            cardList.insertAdjacentHTML('beforeend', html);
        }

        rendered += batch.length;
        bindPwdClick();
        bindDetailTrigger();

        // 检查是否还有剩余
        if (rendered >= allItems.length) {
            isEnd = true;
            loadStatus.innerHTML = '<span class="load-dot"></span> 已加载全部，继续滑动获取更多';
        } else {
            loadStatus.style.display = 'none';
        }
        isLoading = false;
    }

    // ============ 全局加载遮罩 ============
    function showGlobalLoading() {
        if (globalLoading) {
            globalLoading.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    }
    function hideGlobalLoading() {
        if (globalLoading) {
            globalLoading.classList.remove('open');
            document.body.style.overflow = '';
        }
    }

    // ============ 从服务器拉新数据 ============
    function triggerRefresh() {
        if (refreshing) return;

        // 冷却检查
        var now = Date.now();
        if (now - lastRefreshTime < REFRESH_COOLDOWN) {
            return;
        }

        refreshing = true;
        lastRefreshTime = now;

        // 显示全局加载遮罩
        showGlobalLoading();

        // ★ 立即滚到顶部并暂停滚动监听，防止刷新期间抖动
        window.scrollTo({ top: 0, behavior: 'instant' });
        window.removeEventListener('scroll', onScroll, { passive: true });

        // 超时保护
        var refreshTimer = setTimeout(function () {
            if (refreshing) {
                finishRefresh(false);
                showToast('请求超时，请稍后重试');
            }
        }, 20000);

        // 第1步：触发 ting.php 从远程API拉数据
        fetch('ting.php?t=' + Date.now(), { cache: 'no-store' })
            .then(function (res) {
                if (!res.ok) throw new Error('服务器返回 ' + res.status);
                return res.json();
            })
            .then(function (tingResult) {
                if (tingResult.code !== 0) {
                    throw new Error(tingResult.msg || '数据拉取失败');
                }
                return fetch('?a=api&t=' + Date.now(), { cache: 'no-store' });
            })
            .then(function (res) {
                if (!res.ok) throw new Error('服务器返回 ' + res.status);
                return res.json();
            })
            .then(function (apiData) {
                clearTimeout(refreshTimer);

                var merged = [];
                if (apiData && apiData.data) {
                    var keys = Object.keys(apiData.data);
                    for (var k = 0; k < keys.length; k++) {
                        var items = apiData.data[keys[k]];
                        if (Array.isArray(items)) {
                            for (var j = 0; j < items.length; j++) {
                                merged.push(items[j]);
                            }
                        }
                    }
                }
                merged.sort(function (a, b) {
                    return String(b.add_time || '').localeCompare(String(a.add_time || ''));
                });

                allItems = merged;
                rendered = 0;
                isEnd = false;
                cardList.innerHTML = '';

                if (allItems.length > 0) {
                    finishRefresh(true);
                    loadBatch();
                } else {
                    finishRefresh(false);
                    loadStatus.style.display = 'none';
                    showEmpty();
                }
            })
            .catch(function (err) {
                clearTimeout(refreshTimer);
                finishRefresh(false);
                showToast('获取失败: ' + (err.message || '网络错误'));
            });
    }

    // 恢复滚动监听和状态
    function finishRefresh(success) {
        refreshing = false;
        isEnd = false;
        hideGlobalLoading();
        loadStatus.style.display = 'none';
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    function showEmpty() {
        emptyBox.style.display = '';
        emptyMsg.textContent = currentKeyword ? '未找到匹配的资源' : '暂无数据，请先刷新获取';
    }

    // ============ 搜索弹窗 ============
    function openSearchModal() {
        if (modalSearchInput) {
            modalSearchInput.value = '';
            // 短暂延迟让弹窗动画先走，再聚焦
            setTimeout(function () { modalSearchInput.focus(); }, 350);
        }
        if (searchOverlay) {
            searchOverlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSearchModal() {
        if (searchOverlay) {
            searchOverlay.classList.remove('open');
            document.body.style.overflow = '';
        }
    }

    // ============ 版权弹窗 ============
    function openCopyrightModal() {
        if (copyrightOverlay) {
            copyrightOverlay.classList.add('open');
            document.body.style.overflow = 'hidden';
            // 打开弹窗时拉取最新版本
            fetchLatestVersion();
        }
    }

    function closeCopyrightModal() {
        if (copyrightOverlay) {
            copyrightOverlay.classList.remove('open');
            document.body.style.overflow = '';
        }
    }

    // ============ 详情弹窗 ============
    function openDetailModal(item) {
        if (!detailOverlay) return;
        var type = String(item.type || '');
        var icon = type === 'quark' ? 'Q' : (type === 'guangya' ? 'G' : '1');

        // 图标
        if (detailIcon) {
            detailIcon.textContent = icon;
            detailIcon.className = 'detail-icon type-' + escAttr(type);
        }
        // 标题
        if (detailTitle) {
            detailTitle.textContent = item.title || '无标题';
        }
        // 类型
        if (detailType) {
            detailType.textContent = item.type || '-';
            detailType.className = 'detail-type type-' + escAttr(type);
        }
        // 图片（详情弹窗中的大图展示）
        if (detailImages) {
            var imgsHtml = imagesHTML(item.images);
            detailImages.innerHTML = imgsHtml;
            detailImages.style.display = imgsHtml ? '' : 'none';
        }
        // 正文
        if (detailContent) {
            detailContent.textContent = item.content || '';
            detailContent.style.display = item.content ? '' : 'none';
        }
        // 标签
        if (detailMeta) {
            var tagsHtml = '';
            if (item.tags) {
                var arr = String(item.tags).split(',');
                for (var i = 0; i < arr.length; i++) {
                    var t = arr[i].trim();
                    if (t) tagsHtml += '<span class="tag">#' + esc(t) + '</span>';
                }
            }
            detailMeta.innerHTML = tagsHtml;
        }
        // 操作区
        if (detailActions) {
            var html = '';
            html += '<button class="detail-link-btn detail-copy-url" data-url="' + escAttr(item.url) + '">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>' +
                '复制源链接</button>';
            if (item.password) {
                html += '<div class="detail-pwd" data-pwd="' + escAttr(item.password) + '">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>' +
                    '密码：<strong>' + esc(item.password) + '</strong>（点击复制）</div>';
            }
            if (item.add_time) {
                html += '<div class="detail-time">添加时间：' + esc(item.add_time) + '</div>';
            }
            detailActions.innerHTML = html;

            // 绑定源链接复制
            var copyUrlEl = detailActions.querySelector('.detail-copy-url');
            if (copyUrlEl) {
                copyUrlEl.addEventListener('click', function () {
                    var url = this.getAttribute('data-url');
                    if (!url) return;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(url).then(function () {
                            showToast('引擎提示：链接已复制！');
                        }).catch(function () {
                            fallbackCopy(url, '引擎提示：链接已复制！');
                        });
                    } else {
                        fallbackCopy(url, '引擎提示：链接已复制！');
                    }
                });
            }

            // 绑定密码点击
            var pwdEl = detailActions.querySelector('.detail-pwd');
            if (pwdEl) {
                pwdEl.addEventListener('click', function () {
                    var pwd = this.getAttribute('data-pwd');
                    if (!pwd) return;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(pwd).then(function () {
                            showToast('密码已复制: ' + pwd);
                        }).catch(function () {
                            fallbackCopy(pwd);
                        });
                    } else {
                        fallbackCopy(pwd);
                    }
                });
            }
        }

        detailOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeDetailModal() {
        if (detailOverlay) {
            detailOverlay.classList.remove('open');
            document.body.style.overflow = '';
        }
    }

    var versionFetched = false;

    function showLatestVersion(ver) {
        if (latestVersionEl) {
            latestVersionEl.textContent = ver;
            latestVersionEl.style.color = '';
        }
        var local = localVersionEl ? localVersionEl.textContent : '';
        if (ver && local && ver !== local) {
            if (updateHint) updateHint.style.display = '';
            if (connectorStatus) {
                connectorStatus.classList.add('is-outdated');
                connectorStatus.classList.remove('is-updated');
            }
        } else if (ver && local && ver === local) {
            if (connectorStatus) {
                connectorStatus.classList.add('is-updated');
                connectorStatus.classList.remove('is-outdated');
            }
        }
    }

    function showVersionFail() {
        if (latestVersionEl) {
            latestVersionEl.textContent = '获取失败';
            latestVersionEl.style.color = 'var(--text-muted)';
        }
        if (connectorStatus) {
            connectorStatus.classList.remove('is-outdated', 'is-updated');
        }
    }

    function fetchLatestVersion() {
        if (versionFetched) return;
        versionFetched = true;

        // 从后端 ?a=version 接口获取远程版本（由 version.php 统一管理）
        fetch('?a=version', { cache: 'no-store' }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        }).then(function (data) {
            if (data && data.code === 0 && data.data && data.data.remote) {
                showLatestVersion(data.data.remote);
            } else {
                showVersionFail();
            }
        }).catch(function () {
            showVersionFail();
        });
    }

    // 当前搜索关键词
    var currentKeyword = (typeof PANBBS_KEYWORD !== 'undefined' && PANBBS_KEYWORD) ? PANBBS_KEYWORD : '';
    // 当前选中的网盘类型（空数组=全部）
    var currentTypes = [];

    // 全选/取消全选
    function toggleAllTypes() {
        if (!searchTypeCheckboxes) return;
        var allChecked = true;
        for (var i = 0; i < searchTypeCheckboxes.length; i++) {
            if (!searchTypeCheckboxes[i].checked) { allChecked = false; break; }
        }
        var newState = !allChecked;
        for (var i = 0; i < searchTypeCheckboxes.length; i++) {
            searchTypeCheckboxes[i].checked = newState;
        }
        if (searchTypeToggle) {
            searchTypeToggle.textContent = newState ? '取消全选' : '全选';
        }
    }

    // 复选框变化时更新"全选"按钮文字
    function onTypeCheckChange() {
        if (!searchTypeCheckboxes || !searchTypeToggle) return;
        var allChecked = true;
        for (var i = 0; i < searchTypeCheckboxes.length; i++) {
            if (!searchTypeCheckboxes[i].checked) { allChecked = false; break; }
        }
        searchTypeToggle.textContent = allChecked ? '取消全选' : '全选';
    }

    // 获取当前选中的网盘类型
    function getCheckedTypes() {
        if (!searchTypeCheckboxes) return [];
        var types = [];
        for (var i = 0; i < searchTypeCheckboxes.length; i++) {
            if (searchTypeCheckboxes[i].checked) {
                types.push(searchTypeCheckboxes[i].value);
            }
        }
        return types;
    }



    function submitModalSearch() {
        var kw = modalSearchInput ? modalSearchInput.value.trim() : '';
        currentTypes = getCheckedTypes();
        closeSearchModal();

        if (kw === '') {
            // 清空搜索：刷新回首页无关键词，但保留 type 筛选
            currentKeyword = '';
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', window.location.pathname);
            }
            triggerSearchRefresh('', currentTypes);
            return;
        }

        currentKeyword = kw;
        // 更新浏览器地址栏
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '?kw=' + encodeURIComponent(kw));
        }
        // 更新 placeholder
        if (searchPlaceholder) {
            searchPlaceholder.textContent = '当前搜索: ' + kw;
        }
        // 显示清除按钮（如果页面初始没有的话，动态创建）
        if (!searchClearBtn) {
            var toolbar = document.querySelector('.toolbar');
            if (toolbar) {
                searchClearBtn = document.createElement('button');
                searchClearBtn.className = 'search-clear';
                searchClearBtn.id = 'searchClearBtn';
                searchClearBtn.textContent = '✕ 清除筛选';
                searchClearBtn.addEventListener('click', function () {
                    currentKeyword = '';
                    currentTypes = [];
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState(null, '', window.location.pathname);
                    }
                    if (searchPlaceholder) {
                        searchPlaceholder.textContent = '搜索要寻找的影片名...';
                    }
                    searchClearBtn.style.display = 'none';
                    triggerSearchRefresh('', []);
                });
                toolbar.appendChild(searchClearBtn);
            }
        } else {
            searchClearBtn.style.display = '';
        }
        // 通过远程API搜索
        triggerSearchRefresh(kw, currentTypes);
    }

    // ============ 搜索刷新：直接调用远程API获取并展示（不存储到本地JSON） ============
    function triggerSearchRefresh(kw, types) {
        if (refreshing) return;

        refreshing = true;
        showGlobalLoading();

        // 暂停滚动监听
        window.removeEventListener('scroll', onScroll, { passive: true });

        var searchTimedOut = false;

        // 超时保护：60秒，超时只提示不中断请求
        var refreshTimer = setTimeout(function () {
            if (refreshing && !searchTimedOut) {
                searchTimedOut = true;
                showToast('搜索耗时较长，请耐心等待...');
            }
        }, 60000);

        // 辅助：按 type 过滤数据
        function filterByTypes(items, types) {
            if (!types || types.length === 0) return items;
            var typeSet = {};
            for (var t = 0; t < types.length; t++) {
                typeSet[types[t]] = true;
            }
            return items.filter(function (item) {
                return typeSet[item.type] === true;
            });
        }

        if (kw) {
            // 搜索模式：直接请求远程API
            var searchUrl = '?a=search&kw=' + encodeURIComponent(kw);
            if (types && types.length > 0) {
                searchUrl += '&types=' + encodeURIComponent(types.join(','));
            }
            searchUrl += '&t=' + Date.now();
            fetch(searchUrl, { cache: 'no-store' })
                .then(function (res) {
                    if (!res.ok) throw new Error('服务器返回 ' + res.status);
                    return res.json();
                })
                .then(function (searchData) {
                    clearTimeout(refreshTimer);

                    if (searchData.code !== 0) {
                        throw new Error(searchData.msg || '搜索失败');
                    }

                    allItems = searchData.data || [];
                    // 如果后端没做筛选，前端兜底
                    if (types && types.length > 0) {
                        allItems = filterByTypes(allItems, types);
                    }
                    rendered = 0;
                    isEnd = false;
                    cardList.innerHTML = '';

                    if (allItems.length > 0) {
                        finishSearchRefresh();
                        loadBatch();
                    } else {
                        finishSearchRefresh();
                        loadStatus.style.display = 'none';
                        showEmpty();
                        if (emptyMsg) {
                            emptyMsg.textContent = '未找到匹配的资源';
                        }
                    }
                })
                .catch(function (err) {
                    clearTimeout(refreshTimer);
                    finishSearchRefresh();
                    showToast('搜索失败: ' + (err.message || '网络错误'));
                });
        } else {
            // 清除搜索：重新加载本地存储的数据
            fetch('?a=api&t=' + Date.now(), { cache: 'no-store' })
                .then(function (res) {
                    if (!res.ok) throw new Error('服务器返回 ' + res.status);
                    return res.json();
                })
                .then(function (apiData) {
                    clearTimeout(refreshTimer);

                    var merged = [];
                    if (apiData && apiData.data) {
                        var keys = Object.keys(apiData.data);
                        for (var k = 0; k < keys.length; k++) {
                            var items = apiData.data[keys[k]];
                            if (Array.isArray(items)) {
                                for (var j = 0; j < items.length; j++) {
                                    merged.push(items[j]);
                                }
                            }
                        }
                    }
                    merged.sort(function (a, b) {
                        return String(b.add_time || '').localeCompare(String(a.add_time || ''));
                    });

                    // 如果有 type 筛选，过滤
                    allItems = filterByTypes(merged, types);
                    rendered = 0;
                    isEnd = false;
                    cardList.innerHTML = '';

                    if (allItems.length > 0) {
                        finishSearchRefresh();
                        loadBatch();
                    } else {
                        finishSearchRefresh();
                        loadStatus.style.display = 'none';
                        showEmpty();
                    }
                })
                .catch(function (err) {
                    clearTimeout(refreshTimer);
                    finishSearchRefresh();
                    showToast('加载失败: ' + (err.message || '网络错误'));
                });
        }
    }

    function finishSearchRefresh() {
        refreshing = false;
        isEnd = false;
        hideGlobalLoading();
        loadStatus.style.display = 'none';
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    // ============ 滚动监听 ============
    var scrollTicking = false;
    function onScroll() {
        // 回到顶部按钮
        if (backToTop) {
            if (window.pageYOffset > 500) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        }

        if (scrollTicking) return;
        scrollTicking = true;
        requestAnimationFrame(function () {
            scrollTicking = false;

            var scrollBottom = window.innerHeight + window.pageYOffset;
            var docBottom = document.documentElement.scrollHeight;
            var nearBottom = scrollBottom >= docBottom - 400;

            if (!nearBottom) return;

            // 正在加载或刷新中，不重复触发
            if (isLoading || refreshing) return;

            // 已到底 → 触发刷新拉新数据
            if (isEnd) {
                triggerRefresh();
                return;
            }

            // 正常追加
            loadBatch();
        });
    }

    // ============ 密码复制 ============
    function bindPwdClick() {
        var pwds = document.querySelectorAll('.pwd-text');
        for (var i = 0; i < pwds.length; i++) {
            var el = pwds[i];
            if (el._pwdBound) continue;
            el._pwdBound = true;
            el.style.cursor = 'pointer';
            el.addEventListener('click', function () {
                var pwd = this.getAttribute('data-pwd');
                if (!pwd) return;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(pwd).then(function () {
                        showToast('密码已复制: ' + pwd);
                    }).catch(function () {
                        fallbackCopy(pwd);
                    });
                } else {
                    fallbackCopy(pwd);
                }
            });
        }
    }

    // ============ 绑定详情弹窗触发器 ============
    function bindDetailTrigger() {
        var triggers = document.querySelectorAll('.detail-trigger');
        for (var i = 0; i < triggers.length; i++) {
            var el = triggers[i];
            if (el._detailBound) continue;
            el._detailBound = true;
            el.addEventListener('click', function () {
                var card = this.closest('.card');
                if (!card) return;
                var idx = parseInt(card.getAttribute('data-idx'), 10);
                if (isNaN(idx) || idx < 0 || idx >= allItems.length) return;
                openDetailModal(allItems[idx]);
            });
        }
    }

    function fallbackCopy(text, msg) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); showToast(msg || ('密码已复制: ' + text)); } catch (e) {}
        document.body.removeChild(ta);
    }

    // ============ 初始化 ============
    function init() {
        cardList = document.getElementById('cardList');
        emptyBox = document.getElementById('emptyBox');
        emptyMsg = document.getElementById('emptyMsg');
        loadStatus = document.getElementById('loadStatus');
        skeletonBox = document.getElementById('skeletonBox');
        backToTop = document.getElementById('backToTop');
        fabSearch = document.getElementById('fabSearch');
        searchOverlay = document.getElementById('searchOverlay');
        modalSearchInput = document.getElementById('modalSearchInput');
        modalSearchBtn = document.getElementById('modalSearchBtn');
        searchModalClose = document.getElementById('searchModalClose');
        globalLoading = document.getElementById('globalLoading');
        var headerSearchBtn = document.getElementById('headerSearchBtn');
        var inlineSearchBtn = document.getElementById('inlineSearchBtn');
        searchPlaceholder = document.getElementById('searchPlaceholder');
        searchClearBtn = document.getElementById('searchClearBtn');
        fabCopyright = document.getElementById('fabCopyright');
        var fabTheme = document.getElementById('fabTheme');
        copyrightOverlay = document.getElementById('copyrightOverlay');
        copyrightModalClose = document.getElementById('copyrightModalClose');
        localVersionEl = document.getElementById('localVersion');
        latestVersionEl = document.getElementById('latestVersion');
        updateHint = document.getElementById('updateHint');
        connectorStatus = document.getElementById('connectorStatus');
        detailOverlay = document.getElementById('detailOverlay');
        detailModalClose = document.getElementById('detailModalClose');
        detailIcon = document.getElementById('detailIcon');
        detailTitle = document.getElementById('detailTitle');
        detailType = document.getElementById('detailType');
        detailImages = document.getElementById('detailImages');
        detailContent = document.getElementById('detailContent');
        detailMeta = document.getElementById('detailMeta');
        detailActions = document.getElementById('detailActions');
        searchTypeCheckboxes = document.querySelectorAll('#searchTypeFilter input[name="searchType"]');
        searchTypeToggle = document.getElementById('searchTypeToggle');


        // 如果有初始关键词，更新 placeholder
        if (currentKeyword && searchPlaceholder) {
            searchPlaceholder.textContent = '当前搜索: ' + currentKeyword;
        }

        // 移除骨架屏
        if (skeletonBox) skeletonBox.style.display = 'none';

        // 回到顶部
        if (backToTop) {
            backToTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        // 页面中搜索触发器：点击打开弹窗
        if (inlineSearchBtn && searchOverlay) {
            inlineSearchBtn.addEventListener('click', function () {
                openSearchModal();
            });
        }

        // 清除筛选按钮
        if (searchClearBtn) {
            searchClearBtn.addEventListener('click', function () {
                currentKeyword = '';
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', window.location.pathname);
                }
                if (searchPlaceholder) {
                    searchPlaceholder.textContent = '搜索要寻找的影片名...';
                }
                // 清除按钮自身
                searchClearBtn.style.display = 'none';
                // 重新加载全部数据
                triggerSearchRefresh('');
            });
        }

        // 右下角版权弹窗
        if (fabCopyright && copyrightOverlay) {
            fabCopyright.addEventListener('click', function () {
                openCopyrightModal();
            });
        }
        if (copyrightModalClose) {
            copyrightModalClose.addEventListener('click', function () {
                closeCopyrightModal();
            });
        }

        // 详情弹窗关闭
        if (detailModalClose) {
            detailModalClose.addEventListener('click', function () {
                closeDetailModal();
            });
        }

        // 右下角搜索弹窗 + header搜索按钮
        if (fabSearch && searchOverlay) {
            fabSearch.addEventListener('click', function () {
                openSearchModal();
            });
        }
        if (headerSearchBtn && searchOverlay) {
            headerSearchBtn.addEventListener('click', function () {
                openSearchModal();
            });
        }
        if (searchModalClose) {
            searchModalClose.addEventListener('click', function () {
                closeSearchModal();
            });
        }
        // 点击空白处不关闭，只能通过X按钮关闭
        if (modalSearchBtn) {
            modalSearchBtn.addEventListener('click', function () {
                submitModalSearch();
            });
        }
        if (modalSearchInput) {
            modalSearchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitModalSearch();
                }
            });
        }
        // ESC 键不再关闭弹窗，只能通过X按钮关闭

        // 网盘类型筛选复选框
        if (searchTypeCheckboxes) {
            for (var i = 0; i < searchTypeCheckboxes.length; i++) {
                searchTypeCheckboxes[i].addEventListener('change', onTypeCheckChange);
            }
        }
        // 全选/取消全选按钮
        if (searchTypeToggle) {
            searchTypeToggle.addEventListener('click', function (e) {
                e.preventDefault();
                toggleAllTypes();
            });
        }

        window.addEventListener('scroll', onScroll, { passive: true });

        // ============ 主题切换 ============
        // 读取本地存储的偏好，覆盖后端默认值
        (function () {
            var savedTheme = localStorage.getItem('panbbs_theme');
            var html = document.documentElement;
            if (savedTheme === 'dark' || savedTheme === 'light') {
                html.className = html.className.replace(/theme-(light|dark)/, 'theme-' + savedTheme);
            }
        })();

        if (fabTheme) {
            fabTheme.addEventListener('click', function () {
                var html = document.documentElement;
                var isLight = html.className.indexOf('theme-light') !== -1;
                var newTheme = isLight ? 'dark' : 'light';
                html.className = html.className.replace(/theme-(light|dark)/, 'theme-' + newTheme);
                localStorage.setItem('panbbs_theme', newTheme);
            });
        }

        // 没有初始数据，或有关键词需要搜索
        if (allItems.length === 0 || currentKeyword) {
            loadStatus.style.display = 'none';
            // 如果有关键词，触发远程搜索
            if (currentKeyword) {
                triggerSearchRefresh(currentKeyword);
                return;
            }
            showEmpty();
            return;
        }

        // 有数据：隐藏 loadStatus，开始首屏渲染
        loadStatus.style.display = 'none';
        loadBatch();
    }

    // DOM 就绪后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
