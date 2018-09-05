<?php
/*
 * 辅助函数
 */

if (! function_exists('app')) {
    /**
     * 获取应用实例或者可用的容器实例
     *
     * @access public
     * @param  string  $name
     * @param  array   $parameters
     * @return mixed|\Lime\Lime
     */
    function app($name = null, $parameters = []) {
        return \Lime\Lime::app($name, $parameters);
    }
}

if (! function_exists('services')) {
    /**
     * 获取可用的容器实例
     *
     * services() 与 app() 雷同, 其区别主要在语义上
     *
     * @access public
     * @param  string  $name
     * @param  array   $parameters
     * @return mixed|\Lime\Lime
     */
    function services($name, $parameters = []) {
        return \Lime\Lime::app($name, $parameters);
    }
}

if (! function_exists('configure')) {
    /**
     * 添加或者获取配置
     *
     * @access public
     * @param  string|array $name
     * @param  mixed        $values
     * @return \Lime\Option
     */
    function configure($key = null, $value = null) {
        return call_user_func_array([\Lime\Lime::app(), 'configure'], func_get_args());
    }
}

if (! function_exists('request')) {
    /**
     * 返回请求实例
     *
     * @access public
     * @return \Lime\Request
     */
    function request() {
        return app('request');
    }
}

if (! function_exists('response')) {
    /**
     * 返回响应实例
     *
     * @access public
     * @return \Lime\Request
     */
    function response() {
        return app('response');
    }
}

if (! function_exists('view')) {
    /**
     * 渲染视图
     *
     * @access public
     * @param  string  $view
     * @param  array   $data
     * @return mixed|\Lime\View
     */
    function view($view = null, $data = []) {
        $instance = app('view');

        if (is_null($view)) {
            return $instance;
        }

        return $instance->render($view, $data);
    }
}

if (! function_exists('cookie')) {
    /**
     * 返回 Cookie 实例
     *
     * @access public
     * @return \Lime\Request
     */
    function cookie() {
        return app('cookie');
    }
}

if (! function_exists('url_site')) {
    /**
     * 绝对 URL
     *
     * @access public
     * @param boolean $index 是否显示文件
     * @return string 
     */
    function url_site($index = false) {
        return app('url')->site($index);
    }
}

if (! function_exists('url_base')) {
    /**
     * 相对 URL
     *
     * @access public
     * @param boolean $index 是否显示文件
     * @return string 
     */
    function url_base($index = false) {
        return app('url')->base($index);
    }
}

if (! function_exists('url_for')) {
    /**
     * 生成 URL (根据路由名称)
     *
     * @param  string  $name
     * @param  array   $params
     * @return string
     */
    function url_for($name, $params = []) {
        return app('url')->urlFor($name, $params);
    }
}

if (! function_exists('dump')) {
    /**
     * 打印数组
     *
     * @since 1.0.0
     *
     * @access public
     * @return void
     */
    function dump() {
        foreach(func_get_args() as $val) {
            // 如果值为 bollean 类型, 退出当前脚本
            if (is_bool($val) && $val === true) {
                exit();
            } else {
                echo '<pre>'.htmlspecialchars($val === null ? 'NULL' : (is_scalar($val) ? $val : print_r($val, true)), ENT_QUOTES)."</pre>";
            }
        }
    }
}

if (! function_exists('csrf_token')) {
    /**
     * 返回令牌名称
     *
     * @return string
     */
    function csrf_token_name() {
        return '_token';
    }

    /**
     * 生成嵌入令牌的隐藏元素
     *
     * @return string
     */
    function csrf_field() {
        return '<input type="hidden" name="' . csrf_token_name() . '" value="' . htmlspecialchars(csrf_token()) . '">';
    }

    /**
     * 生成嵌入令牌的 query
     *
     * @return string
     */
    function csrf_query() {
        return sprintf("%s=%s", csrf_token_name(), htmlspecialchars(csrf_token()));
    }

    /**
     * 生成嵌入令牌的 path
     *
     * @return string
     */
    function csrf_path() {
        return sprintf("%s/%s", csrf_token_name(), htmlspecialchars(csrf_token()));
    }

    /**
     * 返回令牌
     *
     * 会话 ID 就是一种既简单又安全的令牌的实现方法
     *
     * @return string
     */
    function csrf_token($length = 32, $raw_output = false) {
        $hash = hash_hmac('sha1', session_id(), session_id());
        return substr($hash, 0, $length);
    }

    /**
     * 自动抓取 POST 数据来检查指定令牌当前存储的安全令牌相匹配
     *
     * @param   string  $token
     * @return  boolean
     */
    function csrf_verify() {
        $save = csrf_token();

        if (empty($save)) {
            return false;
        }

        $name = csrf_token_name();

        $token = isset($_POST[$name]) ? $_POST[$name] : (isset($_GET[$name]) ? $_GET[$name] : '');

        if (empty($token)) {
            return false;
        }

        if (csrf_equals($save, $token)) {
            return true;
        }

        return false;
    }

    /**
     * 比较两个哈希值
     * 
     * @param string $a
     * @param string $b
     * @return boolean
     */
    function csrf_equals($a, $b) {
        $a_length = strlen($a);
        if ($a_length !== strlen($b)) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $a_length; $i++) {
            $result |= ord($a[ $i ]) ^ ord($b[ $i ]);
        }

        return $result === 0;
    }
}
