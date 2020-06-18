<?php
/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author bai <sleep@kaituocn.com>
 */

declare(strict_types=1);

namespace Topphp\TopphpInstall;


use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\Request;
use Topphp\TopphpLog\Log;

class TopInstallServer
{
    /**
     * Class Object
     * @var self
     */
    private static $instance;

    /**
     * 验证状态
     * @var string
     */
    public static $ok = "<i class=\"layui-icon layui-icon-ok margin-right bold\"></i>";
    public static $normal = "<i class=\"layui-icon layui-icon-help margin-right warning\"></i>";
    public static $close = "<i class=\"layui-icon layui-icon-close margin-right error bold\"></i>";
    public static $iconDir = "<i class=\"layui-icon layui-icon-form\"></i>";
    public static $iconFile = "<i class=\"layui-icon layui-icon-file\"></i>";
    private static $state = true;

    /**
     * 目录分隔符兼容
     * @var string
     */
    private static $ds = DIRECTORY_SEPARATOR;

    /**
     * 根目录
     * @var string
     */
    private static $rootPath;

    /**
     * 应用目录
     * @var string
     */
    private static $appPath;

    /**
     * 配置目录
     * @var string
     */
    private static $configPath;

    /**
     * 数据目录
     * @var string
     */
    private static $installDataPath;

    /**
     * SQL文件目录
     * @var string
     */
    private static $installSqlPath;

    /**
     * 缓存handle
     * @var Cache
     */
    private static $cacheHandle;

    /**
     * request
     * @var Request
     */
    private static $request;

    /**
     * 错误信息
     * @var string
     */
    private static $errorMsg = "";

    /**
     * 默认超级管理员用户名
     * @var string
     */
    private static $adminUser = "admin";

    /**
     * 默认超级管理员密码
     * @var string
     */
    private static $adminPass = "topphp";

    /**
     * env文件名
     * @var string
     */
    private static $envName = ".env";

    /**
     * sql文件名
     * @var string
     */
    private static $sqlName = "topphp_base.sql";

    /**
     * 表前缀
     * @var string
     */
    private static $tablePrefix = "topphp_";

    /**
     * Sql文件默认表前缀
     * @var string
     */
    private static $defaultPrefix = "topphp_";

    /**
     * 数据库配置信息
     * @var array
     */
    private static $dbConfig = [];

    /**
     * 初始化构造
     *
     * InstallServer constructor.
     */
    private function __construct()
    {
        // 目录初始化
        self::$rootPath        = \think\facade\App::getRootPath();
        self::$appPath         = \think\facade\App::getAppPath();
        self::$configPath      = \think\facade\App::getConfigPath();
        self::$installDataPath = self::$appPath . "data" . self::$ds;
        self::$installSqlPath  = self::$appPath . "sql" . self::$ds;
        // 初始化服务缓存
        $driver = config("cache.default");
        if ($driver === 'redis' && extension_loaded('redis')) {
            // redis配置信息
            $redisConfig = self::getRedisConfig();
            // 检查redis是否连接正常
            try {
                $redis = new \Redis();
                $redis->connect($redisConfig['host'], (int)$redisConfig['port'], (int)$redisConfig['timeout']);
                if ($redisConfig['password']) {
                    $redis->auth($redisConfig['password']);
                }
                $res = $redis->ping();
                if ($res === '+PONG') {
                    $configCache                    = config("cache");
                    $configCache['stores']['redis'] = $redisConfig;
                    Config::set($configCache, "cache");
                }
            } catch (\Exception $e) {
                // 不正常自动切换成文件缓存
                $error = iconv("GBK", "UTF-8", $e->getMessage());
                $error = str_replace(PHP_EOL, '', $error);
                Log::write('Redis die...（ ' . $error . ' ）', 'error');
                $driver = 'file';
            }
        }
        self::$cacheHandle = Cache::store($driver);
    }

