<?php

// 加载 Lime 框架
require '../../Lime/Lime.php';

// 生成一个 Lime 应用实例
$app = new \Lime\Lime();

// 定义一个 HTTP GET 请求路由：
$app->get('/', function() {
    echo 'hello world';
});

// 定义一个 HTTP GET 请求路由：
$app->get('/hello(/<name>)', function($name = 'world') {
    echo 'hello ', $name;
});

// 执行 Lime 应用
$app->run();