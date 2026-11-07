<?php
/**
 * 友情链接
 *
 * 在页面编辑页的「自定义模板」中选择「友情链接」即可启用。
 *
 * 页面正文里每行写一个链接，格式：
 *   名称|地址|一句话描述|头像地址
 * 其中后两项可省略，例如：
 *   Typecho|https://typecho.org|一个轻量的博客程序|https://typecho.org/logo.png
 *
 * @package custom
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$this->need('header.php');

/**
 * 解析页面正文里的友链条目
 *
 * @param string $text
 * @return array<int, array{name: string, url: string, desc: string, avatar: string}>
 */
function ghostline_parse_links(string $text): array
{
    $text = str_replace("\r\n", "\n", $text);
    $links = [];

    foreach (explode("\n", $text) as $line) {
        $line = trim($line);

        if ($line === '' || strpos($line, '|') === false) {
            continue;
        }

        // 跳过 Markdown 列表符号
        $line = preg_replace('/^[-*+]\s+/', '', $line);

        $parts = array_map('trim', explode('|', $line));
        $name = $parts[0] ?? '';
        $url = $parts[1] ?? '';

        if ($name === '' || !preg_match('#^https?://#i', $url)) {
            continue;
        }

        $desc = $parts[2] ?? '';
        $avatar = $parts[3] ?? '';

        if ($avatar !== '' && !preg_match('#^(https?:)?//|^/#i', $avatar)) {
            $avatar = '';
        }

        $links[] = [
            'name'   => $name,
            'url'    => $url,
            'desc'   => $desc,
            'avatar' => $avatar,
        ];
    }

    return $links;
}

$links = ghostline_parse_links((string) $this->text);
?>
<div class="layout<?php echo ghostline_enabled('showSidebar') ? ' has-sidebar' : ''; ?>">
    <section class="content-column" id="content">

        <header class="page-intro">
            <h1 class="page-title"><?php $this->title(); ?></h1>
            <?php if ($links): ?>
                <p class="page-count">
                    <?php _e('共'); ?> <strong><?php echo count($links); ?></strong> <?php _e('个友链'); ?>
                </p>
            <?php endif; ?>
        </header>

        <?php
        // 只输出正文里的说明文字，链接行由下方网格呈现
        $introLines = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $this->text) as $introLine) {
            $trimmed = trim($introLine);

            if ($trimmed === '' || preg_match('/^[-*+]?\s*[^|]+\|/', $trimmed)) {
                continue;
            }

            $introLines[] = $trimmed;
        }
        $introText = trim(str_replace('<!--markdown-->', '', implode("\n", $introLines)));
        ?>
        <?php if ($introText !== ''): ?>
            <div class="post-content links-intro">
                <?php echo nl2br(htmlspecialchars($introText, ENT_QUOTES, 'UTF-8')); ?>
            </div>
        <?php endif; ?>

        <?php if ($links): ?>
            <ul class="link-grid">
                <?php foreach ($links as $link): ?>
                    <li class="link-card">
                        <a class="link-card-inner" href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>"
                           rel="noopener nofollow" target="_blank">
                            <span class="link-avatar" aria-hidden="true">
                                <?php if ($link['avatar']): ?>
                                    <img src="<?php echo htmlspecialchars($link['avatar'], ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <?php echo htmlspecialchars(mb_substr($link['name'], 0, 1), ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </span>

                            <span class="link-body">
                                <span class="link-name"><?php echo htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($link['desc']): ?>
                                    <span class="link-desc"><?php echo htmlspecialchars($link['desc'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php elseif (!$this->text): ?>
            <div class="empty-state">
                <p class="empty-title"><?php _e('还没有添加友链'); ?></p>
                <p class="empty-text"><?php _e('在页面正文中每行写一个：名称|地址|描述'); ?></p>
            </div>
        <?php endif; ?>

        <?php if (ghostline_enabled('showComments') && $this->allow('comment')): ?>
            <?php $this->need('comments.php'); ?>
        <?php endif; ?>
    </section>

    <?php if (ghostline_enabled('showSidebar')): ?>
        <?php $this->need('sidebar.php'); ?>
    <?php endif; ?>
</div>

<?php $this->need('footer.php'); ?>
