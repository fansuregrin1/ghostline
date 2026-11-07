<?php
/**
 * Ghostline 侧栏
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
?>
<aside class="sidebar" id="secondary" aria-label="<?php _e('侧边栏'); ?>">

    <section class="widget">
        <h2 class="widget-title"><?php _e('搜索'); ?></h2>
        <form class="widget-search" method="post" action="<?php $this->options->siteUrl(); ?>" role="search">
            <label class="sr-only" for="sidebar-search"><?php _e('搜索关键字'); ?></label>
            <input id="sidebar-search" type="search" name="s" placeholder="<?php _e('输入关键词'); ?>" required>
            <button type="submit" aria-label="<?php _e('搜索'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M20 20l-3.5-3.5"></path>
                </svg>
            </button>
        </form>
    </section>

    <section class="widget">
        <h2 class="widget-title"><?php _e('最新文章'); ?></h2>
        <ul class="widget-list">
            <?php \Widget\Contents\Post\Recent::alloc('pageSize=5')
                ->parse('<li><a href="{permalink}">{title}</a></li>'); ?>
        </ul>
    </section>

    <section class="widget">
        <h2 class="widget-title"><?php _e('分类'); ?></h2>
        <?php \Widget\Metas\Category\Rows::alloc()->listCategories('wrapClass=widget-list'); ?>
    </section>

    <section class="widget">
        <h2 class="widget-title"><?php _e('标签'); ?></h2>
        <div class="tag-cloud">
            <?php \Widget\Metas\Tag\Cloud::alloc('sort=count&desc=1&limit=20')->to($tags); ?>
            <?php if ($tags->have()): ?>
                <?php while ($tags->next()): ?>
                    <a class="tag" href="<?php $tags->permalink(); ?>"><?php $tags->name(); ?></a>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="widget">
        <h2 class="widget-title"><?php _e('归档'); ?></h2>
        <ul class="widget-list widget-archive">
            <?php \Widget\Contents\Post\Date::alloc('type=month&format=Y 年 m 月&limit=8')
                ->parse('<li><a href="{permalink}">{date}</a></li>'); ?>
        </ul>
    </section>
</aside>
