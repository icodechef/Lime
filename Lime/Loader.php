<?php

namespace Lime;

/**
 * 自动加载
 *
 * 參照 PSR-0 和 PSR-4 标准实现
 * 
 * http://www.php-fig.org/psr/psr-0/
 * http://www.php-fig.org/psr/psr-4/
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Loader
{
    /**
     * 类表
     *
     * @var array
     */
	private $classMap = [];

    /**
     * 键的是命名空间前缀，值是基目录的数组
     *
     * @var array
     */
    protected $prefixes = [];

    /**
     * 基目录
     *
     * @var array
     */
    protected $paths = [];

	/**
     * 注册自动加载实例
     *
     * @param bool $prepend
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, (bool)$prepend);
    }

    /**
     * 注销自动加载实例
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * 注册命名空间
     *
     * @param string  $prefix
     * @param string  $base_dir 
     * @param bool    $prepend
     */
    public function add($prefix, $base_dir, $prepend = false)
    {
        // PSR-0
        if (!$prefix) {
            if ($prepend) {
                $this->paths = array_merge(
                    (array) $base_dir,
                    $this->paths
                );
            } else {
                $this->paths = array_merge(
                    $this->paths,
                    (array) $base_dir
                );
            }
            return;
        }

        // normalize the namespace prefix
        $prefix = trim($prefix, '\\') . '\\';
        // initialize the namespace prefix array if needed
        if (! isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }
        // normalize each base dir with a trailing separator
        $base_dirs = (array) $base_dirs;
        foreach ($base_dirs as $key => $base_dir) {
            $base_dirs[$key] = rtrim($base_dir, DIRECTORY_SEPARATOR)
                             . DIRECTORY_SEPARATOR;
        }
        // prepend or append?
        if ($prepend) {
            $this->prefixes[$prefix] = array_merge($base_dirs, $this->prefixes[$prefix]);
        } else {
            $this->prefixes[$prefix] = array_merge($this->prefixes[$prefix], $base_dirs);
        }
    }

    /**
     * 加载给定的类或接口
     *
     * @param  string  $class
     * @return bool
     */
    public function loadClass($class)
    {
    	// 利用 try catch 修复加载文件时不能抛出异常的 bug
        try {
            if ($file = $this->findFile($class)) {
	            return true;
	        }

            return false;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 查找文件
     *
     * @param  string $class
     * @return string
     */
    public function findFile($class)
    {
        // 在 class map 中查找
        if (isset($this->classMap[$class])) {
            $file = $this->classMap[$class];
            $this->includeFile($file);
            return $file;
        }

        /*
         * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
         */

        // the current namespace prefix
        $prefix = $class;

        // work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while (false !== $pos = strrpos($prefix, '\\')) {

            // retain the trailing namespace separator in the prefix
            $prefix = substr($class, 0, $pos + 1);

            // the rest is the relative class name
            $relative_class = substr($class, $pos + 1);

            // try to load a mapped file for the prefix and relative class
            $mapped_file = $this->loadMappedFile($prefix, $relative_class);
            if ($mapped_file) {
                return $mapped_file;
            }

            // remove the trailing namespace separator for the next iteration
            // of strrpos()
            $prefix = rtrim($prefix, '\\');   
        }

        /*
         * http://www.php-fig.org/psr/psr-0/
         */
        // PSR-0 lookup
        foreach ($this->paths as $path) {
            $className = ltrim($class, '\\');
            $fileName  = '';
            $namespace = '';
            if ($lastNsPos = strrpos($className, '\\')) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

            $file = $path . DIRECTORY_SEPARATOR . $fileName;

            $included = $this->includeFile($file);

            if ($included) {
                return $fileName;
            }
        }

        // never found a mapped file
        // Remember that this class does not exist.
        return $this->classMap[$class] = false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     * 
     * @param string $prefix The namespace prefix.
     * @param string $relative_class The relative class name.
     * @return mixed Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedFile($prefix, $relative_class)
    {
        // are there any base directories for this namespace prefix?
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        // look through base directories for this namespace prefix
        foreach ($this->prefixes[$prefix] as $base_dir) {

            // replace the namespace prefix with the base directory,
            // replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $base_dir
                  . str_replace('\\', '/', $relative_class)
                  . '.php';

            // if the mapped file exists, include it
            if ($this->includeFile($file)) {
                // yes, we're done
                return $file;
            }
        }

        // never found it
        return false;
    }

	/**
     * 添加 class map
     *
     * @param array $classMap
     */
    public function addClassMap(array $classMap)
    {
        if ($this->classMap) {
            $this->classMap = array_merge($this->classMap, $classMap);
        } else {
            $this->classMap = $classMap;
        }
    }

    /**
     * include 文件
     *
     * @param  string  $file
     * @return bool
     */
    public function includeFile($file)
    {
        if (file_exists($file)) {
            include $file;
            return true;
        }

        return false;
    }
}