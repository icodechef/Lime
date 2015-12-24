<?php

namespace Lime;

/**
 * Route
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Route
{
    /**
     * 路由ID
     *
     * @var int
     */
    protected $id;

    /**
     * 路由名
     *
     * @var null|string
     */
    protected $name;

    /**
     * 路由支持的 HTTP 方法
     *
     * @var string[]
     */
    protected $methods = [];

    /**
     * 路由处理程序
     *
     * @var handler
     */
    protected $handler;

    /**
     * 路由表达式
     *
     * @var string
     */
    protected $pattern;

    /**
     * URL 参数的条件
     *
     * @var array
     */
    protected $conditions = [];

    /**
     * 路由参数
     *
     * @var array
     */
    protected $params = [];

    /**
     * 过滤函数
     *
     * @var callable[]
     */
    protected $filters = [];

    /**
     * 路由唯一ID
     *
     * @var int
     */
    protected static $uniqueId = 0;

    /**
     * 匹配一个 URI 组和捕获的内容
     *
     * @var string
     */
    const REGEX_GROUP = '\(((?:(?>[^()]+)|(?R))*)\)';

    /**
     * <segment>的正则表达式
     *
     * @var string
     */
    const REGEX_KEY = '<([a-zA-Z0-9_]++)>';

    /**
     * <segment>值的正则表达式
     *
     * @var string
     */
    const REGEX_SEGMENT = '[^/.,;?\n]++';
    /**
     * 转义字符
     *
     * @var string
     */
    const REGEX_ESCAPE = '[.\\+*?[^\\]${}=!|]';

    /**
     * Constructor
     *
     * @param  array     $methods
     * @param  string    $pattern
     * @param  callable  $handler
     */
    public function __construct($methods, $pattern, $handler)
    {
        $pattern = trim($pattern, '/');

        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->id = static::$uniqueId++;
    }

    /**
     * 路由匹配
     *
     * @param  string  $requestUri
     * @return bool
     */
    public function matches($requestUri)
    {
        // 必须包含 '/'
        $requestUri = '/' . trim($requestUri, '/');

        $expression = $this->compile();

        if ( !preg_match($expression, $requestUri, $matches) ) {
            return false;
        }

        $params = [];

        foreach ($matches as $key => $value) {
            // Skip unnamed keys
            if (is_int($key)) {
                continue;
            }

            $params[$key] = $value;
        }

        if ($this->filters) {
            foreach ($this->filters as $callback) {
                $return = call_user_func_array($callback, [$params, $requestUri, $this]);

                if ($return === false) {
                    // 中止
                    return false;
                } elseif (is_array($return)) {
                    // 过滤修改了 params
                    $params = $return;
                }
            }
        }

        $this->params = $params;

        return true;
    }

    /**
     * 返回支持的 HTTP 方法
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * 该路由是否支持的 HTTP 方法
     *
     * @param  string  $method
     * @return bool
     */
    public function supportsHttpMethod($method)
    {
        return in_array($method, $this->methods);
    }

    /**
     * 返回处理函数
     *
     * @return handler
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * 获取路由名
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 设置路由名
     *
     * @param  string $name
     * @return \Lime\Response
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 设置路由名
     *
     * @see    setName()
     * @param  string $name
     * @return \Lime\Response
     */
    public function name($name)
    {
        return $this->setName($name);
    }

    /**
     * 获取路由的ID
     *
     * @return int
     */
    public function getRouteId()
    {
        return $this->id;
    }

    /**
     * 获取路由的参数值数组
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * 添加参数
     *
     * @param  string $key
     * @param  mixed  $value
     * @return \Lime\Response
     */
    public function params($key, $value = null)
    {
        if (is_array($key)) {
            $this->params = array_merge($this->params, $key);
        } else {
            $this->params[$key] = $value;
        }

        return $this;
    }

    /**
     * 添加条件
     *
     * @param  array $conditions
     * @return \Lime\Response
     */
    public function conditions(array $conditions)
    {
        $this->conditions = array_merge($this->conditions, $conditions);

        return $this;
    }

    /**
     * 添加过滤函数
     *
     * @param  callable  $callable
     * @return \Lime\Response
     */
    public function filter($callable)
    {
        if ( ! is_callable($callable)) {
            throw new \Exception('Invalid Route::callback specified');
        }

        $this->filters[] = $callable;
        return $this;
    }

    /**
     * 编制路由表达式
     *
     * @return string
     */
    protected function compile()
    {
        // 完整的表达式必须包含 '/'
        $pattern = '/' . ltrim($this->pattern, '/');

        // 转义
        $expression = preg_replace('#'.self::REGEX_ESCAPE.'#', '\\\\$0', $pattern);

        // 处理可选部分
        if (strpos($expression, '(') !== false) {
            $expression = str_replace(array('(', ')'), array('(?:', ')?'), $expression);
        }

        // 替换规则中的变量
        $expression = str_replace(array('<', '>'), array('(?P<', '>'.self::REGEX_SEGMENT.')'), $expression);

        if ($this->conditions) {
            $search = $replace = array();
            foreach ($this->conditions as $key => $value)
            {
                $search[]  = "<$key>".self::REGEX_SEGMENT;
                $replace[] = "<$key>$value";
            }

            $expression = str_replace($search, $replace, $expression);
        }

        return '#^'.$expression.'$#uD';
    }

    /**
     * 调度路由处理函数
     *
     * @return mixed
     */
    public function dispatch()
    {
        if (is_object($this->handler) && $this->handler instanceof \Closure) {
            /* 匿名函数 */
            return call_user_func_array($this->handler, array_values($this->params));
        } elseif ( is_string($this->handler) && strpos($this->handler, '@') !== false ) {
            /* 路由器 */
            list($class, $method) = explode('@', $this->handler);

            $instance = new $class();

            if (method_exists($instance, $method)) {
                return call_user_func_array([$instance, $method], array_values($this->params));
            } else {
                throw new \Exception("{$class}::{$method} undefind.");
            }
        }

        return '';
    }
}