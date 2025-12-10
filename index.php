<?php
error_reporting(0);
session_start();
include 'common/config.php';
include 'common/db.php';
$uploadDir = 'upload/';

// 登录验证：未登录跳转到登录页
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = intval($_SESSION['user_id']);
$username = mysqli_real_escape_string($conn, $_SESSION['username']);

// 创建用户私有目录（带权限校验）
$userUploadDir = $uploadDir . "user_" . $user_id . "/";
if (!is_dir($userUploadDir)) {
    mkdir($userUploadDir, 0777, true);
    chmod($userUploadDir, 0777); // 确保目录可写
}

// 配置允许上传的文件类型（白名单）
$allowedExts = [
    // 图片
    'jpg', 'jpeg', 'png',
    // 文档
    'txt',
    // 压缩包
    'zip', 'rar',
];
$allowedMimes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/pdf', 'text/plain',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'
];

// ==================== 1. 批量上传 + 数据库强同步 ====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['files'])) {
    $isEncrypt = isset($_POST['encrypt']) ? 1 : 0;
    $encryptPwd = $isEncrypt ? mysqli_real_escape_string($conn, $_POST['encrypt_pwd']) : '';
    $files = $_FILES['files'];
    $uploadSuccess = 0;
    $uploadFail = 0;
    $errorMsg = [];

    foreach ($files['name'] as $key => $name) {
        // 跳过空文件
        if ($files['error'][$key] != 0) {
            $errorMsg[] = $name . "：文件上传错误（错误码：" . $files['error'][$key] . "）";
            $uploadFail++;
            continue;
        }

        $tmpName = $files['tmp_name'][$key];
        $fileSize = $files['size'][$key];
        $fileMime = mysqli_real_escape_string($conn, mime_content_type($tmpName));
        $fileName = mysqli_real_escape_string($conn, basename($name));
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $uniqueFileName = uniqid('neko_') . '_' . $fileName; // 唯一文件名防止覆盖
        $filePath = $userUploadDir . $uniqueFileName;
        $filePathDb = mysqli_real_escape_string($conn, $filePath);
        $sizeShow = round($fileSize / 1024, 2) . 'KB';

        // 三重验证：类型 + MIME + 大小
        $isExtAllowed = in_array($fileExt, $allowedExts);
        $isMimeAllowed = in_array($fileMime, $allowedMimes);
        $isSizeAllowed = $fileSize > 0 && $fileSize <= $config['maxsize'];

        if (!$isExtAllowed || !$isMimeAllowed) {
            $errorMsg[] = $name . "：不支持的文件类型";
            $uploadFail++;
            continue;
        }
        if (!$isSizeAllowed) {
            $errorMsg[] = $name . "：文件大小超出限制（最大支持" . ($config['maxsize']/1024/1024) . "MB）";
            $uploadFail++;
            continue;
        }

        // 先上传文件到服务器，再写入数据库（强同步）
        if (move_uploaded_file($tmpName, $filePath)) {
            // 写入数据库 SQL
            $sql = "INSERT INTO files (user_id, file_name, file_path, file_size, is_encrypt, encrypt_pwd, create_time) 
                    VALUES ('{$user_id}', '{$fileName}', '{$filePathDb}', '{$sizeShow}', '{$isEncrypt}', '{$encryptPwd}', NOW())";
            
            if (mysqli_query($conn, $sql)) {
                $uploadSuccess++;
            } else {
                // 数据库写入失败 → 删除已上传的文件（回滚）
                unlink($filePath);
                $errorMsg[] = $name . "：文件上传成功，但数据库记录失败（错误：" . mysqli_error($conn) . "）";
                $uploadFail++;
            }
        } else {
            $errorMsg[] = $name . "：文件上传到服务器失败";
            $uploadFail++;
        }
    }

    // 修复字符串拼接：避免引号和注释冲突
    $alertContent = "上传完成！成功：{$uploadSuccess} 个，失败：{$uploadFail} 个";
    if (!empty($errorMsg)) {
        $alertContent .= "\\n" . implode("\\n", $errorMsg);
    }
    $msg = '<script>alert("' . addslashes($alertContent) . '");</script>';
}

