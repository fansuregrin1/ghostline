<?php
/**
 * Ghostline 首页 / 归档列表
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$this->need('header.php');

$withSidebar = ghostline_enabled('showSidebar');
$isHome = $this->is('index');
?>

<div class="layout<?php echo $withSidebar ? ' has-sidebar' : ''; ?>">
    <section class="content-column" id="content">

        <?php if ($isHome): ?>
            <header class="page-intro">
                <h1 class="page-title"><?php $this->options->title(); ?></h1>
                <?php if ($this->options->description): ?>
                    <p class="page-lead"><?php $this->options->description(); ?></p>
                <?php endif; ?>
            </header>
        <?php else: ?>
            <header class="page-intro">
                <h1 class="page-title"><?php $this->archiveTitle([
                        'category' => _t('%s'),
                        'search'   => _t('搜索：%s'),
                        'tag'      => _t('#%s'),
                        'author'   => _t('%s 的文章'),
                    ], '', ''); ?></h1>
            </header>
        <?php endif; ?>

        <?php if ($this->have()): ?>
            <div class="post-list">
                <?php while ($this->next()): ?>
                    <article class="post-card">
                        <h2 class="post-card-title">
                            <a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a>
                        </h2>

                        <p class="post-card-excerpt"><?php echo ghostline_excerpt($this); ?></p>

                        <footer class="post-card-meta">
                            <time datetime="<?php $this->date('c'); ?>"><?php $this->date('Y-m-d'); ?></time>

                            <?php if ($this->category): ?>
                                <span class="meta-sep" aria-hidden="true">·</span>
                                <span class="meta-category"><?php $this->category(','); ?></span>
                            <?php endif; ?>

                            <?php if (ghostline_enabled('showReading')): ?>
                                <span class="meta-sep" aria-hidden="true">·</span>
                                <span><?php echo ghostline_reading_time((string) $this->text); ?> <?php _e('分钟'); ?></span>
                            <?php endif; ?>

                            <?php if ($this->commentsNum > 0): ?>
                                <span class="meta-sep" aria-hidden="true">·</span>
                                <a class="meta-comments" href="<?php $this->permalink(); ?>#comments">
                                    <?php $this->commentsNum(_t('0 条评论'), _t('1 条评论'), _t('%d 条评论')); ?>
                                </a>
                            <?php endif; ?>
                        </footer>
                    </article>
                <?php endwhile; ?>
            </div>

            <nav class="pagination" aria-label="<?php _e('分页'); ?>">
                <?php $this->pageNav('&larr; ' . _t('上一页'), _t('下一页') . ' &rarr;'); ?>
            </nav>
        <?php else: ?>
            <div class="empty-state">
                <p class="empty-title"><?php _e('这里还什么都没有'); ?></p>
                <p class="empty-text"><?php _e('换一个关键词，或者回到首页看看。'); ?></p>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($withSidebar): ?>
        <?php $this->need('sidebar.php'); ?>
    <?php endif; ?>
</div>

<?php $this->need('footer.php'); ?>
