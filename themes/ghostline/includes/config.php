<?php
/**
 * Ghostline — 后台配置
 *
 * 主题设置表单与文章自定义字段
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 在主题设置表单中插入分组标题
 *
 * @param \Typecho\Widget\Helper\Form $form
 * @param string $title 分组名称
 * @param string $desc 分组说明
 * @return void
 */
function ghostline_config_group($form, string $title, string $desc = '')
{
    $wrap = new \Typecho\Widget\Helper\Layout('div');
    $wrap->setAttribute('class', 'ghostline-group');
    $wrap->setAttribute(
        'style',
        'margin:2.25rem 0 0.75rem;padding:0 0 0.6rem;'
        . 'border-bottom:1px solid #e5e8ef;'
    );

    $heading = new \Typecho\Widget\Helper\Layout('h3');
    $heading->setAttribute('class', 'ghostline-group-title');
    $heading->setAttribute(
        'style',
        'margin:0;font-size:15px;font-weight:600;color:#1a2233;'
        . 'display:flex;align-items:center;gap:8px;'
    );
    $heading->html(
        '<span style="display:inline-block;width:3px;height:14px;'
        . 'border-radius:2px;background:#2f6feb;"></span>'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
    );
    $wrap->addItem($heading);

    if ($desc !== '') {
        $note = new \Typecho\Widget\Helper\Layout('p');
        $note->setAttribute('class', 'ghostline-group-desc');
        $note->setAttribute(
            'style',
            'margin:6px 0 0;font-size:12px;line-height:1.6;color:#8a94a6;'
        );
        $note->html(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'));
        $wrap->addItem($note);
    }

    $form->addItem($wrap);
}

/**
 * 后台「设置外观」表单
 *
 * @param \Typecho\Widget\Helper\Form $form
 * @return void
 */
function themeConfig($form)
{
    /* ================= 站点标识 ================= */

    ghostline_config_group($form, _t('站点标识'), _t('网站的名称、图标与主色调'));

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'brandTitle',
            null,
            null,
            _t('站点名称'),
            _t('显示在页面左上角，留空则使用站点标题')
        ))->addRule('maxLength', _t('名称过长'), 40)
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'brandLogo',
            null,
            null,
            _t('站点 Logo'),
            _t('填入图片地址或站内路径（如 /usr/uploads/logo.png），替换左上角的默认色块。建议高度 32px 左右的 PNG 或 SVG，留空则显示默认色块')
        ))->addRule(
            'regexp',
            _t('请填写合法的图片地址，例如 https://… 或 /usr/uploads/…'),
            '#^(https?:)?//[^\s]+$|^/[^\s]*$#i'
        )
    );

    // 预设配色方案
    $palette = ghostline_accent_palette();
    $schemeOptions = [];
    foreach ($palette as $key => $item) {
        $schemeOptions[$key] = $item['label'];
    }

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Select(
            'accentScheme',
            $schemeOptions,
            'blue',
            _t('配色方案'),
            _t('一键切换主题色，自动适配明暗两种模式')
        )
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'accentColor',
            null,
            null,
            _t('自定义颜色'),
            _t('填写后会覆盖上方的配色方案。留空则使用所选方案')
        ))->addRule('regexp', _t('请填写合法的颜色值，例如 #2f6feb'), '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/')
    );

    /* ================= 首页 ================= */

    ghostline_config_group($form, _t('首页'), _t('首页顶部文案与文章列表的呈现方式'));

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'homeTitle',
            null,
            null,
            _t('首页大标题'),
            _t('留空则显示 Ghostline')
        ))->addRule('maxLength', _t('标题过长'), 60)
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Radio(
            'hideHomeTitle',
            [
                '0' => _t('显示'),
                '1' => _t('隐藏'),
            ],
            '0',
            _t('是否显示首页大标题'),
            _t('隐藏后首页直接显示副标题与文章列表，避免与左上角站名重复')
        )
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'homeSubtitle',
            null,
            null,
            _t('首页副标题'),
            _t('留空则显示默认文案 A quiet line between ghost and shell.')
        ))->addRule('maxLength', _t('副标题过长'), 200)
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Radio(
            'hideHomeSubtitle',
            [
                '0' => _t('显示'),
                '1' => _t('隐藏'),
            ],
            '0',
            _t('是否显示首页副标题'),
            _t('隐藏后首页只显示标题与文章列表')
        )
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Select(
            'homeLayout',
            [
                'card'    => _t('卡片式'),
                'classic' => _t('经典式'),
                'compact' => _t('紧凑式'),
            ],
            'card',
            _t('文章列表样式'),
            _t('卡片式：带封面图，左图右文；经典式：纯文字，含标题与摘要；紧凑式：仅标题与日期')
        )
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Number(
            'excerptLength',
            null,
            120,
            _t('摘要长度'),
            _t('列表页显示的摘要字符数，建议 80 - 200')
        ))->addRule('isInteger', _t('请填写数字'))
    );

    /* ================= 阅读体验 ================= */

    ghostline_config_group($form, _t('阅读体验'), _t('文章正文的排版与代码块显示'));

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Select(
            'fontFamily',
            [
                'sans'  => _t('无衬线（系统默认）'),
                'serif' => _t('衬线（正文更书卷气）'),
            ],
            'sans',
            _t('正文字体'),
            _t('仅影响正文与文章内容，不影响界面文字')
        )
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Select(
            'fontSize',
            [
                'small'  => _t('小（16px）'),
                'normal' => _t('标准（17px）'),
                'large'  => _t('大（18px）'),
                'xlarge' => _t('特大（19px）'),
            ],
            'normal',
            _t('正文字号'),
            _t('影响文章正文的显示大小')
        )
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Select(
            'lineHeight',
            [
                'normal' => _t('标准'),
                'loose'  => _t('宽松（更适合长文阅读）'),
            ],
            'normal',
            _t('正文行高'),
            _t('宽松行高会增加正文行间距')
        )
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Select(
            'codeTheme',
            [
                'ghostline' => _t('Ghostline 暗蓝（默认）'),
                'github'    => _t('GitHub Dark'),
                'monokai'   => _t('Monokai'),
            ],
            'ghostline',
            _t('代码高亮配色'),
            _t('切换代码块的语法高亮配色方案')
        )
    );

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Radio(
            'codeWrap',
            [
                '0' => _t('横向滚动'),
                '1' => _t('自动换行'),
            ],
            '0',
            _t('长代码行处理'),
            _t('代码行超长时横向滚动，或自动折行显示')
        )
    );

    /* ================= 侧栏 ================= */

    ghostline_config_group($form, _t('侧栏'), _t('侧边栏显示的博主信息与模块内容'));

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'authorName',
            null,
            null,
            _t('博主昵称'),
            _t('留空则不显示博主卡片')
        ))->addRule('maxLength', _t('昵称过长'), 40)
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'authorBio',
            null,
            null,
            _t('博主简介'),
            _t('一句话介绍，显示在昵称下方')
        ))->addRule('maxLength', _t('简介过长'), 200)
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'authorAvatarUrl',
            null,
            null,
            _t('博主头像'),
            _t('填入图片地址，留空则不显示头像')
        ))->addRule('regexp', _t('请填写合法的图片地址'), '#^(https?:)?//[^\s]+$|^/[^\s]*$#i')
    );

    $socialField = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'socialLinks',
        null,
        null,
        _t('社交链接'),
        _t('每行一个，格式：名称|地址。例如：<br>GitHub|https://github.com/your-name<br>微博|https://weibo.com/your-name')
    );

    $form->addInput($socialField);

    if ($socialField->input instanceof \Typecho\Widget\Helper\Layout) {
        $socialField->input->setAttribute('rows', 4);
        $socialField->input->setAttribute('class', 'text w-100');
        $socialField->input->setAttribute('style', 'resize:vertical;min-height:96px;');
    }

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Number(
            'recentPostCount',
            null,
            5,
            _t('最新文章数量'),
            _t('侧栏「最新文章」显示几条，建议 3 - 10')
        ))->addRule('isInteger', _t('请填写数字'))
    );

    /* ================= 页脚 ================= */

    ghostline_config_group($form, _t('页脚'), _t('版权、备案号与自定义说明文字'));

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'icpNumber',
            null,
            null,
            _t('ICP 备案号'),
            _t('例如：京ICP备12345678号，留空则不显示')
        ))->addRule('maxLength', _t('备案号过长'), 60)
    );

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Text(
            'policeNumber',
            null,
            null,
            _t('公安备案号'),
            _t('例如：京公网安备 11010502030143号，留空则不显示')
        ))->addRule('maxLength', _t('备案号过长'), 60)
    );

    $footerField = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'footerText',
        null,
        null,
        _t('自定义文字'),
        _t('显示在页脚版权信息下方，支持 HTML，可换行书写。留空则不显示')
    );

    $form->addInput($footerField);

    if ($footerField->input instanceof \Typecho\Widget\Helper\Layout) {
        $footerField->input->setAttribute('rows', 3);
        $footerField->input->setAttribute('class', 'text w-100');
        $footerField->input->setAttribute('style', 'resize:vertical;min-height:72px;');
    }

    $footerField->addRule('maxLength', _t('内容过长，建议不超过 500 字'), 500);

    $form->addInput(
        new \Typecho\Widget\Helper\Form\Element\Radio(
            'hidePoweredBy',
            [
                '0' => _t('保留'),
                '1' => _t('隐藏'),
            ],
            '0',
            _t('版权信息'),
            _t('是否保留「由 Typecho 驱动 · 主题 Ghostline」一行')
        )
    );

    /* ================= 功能开关 ================= */

    ghostline_config_group($form, _t('功能开关'), _t('按需开启或关闭各功能模块'));

    $form->addInput(
        (new \Typecho\Widget\Helper\Form\Element\Checkbox(
            'features',
            [
                'showSidebar'    => _t('在列表页显示侧栏'),
                'showToc'        => _t('在文章页显示文章目录'),
                'showReading'    => _t('显示预计阅读时间'),
                'showBackTop'    => _t('显示返回顶部按钮'),
                'showPrevNext'   => _t('显示上一篇 / 下一篇'),
                'showComments'   => _t('开放评论区'),
                'showProgress'   => _t('显示阅读进度条'),
            ],
            [
                'showSidebar', 'showToc', 'showReading', 'showBackTop',
                'showPrevNext', 'showComments', 'showProgress',
            ],
            _t('功能开关')
        ))->multiMode()
    );
}

