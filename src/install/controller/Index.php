<?php
/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author bai <sleep@kaituocn.com>
 */

/**
 * Description - Index.php
 *
 * 一键安装组件
 */
declare(strict_types=1);

namespace app\install\controller;

use app\common\enumerate\HttpStatusEnum;
use app\install\enum\InstallEnum;
use app\middleware\Check;
use Exception;
use lib\SendMsg;
use Psr\SimpleCache\InvalidArgumentException;
use think\annotation\Route;
use think\annotation\route\Middleware;
use think\facade\App;
use think\facade\View;
use think\Response;
use Topphp\TopphpInstall\TopInstallServer;

/**
 * Class Index
 * @package app\install\controller
 */
class Index extends Base
{
    /**
     * 构造
     * @author bai
     */
    protected function initialize()
    {
        $this->rootPath         = App::getRootPath();
        $this->appPath          = App::getAppPath();
        $this->configPath       = App::getConfigPath();
        $this->installDataPath  = $this->appPath . "data" . DS;
        $officialWebsite        = config("topphpInstall.official_website");
        $appProject             = config("topphpInstall.project");
        $this->officialWebsite  = empty($officialWebsite) ? InstallEnum::TOPPHP_DOMAIN : $officialWebsite;
        $this->appProject       = empty($appProject) ? InstallEnum::TOPPHP_NAME : $appProject;
        $this->domain           = $this->request->domain(true);
        $this->topInstallServer = TopInstallServer::instance();
        // 设置安装env文件名（需要安装env的场景可配置，还需要在topphpInstall.php文件将show_env_btn视图显示env配置的按钮配置设为true）
        $this->topInstallServer->setEnvName(".env");
        // 设置安装sql文件名
        $this->topInstallServer->setSqlName("topphp_base.sql");
        // 设置安装sql文件中的表前缀
        $this->topInstallServer->setFromSqlDefPrefix("topphp_");
    }

    /**
     * Step1
     * @return mixed
     * @throws InvalidArgumentException
     * @throws Exception
     * @author bai
     * @Route("index")
     * @Middleware({Check::class})
     */
    public function index()
    {
        // 基础函数验证
        if (!function_exists("file_get_contents")) {
            return response('Warning: Please open the file_get_contents function first!', 500);
        }
        // 校验是否已安装
        $lockPath = $this->rootPath . "install.lock";
        if (file_exists($lockPath)) {
            if (config("topphpInstall.allow_visit") === true) {
                return redirect($this->domain . "/" . $this->allowVisit, HttpStatusEnum::REDIRECT);
            }
            return redirect($this->domain, HttpStatusEnum::REDIRECT);
        }
        // 清除全部安装缓存
        //$this->topInstallServer->rmClearCached(true);// 默认静默删除env文件
        $this->topInstallServer->rmClearCached();// 非静默删除，如果存在，step2会有提示
        // 清除安装中的状态（必须）
        $this->topInstallServer->clearInstalling();
        // 基础页面构建
        $this->basePage();
        // 协议内容替换配置（如果协议模板app/install/data/ProtocolContentTmpl.html内容存在，将直接引用模板协议内容）
        $protocolTmpl   = @file_get_contents($this->installDataPath . "ProtocolContentTmpl.html");
        $protocolConfig = [];
        if (empty($protocolTmpl)) {
            $protocolConfig = InstallEnum::TOPPHP_PROTOCOL_CONFIG;
        }
        // 模板赋值
        View::assign('protocolTmpl', $protocolTmpl);
        View::assign('protocolConfig', $protocolConfig);
        // 缓存Step1
        $this->topInstallServer->setCache('install-topphp-step1', "I accept all terms of the software agreement");
        return View::fetch("index/step1");
    }

