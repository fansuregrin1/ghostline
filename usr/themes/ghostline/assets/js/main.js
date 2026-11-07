/**
 * Ghostline — 前端交互
 * 主题切换 / 代码复制 / 文章目录 / 返回顶部
 */
(function () {
    'use strict';

    var root = document.documentElement;
    var STORAGE_KEY = 'ghostline-theme';

    /* ---------- 主题切换（浅色 → 深色 → 跟随系统） ---------- */
    var systemDark = window.matchMedia('(prefers-color-scheme: dark)');

    /** 当前模式：light | dark | auto */
    function currentMode() {
        try {
            var saved = localStorage.getItem(STORAGE_KEY);
            if (saved === 'light' || saved === 'dark' || saved === 'auto') {
                return saved;
            }
        } catch (e) {}
        return 'auto';
    }

    /** 把模式同步到 <html data-theme> 与按钮状态 */
    function applyMode(mode) {
        if (mode === 'auto') {
            root.removeAttribute('data-theme');
        } else {
            root.setAttribute('data-theme', mode);
        }

        try {
            localStorage.setItem(STORAGE_KEY, mode);
        } catch (e) {}

        var labels = {
            light: '当前：浅色，点击切换到深色',
            dark: '当前：深色，点击切换到跟随系统',
            auto: '当前：跟随系统，点击切换到浅色'
        };

        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute('data-mode', mode);
            button.setAttribute('aria-label', labels[mode] || '切换配色');
            button.setAttribute('title', labels[mode] || '切换配色');
        });
    }

    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var order = ['light', 'dark', 'auto'];
            var next = order[(order.indexOf(currentMode()) + 1) % order.length];
            applyMode(next);
        });
    });

    // 初始化按钮状态
    applyMode(currentMode());

    /* ---------- 代码块：语法高亮 + 行号 + 复制 ---------- */
    document.querySelectorAll('[data-post-content] pre').forEach(function (pre) {
        var code = pre.querySelector('code');
        if (!code) {
            return;
        }

        // 在 highlight.js 改写 class 之前，先记录作者明确指定的语言
        var declaredLang = '';
        var declaredMatch = (code.className || '').match(/(?:^|\s)lang-([\w+#-]+)/i);

        if (declaredMatch) {
            declaredLang = declaredMatch[1].toLowerCase();
        }

        // Typecho 输出形如 class="lang-php"，highlight.js 可直接识别
        if (window.hljs) {
            try {
                window.hljs.highlightElement(code);
            } catch (e) {}
        }

        // 行号：优先取高亮后的行数，保证与显示一致
        var lineCount = code.textContent.replace(/\n$/, '').split('\n').length;

        // 语言标签：仅当作者明确指定了语言时才显示。
        // highlight.js 会对未标注的代码块自动猜测并加上 language-xxx，
        // 那种猜测不可靠（例如把纯文本猜成 css），因此不展示。
        if (declaredLang && declaredLang !== 'plaintext' && declaredLang !== 'text') {
            var label = document.createElement('span');
            label.className = 'code-lang';
            label.textContent = declaredLang;
            label.setAttribute('aria-hidden', 'true');
            pre.appendChild(label);
            pre.classList.add('has-lang');
        }

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

        // 长代码折叠：超过阈值时收起，点击展开
        var COLLAPSE_LINES = 24;

        if (lineCount > COLLAPSE_LINES) {
            pre.classList.add('is-collapsed');

            var toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'code-expand';
            toggle.setAttribute('aria-expanded', 'false');
            toggle.textContent = '展开全部 ' + lineCount + ' 行';

            var fade = document.createElement('span');
            fade.className = 'code-fade';
            fade.setAttribute('aria-hidden', 'true');
            pre.appendChild(fade);

            toggle.addEventListener('click', function () {
                var collapsed = pre.classList.toggle('is-collapsed');
                toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                toggle.textContent = collapsed
                    ? '展开全部 ' + lineCount + ' 行'
                    : '收起';
            });

            pre.appendChild(toggle);
        }
    });

    /* ---------- 文章目录（嵌套树，可折叠） ---------- */
    var toc = document.querySelector('[data-toc]');
    var tocList = document.querySelector('[data-toc-list]');
    var article = document.querySelector('[data-post-content]');

    if (toc && tocList && article) {
        // 收录 h2 / h3 / h4 三级
        var headings = article.querySelectorAll('h2, h3, h4');

        if (headings.length >= 2) {
            var tocItems = [];      // 与 headings 一一对应的 <li>
            var stack = [];         // 用于构建层级：{ level, list }

            headings.forEach(function (heading, index) {
                if (!heading.id) {
                    heading.id = 'heading-' + (index + 1);
                }

                var level = Number(heading.tagName.charAt(1));
                var item = document.createElement('li');
                item.className = 'toc-item toc-lv' + level;

                var row = document.createElement('div');
                row.className = 'toc-row';

                var link = document.createElement('a');
                link.href = '#' + heading.id;
                link.textContent = heading.textContent;
                row.appendChild(link);

                item.appendChild(row);

                // 找到合适的父级：上一个 level 更小的条目
                while (stack.length && stack[stack.length - 1].level >= level) {
                    stack.pop();
                }

                if (stack.length === 0) {
                    tocList.appendChild(item);
                } else {
                    var parent = stack[stack.length - 1];

                    if (!parent.subList) {
                        parent.subList = document.createElement('ul');
                        parent.subList.className = 'toc-sublist';
                        parent.item.appendChild(parent.subList);
                        parent.item.classList.add('has-children');
                    }

                    parent.subList.appendChild(item);
                }

                var entry = { level: level, item: item, list: null };
                stack.push(entry);
                tocItems.push(item);
            });

            // 为带子项的条目添加折叠按钮
            tocItems.forEach(function (item) {
                if (!item.classList.contains('has-children')) {
                    return;
                }

                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'toc-toggle';
                button.setAttribute('aria-label', '折叠子目录');
                button.setAttribute('aria-expanded', 'true');

                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    var collapsed = item.classList.toggle('is-collapsed');
                    button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    button.setAttribute('aria-label', collapsed ? '展开子目录' : '折叠子目录');
                });

                item.querySelector('.toc-row').insertBefore(button, item.querySelector('.toc-row').firstChild);
            });

            toc.hidden = false;

            /* 滚动时高亮当前所在章节，并展开其祖先 */
            var currentIndex = -1;

            var setCurrent = function (index) {
                if (index === currentIndex) {
                    return;
                }

                if (tocItems[currentIndex]) {
                    tocItems[currentIndex].classList.remove('is-current');
                }

                var item = tocItems[index];

                if (item) {
                    item.classList.add('is-current');

                    // 确保当前项所在的父级都是展开的
                    var parent = item.parentElement;

                    while (parent && parent !== tocList) {
                        if (parent.classList.contains('toc-item') && parent.classList.contains('is-collapsed')) {
                            parent.classList.remove('is-collapsed');
                            var toggle = parent.querySelector(':scope > .toc-row > .toc-toggle');
                            if (toggle) {
                                toggle.setAttribute('aria-expanded', 'true');
                            }
                        }
                        parent = parent.parentElement;
                    }
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

        var lightboxImages = [];   // 可切换的全部大图
        var lightboxIndex = -1;    // 当前显示的下标

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
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                stepLightbox(-1);
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                stepLightbox(1);
            }
        };

        var stepLightbox = function (delta) {
            if (!lightbox || lightboxImages.length < 2) {
                return;
            }

            lightboxIndex = (lightboxIndex + delta + lightboxImages.length) % lightboxImages.length;
            updateLightbox();
        };

        var updateLightbox = function () {
            if (!lightbox) {
                return;
            }

            var target = lightboxImages[lightboxIndex];

            if (!target) {
                return;
            }

            var img = lightbox.querySelector('img');

            if (img) {
                img.src = target.src;
                img.alt = target.alt || '';
            }

            var counter = lightbox.querySelector('.image-lightbox-counter');

            if (counter) {
                counter.textContent = (lightboxIndex + 1) + ' / ' + lightboxImages.length;
            }

            var prev = lightbox.querySelector('.image-lightbox-nav.is-prev');
            var next = lightbox.querySelector('.image-lightbox-nav.is-next');
            var single = lightboxImages.length < 2;

            if (prev) {
                prev.hidden = single;
            }
            if (next) {
                next.hidden = single;
            }
        };

        var openLightbox = function (index) {
            if (lightbox) {
                closeLightbox();
            }

            lightboxIndex = index;
            var target = lightboxImages[index];

            if (!target) {
                return;
            }

            lightbox = document.createElement('div');
            lightbox.className = 'image-lightbox';
            lightbox.setAttribute('role', 'dialog');
            lightbox.setAttribute('aria-modal', 'true');
            lightbox.setAttribute('aria-label', target.alt || '图片预览');

            var img = document.createElement('img');
            img.src = target.src;
            img.alt = target.alt || '';
            lightbox.appendChild(img);

            // 多图时提供左右切换按钮与计数
            if (lightboxImages.length > 1) {
                ['prev', 'next'].forEach(function (dir) {
                    var nav = document.createElement('button');
                    nav.type = 'button';
                    nav.className = 'image-lightbox-nav is-' + dir;
                    nav.setAttribute('aria-label', dir === 'prev' ? '上一张' : '下一张');
                    nav.innerHTML = dir === 'prev'
                        ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"></path></svg>'
                        : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"></path></svg>';
                    nav.addEventListener('click', function (event) {
                        event.stopPropagation();
                        stepLightbox(dir === 'prev' ? -1 : 1);
                    });
                    lightbox.appendChild(nav);
                });

                var counter = document.createElement('span');
                counter.className = 'image-lightbox-counter';
                counter.setAttribute('aria-hidden', 'true');
                lightbox.appendChild(counter);
            }

            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'image-lightbox-close';
            closeBtn.setAttribute('aria-label', '关闭预览');
            closeBtn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>';
            lightbox.appendChild(closeBtn);

            document.body.appendChild(lightbox);
            document.body.classList.add('lightbox-open');

            updateLightbox();

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

        // 收集正文里的独立大图，并绑定点击放大
        contentArea.querySelectorAll('img').forEach(function (img) {
            if (!isStandaloneImage(img)) {
                return;
            }

            img.classList.add('is-figure');

            var index = lightboxImages.length;
            lightboxImages.push(img);

            img.addEventListener('click', function () {
                openLightbox(index);
            });
        });
    }

    /* ---------- 评论增强：楼层号 / 回复提示 / 博主徽章 ---------- */
    (function () {
        var listWrap = document.querySelector('[data-comment-list]');

        if (!listWrap) {
            return;
        }

        var ownerIds = (listWrap.getAttribute('data-owner-ids') || '')
            .split(',')
            .map(function (v) { return v.trim(); })
            .filter(Boolean);

        // 顶层评论按顺序编号，子评论用「父楼层-序号」
        var topIndex = 0;
        var childCounters = {};

        var getAuthorName = function (li) {
            var cite = li.querySelector(':scope > .comment-author .fn');
            return cite ? cite.textContent.trim() : '';
        };

        var walk = function (list, parentLi) {
            var items = Array.prototype.filter.call(list.children, function (el) {
                return el.tagName === 'LI' && el.classList.contains('comment-body');
            });

            items.forEach(function (li) {
                var authorBox = li.querySelector(':scope > .comment-author');

                // 楼层号：顶层递增，子评论用 父楼层-序号 表示
                var floor;
                if (!parentLi) {
                    topIndex += 1;
                    floor = String(topIndex);
                    childCounters[floor] = 0;
                } else {
                    var parentFloor = parentLi.dataset.floor || '1';
                    childCounters[parentFloor] = (childCounters[parentFloor] || 0) + 1;
                    floor = parentFloor + '-' + childCounters[parentFloor];
                }

                li.dataset.floor = floor;

                if (authorBox && floor) {
                    var badge = document.createElement('span');
                    badge.className = 'comment-floor';
                    badge.textContent = '#' + floor;
                    authorBox.appendChild(badge);
                }

                // 博主徽章：紧跟在昵称后面
                var coid = (li.id || '').replace('comment-', '');
                if (ownerIds.length && coid && ownerIds.indexOf(coid) !== -1) {
                    if (authorBox && !authorBox.querySelector('.comment-owner')) {
                        var owner = document.createElement('span');
                        owner.className = 'comment-owner';
                        owner.textContent = '博主';
                        var cite = authorBox.querySelector('.fn');
                        if (cite && cite.nextSibling) {
                            authorBox.insertBefore(owner, cite.nextSibling);
                        } else {
                            authorBox.appendChild(owner);
                        }
                    }
                }

                // 回复对象提示：放在内容上方独立一行
                if (parentLi) {
                    var parentName = getAuthorName(parentLi);

                    if (parentName) {
                        var replyTo = document.createElement('div');
                        replyTo.className = 'comment-reply-to';
                        replyTo.innerHTML = '<span aria-hidden="true">↳</span> 回复 <b></b>';
                        replyTo.querySelector('b').textContent = parentName;

                        var content = li.querySelector(':scope > .comment-content');
                        if (content && content.parentNode) {
                            content.parentNode.insertBefore(replyTo, content);
                        }
                    }
                }

                // 递归处理子评论
                var childrenBox = li.querySelector(':scope > .comment-children');
                if (childrenBox) {
                    var childList = childrenBox.querySelector(':scope > .comment-list');
                    if (childList) {
                        walk(childList, li);
                    }
                }
            });
        };

        // 容器的直接子元素是 <ol class="comment-list">，从它开始遍历
        var rootList = listWrap.querySelector('.comment-list');
        if (rootList) {
            walk(rootList, null);
        }
    })();

    /* ---------- 阅读进度条 ---------- */
    var progress = document.querySelector('[data-reading-progress]');
    var progressBar = document.querySelector('[data-reading-progress-bar]');

    if (progress && progressBar && contentArea) {
        var progressTicking = false;

        var updateProgress = function () {
            var rect = contentArea.getBoundingClientRect();
            var viewport = window.innerHeight;
            // 预留一部分视口，使进度在文章读完时恰好到达 100%
            var offset = viewport * 0.35;
            var total = rect.height - offset;
            var scrolled = -rect.top + offset;
            var ratio = total > 0 ? scrolled / total : 0;

            ratio = Math.min(1, Math.max(0, ratio));

            // 页面顶部时不显示，避免一条细线影响观感
            var started = window.scrollY > 40 || rect.top < -40;
            progress.hidden = !started;

            progressBar.style.width = ratio > 0
                ? (ratio * 100).toFixed(2) + '%'
                : '0%';
        };

        var scheduleProgress = function () {
            if (progressTicking) {
                return;
            }

            progressTicking = true;
            window.requestAnimationFrame(function () {
                updateProgress();
                progressTicking = false;
            });
        };

        window.addEventListener('scroll', scheduleProgress, { passive: true });
        window.addEventListener('resize', scheduleProgress);
        // 锚点跳转（如点击目录）也应立即刷新
        window.addEventListener('hashchange', scheduleProgress);
        updateProgress();
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

    /* ---------- 系统配色变化时，auto 模式下自动跟随 ---------- */
    systemDark.addEventListener('change', function () {
        if (currentMode() === 'auto') {
            root.removeAttribute('data-theme');
        }
    });
})();
