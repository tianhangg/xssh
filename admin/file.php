<?php
error_reporting(0);
session_start();
include '../common/config.php';
include '../common/key.php';
include '../common/db.php';

// 权限验证
if (!isset($_SESSION['nekoadmin']) || $config['admin'] == '0') {
    die("<script>alert('权限不足！');window.location.href='../';</script>");
}

$rootFolder = realpath("../upload");

// 删除文件/文件夹
if (isset($_GET['delete'])) {
    $targetPath = $_GET['delete'];
    if (isAllowedPath($targetPath, $rootFolder)) {
        if (is_file($targetPath)) {
            unlink($targetPath);
            // 删除数据库记录
            mysqli_query($conn, "DELETE FROM files WHERE file_path='$targetPath'");
            $msg = "<script>layui.layer.msg('文件删除成功！', {icon: 1});</script>";
        } elseif (is_dir($targetPath)) {
            deleteDirectory($targetPath);
            mysqli_query($conn, "DELETE FROM files WHERE file_path LIKE '$targetPath/%'");
            $msg = "<script>layui.layer.msg('文件夹删除成功！', {icon: 1});</script>";
        }
    } else {
        $msg = "<script>layui.layer.msg('非法路径！', {icon: 2});</script>";
    }
}

// 验证路径是否合法
function isAllowedPath($path, $root) {
    $realPath = realpath($path);
    return $realPath !== false && str_starts_with($realPath, $root);
}

// 递归删除文件夹
function deleteDirectory($dir) {
    if (!is_dir($dir)) return false;
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    return rmdir($dir);
}

// 获取所有文件
function scanFiles($dir) {
    $files = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        $path = realpath($path);
        if (is_dir($path)) {
            $files = array_merge($files, scanFiles($path));
        } else {
            $files[] = $path;
        }
    }
    return $files;
}

$files = $rootFolder ? scanFiles($rootFolder) : [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件管理 - 喵次元网盘后台</title>
    <link rel="stylesheet" href="https://cdn.staticfile.org/layui/2.5.6/css/layui.min.css">
    <link rel="stylesheet" href="../static/css/anime.css">
    <style>
        .file-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }
        .file-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }
        .file-name {
            flex: 1;
        }
        .file-size {
            color: #666;
            margin-right: 20px;
        }
    </style>
</head>
<body>
    <div class="anime-card" style="max-width: 1000px;">
        <h2 class="anime-title">📁 文件管理</h2>
        <div class="layui-btn-group" style="margin: 10px 0;">
            <button class="anime-btn" onclick="location.reload()">刷新列表</button>
            <a href="index.php" class="anime-btn" style="background: #ff8c00;">返回后台首页</a>
        </div>

        <?php if (empty($files)): ?>
            <div class="layui-elem-quote">当前目录暂无文件</div>
        <?php else: ?>
            <div class="file-list">
                <?php foreach ($files as $file): ?>
                    <?php
                    $isDir = is_dir($file);
                    $fileSize = $isDir ? '文件夹' : round(filesize($file)/1024, 2) . ' KB';
                    $relPath = str_replace($rootFolder, '', $file);
                    ?>
                    <div class="file-item">
                        <img class="file-icon" src="https://cdn.staticfile.org/font-awesome/4.7.0/img/<?=$isDir ? 'folder.png' : 'file.png'?>" alt="icon">
                        <div class="file-name"><?=$relPath?></div>
                        <div class="file-size"><?=$fileSize?></div>
                        <div class="file-opt">
                            <a href="<?=$file?>" class="anime-btn" style="padding:4px 12px;" target="_blank">访问</a>
                            <a href="?delete=<?=$file?>" class="anime-btn" style="padding:4px 12px; background:#ff4444;" onclick="return confirm('确定删除？')">删除</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.staticfile.org/layui/2.5.6/layui.min.js"></script>
    <script>
        layui.use('layer', function(){
            var layer = layui.layer;
        });
    </script>
    <?=$msg??''?>
</body>
</html>