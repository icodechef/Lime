<?php

namespace Lime\Middleware;

/**
 * 中间件 管理
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Map
{
    /**
     * 中间件
     * 
     * @var middleware[]
     */
    protected $middleware = [];

    /**
     * 添加指定名称的 middleware
     *
     * @param  string $name
     * @param  callable $callable
     * @return \Lime\Middleware\Map
     */
    public function add($name, $callable)
    {
        $pseudo = 'middle';

        if (($len = strpos($name, ':')) !== false) {
            list($name, $pseudo) = explode(':', $name);
        }

        if (!isset($this->middleware[$name])) {
            $this->middleware[$name] = [];
        }

        $this->middleware[$name][$pseudo][] = $callable;

        return $this;
    }

    /**
     * 获取指定名称的 middleware
     *
     * @param  string $name
     * @return array
     */
    public function get($name)
    {
        return $this->has($name) ? $this->middleware[$name] : null;
    }

    /**
     * 是否存在指定名称的 middleware
     *
     * @param  string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->middleware[$name]);
    }

    /**
     * 移除指定名称的 middleware
     *
     * @param  string $name
     */
    public function remove($name)
    {
        unset($this->$middleware[$name]);
    }

    /**
     * 清空 middleware
     *
     * @param  string $name
     */
    public function clean()
    {
        $this->middleware = [];
    }

    /**
     * 获取所有 middleware
     *
     * @return array
     */
    public function all()
    {
        return $this->middleware;
    }

    /**
     * 生成 middleware 队列
     *
     * @param  string $name
     * @param  callable $callable
     * @return \Lime\Middleware
     */
    public function make($name, $callable = null)
    {
        if (is_callable($callable)) {
            /* 主回调函数 */
            if (isset($this->middleware[$name]['middle'])) {
                array_unshift($this->middleware[$name]['middle'], $callable);
            } else {
                $this->middleware[$name]['middle'] = [$callable];
            }
        }

        $middleware = new \Lime\Middleware($name);

        foreach (['before', 'middle', 'after'] as $pseudo) {
            if (!isset($this->middleware[$name][$pseudo])) {
                continue;
            }

            foreach ($this->middleware[$name][$pseudo] as $callbale) {
                $middleware->push($callbale);
            }
        }

        return $middleware;   
    }
}