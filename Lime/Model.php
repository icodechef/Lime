<?php

namespace Lime;

/**
 * Model
 *
 * 模型的任务是把原有数据转换成包含某些意义的数据，这些数据将被视图所显示
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Model implements \ArrayAccess, \Iterator, \Countable 
{
    /**
     * 数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 当前数据
     *
     * @var array
     */
    protected $row;

    /**
     * 当前位置
     *
     * @var integer
     */
    protected $position = 0;

    /**
     * 用于 each() 标记当前队列的顺序值
     *
     * @var integer
     */
    protected $sequence = 0;

    /**
     * 将实例赋值给变量
     *
     * @param  string  $variable 变量名
     * @return mixed
     */
    public function to(&$variable)
    {
        return $variable = $this;
    }

    /**
     * 获取数据
     *
     * @param  string|null  $key
     * @return mixed
     */
    public function get($key = null)
    {
        if (is_null($key)) {
            return $this->row();
        } else {
            return isset($this->data[$key]) ? $this->data[$key] : null;
        }
    }

    /**
     * 添加数据
     *
     * @param  string|mixed  $key
     */
    public function add($value)
    {
        $this->data[] = $value;
    }

    /**
     * 设置数据
     *
     * @param  string|mixed  $key
     * @param  mixed  $value
     */
    public function set($key, $value = null)
    {
        if (func_num_args() == 1) {
            $this->add($key);
        } else {
            $this->data[$key] = $value;
        }

        $this->row = $value;
    }

    /**
     * 存在数据?
     *
     * @return bool
     */
    public function has()
    {
        return $this->count() > 0 ? true : false;
    }

    /**
     * 返回当前数据
     *
     * $key 以 '@' 开头表示一个希望使用方法(get前缀的方法)访问数据
     * 
     * USAGE:
     * 
     *  Model::row('name');  // 直接获取数据
     *  Model::row('@name'); // getName()
     *
     * @param  string|null  $key
     * @param  mixed        $default  默认值或者回调函数
     * @return mixed
     */
    public function row($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->row;
        }

        if (strpos($key, '@') === 0) {
            /* 方法访问数据 */
            $key = substr($key, 1);
            $getter = sprintf('get%s', ucfirst($key));

            if (method_exists($this, $getter)) {
                $value = isset($this->row[$key]) ? $this->row[$key] : null;
                return $this->$getter($value, $default);
            }
        }

        $isClosure = $default instanceof \Closure;

        if (isset($this->row[$key])) {
            $value = $this->row[$key];
        } else if (! $isClosure) {
            $value = $default;
        } else {
            $value = null;
        }

        return $isClosure ? $default($value) : $value;
    }

    /**
     * 获取所有数据
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * 遍历数据
     *
     * @return bool
     */
    public function each()
    {
        if ($this->valid()) {
            $this->row      = $this->current();
            $this->sequence = $this->key();
            // 指向下一个
            $this->next();
        } else {
            $this->row = null;
        }

        if ($this->row) {
            return true;
        }

        $this->rewind();

        return false;
    }

    /**
     * 清空
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * 移除数据
     *
     * @param  string $key
     */
    public function remove($key)
    {
        unset($this->data[$key]);
    }

    /**
     * 存在方法
     *
     * @param  string $method
     * @return bool
     */
    public function exists($method)
    {
        return method_exists($this, $method);
    }

    /**
     * 返回顺序值
     *
     * @access public
     * @return void
     */
    public function seq()
    {
        return $this->sequence;
    }

    /**
     * 存在数据?
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * 获取数据
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * 添加数据
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
     * 移除数据
     *
     * @param  string $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * 多少数据?
     *
     * @return int
     */
    public function count() 
    {
        return count($this->data);
    }

    /**
     * 重置位置
     *
     * @return \Lime\Model
     */
    public function rewind()
    {
        $this->position = 0;
        return $this;
    }

    /**
     * 获取当前位置的数据
     *
     * @return mixed
     */
    public function current()
    {
        return $this->valid() ? $this->data[$this->position] : null;
    }

    /**
     * 获取当前位置
     *
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * 是否有效
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->data[$this->position]) ? true : false;
    }

    /**
     * 下一个位置
     *
     * @return \Lime\Model
     */
    public function next()
    {
        ++$this->position;
        return $this;
    }

    /**
     * 添加数据
     *
     * @param string  $key
     * @param mixed   $value
     */
    public function __set($key, $value)
    {
        $setter = 'set' . ucfirst($key);
        if (method_exists($this, $setter)) {
            /* 首先调用 set 开头的方法 */
            $this->$setter($value);
        } else {
            $this->set($key, $value);
        }
    }

    /**
     * 获取数据
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        $getter = 'get' . ucfirst($key);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            $this->get($key);
        }
    }

    /**
     * 存在数据?
     *
     * @param  string $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * 移除数据
     *
     * @param  string $key
     */
    public function __unset($key)
    {
        $this->remove($key);
    }

    /**
     * 当类的方法访问不到时调用该方法
     *
     * @param string $name
     * @param array  $arguments
     */
    public function __call($name , $arguments)
    {
        throw new \Exception('Undefined method: ' . get_class($this) . "::$name()");
    }

}