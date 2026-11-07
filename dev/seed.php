<?php
/**
 * Ghostline 本地测试数据生成器
 *
 * 用法: php dev/seed.php
 *
 * 会清空现有文章/分类/标签/评论，并生成一套用于主题开发的测试内容。
 * 仅用于本地开发环境，不要在生产站运行。
 */

$dbFile = __DIR__ . '/db/site.db';

if (!file_exists($dbFile)) {
    fwrite(STDERR, "找不到数据库文件: {$dbFile}\n");
    fwrite(STDERR, "请先安装 Typecho (见 dev/README.md)\n");
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$prefix = 'typecho_';

// ---------------------------------------------------------------------------
// 清空旧数据
// ---------------------------------------------------------------------------
foreach (['comments', 'relationships', 'fields', 'metas', 'contents'] as $table) {
    $pdo->exec("DELETE FROM {$prefix}{$table}");
}
$hasSequence = $pdo->query(
    "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'sqlite_sequence'"
)->fetchColumn();

if ($hasSequence) {
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name IN "
        . "('{$prefix}contents','{$prefix}metas','{$prefix}comments')");
}

$now = time();

// ---------------------------------------------------------------------------
// 分类
// ---------------------------------------------------------------------------
$categories = [
    ['阅读笔记', 'reading', '书与电影的随手记录'],
    ['技术随笔', 'tech', '开发过程中踩过的坑'],
    ['科幻碎片', 'scifi', '关于赛博格与未来的一点胡思乱想'],
];

$catIds = [];
$stmt = $pdo->prepare(
    "INSERT INTO {$prefix}metas (name, slug, type, description, count, `order`, parent)
     VALUES (?, ?, 'category', ?, 0, ?, 0)"
);
foreach ($categories as $i => [$name, $slug, $desc]) {
    $stmt->execute([$name, $slug, $desc, $i]);
    $catIds[$slug] = (int) $pdo->lastInsertId();
}

// ---------------------------------------------------------------------------
// 标签
// ---------------------------------------------------------------------------
$tagNames = ['Typecho', 'PHP', '前端', '设计', '攻壳机动队', '读书', '生活', '开源'];
$tagIds = [];
$stmt = $pdo->prepare(
    "INSERT INTO {$prefix}metas (name, slug, type, description, count, `order`, parent)
     VALUES (?, ?, 'tag', NULL, 0, 0, 0)"
);
foreach ($tagNames as $name) {
    $slug = 'tag-' . substr(md5($name), 0, 6);
    $stmt->execute([$name, $slug]);
    $tagIds[$name] = (int) $pdo->lastInsertId();
}

// ---------------------------------------------------------------------------
// 文章内容模板
// ---------------------------------------------------------------------------
$lorem = [
    '这篇记录写下的时候窗外正在下雨，键盘声和雨声混在一起，倒也成了一种白噪音。',
    '最近在重读一些旧东西，发现时隔几年再看的感受完全不同——大概人本身也在被时间改写。',
    '折腾这些东西的乐趣大概不在于结果，而在于过程里那些“哦原来是这么回事”的瞬间。',
    '有些想法如果不及时写下来，过一天就再也想不起来了，所以还是得留一点痕迹。',
    '简洁不是少做东西，而是把不必要的东西都拿掉之后剩下的部分还能好好工作。',
];

$codeBlock = <<<'MD'
下面是一段示例代码：

```php
<?php
// Ghostline 主题里的一个小工具函数
function reading_time(string $text): int
{
    $chars = mb_strlen(strip_tags($text));
    return max(1, (int) ceil($chars / 400));
}
```
MD;

$posts = [
    ['在本地跑起一个 Typecho', 'typecho-local-setup', 'tech', ['Typecho', 'PHP'], '从零开始把 Typecho 跑起来的一些记录。'],
    ['关于简洁的一点想法', 'on-simplicity', 'scifi', ['设计', '生活'], '简洁并不是空，而是克制。'],
    ['攻壳机动队与我', 'ghost-in-the-shell', 'reading', ['攻壳机动队', '读书'], '第 N 次重看，依然会被那句台词击中。'],
    ['SQLite 其实很好用', 'sqlite-is-great', 'tech', ['PHP', '开源'], '对小型站点来说，单文件数据库真的省心。'],
    ['一个下午的排版实验', 'typesetting-afternoon', 'tech', ['前端', '设计'], '正文宽度、行高、字距，一点点调。'],
    ['赛博格的孤独', 'loneliness-of-cyborg', 'scifi', ['攻壳机动队', '生活'], '身体被替换之后，剩下的还算不算人？'],
    ['读《童年的终结》', 'childhoods-end', 'reading', ['读书', '生活'], '克拉克的温柔与冷酷是一体两面。'],
    ['把博客当成笔记本来用', 'blog-as-notebook', 'tech', ['Typecho', '生活'], '不追求流量，只追求可检索。'],
    ['深色模式的取舍', 'dark-mode-tradeoffs', 'tech', ['前端', '设计'], '对比度、色温与用户习惯之间的平衡。'],
    ['《尤比克》的日常化', 'ubik-everyday', 'reading', ['读书'], 'PKD 把超现实塞进了日常的缝隙里。'],
    ['一个开源主题的自我修养', 'open-source-theme', 'tech', ['Typecho', '开源', '前端'], '可读性、可维护性、可配置性。'],
    ['写代码时的背景音', 'coding-soundtrack', 'scifi', ['生活'], '雨天、白噪音与合成器。'],
];

