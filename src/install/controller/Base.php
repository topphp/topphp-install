<?php
/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author bai <sleep@kaituocn.com>
 */

/**
 * Description - Base.php
 *
 * 业务基类控制器
 */
declare(strict_types=1);

namespace app\install\controller;

use app\BaseController;
use app\install\enum\InstallEnum;
use think\facade\View;
use Topphp\TopphpInstall\TopInstallServer;

abstract class Base extends BaseController
{
    /**
     * 定义业务中间件
     * @var array
     */
    protected $middleware = ['Check'];

    // Install应用基础业务逻辑（所有方法需定义protected关键词）

    /**
     * 项目根目录
     * @var string
     */
    protected $rootPath;

    /**
     * 应用目录
     * @var string
     */
    protected $appPath;

    /**
     * 配置目录
     * @var string
     */
    protected $configPath;

    /**
     * 数据目录
     * @var string
     */
    protected $installDataPath;

    /**
     * 官方网址
     * @var string
     */
    protected $officialWebsite;

    /**
     * 当前主域名
     * @var string
     */
    protected $domain;

    /**
     * 项目名称
     * @var string
     */
    protected $appProject;

    /**
     * Top安装服务
     * @var TopInstallServer
     */
    protected $topInstallServer;

    /**
     * 安装成功后允许访问安装应用的哪个页面，默认step4
     * @var string
     */
    protected $allowVisit = "install/step4";

    /**
     * 基础页面构建
     * @author bai
     */
    protected function basePage()
    {
        // title
        $titleConfig = config("topphpInstall.title_content");
        $title       = empty($titleConfig) ? "安装引导" : $titleConfig;
        // version
        $versionConfig = config("topphpInstall.version");
        $version       = empty($versionConfig) ? InstallEnum::TOPPHP_VERSION : $versionConfig;
        // Copyright
        $sysCopyright  = config("topphpInstall.copyright");
        $copyrightYear = (int)config("topphpInstall.copyright_step_year") + date("Y");
        $copyright     = empty($sysCopyright) ? "2019-" . $copyrightYear : $sysCopyright;
        // 模板赋值
        View::assign('title', $title);
        View::assign('version', $version);
        View::assign('copyright', $copyright);
        View::assign('domain', $this->officialWebsite);
        View::assign('project', $this->appProject);
    }

    /**
     * 环境检测
     * @return array
     * @author bai
     */
    protected function getEnvData()
    {
        $env = [
            "操作系统"  => [
                "low"       => "无限制",
                "recommend" => "Linux",
                "now"       => TopInstallServer::$ok . PHP_OS
            ],
            "服务环境"  => [
                "low"       => "Apache2.0+/Nginx1.6+/Swoole4.4.0",
                "recommend" => "Swoole4.4.0+",
                "now"       => $this->topInstallServer->checkServer()
            ],
            "PHP版本" => [
                "low"       => ">= 7.2",
                "recommend" => "7.2+",
                "now"       => $this->topInstallServer->checkPhp()
            ],
            "附件上传"  => [
                "low"       => "无限制",
                "recommend" => ">= 2M",
                "now"       => $this->topInstallServer->checkUpload()
            ],
            "剩余空间"  => [
                "low"       => "80M",
                "recommend" => ">= 200M",
                "now"       => $this->topInstallServer->checkDisk()
            ],
        ];
        return $env;
    }

    /**
     * 文件检测
     * @return array
     * @author bai
     */
    protected function getFileData()
    {
        // 检验目录配置
        $dirOrFileData = $this->topInstallServer->checkDirConfig();
        // 获取检验结果
        $dirFileAuth = $this->topInstallServer->checkDir($dirOrFileData);
        return $dirFileAuth;
    }

    /**
     * 函数/扩展检测
     * @return array
     * @author bai
     */
    protected function getFunOrExtData()
    {
        // 不影响安装，可忽略的配置项 val = ( recommend 建议 optional 可选 ignore 可忽略 )
        $allowIgnore = [
            'Swoole' => 'optional',
            'Redis'  => 'recommend',
        ];
        // 检验扩展配置
        $extData = $this->topInstallServer->checkExtConfig();
        // 获取检验结果
        $extAuth = $this->topInstallServer->checkFunOrExt($extData, $allowIgnore);
        return $extAuth;
    }
}
