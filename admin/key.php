<?php
error_reporting(0);
session_start();
include '../common/config.php';
include '../common/key.php';

// 权限验证
if (!isset($_SESSION['nekoadmin']) || $config['admin'] == '0') {
    die("<script>alert('权限不足！');window.location.href='../';</script>");
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $oldKey = trim($_POST['key'] ?? '');
    $newKey = trim($_POST['keynew'] ?? '');
    $confirmKey = trim($_POST['confirmkey'] ?? '');

    if ($oldKey !== $key) {
        $msg = "<script>alert('原密匙错误！');</script>";
    } elseif (strlen($newKey) < 8 || strlen($newKey) > 32) {
        $msg = "<script>alert('新密匙长度需在8-32位之间！');</script>";
    } elseif ($newKey !== $confirmKey) {
        $msg = "<script>alert('两次输入的新密匙不一致！');</script>";
    } else {
        $escapedKey = addslashes($newKey);
        $_data = "<?php\n\$key= '$escapedKey';\n/*

*/?>";

        $keyPath = '../common/key.php';
        if (file_put_contents($keyPath, $_data)) {
            $msg = "<script>alert('密匙修改成功！请重新登录');window.location.href='index.php';</script>";
        } else {
            $msg = "<script>alert('修改失败！请检查文件权限');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密匙 - 喵次元网盘后台</title>
    <link rel="stylesheet" href="https://cdn.staticfile.org/layui/2.5.6/css/layui.min.css">
    <link rel="stylesheet" href="../static/css/anime.css">
</head>
<body>
    <div class="anime-card" style="max-width: 500px;">
        <h2 class="anime-title"> 喵次元网盘 🔑 修改管理密匙</h2>
        <form method="post" class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color: red;">*</span> 原密匙</label>
                <div class="layui-input-block">
                    <input type="password" name="key" class="anime-input" required placeholder="输入原管理密匙">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color: red;">*</span> 新密匙</label>
                <div class="layui-input-block">
                    <input type="password" name="keynew" class="anime-input" required placeholder="8-32位字母/数字/符号组合">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color: red;">*</span> 确认新密匙</label>
                <div class="layui-input-block">
                    <input type="password" name="confirmkey" class="anime-input" required placeholder="再次输入新密匙">
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button type="submit" class="anime-btn">保存新密匙</button>
                    <a href="index.php" class="anime-btn" style="background: #ff8c00; margin-left:10px;">返回首页</a>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.staticfile.org/layui/2.5.6/layui.min.js"></script>
    <script>
        layui.use('form', function(){
            var form = layui.form;
        });
    </script>
    <?=$msg?>
</body>
</html>