    /**
     * Step2
     * @return mixed
     * @throws InvalidArgumentException
     * @throws Exception
     * @author bai
     * @Route("step2")
     * @Middleware({Check::class})
     */
    public function step2()
    {
        // 基础函数验证
        if (!function_exists("file_get_contents")) {
            return response('Warning: Please open the file_get_contents function first!', 500);
        }
        // 校验是否已安装
        $lockPath = $this->rootPath . "install.lock";
        if (file_exists($lockPath)) {
            if (config("topphpInstall.allow_visit") === true) {
                return redirect($this->domain . "/" . $this->allowVisit, HttpStatusEnum::REDIRECT);
            }
            return redirect($this->domain, HttpStatusEnum::REDIRECT);
        }
        // 基础页面构建
        $this->basePage();
        // 验证是否接受服务条款Step1是否通过
        if (!$this->topInstallServer->getCache('install-topphp-step1')) {
            return redirect("/install");
        }
        // env文件校验提示
        if (file_exists($this->rootPath . ".env") && !$this->topInstallServer->getCache('install-topphp-envtips')) {
            View::assign('envExists', 1);
        } else {
            View::assign('envExists', 0);
        }
        // 获取页面信息
        if (!$this->topInstallServer->getCache('install-topphp-step2')) {
            $env           = $this->getEnvData();
            $dirFileAuth   = $this->getFileData();
            $functionOrExt = $this->getFunOrExtData();
            // 缓存检验结果，防止跳步
            $cacheRes = [
                'env'   => $env,
                'file'  => $dirFileAuth,
                'ext'   => $functionOrExt,
                'state' => $this->topInstallServer->getState(),
            ];
            $this->topInstallServer->setCache('install-topphp-step2', $cacheRes);
        } else {
            $cacheRes      = $this->topInstallServer->getCache('install-topphp-step2');
            $env           = $cacheRes['env'];
            $dirFileAuth   = $cacheRes['file'];
            $functionOrExt = $cacheRes['ext'];
        }
        // 检测是否允许进入下一步
        if ($this->topInstallServer->getState()) {
            $allowInstall = 1;
        } else {
            $allowInstall = 0;
        }
        // 模板赋值
        View::assign('env', $env);
        View::assign('file', $dirFileAuth);
        View::assign('ext', $functionOrExt);
        View::assign('allowInstall', $allowInstall);
        // 设置安装中的状态（必须）
        $this->topInstallServer->setInstalling();
        return View::fetch("index/step2");
    }

    /**
     * Step3
     * @return mixed
     * @throws InvalidArgumentException
     * @throws Exception
     * @author bai
     * @Route("step3")
     * @Middleware({Check::class})
     */
    public function step3()
    {
        // 基础函数验证
        if (!function_exists("file_get_contents")) {
            return response('Warning: Please open the file_get_contents function first!', 500);
        }
        // 校验是否已安装
        $lockPath = $this->rootPath . "install.lock";
        if (file_exists($lockPath)) {
            if (config("topphpInstall.allow_visit") === true) {
                return redirect($this->domain . "/" . $this->allowVisit, HttpStatusEnum::REDIRECT);
            }
            return redirect($this->domain, HttpStatusEnum::REDIRECT);
        }
        // 基础页面构建
        $this->basePage();
        // 验证是否接受服务条款Step1是否通过
        if (!$this->topInstallServer->getCache('install-topphp-step1')) {
            return redirect("/install");
        }
        // 验证环境检测Step2是否通过
        if (!$this->topInstallServer->getCache('install-topphp-step2')) {
            return redirect("/install");
        }

        // 模板赋值
        View::assign('redirect', $this->domain);
        if (config("topphpInstall.show_env_btn")) {
            View::assign('isDebug', 1);
        } else {
            View::assign('isDebug', 0);
        }
        // 设置安装中的状态（必须）
        $this->topInstallServer->setInstalling();
        return View::fetch("index/step3");
    }

    /**
     * Step4
     * @return mixed
     * @throws Exception
     * @throws InvalidArgumentException
     * @author bai
     * @Route("step4")
     * @Middleware({Check::class})
     */
    public function step4()
    {
        // 基础函数验证
        if (!function_exists("file_get_contents")) {
            return response('Warning: Please open the file_get_contents function first!', 500);
        }
        // 校验是否已安装
        if (!empty($this->topInstallServer->getCache("install-topphp-step3")['install_success'])) {
            $installSuccess = true;
        } else {
            $installSuccess = false;
        }
        $lockPath = $this->rootPath . "install.lock";
        if (file_exists($lockPath) && $installSuccess) {
        } elseif (file_exists($lockPath) && config("topphpInstall.allow_visit") !== true) {
            return redirect($this->domain, HttpStatusEnum::REDIRECT);
        } elseif (!file_exists($lockPath)) {
            // 验证是否接受服务条款Step1是否通过
            if (!$this->topInstallServer->getCache('install-topphp-step1')) {
                return redirect("/install");
            }
            // 验证环境检测Step2是否通过
            if (!$this->topInstallServer->getCache('install-topphp-step2')) {
                return redirect("/install");
            }
            // 验证安装程序Step3是否通过
            if (!$installSuccess) {
                return redirect("/install");
            }
        }
        // 基础页面构建
        $this->basePage();
        // 安装成功页面模板配置（如果模板app/install/data/InstallSuccessContentTmpl.html内容存在，将直接引用模板内容）
        $installSuccessTmpl = @file_get_contents($this->installDataPath . "InstallSuccessContentTmpl.html");
        // 公众号名称
        $weChat = InstallEnum::TOPPHP_WECHAT_NAME;
        // 模板赋值
        View::assign('redirect', $this->domain);
        View::assign('sucTmpl', $installSuccessTmpl);
        View::assign('weChat', $weChat);
        return View::fetch("index/step4");
    }

