<?php
/**
 * Ghostline 日期归档
 *
 * 访问 /2026/ 或 /2026/11/ 时使用，与分类、标签页保持一致的紧凑列表。
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$this->need('header.php');

$withSidebar = ghostline_enabled('showSidebar');
?>
<div class="layout<?php echo $withSidebar ? ' has-sidebar' : ''; ?>">
    <section class="content-column" id="content">

        <header class="page-intro">
            <p class="page-kicker"><?php _e('归档'); ?></p>
            <h1 class="page-title"><?php $this->archiveTitle([
                    'archive_year'       => _t('%s 年的文章'),
                    'archive_year_page'  => _t('%s 年的文章'),
                    'archive_month'      => _t('%s 的文章'),
                    'archive_month_page' => _t('%s 的文章'),
                    'archive_day'        => _t('%s 的文章'),
                    'archive_day_page'   => _t('%s 的文章'),
                ], '', ''); ?></h1>

            <p class="page-count">
                <?php _e('共'); ?> <strong><?php echo $this->getTotal(); ?></strong> <?php _e('篇文章'); ?>
            </p>
        </header>

        <?php if ($this->have()): ?>
            <ul class="archive-list">
                <?php while ($this->next()): ?>
                    <li class="archive-item">
                        <a class="archive-link" href="<?php $this->permalink(); ?>">
                            <span class="archive-title"><?php $this->title(); ?></span>
                            <time class="archive-date" datetime="<?php $this->date('c'); ?>">
                                <?php $this->date('Y-m-d'); ?>
                            </time>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>

            <nav class="pagination" aria-label="<?php _e('分页'); ?>">
                <?php $this->pageNav('&larr; ' . _t('上一页'), _t('下一页') . ' &rarr;'); ?>
            </nav>
        <?php else: ?>
            <div class="empty-state">
                <p class="empty-title"><?php _e('这段时间还没有文章'); ?></p>
                <p class="empty-text"><?php _e('换个时间段看看，或者回到首页。'); ?></p>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($withSidebar): ?>
        <?php $this->need('sidebar.php'); ?>
    <?php endif; ?>
</div>

<?php $this->need('footer.php'); ?>
