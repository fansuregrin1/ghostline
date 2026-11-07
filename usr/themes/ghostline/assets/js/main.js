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

    /* ---------- 代码块：语法高亮 + 行号 + 复制 ---------- */
    document.querySelectorAll('[data-post-content] pre').forEach(function (pre) {
        var code = pre.querySelector('code');
        if (!code) {
            return;
        }

        // Typecho 输出形如 class="lang-php"，highlight.js 可直接识别
        if (window.hljs) {
            try {
                window.hljs.highlightElement(code);
            } catch (e) {}
        }

        // 行号：优先取高亮后的行数，保证与显示一致
        var lineCount = code.textContent.replace(/\n$/, '').split('\n').length;

        if (lineCount > 1) {
            var gutter = document.createElement('span');
            gutter.className = 'code-lines';
            gutter.setAttribute('aria-hidden', 'true');

            var numbers = [];
            for (var n = 1; n <= lineCount; n++) {
                numbers.push(n);
            }
            gutter.textContent = numbers.join('\n');

            pre.appendChild(gutter);
            pre.classList.add('has-lines');
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
        // 收录 h2 / h3 / h4 三级
        var headings = article.querySelectorAll('h2, h3, h4');

        if (headings.length >= 2) {
            var tocLinks = [];

            headings.forEach(function (heading, index) {
                if (!heading.id) {
                    heading.id = 'heading-' + (index + 1);
                }

                var level = Number(heading.tagName.charAt(1));
                var item = document.createElement('li');
                var link = document.createElement('a');

                link.href = '#' + heading.id;
                link.textContent = heading.textContent;

                // h2 顶格，h3 / h4 依次缩进并换用不同符号
                if (level > 2) {
                    item.className = 'toc-lv' + level;
                }

                item.appendChild(link);
                tocList.appendChild(item);
                tocLinks.push(item);
            });

            toc.hidden = false;

            /* 滚动时高亮当前所在章节 */
            var currentIndex = -1;

            var setCurrent = function (index) {
                if (index === currentIndex) {
                    return;
                }

                if (tocLinks[currentIndex]) {
                    tocLinks[currentIndex].classList.remove('is-current');
                }

                if (tocLinks[index]) {
                    tocLinks[index].classList.add('is-current');
                }

                currentIndex = index;
            };

            var syncCurrent = function () {
                // 以「标题顶部越过视口上方 100px」作为切换点
                var offset = 100;
                var active = 0;

                for (var i = 0; i < headings.length; i++) {
                    if (headings[i].getBoundingClientRect().top - offset <= 0) {
                        active = i;
                    } else {
                        break;
                    }
                }

                // 滚到底部时固定高亮最后一项，避免末尾章节不被选中
                var atBottom = window.innerHeight + window.scrollY
                    >= document.documentElement.scrollHeight - 2;

                if (atBottom) {
                    active = headings.length - 1;
                }

                setCurrent(active);
            };

            var tocTicking = false;

            window.addEventListener('scroll', function () {
                if (tocTicking) {
                    return;
                }

                tocTicking = true;
                window.requestAnimationFrame(function () {
                    syncCurrent();
                    tocTicking = false;
                });
            }, { passive: true });

            syncCurrent();
        }
    }

    /* ---------- 搜索弹窗 ---------- */
    var searchDialog = document.querySelector('[data-search-dialog]');
    var searchForm = document.querySelector('[data-search-form]');
    var searchToggle = document.querySelector('[data-search-toggle]');
    var searchInput = document.querySelector('[data-search-input]');
    var searchClose = document.querySelector('[data-search-close]');
    var searchResults = document.querySelector('[data-search-results]');
    var searchFooter = document.querySelector('[data-search-footer]');
    var searchHint = document.querySelector('[data-search-hint-text]');

    var searchState = {
        items: [],
        activeIndex: -1,
        timer: null,
        requestId: 0,
        lastQuery: ''
    };

    var escapeHtml = function (str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    var highlight = function (text, query) {
        var safe = escapeHtml(text);

        if (!query) {
            return safe;
        }

        var escapedQuery = escapeHtml(query).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

        try {
            return safe.replace(new RegExp('(' + escapedQuery + ')', 'gi'), '<mark>$1</mark>');
        } catch (e) {
            return safe;
        }
    };

    var setHint = function (text) {
        if (searchHint) {
            searchHint.textContent = text;
        }
    };

    var clearResults = function () {
        searchState.items = [];
        searchState.activeIndex = -1;

        if (searchResults) {
            searchResults.innerHTML = '';
            searchResults.hidden = true;
        }

        if (searchFooter) {
            searchFooter.innerHTML = '';
            searchFooter.hidden = true;
        }

        if (searchInput) {
            searchInput.setAttribute('aria-expanded', 'false');
        }
    };

    var setActive = function (index) {
        if (!searchResults) {
            return;
        }

        var nodes = searchResults.querySelectorAll('.search-result-item');

        nodes.forEach(function (node, i) {
            var active = i === index;
            node.classList.toggle('is-active', active);
            node.setAttribute('aria-selected', active ? 'true' : 'false');

            if (active && node.scrollIntoView) {
                node.scrollIntoView({ block: 'nearest' });
            }
        });

        searchState.activeIndex = index;
    };

    var renderResults = function (data) {
        if (!searchResults) {
            return;
        }

        var query = data.query || searchState.lastQuery;
        var items = data.items || [];

        searchState.items = items;
        searchState.activeIndex = -1;
        searchResults.innerHTML = '';

        if (items.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'search-result-empty';
            empty.textContent = '没有找到相关文章';
            searchResults.appendChild(empty);
            searchResults.hidden = false;
            searchInput.setAttribute('aria-expanded', 'true');
            setHint('没有匹配结果，按 Enter 查看完整搜索');
            return;
        }

        items.forEach(function (item, index) {
            var link = document.createElement('a');
            link.className = 'search-result-item';
            link.href = item.url;
            link.setAttribute('role', 'option');
            link.setAttribute('aria-selected', 'false');
            link.dataset.index = String(index);

            var title = document.createElement('span');
            title.className = 'search-result-title';
            title.innerHTML = highlight(item.title, query);
            link.appendChild(title);

            var meta = document.createElement('span');
            meta.className = 'search-result-meta';

            if (item.category) {
                var cat = document.createElement('span');
                cat.textContent = item.category;
                meta.appendChild(cat);
            }

            if (item.date) {
                if (meta.childNodes.length) {
                    var sep = document.createElement('span');
                    sep.className = 'meta-sep';
                    sep.textContent = '·';
                    meta.appendChild(sep);
                }
                var date = document.createElement('span');
                date.textContent = item.date;
                meta.appendChild(date);
            }

            link.appendChild(meta);

            if (item.excerpt) {
                var excerpt = document.createElement('span');
                excerpt.className = 'search-result-excerpt';
                excerpt.innerHTML = highlight(item.excerpt, query);
                link.appendChild(excerpt);
            }

            link.addEventListener('mouseenter', function () {
                setActive(index);
            });

            searchResults.appendChild(link);
        });

        // 「查看全部结果」放到滚动区之外，始终可见
        if (searchFooter) {
            searchFooter.innerHTML = '';
            searchFooter.hidden = true;
        }

        if (data.total && data.total > items.length && searchFooter && searchForm) {
            var all = document.createElement('a');
            all.className = 'search-result-all';
            all.href = searchForm.action + '?s=' + encodeURIComponent(query);
            all.textContent = '查看全部 ' + data.total + ' 条结果 →';
            searchFooter.appendChild(all);
            searchFooter.hidden = false;
        }

        searchResults.hidden = false;
        searchInput.setAttribute('aria-expanded', 'true');
        setHint('共 ' + data.total + ' 条匹配，↑↓ 选择');
    };

    var fetchSuggestions = function (query) {
        if (!searchForm) {
            return;
        }

        var api = searchForm.getAttribute('data-api');

        if (!api) {
            return;
        }

        searchState.lastQuery = query;

        if (!query) {
            clearResults();
            setHint('输入关键词开始搜索');
            return;
        }

        var requestId = ++searchState.requestId;

        fetch(api + '?q=' + encodeURIComponent(query), {
            headers: { Accept: 'application/json' }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (data) {
                // 只处理最后一次请求，避免竞态
                if (requestId !== searchState.requestId) {
                    return;
                }
                renderResults(data);
            })
            .catch(function () {
                if (requestId !== searchState.requestId) {
                    return;
                }
                clearResults();
                setHint('搜索建议暂不可用，按 Enter 直接搜索');
            });
    };

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

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var query = searchInput.value.trim();

                window.clearTimeout(searchState.timer);

                if (!query) {
                    clearResults();
                    setHint('输入关键词开始搜索');
                    return;
                }

                searchState.timer = window.setTimeout(function () {
                    fetchSuggestions(query);
                }, 220);
            });

            searchInput.addEventListener('keydown', function (event) {
                var count = searchState.items.length;

                if (event.key === 'ArrowDown' && count > 0) {
                    event.preventDefault();
                    var next = searchState.activeIndex + 1;
                    setActive(next >= count ? 0 : next);
                    return;
                }

                if (event.key === 'ArrowUp' && count > 0) {
                    event.preventDefault();
                    var prev = searchState.activeIndex - 1;
                    setActive(prev < 0 ? count - 1 : prev);
                    return;
                }

                if (event.key === 'Enter') {
                    if (searchState.activeIndex >= 0 && searchState.items[searchState.activeIndex]) {
                        event.preventDefault();
                        window.location.href = searchState.items[searchState.activeIndex].url;
                    }
                    // 否则走表单默认提交，进入完整搜索结果页
                }
            });
        }

        // 打开弹窗时若已有输入，重新请求一次候选
        searchDialog.addEventListener('close', function () {
            document.body.classList.remove('search-open');
            if (searchToggle) {
                searchToggle.setAttribute('aria-expanded', 'false');
            }
        });

        searchDialog.addEventListener('cancel', function (event) {
            event.preventDefault();
            closeSearch();
        });

        // 点击遮罩关闭
        searchDialog.addEventListener('click', function (event) {
            if (event.target === searchDialog) {
                closeSearch();
            }
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

    /* ---------- 图片灯箱 ---------- */
    var contentArea = document.querySelector('[data-post-content]');

    if (contentArea) {
        var lightbox = null;

        /**
         * 判断图片是否为「独立成段的大图」。
         * 段落里除了这张图和空白外没有其它内容，就按块级展示并可点击放大。
         */
        var isStandaloneImage = function (img) {
            var paragraph = img.closest('p');

            // 不在段落里（如单独写 <img> 标签）也按块级处理
            if (!paragraph) {
                return true;
            }

            var others = Array.prototype.filter.call(paragraph.childNodes, function (node) {
                if (node === img) {
                    return false;
                }

                if (node.nodeType === 3) {
                    return node.textContent.trim() !== '';
                }

                return node.nodeType === 1;
            });

            return others.length === 0;
        };

        var closeLightbox = function () {
            if (!lightbox) {
                return;
            }

            lightbox.classList.remove('is-open');
            document.body.classList.remove('lightbox-open');

            var box = lightbox;
            lightbox = null;

            window.setTimeout(function () {
                if (box && box.parentNode) {
                    box.parentNode.removeChild(box);
                }
            }, 200);

            document.removeEventListener('keydown', onLightboxKey);
        };

        var onLightboxKey = function (event) {
            if (event.key === 'Escape') {
                closeLightbox();
            }
        };

        var openLightbox = function (src, alt) {
            if (lightbox) {
                closeLightbox();
            }

            lightbox = document.createElement('div');
            lightbox.className = 'image-lightbox';
            lightbox.setAttribute('role', 'dialog');
            lightbox.setAttribute('aria-modal', 'true');
            lightbox.setAttribute('aria-label', alt || '图片预览');

            var img = document.createElement('img');
            img.src = src;
            img.alt = alt || '';
            lightbox.appendChild(img);

            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'image-lightbox-close';
            closeBtn.setAttribute('aria-label', '关闭预览');
            closeBtn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>';
            lightbox.appendChild(closeBtn);

            document.body.appendChild(lightbox);
            document.body.classList.add('lightbox-open');

            // 触发过渡
            window.requestAnimationFrame(function () {
                if (lightbox) {
                    lightbox.classList.add('is-open');
                }
            });

            closeBtn.addEventListener('click', closeLightbox);

            // 点背景关闭，点图片不关闭
            lightbox.addEventListener('click', function (event) {
                if (event.target === lightbox) {
                    closeLightbox();
                }
            });

            document.addEventListener('keydown', onLightboxKey);
        };

        // 标记独立大图并绑定点击放大
        contentArea.querySelectorAll('img').forEach(function (img) {
            if (!isStandaloneImage(img)) {
                return;
            }

            img.classList.add('is-figure');

            img.addEventListener('click', function () {
                openLightbox(img.currentSrc || img.src, img.alt);
            });
        });
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
