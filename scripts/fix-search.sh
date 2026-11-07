#!/usr/bin/env bash
#
# 修复 Typecho 1.3.0 的中文搜索问题
#
# 症状：
#   搜索含中文的关键词时跳转到 /search/ 并返回 404；英文关键词正常。
#
# 原因：
#   Archive::searchHandle() 用 filter('url', 'search') 读取关键词，
#   其中 'url' 对应 Common::safeUrl()，内部经过 parse_url()，
#   会把非 ASCII 文本破坏成乱码（如 "攻壳" -> "�_�壳"）。
#   乱码被 slugName() 过滤为空，于是搜不到东西；
#   同时 checkPermalink() 反解出不带关键词的链接而触发 301。
#
# 用法：
#   bash scripts/fix-search.sh /path/to/typecho
#
# 默认目标为本仓库的开发站点 dev/typecho。
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET="${1:-$ROOT/dev/typecho}"
FILE="$TARGET/var/Widget/Archive.php"

if [ ! -f "$FILE" ]; then
    echo "找不到 $FILE，请确认 Typecho 路径" >&2
    exit 1
fi

if grep -q "filter('search')->get('keywords')" "$FILE"; then
    echo "已经是修复后的状态，无需重复操作。"
    exit 0
fi

if ! grep -q "filter('url', 'search')->get('keywords')" "$FILE"; then
    echo "目标文件内容与预期不符，可能已升级到其他版本。" >&2
    echo "请手动检查：$FILE 中的 searchHandle() 方法。" >&2
    exit 1
fi

cp "$FILE" "$FILE.bak"

# 只替换搜索关键词读取这一行
php -r '
$f = $argv[1];
$s = file_get_contents($f);
$s = str_replace(
    "\$keywords = \$this->request->filter(\x27url\x27, \x27search\x27)->get(\x27keywords\x27);",
    "\$keywords = \$this->request->filter(\x27search\x27)->get(\x27keywords\x27);",
    $s
);
file_put_contents($f, $s);
' "$FILE"

echo "已修复：$FILE"
echo "备份文件：$FILE.bak"
echo
echo "验证：在站点搜索中文关键词（如「攻壳」），应能正常返回结果。"
