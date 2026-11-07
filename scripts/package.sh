#!/usr/bin/env bash
#
# 把主题打包成可发布的 zip
#
# 用法: bash scripts/package.sh
# 产物: dist/ghostline-<version>.zip
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME="$ROOT/usr/themes/ghostline"
OUT="$ROOT/dist"

if [ ! -d "$THEME" ]; then
    echo "找不到主题目录: $THEME" >&2
    exit 1
fi

VERSION="$(grep -m1 '@version' "$THEME/functions.php" | sed 's/.*@version[[:space:]]*//' | tr -d ' \r')"
VERSION="${VERSION:-0.1.0}"

mkdir -p "$OUT"
ZIP="$OUT/ghostline-${VERSION}.zip"
rm -f "$ZIP"

# 进入主题父目录，保证 zip 内结构为 ghostline/...
cd "$(dirname "$THEME")"
zip -rq "$ZIP" ghostline \
    -x '*.DS_Store' \
    -x '__MACOSX*' \
    -x '*.log'

echo "已打包: $ZIP"
unzip -l "$ZIP" | tail -1
