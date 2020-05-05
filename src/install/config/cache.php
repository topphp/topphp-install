<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------
return [
    // 默认缓存驱动
    'default' => env('cache.install_driver', 'file'),

    // 缓存连接方式配置
    'stores'  => [
        // 文件缓存
        'file'  => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => \think\facade\App::getRootPath() . 'runtime' . DS . 'install' . DS . 'cache' . DS,
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // redis缓存
        'redis' => [
            // 驱动方式
            'type'       => 'Redis',
            // 服务器地址
            'host'       => env('cache.install_host', '127.0.0.1'),
            // 端口
            'port'       => env('cache.install_port', '6379'),
            // 密码
            'password'   => env('cache.install_password', ''),
            // 默认db库
            'select'     => env('cache.install_select', 0),
            // 缓存前缀
            'prefix'     => 'install:',
            // 缓存标签前缀
            'tag_prefix' => 'install:tag:',
        ],
        // 更多的缓存连接
    ],
];
