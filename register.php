<?php
// 开启错误显示（调试阶段用，上线后改回 error_reporting(0)）
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'common/config.php';
include 'common/db.php';

// 检查数据库连接
if (!$conn) {
    die("<script>alert('数据库连接失败：" . mysqli_connect_error() . "');</script>");
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    // 初始化变量，避免未定义警告
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // 1. 基础验证
    if (empty($username) || empty($email) || empty($password)) {
        $msg = "<script>alert('请填写所有必填项！');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "<script>alert('邮箱格式不正确！');</script>";
    } elseif (strlen($password) < 8) {
        $msg = "<script>alert('密码长度不能少于8位！');</script>";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
        // 密码规则：必须包含小写字母、大写字母、数字，长度≥8位，无特殊字符
        $msg = "<script>alert('密码需包含大小写字母和数字！');</script>";
    } else {
        // 2. 防SQL注入
        $username = mysqli_real_escape_string($conn, $username);
        $email = mysqli_real_escape_string($conn, $email);

        // 3. 验证用户名和邮箱是否已存在
        $check_user = "SELECT * FROM users WHERE username = '{$username}' LIMIT 1";
        $res_user = mysqli_query($conn, $check_user);
        if (!$res_user) {
            $msg = "<script>alert('查询用户名失败：" . mysqli_error($conn) . "');</script>";
        } elseif (mysqli_num_rows($res_user) > 0) {
            $msg = "<script>alert('用户名已被注册！');</script>";
        } else {
            $check_email = "SELECT * FROM users WHERE email = '{$email}' LIMIT 1";
            $res_email = mysqli_query($conn, $check_email);
            if (!$res_email) {
                $msg = "<script>alert('查询邮箱失败：" . mysqli_error($conn) . "');</script>";
            } elseif (mysqli_num_rows($res_email) > 0) {
                $msg = "<script>alert('该邮箱已绑定其他账号！');</script>";
            } else {
                // 4. 密码加密
                $hash_pwd = password_hash($password, PASSWORD_DEFAULT);
                if (!$hash_pwd) {
                    $msg = "<script>alert('密码加密失败！');</script>";
                } else {
                    // 5. 插入用户数据
                    $reg_sql = "INSERT INTO users (username, email, password, create_time) 
                               VALUES ('{$username}', '{$email}', '{$hash_pwd}', NOW())";
                    $result = mysqli_query($conn, $reg_sql);
                    if ($result) {
                        $msg = "<script>alert('注册成功！请登录');window.location.href='login.php';</script>";
                    } else {
                        $msg = "<script>alert('注册失败：" . mysqli_error($conn) . "');</script>";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册 - 喵次元网盘</title>
    <link rel="stylesheet" href="static/css/anime.css">
    <script src="https://cdn.staticfile.org/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>
    <div class="anime-card" style="max-width: 400px; margin: 50px auto;">
        <h2 class="anime-title" style="text-align: center;"> 喵次元网盘 ✨ 用户注册</h2>
        <form method="post" id="reg-form">
            <input type="text" name="username" class="anime-input" placeholder="请输入用户名" required><br>
            <input type="email" name="email" class="anime-input" placeholder="请输入邮箱" required><br>
            <input type="password" name="password" class="anime-input" placeholder="请输入密码（≥8位，含大小写字母和数字）" required><br>
            <button type="submit" name="submit" class="anime-btn" style="width: 100%;">注册</button>
        </form>
        <p style="text-align: center; margin-top: 15px;">
            已有账号？<a href="login.php" style="color:#ff69b4;">立即登录</a>
        </p>
    </div>
    <?=$msg?>
</body>
</html>