    /**
     * 该类的实例
     * @return TopInstallServer
     * @author bai
     */
    public static function instance(): self
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        // 初始化request
        self::$request = request();
        return self::$instance;
    }

    /**
     * 私有化克隆
     * @author bai
     */
    private function __clone()
    {
    }

    /**
     * 获取错误信息
     * @return string
     * @author bai
     */
    public function getErrorMsg()
    {
        return self::$errorMsg;
    }

    /**
     * 设置当前验证状态
     * @param bool $state
     * @return bool
     * @author bai
     */
    public function setState(bool $state = false): bool
    {
        self::$state = $state;
        return self::$state;
    }

    /**
     * 获取env文件名
     * @return string
     * @author bai
     */
    public function getEnvName(): string
    {
        return self::$envName;
    }

    /**
     * 设置env文件名
     * @param string $envName
     * @return string
     * @author bai
     */
    public function setEnvName(string $envName = ".env"): string
    {
        self::$envName = $envName;
        return self::$envName;
    }

    /**
     * 获取sql文件名
     * @return string
     * @author bai
     */
    public function getSqlName(): string
    {
        return self::$sqlName;
    }

    /**
     * 设置sql文件名
     * @param string $sqlName
     * @return string
     * @author bai
     */
    public function setSqlName(string $sqlName = "topphp_base.sql"): string
    {
        self::$sqlName = $sqlName;
        return self::$sqlName;
    }

    /**
     * 获取当前验证状态
     * @param string $step2CacheName
     * @return bool
     * @author bai
     */
    public function getState(string $step2CacheName = "install-topphp-step2"): bool
    {
        $step2Cache = $this->getCache($step2CacheName);
        if (!empty($step2Cache) && isset($step2Cache['state'])) {
            self::$state = $step2Cache['state'];
        }
        return self::$state;
    }

    /**
     * 设置Sql文件中的需要被安装程序替换的表前缀
     * @param string $sqlFilePrefix
     * @author bai
     */
    public function setFromSqlDefPrefix(string $sqlFilePrefix)
    {
        self::$defaultPrefix = $sqlFilePrefix;
    }

    /**
     * 安装设置缓存
     * @param string $key
     * @param mixed $val
     * @param int $expire
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author bai
     */
    public function setCache(string $key, $val, int $expire = 3600): bool
    {
        return self::$cacheHandle->set($key, $val, $expire);
    }

    /**
     * 安装获取缓存
     * @param string $key
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author bai
     */
    public function getCache(string $key)
    {
        return self::$cacheHandle->get($key);
    }

    /**
     * 设置安装中状态（设置了此状态当次请求将不会触发重定向跳转）
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author bai
     */
    public function setInstalling()
    {
        $installingLock     = self::$rootPath . "vendor" . self::$ds . "topphp" . self::$ds . "topphp-install"
            . self::$ds . "src" . self::$ds . "data" . self::$ds;
        $installingLockData = "[ " . date("Y-m-d H:i:s") . " ] " . request()->action() . " 正在安装..." . PHP_EOL;
        if (empty($this->getCache("install-topphp-ing"))) {
            @file_put_contents($installingLock . "topphp-install.log", $installingLockData, FILE_APPEND);
            $this->setCache("install-topphp-ing", "installing-" . request()->action(), 3600);
        } else {
            $stepNum = $this->getCache("install-topphp-ing");
            if ("installing-" . request()->action() != $stepNum) {
                @file_put_contents($installingLock . "topphp-install.log", $installingLockData, FILE_APPEND);
                $this->setCache("install-topphp-ing", "installing-" . request()->action(), 3600);
            }
        }
        if (!empty($this->getCache("install-topphp-ing-time"))) {
            $times = (int)$this->getCache("install-topphp-ing-time") + 1;
            // 防请求攻击，导致写入文件过大，超过300次，自动清空文件
            if ($times > 300) {
                @file_put_contents($installingLock . "topphp-install.log", "");
            }
        } else {
            $times = 1;
        }
        $this->setCache("install-topphp-ing-time", $times, 0);
        @file_put_contents($installingLock . "topphp-installing.lock", app('http')->getName() . "_" . time());
        return $installingLockData;
    }

    /**
     * 清除安装中的状态（清除后每次请求都会校验安装是否成功并触发重定向）
     * @author bai
     */
    public function clearInstalling()
    {
        $installingLock = self::$rootPath . "vendor" . self::$ds . "topphp" . self::$ds . "topphp-install"
            . self::$ds . "src" . self::$ds . "data" . self::$ds;
        @unlink($installingLock . "topphp-installing.lock");
    }

    /**
     * 部署安装重定向状态（为非install应用，采用接口调用的方式提供的是否安装的校验）
     * @return bool
     * @author bai
     */
    public function installRedirectState()
    {
        $installRedirectStatePath = self::$rootPath . "vendor" . self::$ds . "topphp" . self::$ds . "topphp-install"
            . self::$ds . "src" . self::$ds . "data" . self::$ds;
        if (file_exists($installRedirectStatePath . "topphp-redirect.log")) {
            return true;
        }
        return false;
    }

    /**
     * 更新配置文件
     * @param string $file
     * @param string $saveFile
     * @param array $config
     * @return bool|int
     * @author bai
     */
    private function putFileData(string $file, string $saveFile, array $config)
    {
        $fileData = @file_get_contents($file);
        if (!empty($fileData)) {
            foreach ($config as $key => $val) {
                $fileData = preg_replace("/\@" . $key . "/", $val, $fileData);
            }
            return @file_put_contents($saveFile, $fileData);
        }
        return true;
    }

    /**
     * redis缓存配置
     * @return array
     * @author bai
     */
    private function getRedisConfig(): array
    {
        $redisConfig = [
            // 连接超时1s自动切换文件缓存，
            'timeout' => 1
        ];
        return array_merge($redisConfig, config('cache.stores.redis'));
    }

    /**
     * 删除Step1缓存
     * @param string $step1name
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author bai
     */
    public function rmStep1Cached(string $step1name = "install-topphp-step1"): bool
    {
        self::$cacheHandle->set("install-topphp-ing-time", null);
        $installLog = self::$rootPath . "vendor" . self::$ds . "topphp" . self::$ds . "topphp-install"
            . self::$ds . "src" . self::$ds . "data" . self::$ds;
        // 安装日志大于500k自动清空
        if (filesize($installLog . "topphp-install.log") > 500 * 1024) {
            @file_put_contents($installLog . "topphp-install.log", "");
        }
        return self::$cacheHandle->set($step1name, null);
    }

    /**
     * 删除Step2缓存
     * @param string $step2name
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author bai
     */
    public function rmStep2Cached(string $step2name = "install-topphp-step2"): bool
    {
        return self::$cacheHandle->set($step2name, null);
    }

    /**
     * 删除Step3缓存
     * @param string $step3name
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author bai
     */
    public function rmStep3Cached(string $step3name = "install-topphp-step3"): bool
    {
        if (!empty($this->getCache($step3name)['install_db_params'])) {
            $cacheMsg = $this->getCache($step3name)['install_db_params'];
            // 清除旧数据库
            unset($cacheMsg['prefix']);
            $this->clearDb($cacheMsg);
        }
        return self::$cacheHandle->set($step3name, null);
    }

    /**
     * 删除Step4缓存
     * @param string $step4name
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author bai
     */
    public function rmStep4Cached(string $step4name = "install-topphp-step4"): bool
    {
        return self::$cacheHandle->set($step4name, null);
    }

    /**
     * 删除env文件
     * @author bai
     */
    public function rmEnvFile()
    {
        if (!empty(self::$envName) && file_exists(self::$rootPath . self::$envName)) {
            @unlink(self::$rootPath . self::$envName);
        }
    }

    /**
     * 清除全部Install缓存
     * @param bool $needEnv
     * @author bai
     */
    public function rmClearCached($needEnv = false)
    {
        if ($needEnv) {
            $this->rmEnvFile();
        }
        $this->rmStep1Cached();
        $this->rmStep2Cached();
        $this->rmStep3Cached();
        $this->rmStep4Cached();
    }

    /**
     * 环境检测配置限制
     * @return array
     * @author bai
     */
    private function envConfig(): array
    {
        $envConfig = config("topphpInstall.env_config");
        if (empty($envConfig)) {
            $envConfig = [];
        }
        $default = [
            "checkServer" => [
                "apache" => 2,// float
                "nginx"  => 1.6,// float
                "swoole" => 4.4// float
            ],
            "checkPhp"    => 7.2,// float
            "checkDisk"   => 80// int 单位M（兆）
        ];
        return array_merge($default, $envConfig);
    }

    /**
     * 获取并校验服务环境
     * @return string
     * @author bai
     */
    public function checkServer(): string
    {
        $server = self::$request->server("SERVER_SOFTWARE");
        if (preg_match("/(Apache\/[0-9\.]+)/", $server, $matches)
            || preg_match("/Apache/", ucfirst($server), $matches)) {
            $version = @explode("/", $matches[0])[1];
            if ($version === null) {
                $matches[0] = $matches[0] . " unknow";
                $resServer  = self::$normal . $matches[0];
            } else {
                if ((float)$version < (float)$this->envConfig()['checkServer']['apache']) {
                    $resServer   = self::$close . "<font class='error'>" . $matches[0] . "</font>";
                    self::$state = false;
                } else {
                    $resServer = self::$ok . $matches[0];
                }
            }
        } elseif (preg_match("/(nginx\/[0-9\.]+)/", $server, $matches)
            || preg_match("/Nginx/", ucfirst($server), $matches)) {
            $version = @explode("/", $matches[0])[1];
            if ($version === null) {
                $matches[0] = $matches[0] . " unknow";
                $resServer  = self::$normal . $matches[0];
            } else {
                if ((float)$version < (float)$this->envConfig()['checkServer']['nginx']) {
                    $resServer   = self::$close . "<font class='error'>" . $matches[0] . "</font>";
                    self::$state = false;
                } else {
                    $resServer = self::$ok . $matches[0];
                }
            }
        } elseif (preg_match("/Swoole/", $server)) {
            $version = @explode("/", $server)[1];
            if (empty($version)) {
                $resServer = self::$normal . "Swoole unknow";
            } elseif ((float)$version < (float)$this->envConfig()['checkServer']['swoole']) {
                $resServer   = self::$close . "<font class='error'>Swoole/{$version}</font>";
                self::$state = false;
            } else {
                $resServer = self::$ok . "Swoole/" . $version;
            }
        } else {
            if (empty($server)) {
                $server = "unknow";
            }
            $resServer   = self::$close . "<font class='error'>" . $server . "</font>";
            self::$state = false;
        }
        return $resServer;
    }

    /**
     * 获取并校验PHP版本
     * @return string
     * @author bai
     */
    public function checkPhp(): string
    {
        $version = phpversion();
        if ((float)$version < (float)$this->envConfig()['checkPhp']) {
            $resPhp      = self::$close . "<font class='error'>{$version}</font>";
            self::$state = false;
        } else {
            $resPhp = self::$ok . $version;
        }
        return $resPhp;
    }

    /**
     * 获取上传最大值
     * @return string
     * @author bai
     */
    public function checkUpload(): string
    {
        $limit = ini_get('upload_max_filesize');
        return self::$ok . $limit;
    }

    /**
     * 获取剩余硬盘空间
     * @return string
     * @author bai
     */
    public function checkDisk(): string
    {
        $surplusSpace = round((disk_free_space(".") / (1024 * 1024)), 2);
        if ($surplusSpace < (float)$this->envConfig()['checkDisk']) {
            $resDisk     = self::$close . "<font class='error'>{$surplusSpace}M</font>";
            self::$state = false;
        } else {
            if ($surplusSpace > 1024) {
                $surplusSpace = number_format($surplusSpace / 1024, 2, '.', '') . "G";
            } else {
                $surplusSpace = $surplusSpace . "M";
            }
            $resDisk = self::$ok . $surplusSpace;
        }
        return $resDisk;
    }

    /**
     * 获取并校验目录&文件权限
     * @param array $checkItems
     * @return array
     * @author bai
     */
    public function checkDir(array $checkItems = []): array
    {
        // 开始校验
        foreach ($checkItems as &$item) {
            if ($item[0] === 'dir') {
                if (!is_writable($item[1])) {
                    if (is_dir($item[1])) {
                        $item[4] = '不可写';
                        $item[5] = false;
                    } else {
                        $item[4] = '不存在';
                        $item[5] = false;
                    }
                }
            } else {
                if (!is_writable($item[1])) {
                    $item[4] = '不可写';
                    $item[5] = false;
                }
            }
        }
        // 清除读写权限缓存
        unset($item);
        clearstatcache();
        // 解析结果
        $dirFileAuth = [];
        foreach ($checkItems as $item) {
            if ($item[0] === 'dir') {
                $icon = self::$iconDir;
            } else {
                $icon = self::$iconFile;
            }
            if ($item[5]) {
                $dirFileAuth[$item[2]] = [
                    "icon" => $icon,
                    "need" => $item[3],
                    "now"  => self::$ok . $item[4]
                ];
            } else {
                $dirFileAuth[$item[2]] = [
                    "icon" => $icon,
                    "need" => $item[3],
                    "now"  => self::$close . "<font class='error'>" . $item[4] . "</font>"
                ];
                self::$state           = false;
            }
        }
        return $dirFileAuth;
    }

    /**
     * 构造检验目录配置
     * @param array $checkDir
     * @return array
     * @author bai
     */
    public function checkDirConfig(array $checkDir = []): array
    {
        $checkItems = [
            // 目录or文件--路径--显示名称--需要权限--当前权限--状态
            ['dir', self::$rootPath . 'app', 'app', '读写', '读写', true],
            ['dir', self::$rootPath . 'public/static', 'static', '读写', '读写', true],
            ['dir', self::$rootPath . 'runtime', 'runtime', '读写', '读写', true],
            ['file', self::$rootPath . 'app/install/data/env.tpl', '.env', '读写', '读写', true],
            ['file', self::$rootPath . 'config/database.php', 'config/database.php', '读写', '读写', true],
            ['file', self::$rootPath . 'config/cache.php', 'config/cache.php', '读写', '读写', true],
        ];
        $configDir  = config("topphpInstall.dir_config");
        if (!empty($configDir) && empty($checkDir)) {
            $checkDir = $configDir;
        }
        if (!empty($checkDir) && is_array($checkDir)) {
            $checkItems = [];
            foreach ($checkDir as $ik => &$item) {
                if (is_array($item) && count($item) === 3) {
                    $item[1]         = self::$rootPath . $item[1];
                    $item[]          = "读写";
                    $item[]          = "读写";
                    $item[]          = true;
                    $checkItems[$ik] = $item;
                }
            }
        }
        return $checkItems;
    }

    /**
     * 获取并校验函数&扩展权限
     * @param array $checkItems
     * @param array $allowIgnore 可忽略的配置项 key[配置名称]=》val[提示语(recommend 建议 optional 可选 ignore 可忽略)]
     * @return array
     * @author bai
     */
    public function checkFunOrExt(array $checkItems = [], array $allowIgnore = []): array
    {
        // 开始校验
        foreach ($checkItems as &$item) {
            switch ($item[1]) {
                case '扩展':
                    if (!extension_loaded(strtolower($item[0]))) {
                        if (in_array($item[0], array_keys($allowIgnore))) {
                            if ($allowIgnore[$item[0]] === "recommend") {
                                $item[2] = '未安装（建议）';
                            } elseif ($allowIgnore[$item[0]] === "optional") {
                                $item[2] = '未安装（可选）';
                            } else {
                                $item[2] = '未安装（可忽略）';
                            }
                            $item[3] = null;
                        } else {
                            $item[2] = '不支持';
                            $item[3] = false;
                        }
                    }
                    break;
                case '模块':
                case '函数':
                    if ($item[0] === 'bcmath') {
                        if (!function_exists('bcadd')) {
                            $item[2] = '不支持';
                            $item[3] = false;
                        }
                    } else {
                        if (!function_exists($item[0])) {
                            $item[2] = '不支持';
                            $item[3] = false;
                        }
                    }
                    break;
            }
        }
        unset($item);
        // 解析结果
        $extAuth = [];
        foreach ($checkItems as $item) {
            if ($item[3]) {
                $extAuth[$item[0]] = [
                    "type" => $item[1],
                    "now"  => self::$ok . $item[2]
                ];
            } elseif ($item[3] === null) {
                $extAuth[$item[0]] = [
                    "type" => $item[1],
                    "now"  => self::$normal . $item[2]
                ];
            } else {
                $extAuth[$item[0]] = [
                    "type" => $item[1],
                    "now"  => self::$close . "<font class='error'>" . $item[2] . "</font>"
                ];
                self::$state       = false;
            }
        }
        return $extAuth;
    }

    /**
     * 构造扩展检测配置
     * @param array $checkExt
     * @return array
     * @author bai
     */
    public function checkExtConfig(array $checkExt = []): array
    {
        $checkItems = [
            // name--类型--状态名称--状态
            ['PDO', '扩展', '已开启', true],
            ['CURL', '扩展', '已开启', true],
            ['Swoole', '扩展', '已开启', true],
            ['Redis', '扩展', '已开启', true],
            ['fileinfo', '扩展', '已开启', true],
            ['mbstring', '扩展', '已开启', true],
            ['openssl', '扩展', '已开启', true],
            ['pdo_mysql', '扩展', '已开启', true],
            ['bcmath', '模块', '支持', true],
            ['file_put_contents', '函数', '支持', true],
            ['file_get_contents', '函数', '支持', true],
            ['xml', '函数', '支持', true],
            ['shell_exec', '函数', '支持', true],
        ];
        $configExt  = config("topphpInstall.ext_config");
        if (!empty($configExt) && empty($checkExt)) {
            $checkExt = $configExt;
        }
        if (!empty($checkExt) && is_array($checkExt)) {
            $checkItems = [];
            foreach ($checkExt as $ik => &$item) {
                if (is_array($item) && count($item) === 3) {
                    $item[]          = true;
                    $checkItems[$ik] = $item;
                }
            }
        }
        return $checkItems;
    }

    /**
     * 数据库连接测试
     * @param array $params 数据库连接参数
     * @return bool
     * @author bai
     */
    public function testDbConnect(array $params): bool
    {
        // 初始化配置数据
        $data         = [
            'hostname' => "127.0.0.1",
            'hostport' => "3306",
            'database' => "topphp_skeleton",
            'username' => "root",
            'password' => "root",
        ];
        $dbType       = [
            "mysql",
            "sqlite",
            "pgsql",
            "sqlsrv",
            "mongo",
            "oracle"
        ];
        $cover        = isset($params['cover']) && $params['cover'] === 'yes' ? "yes" : "no";
        $data['type'] = isset($params['type']) && in_array($params['type'], $dbType) ? $params['type'] : "mysql";
        $data         = array_intersect_key(array_merge($data, $params), $data);
        $database     = $data['database'];
        if (empty($database)) {
            self::$errorMsg = "请填写数据库名称";
            return false;
        }
        // 检测数据库连接
        try {
            if (!extension_loaded('pdo_mysql') && $data['type'] === 'mysql') {
                self::$errorMsg = "请先安装pdo_mysql！";
                return false;
            }
            unset($data['database']);
            $dsn       = $this->parseDsn($data);
            $dbConnect = $this->connect($dsn, $data);
            if (empty($dbConnect)) {
                if (empty(self::$errorMsg)) {
                    self::$errorMsg = "数据库连接失败<br>请检查数据库配置！";
                }
                return false;
            }
        } catch (\Exception $e) {
            self::$errorMsg = "数据库连接失败<br>请检查数据库配置！";
            return false;
        }
        // 不覆盖检测是否已存在数据库
        if ($cover === "no") {
            //$dbVersion = $this->query('select version() as version', $dbConnect);
            $check = $this->query('SELECT * FROM information_schema.schemata WHERE schema_name="' . $database . '"',
                $dbConnect);
            if ($check) {
                self::$errorMsg = "数据库【{$database}】已存在<br>如需覆盖，请选择覆盖数据库！";
                return false;
            }
        }
        return true;
    }

    /**
     * 解析Dsn
     * @param string $type
     * @param array $config
     * @return string
     * @author bai
     */
    private function parseDsn(array $config = [], string $type = "mysql"): string
    {
        $dsn       = "";
        $configKey = [
            "hostname",
            "hostport",
            "database",
            "username",
            "password"
        ];
        if (count(array_intersect(array_keys($config), $configKey)) > 5) {
            return $dsn;
        }
        switch ($type) {
            case "mysql":
                $dsn = 'mysql:host=' . $config['hostname'] . ';port=' . $config['hostport'];
                if (!empty($config['database'])) {
                    $dsn .= ';dbname=' . $config['database'];
                }
                $dsn .= ';charset=utf8';
                break;
            case "sqlite":
                $dsn = 'sqlite:' . $config['database'];
                break;
            case "pgsql":
                $dsn = 'pgsql:dbname=' . $config['database'] . ';host=' . $config['hostname'];
                if (!empty($config['hostport'])) {
                    $dsn .= ';port=' . $config['hostport'];
                }
                if (!empty($config['database'])) {
                    $dsn .= ';dbname=' . $config['database'];
                }
                break;
            case "sqlsrv":
                $dsn = 'sqlsrv:Database=' . $config['database'] . ';Server=' . $config['hostname'];
                if (!empty($config['hostport'])) {
                    $dsn .= ',' . $config['hostport'];
                }
                break;
            case "mongo":
                $dsn = '27017';
                if (!empty($config['hostport'])) {
                    $dsn = $config['hostport'];
                }
                break;
            case "oracle":
                $dsn = 'oci:dbname=';
                if (!empty($config['hostname'])) {
                    $dsn .= '//' . $config['hostname'] . ($config['hostport'] ? ':' . $config['hostport'] : '') . '/';
                }
                $dsn .= $config['database'] . ';charset=utf8';
                break;
        }
        return $dsn;
    }

    /**
     * 连接数据库
     * @param string $dsn
     * @param array $config
     * @param string $type
     * @return \PDO|\think\db\BaseQuery|null
     * @author bai
     */
    private function connect(string $dsn, array $config = [], string $type = "mysql")
    {
        $connect = null;
        switch ($type) {
            case "mysql":
            case "sqlite":
            case "pgsql":
            case "sqlsrv":
            case "oracle":
                $option = [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                ];
                try {
                    $username = empty($config['username']) ? "" : $config['username'];
                    $password = empty($config['password']) ? "" : $config['password'];
                    $connect  = new \PDO($dsn, $username, $password, $option);
                } catch (\PDOException $e) {
                    if (preg_match("/password/", $e->getMessage())) {
                        self::$errorMsg = "数据库账号或密码错误";
                    } else {
                        self::$errorMsg = $e->getMessage();
                    }
                }
                break;
            case "mongo":
                $mongoConfig                   = [
                    // 数据库类型
                    'type'              => 'mongo',
                    // 服务器地址
                    'hostname'          => '',
                    // 数据库名
                    'database'          => '',
                    // 用户名
                    'username'          => '',
                    // 密码
                    'password'          => '',
                    // 端口
                    'hostport'          => $dsn,
                    // 数据库连接参数
                    'params'            => [],
                    // 数据库调试模式
                    'debug'             => true,
                    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
                    'deploy'            => 0,
                    // 数据库读写是否分离 主从式有效
                    'rw_separate'       => false,
                    // 监听SQL
                    'trigger_sql'       => true,
                    // 读写分离后 主服务器数量
                    'master_num'        => 1,
                    // 指定从服务器序号
                    'slave_no'          => '',
                    // 是否严格检查字段是否存在
                    'fields_strict'     => true,
                    // 是否需要断线重连
                    'break_reconnect'   => false,
                    // 字段缓存路径
                    'schema_cache_path' => app()->getRuntimePath() . 'schema' . DIRECTORY_SEPARATOR,
                ];
                $databaseConfig                = config("database");
                $connections                   = config("database.connections");
                $connections['mongo']          = $mongoConfig;
                $databaseConfig['connections'] = $connections;
                Config::set($databaseConfig, "database");
                $connect = \think\facade\Db::connect('mongo');
                break;
        }
        return $connect;
    }

    /**
     * 执行SQL语句
     * @param string $sql
     * @param $dbConnect
     * @param string $type
     * @return bool|mixed
     * @author bai
     */
    private function execute(string $sql, $dbConnect, string $type = "pdo")
    {
        if ($type === 'mongo') {
            return $dbConnect->execute($sql);
        } else {
            try {
                $pdo = $dbConnect->prepare($sql);
                $res = $pdo->execute();
            } catch (\PDOException $e) {
                self::$errorMsg = $e->getMessage();
                return false;
            }
            if ($res) {
                return $pdo->rowCount();
            }
            self::$errorMsg = "执行SQL失败，请检查SQL语句";
            return false;
        }
    }

    /**
     * 查询SQL语句
     * @param string $sql
     * @param $dbConnect
     * @param string $type
     * @return bool|mixed
     * @author bai
     */
    private function query(string $sql, $dbConnect, string $type = "pdo")
    {
        if ($type === 'mongo') {
            return $dbConnect->query($sql);
        } else {
            try {
                $pdo = $dbConnect->query($sql);
            } catch (\PDOException $e) {
                self::$errorMsg = $e->getMessage();
                return false;
            }
            $res = $pdo->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($res) && is_array($res) && count($res) === 1) {
                return $res[0];
            }
            return $res;
        }
    }

    /**
     * 安装数据库
     * @param array $params
     * @param string $cacheName
     * @param string $sqlType
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author bai
     */
    public function installDb(
        array $params,
        string $cacheName = "install-topphp-step3",
        string $sqlType = "mysql"
    ): bool {
        $data   = [];
        $dbType = [
            "mysql",
            //"sqlite",
            //"pgsql",
            //"sqlsrv",
            //"mongo",
            //"oracle"
        ];
        if (!in_array($sqlType, $dbType)) {
            self::$errorMsg = "暂不支持该数据库类型";
            return false;
        }
        $data['hostname'] = $params['hostname'];
        $data['hostport'] = $params['hostport'];
        $database         = $params['database'];
        $data['username'] = $params['username'];
        $data['password'] = $params['password'];
        try {
            if (!extension_loaded('pdo_mysql') && $data['type'] === 'mysql') {
                self::$errorMsg = "请先安装pdo_mysql！";
                return false;
            }
            $dsn       = $this->parseDsn($data);
            $dbConnect = $this->connect($dsn, $data);
            // 删除已存在数据库
            $this->execute("DROP DATABASE IF  EXISTS  `" . $database . "`", $dbConnect);
            // 创建数据库
            if (!$this->execute("CREATE DATABASE IF NOT EXISTS `{$database}` DEFAULT CHARACTER SET utf8mb4",
                $dbConnect)) {
                if (empty(self::$errorMsg)) {
                    self::$errorMsg = "创建数据库失败";
                }
                return false;
            }

            // 生成数据库配置文件
            $data['type']     = $sqlType;
            $data['database'] = $database;
            $data['prefix']   = isset($params['prefix']) ? $params['prefix'] : "topphp_";
            $dbInitFile       = self::$installDataPath . "database.tpl";
            if (!file_exists($dbInitFile)) {
                self::$errorMsg = "安装模板[database.tpl]不存在";
                return false;
            }
            $dbWriteFile = self::$rootPath . "config" . self::$ds . "database.php";
            $dbConfig    = [
                "hostname" => $data['hostname'],
                "database" => $data['database'],
                "username" => $data['username'],
                "password" => $data['password'],
                "hostport" => $data['hostport'],
                "prefix"   => $data['prefix'],
            ];
            // 缓存原始配置，方便后续回滚
            if ($this->getCache($cacheName)) {
                $cached = $this->getCache($cacheName);
            }
            $cached['db']['file']    = $dbWriteFile;
            $cached['db']['content'] = @file_get_contents($dbWriteFile);
            $this->setCache($cacheName, $cached);
            // 写入新配置
            $this->putFileData($dbInitFile, $dbWriteFile, $dbConfig);

            // 写入初始化sql数据
            $sqlFile           = self::$installSqlPath . self::$sqlName;
            self::$tablePrefix = $data['prefix'];
            self::$dbConfig    = $dbConfig;
            $res               = $this->writeSql($sqlFile);
            if ($res === false) {
                return false;
            }
        } catch (\Exception $e) {
            self::$errorMsg = "数据库【{$database}】连接失败<br>请检查数据库配置！";
            return false;
        }
        return true;
    }

    /**
     * 导入SQL安装数据（业务涉及创建数据库，生成数据表，写入数据表数据）
     * @param array $params 数据库配置参数 hostname|hostport|database|username|password|prefix
     * @param string $sqlName SQL文件名，不能包含路径，SQL文件必须放在install/sql文件夹下
     * @param string $sqlType 暂仅支持mysql
     * @return bool
     * @author bai
     */
    public function executeSqlFile(
        array $params,
        string $sqlName,
        string $sqlType = "mysql"
    ): bool {
        $data   = [];
        $dbType = [
            "mysql",
            //"sqlite",
            //"pgsql",
            //"sqlsrv",
            //"mongo",
            //"oracle"
        ];
        if (!in_array($sqlType, $dbType)) {
            self::$errorMsg = "暂不支持该数据库类型";
            return false;
        }
        $data['hostname'] = $params['hostname'];
        $data['hostport'] = $params['hostport'];
        $database         = $params['database'];
        $data['username'] = $params['username'];
        $data['password'] = $params['password'];
        try {
            if (!extension_loaded('pdo_mysql') && $data['type'] === 'mysql') {
                self::$errorMsg = "请先安装pdo_mysql！";
                return false;
            }
            $dsn       = $this->parseDsn($data);
            $dbConnect = $this->connect($dsn, $data);
            // 删除已存在数据库
            $this->execute("DROP DATABASE IF  EXISTS  `" . $database . "`", $dbConnect);
            // 创建数据库
            if (!$this->execute("CREATE DATABASE IF NOT EXISTS `{$database}` DEFAULT CHARACTER SET utf8mb4",
                $dbConnect)) {
                if (empty(self::$errorMsg)) {
                    self::$errorMsg = "创建数据库失败";
                }
                return false;
            }
            // 写入初始化sql数据
            $dbConfig          = [
                "hostname" => $data['hostname'],
                "database" => $data['database'],
                "username" => $data['username'],
                "password" => $data['password'],
                "hostport" => $data['hostport'],
                "prefix"   => $params['prefix'],
            ];
            $sqlFile           = self::$installSqlPath . $sqlName;
            self::$tablePrefix = $data['prefix'];
            self::$dbConfig    = $dbConfig;
            $res               = $this->writeSql($sqlFile);
            if ($res === false) {
                return false;
            }
        } catch (\Exception $e) {
            self::$errorMsg = "数据库【{$database}】连接失败<br>请检查数据库配置！";
            return false;
        }
        return true;
    }

    /**
     * 删除指定数据库
     * @param array $dbConfig
     * @param string $sqlType
     * @return bool
     * @author bai
     */
    public function clearDb(array $dbConfig, string $sqlType = "mysql"): bool
    {
        $configKey = [
            "hostname",
            "hostport",
            "database",
            "username",
            "password"
        ];
        $paramsKey = array_keys($dbConfig);
        if ($configKey != $paramsKey) {
            self::$errorMsg = "数据库配置参数错误！";
            return false;
        }
        $dbType = [
            "mysql",
            //"sqlite",
            //"pgsql",
            //"sqlsrv",
            //"mongo",
            //"oracle"
        ];
        if (!in_array($sqlType, $dbType)) {
            self::$errorMsg = "暂不支持该数据库类型";
            return false;
        }
        try {
            if (!extension_loaded('pdo_mysql') && $sqlType === 'mysql') {
                self::$errorMsg = "请先安装pdo_mysql！";
                return false;
            }
            $database = $dbConfig['database'];
            unset($dbConfig['database']);
            $dsn       = $this->parseDsn($dbConfig);
            $dbConnect = $this->connect($dsn, $dbConfig);
            $this->execute("DROP DATABASE IF  EXISTS  `" . $database . "`", $dbConnect);
        } catch (\Exception $e) {
            self::$errorMsg = "数据库连接失败<br>请检查数据库配置！";
            return false;
        }
        return true;
    }

    /**
     * 读写SQL
     * @param string $sqlFile SQL文件路径
     * @param string $replacePrefix 前缀替换成xxx
     * @param bool $onlyRead 是否仅读取SQL语句
     * @param array $dbConfig 数据库配置信息
     * @return array|bool|string
     * @author bai
     */
    public function writeSql(string $sqlFile, string $replacePrefix = "", bool $onlyRead = false, array $dbConfig = [])
    {
        if (!file_exists($sqlFile)) {
            self::$errorMsg = "安装SQL[" . basename($sqlFile) . "]不存在";
            return false;
        }
        if (empty($replacePrefix)) {
            $replacePrefix = self::$tablePrefix;
        }
        $sqlList = $this->parseSql($sqlFile, [self::$defaultPrefix => $replacePrefix]);
        if ($onlyRead) {
            return $sqlList;
        }
        if (empty($dbConfig)) {
            if (!empty(self::$dbConfig)) {
                $dbConfig = self::$dbConfig;
            } else {
                self::$errorMsg = "请传入数据库配置信息";
                return false;
            }
        }
        $configInit = [
            'hostname' => "127.0.0.1",
            'hostport' => "3306",
            'database' => "",
            'username' => "root",
            'password' => "root",
        ];
        $dbConfig   = array_intersect_key(array_merge($configInit, $dbConfig), $configInit);
        if (empty($dbConfig['database'])) {
            self::$errorMsg = "请指定数据库database";
            return false;
        }
        if (!empty($sqlList) && is_array($sqlList) && !empty(array_filter($sqlList))) {
            $sqlList   = array_filter($sqlList);
            $dsn       = $this->parseDsn($dbConfig);
            $dbConnect = $this->connect($dsn, $dbConfig);
            if (empty($dbConnect)) {
                if (empty(self::$errorMsg)) {
                    self::$errorMsg = "数据库连接失败<br>请检查数据库配置！";
                }
                return false;
            }
            foreach ($sqlList as $sql) {
                try {
                    $this->execute($sql, $dbConnect);
                } catch (\Exception $e) {
                    self::$errorMsg = "请启用InnoDB数据引擎<br>并检查数据库是否有DROP和CREATE权限！";
                    return false;
                }
            }
        } else {
            self::$errorMsg = "安装SQL数据不存在<br>请检查.sql文件SQL语句";
            return false;
        }
        return true;
    }

    /**
     * 解析Sql
     * @param string $sqlFile
     * @param array $prefix
     * @param bool $isArray
     * @return array|string
     * @author bai
     */
    private function parseSql($sqlFile = '', $prefix = [], $isArray = true)
    {
        // 获取Sql内容
        $sql = @file_get_contents($sqlFile);
        if (!empty($sql)) {
            $fromPrefix    = '';
            $replacePrefix = '';
            // 替换规则 默认ignore全部忽略
            $replaceRule = 'ignore';
            // 检验前缀替换规则
            if (!empty($prefix)) {
                $replacePrefix = current($prefix);
                $fromPrefix    = current(array_flip($prefix));
                if ($fromPrefix !== "*") {
                    $replaceRule = "assign";
                } else {
                    // key为通配符，表示全部替换
                    $replaceRule = "all";
                }
            }
            // 初始化Sql数据并开始解析Sql
            $pureSql   = [];
            $notesSign = false;
            $sql       = str_replace(["\r\n", "\r"], "\n", $sql);
            $sql       = explode("\n", trim($sql));
            foreach ($sql as $key => $line) {
                if ($line == '') {
                    continue;
                }
                if (preg_match("/^(#|--)/", $line)) {
                    continue;
                }
                if (preg_match("/^\/\*(.*?)\*\//", $line)) {
                    continue;
                }
                if (substr($line, 0, 2) == '/*') {
                    $notesSign = true;
                    continue;
                }
                if (substr($line, -2) == '*/') {
                    $notesSign = false;
                    continue;
                }
                if ($notesSign) {
                    continue;
                }
                // 替换表前缀
                if ($replaceRule !== 'ignore') {
                    if ($replaceRule === "all" && !empty($replacePrefix)) {
                        $line = preg_replace('/DROP TABLE IF EXISTS `([\S]+_)/isU',
                            'DROP TABLE IF EXISTS `' . $replacePrefix, $line);
                        $line = preg_replace('/CREATE TABLE `([\S]+_)/isU', 'CREATE TABLE `' . $replacePrefix, $line);
                        $line = preg_replace('/INSERT INTO `([\S]+_)/isU', 'INSERT INTO `' . $replacePrefix, $line);
                    } elseif ($replaceRule === "assign" && !empty($fromPrefix) && !empty($replacePrefix)) {
                        $line = str_replace('DROP TABLE IF EXISTS `' . $fromPrefix,
                            'DROP TABLE IF EXISTS `' . $replacePrefix, $line);
                        $line = str_replace('CREATE TABLE `' . $fromPrefix, 'CREATE TABLE `' . $replacePrefix, $line);
                        $line = str_replace('INSERT INTO `' . $fromPrefix, 'INSERT INTO `' . $replacePrefix, $line);
                    }
                }
                if ($line == 'BEGIN;' || $line == 'COMMIT;') {
                    continue;
                }
                // sql语句
                array_push($pureSql, $line);
            }
            // 是否返回多条sql语句数组
            if ($isArray) {
                // 以数组形式返回sql语句
                $pureSql = implode($pureSql, "\n");
                $pureSql = explode(";\n", $pureSql);
                return $pureSql;
            }
            // 仅返回一条长的sql语句
            return implode($pureSql, "");
        }
        return $isArray ? [] : "";
    }

    /**
     * 写入Redis缓存配置
     * @param array $params
     * @param string $cacheName
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author bai
     */
    public function installCache(array $params, string $cacheName = "install-topphp-step3"): bool
    {
        $data['host']     = isset($params['host']) ? $params['host'] : '127.0.0.1';
        $data['port']     = isset($params['port']) ? $params['port'] : '6379';
        $data['password'] = isset($params['pass']) ? $params['pass'] : '';
        $data['select']   = isset($params['select']) ? $params['select'] : '0';
        $cacheInitFile    = self::$installDataPath . "cache.tpl";
        if (!file_exists($cacheInitFile)) {
            self::$errorMsg = "安装模板[cache.tpl]不存在";
            return false;
        }
        $cacheWriteFile = self::$rootPath . "config" . self::$ds . "cache.php";
        // 缓存原始配置，方便后续回滚
        if ($this->getCache($cacheName)) {
            $cached = $this->getCache($cacheName);
        }
        $cached['cache']['file']    = $cacheWriteFile;
        $cached['cache']['content'] = @file_get_contents($cacheWriteFile);
        $this->setCache($cacheName, $cached);
        // 写入新配置
        $this->putFileData($cacheInitFile, $cacheWriteFile, $data);
        return true;
    }

    /**
     * 安装配置（安装服务提供安装配置方法，规则使用 “@xxx” 正则替换对应的配置参数值）
     * @param array $configParams 配置参数 [config_name=>config_val]
     * @param string $tmplFilePath 模板文件路径（模板文件中对应的配置应为[config_name=>@config_name]，详细可参考已存在的模板）
     * @param string $writeFilePath 替换配置文件路径
     * @return bool|int
     * @author bai
     */
    public function installConfig(array $configParams, string $tmplFilePath, string $writeFilePath)
    {
        if (!file_exists($tmplFilePath)) {
            self::$errorMsg = "安装模板[" . basename($tmplFilePath) . "]不存在";
            return false;
        }
        return $this->putFileData($tmplFilePath, $writeFilePath, $configParams);
    }

    /**
     * 安装env文件
     * @param array $envParams
     * @param string $envFilePath
     * @return bool|int|string
     * @author bai
     */
    public function installEnv(array $envParams, string $envFilePath = "")
    {
        $envInitFile = self::$installDataPath . "env.tpl";
        $envInitData = parse_ini_file($envInitFile, true);
        $envSaveData = $this->arrayMergeMultiple($envInitData, $envParams);
        if (empty($envFilePath)) {
            $envFilePath = self::$rootPath . self::$envName;
        }
        return $this->putEnvFile($envFilePath, $envSaveData);
    }

    /**
     * 配置写入env文件
     * @param $file
     * @param $array
     * @param int $i
     * @return bool|int|string
     * @author bai
     */
    private function putEnvFile($file, $array, $i = 0)
    {
        $str = "";
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $str .= str_repeat(" ", $i * 2) . "[$k]" . PHP_EOL;
                $str .= $this->putEnvFile("", $v, $i + 1);
            } else {
                $str .= str_repeat(" ", $i * 2) . "$k = $v" . PHP_EOL;
            }
        }
        if ($file) {
            return @file_put_contents($file, $str);
        } else {
            return $str;
        }
    }

    /**
     * 多维数组合并
     * @param array $array1
     * @param array $array2
     * @return array
     * @author bai
     */
    private function arrayMergeMultiple(array $array1, array $array2): array
    {
        $merge = $array1 + $array2;
        $data  = [];
        foreach ($merge as $key => $val) {
            if (
                isset($array1[$key])
                && is_array($array1[$key])
                && isset($array2[$key])
                && is_array($array2[$key])
            ) {
                $data[$key] = self::arrayMergeMultiple($array1[$key], $array2[$key]);
            } else {
                $data[$key] = isset($array2[$key]) ? $array2[$key] : $array1[$key];
            }
        }
        return $data;
    }

    /**
     * 写入超级管理员
     * Tips：管理员表必须包含字段 admin_name|password|is_super_admin|create_time|update_time
     *
     * @param array $params 管理员admin_user与admin_pass以及数据库配置信息
     * @param string $adminTable 管理员表名称（不包含表前缀）
     * @param string $passType 密码加密类型 custom 自定义的话，直接透传密码字符串
     *                                     md5 采用 md5(md5(原始密码) . 盐) 进行加密
     *                                     default 默认采用PHP password_hash 进行加密
     * @param string $salt 密码盐，双MD5加密特有
     * @return bool
     * @author bai
     */
    public function registerSuperAdmin(
        array $params,
        string $adminTable,
        string $passType = "default",
        string $salt = "StphZj"
    ): bool {
        try {
            $data['prefix']     = isset($params['prefix']) ? $params['prefix'] : self::$tablePrefix;
            $data['admin_user'] = isset($params['admin_user']) ? $params['admin_user'] : self::$adminUser;
            $data['admin_pass'] = isset($params['admin_pass']) ? $params['admin_pass'] : self::$adminPass;
            $adminTable         = $data['prefix'] . $adminTable;
            // 密码加密类型
            switch ($passType) {
                case "custom":
                    $password = $data['admin_pass'];
                    break;
                case "md5":
                    $password = md5(md5($data['admin_pass']) . $salt);
                    break;
                default:
                    $password = password_hash($data['admin_pass'], PASSWORD_DEFAULT);
            }
            // 更新数据库配置
            $configInit = [
                'hostname' => "127.0.0.1",
                'hostport' => "3306",
                'database' => "",
                'username' => "root",
                'password' => "root",
            ];
            $dbConfig   = array_intersect_key(array_merge($configInit, $params), $configInit);
            if (empty($dbConfig['database'])) {
                self::$errorMsg = "请指定数据库database";
                return false;
            }
            $dbConfig['prefix']            = $data['prefix'];
            $databaseConfig                = config("database");
            $connections                   = config("database.connections");
            $mysqlConfig                   = $connections['mysql'];
            $mysqlConfig                   = array_merge($mysqlConfig, $dbConfig);
            $connections['mysql_tmp']      = $mysqlConfig;
            $databaseConfig['connections'] = $connections;
            Config::set($databaseConfig, "database");
            // 连接并验证数据表是否存在
            $dbConnect = Db::connect("mysql_tmp");
            $isTable   = $dbConnect->query("SHOW TABLES LIKE '" . $adminTable . "'");
            if (!empty($isTable)) {
                // 开始写入超级管理员数据
                $whereData = [
                    "admin_name"     => $data['admin_user'],
                    "is_super_admin" => 1,
                ];
                $saveData  = [
                    "admin_name"     => $data['admin_user'],
                    "password"       => $password,
                    "is_super_admin" => 1,
                    "create_time"    => date("Y-m-d H:i:s"),
                    "update_time"    => date("Y-m-d H:i:s"),
                ];
                if (!$dbConnect->table($adminTable)->where($whereData)->find()) {
                    $dbConnect->table($adminTable)->insert($saveData);
                }
            } else {
                self::$errorMsg = "管理员表【{$adminTable}】不存在";
                return false;
            }
        } catch (\Exception $e) {
            self::$errorMsg = "写入超级管理员失败<br>请检查数据库配置！";
            return false;
        }
        return true;
    }

    /**
     * 安装成功的处理
     * @param string $cacheName
     * @param string $redirect
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @author bai
     */
    public function installSuccess(
        string $cacheName = "install-topphp-step3",
        string $redirect = "install/step4"
    ): string {
        // 写入安装锁
        $lockFile    = self::$rootPath . "install.lock";
        $copyright   = $this->getCopyright();
        $version     = !empty(config("topphpInstall.version")) ? 'v' . config("topphpInstall.version") : 'TopPHP';
        $installSign = password_hash("topphp" . date('Y-m-d H:i:s') . $version, PASSWORD_DEFAULT);
        $lockData    = $copyright . PHP_EOL . PHP_EOL . "如需重新安装，请手动删除此文件" . PHP_EOL . "安装时间：" . date('Y-m-d H:i:s');
        $lockData    .= PHP_EOL . "安装版本：" . $version;
        $lockData    .= PHP_EOL . "安装签名：" . $installSign;
        @file_put_contents($lockFile, $lockData);
        // 写入安装成功的缓存
        if ($this->getCache($cacheName)) {
            $cached = $this->getCache($cacheName);
        }
        $cached['install_success']  = true;
        $cached['install_redirect'] = $redirect;
        $this->setCache("install-topphp-step3", $cached);
        // 写入安装成功日志
        $installingLog      = self::$rootPath . "vendor" . self::$ds . "topphp" . self::$ds . "topphp-install"
            . self::$ds . "src" . self::$ds . "data" . self::$ds;
        $project            = config("topphpInstall.project");
        $project            = empty($project) ? "TopPHP" : $project;
        $installingLockData = "[ " . date("Y-m-d H:i:s") . " ] " . $project . " 安装成功！" . PHP_EOL;
        @file_put_contents($installingLog . "topphp-install.log", $installingLockData, FILE_APPEND);
        return $redirect;
    }

    /**
     * 版权
     * @return string
     * @author bai
     */
    private function getCopyright(): string
    {
        return "/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author TopPHP <sleep@kaituocn.com>
 */";
    }
}