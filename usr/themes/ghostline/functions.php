<?php
/**
 * Ghostline — a quiet line between ghost and shell.
 *
 * 主题入口文件。实际逻辑分放在 includes/ 目录：
 *   config.php   后台设置表单与文章自定义字段
 *   helpers.php  供模板调用的配置读取与数据处理函数
 *   color.php    预设色板、暗色适配与 CSS 变量输出
 *
 * @package Ghostline
 * @author Ghostline
 * @version 0.1.0
 * @link https://github.com/fansuregrin1/ghostline
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/color.php';
require_once __DIR__ . '/includes/config.php';
