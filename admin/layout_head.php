<?php
/**
 * 后台 - 公共 HTML 头部 + 全局样式
 * 被各页面模板 require 使用
 */

if (!defined('ADMIN_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

/**
 * 输出后台页面公共 <head> 部分
 * @param string $title 页面标题
 */
function adminHead($title = '管理面板') {
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PanBbs - <?php echo htmlspecialchars($title); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            background: #f0f2f5;
            color: #333;
            min-height: 100vh;
        }
        /* 整体布局 */
        .app-layout {
            display: flex;
            min-height: calc(100vh - 56px);
        }
        /* 顶部导航 */
        .topbar {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            padding: 0 24px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar .logo {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .topbar .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 14px;
        }
        .topbar .user-info .username {
            color: #a0c4ff;
        }
        .btn-sm {
            padding: 6px 14px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            background: transparent;
            color: #ccc;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-sm:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        /* 侧边栏 */
        .sidebar {
            width: 220px;
            background: #fff;
            border-right: 1px solid #e8e8e8;
            padding: 16px 0;
            flex-shrink: 0;
            min-height: calc(100vh - 56px);
        }
        .sidebar .nav-title {
            font-size: 11px;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 8px 20px 12px;
            font-weight: 600;
        }
        .sidebar .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 20px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.15s;
            border-left: 3px solid transparent;
            margin: 2px 0;
        }
        .sidebar .nav-item:hover {
            background: #f5f7fa;
            color: #1a1a2e;
        }
        .sidebar .nav-item.active {
            background: #eef2ff;
            color: #0f3460;
            border-left-color: #0f3460;
            font-weight: 600;
        }
        .sidebar .nav-item .nav-icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        /* 主内容区 */
        .main-content {
            flex: 1;
            padding: 24px;
            overflow-x: auto;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .page-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1a1a2e;
        }
        /* 统计卡片 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e8e8e8;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .stat-card .stat-label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .stat-card .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a2e;
        }
        .stat-card.total .stat-value { color: #0f3460; }
        .stat-card.version .stat-value { font-size: 18px; color: #666; }
        /* 面板区块 */
        .panel {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e8e8e8;
        }
        .panel h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panel h3::before {
            content: '';
            width: 4px;
            height: 18px;
            background: #0f3460;
            border-radius: 2px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary {
            background: #0f3460;
            color: #fff;
        }
        .btn-primary:hover {
            background: #1a4a8a;
            box-shadow: 0 4px 12px rgba(15,52,96,0.3);
        }
        .btn-danger {
            background: #fff;
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
        .btn-danger:hover {
            background: #e74c3c;
            color: #fff;
        }
        .btn-warning {
            background: #fff;
            color: #f39c12;
            border: 1px solid #f39c12;
        }
        .btn-warning:hover {
            background: #f39c12;
            color: #fff;
        }
        .btn-sm-inline {
            padding: 4px 12px;
            font-size: 12px;
            border-radius: 5px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        /* 类型列表 */
        .type-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .type-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .type-item .type-name {
            font-weight: 600;
            color: #1a1a2e;
        }
        .type-item .type-count {
            color: #888;
            font-size: 13px;
            margin-left: 12px;
        }
        .type-item .type-actions {
            display: flex;
            gap: 8px;
        }
        /* Toast 提示 */
        .toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            animation: slideDown 0.3s ease;
            pointer-events: none;
        }
        .toast.success { background: #27ae60; color: #fff; }
        .toast.error { background: #e74c3c; color: #fff; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        /* 表单 */
        .form-row {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .form-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .form-field label {
            font-size: 12px;
            color: #888;
            font-weight: 500;
        }
        .form-field input, .form-field select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
            width: 160px;
        }
        .form-field input:focus, .form-field select:focus {
            border-color: #0f3460;
            box-shadow: 0 0 0 2px rgba(15,52,96,0.1);
        }
        .form-field.wide input { width: 320px; }
        /* 版本卡片 */
        .version-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #eee;
            margin-bottom: 12px;
        }
        .version-card .ver-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .version-card .ver-tag {
            font-weight: 600;
            color: #1a1a2e;
            font-size: 15px;
        }
        .version-card .ver-date {
            font-size: 12px;
            color: #999;
        }
        .version-card .ver-badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .ver-badge.current {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .ver-badge.remote {
            background: #e3f2fd;
            color: #1565c0;
        }
        .setting-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .setting-item:last-child {
            border-bottom: none;
        }
        .setting-item .setting-label {
            font-weight: 500;
            color: #333;
        }
        .setting-item .setting-desc {
            font-size: 12px;
            color: #999;
            margin-top: 2px;
        }
        /* 标签页导航 */
        .tab-nav {
            display: flex;
            gap: 0;
            margin-bottom: 0;
            border-bottom: 2px solid #e8e8e8;
        }
        .tab-btn {
            padding: 10px 24px;
            border: none;
            background: transparent;
            font-size: 14px;
            font-weight: 500;
            color: #888;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
            outline: none;
        }
        .tab-btn:hover {
            color: #0f3460;
        }
        .tab-btn.active {
            color: #0f3460;
            border-bottom-color: #0f3460;
            font-weight: 600;
        }
        .tab-panel {
            border-top: none;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            margin-top: 20px;
        }
        /* README 内容渲染 */
        .readme-panel {
            max-width: 100%;
            overflow-x: auto;
        }
        .readme-body {
            font-size: 14px;
            line-height: 1.8;
            color: #444;
        }
        .readme-body h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a2e;
            margin: 24px 0 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        .readme-body h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin: 20px 0 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #f0f0f0;
        }
        .readme-body h3 {
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin: 16px 0 8px;
        }
        .readme-body h4, .readme-body h5, .readme-body h6 {
            font-size: 14px;
            font-weight: 600;
            color: #555;
            margin: 12px 0 6px;
        }
        .readme-body p {
            margin: 8px 0;
        }
        .readme-body li {
            margin: 4px 0 4px 20px;
            list-style: disc;
        }
        .readme-body strong {
            color: #1a1a2e;
        }
        .readme-body a {
            color: #0f3460;
            text-decoration: none;
            border-bottom: 1px dashed #0f3460;
        }
        .readme-body a:hover {
            color: #1a4a8a;
            border-bottom-style: solid;
        }
        .readme-body code.inline-code {
            background: #f0f2f5;
            color: #e74c3c;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
            font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
        }
        .readme-body pre {
            background: #1a1a2e;
            color: #e0e0e0;
            padding: 16px 20px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 12px 0;
            font-size: 13px;
            line-height: 1.6;
        }
        .readme-body pre code {
            font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
        }
        .readme-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 13px;
        }
        .readme-body table td {
            padding: 8px 12px;
            border: 1px solid #e8e8e8;
        }
        .readme-body table tr:nth-child(even) td {
            background: #f8f9fa;
        }
        .readme-body hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 16px 0;
        }
        .readme-body br {
            display: block;
            content: '';
            margin: 4px 0;
        }
        /* 响应式 */
        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
                padding: 8px 0;
            }
            .sidebar .nav-item {
                justify-content: center;
                padding: 12px 0;
            }
            .sidebar .nav-item .nav-text,
            .sidebar .nav-title {
                display: none;
            }
            .main-content {
                padding: 16px;
            }
        }
        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .form-row { flex-direction: column; }
            .form-field input, .form-field select { width: 100%; }
            .form-field.wide input { width: 100%; }
            .topbar { padding: 0 12px; }
        }
    </style>
</head>
    <?php
}
