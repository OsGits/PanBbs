<?php
/**
 * 后台 - 登录页面模板
 */

if (!defined('ADMIN_ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access Denied');
}

/**
 * 渲染登录页面
 * @param string $errorMsg 错误信息（空字符串表示无错误）
 */
function adminShowLoginPage($errorMsg = '') {
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PanBbs - 后台登录</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 48px 40px;
            width: 400px;
            max-width: 90vw;
        }
        .login-box h1 {
            color: #e0e0e0;
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .login-box .subtitle {
            color: #888;
            text-align: center;
            font-size: 14px;
            margin-bottom: 36px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: #aaa;
            font-size: 13px;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            background: rgba(255,255,255,0.06);
            color: #e0e0e0;
            font-size: 15px;
            outline: none;
            transition: all 0.3s;
        }
        .form-group input:focus {
            border-color: #0f3460;
            background: rgba(255,255,255,0.1);
            box-shadow: 0 0 0 3px rgba(15, 52, 96, 0.3);
        }
        .btn-login {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #0f3460, #1a1a6e);
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 8px;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(15, 52, 96, 0.4);
        }
        .error-msg {
            background: rgba(255, 71, 87, 0.15);
            border: 1px solid rgba(255, 71, 87, 0.3);
            color: #ff6b7a;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            text-align: center;
            margin-bottom: 20px;
        }
        .security-tip {
            color: #666;
            text-align: center;
            font-size: 12px;
            margin-top: 24px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔐 PanBbs</h1>
        <p class="subtitle">后台管理系统</p>
        <?php if ($errorMsg): ?>
        <div class="error-msg"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>
        <form method="post" action="admin.php">
            <div class="form-group">
                <label>账号</label>
                <input type="text" name="username" placeholder="请输入管理员账号" required autofocus>
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" placeholder="请输入密码" required>
            </div>
            <button type="submit" class="btn-login">登 录</button>
        </form>
        <p class="security-tip">
            默认账号: admin / 密码: panbbs2024<br>
            请登录后尽快修改密码
        </p>
    </div>
</body>
</html>
    <?php
    exit;
}
