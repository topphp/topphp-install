<?php
/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author bai <sleep@kaituocn.com>
 */

// 定义topphp部署路径变量与基本function
$topphpDS             = DIRECTORY_SEPARATOR;
$topphpInstallLogPath = __DIR__ . $topphpDS . "data" . $topphpDS;
$topphpRootPath       = dirname(dirname(dirname(dirname(__DIR__)))) . $topphpDS;

/**
 * 删除目录及目录下的所有文件
 * @param string $dir
 * @author bai
 */
function topphpDelDir(string $dir)
{
    $array = scandir($dir);
    foreach ($array as $val) {
        if ($val != '.' && $val != '..') {
            $file = $dir . '/' . $val;
            if (is_dir($file)) {
                topphpDelDir($file);
            } else {
                @unlink($file);
            }
        }
    }
    @rmdir($dir);
}

/**
 * 拷贝目录及目录下的所有文件
 * @param string $dir1 源目录
 * @param string $dir2 目标目录
 * @author bai
 */
function topphpCopyDir(string $dir1, string $dir2)
{
    if (!file_exists($dir2)) {
        @mkdir($dir2, 0755, true);
    }
    $array = scandir($dir1);
    foreach ($array as $val) {
        if ($val != '.' && $val != '..') {
            $sFile = $dir1 . '/' . $val;
            $tFile = $dir2 . '/' . $val;
            if (is_dir($sFile)) {
                topphpCopyDir($sFile, $tFile);
            } else {
                @copy($sFile, $tFile);
            }
        }
    }
}

// 验证是否首次安装
if (!file_exists($topphpInstallLogPath . "topphp-installed.lock")
    && function_exists("file_put_contents") && function_exists("file_get_contents")) {

    // 定义topphp部署路径变量
    $topphpInstallPath    = __DIR__ . $topphpDS . "install" . $topphpDS;
    $topphpInstallPubPath = __DIR__ . $topphpDS . "install-public" . $topphpDS;

    $topphpAppPath            = $topphpRootPath . "app" . $topphpDS;
    $topphpAppInstallDataPath = $topphpAppPath . "install" . $topphpDS . "data" . $topphpDS;
    $topphpStaticPath         = $topphpRootPath . "public" . $topphpDS . "static" . $topphpDS . "install";

    $topphpInstallVersion  = include "version.php";
    $topphpCopyrightData   = "/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author TopPHP <sleep@kaituocn.com>
 */";
    $topphpInstallLockData = $topphpCopyrightData . PHP_EOL . PHP_EOL . "Composer 安装时间：" . date("Y-m-d H:i:s");
    $topphpInstallLockData .= PHP_EOL . "安装组件版本：" . $topphpInstallVersion['name'] . " v" . $topphpInstallVersion['version'];

    // 判断初始化锁
    if (!file_exists($topphpAppInstallDataPath . "init.lock")) {
        // 首次安装topphp-install，部署相关文件
        $topphpInitLockData = $topphpCopyrightData . PHP_EOL . PHP_EOL;
        $topphpInitLockData .= "如需更新或重新安装topphp-install组件，请删除此文件，同时删除vendor/topphp/topphp-install/src/data/topphp-installed.lock全局锁" . PHP_EOL;
        $topphpInitLockData .= "特别提醒：安装/更新topphp-install组件，会覆盖app/install应用以及对应的静态文件，如您已存在此应用或已对topphp-install组件应用进行过二次开发，请提前做好备份" . PHP_EOL;
        $topphpInitLockData .= PHP_EOL . "初始化时间：" . date("Y-m-d H:i:s") . PHP_EOL;
        $topphpInitLockData .= "组件版本：" . $topphpInstallVersion['name'] . " v" . $topphpInstallVersion['version'];
        // 开始部署目录
        if (is_dir($topphpAppPath . "install")) {
            topphpDelDir($topphpAppPath . "install");
        }
        if (is_dir($topphpStaticPath)) {
            topphpDelDir($topphpStaticPath);
        }
        topphpCopyDir(__DIR__ . $topphpDS . "install", $topphpAppPath . "install");
        topphpCopyDir(__DIR__ . $topphpDS . "install-public" . $topphpDS . "install", $topphpStaticPath);
        // 写入安装锁
        @file_put_contents($topphpAppInstallDataPath . "init.lock", $topphpInitLockData);
        @file_put_contents($topphpInstallLogPath . "topphp-installed.lock", $topphpInstallLockData);
        // 写入安装日志
        @file_put_contents($topphpInstallLogPath . "topphp-install.log",
            "[ " . date("Y-m-d H:i:s") . " ] " . "topphp-install 初始化安装部署成功！" . PHP_EOL, FILE_APPEND);
    } else {
        // 非首次安装（可能原因：开发者二开后部署上线，直接写入安装锁）
        @file_put_contents($topphpInstallLogPath . "topphp-installed.lock", $topphpInstallLockData);
        // 写入安装日志
        @file_put_contents($topphpInstallLogPath . "topphp-install.log",
            "[ " . date("Y-m-d H:i:s") . " ] " . "topphp-install Composer安装完成！" . PHP_EOL, FILE_APPEND);
    }

    //清空安装系统已生成的变量，防止开发者错用
    $topphpGlobalArray = [
        'topphpInstallPath',
        'topphpInstallPubPath',
        'topphpAppPath',
        'topphpAppInstallDataPath',
        'topphpStaticPath',
        'topphpCopyrightData',
        'topphpInstallVersion',
        'topphpInstallLockData',
        'topphpInitLockData',
    ];

    $topphpUnset = get_defined_vars();
    foreach ($topphpUnset as $unsetKey => $unsetVal) {
        if (in_array($unsetKey, $topphpGlobalArray)) {
            unset($$unsetKey);
        }
    }
    unset($unsetKey);
    unset($unsetVal);
    unset($topphpUnset);
    unset($topphpGlobalArray);
}

