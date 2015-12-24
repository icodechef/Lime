<?php

namespace Lime;

/**
 * 响应
 *
 * @package  Lime
 * @author   icodechef
 * @since    1.0.0
 */
class Response
{
	/**
     * HTTP 状态代码
     *
     * @var int
     */
    protected $status;

    /**
     * HTTP 响应首部字段
     *
     * @var array
     */
    protected $headers = [];

    /**
     * HTTP 响应体
     *
     * @var array
     */
    protected $body;

    /**
     * 已经响应
     *
     * @var bool
     */
    protected $responded = false;

    /**
     * no cache
     *
     * @var bool
     */
    protected $noCache = false;

    /**
     * 协议
     *
     * @var string
     */
    protected $protocol = '1.1';

    /**
     * HTTP 状态代码和消息
     *
     * @var array
     */
    protected static $messages = array(
        //Informational 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        //Successful 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        226 => '226 IM Used',
        //Redirection 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',
        //Client Error 4xx
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        418 => '418 I\'m a teapot',
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',
        426 => '426 Upgrade Required',
        428 => '428 Precondition Required',
        429 => '429 Too Many Requests',
        431 => '431 Request Header Fields Too Large',
        //Server Error 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported',
        506 => '506 Variant Also Negotiates',
        510 => '510 Not Extended',
        511 => '511 Network Authentication Required'
    );

	/**
     * Constructor
     *
     * @param string  $body
     * @param int     $status
     */
    public function __construct($body = '', $status = 200)
    {
        $this->setStatus($status);
        $this->write($body);
    }

    /**
     * 返回 HTTP 状态代码
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 设置 HTTP 状态代码
     *
     * @param int $status
     */
    public function setStatus($status)
    {
    	if (! is_int($status) || ! static::getMessageForCode($status)) {
	        throw new \Exception('Invalid HTTP status code');
	    }

        $this->status = (int)$status;
    }

    /**
     * 获取 HTTP 响应体
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * 附加 HTTP 响应体
     *
     * @param  string  $body     附加到当前的 HTTP 响应体的内容
     * @param  bool    $replace  覆盖现有的响应体?
     * @return string            更新后的HTTP响应体
     */
    public function setBody($content, $replace = true)
    {
        return $this->write($content, $replace);
    }

    /**
     * 获取所有 HTTP 响应首部字段
     *
     * @return array
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * 获取 HTTP 响应首部字段
     *
     * @return array
     */
    public function getHeader($name)
    {
        $name  = $this->normalizeName($name);
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * 设置协议
     *
     * @param string $protocol
     */
    public function setprotocol($protocol)
    {
    	$this->protocol = (string)$protocol;
    }

    /**
     * 获取协议
     *
     * @return string
     */
    public function getProtocol()
    {
    	return $this->protocol;
    }

    /**
     * 添加 HTTP 响应首部字段
     *
     * @param  string   $name
     * @param  string   $value
     * @param  boolean  $replace
     * @return \Lime\Response
     */
    public function header($name, $value, $replace = false)
    {
        $name  = $this->normalizeName($name);
        $value = (string)$value;

        if ($replace) {
            if (isset($this->headers[$name])) {
                unset($this->headers[$name]);
            }
        }

        $this->headers[$name][] = array(
            'value'   => $value,
            'replace' => $replace,
        );

        return $this;
    }

    /**
     * 移除 HTTP 响应首部字段
     *
     * @param  string   $name
     * @return \Lime\Response
     */
    public function removeHeader($name)
    {
        $name  = $this->normalizeName($name);
        unset($this->headers[$name]);
        return $this;
    }

    /**
     * 附加 HTTP 响应体
     *
     * @param  string  $body     附加到当前的 HTTP 响应体的内容
     * @param  bool    $replace  覆盖现有的响应体?
     * @return string            更新后的HTTP响应体
     */
    public function write($body, $replace = false)
    {
        if ($replace) {
            $this->body = $body;
        } else {
            $this->body .= (string)$body;
        }
        //$this->length = strlen($this->body);

        return $this->body;
    }

    /**
     * 输出头部信息
     */
    public function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }

    	header(sprintf('HTTP/%s %s %s',
                $this->getProtocol(), 
                $this->status, 
                static::getMessageForCode($this->status)
        ), true, $this->status);

        if ($this->noCache) {// 不缓存页面
            $this->noCache();
        }

        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
            	header(sprintf('%s: %s', $name, $value['value']), $value['replace']);
            }
        }
    }

    /**
     * 输出主体
     * 
     * @return void
     */
    public function send()
    {
        echo (string)$this->body;
    }

    /**
     * 响应
     */
    public function respond()
    {
        if ($this->responded) {
            return;
        }

        if ($this->isEmptyResponse()) {
            $this->removeHeader('Content-Type');
            $this->removeHeader('Content-Length');
        }

        $this->sendHeaders();

        // HEAD 方法不需要发送 body
        if (!$this->isHead()) {
            $this->send();
        }
        
        $this->responded = true;
    }

    /**
	 * 重定向
	 *
	 * @param string  $url
	 * @param int     $status
	 */
	public function redirect($url, $status = 302)
    {
	    $this->setStatus($status);
	    $this->header('Location', $url);
	    $this->setBody('');
	    
	    session_write_close();

	    $this->sendHeaders();
        $this->send();
        exit();
	}

    /**
     * 清空
     */
	public function clean()
	{
		$this->headers = array();
		$this->body = '';
	}

    /**
	 * 不缓存的头部设置
	 */
	public function noCache()
    {
	    $stamp = gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME']).' GMT';
	    header('Expires: Tue, 13 Mar 1979 18:00:00 GMT');
	    header('Last-Modified: ' . $stamp);
	    header('Cache-Control: no-store, no-cache, must-revalidate');
	    header('Cache-Control: post-check=0, pre-check=0', false);
	    header('Pragma: no-cache');
	}

    /**
	 * 是否空响应
	 *
	 * @return boolean
	 */
	public function isEmptyResponse()
    {
	    return in_array($this->status, array(204, 205, 304));
	}

    /**
     * 格式化
     *
     * @param  string $name
     * @return string
     */
    protected function normalizeName($name)
    {
        $name = str_replace(array('-', '_'), ' ', (string)$name);
        $name = preg_replace('#^http #i', '', $name);
        $name = ucwords(strtolower($name));
        $name = str_replace(' ', '-', $name);
        return $name;
    }

    /**
     * 获取 HTTP 状态代码对应的消息
     *
     * @param  int         $status
     * @return string|null
     */
    public static function getMessageForCode($status)
    {
        if (isset(static::$messages[$status])) {
            return static::$messages[$status];
        } else {
            return null;
        }
    }

    public function isHead()
    {
        return $this->method() === 'HEAD';
    }

    public function method()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';

        if ($method == 'POST') {
            if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
            } else {
                $method = isset($_POST['_method']) ? strtoupper($_POST['_method']) : $method;
            }
        }

        return $method;
    }

    /**
     * 返回主体
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->body;
    }
}