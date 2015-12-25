<?php $this->extend('layout.php') ?>

<?php (new \Model\Posts())->to($posts) ?>
<?php while($posts->each()) { ?>
<article class="post">
    <header>
        <h1><a href="<?php echo url_for('post-page', ['pid' => $posts->row('pid')]); ?>"><?php echo $posts->row('title') ?></a></h1>
        <p class="post-meta"><span><?php echo $posts->row('@date_add') ?></span> by <span><?php echo $posts->row('@uid') ?></span></p>
    </header>
    <div class="post-content">
        <?php echo $posts->row('content') ?>
    </div>
    <footer>
        <a href="<?php echo $posts->getEditLink(); ?>">编辑</a>
    </footer>
</article>
<?php } ?>