/**
 * 文章编辑页的「自定义字段」
 *
 * 目前提供一个封面图地址，用于在列表页展示缩略图。
 *
 * @param \Typecho\Widget\Helper\Layout $layout
 * @return void
 */
function themeFields($layout)
{
    $cover = new \Typecho\Widget\Helper\Form\Element\Text(
        'cover',
        null,
        null,
        _t('封面图地址'),
        _t('用于列表页缩略图，可留空')
    );

    // class 必须加在 input 上；对 Element 调 setAttribute 会作用到外层 <ul>
    foreach ($cover->inputs as $input) {
        $input->setAttribute('class', 'text w-100');
        $input->setAttribute('placeholder', 'https://');
    }

    $layout->addItem($cover);

    // Typecho 1.3 把自定义字段渲染成「字段名 + 字段值」两栏，其中 .field-value
    // 是 flex 行容器，会让输入框和说明文字并排各占一半；而后台样式又给说明文字
    // 施加了负的 margin-top，使它压住输入框下边框。
    // 这里只针对封面图这一项，改为纵向排列并恢复正常间距，不影响其他自定义字段。
    static $styled = false;

    if (!$styled) {
        $styled = true;
        echo '<style>'
            . '#custom-field li.field:has(input[name="fields[cover]"])'
            . ' .field-value{flex-direction:column;align-items:stretch}'
            . '#custom-field li.field:has(input[name="fields[cover]"])'
            . ' .field-value .description{margin:.35em 0 0}'
            . '</style>';
    }
}

