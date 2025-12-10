<?php
// 数据库配置
$db_host = "localhost";
$db_user = "nekopan"; // 你的数据库账号
$db_pwd = "04dd9a883b"; // 你的数据库密码
$db_name = "nekopan";

// 连接数据库
$conn = mysqli_connect($db_host, $db_user, $db_pwd, $db_name);
if (!$conn) {
    die("数据库连接失败: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>