// 判断是否执行安装程序
$topphpLockContent = @file_get_contents($topphpRootPath . "install.lock");
if (!file_exists($topphpRootPath . "install.lock") || !preg_match("/topphp/", strtolower($topphpLockContent))) {
    $topphpAllowUri = [
        "/install",
        "/index.php/install",
        "/install/index",
        "/index.php/install/index",
        "/install/step2",
        "/index.php/install/step2",
        "/install/step3",
        "/index.php/install/step3",
        "/install/step4",
        "/index.php/install/step4",
        "/index.php?s=/install",
        "/index.php?s=/install/index",
        "/index.php?s=/install/step2",
        "/index.php?s=/install/step3",
        "/index.php?s=/install/step4",
    ];
    if (isset($_SERVER['REQUEST_URI'])) {
        $topphpRouteConfig = include $topphpRootPath . "config" . $topphpDS . "route.php";
        $topphpUrlSuffix   = $topphpRouteConfig['url_html_suffix'];
        if (!empty($topphpUrlSuffix)) {
            if (preg_match("/\|/", $topphpUrlSuffix)) {
                $topphpUri = @explode(".", $_SERVER['REQUEST_URI'])[0];
            } else {
                $topphpUri = @explode("." . $topphpUrlSuffix, $_SERVER['REQUEST_URI'])[0];
            }
        } else {
            $topphpUri = $_SERVER['REQUEST_URI'];
        }
        if (file_exists($topphpInstallLogPath . "topphp-installing.lock")) {
            $topphpInstallingData = @file_get_contents($topphpInstallLogPath . "topphp-installing.lock");
            $topphpInstallingData = @explode("_", $topphpInstallingData);
        }
        if (!in_array($topphpUri, $topphpAllowUri)) {
            if (!empty($topphpInstallingData[0]) && preg_match("/" . $topphpInstallingData[0] . "/",
                    strtolower($topphpUri)) && (int)$topphpInstallingData[1] + 3600 >= time()) {
                // install 应用调用请求忽略重定向跳转
            } else {
                if (isset($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']),
                        "mozilla") !== false) {
                    // 浏览器请求
                    echo "<SCRIPT LANGUAGE='javascript'>";
                    echo "location.href='/install'";
                    echo "</SCRIPT>";
                } else {
                    // 其他请求
                    if (!file_exists($topphpInstallLogPath . "topphp-redirect.log")) {
                        @file_put_contents($topphpInstallLogPath . "topphp-redirect.log", "/install");
                    }
                }
            }
        }
    }
    unset($topphpAllowUri);
    unset($topphpRouteConfig);
    unset($topphpUrlSuffix);
    unset($topphpInstallingData);
    unset($topphpUri);
} elseif (file_exists($topphpRootPath . "install.lock")) {
    // 安装成功，清理安装中状态
    @unlink($topphpInstallLogPath . "topphp-installing.lock");
    @unlink($topphpInstallLogPath . "topphp-redirect.log");
}

unset($topphpDS);
unset($topphpInstallLogPath);
unset($topphpRootPath);
unset($topphpLockContent);



