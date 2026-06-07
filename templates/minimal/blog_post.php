<?php
// /plugins/tcse/email2post/templates/minimal/blog_post.php

// Подключаем парсеры
require_once TCSE_CORE . 'parsers/markdown.php';
require_once TCSE_CORE . 'parsers/bbcode.php';
require_once TCSE_CORE . 'parsers/common.php';

$contentRaw = $post['text'] ?? $post['content'] ?? '';

// ============================================
// ПРИОРИТЕТ ОПРЕДЕЛЕНИЯ ФОРМАТА:
// 1. ФОРМАТ ИЗ ПОСТА (если указан в JSON) - САМЫЙ ВЫСОКИЙ ПРИОРИТЕТ
// 2. Принудительный формат из конфига
// 3. Автоопределение
// ============================================

$parsersConfig = $config['parsers'] ?? [];
$commonConfig = $parsersConfig['common'] ?? [];

// Настройки отдельных парсеров
$markdownEnabled = $parsersConfig['markdown']['enabled'] ?? true;
$bbcodeEnabled = $parsersConfig['bbcode_dle']['enabled'] ?? true;
$forceFormat = $parsersConfig['default'] ?? 'auto';

// Дополнительные настройки
$autoDetect = true;
$hybridMode = true;

// ============================================
// ПРОВЕРЯЕМ ФОРМАТ ИЗ ПОСТА (приоритет 1)
// ============================================
$postFormat = $post['format'] ?? null;

if ($postFormat && in_array($postFormat, ['markdown', 'bbcode', 'plain'])) {
    // Используем формат, указанный в посте
    $parsedContent = $contentRaw;
    $usedParsers = [];
    
    if ($postFormat === 'markdown' && $markdownEnabled) {
        $parsedContent = simpleMarkdown($parsedContent);
        $usedParsers[] = 'markdown';
        $displayFormat = 'markdown';
    } elseif ($postFormat === 'bbcode' && $bbcodeEnabled) {
        $parsedContent = bbcodeToHtml($parsedContent);
        $usedParsers[] = 'bbcode';
        $displayFormat = 'bbcode';
    } else {
        // plain text
        $parsedContent = nl2br(htmlspecialchars($parsedContent));
        $displayFormat = 'plain';
    }
    
} else {
    // ============================================
    // ФОРМАТ НЕ УКАЗАН В ПОСТЕ - ИСПОЛЬЗУЕМ КОНФИГ ИЛИ АВТООПРЕДЕЛЕНИЕ
    // ============================================
    
    $hasMarkdown = false;
    $hasBBCode = false;
    $detectedFormat = 'plain';
    
    if ($autoDetect) {
        $detectedFormat = detectContentFormat($contentRaw);
        $hasMarkdown = ($detectedFormat === 'markdown') || 
                       preg_match('/^# /m', $contentRaw) || 
                       preg_match('/^\*\*.*\*\*/', $contentRaw) || 
                       preg_match('/\[.*\]\(.*\)/', $contentRaw) ||
                       preg_match('/^\|.+\|$/m', $contentRaw);
        
        $hasBBCode = ($detectedFormat === 'bbcode') || 
                     preg_match('/\[b\]/i', $contentRaw) || 
                     preg_match('/\[i\]/i', $contentRaw) || 
                     preg_match('/\[url\]/i', $contentRaw) ||
                     preg_match('/\[quote\]/i', $contentRaw) ||
                     preg_match('/\[ul\]/i', $contentRaw) ||
                     preg_match('/\[h1\]/i', $contentRaw);
    }
    
    $parsedContent = $contentRaw;
    $usedParsers = [];
    
    // Режим 1: Принудительный формат из конфига
    if ($forceFormat !== 'auto') {
        switch ($forceFormat) {
            case 'markdown':
                if ($markdownEnabled) {
                    $parsedContent = simpleMarkdown($parsedContent);
                    $usedParsers[] = 'markdown';
                }
                break;
            case 'bbcode_dle':
            case 'bbcode_full':
                if ($bbcodeEnabled) {
                    $parsedContent = bbcodeToHtml($parsedContent);
                    $usedParsers[] = 'bbcode';
                }
                break;
            case 'plain':
                // Только plain text
                break;
        }
    } 
    // Режим 2: Гибридная обработка
    elseif ($hybridMode) {
        if ($markdownEnabled && $hasMarkdown) {
            $parsedContent = simpleMarkdown($parsedContent);
            $usedParsers[] = 'markdown';
        }
        
        if ($bbcodeEnabled && $hasBBCode) {
            $parsedContent = bbcodeToHtml($parsedContent);
            $usedParsers[] = 'bbcode';
        }
        
        if (empty($usedParsers) && $markdownEnabled && $bbcodeEnabled) {
            if ($hasMarkdown || $detectedFormat === 'markdown') {
                $parsedContent = simpleMarkdown($parsedContent);
                $usedParsers[] = 'markdown';
            } elseif ($hasBBCode || $detectedFormat === 'bbcode') {
                $parsedContent = bbcodeToHtml($parsedContent);
                $usedParsers[] = 'bbcode';
            }
        }
    }
    // Режим 3: Автоопределение (только один парсер)
    else {
        switch ($detectedFormat) {
            case 'markdown':
                if ($markdownEnabled) {
                    $parsedContent = simpleMarkdown($parsedContent);
                    $usedParsers[] = 'markdown';
                }
                break;
            case 'bbcode':
                if ($bbcodeEnabled) {
                    $parsedContent = bbcodeToHtml($parsedContent);
                    $usedParsers[] = 'bbcode';
                }
                break;
            default:
                break;
        }
    }
    
    // Определяем бейдж для отображения
    if ($forceFormat !== 'auto') {
        $displayFormat = $forceFormat;
    } elseif (!empty($usedParsers)) {
        $displayFormat = implode('+', $usedParsers);
    } else {
        $displayFormat = 'plain';
    }
}

