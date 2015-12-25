<?php $this->extend('layout.php') ?>

<?php if (isset($error)) { ?>
<p class="alert"><?php echo $error ?></p>
<?php } ?>

<form class="form-signin" method="post" action="<?php echo url_base() ?>/login">
    <h2>用户登录</h2>
    <label for="username" class="sr-only">用户名</label>
    <input type="text" id="username" name="username" class="form-control" placeholder="用户名">
    <label for="password" class="sr-only">密码</label>
    <input type="password" id="password" name="password" class="form-control" placeholder="密码">
    <button class="btn btn-primary" type="submit">登录</button>
</form>

<blockquote>
    <p>测试账号：admin，密码：admin</p>
</blockquote>

<?php $this->section('title', '用户登录'); ?>