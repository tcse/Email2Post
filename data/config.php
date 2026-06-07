<?php
// /plugins/tcse/email2post/data/config.php

return [
    // ============================================
    // 1. ОСНОВНЫЕ НАСТРОЙКИ
    // ============================================
    'base_url' => 'https://email2post.tcse-cms.com/',
    'secret_key' => 'mysecret123',
    'enable_logging' => false,
    'debug' => false,

    // ============================================
    // 2. ПУТИ К ФАЙЛАМ ДАННЫХ
    // ============================================
    'paths' => [
        'posts_email_file' => __DIR__ . '/posts_email.json',
        'pending_posts_file' => __DIR__ . '/pending_posts.json',
        'log_dir' => __DIR__ . '/logs',
    ],

    // ============================================
    // 3. EMAIL ПУБЛИКАЦИИ
    // ============================================
    'email' => [
        'enabled' => true,
        
        // IMAP подключение
        'imap' => [
            'host' => '{imap.masterhost.ru:993/imap/ssl}INBOX',
            'username' => 'SECRET@sitename.com',
            'password' => '1q2w3e4r5t6y7u8i9o0p',
            'check_interval' => 300,
            'mark_as_read' => true,
            'delete_after_process' => false,
        ],
        
        // Правила публикации
        'publishing' => [
            'require_password' => true,
            'default_status' => 'pending',
            'strip_signatures' => true,
        ],
        
        // Пароли доступа (пароль => автор)
        'publish_passwords' => [
            'blog2emqil2026' => 'Автор из email',
        ],
        
        // Модерация (для email)
        'moderation' => [
            'admin_email' => 'talik@tcse-cms.com',
            'notify_on_pending' => true,
            'secret_link_ttl' => 604800,
            'base_url' => 'https://email2post.tcse-cms.com',
        ],
        
        // Ограничения
        'limits' => [
            'max_emails_per_run' => 10,
            'max_attachment_size' => 5 * 1024 * 1024,
            'allowed_attachments' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        ],
        
        // Безопасность
        'security' => [
            'allowed_senders' => [],
            'reject_spam' => false,
        ],
    ],

    // ============================================
    // 4. ВЕБ-ИНТЕРФЕЙС (сайт)
    // ============================================
    'site' => [
        'title' => 'Блог из email',
        'description' => 'Публикации из email-писем',
        'logo_url' => 'https://email2post.tcse-cms.com/assets/logo.jpg',
        'accent_color' => '#3b82f6',
        'background_color' => '#0a0a0a',
        'text_color' => '#e8e8e8',
        'border_color' => '#2a2a2a',
        
        // Светлая тема (опционально)
        'light_theme' => [
            'accent_color' => '#3b82f6',
            'background_color' => '#f8fafc',
            'text_color' => '#0f172a',
            'border_color' => '#e2e8f0',
        ],
    ],

    // ============================================
    // 5. НАСТРОЙКИ КАНАЛА (Telegram)
    // ============================================
    'channel' => [                              // ← ДОБАВИТЬ ВСЮ ЭТУ СЕКЦИЮ
        'blog_title' => 'Блог из email',        // Название блога в шапке
        'channel_username' => 'tcsecms',        // Имя Telegram-канала (без @)
        'site_description' => 'Публикации из email-писем',
    ],
    
    // Дизайн / шаблоны
    'theme' => [
        'active' => 'minimal',
        'path' => 'templates',
    ],
    
    // Блог
    'blog' => [
        'posts_per_page' => 12,
        'show_excerpts' => true,
        'enable_comments' => false,
        'gallery_autoplay' => true,
        'gallery_delay' => 5000,
    ],

    // ============================================
    // НАСТРОЙКИ ПАРСЕРОВ КОНТЕНТА
    // ============================================
    'parsers' => [
        // Глобальный режим: 'auto', 'plain', 'markdown', 'bbcode_dle', 'bbcode_full'
        'default' => 'auto',
        
        // === НАСТРОЙКИ ДЛЯ ВСЕХ ПАРСЕРОВ ===
        'common' => [
            'hashtags' => true,        // Преобразовывать #теги в ссылки
            'autolinks' => true,       // Преобразовывать URL в ссылки
            'nl2br' => true,           // Заменять переносы строк на <br>
            'target_blank' => true,    // Открывать ссылки в новой вкладке
        ],
        
        // === PLAIN TEXT ===
        'plain' => [
            'enabled' => true,
            'autolinks' => true,       // Ссылки делать кликабельными
            'hashtags' => true,        // Хештеги в ссылки
            'nl2br' => true,           // Переносы строк в <br>
        ],
        
        // === MARKDOWN ===
        'markdown' => [
            'enabled' => true,
            'tables' => true,          // Поддержка таблиц
            'headers' => true,         // Заголовки #, ##, ###
            'bold_italic' => true,     // **жирный**, *курсив*
            'codes' => true,           // `код` и ```блок кода```
            'lists' => true,           // Списки - и 1.
            'quotes' => true,          // Цитаты > и &gt;
            'hr' => true,              // Горизонтальные линии ---
            'links' => true,           // [текст](url)
            'images' => true,          // ![alt](url)
            'autolinks' => true,       // Автоматические ссылки
            'hashtags' => true,        // Хештеги
        ],
        
        // === BBCODE (DLE-совместимый) ===
        'bbcode_dle' => [
            'enabled' => true,
            'bold' => true,            // [b]текст[/b]
            'italic' => true,          // [i]текст[/i]
            'underline' => true,       // [u]текст[/u]
            'strike' => true,          // [s]текст[/s]
            'code' => true,            // [code]текст[/code]
            'quote' => true,           // [quote]текст[/quote]
            'headers' => true,         // [h1]...[h3]
            'lists' => true,           // [list], [ol=1], [*]
            'links' => true,           // [url] и [url=...]
            'images' => true,          // [img]url[/img]
            'colors' => true,          // [color=red]текст[/color]
            'sizes' => true,           // [size=20]текст[/size]
            'align' => true,           // [center], [left], [right]
            'autolinks' => true,       // Автоматические ссылки
            'hashtags' => true,        // Хештеги
        ],
        
        // === BBCODE РАСШИРЕННЫЙ ===
        'bbcode_full' => [
            'enabled' => true,
            'extend' => 'bbcode_dle',   // Наследует все настройки bbcode_dle
            'tables' => true,           // [table], [tr], [td], [th]
            'video' => true,            // [video]youtube.com/watch?v=...[/video]
            'spoiler' => true,          // [spoiler]скрытый текст[/spoiler]
            'font' => true,             // [font=Arial]текст[/font]
            'email' => true,            // [email]address@example.com[/email]
        ],
    ],
];