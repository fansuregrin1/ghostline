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

<?php if (($this->is('post') || $this->is('page')) && ghostline_enabled('showProgress')): ?>
    <div class="reading-progress" data-reading-progress hidden>
        <span class="reading-progress-bar" data-reading-progress-bar></span>
    </div>
<?php endif; ?>

<footer class="site-footer">
    <div class="shell footer-inner">
        <p class="copyright">
            &copy; <?php echo date('Y'); ?>
            <a href="<?php $options->siteUrl(); ?>"><?php $options->title(); ?></a>
            <span class="footer-sep" aria-hidden="true">·</span>
            <a class="footer-rss" href="<?php $options->feedUrl(); ?>" rel="alternate" type="application/rss+xml">
                <svg viewBox="0 0 24 24" aria-hidden="true" aria-label="<?php _e('RSS 订阅'); ?>">
                    <circle cx="6" cy="18" r="1.6"></circle>
                    <path d="M5 12a7 7 0 0 1 7 7M4 5a15 15 0 0 1 15 15"></path>
                </svg>
                <?php _e('RSS'); ?>
            </a>
        </p>

        <?php
        $icp = ghostline_option('icpNumber', '');
        $police = ghostline_option('policeNumber', '');
        ?>

        <?php if ($icp): ?>
            <p class="footer-license">
                <a href="https://beian.miit.gov.cn/" rel="noopener nofollow" target="_blank">
                    <?php echo htmlspecialchars($icp, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </p>
        <?php endif; ?>

        <?php if ($police): ?>
            <p class="footer-license">
                <a href="https://beian.mps.gov.cn/" rel="noopener nofollow" target="_blank">
                    <?php echo htmlspecialchars($police, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </p>
        <?php endif; ?>

        <?php if ($options->footerText): ?>
            <div class="footer-note"><?php echo $options->footerText; ?></div>
        <?php endif; ?>

        <?php if ((string) ghostline_option('hidePoweredBy', '0') !== '1'): ?>
            <p class="powered">
                <?php _e('由'); ?>
                <a href="https://typecho.org" rel="noopener" target="_blank">Typecho</a>
                <?php _e('驱动 · 主题'); ?> Ghostline
            </p>
        <?php endif; ?>
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
