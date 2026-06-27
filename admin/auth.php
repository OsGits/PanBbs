<?php
/**
 * 后台 - 登录验证模块
 * 被 admin.php 引用，不直接访问
 */

if (!defined('ADMIN_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

/**
 * 检查登录状态，处理登录请求
 * @param array $accounts 账号密码数组
 * @return true|array  true=已登录, ['error'=>'msg']=登录失败
 */
function adminCheckLogin($accounts) {
    // 已登录
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return true;
    }

    // 处理登录请求
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        if (isset($accounts[$username]) && $accounts[$username] === $password) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            header('Location: admin.php');
            exit;
        }

        return ['error' => '账号或密码错误'];
    }

    return false;
}
