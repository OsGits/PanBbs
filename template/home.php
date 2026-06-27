<?php
/**
 * 网盘资源聚合 - 前端展示页面（模块中枢）
 * 全量数据写入 JS 变量，由前端控制分批加载（每次10条）
 * 到底自动触发 ting.php 刷新并重置
 *
 * 模块划分：
 *   _head.php           - HTML头部 + 数据准备
 *   _header.php         - 顶部导航栏
 *   _toolbar.php        - 工具栏 + 骨架屏 + 加载指示器
 *   _content.php        - 卡片容器 + 空状态
 *   _fab.php            - 右下角浮动按钮组
 *   _modal_version.php  - 版本信息弹窗
 *   _modal_search.php   - 搜索弹窗
 *   _footer.php         - 页脚 + 全局加载 + JS注入
 */

require __DIR__ . '/_head.php';
require __DIR__ . '/_header.php';
require __DIR__ . '/_toolbar.php';
require __DIR__ . '/_content.php';
require __DIR__ . '/_fab.php';
require __DIR__ . '/_modal_version.php';
require __DIR__ . '/_modal_search.php';
require __DIR__ . '/_footer.php';
