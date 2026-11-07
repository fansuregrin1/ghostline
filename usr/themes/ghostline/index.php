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
            <?php $homeLayout = ghostline_home_layout(); ?>
            <div class="post-list post-list-<?php echo $homeLayout; ?>">
                <?php while ($this->next()): ?>
                    <?php
                    $cover = ghostline_cover($this);
                    $showCover = $cover && $homeLayout !== 'compact';
                    ?>
                    <?php if ($homeLayout === 'compact'): ?>
                        <article class="post-row">
                            <a class="post-row-link" href="<?php $this->permalink(); ?>">
                                <span class="post-row-title"><?php $this->title(); ?></span>
                                <time class="post-row-date" datetime="<?php $this->date('c'); ?>">
                                    <?php $this->date('Y-m-d'); ?>
                                </time>
                            </a>
                        </article>
                    <?php else: ?>
                        <article class="post-card<?php echo $showCover ? ' has-cover' : ''; ?>">
                            <?php if ($showCover): ?>
                                <a class="post-card-cover" href="<?php $this->permalink(); ?>" tabindex="-1" aria-hidden="true">
                                    <img src="<?php echo htmlspecialchars($cover, ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="" loading="lazy" decoding="async">
                                </a>
                            <?php endif; ?>

                            <div class="post-card-body">
                                <h2 class="post-card-title">
                                    <a href="<?php $this->permalink(); ?>"><?php $this->title(); ?></a>
                                </h2>

                                <p class="post-card-excerpt"><?php echo ghostline_excerpt($this, ghostline_excerpt_length()); ?></p>

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
                            </div>
                        </article>
                    <?php endif; ?>
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