// ==================== 2. 文件删除 + 数据库强同步 ====================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['file_id'])) {
    $file_id = intval($_GET['file_id']);
    // 1. 查询数据库：验证文件归属 + 获取物理路径
    $sql = "SELECT * FROM files WHERE id = '{$file_id}' AND user_id = '{$user_id}'";
    $result = mysqli_query($conn, $sql);
    $file = mysqli_fetch_assoc($result);

    if ($file) {
        $filePath = $file['file_path'];
        $deleteSuccess = true;

        // 2. 先删除数据库记录，再删除物理文件（防止数据库记录残留）
        $del_sql = "DELETE FROM files WHERE id = '{$file_id}'";
        if (!mysqli_query($conn, $del_sql)) {
            $deleteSuccess = false;
            $msg = "<script>alert('数据库记录删除失败：" . mysqli_error($conn) . "');</script>";
        }

        // 3. 物理文件存在则删除
        if ($deleteSuccess && file_exists($filePath)) {
            if (!unlink($filePath)) {
                $msg = "<script>alert('数据库记录已删除，但物理文件删除失败，请手动删除：{$filePath}');</script>";
            } else {
                $msg = "<script>alert('文件删除成功！');window.location.href='index.php';</script>";
            }
        } elseif ($deleteSuccess) {
            $msg = "<script>alert('文件删除成功！');window.location.href='index.php';</script>";
        }
    } else {
        $msg = "<script>alert('文件不存在或无权限删除！');</script>";
    }
}

