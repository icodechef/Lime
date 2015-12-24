<?php

namespace Lime;

/**
 * Cookie
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Cookie
{
    /**
     * cookie 的有效期
     *
     * If set to 0, or omitted, the cookie will expire 
     * at the end of the session (when the browser closes). 
     *
     * @var int
     */
    protected $expire = 0;

    /**
     * cookie 的服务器路径
     * 
     * @var string
     */
    protected $path = '/';

    /**
     *  cookie 的域名
     *
     * @var string
     */
    protected $domain = null;

    /**
     * 是否通过安全的 HTTPS 连接来传输 cookie
     *
     * @var bool
     */
    protected $secure = false;

    /**
     * 利用httponly提升应用程序安全性
     *
     * @var  bool
     */
    protected $httponly = true;

    /**
     * 盐值
     *
     * @var string
     */
    protected $salt = '';

    /**
     * cookie 前缀, 防止 cookie 之间的冲突
     *
     * @var  bool
     */
    protected $prefix = '';

    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        if (!$this->secure) {
            $this->secure = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443 ? true : false;
        }
    }

    /**
     * 对 cookie 进行赋值
     *
     * @param string    $name        cookie 的名称
     * @param string    $value       cookie 的值
     * @param integer   $expire      cookie 的有效期
     * @param string    $path        cookie 的服务器路径
     * @param string    $domain      cookie 的域名
     * @param bool      $secure      规定是否通过安全的 HTTPS 连接来传输 cookie
     * @param bool      $httponly    规定是否利用httponly提升应用程序安全性
     */
    public function set($name, $value, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        if ($expire === null) {
            $expire = $this->expire;
        }

        if ($expire !== 0) {
            $expire += time();
        }

        if ($path === null) {
            $path = $this->path;
        }

        if ($domain === null) {
            $domain = $this->domain;
        }

        if ($secure === null) {
            $secure = $this->secure;
        }

        if ($httponly === null) {
            $httponly = $this->httponly;
        }

        $name  = $this->name($name);
        $value = $this->salt($name, $value).'~'.$value;

        // 当前页面立即生效
        $_COOKIE[$name] = $value;
        
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * 获取 cookie 的值
     *
     * @param string     $name       cookie 的名称
     * @param mixed      $default    默认值
     */
    public function get($name, $default = null)
    {
        $key = $this->name($name);

        if (! isset($_COOKIE[$key])) {
            return $default;
        }

        $cookie = $_COOKIE[$key];

        if (isset($cookie[16]) AND $cookie[16] === '~') {
            list ($hash, $value) = explode('~', $cookie, 2);

            if ( $this->salt($key, $value) === $hash ) {
                return $value;
            }
        }

        $this->delete($name);
        return $default;
    }

    /**
     * 删除cookie
     *
     * @param string $name cookie 的名称
     * @return bool
     */
    public function delete($name)
    {
        $name = $this->name($name);
        unset($_COOKIE[$name]);
        return setcookie($name, null, -86400, $this->path, $this->domain, $this->secure, $this->httponly);
    }

    /**
     * 生成盐值
     * 
     * @param   string $name  cookie 的名称
     * @param   string $value cookie 的值
     * @return  string
     */
    public function salt($name, $value)
    {
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : 'unknown';
        return substr(sha1($agent.$name.$value.$this->salt), 1, 16);
    }

    /**
     * 对 cookie 的名称添加前缀, 防止冲突
     *
     * @param string  $name cookie 的名称
     * @return string
     */
    public function name($name)
    {
        return $this->prefix . $name;
    }
}