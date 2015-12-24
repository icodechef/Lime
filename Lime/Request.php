<?php

namespace Lime;

/**
 * HTTP 请求
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Request
{
    /**
     * 请求方法
     *
     * @var string
     */
    protected $method;

    /**
     * 请求的 URI
     *
     * @var string
     */
    protected $requestUri;

    /**
     * PATH_INFO
     *
     * @var string
     */
    protected $pathInfo;

    /**
     * 基地址
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * 基路径
     *
     * @var string
     */
    protected $basePath;

    /**
     * 当前过滤器
     *
     * @var array
     */
    protected $filters = [];

    /**
     * 匹配的路由参数
     *
     * @var array
     */
    protected $params = [];

    /**
     * 获取请求的方法
     *
     * @return string
     */
    public function method()
    {
        if (is_null($this->method)) {
            $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';

            if ($method == 'POST') {
                if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                    $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
                } else {
                    $method = isset($_POST['_method']) ? strtoupper($_POST['_method']) : $method;
                }
            }

            $this->method = $method;
        }

        return $this->method;
    }

    /**
     * 获取请求的Scheme
     *
     * @return string
     */
    public function scheme()
    {
        return (isset($_SERVER['HTTPS']) 
                && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] === true))
                ? 'https'
                : 'http';
    }

    /**
     * 获取请求的Http host
     *
     * @return string
     */
    public function host()
    {
        return sprintf('%s:%s', $this->hostname(), $this->port());
    }

    /**
     * 获取请求的 hostname(不包含无端口)
     *
     * @return string
     */
    public function hostname()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            if (strpos($host, ':') !== false) {
                list($host, $port) = explode(':', $_SERVER['HTTP_HOST']);
            }
            return $host;
        }

        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
    }

    /**
     * 获取请求的端口
     *
     * @return string
     */
    public function port()
    {
        return isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '';
    }

    /**
     * 获取请求的 path 部分
     *
     * @return string
     */
    public function pathname()
    {
        return $this->basePath();
    }

    /**
     * 获取请求的执行文件
     *
     * @return string
     */
    public function scriptName()
    {
        return isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
    }

    /**
     * 获取请求的查询部分
     *
     * @return string
     */
    public function query()
    {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    }

    /**
     * 获取 Header
     *
     * @return string
     */
    public function header($header, $default = false) {
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        if (isset($_SERVER[$temp])) {
            return $_SERVER[$temp];
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers[$header])) {
                return $headers[$header];
            }
            $header = strtolower($header);
            foreach ($headers as $key => $value) {
                if (strtolower($key) == $header) {
                    return $value;
                }
            }
        }

        return $default;
    }

    /**
     * 获取请求的 $_SERVER
     *
     * @return string
     */
    public function server($key, $default = null)
    {
        return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
    }

    /**
     * 是否一个GET方法请求
     *
     * @return boolean
     */
    public function isGet()
    {
        return $this->method() === 'GET' ? true : false;
    }

    /**
     * 是否一个POST方法请求
     *
     * @return boolean
     */
    public function isPost()
    {
        return $this->method() === 'POST' ? true : false;
    }

    /**
     * 是否一个PUT方法请求
     *
     * @return boolean
     */
    public function isPut() {
        return $this->method() === 'PUT' ? true : false;
    }

    /**
     * 是否一个DELETE方法请求
     *
     * @return boolean
     */
    public function isDelete()
    {
        return $this->method() === 'DELETE' ? true : false;
    }

    /**
     * 是否一个PATCH方法请求
     *
     * @return boolean
     */
    public function isPatch()
    {
        return $this->method() === 'PATCH' ? true : false;
    }

    /**
     * 是否一个HEAD方法请求
     *
     * @return boolean
     */
    public function isHead()
    {
        return $this->method() === 'HEAD' ? true : false;
    }

    /**
     * 是否一个OPTIONS方法请求
     *
     * @return boolean
     */
    public function isOptions()
    {
        return $this->method() === 'OPTIONS' ? true : false;
    }

    /**
     * 是否一个 XMLHttpRequest 请求
     *
     * @return boolean
     */
    public function isAjax()
    {
        return ($this->header('X_REQUESTED_WITH') === 'XMLHttpRequest');
    }

    /**
     * 获取请求来源地址
     *
     * @return string
     */
    public function referer($default = null)
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $default;
    }

    /**
     * 获取客户端的IP地址
     *
     * @return string
     */
    public function ip()
    {
        $keys = array('X_FORWARDED_FOR', 'HTTP_X_FORWARDED_FOR', 'CLIENT_IP', 'REMOTE_ADDR');

        foreach ($keys as $key) {
            if (isset($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        return '';
    }

    /**
     * 添加过滤函数
     *
     * @return \Lime\Request
     */
    public function filter()
    {
        $filters = func_get_args();

        foreach ($filters as $callable) {
            if (is_callable($callable)) {
                $this->filters[] = $callable;
            }
        }

        return $this;
    }

    /**
     * 过滤
     *
     * @param  mixed $value
     * @return mixed
     */
    protected function sanitize($value)
    {
        foreach ($this->filters as $callable) {
            $value = is_array($value) ? array_map($callable, $value) :
            call_user_func($callable, $value);
        }

        // 清除
        $this->filters = array();

        return $value;
    }

    /**
     * 获取一个请求变量
     *
     * 首先查找 POST 变量，最后才查找 GET 变量
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        if (is_null($key)) {
            return array_merge($this->get(), $this->post());
        }

        $value = $this->post($key);
        $value = $value ? $value : $this->get($key, $default);

        return $this->sanitize($value);
    }

    /**
     * 获取一个 GET 变量
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_GET;
        }

        $value = isset( $_GET[$key] ) ? $_GET[$key] : $default;

        return $this->sanitize($value);
    }

    /**
     * 获取一个 POST 变量
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function post($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_POST;
        }

        $value = isset($_POST[$key]) ? $_POST[$key] : $default;

        return $this->sanitize($value);
    }

    /**
     * 获取匹配的路由的参数
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function params($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->params;
        }

        $value = isset($this->params[$key]) ? $this->params[$key] : $default;

        return $this->sanitize($value);
    }

    /**
     * 添加匹配路由的参数
     *
     * @param array $params
     */
    public function setParams($params = [])
    {
        $this->params = $params;
    }

    /**
     * 获取请求的 PATH_INFO
     *
     * @return string
     */
    public function pathInfo()
    {
        if (is_null($this->pathInfo)) {
            $this->pathInfo = $this->detectPathInfo();
        }

        return $this->pathInfo;
    }

    /**
     * 获取 URI
     *
     * @return string
     */
    public function uri()
    {
        $pathInfo = $this->pathInfo();
        return $pathInfo ? $pathInfo : '/';
    }

    /**
     * 获取请求的 URI
     *
     * @return string
     */
    public function url()
    {
        if (is_null($this->requestUri)) {
            $this->requestUri = $this->detectUrl();
        }

        return $this->requestUri;
    }

    /**
     * 获取基地址
     * 
     * 自动检测从请求环境的基本URL
     * 采用了多种标准, 以检测请求的基本URL
     *
     * <code>
     * /site/demo/index.php
     * </code>
     *
     * @param boolean $raw 是否编码
     * @return string
     */
    public function baseUrl($raw = false)
    {
        if (is_null($this->baseUrl)) {
            $this->baseUrl = rtrim($this->detectBaseUrl(), '/');
        }

        return $raw == false ? urldecode($this->baseUrl) : $this->baseUrl;
    }

    /**
     * 获取基路径, 不包含请求文件名
     *
     * <code>
     * /site/demo/
     * </code>
     *
     * @return string
     */
    public function basePath()
    {
        if (is_null($this->basePath)) {
            $this->basePath = rtrim($this->detectBasePath(), '/');
        }

        return $this->basePath;
    }

    /**
     * 检测 baseURL 和查询字符串之间的 PATH_INFO
     *
     * @return string
     */
    protected function detectPathInfo()
    {
        // 如果已经包含 PATH_INFO
        if ( ! empty($_SERVER['PATH_INFO'])) {
            return $_SERVER['PATH_INFO'];
        }

        if ( '/' === ($requestUri = $this->url()) ) {
            return '';
        }

        $baseUrl = $this->baseUrl();
        $baseUrlEncoded = urlencode($baseUrl);

        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        
        if (! empty($baseUrl)) {
            if ( strpos($requestUri, $baseUrl) === 0 ) {
                $pathInfo = substr($requestUri, strlen($baseUrl));
            } elseif ( strpos($requestUri, $baseUrlEncoded) === 0 ) {
                $pathInfo = substr($requestUri, strlen($baseUrlEncoded));
            } else {
                $pathInfo = $requestUri;
            }
        } else {
            $pathInfo = $requestUri;
        }

        return $pathInfo;
    }

    /**
     * 测出请求的URI
     *
     * @return string
     */
    protected function detectUrl()
    {
        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) { 
            // 带微软重写模块的IIS
            $requestUri = $_SERVER['HTTP_X_ORIGINAL_URL'];
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) { 
            // 带ISAPI_Rewrite的IIS
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (
            isset($_SERVER['IIS_WasUrlRewritten'])
            && $_SERVER['IIS_WasUrlRewritten'] == '1'
            && isset($_SERVER['UNENCODED_URL'])
            && $_SERVER['UNENCODED_URL'] != ''
            ) {
            // URL重写的IIS7：确保我们得到的未编码的URL(双斜杠的问题)
            $requestUri = $_SERVER['UNENCODED_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            // 只使用URL路径, 不包含scheme、主机[和端口]或者http代理
            if ($requestUri) {
                $requestUri = preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            $requestUri = '/';
        }

        return $requestUri;
    }

    /**
     * 自动检测从请求环境的基本 URL
     * 采用了多种标准, 以检测请求的基本 URL
     *
     * @return string
     */
    protected function detectBaseUrl()
    {
        $baseUrl        = '';
        $fileName       = isset($_SERVER['SCRIPT_FILENAME']) 
                          ? $_SERVER['SCRIPT_FILENAME'] 
                          : '';
        $scriptName     = isset($_SERVER['SCRIPT_NAME']) 
                          ? $_SERVER['SCRIPT_NAME'] 
                          : null;
        $phpSelf        = isset($_SERVER['PHP_SELF']) 
                          ? $_SERVER['PHP_SELF']
                          : null;
        $origScriptName = isset($_SERVER['ORIG_SCRIPT_NAME']) 
                          ? $_SERVER['ORIG_SCRIPT_NAME']
                          : null;

        if ($scriptName !== null && basename($scriptName) === $fileName) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $fileName) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $fileName) {
            $baseUrl = $origScriptName;
        } else {
            $baseUrl  = '/';
            $basename = basename($fileName);
            if ($basename) {
                $path     = ($phpSelf ? trim($phpSelf, '/') : '');
                $baseUrl .= substr($path, 0, strpos($path, $basename)) . $basename;
            }
        }

        // 请求的URI
        $requestUri = $this->url();

        // 与请求的URI一样?
        if (0 === strpos($requestUri, $baseUrl)) {
            return $baseUrl;
        }

        $baseDir = str_replace('\\', '/', dirname($baseUrl));
        if (0 === strpos($requestUri, $baseDir)) {
            return $baseDir;
        }

        $basename = basename($baseUrl);

        if ( empty($basename) ) {
            return '';
        }

        if (strlen($requestUri) >= strlen($baseUrl)
            && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)
        ) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return $baseUrl;
    }

    /**
     * 自动检测请求的基本路径
     * 使用不同的标准来确定该请求的基本路径。
     *
     * @return string
     */
    protected function detectBasePath()
    {
        $fileName = isset($_SERVER['SCRIPT_FILENAME']) 
                    ? basename($_SERVER['SCRIPT_FILENAME']) 
                    : '';
        $baseUrl  = $this->baseUrl();

        if ($baseUrl === '') {
            return '';
        }

        if (basename($baseUrl) === $fileName) {
            return str_replace('\\', '/', dirname($baseUrl));
        }

        return $baseUrl;
    }
}