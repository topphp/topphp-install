<?php
/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author bai <sleep@kaituocn.com>
 */

return [
    // 是否允许安装成功后访问install安装模块（为true，如果已安装成功，访问install应用将每次都会访问安装成功的页面-step4）
    "allow_visit"         => false,
    // 固定版权期限（为空，默认跟随系统日期递增）
    "copyright"           => "",
    // 版权期限递增年限（为0，默认跟随系统年限递增，copyright为空时有效）
    "copyright_step_year" => 0,
    // 官网域名（用于底部版权链接跳转）
    "official_website"    => "https://topphp.io",
    // 项目名称
    "project"             => "TopPHP",
    // 页面title内容
    "title_content"       => "系统安装-TopPHP",
    // 软件应用版本
    "version"             => "1.0.0 beta",
    // 是否显示创建env文件的按钮
    "show_env_btn"        => true,
    // 环境检测配置
    "env_config"          => [
        "checkServer" => [
            "apache" => 2,// float Apache最低版本
            "nginx"  => 1.6,// float Nginx最低版本
            "swoole" => 4.4// float Swoole最低版本
        ],
        "checkPhp"    => 7.2,// float PHP最低版本
        "checkDisk"   => 80// int 最低硬盘空间大小，单位M（兆）
    ],
    // 目录或文件检测配置
    "dir_config"          => [
        // 目录or文件--路径(相对根目录)--显示名称
        ['dir', 'app', 'app'],
        ['dir', 'public/static', 'static'],
        ['dir', 'runtime', 'runtime'],
        ['file', 'app/install/data/env.tmpl', '.env'],
        ['file', 'config/database.php', 'config/database.php'],
        ['file', 'config/cache.php', 'config/cache.php'],
    ],
    // 函数&依赖扩展检测配置
    "ext_config"          => [
        // name--类型--状态名称
        ['PDO', '扩展', '已开启'],
        ['CURL', '扩展', '已开启'],
        ['Swoole', '扩展', '已开启'],
        ['Redis', '扩展', '已开启'],
        ['fileinfo', '扩展', '已开启'],
        ['mbstring', '扩展', '已开启'],
        ['openssl', '扩展', '已开启'],
        ['pdo_mysql', '扩展', '已开启'],
        ['bcmath', '模块', '支持'],
        ['file_put_contents', '函数', '支持'],
        ['file_get_contents', '函数', '支持'],
        ['xml', '函数', '支持'],
        ['shell_exec', '函数', '支持'],
    ],
];