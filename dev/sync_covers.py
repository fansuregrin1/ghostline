#!/usr/bin/env python3
"""
把 Hexo 文章的 index_img 同步为 Typecho 的封面（cover 字段）。

用法:
    python3 sync_covers.py --dry-run
    python3 sync_covers.py
"""
import argparse
import json
import re
import subprocess
import sys

HEXO_DIR = '/Users/fansuregrin/Documents/Code/blog/source/_posts'
SSH_HOST = 'wintermute'
DB = 'blog'
PREFIX = 'fg_'


def run_sql(sql):
    cmd = ['ssh', SSH_HOST, f'mysql -uroot -p128086 -N -B {DB}']
    r = subprocess.run(cmd, input=sql + ';', capture_output=True, text=True)
    err = r.stderr.replace('[Warning] Using a password on the command line interface can be insecure.', '').strip()
    if r.returncode != 0:
        raise RuntimeError(err[:400])
    return r.stdout


def collect_covers():
    """从 Hexo front-matter 提取 title -> index_img 映射"""
    covers = {}
    for name in sorted(__import__('os').listdir(HEXO_DIR)):
        if not name.endswith('.md'):
            continue

        with open(f'{HEXO_DIR}/{name}', encoding='utf-8') as f:
            head = f.read(2000)

        m = re.match(r'^---\s*\n(.*?)\n---', head, re.S)
        if not m:
            continue

        fm = m.group(1)
        t = re.search(r'^title:\s*(.+)$', fm, re.M)
        c = re.search(r'^index_img:\s*(.+)$', fm, re.M)

        if t and c:
            title = t.group(1).strip().strip('\'"')
            cover = c.group(1).strip().strip('\'"')
            if cover.startswith('http'):
                covers[title] = cover

    return covers


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--dry-run', action='store_true')
    args = ap.parse_args()

    covers = collect_covers()
    print(f'找到 {len(covers)} 篇带封面的文章')

    done = 0
    for title, url in covers.items():
        esc_title = title.replace('\\', '\\\\').replace("'", "\\'")
        esc_url = url.replace('\\', '\\\\').replace("'", "\\'")

        cid = run_sql(
            f"SELECT cid FROM {PREFIX}contents WHERE type='post' "
            f"AND title='{esc_title}' LIMIT 1"
        ).strip()

        if not cid.isdigit():
            print(f'  跳过（Typecho 无此文章）: {title[:30]}')
            continue

        if args.dry_run:
            print(f'  {title[:30]} -> {url[:50]}')
            done += 1
            continue

        # fields 表用 REPLACE INTO 保证幂等
        run_sql(
            f"REPLACE INTO {PREFIX}fields (cid, name, type, str_value, int_value, float_value) "
            f"VALUES ({cid}, 'cover', 'str', '{esc_url}', 0, 0)"
        )
        print(f'  [{cid}] {title[:30]}')
        done += 1

    print(f'\n{"预览" if args.dry_run else "完成"}：{done} 篇')


if __name__ == '__main__':
    main()
