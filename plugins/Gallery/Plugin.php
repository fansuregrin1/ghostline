<?php

namespace TypechoPlugin\Gallery;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Checkbox;
use Typecho\Widget\Helper\Form\Element\Number;
use Typecho\Widget\Helper\Form\Element\Select;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 兼容 Hexo 图集语法，把 {% gi %} 渲染为响应式图片网格。
 *
 * @package Gallery
 * @author Ghostline
 * @version 1.0.0
 * @link https://github.com/fansuregrin1/ghostline
 */
class Plugin implements PluginInterface
{
    /**
     * 启用插件
     */
    public static function activate()
    {
        \Typecho\Plugin::factory('Widget\Base\Contents')->contentEx = __CLASS__ . '::parse';
    }

    /**
     * 禁用插件
     */
    public static function deactivate()
    {
    }

    /**
     * 插件配置
     *
     * @param Form $form
     */
    public static function config(Form $form)
    {
        // 参数顺序：name, options, value, label, description
        $form->addInput(new Number(
            'gap',
            null,
            '0.5',
            _t('图片间距'),
            _t('单位 rem，建议 0.25 - 1')
        ));

        $form->addInput(new Number(
            'radius',
            null,
            '10',
            _t('圆角大小'),
            _t('单位 px，填 0 表示直角')
        ));

        $form->addInput(new Select(
            'maxHeight',
            [
                '45' => _t('45vh（紧凑）'),
                '60' => _t('60vh（推荐）'),
                '70' => _t('70vh（更高）'),
                '0'  => _t('不限制'),
            ],
            '60',
            _t('图片最大高度'),
            _t('限制单元格高度，超出部分裁剪')
        ));

        $form->addInput((new Checkbox(
            'inlineCss',
            ['1' => _t('自动输出样式')],
            ['1'],
            _t('样式输出'),
            _t('勾选后插件会内联输出所需 CSS，无需主题额外适配')
        ))->multiMode());
    }

    /**
     * 个人配置（无）
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * 解析图集语法
     *
     * @param string $content 已渲染的 HTML
     * @param mixed $archive
     * @param string $result
     * @return string
     */
    public static function parse($content, $archive = null, $result = '')
    {
        $html = (string) ($result !== '' ? $result : $content);

        if (stripos($html, 'gi ') === false && stripos($html, 'gi&nbsp;') === false) {
            return $html;
        }

        // 直接读数据库，避免在 contentEx 钩子里调用 Options::alloc() 造成递归
        $gap = '0.5';
        $radius = '10';
        $maxHeight = '60';
        $inlineCss = true;

        try {
            $db = \Typecho\Db::get();
            $row = $db->fetchRow(
                $db->select('value')->from('table.options')
                    ->where('name = ?', 'plugin:Gallery')
                    ->where('user = ?', 0)
            );

            if (!empty($row['value'])) {
                $cfg = json_decode((string) $row['value'], true);

                if (is_array($cfg)) {
                    $gap = self::num($cfg['gap'] ?? null, 0.5);
                    $radius = self::num($cfg['radius'] ?? null, 10);
                    $maxHeight = (string) ($cfg['maxHeight'] ?? '60');
                    $inlineCss = !empty($cfg['inlineCss'])
                        && in_array('1', (array) $cfg['inlineCss'], true);
                }
            }
        } catch (\Throwable $e) {
            // 读取失败时使用默认值
        }

        $html = preg_replace_callback(
            '/<p>\s*\{%\s*gi\s+(\d+)\s+([\d-]+)\s*%\}(.*?)\{%\s*endgi\s*%\}\s*<\/p>'
            . '|\{%\s*gi\s+(\d+)\s+([\d-]+)\s*%\}(.*?)\{%\s*endgi\s*%\}/is',
            function (array $m) use ($gap, $radius, $maxHeight) {
                $spec = trim($m[2] !== '' ? $m[2] : ($m[5] ?? ''));
                $inner = trim($m[3] !== '' ? $m[3] : ($m[6] ?? ''));

                preg_match_all('/<img\b[^>]*>/i', $inner, $found);

                if (empty($found[0])) {
                    return '';
                }

                $rows = array_values(array_filter(array_map('intval', explode('-', $spec))));
                $total = count($found[0]);

                if (empty($rows) || array_sum($rows) !== $total) {
                    $rows = [];
                }

                $heightStyle = $maxHeight === '0' ? '' : '--gi-max-h:' . (int) $maxHeight . 'vh;';

                $out = '<div class="gi-gallery" style="--gi-gap:' . $gap . 'rem;'
                    . '--gi-radius:' . $radius . 'px;' . $heightStyle . '">';

                if ($rows) {
                    $index = 0;
                    foreach ($rows as $count) {
                        $slice = array_slice($found[0], $index, $count);
                        $index += $count;

                        if (empty($slice)) {
                            continue;
                        }

                        $out .= '<div class="gi-row" style="--gi-cols:' . (int) $count . '">';
                        foreach ($slice as $img) {
                            $out .= '<figure class="gi-item">' . $img . '</figure>';
                        }
                        $out .= '</div>';
                    }
                } else {
                    $out .= '<div class="gi-row" style="--gi-cols:2">';
                    foreach ($found[0] as $img) {
                        $out .= '<figure class="gi-item">' . $img . '</figure>';
                    }
                    $out .= '</div>';
                }

                $out .= '</div>';

                return $out;
            },
            $html
        );

        $html = $html ?? $content;

        if ($inlineCss) {
            $html = self::style() . $html;
        }

        return $html;
    }

    /**
     * 取数值配置
     */
    private static function num($value, float $default): string
    {
        if ($value === null || $value === '') {
            return (string) $default;
        }

        return is_numeric($value) ? (string) (float) $value : (string) $default;
    }

    /**
     * 内联样式（仅在插件设置开启时输出）
     */
    private static function style(): string
    {
        return '<style>'
            . '.gi-gallery{margin:1.6em 0;display:flex;flex-direction:column;gap:var(--gi-gap,.5rem)}'
            . '.gi-row{display:grid;grid-template-columns:repeat(var(--gi-cols,2),minmax(0,1fr));'
            . 'gap:var(--gi-gap,.5rem)}'
            . '.gi-item{margin:0;overflow:hidden;border-radius:var(--gi-radius,10px);'
            . 'background:rgba(127,127,127,.08)}'
            . '.gi-item img{display:block;width:100%;height:100%;object-fit:cover;margin:0;'
            . 'border-radius:0;box-shadow:none;cursor:zoom-in;transition:transform .3s ease}'
            . '.gi-item:hover img{transform:scale(1.03)}'
            . '.gi-gallery[style*="--gi-max-h"] .gi-item img{max-height:var(--gi-max-h)}'
            . '@media(max-width:640px){.gi-item img{max-height:45vh}}'
            . '</style>';
    }
}
