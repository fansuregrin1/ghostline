<?php
/**
 * Ghostline — 模板辅助函数
 *
 * 供模板调用的配置读取与数据处理函数
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 读取文章封面图地址
 *
 * @param \Widget\Archive $archive
 * @return string 没有封面图时返回空字符串
 */
function ghostline_cover(\Widget\Archive $archive): string
{
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
 * 首页大标题，留空时回退到站点标题
 *
 * @return string
 */
function ghostline_home_title(): string
{
    $title = (string) ghostline_option('homeTitle', '');

    if ($title !== '') {
        return $title;
    }

    return 'Ghostline';
}

/**
 * 首页副标题，留空时使用默认文案
 *
 * @return string
 */
function ghostline_home_subtitle(): string
{
    $subtitle = (string) ghostline_option('homeSubtitle', '');

    if ($subtitle !== '') {
        return $subtitle;
    }

    return 'A quiet line between ghost and shell.';
}

/**
 * 顶栏站点标题，留空时回退到站点标题
 *
 * @return string
 */
function ghostline_brand_title(): string
{
    $brand = (string) ghostline_option('brandTitle', '');

    if ($brand !== '') {
        return $brand;
    }

    return (string) \Typecho\Widget::widget('\Widget\Options')->title;
}

/**
 * 顶栏 Logo 图片地址
 *
 * @return string 未配置或格式不合法时返回空字符串
 */
function ghostline_brand_logo(): string
{
    $logo = (string) ghostline_option('brandLogo', '');

    if ($logo === '') {
        return '';
    }

    // 支持 http(s)、协议相对地址与站内绝对路径
    if (!preg_match('#^(https?:)?//#i', $logo) && strpos($logo, '/') !== 0) {
        return '';
    }

    return $logo;
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
