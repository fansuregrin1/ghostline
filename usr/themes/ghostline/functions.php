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
    /* ---------------- 外观 ---------------- */

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'accentColor',
            null,
            '#2f6feb',
            _t('强调色'),
            _t('用于链接、按钮和高亮的品牌色，建议填写暗蓝色系')
        ))->addRule('regexp', _t('请填写合法的颜色值，例如 #2f6feb'), '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/')
    );

    /* ---------------- 布局 ---------------- */

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Select(
            'homeLayout',
            [
                'card'      => _t('卡片式（含封面图）'),
                'compact'   => _t('紧凑列表（仅标题与日期）'),
                'classic'   => _t('经典式（标题 + 摘要）'),
            ],
            'card',
            _t('首页文章列表样式'),
            _t('卡片式会显示封面图；经典式与之前的样式一致')
        )
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Number(
            'excerptLength',
            null,
            120,
            _t('摘要长度'),
            _t('列表页显示的摘要字符数，建议 80 - 200')
        ))->addRule('isInteger', _t('请填写数字'))
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Number(
            'recentPostCount',
            null,
            5,
            _t('侧栏最新文章数量'),
            _t('侧栏「最新文章」显示几条，建议 3 - 10')
        ))->addRule('isInteger', _t('请填写数字'))
    );

    /* ---------------- 排版 ---------------- */

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Select(
            'fontFamily',
            [
                'sans'  => _t('无衬线（系统默认）'),
                'serif' => _t('衬线（正文更书卷气）'),
            ],
            'sans',
            _t('正文字体'),
            _t('仅影响正文与文章内容，不影响界面文字')
        )
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Select(
            'fontSize',
            [
                'small'  => _t('小（16px）'),
                'normal' => _t('标准（17px）'),
                'large'  => _t('大（18px）'),
                'xlarge' => _t('特大（19px）'),
            ],
            'normal',
            _t('正文字号'),
            _t('影响文章正文的显示大小')
        )
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Select(
            'lineHeight',
            [
                'normal' => _t('标准'),
                'loose'  => _t('宽松（更适合长文阅读）'),
            ],
            'normal',
            _t('正文行高'),
            _t('宽松行高会增加正文行间距')
        )
    );

    /* ---------------- 代码块 ---------------- */

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Select(
            'codeTheme',
            [
                'ghostline' => _t('Ghostline 暗蓝（默认）'),
                'github'    => _t('GitHub Dark'),
                'monokai'   => _t('Monokai'),
            ],
            'ghostline',
            _t('代码高亮配色'),
            _t('切换代码块的语法高亮配色方案')
        )
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Radio(
            'codeWrap',
            [
                '0' => _t('横向滚动'),
                '1' => _t('自动换行'),
            ],
            '0',
            _t('长代码行处理'),
            _t('代码行超长时横向滚动，或自动折行显示')
        )
    );

    /* ---------------- 个人资料 ---------------- */

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'authorName',
            null,
            null,
            _t('博主昵称'),
            _t('显示在文章页作者处，留空则使用文章发布者')
        ))->addRule('maxLength', _t('昵称过长'), 40)
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'authorBio',
            null,
            null,
            _t('博主的个人简介'),
            _t('显示在侧栏顶部，留空则不显示')
        ))->addRule('maxLength', _t('简介过长'), 200)
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'authorAvatarUrl',
            null,
            null,
            _t('博主头像地址'),
            _t('填入图片 URL，留空则不显示')
        ))->addRule('url', _t('请填写合法的 URL 地址'))
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'socialLinks',
            null,
            null,
            _t('社交链接'),
            _t('每行一个，格式：名称|地址，例如：GitHub|https://github.com/your-name')
        ))
    );

    /* ---------------- 页脚 ---------------- */

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
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'icpNumber',
            null,
            null,
            _t('ICP 备案号'),
            _t('例如：京ICP备12345678号，留空则不显示')
        ))->addRule('maxLength', _t('备案号过长'), 60)
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'policeNumber',
            null,
            null,
            _t('公安备案号'),
            _t('例如：京公网安备 11010502030143号，留空则不显示')
        ))->addRule('maxLength', _t('备案号过长'), 60)
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Radio(
            'hidePoweredBy',
            [
                '0' => _t('保留'),
                '1' => _t('隐藏'),
            ],
            '0',
            _t('页脚版权信息'),
            _t('是否保留「由 Typecho 驱动 · 主题 Ghostline」一行')
        )
    );

    /* ---------------- 功能开关 ---------------- */

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Checkbox(
            'features',
            [
                'showSidebar'    => _t('在列表页显示侧栏'),
                'showToc'        => _t('在文章页显示文章目录'),
                'showReading'    => _t('显示预计阅读时间'),
                'showBackTop'    => _t('显示返回顶部按钮'),
                'showPrevNext'   => _t('显示上一篇 / 下一篇'),
                'showComments'   => _t('开放评论区'),
                'showCover'      => _t('在列表页显示文章封面图'),
                'showProgress'   => _t('显示阅读进度条'),
            ],
            [
                'showSidebar', 'showToc', 'showReading', 'showBackTop',
                'showPrevNext', 'showComments', 'showCover', 'showProgress',
            ],
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
        'accentColor'      => '#2f6feb',
        'homeLayout'       => 'card',
        'excerptLength'    => '120',
        'recentPostCount'  => '5',
        'fontFamily'       => 'sans',
        'fontSize'         => 'normal',
        'lineHeight'       => 'normal',
        'codeTheme'        => 'ghostline',
        'codeWrap'         => '0',
        'hidePoweredBy'    => '0',
        'features'         => [
            'showSidebar',
            'showToc',
            'showReading',
            'showBackTop',
            'showPrevNext',
            'showComments',
            'showCover',
            'showProgress',
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
 * 读取主题配置项，未设置时返回默认值
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function ghostline_option(string $key, $default = null)
{
    $options = \Typecho\Widget::widget('\Widget\Options');

    if (!isset($options->{$key})) {
        return $default;
    }

    $value = $options->{$key};

    if (is_string($value)) {
        $value = trim($value);
    }

    return $value === '' || $value === null ? $default : $value;
}

/**
 * 首页列表布局
 *
 * @return string card | compact | classic
 */
function ghostline_home_layout(): string
{
    $layout = ghostline_option('homeLayout', 'card');

    return in_array($layout, ['card', 'compact', 'classic'], true) ? $layout : 'card';
}

/**
 * 摘要长度
 *
 * @return int
 */
function ghostline_excerpt_length(): int
{
    $length = (int) ghostline_option('excerptLength', 120);

    return $length >= 20 && $length <= 400 ? $length : 120;
}

/**
 * 侧栏最新文章数量
 *
 * @return int
 */
function ghostline_recent_count(): int
{
    $count = (int) ghostline_option('recentPostCount', 5);

    return $count >= 1 && $count <= 20 ? $count : 5;
}

/**
 * 解析社交链接配置
 *
 * 每行格式：名称|地址
 *
 * @return array<int, array{name: string, url: string}>
 */
function ghostline_social_links(): array
{
    $raw = (string) ghostline_option('socialLinks', '');

    if ($raw === '') {
        return [];
    }

    $links = [];

    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        $line = trim($line);

        if ($line === '' || strpos($line, '|') === false) {
            continue;
        }

        [$name, $url] = array_map('trim', explode('|', $line, 2));

        if ($name === '' || !preg_match('#^https?://#i', $url)) {
            continue;
        }

        $links[] = ['name' => $name, 'url' => $url];
    }

    return $links;
}

/**
 * 输出内联 CSS 变量（强调色）
 *
 * @return void
 */
function ghostline_accent_style()
{
    $vars = [];

    // 强调色
    $accent = \Typecho\Widget::widget('\Widget\Options')->accentColor;
    $accent = is_string($accent) ? trim($accent) : '';

    if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $accent)) {
        $vars[] = '--accent:' . $accent;
    }

    // 正文字号
    $fontSizes = [
        'small'  => '1rem',
        'normal' => '1.0625rem',
        'large'  => '1.125rem',
        'xlarge' => '1.1875rem',
    ];
    $size = ghostline_option('fontSize', 'normal');
    if (isset($fontSizes[$size])) {
        $vars[] = '--post-font-size:' . $fontSizes[$size];
    }

    // 正文行高
    $lineHeights = [
        'normal' => '1.85',
        'loose'  => '2.05',
    ];
    $lh = ghostline_option('lineHeight', 'normal');
    if (isset($lineHeights[$lh])) {
        $vars[] = '--post-line-height:' . $lineHeights[$lh];
    }

    // 正文字体
    if (ghostline_option('fontFamily', 'sans') === 'serif') {
        $vars[] = '--font-serif: "Songti SC", "Source Han Serif SC", '
            . '"Noto Serif SC", Georgia, "Times New Roman", serif';
        $vars[] = '--post-font-family: var(--font-serif)';
    }

    // 代码块换行
    if ((string) ghostline_option('codeWrap', '0') === '1') {
        $vars[] = '--code-white-space: pre-wrap';
        $vars[] = '--code-word-break: break-word';
    }

    if ($vars) {
        echo ':root{' . implode(';', $vars) . ';}';
    }
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
