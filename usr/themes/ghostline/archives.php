<?php
/**
 * 文章归档
 *
 * @package custom
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

// 在页面编辑页的「自定义模板」中选择「文章归档」即可启用。
// 页面正文会被忽略，归档内容自动按年份分组生成。
$this->need('header.php');

// 取全部已发布文章
$archivePosts = [];
$db = \Typecho\Db::get();
$options = \Typecho\Widget::widget('\Widget\Options');

$rows = $db->fetchAll(
    $db->select('table.contents.cid', 'table.contents.title', 'table.contents.slug', 'table.contents.created')
        ->from('table.contents')
        ->where('table.contents.type = ?', 'post')
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.created < ?', $options->time)
        ->where("(table.contents.password IS NULL OR table.contents.password = '')")
        ->order('table.contents.created', \Typecho\Db::SORT_DESC)
);

\Typecho\Router::setRoutes($options->routingTable);

foreach ($rows as $row) {
    $year = date('Y', (int) $row['created'] + (int) $options->timezone - (int) $options->serverTimezone);
    $archivePosts[$year][] = [
        'title' => html_entity_decode((string) $row['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'url'   => \Typecho\Router::url(
            'post',
            ['cid' => $row['cid'], 'slug' => $row['slug']],
            rtrim($options->siteUrl, '/') . ($options->rewrite ? '/' : '/index.php')
        ),
        'date'  => date('m-d', (int) $row['created'] + (int) $options->timezone - (int) $options->serverTimezone),
    ];
}
?>
<div class="layout<?php echo ghostline_enabled('showSidebar') ? ' has-sidebar' : ''; ?>">
    <section class="content-column" id="content">

        <header class="page-intro">
            <h1 class="page-title"><?php $this->title(); ?></h1>
            <p class="page-count">
                <?php _e('共'); ?> <strong><?php echo count($rows); ?></strong> <?php _e('篇文章'); ?>
            </p>
        </header>

        <?php if ($archivePosts): ?>
            <div class="archive-years">
                <?php foreach ($archivePosts as $year => $posts): ?>
                    <details class="archive-year" open>
                        <summary class="archive-year-title">
                            <?php echo htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8'); ?>
                            <span class="archive-year-count"><?php echo count($posts); ?></span>
                        </summary>

                        <ul class="archive-list">
                            <?php foreach ($posts as $post): ?>
                                <li class="archive-item">
                                    <a class="archive-link" href="<?php echo htmlspecialchars($post['url'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <span class="archive-title"><?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <time class="archive-date"><?php echo htmlspecialchars($post['date'], ENT_QUOTES, 'UTF-8'); ?></time>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p class="empty-title"><?php _e('还没有文章'); ?></p>
            </div>
        <?php endif; ?>

        <?php if (ghostline_enabled('showComments') && $this->allow('comment')): ?>
            <?php $this->need('comments.php'); ?>
        <?php endif; ?>
    </section>

    <?php if (ghostline_enabled('showSidebar')): ?>
        <?php $this->need('sidebar.php'); ?>
    <?php endif; ?>
</div>

<?php $this->need('footer.php'); ?>
