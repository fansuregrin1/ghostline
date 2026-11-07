#!/usr/bin/env python3
"""
把 Hexo 博客文章同步到 Typecho。

用法:
    python3 sync_hexo_to_typecho.py --dry-run     # 预览
    python3 sync_hexo_to_typecho.py              # 实际执行

通过 SSH 在服务器上执行 SQL，避免本地装 MySQL 客户端依赖。
"""
import argparse
import json
import os
import re
import subprocess
import sys
import time

HEXO_DIR = '/Users/fansuregrin/Documents/Code/blog/source/_posts'
SSH_HOST = 'wintermute'
DB_USER = 'root'
DB_PASS = '128086'
DB_NAME = 'blog'
PREFIX = 'fg_'
AUTHOR_ID = 1


def parse_front_matter(text):
    """解析 Hexo 的 YAML front-matter（只处理常见的简单结构）"""
    m = re.match(r'^---\s*\n(.*?)\n---\s*\n?(.*)$', text, re.S)
    if not m:
        return {}, text

    raw, body = m.group(1), m.group(2)
    data = {}
    key = None
    list_mode = None

    for line in raw.split('\n'):
        if not line.strip():
            continue

        # 列表项
        if line.startswith('  - ') or line.startswith('- '):
            item = line.strip()[2:].strip()
            if list_mode == 'categories':
                # Hexo 二维分类：categories:\n  - - 父分类\n    - 子分类
                if item.startswith('- '):
                    data.setdefault('categories', []).append(item[2:].strip())
                elif item and item not in data.get('categories', []):
                    data.setdefault('categories', []).append(item)
            elif list_mode == 'tags':
                if item:
                    data.setdefault('tags', []).append(item)
            continue

        km = re.match(r'^(\w+):\s*(.*)$', line)
        if not km:
            continue

        key, value = km.group(1), km.group(2).strip()
        list_mode = None

        if value == '':
            if key in ('categories', 'tags'):
                list_mode = key
                data.setdefault(key, [])
            continue

        value = value.strip('\'"')
        data[key] = value

    return data, body


def slugify(title, fallback):
    """生成 URL 友好的 slug"""
    s = re.sub(r'[^\w\u4e00-\u9fff-]+', '-', title.lower()).strip('-')
    s = re.sub(r'-+', '-', s)
    if not s:
        s = fallback
    return s[:140]


def collect_posts():
    posts = []
    for name in sorted(os.listdir(HEXO_DIR)):
        if not name.endswith('.md'):
            continue

        path = os.path.join(HEXO_DIR, name)
        with open(path, encoding='utf-8') as f:
            text = f.read()

        meta, body = parse_front_matter(text)
        title = meta.get('title', name[:-3])
        date = meta.get('date', '')

        body = body.strip()
        if not body:
            continue

        posts.append({
            'file': name,
            'title': title,
            'date': date,
            'categories': meta.get('categories', []),
            'tags': meta.get('tags', []),
            'text': body,
        })

    return posts


