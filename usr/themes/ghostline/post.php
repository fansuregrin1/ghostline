<?php
/**
 * Ghostline 文章详情
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$this->need('header.php');
?>
<article class="post-full" itemscope itemtype="https://schema.org/BlogPosting">

    <header class="post-header">
        <h1 class="post-title" itemprop="headline"><?php $this->title(); ?></h1>

        <div class="post-meta">
            <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished">
                <?php $this->date('Y 年 m 月 d 日'); ?>
            </time>

            <?php if ($this->category): ?>
                <span class="meta-sep" aria-hidden="true">·</span>
                <span itemprop="articleSection"><?php $this->category(','); ?></span>
            <?php endif; ?>

            <?php if (ghostline_enabled('showReading')): ?>
                <span class="meta-sep" aria-hidden="true">·</span>
                <span><?php echo ghostline_reading_time((string) $this->text); ?> <?php _e('分钟阅读'); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <?php if (ghostline_enabled('showToc')): ?>
        <details class="toc" data-toc hidden>
            <summary><?php _e('目录'); ?></summary>
            <ol class="toc-list" data-toc-list></ol>
        </details>
    <?php endif; ?>

    <div class="post-content" itemprop="articleBody" data-post-content>
        <?php $this->content(); ?>
    </div>

    <?php if ($this->tags): ?>
        <footer class="post-tags">
            <?php $this->tags(' ', true, 'none'); ?>
        </footer>
    <?php endif; ?>
</article>

<?php if (ghostline_enabled('showPrevNext')): ?>
    <nav class="post-nav" aria-label="<?php _e('文章导航'); ?>">
        <div class="post-nav-item post-nav-prev">
            <span class="post-nav-label"><?php _e('上一篇'); ?></span>
            <?php $this->thePrev('%s', '<span class="post-nav-empty">' . _t('没有了') . '</span>'); ?>
        </div>
        <div class="post-nav-item post-nav-next">
            <span class="post-nav-label"><?php _e('下一篇'); ?></span>
            <?php $this->theNext('%s', '<span class="post-nav-empty">' . _t('没有了') . '</span>'); ?>
        </div>
    </nav>
<?php endif; ?>

<?php if (ghostline_enabled('showComments') && $this->allow('comment')): ?>
    <?php $this->need('comments.php'); ?>
<?php endif; ?>

<?php $this->need('footer.php'); ?>
