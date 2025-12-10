<?php
error_reporting(0);
session_start();
include '../common/config.php';
include '../common/key.php';

// 权限验证
if (!isset($_SESSION['nekoadmin']) || $config['admin'] == '0') {
    die("<script>alert('权限不足！');window.location.href='../';</script>");
}

$allowedFields = [
    'title', 'describition', 'keywords', 'user', 'logintype', 'url',
    'APPID', 'APPKEY', 'QQ', 'wx', 'zfb', 'ting', 'host', 'port',
    'secure', 'youremail', 'yourpass', 'background', 'foot'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $inputKey = trim($_POST['key'] ?? '');
    if ($inputKey !== $key) {
        $msg = "<script>alert('密匙错误！');</script>";
    } else {
        $configData = [];
        foreach ($allowedFields as $field) {
            $value = isset($_POST[$field]) ? addslashes(trim($_POST[$field])) : '';
            $configData[$field] = $value;
        }

        $_data = "<?php\n\$config= [\n";
        foreach ($configData as $k => $v) {
            $_data .= "    '$k' => '$v', // " . getFieldDesc($k) . "\n";
        }
        $_data .= "    'admin' => '{$config['admin']}', // 后台开关\n";
        $_data .= "    'maxsize' => '{$config['maxsize']}', // 最大上传大小\n";
        $_data .= "    'bq' => 'QlnvvJrkupHnjKsmUVHvvJozNTIyOTM0ODI4' // 代码\n";
        $_data .= "];\n/*\n喵次元网盘\nBY：随风\n2025.12.8\n*/\n?>";

        $configPath = '../common/config.php';
        if (file_put_contents($configPath, $_data)) {
            $msg = "<script>alert('配置修改成功！');window.location.href='set.php';</script>";
        } else {
            $msg = "<script>alert('修改失败！请检查文件权限');</script>";
        }
    }
}

function getFieldDesc($field) {
    $desc = [
        'title' => '网站名称',
        'describition' => '网站描述',
        'keywords' => '关键词',
        'user' => '是否要登录',
        'logintype' => '登录方式',
        'url' => '接口地址',
        'APPID' => 'APPID',
        'APPKEY' => 'APPKEY',
        'QQ' => 'QQ',
        'wx' => 'wx',
        'zfb' => 'zfb',
        'ting' => 'ting',
        'host' => 'host',
        'port' => 'port',
        'secure' => 'secure',
        'youremail' => 'youremail',
        'yourpass' => 'yourpass',
        'background' => '背景',
        'foot' => '底部'
    ];
    return $desc[$field] ?? $field;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站配置 - 喵次元网盘后台</title>
    <link rel="stylesheet" href="https://cdn.staticfile.org/layui/2.5.6/css/layui.min.css">
    <link rel="stylesheet" href="../static/css/anime.css">
</head>
<body>
    <div class="anime-card" style="max-width: 800px;">
        <h2 class="anime-title">⚙️ 网站基本配置</h2>
        <form method="post" class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color: red;">*</span> 管理密匙</label>
                <div class="layui-input-block">
                    <input type="password" name="key" class="anime-input" required placeholder="输入管理密匙">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color: red;">*</span> 网站标题</label>
                <div class="layui-input-block">
                    <input type="text" name="title" class="anime-input" value="<?=htmlspecialchars($config['title'])?>" required>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color: red;">*</span> 网站描述</label>
                <div class="layui-input-block">
                    <input type="text" name="describition" class="anime-input" value="<?=htmlspecialchars($config['describition'])?>" required>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color: red;">*</span> 网站关键词</label>
                <div class="layui-input-block">
                    <input type="text" name="keywords" class="anime-input" value="<?=htmlspecialchars($config['keywords'])?>" required>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">用户登录</label>
                <div class="layui-input-block">
                    <input type="text" name="user" class="anime-input" value="<?=htmlspecialchars($config['user'])?>" placeholder="1开启，0关闭">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">登录方式</label>
                <div class="layui-input-block">
                    <input type="text" name="logintype" class="anime-input" value="<?=htmlspecialchars($config['logintype'])?>" placeholder="1快捷，2邮箱，3两者都有">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">网站背景</label>
                <div class="layui-input-block">
                    <input type="text" name="background" class="anime-input" value="<?=htmlspecialchars($config['background'])?>">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">底部信息</label>
                <div class="layui-input-block">
                    <input type="text" name="foot" class="anime-input" value="<?=htmlspecialchars($config['foot'])?>">
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button type="submit" class="anime-btn">保存配置</button>
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
    <?=$msg??''?>
</body>
</html>