$textTpl = function (string $lead) use ($lorem, $codeBlock): string {
    $paragraphs = [$lead];
    $paragraphs[] = $lorem[array_rand($lorem)];
    $paragraphs[] = '## 一点背景';
    $paragraphs[] = $lorem[array_rand($lorem)];
    $paragraphs[] = $codeBlock;
    $paragraphs[] = '## 具体做法';
    $paragraphs[] = $lorem[array_rand($lorem)];
    $paragraphs[] = '### 需要注意的地方';
    $paragraphs[] = $lorem[array_rand($lorem)];

    // Typecho 以该标记判断正文是否为 Markdown
    return "<!--markdown-->" . implode("\n\n", $paragraphs);
};

$stmt = $pdo->prepare(
    "INSERT INTO {$prefix}contents
     (title, slug, created, modified, text, `order`, authorId, template, type, status,
      password, commentsNum, allowComment, allowPing, allowFeed, parent)
     VALUES (?, ?, ?, ?, ?, 0, 1, NULL, 'post', 'publish', NULL, 0, '1', '1', '1', 0)"
);

$stmtRel = $pdo->prepare(
    "INSERT INTO {$prefix}relationships (cid, mid) VALUES (?, ?)"
);

$postIds = [];
$dayOffset = count($posts);
foreach ($posts as [$title, $slug, $cat, $tags, $lead]) {
    $created = $now - ($dayOffset-- * 86400) - 3600 * 3;
    $stmt->execute([$title, $slug, $created, $created, $textTpl($lead)]);
    $cid = (int) $pdo->lastInsertId();
    $postIds[] = $cid;

    $stmtRel->execute([$cid, $catIds[$cat]]);
    foreach ($tags as $t) {
        $stmtRel->execute([$cid, $tagIds[$t]]);
    }
}

// ---------------------------------------------------------------------------
// 独立页面
// ---------------------------------------------------------------------------
$pageStmt = $pdo->prepare(
    "INSERT INTO {$prefix}contents
     (title, slug, created, modified, text, `order`, authorId, template, type, status,
      password, commentsNum, allowComment, allowPing, allowFeed, parent)
     VALUES (?, ?, ?, ?, ?, 0, 1, NULL, 'page', 'publish', NULL, 0, '1', '0', '0', 0)"
);

$pageStmt->execute([
    '关于',
    'about',
    $now - 30 * 86400,
    $now - 30 * 86400,
    "<!--markdown-->这里是一个个人博客，用来放一些零散的笔记与想法。\n\n"
    . "主题名字叫 **Ghostline**，致敬《攻壳机动队》里那句 *Ghost in the Shell*。",
]);

$pageStmt->execute([
    '友情链接',
    'links',
    $now - 20 * 86400,
    $now - 20 * 86400,
    "<!--markdown-->- [Typecho](https://typecho.org)\n- [GitHub](https://github.com)\n",
]);

// ---------------------------------------------------------------------------
// 评论
// ---------------------------------------------------------------------------
$commentStmt = $pdo->prepare(
    "INSERT INTO {$prefix}comments
     (cid, created, author, authorId, ownerId, mail, url, ip, agent, text, type, status, parent)
     VALUES (?, ?, ?, 1, 0, ?, ?, '127.0.0.1', 'seed-script', ?, 'comment', 'approved', ?)"
);

$commentTexts = [
    '写得很清楚，收藏了。',
    '正好在做类似的东西，很有参考价值。',
    '这段代码我试了一下，确实能跑。',
    '同感，简洁比复杂难多了。',
    '期待后续更新。',
];

$commenters = [
    ['路过的人', 'passer@example.com', null],
    ['少佐', 'motoko@example.com', 'https://example.com'],
    ['塔奇克马', 'tachikoma@example.com', null],
];

foreach (array_slice($postIds, 0, 6) as $i => $cid) {
    // 每条主评论
    $n = mt_rand(1, 3);
    for ($j = 0; $j < $n; $j++) {
        [$author, $mail, $url] = $commenters[array_rand($commenters)];
        $commentStmt->execute([
            $cid,
            $now - ($i * 3600) - ($j * 600),
            $author,
            $mail,
            $url,
            $commentTexts[array_rand($commentTexts)],
            0,
        ]);
    }
}

// 更新评论计数
$pdo->exec("UPDATE {$prefix}contents SET commentsNum = (
    SELECT COUNT(*) FROM {$prefix}comments c
    WHERE c.cid = {$prefix}contents.cid AND c.status = 'approved'
)");

// 更新分类/标签计数
$pdo->exec("UPDATE {$prefix}metas SET count = (
    SELECT COUNT(*) FROM {$prefix}relationships r
    WHERE r.mid = {$prefix}metas.mid
)");

echo "已生成测试数据：\n";
echo '  文章 ' . count($posts) . " 篇\n";
echo "  独立页面 2 个\n";
echo '  分类 ' . count($categories) . " 个\n";
echo '  标签 ' . count($tagNames) . " 个\n";
