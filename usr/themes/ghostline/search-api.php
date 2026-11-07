<?php
/**
 * Ghostline 搜索候选接口
 *
 * 给弹窗里的实时候选列表使用。返回 JSON：
 * {
 *   "query": "攻壳",
 *   "total": 3,
 *   "items": [
 *     {
 *       "title": "攻壳机动队与我",
 *       "url": "...",
 *       "category": "阅读笔记",
 *       "date": "2026-10-28",
 *       "excerpt": "..."
 *     }
 *   ]
 * }
 *
 * 只返回已发布、未加密的文章；关键词与 Typecho 原生搜索保持一致（标题 + 正文）。
 *
 * @package Ghostline
 */

// 探测 Typecho 根目录：主题可能位于 <root>/usr/themes/<theme>/
// 开发时主题通过软链接挂载，因此用真实路径向上查找含 config.inc.php 的目录
$candidates = [
    dirname(__DIR__, 3),                        // 标准部署：<root>/usr/themes/ghostline
    dirname(__DIR__, 3) . '/dev/typecho',       // 本仓库开发布局
    dirname(__DIR__, 4) . '/dev/typecho',       // 软链接情况下可能多一层
];

$root = null;
foreach ($candidates as $dir) {
    if ($dir && file_exists($dir . '/config.inc.php')) {
        $root = $dir;
        break;
    }
}

if ($root === null) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => 'Typecho 未安装或路径不正确'], JSON_UNESCAPED_UNICODE);
    exit;
}

// config.inc.php 会自行定义 __TYPECHO_ROOT_DIR__，这里不再预定义，避免常量重复
require_once $root . '/config.inc.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

/** 读取并清洗关键词 */
$query = isset($_GET['q']) ? (string) $_GET['q'] : '';
$query = trim(preg_replace('/\s+/u', ' ', $query));

// 限制长度，避免超长输入拖慢查询
if (mb_strlen($query) > 64) {
    $query = mb_substr($query, 0, 64);
}

if ($query === '') {
    echo json_encode(['query' => '', 'total' => 0, 'items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$limit = 6;

/** @var \Typecho\Db $db */
$db = \Typecho\Db::get();
$options = \Typecho\Widget::widget('\Widget\Options');

// 注册路由表，才能用 Router::url() 反解文章链接
\Typecho\Router::setRoutes($options->routingTable);

/**
 * 高亮用：截取纯文本摘要
 *
 * @param string $text
 * @param string $keyword
 * @param int $length
 * @return string
 */
function ghostline_api_excerpt(string $text, string $keyword, int $length = 70): string
{
    // 去掉 Markdown 标记与 HTML
    $text = preg_replace('/<!--\s*markdown\s*-->/i', '', $text);
    $text = preg_replace('/```[\s\S]*?```/', '', $text);
    $text = strip_tags($text);
    $text = preg_replace('/^\s{0,3}#{1,6}\s*/mu', '', $text);
    $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $text);
    $text = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $text);
    $text = str_replace(['**', '__', '`', '> '], '', $text);
    $text = trim(preg_replace('/\s+/u', ' ', $text));

    // 尽量从命中位置附近截取
    $pos = $keyword !== '' ? mb_stripos($text, $keyword) : false;

    if ($pos !== false && $pos > 20) {
        $start = max(0, $pos - 20);
        $text = ($start > 0 ? '…' : '') . mb_substr($text, $start);
    }

    if (mb_strlen($text) > $length) {
        $text = mb_substr($text, 0, $length) . '…';
    }

    return $text;
}

try {
    $like = '%' . str_replace(' ', '%', $query) . '%';

    $rows = $db->fetchAll(
        $db->select(
            'table.contents.cid',
            'table.contents.title',
            'table.contents.slug',
            'table.contents.created',
            'table.contents.text',
            'table.contents.type'
        )
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created < ?', $options->time)
            ->where(
                "(table.contents.password IS NULL OR table.contents.password = '')"
            )
            ->where(
                '(table.contents.title LIKE ? OR table.contents.text LIKE ?)',
                $like,
                $like
            )
            ->order('table.contents.created', \Typecho\Db::SORT_DESC)
            ->limit($limit)
    );

    // 总数（用于提示「还有更多」）
    $total = (int) $db->fetchObject(
        $db->select(['COUNT(*)' => 'num'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.created < ?', $options->time)
            ->where("(table.contents.password IS NULL OR table.contents.password = '')")
            ->where('(table.contents.title LIKE ? OR table.contents.text LIKE ?)', $like, $like)
    )->num;

    $items = [];

    foreach ($rows as $row) {
        // 分类
        $category = $db->fetchObject(
            $db->select('table.metas.name')
                ->from('table.metas')
                ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $row['cid'])
                ->where('table.metas.type = ?', 'category')
                ->limit(1)
        );

        $permalink = \Typecho\Router::url(
            'post',
            ['cid' => $row['cid'], 'slug' => $row['slug']],
            rtrim($options->siteUrl, '/') . ($options->rewrite ? '/' : '/index.php')
        );

        $items[] = [
            'title'    => html_entity_decode((string) $row['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'url'      => $permalink,
            'category' => $category ? html_entity_decode((string) $category->name, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '',
            'date'     => date('Y-m-d', (int) $row['created'] + (int) $options->timezone - (int) $options->serverTimezone),
            'excerpt'  => ghostline_api_excerpt((string) $row['text'], $query),
        ];
    }

    echo json_encode([
        'query' => $query,
        'total' => $total,
        'items' => $items,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => '搜索失败'], JSON_UNESCAPED_UNICODE);
}
