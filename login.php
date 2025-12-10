<?php
error_reporting(0);
session_start();
include 'common/config.php';
include 'common/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $user = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($user) > 0) {
        $userData = mysqli_fetch_assoc($user);
        if (password_verify($password, $userData['password'])) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            header("Location: index.php");
            exit;
        } else {
            $msg = "<script>alert('密码错误！');</script>";
        }
    } else {
        $msg = "<script>alert('用户不存在！');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>萌系登录 - 喵次元网盘 <?=$config['title']?></title>
    <link rel="stylesheet" href="static/css/anime.css">
</head>
<body>
    <div class="anime-card" style="max-width: 400px;">
        <h2 class="anime-title"> 喵次元网盘 🔑 用户登录</h2>
        <form method="post">
            <input type="text" name="username" class="anime-input" placeholder="用户名" required><br>
            <input type="password" name="password" class="anime-input" placeholder="密码" required><br>
            <button type="submit" class="anime-btn" style="width:100%; margin-top:10px;">登录</button>
        </form>
        <p style="text-align: center; margin-top:15px;">
            没有账号？<a href="register.php" style="color:#ff69b4;">立即注册</a>
        </p>
    </div>
    <?=$msg?>
</body>
</html>