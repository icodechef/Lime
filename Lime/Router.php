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

        $pattern = '/' . ltrim($pattern, '/');

        /* 新建一个路由表象 */
        $route = new Route($methods, $pattern, $handler);

        return $this->routes[$route->getRouteId()] = $route;
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
}