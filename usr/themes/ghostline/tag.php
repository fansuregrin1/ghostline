<?php
/**
 * Ghostline 标签归档
 *
 * 与首页的文章卡片不同，标签页使用紧凑的标题列表，
 * 便于在一个标签下快速浏览较多文章。
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
            <p class="page-kicker"><?php _e('标签'); ?></p>
            <h1 class="page-title"><?php $this->archiveTitle('', '', ''); ?></h1>

            <?php if ($this->getDescription()): ?>
                <p class="page-lead"><?php $this->getDescription(); ?></p>
            <?php endif; ?>

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
                <p class="empty-title"><?php _e('这个标签下还没有文章'); ?></p>
                <p class="empty-text"><?php _e('换个标签逛逛，或者回到首页。'); ?></p>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($withSidebar): ?>
        <?php $this->need('sidebar.php'); ?>
    <?php endif; ?>
</div>

<?php $this->need('footer.php'); ?>