// ============================================
// ПРИМЕНЯЕМ ОБЩИЕ ПРОЦЕССОРЫ (хештеги, ссылки)
// ============================================

// Преобразование хештегов в ссылки
if ($commonConfig['hashtags'] ?? true) {
    $parsedContent = hashtagsToLinks($parsedContent);
}

// Для plain text добавляем автоссылки, если еще не обработаны
if (empty($usedParsers) || ($forceFormat === 'plain' && !$postFormat)) {
    if ($commonConfig['autolinks'] ?? true) {
        $parsedContent = preg_replace_callback(
            '/(https?:\/\/[^\s<]+[^<.,:;"\')\]\s])/',
            function($m) {
                $url = $m[1];
                $display = mb_strlen($url) > 50 ? mb_substr($url, 0, 40) . '...' : $url;
                return '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener">' . $display . '</a>';
            },
            $parsedContent
        );
    }
    
    // Переносы строк для plain text
    if ($commonConfig['nl2br'] ?? true) {
        $parsedContent = nl2br($parsedContent);
    }
}

// Если контент пустой после всех обработок - показываем исходник
if (empty(trim(strip_tags($parsedContent)))) {
    $parsedContent = nl2br(htmlspecialchars($contentRaw));
}
?>

<!-- HTML-шаблон -->
<article class="post">
    <header class="post-header">
        <h1><?= htmlspecialchars($post['title'] ?? 'Без названия') ?></h1>
        <div class="post-meta">
            <time datetime="<?= date('Y-m-d', strtotime($post['date'])) ?>">
                <?= date('d.m.Y', strtotime($post['date'])) ?>
            </time>
            <?php if (!empty($post['author'])): ?>
                <span class="separator">•</span>
                <span>Автор: <?= htmlspecialchars($post['author']) ?></span>
            <?php endif; ?>
            <?php if ($displayFormat !== 'plain'): ?>
                <span class="badge">✨ <?= htmlspecialchars($displayFormat) ?></span>
            <?php endif; ?>
        </div>
    </header>

    <!-- Изображения из email -->
    <?php if (!empty($post['images']) && is_array($post['images']) && count($post['images']) > 0): ?>
        <?php if (count($post['images']) === 1): ?>
            <div class="single-image">
                <img src="<?= BASE_URL . $post['images'][0] ?>" alt="<?= htmlspecialchars($post['title'] ?? 'Изображение') ?>" class="img-fluid rounded mb-4" loading="lazy">
            </div>
        <?php else: ?>
            <div class="gallery swiper mb-4">
                <div class="swiper-wrapper">
                    <?php foreach ($post['images'] as $img): ?>
                        <div class="swiper-slide">
                            <img src="<?= BASE_URL . $img ?>" alt="Фото" class="img-fluid rounded" loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Содержание поста -->
    <div class="post-content">
        <?= $parsedContent ?>
    </div>

    <!-- Теги -->
    <?php if (!empty($post['tags']) && is_array($post['tags'])): ?>
        <div class="post-tags">
            <?php foreach ($post['tags'] as $tag): ?>
                <a href="<?= BASE_URL ?>/tag/<?= urlencode($tag) ?>" class="post-tag">#<?= htmlspecialchars($tag) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Навигация -->
    <div class="post-navigation">
        <a href="<?= BASE_URL ?>/" class="btn-back">← Вернуться к списку</a>
    </div>
