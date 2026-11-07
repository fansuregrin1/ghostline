<?php
/**
 * Ghostline 公共头部
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0b1220">
    <title><?php $this->archiveTitle([
            'category' => _t('分类 %s'),
            'search'   => _t('搜索 %s'),
            'tag'      => _t('标签 %s'),
            'author'   => _t('%s 的文章'),
        ], '', ' — '); ?><?php $this->options->title(); ?></title>

    <link rel="stylesheet" href="<?php $this->options->themeUrl('style.css'); ?>">
    <style><?php ghostline_accent_style(); ?></style>

    <?php $this->header(); ?>

    <script>
        // 在首屏渲染前应用已保存的配色偏好，避免闪烁
        (function () {
            try {
                var saved = localStorage.getItem('ghostline-theme');
                if (saved === 'light' || saved === 'dark') {
                    document.documentElement.setAttribute('data-theme', saved);
                }
            } catch (e) {}
        })();
    </script>
</head>
<body>
<a class="skip-link" href="#main"><?php _e('跳到正文'); ?></a>

<header class="site-header">
    <div class="shell header-inner">
        <a class="site-brand" href="<?php $this->options->siteUrl(); ?>">
            <span class="brand-mark" aria-hidden="true"></span>
            <span class="brand-name"><?php $this->options->title(); ?></span>
        </a>

        <nav class="site-nav" aria-label="<?php _e('主导航'); ?>">
            <a<?php if ($this->is('index')): ?> class="is-current" aria-current="page"<?php endif; ?>
               href="<?php $this->options->siteUrl(); ?>"><?php _e('首页'); ?></a>

            <?php \Widget\Contents\Page\Rows::alloc()->to($pages); ?>
            <?php while ($pages->next()): ?>
                <a<?php if ($this->is('page', $pages->slug)): ?> class="is-current" aria-current="page"<?php endif; ?>
                   href="<?php $pages->permalink(); ?>"><?php $pages->title(); ?></a>
            <?php endwhile; ?>

            <button class="icon-button nav-search-toggle" type="button" aria-label="<?php _e('搜索'); ?>"
                    aria-haspopup="dialog" aria-expanded="false" aria-controls="search-dialog"
                    data-search-toggle>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M20 20l-3.5-3.5"></path>
                </svg>
            </button>

            <button class="icon-button theme-toggle" type="button" aria-label="<?php _e('切换配色'); ?>"
                    data-theme-toggle>
                <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"></circle>
                    <path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path>
                </svg>
                <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path>
                </svg>
            </button>
        </nav>
    </div>
</header>

<dialog class="search-dialog" id="search-dialog" aria-label="<?php _e('搜索'); ?>" data-search-dialog>
    <form class="search-dialog-form" method="post" action="<?php $this->options->siteUrl(); ?>" role="search"
          data-search-form data-api="<?php $this->options->themeUrl('search-api.php'); ?>">
        <div class="search-dialog-field">
            <svg class="search-dialog-icon" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="M20 20l-3.5-3.5"></path>
            </svg>
            <label class="sr-only" for="search-dialog-input"><?php _e('搜索关键字'); ?></label>
            <input id="search-dialog-input" type="search" name="s" autocomplete="off"
                   placeholder="<?php _e('搜索文章…'); ?>" required data-search-input
                   role="combobox" aria-expanded="false" aria-controls="search-results"
                   aria-autocomplete="list">
            <button type="button" class="search-dialog-close" aria-label="<?php _e('关闭搜索'); ?>"
                    data-search-close>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>
        </div>

        <div class="search-results" id="search-results" role="listbox"
             aria-label="<?php _e('搜索结果'); ?>" data-search-results hidden></div>

        <div class="search-results-footer" data-search-footer hidden></div>

        <div class="search-dialog-hint">
            <span data-search-hint-text><?php _e('输入关键词开始搜索'); ?></span>
            <span class="search-dialog-kbd">
                <kbd>↑</kbd><kbd>↓</kbd> <?php _e('选择'); ?>
                <kbd>Esc</kbd> <?php _e('关闭'); ?>
            </span>
        </div>
    </form>
</dialog>

<main id="main" class="shell site-main">
