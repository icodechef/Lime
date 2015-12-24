<?php

namespace Lime;

/**
 * 视图
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class View implements \ArrayAccess
{
    /**
     * 视图数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 视图目录
     *
     * @var string
     */
    protected $path;

    /**
     * 视图渲染的嵌套级别
     *
     * @var string
     */
    protected $offset = 0;

    /**
     * 视图继承中的父模板
     *
     * @var array
     */
    protected $extends = [];

    /**
     * 视图片段
     *
     * @var array
     */
    protected $sections = [];

    /**
     * 视图片段名
     *
     * @var array
     */
    protected $sectionStacks = [];

    /**
     * 未发现的视图片段
     *
     * @var array
     */
    protected $sectionsNotFound = [];

    /**
     * 构造函数
     *
     * @param string  $path  视图目录
     * @param array   $data  视图数据
     */
    public function __construct($path = '', $data = [])
    {
        $this->path = $path;
        $this->data = $data;
    }

    /**
     * 显示视图
     *
     * @param string  $view  视图
     * @param array   $data  视图数据
     */
    public function display($view, $data = [])
    {
        echo $this->render($view, $data);
    }

    /**
     * 返回视图
     *
     * @param  string  $view  视图
     * @param  array   $data  视图数据
     * @return string
     */
    public function fetch($view, $data = [])
    {
        return $this->render($view, $data);
    }

    /**
     * 渲染视图
     *
     * @param  string  $view  视图
     * @param  array   $data  视图数据
     * @return string
     */
    public function render($view, $data = [])
    {
        $this->set($data);

        $this->increase();

        $contents = trim($this->getContent($view));

        if (isset($this->extends[$this->offset])) {// 视图继承处理
            $this->setSections('content', $contents);
            $parent = $this->extends[$this->offset];
            $contents = trim($this->getContent($parent));
        }

        if (isset($this->sectionsNotFound[$this->offset])) {// 对模板中未输出的变量进行替换
            foreach ($this->sectionsNotFound[$this->offset] as $name) {
                $contents = str_replace('<!--@section_'.$name.'-->', $this->getSections($name), $contents);
            }
        }

        $this->flush();

        $this->decrement();

        return $contents;
    }

    /**
     * 视图继承
     *
     * @param  string  $view
     */
    public function extend($view)
    {
        $this->extends[$this->offset] = $view;
    }

    /**
     * 嵌套视图
     *
     * @param string $view
     * @param array  $data
     */
    public function nest($view, $data = [])
    {
        echo $this->render($view, $data);
    }

    /**
     * 开始定义一个视图片段
     * 
     * 一般 section() 与 end() 成对出现, 但传递第二个参数，则不需要 end()
     * 
     * @param string $name
     * @param string $content
     */
    public function section($name , $content = '')
    {
        ob_start();
        $this->sectionStacks[$this->offset][] = $name;

        if ( $content ) {
            $lastname = array_pop($this->sectionStacks[$this->offset]);
            $this->setSections($lastname, $content);
            ob_end_clean();
        }
    }

    /**
     * 结束定义一个视图片段
     *
     * @return string 视图片段标识符
     */
    public function end()
    {
        $lastname = array_pop($this->sectionStacks[$this->offset]);
        $this->setSections($lastname, ob_get_clean());
        return $lastname;
    }

    /**
     * 定义视图片段输出位置
     *
     * @param string $name
     * @param string $content
     */
    public function with($name, $content = '')
    {
        if ( isset($this->sections[$this->offset][$name]) ) {
            echo $this->sections[$this->offset][$name];
        } else {
            $this->sectionsNotFound[$this->offset][] = $name;
            echo '<!--@section_' . $name . '-->';
        }

        if ($content) {
            $this->setSections($name, $content);
        }
    }

    /**
     * 输出视图数据 - content
     *
     * @param  boolean $echo 是否直接输出
     * @return string        如果不是直接输出则返回内容
     */
    public function content($echo = true)
    {
        $content = $this->getSections('content');

        if ($echo) {
            echo $content;
        } else {
            return $content;
        }
    }

    /**
     * 添加视图片段
     *
     * @param  string $name
     * @param  string $content 
     */
    protected function setSections($name, $content)
    {
        $this->sections[$this->offset][$name] = $content;
    }

    /**
     * 获取视图片段
     *
     * @param  string $name
     * @return string
     */
    protected function getSections($name)
    {
        if (isset($this->sections[$this->offset][$name])) {
            return $this->sections[$this->offset][$name];
        } else {
            return '';
        }
    }

    /**
     * 设置视图目录
     *
     * @param  string  $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * 获取视图目录
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 视图是否存在
     *
     * @return bool
     */
    public function exists($view)
    {
        return $this->getPathname($view) ? true : false;
    }

    /**
     * 视图数据是否存在?
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * 获取视图数据
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * 添加视图数据
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * 删除视图数据
     *
     * @param string $key
     */
    public function delete($key)
    {
        unset($this->data[$key]);
    }

    /**
     * 清空视图数据
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * 视图数据是否存在?
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }
    
    /**
     * 获取视图数据
     *
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }
    
    /**
     * 添加视图数据
     *
     * @param string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * 删除视图数据
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        $this->delete($key);
    }

    /**
     * 处理视图文件
     * 
     * @param  string $view
     * @param  array  $data
     * @return string
     */
    protected function getContent($view, $data = [])
    {
        $file = $this->getPathname($view);

        if (file_exists($file)) {
            ob_start();
            $data = array_merge($this->data, (array) $data);
            extract($data);

            try
            {
                include $file;
            } catch (Exception $e) {
                ob_get_clean();
                throw $e;
            }

            return ob_get_clean();
        } else {
            throw new \Exception("Cannot find the requested view: " . $view);
        }
    }

    /**
     * 返回视图文件路径
     * 
     * @param  string $view
     * @return string
     */
    protected function getPathname($view)
    {
        if ($this->path) {
            $view = $this->path . DIRECTORY_SEPARATOR . ltrim($view, DIRECTORY_SEPARATOR);
        }
        
        return $view;
    }

    /**
     * 提升嵌套级别
     */
    protected function increase()
    {
        $this->offset++;
    }

    /**
     * 降低嵌套级别
     */
    protected function decrement()
    {
        $this->offset--;
    }

    /**
     * 重置视图片段
     * 
     * @return void
     */
    protected function flush()
    {
        unset($this->sections[$this->offset], 
              $this->sectionStacks[$this->offset], 
              $this->sectionsNotFound[$this->offset]);
    }

    /**
     * 获取视图数据
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }
    
    /**
     * 添加视图数据
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }
    

    /**
     * 视图数据是否存在?
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }
    
    /**
     * 删除视图数据
     *
     * @param string $key
     */
    public function __unset($key)
    {
        $this->delete($key);
    }
}