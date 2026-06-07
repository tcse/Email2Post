<?php
// /plugins/tcse/email2post/core/init.php

// ============================================
// 1. БАЗОВЫЕ ПУТИ (обязательные)
// ============================================
define('TCSE_ROOT', dirname(__DIR__));           // Корень плагина
define('TCSE_CORE', TCSE_ROOT . '/core/');       // Папка с ядром
define('TCSE_DATA', TCSE_ROOT . '/data/');       // Папка с данными
define('TCSE_TEMPLATES', TCSE_ROOT . '/templates/'); // Папка с шаблонами

// ============================================
// 2. ЗАГРУЗКА КОНФИГУРАЦИИ
// ============================================
$configFile = TCSE_DATA . 'config.php';
if (!file_exists($configFile)) {
    die('Config file not found');
}
$config = include $configFile;

// ============================================
// 3. ОСНОВНЫЕ НАСТРОЙКИ (из корня config.php)
// ============================================
define('BASE_URL', rtrim($config['base_url'] ?? '', '/'));
define('SECRET_KEY', $config['secret_key'] ?? '');
define('DEBUG', $config['debug'] ?? false);
define('ENABLE_LOGGING', $config['enable_logging'] ?? false);

// ============================================
// 4. ПУТИ К ФАЙЛАМ (из секции 'paths')
// ============================================
$paths = $config['paths'] ?? [];
define('POSTS_EMAIL_FILE', $paths['posts_email_file'] ?? TCSE_DATA . 'posts_email.json');
define('PENDING_POSTS_FILE', $paths['pending_posts_file'] ?? TCSE_DATA . 'pending_posts.json');
define('LOG_DIR', $paths['log_dir'] ?? TCSE_DATA . 'logs');

// ============================================
// 5. НАСТРОЙКИ САЙТА (из секции 'site')
// ============================================
$siteConfig = $config['site'] ?? [];
define('SITE_TITLE', $siteConfig['title'] ?? 'Блог из email');
define('SITE_BLOG_TITLE', $siteConfig['blog_title'] ?? SITE_TITLE);
define('SITE_DESCRIPTION', $siteConfig['description'] ?? 'Публикации из email-писем');
define('SITE_LOGO_URL', $siteConfig['logo_url'] ?? BASE_URL . '/assets/logo.jpg');

// Цветовая схема (тёмная тема по умолчанию)
define('ACCENT_COLOR', $siteConfig['accent_color'] ?? '#3b82f6');
define('BG_COLOR', $siteConfig['background_color'] ?? '#0a0a0a');
define('TEXT_COLOR', $siteConfig['text_color'] ?? '#e8e8e8');
define('BORDER_COLOR', $siteConfig['border_color'] ?? '#2a2a2a');

// Цветовая схема (светлая тема)
$lightTheme = $siteConfig['light_theme'] ?? [];
define('LIGHT_ACCENT_COLOR', $lightTheme['accent_color'] ?? '#3b82f6');
define('LIGHT_BG_COLOR', $lightTheme['background_color'] ?? '#f8fafc');
define('LIGHT_TEXT_COLOR', $lightTheme['text_color'] ?? '#0f172a');
define('LIGHT_BORDER_COLOR', $lightTheme['border_color'] ?? '#e2e8f0');

// ============================================
// 6. НАСТРОЙКИ КАНАЛА (из секции 'channel') - для Telegram
// ============================================
$channelConfig = $config['channel'] ?? [];
define('CHANNEL_USERNAME', $channelConfig['channel_username'] ?? '');
define('CHANNEL_TITLE', $channelConfig['blog_title'] ?? SITE_TITLE);
define('CHANNEL_DESCRIPTION', $channelConfig['site_description'] ?? SITE_DESCRIPTION);
define('CHANNEL_URL', 'https://t.me/' . CHANNEL_USERNAME);

// ============================================
// 7. НАСТРОЙКИ EMAIL (из секции 'email')
// ============================================
$emailConfig = $config['email'] ?? [];
define('EMAIL_ENABLED', $emailConfig['enabled'] ?? false);

// IMAP настройки
$imapConfig = $emailConfig['imap'] ?? [];
define('IMAP_HOST', $imapConfig['host'] ?? '');
define('IMAP_USERNAME', $imapConfig['username'] ?? '');
define('IMAP_PASSWORD', $imapConfig['password'] ?? '');
define('IMAP_CHECK_INTERVAL', $imapConfig['check_interval'] ?? 300);

// Настройки публикации (пароль, статус)
$publishingConfig = $emailConfig['publishing'] ?? [];
define('REQUIRE_PASSWORD', $publishingConfig['require_password'] ?? true);
define('DEFAULT_POST_STATUS', $publishingConfig['default_status'] ?? 'pending');

// Пароли доступа (массив, поэтому не константа, но можно определить)
$publishPasswords = $emailConfig['publish_passwords'] ?? [];

