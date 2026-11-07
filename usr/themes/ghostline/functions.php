<?php
/**
 * Ghostline — a quiet line between ghost and shell.
 *
 * 主题配置与辅助函数
 *
 * @package Ghostline
 * @author Ghostline
 * @version 0.1.0
 * @link https://github.com/fansuregrin1/ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 后台「设置外观」表单
 *
 * @param \Typecho\Widget\Helper\Form $form
 * @return void
 */
function themeConfig($form)
{
    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'accentColor',
            null,
            '#2f6feb',
            _t('强调色'),
            _t('用于链接、按钮和高亮的品牌色，建议填写暗蓝色系')
        ))->addRule('color', _t('请填写合法的颜色值'))
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'footerText',
            null,
            null,
            _t('页脚自定义文字'),
            _t('显示在页脚版权信息下方，支持 HTML，留空则不显示')
        ))->addRule('maxLength', _t('文字过长'), 240)
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Checkbox(
            'features',
            [
                'showSidebar'  => _t('在列表页显示侧栏'),
                'showToc'      => _t('在文章页显示文章目录'),
                'showReading'  => _t('显示预计阅读时间'),
                'showBackTop'  => _t('显示返回顶部按钮'),
                'showPrevNext' => _t('显示上一篇 / 下一篇'),
                'showComments' => _t('开放评论区'),
                'showCover'    => _t('在列表页显示文章封面图'),
            ],
            ['showSidebar', 'showToc', 'showReading', 'showBackTop', 'showPrevNext', 'showComments', 'showCover'],
            _t('功能开关')
        )->multiMode()
    );
}

/**
 * 文章编辑页的「自定义字段」
 *
 * 目前提供一个封面图地址，用于在列表页展示缩略图。
 *
 * @param \Typecho\Widget\Helper\Layout $layout
 * @return void
 */
function themeFields($layout)
{
    $cover = new \Typecho\Widget\Helper\Form\Element\Text(
        'cover',
        null,
        null,
        _t('封面图地址'),
        _t('填入图片 URL，会显示在列表页。留空则使用纯文字样式'),
        _t('显示在文章列表中的缩略图')
    );

    $cover->setAttribute('class', 'text w-100');

    $layout->addItem($cover);
}

/**
 * 读取文章封面图地址
 *
 * @param \Widget\Archive $archive
 * @return string 没有封面图时返回空字符串
 */
function ghostline_cover(\Widget\Archive $archive): string
{
    if (!ghostline_enabled('showCover')) {
        return '';
    }

    $cover = '';

    try {
        if (isset($archive->fields->cover) && is_string($archive->fields->cover)) {
            $cover = trim($archive->fields->cover);
        }
    } catch (\Throwable $e) {
        return '';
    }

    if ($cover === '') {
        return '';
    }

    // 只接受 http(s)、协议相对地址，或站内绝对路径
    if (!preg_match('#^(https?:)?//#i', $cover) && strpos($cover, '/') !== 0) {
        return '';
    }

    return $cover;
}

/**
 * 主题首次启用时写入默认配置
 *
 * Typecho 只在用户保存「设置外观」后才写入 theme:xxx 配置项。
 * 这里补一次默认值，避免首次启用时功能开关全部为空、界面过于简陋。
 *
 * @param \Widget\Options $options
 * @return void
 */
function themeInit($options = null)
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;

    $options = \Typecho\Widget::widget('\Widget\Options');
    $theme = (string) $options->theme;

    if ('' === $theme) {
        return;
    }

    // 已有配置（含用户保存过的）则不做任何事
    if (isset($options->{'theme:' . $theme})) {
        return;
    }

    $db = \Typecho\Db::get();
    $key = 'theme:' . $theme;

    $defaults = [
        'accentColor' => '#2f6feb',
        'features'    => [
            'showSidebar',
            'showToc',
            'showReading',
            'showBackTop',
            'showPrevNext',
            'showComments',
            'showCover',
        ],
    ];

    \Typecho\Db::get()->query(
        \Typecho\Db::get()->insert('table.options')->rows([
            'name'  => $key,
            'user'  => 0,
            'value' => json_encode($defaults),
        ])
    );
}

/**
 * 主题是否启用某项功能
 *
 * @param string $key
 * @return bool
 */
function ghostline_enabled(string $key): bool
{
    $features = \Typecho\Widget::widget('\Widget\Options')->features;

    if (empty($features)) {
        return false;
    }

    return in_array($key, (array) $features, true);
}

/**
 * 输出内联 CSS 变量（强调色）
 *
 * @return void
 */
function ghostline_accent_style()
{
    $accent = \Typecho\Widget::widget('\Widget\Options')->accentColor;
    $accent = is_string($accent) ? trim($accent) : '';

    if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $accent)) {
        return;
    }

    echo ':root{--accent:' . $accent . ';}';
}

/**
 * 估算阅读时间（分钟）
 *
 * @param string $text
 * @return int
 */
function ghostline_reading_time(string $text): int
{
    $chars = mb_strlen(strip_tags($text));

    return max(1, (int) ceil($chars / 400));
}

/**
 * 提取文章摘要：优先使用 <!--more--> 之前的内容，否则截断正文
 *
 * @param \Widget\Archive $archive
 * @param int $length
 * @return string
 */
function ghostline_excerpt(\Widget\Archive $archive, int $length = 120): string
{
    $text = (string) $archive->text;

    if (preg_match('/<!--\s*more\s*-->/i', $text)) {
        $text = preg_split('/<!--\s*more\s*-->/i', $text, 2)[0];
    }

    // 去掉代码块，避免摘要里出现大段代码
    $text = preg_replace('/```[\s\S]*?```/', '', $text);
    $text = strip_tags((string) $text);
    // 去掉常见 Markdown 标记，避免摘要里出现 ## ** 等符号
    $text = preg_replace('/<!--\s*markdown\s*-->/i', '', $text);
    $text = preg_replace('/^\s{0,3}#{1,6}\s*/mu', '', $text);
    $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $text);
    $text = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $text);
    $text = str_replace(['**', '__', '`', '> ', '* ', '- '], '', $text);
    $text = trim(preg_replace('/\s+/u', ' ', $text));

    if (mb_strlen($text) > $length) {
        $text = mb_substr($text, 0, $length) . '…';
    }

    return $text;
}
