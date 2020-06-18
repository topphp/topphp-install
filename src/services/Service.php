<?php
/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author bai <sleep@kaituocn.com>
 */

namespace Topphp\TopphpInstall\services;


use lib\SendMsg;
use think\Request;
use think\facade\Event;

class Service extends \think\Service
{
    public function boot()
    {
        Event::listen('RouteLoaded', function (Request $request) {
            $topphpLockContent = @file_get_contents($this->app->getRootPath() . "install.lock");
            if (!file_exists($this->app->getRootPath() . "install.lock") ||
                (file_exists($this->app->getRootPath() . "install.lock") && !preg_match("/topphp/",
                        strtolower($topphpLockContent)))) {
                $appName              = $this->app->get("http")->getName();
                $userAgent            = strtolower($request->server("HTTP_USER_AGENT"));
                $requestUri           = $request->server("REQUEST_URI");
                $topphpInstallLogPath = $this->app->getRootPath() . "vendor" . DIRECTORY_SEPARATOR
                    . "topphp" . DIRECTORY_SEPARATOR . "topphp-install" . DIRECTORY_SEPARATOR . "src"
                    . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR;
                $topphpAllowUri       = [
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
                $isRedirect           = false;
                if (!empty($appName) && $appName !== "install") {
                    $isRedirect = true;
                } elseif (empty($appName) && !empty($requestUri) && strpos($userAgent, "mozilla") !== false) {
                    $topphpUrlSuffix = config("route.url_html_suffix");
                    if (!empty($topphpUrlSuffix)) {
                        if (preg_match("/\|/", $topphpUrlSuffix)) {
                            $topphpUri = @explode(".", $requestUri)[0];
                        } else {
                            $topphpUri = @explode("." . $topphpUrlSuffix, $requestUri)[0];
                        }
                    } else {
                        $topphpUri = $requestUri;
                    }
                    $topphpInstallingData = [];
                    if (file_exists($topphpInstallLogPath . "topphp-installing.lock")) {
                        $topphpInstallingData = @file_get_contents($topphpInstallLogPath . "topphp-installing.lock");
                        $topphpInstallingData = @explode("_", $topphpInstallingData);
                    }
                    if (!in_array($topphpUri, $topphpAllowUri)) {
                        if (!empty($topphpInstallingData[0]) && preg_match("/" . $topphpInstallingData[0] . "/",
                                strtolower($topphpUri)) && (int)$topphpInstallingData[1] + 3600 >= time()) {
                            // install 应用调用请求忽略重定向跳转
                        } else {
                            $isRedirect = true;
                        }
                    }
                }
                if ($isRedirect) {
                    if (strpos($userAgent, "mozilla") !== false) {
                        if (preg_match("/Swoole/", $request->server("SERVER_SOFTWARE"))) {
                            if ($request->isAjax() || $request->isPjax()) {
                                SendMsg::jsonThrow("您还未安装系统，请前去安装：" . $request->domain() . "/install");
                            } else {
                                SendMsg::jsonJump($request->domain() . "/install");
                            }
                        } elseif ($request->isAjax() || $request->isPjax()) {
                            SendMsg::jsonThrow("您还未安装系统，请前去安装：" . $request->domain() . "/install");
                        } else {
                            echo "<SCRIPT LANGUAGE='javascript'>";
                            echo "location.href='/install'";
                            echo "</SCRIPT>";
                        }
                    } else {
                        SendMsg::jsonThrow("您还未安装系统，请前去安装：" . $request->domain() . "/install");
                    }
                }
            }
        });
    }
}