// Модерация
$moderationConfig = $emailConfig['moderation'] ?? [];
define('ADMIN_EMAIL', $moderationConfig['admin_email'] ?? '');
define('NOTIFY_ON_PENDING', $moderationConfig['notify_on_pending'] ?? true);
define('SECRET_LINK_TTL', $moderationConfig['secret_link_ttl'] ?? 604800); // 7 дней
define('MODERATION_BASE_URL', $moderationConfig['base_url'] ?? BASE_URL);

// Ограничения
$limitsConfig = $emailConfig['limits'] ?? [];
define('MAX_EMAILS_PER_RUN', $limitsConfig['max_emails_per_run'] ?? 10);
define('MAX_ATTACHMENT_SIZE', $limitsConfig['max_attachment_size'] ?? 5 * 1024 * 1024);
define('ALLOWED_ATTACHMENTS', $limitsConfig['allowed_attachments'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Безопасность
$securityConfig = $emailConfig['security'] ?? [];
define('ALLOWED_SENDERS', $securityConfig['allowed_senders'] ?? []);
define('REJECT_SPAM', $securityConfig['reject_spam'] ?? false);

// ============================================
// 8. НАСТРОЙКИ БЛОГА (из секции 'blog')
// ============================================
$blogConfig = $config['blog'] ?? [];
define('POSTS_PER_PAGE', $blogConfig['posts_per_page'] ?? 12);
define('SHOW_EXCERPTS', $blogConfig['show_excerpts'] ?? true);
define('ENABLE_COMMENTS', $blogConfig['enable_comments'] ?? false);
define('GALLERY_AUTOPLAY', $blogConfig['gallery_autoplay'] ?? true);
define('GALLERY_DELAY', $blogConfig['gallery_delay'] ?? 5000);

// ============================================
// 9. НАСТРОЙКИ ТЕМЫ (из секции 'theme')
// ============================================
$themeConfig = $config['theme'] ?? [];
define('ACTIVE_THEME', $themeConfig['active'] ?? 'minimal');
define('THEME_PATH', $themeConfig['path'] ?? 'templates');
define('TEMPLATE_PATH', TCSE_ROOT . '/' . THEME_PATH . '/' . ACTIVE_THEME);

// ============================================
// 10. НАСТРОЙКИ ПАРСЕРОВ (из секции 'parsers')
// ============================================
$parsersConfig = $config['parsers'] ?? [];
define('DEFAULT_PARSER', $parsersConfig['default'] ?? 'auto');

// Общие настройки парсеров
$commonParserConfig = $parsersConfig['common'] ?? [];
define('PARSER_HASHTAGS', $commonParserConfig['hashtags'] ?? true);
define('PARSER_AUTOLINKS', $commonParserConfig['autolinks'] ?? true);
define('PARSER_NL2BR', $commonParserConfig['nl2br'] ?? true);
define('PARSER_TARGET_BLANK', $commonParserConfig['target_blank'] ?? true);

// Настройки Plain Text парсера
$plainConfig = $parsersConfig['plain'] ?? [];
define('PLAIN_PARSER_ENABLED', $plainConfig['enabled'] ?? true);

// Настройки Markdown парсера
$markdownConfig = $parsersConfig['markdown'] ?? [];
define('MARKDOWN_PARSER_ENABLED', $markdownConfig['enabled'] ?? true);

// Настройки BBCode DLE парсера
$bbcodeDleConfig = $parsersConfig['bbcode_dle'] ?? [];
define('BBCODE_DLE_ENABLED', $bbcodeDleConfig['enabled'] ?? true);

// Настройки BBCode Full парсера
$bbcodeFullConfig = $parsersConfig['bbcode_full'] ?? [];
define('BBCODE_FULL_ENABLED', $bbcodeFullConfig['enabled'] ?? true);

// ============================================
// 11. PHP НАСТРОЙКИ (отладка, ошибки)
// ============================================
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================
// 12. СОЗДАНИЕ НЕОБХОДИМЫХ ПАПОК
// ============================================
if (ENABLE_LOGGING && !is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// Папка для загрузок (если не существует)
$uploadsDir = TCSE_DATA . 'uploads/';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

$emailAttachmentsDir = TCSE_DATA . 'uploads/email_attachments/';
if (!is_dir($emailAttachmentsDir)) {
    mkdir($emailAttachmentsDir, 0755, true);
}

// ============================================
// 13. ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ (глобальные)
// ============================================

/**
 * Получение значения из конфига с запасным вариантом
 */
function config(string $key, $default = null) {
    global $config;
    $keys = explode('.', $key);
    $value = $config;
    foreach ($keys as $segment) {
        if (!isset($value[$segment])) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

/**
 * Форматирование даты
 */
function formatDate(string $date, string $format = 'd.m.Y'): string {
    return date($format, strtotime($date));
}

/**
 * Обрезка текста до нужной длины
 */
function truncate(string $text, int $length = 200, string $suffix = '...'): string {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}