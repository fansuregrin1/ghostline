<?php
/**
 * Ghostline 公共页脚
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$options = $this->options;
?>
</main>

<footer class="site-footer">
    <div class="shell footer-inner">
        <p class="copyright">
            &copy; <?php echo date('Y'); ?>
            <a href="<?php $options->siteUrl(); ?>"><?php $options->title(); ?></a>
        </p>

        <?php if ($options->footerText): ?>
            <div class="footer-note"><?php echo $options->footerText; ?></div>
        <?php endif; ?>

        <p class="powered">
            <?php _e('由'); ?>
            <a href="https://typecho.org" rel="noopener" target="_blank">Typecho</a>
            <?php _e('驱动 · 主题'); ?> Ghostline
        </p>
    </div>
</footer>

<?php if (ghostline_enabled('showBackTop')): ?>
    <button class="back-top" type="button" aria-label="<?php _e('返回顶部'); ?>" data-back-top hidden>
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 19V5M5 12l7-7 7 7"></path>
        </svg>
    </button>
<?php endif; ?>

<?php
/**
 * 仅在文章 / 独立页面加载高亮库，避免其他页面无谓请求。
 * 必须排在 main.js 之前，因为 main.js 会立即调用 window.hljs。
 */
$needHighlight = ($this->is('post') || $this->is('page'));
?>
<?php if ($needHighlight): ?>
    <script src="<?php $options->themeUrl('assets/js/vendor/highlight.min.js'); ?>" defer></script>
<?php endif; ?>

<script src="<?php $options->themeUrl('assets/js/main.js'); ?>" defer></script>

<?php $this->footer(); ?>
</body>
</html>
