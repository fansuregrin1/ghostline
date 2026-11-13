<?php
/**
 * Ghostline 404
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$this->need('header.php');
?>
<section class="notfound">
    <p class="notfound-code">404</p>
    <h1 class="notfound-title"><?php _e('页面走丢了'); ?></h1>
    <p class="notfound-text"><?php _e('你想找的内容可能已被移动或删除。'); ?></p>

    <form class="notfound-search" method="post" action="<?php $this->options->siteUrl(); ?>" role="search">
        <label class="sr-only" for="notfound-s"><?php _e('搜索关键字'); ?></label>
        <input id="notfound-s" type="search" name="s" placeholder="<?php _e('搜索一下'); ?>" autofocus required>
        <button type="submit"><?php _e('搜索'); ?></button>
    </form>

    <p><a class="link-muted" href="<?php $this->options->siteUrl(); ?>"><?php _e('返回首页'); ?></a></p>
</section>

<?php $this->need('footer.php'); ?>
