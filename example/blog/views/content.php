<?php $this->extend('layout.php') ?>

<article class="post">
    <header>
        <h1><?php echo $post['title'] ?></h1>
        <p class="post-meta"><span><?php echo $post['date_add'] ?></span> by <span><?php echo $post['username'] ?></span></p>
    </header>
    <div class="post-content">
        <?php echo $post['content'] ?>
    </div>
</article>

<?php $this->section('title', $post['title']); ?>