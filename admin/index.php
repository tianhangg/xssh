<?php
error_reporting(0);
session_start();
include '../common/config.php';
include '../common/key.php';

// 后台开关验证
if ($config['admin'] == '0') {
    die("<script>alert('后台已关闭');window.location.href='../';</script>");
}

// 密匙验证登录
if (isset($_POST['key'])) {
    if ($_POST['key'] == $key) {
        $_SESSION['nekoadmin'] = '1';
    } else {
        $error = "<script>alert('密匙错误！');</script>";
    }
}

if (!isset($_SESSION['nekoadmin'])) {
    die('
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>后台登录 - 喵次元网盘</title>
        <link rel="stylesheet" href="https://cdn.staticfile.org/layui/2.5.6/css/layui.min.css">
        <link rel="stylesheet" href="../static/css/anime.css">
    </head>
    <body>
        <div class="anime-card" style="max-width: 400px;">
            <h2 class="anime-title"> 喵次元网盘 🔒 后台登录</h2>
            <form method="post" class="layui-form">
                <div class="layui-form-item">
                    <input type="password" name="key" class="anime-input" placeholder="输入管理密匙" required>
                </div>
                <div class="layui-form-item" style="text-align: center;">
                    <button type="submit" class="anime-btn">登录</button>
                </div>
            </form>
        </div>
        '.$error.'
    </body>
    </html>
    ');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - 喵次元网盘</title>
    <link rel="stylesheet" href="https://cdn.staticfile.org/layui/2.5.6/css/layui.min.css">
    <link rel="stylesheet" href="../static/css/anime.css">
</head>
<body>
    <div class="anime-card" style="max-width: 600px;">
        <h2 class="anime-title">🐾 喵次元网盘后台</h2>

        <div class="layui-btn-container" style="display: flex; gap: 15px; flex-direction: column; margin: 20px 0;">
            <a href="file.php" class="anime-btn">📁 文件管理</a>
            <a href="set.php" class="anime-btn">⚙️ 网站配置</a>
            <a href="key.php" class="anime-btn">🔑 修改密匙</a>
            <a href="../" class="anime-btn" style="background: #ff8c00;">🏠 返回前台</a>
        </div>

        <div style="text-align: center; color: #999;">
            <?=$config['foot']?>
        </div>
    </div>

    <script src="https://cdn.staticfile.org/layui/2.5.6/layui.min.js"></script>
</body>
</html>
