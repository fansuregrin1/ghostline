# 本地开发环境（不随主题发布）

这个目录只用于在本地跑一个 Typecho 站点来开发 / 调试 Ghostline，**不要提交、也不要部署**。

## 目录说明

```
dev/
├── typecho/      Typecho 1.3.0 源码（已安装）
├── db/site.db    SQLite 数据库
└── seed.php      测试数据生成脚本
```

主题通过软链接接入：

```
dev/typecho/usr/themes/ghostline -> ../../../../usr/themes/ghostline
```

改主题文件后刷新页面即可生效。

## 从零搭建

如果 `dev/typecho/` 不存在（比如刚 clone 仓库），按下面步骤重建：

```bash
cd ghostline
mkdir -p dev/db

# 1. 下载 Typecho 1.3.0
curl -sL -o /tmp/typecho.zip \
  https://github.com/typecho/typecho/releases/download/v1.3.0/typecho.zip
unzip -q /tmp/typecho.zip -d dev/typecho

# 2. 安装（SQLite，非交互）
cd dev/typecho
TYPECHO_DB_ADAPTER=SQLite \
TYPECHO_DB_FILE="$(pwd)/../db/site.db" \
TYPECHO_USER_NAME=admin \
TYPECHO_USER_PASSWORD=ghostline123 \
TYPECHO_USER_MAIL=admin@ghostline.local \
TYPECHO_SITE_URL=http://127.0.0.1:8099 \
php install.php
cd ../..

# 3. 链接主题
ln -s "$(pwd)/usr/themes/ghostline" dev/typecho/usr/themes/ghostline

# 4. 启用主题 + 生成测试数据
sqlite3 dev/db/site.db "UPDATE typecho_options SET value='ghostline' WHERE name='theme' AND user=0;"
php dev/seed.php
```

## 日常使用

```bash
# 启动
php -S 127.0.0.1:8099 -t dev/typecho

# 访问
open http://127.0.0.1:8099/            # 前台
open http://127.0.0.1:8099/admin/      # 后台
```

### 测试数据

```bash
php dev/seed.php          # 基础文章、分类、标签、评论
php dev/seed-images.php   # 3 篇演示图片用法的文章
```

`seed-images.php` 用到的图片在 `dev/typecho/usr/uploads/2026/11/`，
覆盖了封面图、竖构图、行内图标、超宽横幅等常见场景。

后台账号：`admin` / `ghostline123`

> 以上账号密码仅用于本地开发环境。

## 中文搜索

Typecho 1.3.0 的搜索对中文有缺陷（关键词被 `safeUrl()` 破坏成乱码，导致 404）。
本仓库的 `dev/typecho` 已经打过补丁。

如果重建了开发环境，重新执行：

```bash
bash scripts/fix-search.sh
```

## 注意：端口冲突
本地使用 **8099** 而不是 8080，因为 macOS 上 8080 常被其他服务（如 Jetty / Tomcat）占用，
而且 `localhost` 会优先解析到 IPv6，容易打到别的服务上。

如果浏览器里看到的不是本主题、搜索乱跳或出现 `HTTP ERROR 405/404`（Jetty 页面），
说明端口被占用了。检查：

```bash
lsof -nP -iTCP:8099 -sTCP:LISTEN
```

换端口时记得同步修改站点地址，否则站内链接和搜索跳转会指向旧地址：

```bash
sqlite3 dev/db/site.db \
  "UPDATE typecho_options SET value='http://127.0.0.1:8099' WHERE name='siteUrl' AND user=0;"
```

> 建议始终用 `127.0.0.1` 访问，不要用 `localhost`。