    /**
     * 屏蔽env存在的提示
     * @return Response
     * @throws InvalidArgumentException
     * @author bai
     * @Route("envTips", method="POST")
     * @Middleware({Check::class})
     */
    public function envTips()
    {
        // 不再提示env文件已存在
        $this->topInstallServer->setCache('install-topphp-envtips', true);
        return SendMsg::jsonData();
    }

    /**
     * Db连接测试
     * @return Response
     * @throws \lib\TopException
     * @throws InvalidArgumentException
     * @author bai
     * @Route("dbConnectTest", method="POST")
     * @Middleware({Check::class})
     */
    public function dbConnectTest()
    {
        $post = $this->request->post();
        // 校验是否已安装
        $lockPath = $this->rootPath . "install.lock";
        if (file_exists($lockPath)) {
            return SendMsg::jsonAlert("您已安装成功，请不要重复安装", 40001);
        }
        // 验证Step1,Step2是否通过
        if (!$this->topInstallServer->getCache('install-topphp-step1')
            || !$this->topInstallServer->getCache('install-topphp-step2')) {
            return SendMsg::jsonAlert("安装已超时，请重新安装", 40002);
        }
        // 测试数据库连接
        $connectTest = false;
        if (!empty($post['hostname']) && !empty($post['hostport']) && !empty($post['database'])
            && !empty($post['username']) && !empty($post['password'])) {
            $connectTest = $this->topInstallServer->testDbConnect($post);
        }
        if ($connectTest) {
            return SendMsg::jsonData();
        }
        $errorMsg = $this->topInstallServer->getErrorMsg();
        $message  = !empty($errorMsg) ? $errorMsg : "连接失败";
        if (empty($post['hostname']) || empty($post['hostport']) || empty($post['database'])
            || empty($post['username']) || empty($post['password'])) {
            $message = "连接失败【参数不全】";
        }
        return SendMsg::jsonAlert($message);
    }

