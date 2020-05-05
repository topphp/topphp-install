<?php
/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author bai <sleep@kaituocn.com>
 */

/**
 * Description - InstallEnum.php
 *
 * 一键安装组件配置枚举
 */
declare(strict_types=1);

namespace app\install\enum;

class InstallEnum
{
    const TOPPHP_DOMAIN = "https://topphp.io";
    const TOPPHP_NAME = "TopPHP";
    const TOPPHP_VERSION = "1.0.0";
    const TOPPHP_WECHAT_NAME = "凯拓软件";

    // 协议内容配置（仅协议模板app/install/data/ProtocolContentTmpl.html内容为空时有效）
    const TOPPHP_PROTOCOL_CONFIG = [
        "company"            => "天津凯拓未来科技有限公司",
        "company_short"      => "凯拓软件",
        "project_name"       => "TopPHP敏捷开发骨架",
        "project_name_short" => "TopPHP",
        "brief"              => "TopPHP基于 PHP + MySQL + SWOOLE 的技术，采用ThinkPHP 6.0框架开发，遵循Apache Lisense 2.0开源协议发布，并提供免费使用。",
        "company_website"    => "www.kaituocn.com",
        "project_website"    => "topphp.io",
    ];
}
