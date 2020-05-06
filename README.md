# topphp-install

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

>这是一个一键安装工具，旨在为开发者提供一键安装web项目的可视化安装程序解决方案。

## 组件结构


```
config/     
src/     
vendor/
```


## 安装

``` bash
    composer require topphp/topphp-install
```

## 用法

```php
    二次开发使用说明：
        
        1.考虑到二次开发成本和定制化依赖程度原因，topphp-install组件默认采用非前后端分离形式开发（think-view视图形式）。
        
        2.直接composer安装完以后，topphp骨架会自动多出install应用模块，使用浏览器访问任何应用，
          系统都会直接先跳转到安装程序应用。
        
        3.如果您在骨架app目录下已存在install目录，topphp-install组件会覆盖此目录，请提前做好此应用目录前后端文件的备份，
          安装还会覆盖对应的静态文件目录（public/static/install）。
        
        4.topphp-install组件为您提供TopInstallServer服务类，采用单例模式，
          您可以直接静态实例化TopInstallServer::instance();
        
        5.如果采用前后端分离的形式，安装服务类TopInstallServer::instance()提供installRedirectState()方法，
          用于判断当前请求是否需要跳转到install安装应用的判断，true为是，false为否，开发者需要根据此方法自行实现分离式跳转。
        
        6.如您的定制化需求不那么明显，可沿用topphp-install组件的视图风格，我们提供配置参数定制化解决方案：
            Tips：您不需要关心install应用下的太多目录代码，只需要关心以下几个参数配置文件，和视图&静态文件
            a.骨架根目录config下的topphpInstall.php配置文件
                -- 提供页面版权配置（建议默认）
                -- 底部版权LOGO跳转链接配置
                -- 项目名称配置（安装视图全局将会替换成您的项目名称，如TopPHP）
                -- 安装页面title内容配置
                -- 以及环境检测相关配置（开发者可根据install应用源码以及安装页面展示结合自己的项目需求进行动态配置）
            b.安装应用install下的data数据配置模板文件
                -- 以.tpl结尾的模板配置文件建议原样保持
                -- 以xxxTmpl.html的html替换模板，可以添加html内容
                -- ProtocolContentTmpl.html文件为安装首页index页面的协议html内容，
                   您可自定义软件安装协议（此文件一旦不为空，index页面内容将会以此html内容渲染）
                -- InstallSuccessContentTmpl.html文件为安装成功后的step4页面html内容
                   （此文件一旦不为空，step4页面内容将会以此html内容渲染）
            c.安装应用install下的sql文件目录
                -- 您可以重新编辑topphp_base.sql文件为您想要安装的数据库SQL语句
                -- 还可以直接新增您自己导出的.sql文件，并在install应用的index控制器initialize构造方法中
                   将topphp_base.sql改为您要安装的.sql文件名，安装程序将自动安装该sql文件
            d.特别注意，如果是您自定义的sql文件，您还需要包含一张管理员表，
              管理员表必须包含字段 admin_name|password|is_super_admin|create_time|update_time
              如无创建后台管理员的业务需求，您需要修改index控制器的start安装数据库方法，将安装超级管理员的业务代码注释掉
            e.视图页面的左上角LOGO和安装页面背景图可通过替换public/static/install/images中对应的图片进行实现
            f.如您对于软件安装协议也没特殊需求的话，还可以通过安装应用install/enum枚举类，
              直接沿用topphp的安装协议风格配置【协议内容配置】
              
        7.如您对于安装程序定制化需求明显，您可参看install应用源码注释进行二次开发，
          组件安装服务类TopInstallServer提供定制化技术支持     
```

## 修改日志

有关最近更改的内容的详细信息，请参阅更改日志（[CHANGELOG](CHANGELOG.md)）。


## 贡献

详情请参阅贡献（[CONTRIBUTING](CONTRIBUTING.md)）和行为准则（[CODE_OF_CONDUCT](CODE_OF_CONDUCT.md)）。


## 安全

如果您发现任何与安全相关的问题，请发送电子邮件至sleep@kaitoocn.com，而不要使用问题跟踪器。

## 信用

- [topphp][link-author]
- [All Contributors][link-contributors]

## 许可证

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/topphp/component-builder.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/topphp/component-builder/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/topphp/component-builder.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/topphp/component-builder.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/topphp/component-builder.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/topphp/component-builder
[link-travis]: https://travis-ci.org/topphp/component-builder
[link-scrutinizer]: https://scrutinizer-ci.com/g/topphp/component-builder/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/topphp/component-builder
[link-downloads]: https://packagist.org/packages/topphp/component-builder
[link-author]: https://github.com/topphp
[link-contributors]: ../../contributors