// ==================== 3. 文件下载 + 加密验证 ====================
if (isset($_GET['action']) && $_GET['action'] == 'download' && isset($_GET['file_id'])) {
    $file_id = intval($_GET['file_id']);
    $sql = "SELECT * FROM files WHERE id = '{$file_id}' AND user_id = '{$user_id}'";
    $result = mysqli_query($conn, $sql);
    $file = mysqli_fetch_assoc($result);
    
    if (!$file) {
        die("<script>alert('文件不存在或无权限下载！');window.location.href='index.php';</script>");
    }

    if ($file['is_encrypt'] == 1) {
        if (!isset($_POST['check_pwd'])) {
            die('
            <!DOCTYPE html>
            <html lang="zh-CN">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>文件加密验证 - 喵次元网盘</title>
                <link rel="stylesheet" href="static/css/anime.css">
            </head>
            <body>
                <div class="anime-card" style="max-width:300px;">
                    <h3 class="anime-title">🔐 文件加密验证</h3>
                    <form method="post">
                        <input type="password" name="pwd" class="anime-input" placeholder="输入加密密码" required>
                        <input type="hidden" name="file_id" value="'.$file_id.'">
                        <button type="submit" name="check_pwd" class="anime-btn" style="width:100%; margin-top:10px;">验证</button>
                    </form>
                </div>
            </body>
            </html>');
        } else {
            $input_pwd = mysqli_real_escape_string($conn, $_POST['pwd']);
            if ($input_pwd != $file['encrypt_pwd']) {
                die("<script>alert('密码错误！');history.go(-1);</script>");
            }
        }
    }

    // 执行下载
    if (file_exists($file['file_path'])) {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=" . basename($file['file_name']));
        header("Content-Length: " . filesize($file['file_path']));
        readfile($file['file_path']);
        exit;
    } else {
        die("<script>alert('文件不存在！');window.location.href='index.php';</script>");
    }
}

// ==================== 4. 生成文件分享二维码 ====================
function generateQrcode($fileId) {
    $shareUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?action=download&file_id=" . $fileId;
    echo '<div id="qrcode_'.$fileId.'" style="display:inline-block;"></div>
    <script>
        QRCode.toCanvas(document.getElementById("qrcode_'.$fileId.'"), "'.$shareUrl.'", {width: 80}, function (error) {
            if (error) console.error(error)
        })
    </script>';
}

// 获取用户文件列表：增加 create_time 字段
$fileList = mysqli_query($conn, "SELECT * FROM files WHERE user_id='{$user_id}' ORDER BY create_time DESC");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$config['title']?> - <?=$username?></title>
    <link rel="stylesheet" href="https://cdn.staticfile.org/layui/2.5.6/css/layui.min.css">
    <link rel="stylesheet" href="static/css/anime.css">
    <script src="static/js/qrcode.js"></script>
    <style>
        /* 操作按钮统一样式 */
        .op-btn {
            display: inline-block;
            padding: 4px 8px;
            font-size: 12px;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 5px;
        }
        .op-btn.download {
            background-color: #5cb85c;
            color: #fff;
        }
        .op-btn.delete {
            background-color: #ff4444;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="anime-card" style="max-width: 1000px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2 class="anime-title">🐾 喵次元网盘</h2>
            <div>
                <span>欢迎，<?=$username?></span>
                <a href="logout.php" style="color:#ff4444; margin-left:10px;">退出</a>
            </div>
        </div>

        <!-- 批量上传 + 加密选项 -->
        <form method="post" enctype="multipart/form-data" id="upload-form">
            <input type="file" name="files[]" multiple 
                   accept=".jpg,.png,.gif,.pdf,.doc,.docx,.txt,.zip,.rar"
                   class="anime-input" required><br>
            <label style="margin-top:5px; display:flex; align-items:center;">
                <input type="checkbox" name="encrypt" id="encrypt"> 加密文件
                <input type="password" name="encrypt_pwd" class="anime-input" style="width:60%; margin-left:10px; display:none;" placeholder="设置加密密码">
            </label>
            <button type="submit" class="anime-btn" style="width:100%; margin-top:10px;">📤 批量上传</button>
        </form>

        <!-- 上传进度显示 -->
        <div id="upload-progress" style="margin:10px 0;"></div>

        <hr style="border:1px dashed #ffb6c1;">

        <!-- 文件列表 + 二维码分享 + 上传时间显示 -->
        <h3 class="anime-title" style="font-size:18px;"> 喵次元网盘 📂 我的文件</h3>
        <?php if (mysqli_num_rows($fileList) == 0): ?>
            <p style="text-align:center; color:#999;">暂无上传文件，点击上方按钮开始上传吧~</p>
        <?php else: ?>
            <table class="layui-table" style="border-radius:10px;">
                <thead>
                    <tr style="background: rgba(255, 182, 193, 0.2);">
                        <th width="30%">文件名</th>
                        <th width="10%">大小</th>
                        <th width="15%">上传时间</th>
                        <th width="10%">状态</th>
                        <th width="15%">操作</th>
                        <th width="20%">分享</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($file = mysqli_fetch_assoc($fileList)): ?>
                    <tr>
                        <td title="<?=$file['file_name']?>"><?=mb_substr($file['file_name'], 0, 20)?><?=mb_strlen($file['file_name'])>20?'...':''?></td>
                        <td><?=$file['file_size']?></td>
                        <td><?=$file['create_time']?></td>
                        <td><?=$file['is_encrypt'] ? '<span style="color:#ff69b4;">加密</span>' : '普通'?></td>
                        <td>
                            <!-- 统一使用自定义按钮样式 -->
                            <a href="?action=download&file_id=<?=$file['id']?>" class="op-btn download">下载</a>
                            <a href="?action=delete&file_id=<?=$file['id']?>" class="op-btn delete" onclick="return confirm('确定要删除这个文件吗？删除后无法恢复！')">删除</a>
                        </td>
                        <td>
                            <?php generateQrcode($file['id']); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div style="text-align:center; margin-top:20px; color:#999;">
            <?=$config['foot']?>
            <a href="admin/" style="color:#ff69b4;">🔧 后台管理</a>
        </div>
    </div>

    <script src="static/js/upload.js"></script>
    <script>
        // 加密选项显示/隐藏
        document.getElementById('encrypt').addEventListener('change', function() {
            const pwdInput = this.nextElementSibling;
            pwdInput.style.display = this.checked ? 'inline-block' : 'none';
            pwdInput.required = this.checked;
        });

        // 上传进度监听
        const form = document.getElementById('upload-form');
        form.addEventListener('submit', function(e) {
            const files = document.querySelector('input[name="files[]"]').files;
            if (files.length === 0) return;
            
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.action);
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const progress = Math.round((e.loaded / e.total) * 100);
                    uploadProgress(files[0], progress);
                }
            });
            
            xhr.send(formData);
            e.preventDefault();
        });
    </script>
    <?php echo $msg ?? ''; ?>
</body>
</html>
