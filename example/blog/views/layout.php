<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php $this->with('title', 'Lime 博客'); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo url_base() ?>/assets/css/style.css">
    <?php $this->with('css'); ?>
</head>
<body>
    <header class="blog-masthead">
        <div class="container">
            <nav class="blog-nav">
                <a class="active" href="<?php echo url_base(); ?>">首页</a>
                <a href="<?php echo url_base(); ?>/about">关于我</a>
                <a href="<?php echo url_base(); ?>/404">404 页面</a>
                <?php if (empty($_SESSION['uid'])) { ?>
                <a href="<?php echo url_base(); ?>/login">登录</a>
                <?php } else { ?>
                <a href="<?php echo url_base(); ?>/post/new">添加文章</a>
                <a href="<?php echo url_base(); ?>/logout">退出</a>
                <?php } ?>
            </nav>
        </div>
    </header>
    <div class="container content">
        <?php $this->content(); ?>
    </div>
    <footer class="blog-footer">
        <p>Powered by <a href="https://github.com/icodechef/Lime">Lime</a>.</p>
    </footer>
</body>
</html>