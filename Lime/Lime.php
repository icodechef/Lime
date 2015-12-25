<?php
/**
 * Lime - a micro PHP 5 framework
 *
 * @author      icodechef <dengman2010@gmail.com>
 * @copyright   2015 icodechef
 * @link        https://github.com/icodechef/Lime
 * @license     MIT <https://github.com/icodechef/Lime/blob/master/LICENSE>
 * @version     1.0.0
 * @package     Lime
 */
namespace Lime;

/**
 * Lime
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Lime {
    /**
     * 版本号
     *
     * @const string
     */
    const VERSION = '1.0.0';

    /**
     * 容器
     * 
     * @var \Lime\Container
     */
    public $container;

    /**
     * 中间件
     * 
     * @var \Lime\Middleware\Map
     */
    protected $middleware;

    protected $defaultOptions = [
        'debug' => false,
        'resource.path' => '',
        'views.path' => '',
        'services' => [],
        'middleware' => [],
    ];

    /**
     * 自动加载
     * 
     * @var \Lime\Loader
     */
    public static $loader;

    /**
     * 全局变量
     *
     * @var static
     */
    protected static $app;

    /**
     * 获取应用实例或者可用的容器实例
     *
     * @param  string  $name
     * @param  array   $parameters
     * @return mixed|\Lime\Lime
     */
    public static function app($name = null, $parameters = [])
    {
        if (is_null($name)) {
            return static::$app;
        }
        
        return static::$app->container->get($name, $parameters);
    }

    /**
     * 设置应用实例
     *
     * @param  \Lime\Lime
     * @return void
     */
    public static function setApp($app)
    {
        if (is_null(static::$app) && $app) {
            static::$app = $app;
        }
    }

    /**
     * 创建一个应用实例。
     *
     * @param  array        $options
     * @param  loader|null  $loader   如果为空, 使用内置自动加载
     * @return void
     */
    public function __construct($options = [], $loader = null)
    {
        // 配置
        $options = array_merge($this->getDefaultOptions(), (array) $options);

        static::setApp($this);

        if (is_null($loader)) {
            // 使用 Lime 的自动加载
            $loader = $this->registerAutoloader($options['resource.path']);
        }

        $this->unregisterGlobals();

        /*
         * 注册错误或者异常处理函数
         */
        set_error_handler(array('\Lime\Lime', 'handleErrors'));
        set_exception_handler(array('\Lime\Lime', 'handleException'));
        // 注册 shutdown 函数
        register_shutdown_function(array('\Lime\Lime', 'shutdownOrExit'));

        $option = new Option($options);

        // 创建 Middleware 管理
        $this->middleware = new \Lime\Middleware\Map();

        // 创建一个依赖注入容器
        $this->container = new Container();

        /*
         * 第三个参数为 true 表示共享服务
         */
        $this->container->set('loader', $loader, true);

        $this->container->set('option', $option, true);

        $this->container->set('router', function() {
            return new Router();
        }, true);

        $this->container->set('request', function() {
            return new Request();
        }, true);

        $this->container->set('response', function() {
            return new Response();
        }, true);

        $this->container->set('view', function() use($option) {
            return new View($option->get('views.path'));
        }, true);

        $this->container->set('url', function() {
            return new Url();
        }, true);

        $this->container->set('cookie', function() {
            return new Cookie();
        }, true);

        $this->loadHelpers();

        $this->registerServices($options['services']);
    }

    /**
     * 清理全局变量
     *
     * Register Globals 特性已自 PHP 5.3.0 起废弃并将自 PHP 5.4.0 起移除
     *
     * @return void
     */
    public function unregisterGlobals()
    {
        if (!ini_get('register_globals'))
            return;

        if (isset($_REQUEST['GLOBALS']))
            die( 'GLOBALS overwrite attempt detected' );

        // 保留的全局变量
        $no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

        $input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset( $_SESSION ) && is_array( $_SESSION ) ? $_SESSION : []);
        foreach ($input as $k => $v) {
            if (!in_array( $k, $no_unset) && isset($GLOBALS[$k])) {
                unset( $GLOBALS[$k] );
            }
        }

        ini_set('register_globals', 0);
    }

    /**
     * 获取默认应用程序的设置
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    /**
     * 添加或者获取配置
     *
     * @param  string|array $name
     * @param  mixed        $values
     * @return \Lime\Option
     */
    public function configure($name = null, $value = null)
    {
        $option = $this->container->get('option');

        if (is_null($name)) {
            return $option->all();
        }

        if (func_num_args() == 1 && !is_array($name)) {
            return $option->get($name);
        }

        $option->set($name, $value);
    }

    /**
     * 注册服务
     *
     * @param array $services
     */
    public function registerServices($services =[])
    {
        foreach ($services as $service) {
            call_user_func_array([$this->container, 'set'], $service);
        }
    }

    /**
     * 注册一个服务
     *
     * @param  string           $name
     * @param  object|callable  $definition
     * @param  bool             $shared
     * @return \Lime\Container
     */
    public function service($name, $definition, $shared = false)
    {
        return $this->container->set($name, $definition, $shared);
    }

    /**
     * 中间件
     *
     * @param  string   $name
     * @param  mixed    $callable  A callable object
     * @return middleware
     */
    public function middleware($name = null, $callable = null)
    {
        if (is_null($name)) {
            return $this->middleware->all();
        }

        if (func_num_args() == 1) {
            return $this->middleware->get($name);
        }

        $this->middleware->add($name, $callable);

        return $this;
    }

    /**
     * 添加路由
     *
     * USAGE:
     *
     * Lime::map('GET', '/', handler);
     *
     * @param  string|array  $method GET|POST|PUT|PATCH|DELETE|OPTIONS
     * @param  string        $pattern
     * @param  mixed         $handler
     * @return \Lime\Route
     */
    public function map($method, $pattern, $handler)
    {
        if ($handler instanceof \Closure) {
            $handler = $handler->bindTo($this);
        }
        
        return $this->container->get('router')->map($method, $pattern, $handler);
    }

    /**
     * 路由分组
     *
     * @param  string $pattern
     * @param  mixed  $handler
     * @param  mixed  $filter
     */
    public function group($pattern, $handler, $filter = null)
    {
        $args = func_get_args();

        $filters = array_slice($args, 2);

        if ($handler instanceof \Closure) {
            $handler = $handler->bindTo($this);
        }

        $this->container->get('router')->pushGroup($pattern);

        if (is_callable($handler)) {
            $this->container->get('router')->mount($handler, $filters);
        }

        $this->container->get('router')->popGroup();
    }

    /**
     * 添加 GET 路由
     *
     * @see    map()
     * @param  string $pattern
     * @param  mixed  $handler
     * @return \Lime\Route
     */
    public function get($pattern, $handler)
    {
        return $this->map(['GET'], $pattern, $handler);
    }

    /**
     * 添加 POST 路由
     *
     * @see    map()
     * @param  string $pattern
     * @param  mixed  $handler
     * @return \Lime\Route
     */
    public function post($pattern, $handler)
    {
        return $this->map(['POST'], $pattern, $handler);
    }

    /**
     * 添加 PUT 路由
     *
     * @see    map()
     * @param  string $pattern
     * @param  mixed  $handler
     * @return \Lime\Route
     */
    public function put($pattern, $handler)
    {
        return $this->map(['PUT'], $pattern, $handler);
    }

    /**
     * 添加 PATCH 路由
     *
     * @see    map()
     * @param  string $pattern
     * @param  mixed  $handler
     * @return \Lime\Route
     */
    public function patch($pattern, $handler)
    {
        return $this->map(['PATCH'], $pattern, $handler);
    }

    /**
     * 添加 DELETE 路由
     *
     * @see    map()
     * @param  string $pattern
     * @param  mixed  $handler
     * @return \Lime\Route
     */
    public function delete($pattern, $handler)
    {
        return $this->map(['DELETE'], $pattern, $handler);
    }

    /**
     * 添加 OPTIONS 路由
     *
     * @see    map()
     * @param  string $pattern
     * @param  mixed  $handler
     * @return \Lime\Route
     */
    public function options($pattern, $handler)
    {
        return $this->map(['OPTIONS'], $pattern, $handler);
    }

    /**
     * 添加任何 HTTP 方法的路由
     *
     * @param  string $pattern
     * @param  mixed  $handler
     * @return \Lime\Route
     */
    public function any($pattern, $handler)
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $handler);
    }

    /**
     * Run
     *
     * @param  bool  $echo 是否输出
     * @return mixed
     */
    public function run( $echo = true )
    {
        $router   = $this->container->get('router');
        $request  = $this->container->get('request');
        $response = $this->container->get('response');

        try {
            $route = $this->middleware->make('router', function($method, $uri) use ($router) {
                return $router->handle($method, $uri);
            })->call($request->method(), $request->uri());
            
            if ($route) {
                $request->setParams($route->getParams());

                // middleware dispatch
                $middleware = $this->middleware->make('dispatch', function() use ($route) {
                    return $route->dispatch();
                });

                $res = $middleware->call();

            } else {
                throw new \Lime\Exception\NotFoundException("Page Not Found");
            }
        } catch (\Lime\Exception\NotFoundException $e) {
            if (! $this->middleware->has('notFoundHandler')) {
                throw $e;
            }

            $this->cleanBuffer();
            $response->setStatus(404);
            $res = $this->middleware->make('notFoundHandler')->call($e);
        } catch (\Exception $e) {
            if (! $this->middleware->has('errorHandler')) {
                throw $e;
            }

            $this->cleanBuffer();
            $response->setStatus(500);
            $res = $this->middleware->make('errorHandler')->call($e);
        }

        if(! is_null($res)) {
            $response->setBody($res);
        }

        if ($echo) {
            $response->respond();
        }

        return $response;
    }

    /**
     * 将 error 转换为 ErrorException
     *
     * @param  int            $errno
     * @param  string         $errstr
     * @param  string         $errfile
     * @param  int            $errline
     * @return bool
     * @throws \ErrorException
     */
    public static function handleErrors($errno, $errstr, $errfile, $errline)
    {
        if (error_reporting() & $errno) {
            static::handleException(new \ErrorException($errstr, $errno, 0, $errfile, $errline));
        }

        return true;
    }

    /**
     * 处理异常
     *
     * @param Exception $e
     */
    public static function handleException(\Exception $e)
    {
        restore_error_handler();
        restore_exception_handler();

        // clean buffer
        if (! headers_sent()) {
            ob_get_level() AND ob_clean();
        }

        $response = new Response();

        try {
            $status = $e instanceof \Lime\Exception\NotFoundException ? 404 : 500;
            $response->setStatus($status);
            $response->setBody($status == 404 ? static::notFound($e) : static::error($e));
            $response->respond();
        } catch (\Exception $e) {
            /* 异常处理中出错的最后处理 */
            ob_get_level() AND ob_clean();
            header('Content-Type: text/plain; charset=UTF-8', true, 500);
            echo static::text($e);
        }

        exit(1);
    }

    /**
     * 格式化异常信息 - text
     *
     * @param  Exception $e
     * @return string
     */
    public static function text(\Exception $e)
    {
        $file = $e->getFile();
        $file = realpath($file);
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            $root = realpath($_SERVER['DOCUMENT_ROOT']);
            $file = str_replace($root, '', $file);
        }

        return sprintf('%s ( %s ): %s ~ %s [ %d ]',
            get_class($e), $e->getCode(), strip_tags($e->getMessage()), $file, $e->getLine());
    }

    /**
     * 格式化异常信息 - html
     *
     * @param  Exception $e
     * @return string
     */
    public static function html($title, $body)
    {
        return sprintf('<!DOCTYPE html><html><head><title>%s</title><style type="text/css">body{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:14px;line-height:1.4;color:#333;background-color:#fff;margin:40px}article{min-height:20px;padding:15px;border:1px solid #e3e3e3;border-radius:4px}h1{font-size:18px;margin-top:10px;margin-bottom:10px;padding-bottom:9px;border-bottom:1px solid #e3e3e3}p{margin:0 0 10px}</style></head><body><article><h1>%s</h1>%s</article>', $title, $title, $body);
    }

    /**
     * Not found
     *
     * @param  Exception $e
     * @return string
     */
    public static function notFound(\Exception $e)
    {
        return static::html('404 Page Not Found', '<p>The page you requested was not found. Check the address bar to ensure your URL is spelled correctly. If all else fails, you can visit our home page at the link below.</p><a href="' . static::$app->request->basePath() . '">Visit the Home Page</a>');
    }

    /**
     * error
     *
     * @param  Exception $e
     * @return string
     */
    public static function error(\Exception $e)
    {
        return static::html('Error', '<p>A website error has occurred. The website administrator has been notified of the issue. Sorry for the temporary inconvenience.</p><p style="color:#CC0000;">' . static::text($e) . '</p>');
    }

    /**
     * 清除输出缓冲器
     */
    public function cleanBuffer()
    {
        if (! headers_sent()) {
            ob_get_level() AND ob_clean();
        }
    }

    /**
     * shutdown 函数
     *
     * @param boolean $exit
     */
    public static function shutdownOrExit( $exit = false )
    {
        if (($error = error_get_last()) && $error['type']) {
            if (static::$app->option['debug']) {
                $constants = [E_PARSE, E_ERROR, E_USER_ERROR, E_CORE_ERROR, E_COMPILE_ERROR];
            } else {
                $constants = [E_PARSE, E_ERROR, E_USER_ERROR];
            }

            if ( in_array($error['type'], $constants) ) {
                static::handleException( new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']) );
            }
        }

        if ( $exit ) {
            exit();
        }
    }

    /**
     * 注册自动加载器
     */
    public function registerAutoloader($path = null)
    {
        if (is_null(static::$loader)) {
            require __DIR__ . DIRECTORY_SEPARATOR . 'Loader.php';

            $loader = new Loader();
            $loader->register();
            $loader->addClassMap(require __DIR__ . DIRECTORY_SEPARATOR . 'classmap.php');
            
            // 注册文件夹
            if (is_dir($path)) {
                $loader->add(null, $path);
            }
            
            static::$loader = $loader;
        }

        return static::$loader;
    }

    /**
     * 载入 Helplers
     */
    public function loadHelpers()
    {
        require __DIR__ . DIRECTORY_SEPARATOR . 'helpers.php';
    }

    /**
     * 返回一个服务的新实例
     *
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->container->get($name);
    }

    /**
     * 判断一个指定的服务是否存在
     *
     * @param  string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->container->has($name);
    }
    /**
     * 调用一个不存在的方法时，从容器中查找，如存在则调用该服务
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, $argumnts)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name, $argumnts);
        }
    }
}