<?php
/**
 * Ghostline 侧栏
 *
 * 每个模块使用 <details> 包裹：
 * - 桌面端通过 CSS 强制展开，外观与普通侧栏一致
 * - 移动端默认收起，点开才显示内容，避免堆叠成一大片
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
?>
<aside class="sidebar" id="secondary" aria-label="<?php _e('侧边栏'); ?>">

    <details class="widget" open>
        <summary class="widget-title"><?php _e('最新文章'); ?></summary>
        <ul class="widget-list">
            <?php \Widget\Contents\Post\Recent::alloc('pageSize=5')
                ->parse('<li><a href="{permalink}">{title}</a></li>'); ?>
        </ul>
    </details>

    <details class="widget">
        <summary class="widget-title"><?php _e('分类'); ?></summary>
        <?php \Widget\Metas\Category\Rows::alloc()->listCategories('wrapClass=widget-list'); ?>
    </details>

    <details class="widget">
        <summary class="widget-title"><?php _e('标签'); ?></summary>
        <div class="tag-cloud">
            <?php \Widget\Metas\Tag\Cloud::alloc('sort=count&desc=1&limit=20')->to($tags); ?>
            <?php if ($tags->have()): ?>
                <?php while ($tags->next()): ?>
                    <a class="tag" href="<?php $tags->permalink(); ?>"><?php $tags->name(); ?></a>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </details>

    <details class="widget">
        <summary class="widget-title"><?php _e('归档'); ?></summary>
        <ul class="widget-list widget-archive">
            <?php \Widget\Contents\Post\Date::alloc('type=month&format=Y 年 m 月&limit=8')
                ->parse('<li><a href="{permalink}">{date}</a></li>'); ?>
        </ul>
    </details>
</aside>
