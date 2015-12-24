# Lime

Lime 是一个 PHP 的微框架, 帮助你快速构建简单但强大的 RESTful 风格的网页应用和 API。它提供路由(routing)、依赖注入(dependency injection)、中间件(middleware)、视图继承、视图片段和安全 cookies 等功能。

## 系统需求

* PHP >= 5.4.0

## 用法

一个 Lime 应用主要包含三部分，生成 Lime 应用实例，定义路由，执行应用。

```php
// 加载 Lime 框架
require 'Lime/Lime.php';

// 生成一个 Lime 应用实例
$app = new \Lime\Lime();

// 定义一个 HTTP GET 请求路由：
$app->get('/', function() {
    echo 'hello world';
});

// 执行 Lime 应用
$app->run();
```

## 中文文档

[Documentation](http://icodechef.github.io/docs)

## License

The Lime Framework is licensed under the MIT license. See [License File](https://github.com/icodechef/Lime/blob/master/LICENSE) for more information.