<?php
/**
 * Ghostline — 配色计算
 *
 * 预设色板、暗色适配与 CSS 变量输出
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 预设配色方案
 *
 * 每项包含亮色与暗色两个值：暗色模式下提亮，保证深色背景上的对比度。
 *
 * @return array<string, array{label: string, light: string, dark: string}>
 */
function ghostline_accent_palette(): array
{
    return [
        'blue'   => ['label' => _t('幽灵蓝（默认）'), 'light' => '#2f6feb', 'dark' => '#5b8def'],
        'teal'   => ['label' => _t('深海青'),        'light' => '#0d9488', 'dark' => '#2dd4bf'],
        'violet' => ['label' => _t('赛博紫'),        'light' => '#7c3aed', 'dark' => '#a78bfa'],
        'cyan'   => ['label' => _t('暗夜青绿'),      'light' => '#0891b2', 'dark' => '#22d3ee'],
        'rose'   => ['label' => _t('玫瑰红'),        'light' => '#e11d48', 'dark' => '#fb7185'],
        'amber'  => ['label' => _t('琥珀橙'),        'light' => '#ea580c', 'dark' => '#fb923c'],
        'slate'  => ['label' => _t('石墨灰'),        'light' => '#475569', 'dark' => '#94a3b8'],
        'pink'   => ['label' => _t('樱花粉'),        'light' => '#db2777', 'dark' => '#f472b6'],
    ];
}

/**
 * 把十六进制颜色提亮，用于暗色模式
 *
 * 做法：转 HSL，把亮度抬到至少 62%、饱和度略增，再转回 RGB。
 *
 * @param string $hex
 * @return string
 */
function ghostline_lighten_color(string $hex): string
{
    if (!preg_match('/^#?([0-9a-f]{6})$/i', $hex, $m)) {
        return $hex;
    }

    $value = $m[1];
    $r = hexdec(substr($value, 0, 2)) / 255;
    $g = hexdec(substr($value, 2, 2)) / 255;
    $b = hexdec(substr($value, 4, 2)) / 255;

    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l = ($max + $min) / 2;
    $h = 0.0;
    $s = 0.0;

    if ($max !== $min) {
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        if ($max === $r) {
            $h = 60 * fmod(($g - $b) / $d, 6);
        } elseif ($max === $g) {
            $h = 60 * (($b - $r) / $d + 2);
        } else {
            $h = 60 * (($r - $g) / $d + 4);
        }

        if ($h < 0) {
            $h += 360;
        }
    }

    // 计算源色亮度（0-255），用于决定提亮程度
    $lSource = hexdec(substr($value, 0, 2)) * 0.299
        + hexdec(substr($value, 2, 2)) * 0.587
        + hexdec(substr($value, 4, 2)) * 0.114;

    // 低饱和（接近灰）的颜色保持低饱和，避免暗色下变成莫名其妙的彩色
    if ($s < 0.12) {
        $targetL = $lSource < 96 ? 0.72 : 0.68;
        $targetS = min($s, 0.08);

        $gray = (int) round($targetL * 255);

        return sprintf('#%02x%02x%02x', $gray, $gray, $gray);
    }

    $targetL = $lSource < 96 ? 0.68 : 0.63;
    $targetS = min(1.0, max(0.35, $s * 1.05));

    // HSL -> RGB
    $c = (1 - abs(2 * $targetL - 1)) * $targetS;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m2 = $targetL - $c / 2;

    if ($h < 60) {
        [$rr, $gg, $bb] = [$c, $x, 0];
    } elseif ($h < 120) {
        [$rr, $gg, $bb] = [$x, $c, 0];
    } elseif ($h < 180) {
        [$rr, $gg, $bb] = [0, $c, $x];
    } elseif ($h < 240) {
        [$rr, $gg, $bb] = [0, $x, $c];
    } elseif ($h < 300) {
        [$rr, $gg, $bb] = [$x, 0, $c];
    } else {
        [$rr, $gg, $bb] = [$c, 0, $x];
    }

    return sprintf(
        '#%02x%02x%02x',
        (int) round(($rr + $m2) * 255),
        (int) round(($gg + $m2) * 255),
        (int) round(($bb + $m2) * 255)
    );
}

/**
 * 解析最终生效的强调色
 *
 * 优先级：自定义颜色 > 预设方案 > 默认
 *
 * @return array{light: string, dark: string}
 */
function ghostline_accent_colors(): array
{
    $custom = (string) ghostline_option('accentColor', '');

    if ($custom !== '' && preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $custom)) {
        // 补全三位简写
        if (strlen($custom) === 4) {
            $custom = '#' . $custom[1] . $custom[1] . $custom[2] . $custom[2] . $custom[3] . $custom[3];
        }

        return ['light' => $custom, 'dark' => ghostline_lighten_color($custom)];
    }

    $palette = ghostline_accent_palette();
    $scheme = (string) ghostline_option('accentScheme', 'blue');

    if (!isset($palette[$scheme])) {
        $scheme = 'blue';
    }

    return ['light' => $palette[$scheme]['light'], 'dark' => $palette[$scheme]['dark']];
}

/**
 * 输出内联 CSS 变量（强调色）
 *
 * @return void
 */
function ghostline_accent_style()
{
    $vars = [];

    $accent = ghostline_accent_colors();
    $vars[] = '--accent-light:' . $accent['light'];
    $vars[] = '--accent-dark:' . $accent['dark'];

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
