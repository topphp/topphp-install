<?php
/**
 * @copyright 凯拓软件 [临渊羡鱼不如退而结网,凯拓与你一同成长]
 * @author bai <sleep@kaituocn.com>
 */

/**
 * Description - IndexCheck.php
 *
 * Install验证器
 */

namespace app\install\validate;

use think\Validate;

class IndexCheck extends Validate
{
    // 注意：全部Topphp验证器文件名统一以Check结尾，否则不会生效

    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        "host"       => ['regex' => '/^([0-9A-Za-z.\-\_]+)$/'],
        "port"       => ['number'],
        "select"     => ['number'],
        "hostname"   => ['require', 'regex' => '/^([0-9A-Za-z.\-\_]+)$/'],
        "hostport"   => ['require', 'number'],
        "database"   => ['require', 'regex' => '/^([a-zA-Z][0-9A-Za-z\-\_]+){0,}$/'],
        "username"   => ['require', 'regex' => '/^([a-zA-Z][0-9A-Za-z\-\_]+){0,}$/'],
        "password"   => ['require'],
        "prefix"     => ['regex' => '/^([a-zA-Z\-\_]+)$/'],
        "admin_user" => ['regex' => '/^(?!_)([a-zA-Z\_]+){4,}$/'],
        "admin_pass" => ['regex' => '/^(?:\d+|[a-zA-Z]+|[!@#$%^&*]+){6,20}$/'],
    ];

    /**
     * 定义错误信息【1、使用TopPHP自带的Check验证器中间件支持数组定义（骨架已自动集成）；2、使用Tp6的注解验证器不支持数组形式定义】
     * 格式：'字段名.规则名'    =>    '错误信息'
     * 数组定义示例："username" => ['code' => 40000, 'message' => '请填写用户名'] 返回 {"code":40000,"message":"请填写用户名","data":[]}
     *
     * 注意：因为是严格模式，错误信息内容被限定为字符串，传int型会报错，数组形式的code码允许是int型
     *
     * @var array
     */
    protected $message = [
        "host"       => ['code' => 40000, 'message' => '请填写正确的host'],
        "port"       => ['code' => 40000, 'message' => '请填写正确的port'],
        "select"     => ['code' => 40000, 'message' => '请填写正确的select'],
        "hostname"   => ['code' => 40000, 'message' => '服务器地址错误'],
        "hostport"   => ['code' => 40000, 'message' => '数据库端口错误'],
        "database"   => ['code' => 40000, 'message' => '数据库名称错误'],
        "username"   => ['code' => 40000, 'message' => '数据库账号错误'],
        "password"   => ['code' => 40000, 'message' => '数据库密码不为空'],
        "prefix"     => ['code' => 40000, 'message' => '数据表前缀错误'],
        "admin_user" => ['code' => 40000, 'message' => '管理员账号名错误'],
        "admin_pass" => ['code' => 40000, 'message' => '管理员账号密码错误'],
    ];

    /**
     * 定义验证场景【key全小写，单独验证某个字段的写法：操作方法名(actionName)@字段名 => ['字段验证规则key']，注意 @ 后字段名区分大小写】
     * 格式：【非多层级控制器】1、'操作方法名(actionName)'    =>    ['字段1','字段2'...]
     *       【多层级控制器】2、'层级名(layered).操作方法名(actionName)'    =>    ['字段1','字段2'...]
     * 单独验证：配置好单独验证的场景【"index@username"=>['username']】后，直接在控制器调用 checkOneRequestParam("username","post"); 方法即可
     *
     * @var array
     */
    protected $scene = [
        'index'         => [''],
        'step2'         => [''],
        'step3'         => [''],
        'step4'         => [''],
        'envTips'       => [''],
        'dbConnectTest' => [
            'hostname',
            'hostport',
            'database',
            'username',
            'password',
        ],
        'start'         => [
            'host',
            'port',
            'select',
            'hostname',
            'hostport',
            'database',
            'username',
            'password',
            'prefix',
            'admin_user',
            'admin_pass'
        ],
    ];
}
