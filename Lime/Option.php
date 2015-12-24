<?php

namespace Lime;

/**
 * 配置
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Option implements \ArrayAccess
{
    /**
     * 配置数组
     *
     * @var array
     */
    protected $options = [];

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->set($options);
    }

    /**
     * 添加配置
     *
     * @param  string|array $name
     * @param  mixed        $values
     * @return \Lime\Option
     */
    public function set($name = null, $values = null)
    {
        // $name为 k-v 数组, 相当于赋值
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->setOption($key, $value);
            }
        } else {
            $this->setOption($name, $values);
        }

        return $this;
    }

    /**
     * 使用“点”符号 获取数组的值
     *
     * <code>
     *   \Lime\Options::get('user.name');
     * </code>
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        // 返回所有 options
        if ( is_null($key) ) {
            return $this->options;
        }

        $array = $this->options;

        if ( isset($array[$key]) ) {
            return $array[$key];
        }

        foreach ( explode('.', $key) as $segment ) {
            if ( !is_array($array) || !array_key_exists($segment, $array) ) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * 返回所有配置
     *
     * @return array
     */
    public function all()
    {
        return $this->options;
    }

    /**
     * 配置是否存在
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->options[$key]);
    }

    /**
     * 删除配置
     *
     * @param string $key
     */
    public function remove($key)
    {
        unset($this->options[$key]);
    }

    /**
     * 使用“点”符号 设置数组的值
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public function setOption($key, $value) {
        if (is_null($key)) {
            $this->options = $value;
            return;
        }

        $keys = explode('.', $key);
        $array = &$this->options;

        while ( count($keys) > 1 ) {
            $key = array_shift($keys);
            if ( !isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = array();
            }
            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * 配置是否存在
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * 获取配置
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * 添加配置
     *
     * @param  string $offset
     * @param  mixed  $value
     * @return mixed
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * 删除配置
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}