def run_sql(sql):
    """在服务器上执行 SQL，返回 stdout"""
    cmd = ['ssh', SSH_HOST, f"mysql -u{DB_USER} -p{DB_PASS} -N -B {DB_NAME}"]
    r = subprocess.run(cmd, input=sql + ';', capture_output=True, text=True)
    err = r.stderr.replace(f'[Warning] Using a password on the command line interface can be insecure.', '').strip()
    if r.returncode != 0:
        raise RuntimeError(f'SQL 失败: {err[:500]}')
    return r.stdout.strip()


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--dry-run', action='store_true')
    args = ap.parse_args()

    posts = collect_posts()
    print(f'解析到 {len(posts)} 篇 Hexo 文章')

    if args.dry_run:
        for p in posts[:5]:
            print(f"  {p['date'][:10]} | {p['title'][:30]} | 分类={p['categories']} 标签={p['tags']}")
        print('  ...')
        return

    # 建分类/标签映射
    existing_cats = {}
    for line in run_sql(f"SELECT name, mid FROM {PREFIX}metas WHERE type='category';").split('\n'):
        if '\t' in line:
            n, mid = line.split('\t', 1)
            existing_cats[n] = int(mid)

    existing_tags = {}
    for line in run_sql(f"SELECT name, mid FROM {PREFIX}metas WHERE type='tag';").split('\n'):
        if '\t' in line:
            n, mid = line.split('\t', 1)
            existing_tags[n] = int(mid)

    print(f'已有分类 {len(existing_cats)} 个，标签 {len(existing_tags)} 个')

    stats = {'inserted': 0, 'updated': 0, 'skipped': 0}

    for idx, p in enumerate(posts, 1):
        title = p['title'].replace('\\', '\\\\').replace("'", "\\'")
        text = p['text'].replace('\\', '\\\\').replace("'", "\\'")

        # 解析时间
        try:
            ts = int(time.mktime(time.strptime(p['date'][:19], '%Y-%m-%d %H:%M:%S')))
        except Exception:
            ts = int(time.time()) - (len(posts) - idx) * 86400

        # 查是否已存在（按标题）
        found = run_sql(
            f"SELECT cid FROM {PREFIX}contents WHERE type='post' AND title='{title}' LIMIT 1;"
        )

        if found.isdigit():
            cid = int(found)
            run_sql(
                f"UPDATE {PREFIX}contents SET text='{text}', modified={ts} WHERE cid={cid};"
            )
            stats['updated'] += 1
        else:
            slug = slugify(p['title'], f'hexo-{idx}')
            run_sql(
                f"INSERT INTO {PREFIX}contents "
                f"(title, slug, created, modified, text, `order`, authorId, template, type, status, "
                f"password, commentsNum, allowComment, allowPing, allowFeed, parent) "
                f"VALUES ('{title}', '{slug}', {ts}, {ts}, '{text}', 0, {AUTHOR_ID}, NULL, 'post', "
                f"'publish', NULL, 0, '1', '1', '1', 0);"
            )
            cid = int(run_sql(f"SELECT cid FROM {PREFIX}contents WHERE slug='{slug}' AND type='post' LIMIT 1;"))
            stats['inserted'] += 1

        # 关联分类
        for cat in p['categories']:
            cat = cat.strip()
            if not cat:
                continue
            if cat not in existing_cats:
                cs = slugify(cat, 'cat')
                run_sql(
                    f"INSERT INTO {PREFIX}metas (name, slug, type, description, count, `order`, parent) "
                    f"VALUES ('{cat}', '{cs}', 'category', NULL, 0, 0, 0);"
                )
                existing_cats[cat] = int(run_sql(f"SELECT mid FROM {PREFIX}metas WHERE name='{cat}' AND type='category' LIMIT 1;"))
                print(f'  新建分类: {cat}')

            mid = existing_cats[cat]
            run_sql(
                f"INSERT IGNORE INTO {PREFIX}relationships (cid, mid) VALUES ({cid}, {mid});"
            )

        # 关联标签
        for tag in p['tags']:
            tag = tag.strip()
            if not tag:
                continue
            if tag not in existing_tags:
                ts_slug = slugify(tag, 'tag')
                run_sql(
                    f"INSERT INTO {PREFIX}metas (name, slug, type, description, count, `order`, parent) "
                    f"VALUES ('{tag}', '{ts_slug}', 'tag', NULL, 0, 0, 0);"
                )
                existing_tags[tag] = int(run_sql(f"SELECT mid FROM {PREFIX}metas WHERE name='{tag}' AND type='tag' LIMIT 1;"))
                print(f'  新建标签: {tag}')

            mid = existing_tags[tag]
            run_sql(
                f"INSERT IGNORE INTO {PREFIX}relationships (cid, mid) VALUES ({cid}, {mid});"
            )

        print(f"[{idx}/{len(posts)}] {p['title'][:36]}")

    # 更新计数
    run_sql(
        f"UPDATE {PREFIX}metas SET count=(SELECT COUNT(*) FROM {PREFIX}relationships r WHERE r.mid={PREFIX}metas.mid);"
    )

    print()
    print(f"完成：新增 {stats['inserted']} 篇，更新 {stats['updated']} 篇")


if __name__ == '__main__':
    main()
