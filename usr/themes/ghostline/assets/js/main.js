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

    /* ---------- 移动端顶栏搜索 ---------- */
    var searchToggle = document.querySelector('[data-search-toggle]');
    var searchPanel = document.querySelector('[data-search-panel]');
    var searchInput = document.querySelector('#mobile-search-input');

    if (searchToggle && searchPanel) {
        searchToggle.addEventListener('click', function () {
            var willOpen = searchPanel.hidden;
            searchPanel.hidden = !willOpen;
            searchToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');

            if (willOpen && searchInput) {
                searchInput.focus();
            }
        });

        // 提交后不需要保留展开状态
        searchPanel.addEventListener('submit', function () {
            searchPanel.hidden = true;
            searchToggle.setAttribute('aria-expanded', 'false');
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
