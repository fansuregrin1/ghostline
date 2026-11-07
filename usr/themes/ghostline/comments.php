<?php
/**
 * Ghostline 评论区
 *
 * @package Ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
?>
<section class="comments" id="comments">
    <?php $this->comments()->to($comments); ?>

    <h2 class="section-title">
        <?php $comments->commentsNum(_t('评论'), _t('1 条评论'), _t('%d 条评论')); ?>
    </h2>

    <?php if ($comments->have()): ?>
        <?php $comments->listComments([
            'avatarSize' => 40,
            'before'     => '<ol class="comment-list">',
            'after'      => '</ol>',
        ]); ?>

        <nav class="pagination comment-pagination" aria-label="<?php _e('评论分页'); ?>">
            <?php $comments->pageNav('&larr; ' . _t('更早'), _t('更新') . ' &rarr;', 3); ?>
        </nav>
    <?php endif; ?>

    <?php if ($this->allow('comment')): ?>
        <div class="respond" id="<?php $this->respondId(); ?>">
            <?php $comments->cancelReply(); ?>

            <h2 class="section-title respond-title" id="response"><?php _e('写下你的想法'); ?></h2>

            <form class="comment-form" method="post" action="<?php $this->commentUrl(); ?>" id="comment-form">
                <?php if ($this->user->hasLogin()): ?>
                    <p class="logged-in">
                        <?php _e('已登录为'); ?>
                        <a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a>
                        <a class="link-muted" href="<?php $this->options->logoutUrl(); ?>"><?php _e('退出'); ?></a>
                    </p>
                <?php else: ?>
                    <div class="form-row">
                        <label for="author"><?php _e('称呼'); ?></label>
                        <input type="text" name="author" id="author" value="<?php $this->remember('author'); ?>" required>
                    </div>
                    <div class="form-row">
                        <label for="mail">
                            <?php _e('邮箱'); ?><?php if ($this->options->commentsRequireMail): ?><span class="req">*</span><?php endif; ?>
                        </label>
                        <input type="email" name="mail" id="mail" value="<?php $this->remember('mail'); ?>"
                               <?php if ($this->options->commentsRequireMail): ?>required<?php endif; ?>>
                    </div>
                    <div class="form-row">
                        <label for="url">
                            <?php _e('网站'); ?><?php if ($this->options->commentsRequireUrl): ?><span class="req">*</span><?php endif; ?>
                        </label>
                        <input type="url" name="url" id="url" placeholder="https://"
                               value="<?php $this->remember('url'); ?>"
                               <?php if ($this->options->commentsRequireUrl): ?>required<?php endif; ?>>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <label for="textarea"><?php _e('评论内容'); ?></label>
                    <textarea rows="6" name="text" id="textarea" required><?php $this->remember('text'); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button-primary"><?php _e('提交评论'); ?></button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <p class="comments-closed"><?php _e('评论已关闭。'); ?></p>
    <?php endif; ?>
</section>