/**
 * 主题首次启用时写入默认配置
 *
 * Typecho 只在用户保存「设置外观」后才写入 theme:xxx 配置项。
 * 这里补一次默认值，避免首次启用时功能开关全部为空、界面过于简陋。
 *
 * @param \Widget\Options $options
 * @return void
 */
function themeInit($options = null)
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;

    $options = \Typecho\Widget::widget('\Widget\Options');
    $theme = (string) $options->theme;

    if ('' === $theme) {
        return;
    }

    // 已有配置（含用户保存过的）则不做任何事
    if (isset($options->{'theme:' . $theme})) {
        return;
    }

    $db = \Typecho\Db::get();
    $key = 'theme:' . $theme;

    $defaults = [
        'accentScheme'     => 'blue',
        'homeLayout'       => 'card',
        'excerptLength'    => '120',
        'recentPostCount'  => '5',
        'fontFamily'       => 'sans',
        'fontSize'         => 'normal',
        'lineHeight'       => 'normal',
        'codeTheme'        => 'ghostline',
        'codeWrap'         => '0',
        'hidePoweredBy'    => '0',
        'features'         => [
            'showSidebar',
            'showToc',
            'showReading',
            'showBackTop',
            'showPrevNext',
            'showComments',
            'showProgress',
        ],
    ];

    \Typecho\Db::get()->query(
        \Typecho\Db::get()->insert('table.options')->rows([
            'name'  => $key,
            'user'  => 0,
            'value' => json_encode($defaults),
        ])
    );
}
