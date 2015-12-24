<?php

namespace Lime;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionParameter;

/**
 * 容器
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Container implements \ArrayAccess
{
    /**
     * 服务
     *
     * @var array
     */
    private $services = [];

    /**
     * 容器共享实例
     *
     * @var array
     */
    private $instances = [];

    /**
     * 注册服务
     *
     * @param  string           $name
     * @param  object|callable  $definition
     * @param  bool             $shared  共享服务
     * @return \Lime\Container
     */
    public function set($name, $definition, $shared = false)
    {
        $this->services[$name] = [
            'definition' => $definition,
            'shared' => (bool)$shared,
        ];

        return $this;
    }

    /**
     * 返回一个服务的新实例
     *
     * @param  string  $name
     * @param  array   $parameters
     * @return mixed
     */
    public function get($name, $parameters = [])
    {
        $instance = null;

        if ( $this->has($name) ) {
            $instance = $this->resolve($name, $parameters);
        } else {
            $instance = $this->build($name, $parameters);
        }

        return $instance;
    }

    /**
     * 移除服务
     *
     * @param  string  $name
     */
    public function remove($name)
    {
        unset($this->services[$name], $this->instances[$name]);
    }

    /**
     * 判断一个指定的服务是否存在
     *
     * @param  string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->services[$name]);
    }

    /**
     * 解析服务
     *
     * @param  string  $name
     * @param  array   $parameters
     * @return mixed
     */
    public function resolve($name, $parameters = [])
    {
        $instance = null;
        $service = $this->services[$name];
        
        if ($service['shared']) {
            if (isset($this->instances[$name])) {
                return $this->instances[$name];
            }
        }

        $definition = $service['definition'];

        if (is_object($definition)) {
            if ($definition instanceof Closure) {
                /* 如果是一个匿名函数 */
                $instance = $this->build($definition, $parameters);
            } else {
                /* 一个对象, 直接返回 */
                $instance = $definition;
            }
        } elseif (is_string($definition)) {
            /* 新建一个类实例 */
            $instance = $this->build($definition, $parameters);
        } else if (is_array($definition)) {
            if (!isset($definition['className'])) {
                throw new \Exception("Invalid service definition. Missing 'className'");
            }

            $arguments = isset($definition['arguments']) ? $definition['arguments'] : [];
            $arguments = array_merge($arguments, $parameters);

            $instance = $this->build($definition['className'], $arguments);
        }

        if ($service['shared'] && is_object($instance)) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    /**
     * 创建一个类实例
     *
     * @param  string|callable  $definition
     * @param  array            $parameters
     * @return mixed
     */
    public function build($definition, $parameters = array())
    {
        if ($definition instanceof Closure) {
            return $definition($this, $parameters);
        }

        if (! class_exists($definition)) {
            throw new Exception("Service '" . $definition . "' wasn't found in the dependency injection container");
            return;
        }

        $reflector = new ReflectionClass($definition);

        // 检查类是否可实例化, 排除抽象类 abstract 和对象接口 interface
        if (! $reflector->isInstantiable()) {
            throw new Exception("Can't instantiate this.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $definition;
        }

        $dependencies = $constructor->getParameters();

        $parameters = $this->keyParametersByArgument(
            $dependencies, $parameters
        );

        $instances = $this->getDependencies(
            $dependencies, $parameters
        );

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * 重置实参数组的键值
     *
     * @param  array  $dependencies
     * @param  array  $parameters
     * @return array
     */
    protected function keyParametersByArgument(array $dependencies, array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_numeric($key)) {
                unset($parameters[$key]);
                $parameters[$dependencies[$key]->name] = $value;
            }
        }

        return $parameters;
    }

    /**
     * 解析类的参数的依赖
     *
     * @param  array  $parameters
     * @param  array  $primitives
     * @return array
     */
    protected function getDependencies(array $parameters, array $primitives = array())
    {
        $dependencies = array();

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            if (array_key_exists($parameter->name, $primitives)) {
                /* 实参 */
                $dependencies[] = $primitives[$parameter->name];
            } elseif (is_null($dependency)) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->resolveClass($parameter);
            }
        }

        return (array) $dependencies;
    }

    /**
     * 解析一个非类的依赖
     *
     * @param  ReflectionParameter $parameter
     * @return mixed
     */
    protected function resolveNonClass(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new Exception($message);
    }

    /**
     * 解析一个类的依赖
     *
     * @param  ReflectionParameter $parameter
     * @return mixed
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->build($parameter->getClass()->name);
        } catch (Exception $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }
            throw $e;
        }
    }

    /**
     * 存在服务?
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * 返回服务
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * 设置服务
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * 移除服务
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * 设置服务
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * 返回服务
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * 移除服务
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->remove($key);
    }

    /**
     * 存在服务?
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }
}