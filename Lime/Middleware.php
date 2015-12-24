<?php

namespace Lime;

/**
 * 中间件
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Middleware
{
    /**
     * SplQueue
     *
     * @var \SplQueue
     */
    protected $queue;
    
    /**
     * 是否要停止传播
     *
     * @var bool
     */
    protected $propagation = false;

    /**
     * 响应
     *
     * @var mixed
     */
    protected $response;

    /**
     * 名称
     *
     * @var string
     */
    protected $name;

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        if ($name) {
            $this->setName($name);
        }

        $this->queue = new \SplQueue();
    }

    public function bindToThis($callbale)
    {
        return $callbale->bindTo($this, $this);
    }

    /**
     * 压入 middleware 
     *
     * @param  callbale  $callbale 一般为回调函数
     * @return \Lime\Middleware
     */
    public function push($callbale)
    {
        if ($callbale instanceof \Closure) {
            $callbale = $this->bindToThis($callbale);
        }
        
        $this->queue->push($callbale);
        return $this;
    }

    /**
     * 弹出 middleware
     *
     * @return callable
     */
    public function pop()
    {
        return $this->queue->pop();
    }

    /**
     * 在开头添加 middleware
     *
     * @param  callbale  $callbale 一般为回调函数
     * @return \Lime\Middleware
     */
    public function unshift($callbale)
    {
        if ($callbale instanceof \Closure) {
            $callbale = $this->bindToThis($callbale);
        }
        
        $this->queue->unshift($callbale);
        return $this;
    }

    /**
     * 从开头移出 middleware
     *
     * @return callable
     */
    public function shift()
    {
        return $this->queue->shift();
    }

    /**
     * 调用 middleware
     *
     * @return mixed
     */
    public function call()
    {
        $this->queue->rewind();

        while($this->queue->valid()) {
            if ($this->getPropagation()) {
                break;
            }

            $callbale = $this->queue->current();

            $response = call_user_func_array($callbale, func_get_args());

            if ($response === false) {
                break;
            } elseif (!empty($response)) {
                $this->response = $response;
            }

            $this->queue->next();
        }

        return $this->response;
    }

    /**
     * 是否停止事件传播
     *
     * @param  bool $flag
     */
    public function stopPropagation($flag = true)
    {
        $this->propagation = (bool) $flag;
    }

    /**
     * 设置名称
     *
     * @param  string $name
     * @return \Lime\Middleware
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * 获取名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 返回是否停止事件传播
     *
     * @param  bool
     */
    public function getPropagation()
    {
        return $this->propagation;
    }

    /**
     * 返回响应
     *
     * @return mixed
     */
    public function __toString()
    {
        return $this->response;
    }
}