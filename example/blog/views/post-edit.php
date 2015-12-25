<?php $this->extend('layout.php') ?>

<?php if (isset($error)) { ?>
<p class="alert"><?php echo $error ?></p>
<?php } ?>

<form class="form-post" method="post" action="<?php echo url_base() ?>/post/edit/<?php echo $post['pid'] ?>">
    <h2>编辑文章</h2>
    <label for="title" class="sr-only">标题</label>
    <input type="text" id="title" name="title" class="form-control" value="<?php echo request()->input('title', $post['title']) ?>" placeholder="标题">
    <label for="content" class="sr-only">文章内容</label>
    <textarea id="content" name="content" placeholder="输入文章内容" rows="10"><?php echo request()->input('content', $post['content']) ?></textarea>
    <button class="btn btn-primary" type="submit" name="edit" value="1">发布</button>
</form>

<?php $this->section('title', '编辑文章'); ?>

<?php $this->section('css'); // 开始定义一个视图片段 ?>
<style type="text/css">
input[name="title"] {
    width: 100%;
}
</style>
<?php $this->end(); // 结束定义一个视图片段 ?>