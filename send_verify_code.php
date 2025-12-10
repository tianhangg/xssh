<?php
session_start();
include 'common/db.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json; charset=utf-8");

// 只允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['code' => 0, 'msg' => '请求方式错误']);
    exit;
}

// 接收并过滤邮箱
$email = trim($_POST['email'] ?? '');
$email = mysqli_real_escape_string($conn, $email);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['code' => 0, 'msg' => '邮箱格式不正确']);
    exit;
}

// 检查邮箱是否已注册（有 username 即为已注册）
$check_sql = "SELECT * FROM users WHERE email = '{$email}' AND username IS NOT NULL";
$check_res = mysqli_query($conn, $check_sql);
if (mysqli_num_rows($check_res) > 0) {
    echo json_encode(['code' => 0, 'msg' => '该邮箱已被注册']);
    exit;
}

// 生成 6 位数字验证码
$verify_code = rand(100000, 999999);
// 有效期 10 分钟
$expire_time = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// 先删除该邮箱的旧验证码记录
$del_sql = "DELETE FROM users WHERE email = '{$email}' AND username IS NULL";
mysqli_query($conn, $del_sql);

// 插入新的临时验证码记录
$insert_sql = "INSERT INTO users (email, verify_code, code_expire, create_time) 
               VALUES ('{$email}', '{$verify_code}', '{$expire_time}', NOW())";
if (!mysqli_query($conn, $insert_sql)) {
    echo json_encode(['code' => 0, 'msg' => '验证码写入数据库失败：' . mysqli_error($conn)]);
    exit;
}

// ==================== 配置邮件发送 ====================
$mail = new PHPMailer(true);
try {
    // 服务器配置（根据你的邮箱修改）
    $mail->SMTPDebug = SMTP::DEBUG_OFF; // 上线后关闭调试
    $mail->isSMTP();
    $mail->Host       = 'xxxxxx'; // QQ邮箱填这个，163填 smtp.163.com
    $mail->SMTPAuth   = true;
    $mail->Username   = 'xxxxxx@qq.com'; // 发送邮件的邮箱
    $mail->Password   = 'xxxxxx'; // SMTP授权码，不是登录密码
    $mail->SMTPSecure = SMTP::ENCRYPTION_SMTPS;
    $mail->Port       = 587; // 端口对应，QQ邮箱465，163邮箱465

    // 收件人
    $mail->setFrom('xxxxxx@qq.com', '喵次元网盘');
    $mail->addAddress($email); // 发送到用户填写的邮箱

    // 邮件内容
    $mail->isHTML(true);
    $mail->Subject = '喵次元网盘注册验证码';
    $mail->Body    = "你好！你的注册验证码是：<b>{$verify_code}</b><br>有效期10分钟，请及时使用。";
    $mail->AltBody = "你好！你的注册验证码是：{$verify_code}，有效期10分钟，请及时使用。";

    $mail->send();
    echo json_encode(['code' => 1, 'msg' => '验证码已发送到你的邮箱']);
} catch (Exception $e) {
    // 邮件发送失败 → 删除数据库临时记录
    mysqli_query($conn, "DELETE FROM users WHERE email = '{$email}' AND verify_code = '{$verify_code}'");
    echo json_encode(['code' => 0, 'msg' => '邮件发送失败：' . $mail->ErrorInfo]);
}
?>
