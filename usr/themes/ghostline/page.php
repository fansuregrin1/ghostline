<?php
/**
 * Ghostline 独立页面
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$this->need('header.php');
?>
<article class="post-full post-page" itemscope itemtype="https://schema.org/CreativeWork">
    <header class="post-header">
        <h1 class="post-title" itemprop="headline"><?php $this->title(); ?></h1>
        <div class="post-meta">
            <time datetime="<?php $this->date('c'); ?>"><?php $this->date('Y 年 m 月 d 日'); ?></time>
        </div>
    </header>

    <div class="post-content" itemprop="text" data-post-content>
        <?php $this->content(); ?>
    </div>
</article>

<?php if (ghostline_enabled('showComments') && $this->allow('comment')): ?>
    <?php $this->need('comments.php'); ?>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
