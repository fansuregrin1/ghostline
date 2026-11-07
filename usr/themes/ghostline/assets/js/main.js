/**
 * Ghostline — 前端交互
 * 主题切换 / 代码复制 / 文章目录 / 返回顶部
 */
(function () {
    'use strict';

    var root = document.documentElement;
    var STORAGE_KEY = 'ghostline-theme';

    /* ---------- 主题切换 ---------- */
    function currentTheme() {
        var saved = root.getAttribute('data-theme');
        if (saved) {
            return saved;
        }
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {}
    }

    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        });
    });

    /* ---------- 代码复制 ---------- */
    document.querySelectorAll('[data-post-content] pre').forEach(function (pre) {
        var code = pre.querySelector('code');
        if (!code) {
            return;
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'code-copy';
        button.textContent = '复制';

        button.addEventListener('click', function () {
            var text = code.innerText;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(onCopied, fallback);
            } else {
                fallback();
            }

            function fallback() {
                var area = document.createElement('textarea');
                area.value = text;
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();
                try {
                    document.execCommand('copy');
                } catch (e) {}
                document.body.removeChild(area);
                onCopied();
            }

            function onCopied() {
                button.textContent = '已复制';
                setTimeout(function () {
                    button.textContent = '复制';
                }, 1600);
            }
        });

        pre.appendChild(button);
    });

    /* ---------- 文章目录 ---------- */
    var toc = document.querySelector('[data-toc]');
    var tocList = document.querySelector('[data-toc-list]');
    var article = document.querySelector('[data-post-content]');

    if (toc && tocList && article) {
        var headings = article.querySelectorAll('h2, h3');

        if (headings.length >= 2) {
            headings.forEach(function (heading, index) {
                if (!heading.id) {
                    heading.id = 'heading-' + (index + 1);
                }

                var item = document.createElement('li');
                var link = document.createElement('a');
                link.href = '#' + heading.id;
                link.textContent = heading.textContent;
                if (heading.tagName === 'H3') {
                    item.className = 'toc-lv3';
                }
                item.appendChild(link);
                tocList.appendChild(item);
            });

            toc.hidden = false;
        }
    }

    /* ---------- 搜索弹窗 ---------- */
    var searchDialog = document.querySelector('[data-search-dialog]');
    var searchToggle = document.querySelector('[data-search-toggle]');
    var searchInput = document.querySelector('[data-search-input]');
    var searchClose = document.querySelector('[data-search-close]');

    if (searchDialog) {
        var openSearch = function () {
            if (typeof searchDialog.showModal === 'function') {
                if (!searchDialog.open) {
                    searchDialog.showModal();
                }
            } else {
                searchDialog.setAttribute('open', '');
            }

            document.body.classList.add('search-open');

            if (searchToggle) {
                searchToggle.setAttribute('aria-expanded', 'true');
            }

            // 等待浏览器完成布局再聚焦，避免移动端键盘闪一下
            window.requestAnimationFrame(function () {
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            });
        };

        var closeSearch = function () {
            if (typeof searchDialog.close === 'function' && searchDialog.open) {
                searchDialog.close();
            } else {
                searchDialog.removeAttribute('open');
            }

            document.body.classList.remove('search-open');

            if (searchToggle) {
                searchToggle.setAttribute('aria-expanded', 'false');
            }
        };

        if (searchToggle) {
            searchToggle.addEventListener('click', openSearch);
        }

        if (searchClose) {
            searchClose.addEventListener('click', closeSearch);
        }

        // 点击遮罩关闭
        searchDialog.addEventListener('click', function (event) {
            if (event.target === searchDialog) {
                closeSearch();
            }
        });

        // 原生关闭（Esc 等）时同步状态
        searchDialog.addEventListener('close', function () {
            document.body.classList.remove('search-open');
            if (searchToggle) {
                searchToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Esc 兜底（部分旧浏览器 dialog 不触发 close）
        searchDialog.addEventListener('cancel', function (event) {
            event.preventDefault();
            closeSearch();
        });

        // Cmd/Ctrl + K 唤起
        document.addEventListener('keydown', function (event) {
            var isK = String(event.key || '').toLowerCase() === 'k';
            if (isK && (event.metaKey || event.ctrlKey) && !event.altKey) {
                event.preventDefault();
                if (searchDialog.open) {
                    closeSearch();
                } else {
                    openSearch();
                }
            }
        });
    }

    /* ---------- 侧栏折叠：桌面端全部展开 ---------- */
    var sidebar = document.querySelector('.sidebar');
    var desktopQuery = window.matchMedia('(min-width: 901px)');

    if (sidebar) {
        var syncSidebar = function () {
            sidebar.querySelectorAll('.widget').forEach(function (widget) {
                widget.open = desktopQuery.matches;
            });
        };

        syncSidebar();
        desktopQuery.addEventListener('change', syncSidebar);
    }

    /* ---------- 返回顶部 ---------- */
    var backTop = document.querySelector('[data-back-top]');

    if (backTop) {
        var toggleBackTop = function () {
            if (window.scrollY > 600) {
                backTop.hidden = false;
            } else {
                backTop.hidden = true;
            }
        };

        window.addEventListener('scroll', toggleBackTop, { passive: true });
        toggleBackTop();

        backTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ---------- 跟随系统（未手动选择时） ---------- */
    var media = window.matchMedia('(prefers-color-scheme: dark)');
    media.addEventListener('change', function () {
        try {
            if (!localStorage.getItem(STORAGE_KEY)) {
                root.removeAttribute('data-theme');
            }
        } catch (e) {}
    });
})();
