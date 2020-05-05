<?php
/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author bai <sleep@kaituocn.com>
 */
// install中间件配置
return [
    // 别名或分组
    'alias'    => [
        "Check" => app\middleware\Check::class,
    ],
    // 优先级设置，此数组中的中间件会按照数组中的顺序优先执行
    'priority' => [
        app\middleware\Check::class,
    ],
];
