<?php
/**
 * Lime blog
 *
 * 此程序只是用于展示 Lime 的功能，并不能用于实际应用
 */
// 加载 Lime 框架
require '../../Lime/Lime.php';

session_start();

date_default_timezone_set('PRC');

// 生成一个 Lime 应用实例
$app = new \Lime\Lime([
    'resource.path' => __DIR__ . '/usr',
    'views.path'    => __DIR__ . '/views',
]);

// 注入服务
$app->service('database',
    [
        'className' => '\Lime\Pdo',
        'arguments' => [
            [
                'dsn'        => 'mysql:host=127.0.0.1;dbname=blog',
                'username'   => 'root',
                'password'   => '123456',
                'charset'    => 'utf8',
                'prefix'     => 'demo_',
            ],
        ],
    ]
);

// 中间件
$app->middleware('notFoundHandler', function($e) {
    return view('404.php', ['exception' => $e]);
});

// 定义请求路由：
$app->get('/', function() {
    return view('index.php'); // view 辅助函数
});

// 资源式路由
$app->get('/about', '\Controller\About@index');

$app->get('/login', function() {
    return view('login.php');
})->filter(function() {// 路由过滤
    if (! empty($_SESSION['uid'])) {
        response()->redirect(url_base());// 跳转
    }
});

$app->post('/login', function() {
    $username = request()->filter('trim', 'strip_tags')->input('username');
    $password = request()->input('password');

    $User = new \Model\User(); // 模型

    $uid = $User->login($username, $password);

    if ($uid) {
        $_SESSION['uid'] = $uid;

        response()->redirect(url_base());
    }

    // 视图数据
    return view('login.php', [
        'error' => '用户不存在或者密码不匹配.'
    ]);
});

$app->get('/logout', function() {
    $_SESSION['uid'] = '';
    unset($_SESSION['uid']);

    response()->redirect(url_base() . '/login');
});

// 路由分组
$app->group('/post', function() {
    $this->get('/new', function() {
        return view('post.php');
    });

    $this->post('/new', function() {
        $title = request()->filter('trim', 'strip_tags')->input('title');
        $content = request()->filter('trim', 'strip_tags')->input('content');

        $error = '';

        if (empty($title)) {
            $error = '标题不能为空';
        } elseif (empty($content)) {
            $error = '内容不能为空';
        } else {
            $pid = (new \Model\Post())->add($title, $content);

            if ($pid) {
                response()->redirect(url_base() . '/post/' . $pid);
            } else {
                $error = '添加文章失败';
            }
        }

        return view('post.php', [
            'error' => $error,
        ]);
    });

    // 为多种请求注册路由
    $this->map(['GET', 'POST'], '/edit/<pid>', function() {
        $error = '';

        $pid = request()->filter('intval')->params('pid');

        $post = (new \Model\Post())->get($pid);

        if (! $post) { // Not found 异常
            throw new \Lime\Exception\NotFoundException("Post not found");
        }

        if (request()->isPost() && request()->input('edit')) {
            $title = request()->filter('trim', 'strip_tags')->input('title');
            $content = request()->filter('trim', 'strip_tags')->input('content');

            if (empty($title)) {
                $error = '标题不能为空';
            } elseif (empty($content)) {
                $error = '内容不能为空';
            } else {
                (new \Model\Post())->update($pid, $title, $content);
                response()->redirect(url_base() . '/post/' . $pid);
            }
        }

        return view('post-edit.php', [
            'post' => $post,
            'error' => $error,
        ]);
    })->conditions(['pid' => '[0-9]+'])->name('post-edit');

}, function() { // 路由分组的过滤
    if (empty($_SESSION['uid'])) {
        response()->redirect(url_base() . '/login');
    }
});

// 路由参数
$app->get('/post/<pid>', function() {
    $pid = request()->filter('intval')->params('pid');
    $post = (new \Model\Post())->get($pid);

    if (! $post) {
        throw new \Lime\Exception\NotFoundException("Post not found");
        
    }

    return view('content.php', [
        'post' => $post,
    ]);
})->conditions(['pid' => '[0-9]+'])->name('post-page'); // 使用条件限制数组 和 路由命名

// 执行 Lime 应用
$app->run();