    /**
     * 安装数据库
     * @return Response
     * @throws \lib\TopException
     * @throws InvalidArgumentException
     * @author bai
     * @Route("start", method="POST")
     * @Middleware({Check::class})
     */
    public function start()
    {
        ini_set('max_execution_time', '0');
        $post = $this->request->post();
        // 校验是否已安装
        $lockPath = $this->rootPath . "install.lock";
        if (file_exists($lockPath)) {
            return SendMsg::jsonAlert("您已安装成功，请不要重复安装", 40001);
        }
        // 验证Step1,Step2是否通过
        if (!$this->topInstallServer->getCache('install-topphp-step1')
            || !$this->topInstallServer->getCache('install-topphp-step2')) {
            return SendMsg::jsonAlert("安装已超时，请重新安装", 40002);
        }
        // 检查预处理文件权限
        if (!is_writable($this->rootPath . 'config/database.php')) {
            return SendMsg::jsonAlert("[config/database.php]无读写权限！");
        }
        if (!is_writable($this->rootPath . 'config/cache.php')) {
            return SendMsg::jsonAlert("[config/cache.php]无读写权限！");
        }
        if (file_exists($this->rootPath . $this->topInstallServer->getEnvName())) {
            $envFile = $this->topInstallServer->getEnvName();
            if (!is_writable($this->rootPath . $envFile)) {
                return SendMsg::jsonAlert("[" . $envFile . "]无读写权限！");
            }
        }
        // 安装数据库
        if (!$this->topInstallServer->getCache("install-topphp-step3")
            || empty($this->topInstallServer->getCache("install-topphp-step3")['install_db'])) {
            $dbInstallComplete = false;
            // 数据库覆盖检测
            if (!empty($post['cover']) && $post['cover'] === 'no') {
                if (!empty($post['hostname']) && !empty($post['hostport']) && !empty($post['database'])
                    && !empty($post['username']) && !empty($post['password'])) {
                    $connectTest = $this->topInstallServer->testDbConnect($post);
                } else {
                    return SendMsg::jsonAlert("请检查安装参数");
                }
                if (!$connectTest) {
                    $errorMsg = $this->topInstallServer->getErrorMsg();
                    $message  = !empty($errorMsg) ? $errorMsg : "数据库连接失败";
                    return SendMsg::jsonAlert($message);
                }
            }
        } else {
            $dbInstallComplete = true;
            // 处理已安装过的数据库校验安装参数是否改变，改变了需重新安装
            if (!empty($this->topInstallServer->getCache("install-topphp-step3")['install_db_params'])) {
                $cacheMsg = $this->topInstallServer->getCache("install-topphp-step3")['install_db_params'];
                $nowMsg   = [
                    "hostname" => $post['hostname'],
                    "hostport" => $post['hostport'],
                    "database" => $post['database'],
                    "username" => $post['username'],
                    "password" => $post['password'],
                    "prefix"   => $post['prefix'],
                ];
                if ($nowMsg != $cacheMsg) {
                    // 清除旧数据库
                    unset($cacheMsg['prefix']);
                    $res = $this->topInstallServer->clearDb($cacheMsg);
                    if (!$res) {
                        $errorMsg = $this->topInstallServer->getErrorMsg();
                        $message  = !empty($errorMsg) ? $errorMsg : "数据库连接失败";
                        return SendMsg::jsonAlert($message);
                    }
                    $dbInstallComplete = false;
                }
            }
        }
        if (!$dbInstallComplete) {
            if (!empty($post['hostname']) && !empty($post['hostport']) && !empty($post['database'])
                && !empty($post['username']) && !empty($post['password'])) {
                // 二次开发可以通过修改sql安装文件名匹配符合业务的的sql数据库文件进行安装（sql文件目录必须是在install/sql下）
                $this->topInstallServer->setSqlName("topphp_base.sql");
                $onlineDb = $this->topInstallServer->installDb($post);
            } else {
                return SendMsg::jsonAlert("请检查安装参数");
            }
            if (!$onlineDb) {
                $errorMsg = $this->topInstallServer->getErrorMsg();
                $message  = !empty($errorMsg) ? $errorMsg : "安装失败";
                return SendMsg::jsonAlert($message);
            }
            // 写入数据库安装成功的缓存
            if ($this->topInstallServer->getCache("install-topphp-step3")) {
                $cached = $this->topInstallServer->getCache("install-topphp-step3");
            }
            $cached['install_db']        = true;
            $cached['install_db_params'] = [
                "hostname" => $post['hostname'],
                "hostport" => $post['hostport'],
                "database" => $post['database'],
                "username" => $post['username'],
                "password" => $post['password'],
                "prefix"   => $post['prefix'],
            ];
            $this->topInstallServer->setCache("install-topphp-step3", $cached);
            $dbInstallComplete = true;
        }
        // 创建redis缓存信息
        if (!empty($post['redis']) && $post['redis'] === 'on' && $dbInstallComplete) {
            if (!empty($post['host']) && !empty($post['port'])) {
                $this->topInstallServer->installCache($post);
            } else {
                return SendMsg::jsonAlert("请检查Redis缓存配置是否正确");
            }
        }
        // 创建env文件
        if (!empty($post['use_env']) && $post['use_env'] === 'on' && $dbInstallComplete) {
            if (!empty($post['hostname']) && !empty($post['hostport']) && !empty($post['database'])
                && !empty($post['username']) && !empty($post['password'])) {
                $envContent = [
                    "DATABASE" => [
                        "HOSTNAME" => $post['hostname'],
                        "DATABASE" => $post['database'],
                        "USERNAME" => $post['username'],
                        "PASSWORD" => $post['password'],
                        "HOSTPORT" => $post['hostport'],
                        "PREFIX"   => isset($post['prefix']) ? $post['prefix'] : "",
                    ]
                ];
                $this->topInstallServer->installEnv($envContent);
            } else {
                return SendMsg::jsonAlert("请检查安装参数");
            }
        }
        // 注册管理员账号
        if (!empty($post['admin']) && $post['admin'] === 'on' && $dbInstallComplete) {
            if (!empty($post['hostname']) && !empty($post['hostport']) && !empty($post['database'])
                && !empty($post['username']) && !empty($post['password'])) {
                $adminTable      = "admin";// 管理员表名（不能包含表前缀）
                $onlineAdminData = $this->topInstallServer->registerSuperAdmin($post, $adminTable);
                if (!$onlineAdminData) {
                    $errorMsg = $this->topInstallServer->getErrorMsg();
                    $message  = !empty($errorMsg) ? $errorMsg : "管理员创建失败";
                    return SendMsg::jsonAlert($message);
                }
            } else {
                return SendMsg::jsonAlert("请检查数据库安装参数");
            }
        }
        // 安装成功后的处理
        if ($dbInstallComplete) {
            $redirect = $this->topInstallServer->installSuccess();
            if ($redirect !== 'install/step4') {
                $redirect = $this->domain . "/" . $redirect;
                return SendMsg::jsonData($redirect);
            }
            return SendMsg::jsonData();
        }
        return SendMsg::jsonAlert("安装失败");
    }
}
