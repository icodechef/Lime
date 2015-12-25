<?php

namespace Lime;

/**
 * Router
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Router
{
    /**
     * 路由对象表
     *
     * @var array
     */
    protected $routes = [];

    /**
     * 匹配的路由对象
     *
     * @var \Lime\Route
     */
    protected $matchedRoute;

    /**
     * 命名路由
     *
     * @var array
     */
    protected $namedRoutes;

    /**
     * 路由分组
     * 
     * @var array
     */
    protected $routeGroups = [];

    /**
     * 是否在路由分组中
     * 
     * @var boolean
     */
    protected $inGroup = false;

    /**
     * 当前路由分组中的路由 ID
     * 
     * @var array
     */
    protected $routeIdGroups = [];

    /**
     * 路由分组层级
     * 
     * @var int
     */
    protected $groupLevel = 0;

    /**
     * 添加路由对象
     *
     * @param  string|array $methods  支持的 HTTP 方法
     * @param  string       $pattern
     * @param  handler      $handler
     * @return \Lime\Route
     */
    public function map($methods, $pattern, $handler)
    {
        if (! is_string($pattern)) {
            throw new \Exception('Route pattern must be a string');
        }

        if (is_string($methods)) {
            $methods = [$methods];
        }

        // HTTP 方法统一为大写
        $methods = array_map('strtoupper', $methods);

        // 处理分组, 相当于添加 pattern 的前缀
        $groupPattern = $this->processGroups();

        $pattern = '/' . ltrim($pattern, '/');

        /* 新建一个路由表象 */
        $route = new Route($methods, $groupPattern . $pattern, $handler);

        if ($this->inGroup) {
            $this->routeIdGroups[$this->groupLevel][] = $route->getRouteId();
        }

        return $this->routes[$route->getRouteId()] = $route;
    }

    /**
     * 组合路由分组
     *
     * @return string
     */
    public function processGroups()
    {
        $pattern = "";
        foreach ($this->routeGroups as $group) {
            $p = array_shift($group);
            $pattern .= $p;
        }

        return $pattern;
    }

    /**
     * 压入路由分组
     *
     * @param  string  $pattern
     */
    public function pushGroup($pattern)
    {
        array_push($this->routeGroups, [$pattern]);
    }

    /**
     * 弹出路由分组
     *
     * @param  string  $pattern
     */
    public function popGroup()
    {
        return array_pop($this->routeGroups);
    }

    /**
     * 压入路由分组
     *
     * @param  handle $handle
     * @param  array  $filters
     */
    public function mount($handle, $filters = [])
    {
        $this->inGroup = true;
        $this->groupLevel++;

        if (is_callable($handle)) {
            $handle();
        }

        foreach ($this->routeIdGroups[$this->groupLevel] as $id) {
            foreach ($filters as $filter) {
                $this->routes[$id]->filter($filter);
            }
        }

        $this->groupLevel--;
        $this->inGroup = false;
    }

    /**
     * 路由处理
     *
     * @param  string  $httpMethod
     * @param  string  $requestUri
     * @return \Lime\Route
     */
    public function handle($httpMethod, $requestUri)
    {
        foreach ($this->routes as $route) {
            if (! $route->supportsHttpMethod($httpMethod)) {
                continue;
            }

            if ( $route->matches($requestUri) ) {
                $this->matchedRoute = $route;
                break;
            }
        }

        return $this->matchedRoute;
    }

    /**
     * 返回和 URI 匹配的路由
     *
     * @return \Lime\Route
     */
    public function getMatchedRoute()
    {
        return $this->matchedRoute;
    }

    /**
     * 生成 URL (根据路由名称)
     *
     * @param  string  $name
     * @param  array   $params
     * @return string
     */
    public function urlFor($name, $data)
    {
        $this->namedRoutes();
        if (! isset($this->namedRoutes[$name])) {
            throw new \Exception('The requested route does not exist : ' . $name);
        }

        $route = $this->namedRoutes[$name];

        if ($data) {
            $data = array_map('rawurlencode', $data);
            $data = str_replace(array('%2F', '%5C'), array('/', '\\'), $data);
        }

        $pattern = $route->getPattern();

        $replacements = [];

        preg_match('#\(/<([a-zA-Z0-9_]++)>\)#', $pattern, $matches);

        if ($matches) {
            $replacements[$matches[0]] = isset($data[$matches[1]]) ? '/' . $data[$matches[1]] : '';
        }

        foreach ($data as $key => $val) {
            $replacements["<$key>"] = $val;
        }

        $pattern = '/' . strtr($pattern, $replacements);

        return preg_replace('#\(/?:.+\)|\(|\)|\\\\#', '', $pattern);
    }

    /**
     * 检测命名路由
     */
    public function namedRoutes()
    {
        if (is_null($this->namedRoutes)) {
            $this->namedRoutes = [];
            foreach ($this->routes as $route) {
                if (($name = $route->getName()) !== null) {
                    $this->namedRoutes[(string) $name] = $route;
                }
            }
        }
    }
}