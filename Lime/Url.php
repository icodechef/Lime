<?php

namespace Lime;

/**
 * Url
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Url
{
    /**
     * URL 的入口脚本之前的部分
     *
     * @var string
     */
    protected $site;

    /**
     * URL 的 host info 之后, 入口脚本之前的部分
     *
     * @var string
     */
    protected $base;

    /**
     * 请求对象
     *
     * @var \Lime\Request
     */
    protected $request;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->request = new \Lime\Request();
        $this->detectSite();
        $this->detectBase();
    }

    /**
     * 绝对 URL
     *
     * @param  boolean $index 是否显示脚本文件
     * @return string
     */
    public function site($index = false)
    {
        $url = $this->site;

        if ($index === true) {
            $url .= '/' . $this->request->scriptName();
        } else if ( $index ) {
            $url .= '/' . $index;
        }

        return $url;
    }

    /**
     * 相对 URL
     *
     * @param  boolean $index 是否显示脚本文件
     * @return string 
     */
    public function base($index = false)
    {
        $url = $this->base;

        if ($index === true) {
            $url .= '/' . $this->request->scriptName();
        } else if ( $index ) {
            $url .= '/' . $index;
        }

        return $url;
    }

    /**
     * 检测绝对路径, 入口脚本之前的部分
     *
     * @return void 
     */
    protected function detectSite()
    {
        $this->site = $this->request->scheme() . '://' . $this->request->hostname() . $this->request->basePath();
    }

    /**
     * 检测相对路径, host info 之后, 入口脚本之前的部分
     *
     * @return void 
     */
    protected function detectBase()
    {
        $this->base = $this->request->basePath();
    }
}