</article>

<style>
    /* Базовые стили */
    .post-content h1 { font-size: 2rem; margin: 1.5rem 0 1rem; }
    .post-content h2 { font-size: 1.5rem; margin: 1.25rem 0 0.75rem; }
    .post-content h3 { font-size: 1.25rem; margin: 1rem 0 0.5rem; }
    .post-content p { margin-bottom: 1rem; line-height: 1.6; }
    .post-content ul, .post-content ol { margin: 0.75rem 0 0.75rem 1.5rem; }
    .post-content li { margin-bottom: 0.25rem; }
    .post-content blockquote {
        border-left: 3px solid var(--accent);
        margin: 1rem 0;
        padding-left: 1rem;
        font-style: italic;
        color: var(--text-muted);
    }
    .post-content code {
        background: var(--bg-secondary);
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        font-family: monospace;
        font-size: 0.9em;
    }
    .post-content pre {
        background: var(--bg-secondary);
        padding: 1rem;
        border-radius: 8px;
        overflow-x: auto;
        margin: 1rem 0;
    }
    .post-content pre code {
        background: none;
        padding: 0;
    }
    .post-content hr {
        margin: 1.5rem 0;
        border: none;
        border-top: 1px solid var(--border);
    }
    .post-content a {
        color: var(--accent);
        text-decoration: underline;
    }
    .post-content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 1rem 0;
    }
    .single-image img {max-width: 100%;}
    /* Стили для таблиц */
    .markdown-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0;
        font-size: 0.9rem;
        overflow-x: auto;
        display: block;
    }
    .markdown-table th,
    .markdown-table td {
        border: 1px solid var(--border);
        padding: 0.5rem 0.75rem;
        text-align: left;
    }
    .markdown-table th {
        background: var(--bg-secondary);
        font-weight: 600;
    }
    .markdown-table tr:nth-child(even) {
        background: var(--bg-secondary);
    }
    .badge {
        display: inline-block;
        background: var(--accent-soft);
        color: var(--accent);
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        margin-left: 0.75rem;
    }
    /* Стили для видео */
    .video-wrapper {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        margin: 1.5rem 0;
        background: var(--bg-secondary);
        border-radius: 12px;
        overflow: hidden;
    }
    .video-wrapper iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }
    @media (max-width: 640px) {
        .markdown-table {
            font-size: 0.75rem;
        }
        .markdown-table th,
        .markdown-table td {
            padding: 0.35rem 0.5rem;
        }
    }
</style>