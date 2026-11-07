<?php
/**
 * 生成带图片的测试文章
 *
 * 用法: php dev/seed-images.php
 *
 * 会创建 3 篇演示图片用法的文章，图片放在 dev/typecho/usr/uploads/2026/11/。
 * 仅用于本地开发环境。
 *
 * 配图来自 Unsplash（https://unsplash.com/license），Unsplash License 允许
 * 免费商用与修改、无需署名。图片已下载到本地，不依赖外网。
 */

$dbFile = __DIR__ . '/db/site.db';

if (!file_exists($dbFile)) {
    fwrite(STDERR, "找不到数据库：{$dbFile}\n");
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$prefix = 'typecho_';
$base = '/usr/uploads/2026/11';
$now = time();

// 先清理旧的演示文章
$slugs = ['image-basics', 'image-layout', 'image-inline'];
$in = implode(',', array_fill(0, count($slugs), '?'));
$ids = $pdo->prepare("SELECT cid FROM {$prefix}contents WHERE slug IN ($in)");
$ids->execute($slugs);
$cids = array_column($ids->fetchAll(PDO::FETCH_ASSOC), 'cid');

if ($cids) {
    $ph = implode(',', array_fill(0, count($cids), '?'));
    $pdo->prepare("DELETE FROM {$prefix}relationships WHERE cid IN ($ph)")->execute($cids);
    $pdo->prepare("DELETE FROM {$prefix}comments WHERE cid IN ($ph)")->execute($cids);
    $pdo->prepare("DELETE FROM {$prefix}contents WHERE cid IN ($ph)")->execute($cids);
}

$stmt = $pdo->prepare(
    "INSERT INTO {$prefix}contents
     (title, slug, created, modified, text, `order`, authorId, template, type, status,
      password, commentsNum, allowComment, allowPing, allowFeed, parent)
     VALUES (?, ?, ?, ?, ?, 0, 1, NULL, 'post', 'publish', NULL, 0, '1', '1', '1', 0)"
);

$rel = $pdo->prepare("INSERT INTO {$prefix}relationships (cid, mid) VALUES (?, ?)");

// 取分类和标签
$catRows = $pdo->query("SELECT mid, slug FROM {$prefix}metas WHERE type='category'")->fetchAll(PDO::FETCH_ASSOC);
$cats = array_column($catRows, 'mid', 'slug');

$tagRows = $pdo->query("SELECT mid, name FROM {$prefix}metas WHERE type='tag'")->fetchAll(PDO::FETCH_ASSOC);
$tags = array_column($tagRows, 'mid', 'name');

$articles = [];

// ---------------------------------------------------------------------------
// 1. 图片基础用法
// ---------------------------------------------------------------------------
$articles[] = [
    'title' => '图片基础用法演示',
    'slug'  => 'image-basics',
    'cover' => 'cover-mountain.jpg',
    'cat'   => 'tech',
    'tags'  => ['前端', '设计'],
    'day'   => 1,
    'text'  => <<<MD
<!--markdown-->这篇文章演示正文里图片的各种基础用法，方便检查主题的图片样式是否正常。

## 单张图片

Markdown 里写 `![说明](图片地址)` 即可插入图片。独占一段的图片会自动居中、限制最大高度：

![雪山云海]({$base}/cover-mountain.jpg)

## 带图注的图片

图片下方可以补一行斜体作为图注，排版上会与图片保持适当间距：

![森林小径]({$base}/portrait-forest.jpg)

*图 1：竖构图的图片会被等比缩放，并限制最大高度为视口的 70%。*

## 点击查看大图

上面的图片都可以点击放大。灯箱打开后：

- 按 `Esc` 退出
- 点击背景空白处退出
- 点击右上角关闭按钮退出

## 小尺寸图片

小图按原尺寸显示，不会被强行拉伸：

![山峦晨雾]({$base}/scenery-desk.jpg)
MD,
];

// ---------------------------------------------------------------------------
// 2. 图文混排
// ---------------------------------------------------------------------------
$articles[] = [
    'title' => '图文混排与排版细节',
    'slug'  => 'image-layout',
    'cover' => 'wide-lake.jpg',
    'cat'   => 'design',
    'tags'  => ['设计', '前端'],
    'day'   => 2,
    'text'  => <<<MD
<!--markdown-->除了单张图片，实际写作中更常见的是图文混排。

## 超宽横幅

宽幅图片会被压缩到正文宽度，保持比例不失真：

![山谷云海]({$base}/wide-lake.jpg)

## 行内小图标

段落中插入的小图标应当与文字保持合适的垂直对齐，而不是把整段拆成几行：

调整页面强调色时，可以点击顶栏的切换按钮，或者使用快捷键 <img src="{$base}/icon-search.svg" alt="图标"> 打开搜索。图标不应该撑破行高。

## 图片嵌套链接

图片外层套一个链接，点击图片即可跳转：

[![可点击的图片]({$base}/wide-lake.jpg)](https://typecho.org)

## 图文交替

一段文字之后接一张图，再接一段文字，是博客里最常见的节奏：

先看整体效果。下面这张图展示了页面的视觉层级：标题、正文、图片、说明文字，依次递进。

![山峦晨雾]({$base}/scenery-desk.jpg)

图片之后的文字继续保持相同的行高与段距，不会因为图片的插入而变得松散。
MD,
];

// ---------------------------------------------------------------------------
// 3. 图片与代码混合
// ---------------------------------------------------------------------------
$articles[] = [
    'title' => '图片与代码块的组合排版',
    'slug'  => 'image-inline',
    'cover' => 'scenery-desk.jpg',
    'cat'   => 'tech',
    'tags'  => ['Typecho', 'PHP', '前端'],
    'day'   => 3,
    'text'  => <<<'MD'
<!--markdown-->技术文章里经常需要图片和代码交替出现，这篇用来检查两者的间距是否协调。

## 先看效果图

![山峦晨雾]({{BASE}}/scenery-desk.jpg)

## 对应的实现

渲染图片列表时，可以把每张图包进一个 `figure` 元素：

```php
<?php
function render_gallery(array $images): string
{
    $html = '<div class="gallery">';

    foreach ($images as $image) {
        $html .= sprintf(
            '<figure><img src="%s" alt="%s"></figure>',
            htmlspecialchars($image['src']),
            htmlspecialchars($image['alt'])
        );
    }

    return $html . '</div>';
}
```

## 样式部分

```css
.figure {
  margin: 1.5em auto;
  max-width: 100%;
}

.figure img {
  display: block;
  max-height: 70vh;
  border-radius: 10px;
}
```

## 再放一张竖图收尾

![林间光影]({{BASE}}/portrait-forest.jpg)

代码块和图片之间有统一的垂直间距，视觉上不会挤在一起。
MD,
];

$created = [];
$fieldStmt = $pdo->prepare(
    "INSERT INTO {$prefix}fields (cid, name, type, str_value, int_value, float_value)
     VALUES (?, 'cover', 'str', ?, 0, 0)"
);

foreach ($articles as $a) {
    $ts = $now - ($a['day'] * 86400);
    $text = str_replace('{{BASE}}', $base, $a['text']);
    $stmt->execute([$a['title'], $a['slug'], $ts, $ts, $text]);
    $cid = (int) $pdo->lastInsertId();
    $created[] = $cid;

    if (isset($cats[$a['cat']])) {
        $rel->execute([$cid, $cats[$a['cat']]]);
    }

    foreach ($a['tags'] as $t) {
        if (isset($tags[$t])) {
            $rel->execute([$cid, $tags[$t]]);
        }
    }

    // 设置封面图，让列表页展示缩略图
    if (!empty($a['cover'])) {
        $fieldStmt->execute([$cid, $base . '/' . $a['cover']]);
    }
}

// 更新分类/标签计数
$pdo->exec("UPDATE {$prefix}metas SET count = (
    SELECT COUNT(*) FROM {$prefix}relationships r WHERE r.mid = {$prefix}metas.mid
)");

echo "已生成 " . count($created) . " 篇带图文章：\n";
foreach ($articles as $i => $a) {
    echo '  #' . $created[$i] . '  ' . $a['title'] . "\n";
}
echo "\n图片目录：dev/typecho{$base}/\n";
