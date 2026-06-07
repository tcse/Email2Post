<?php
// /plugins/tcse/email2post/core/parsers/common.php

/**
 * Преобразование хештегов в ссылки
 */
function hashtagsToLinks(string $text): string {
    return preg_replace_callback(
        '/(^|\s)(#[^\s#!@$%^&*()\[\]{}<>~`=+\\\|;:\'",.?\/]+)/u',
        function($m) {
            $tag = ltrim($m[2], '#');
            return $m[1] . '<a href="' . BASE_URL . '/tag/' . urlencode($tag) . '" class="tag-link">' . $m[2] . '</a>';
        },
        $text
    );
}

/**
 * Определение формата контента
 */
function detectContentFormat(string $text): string {
    // Маркеры Markdown
    if (preg_match('/^#{1,6}\s+\S+/m', $text) || preg_match('/^\*\*.*\*\*/', $text) || 
        preg_match('/\[.*\]\(.*\)/', $text) || preg_match('/^\|.+\|$/m', $text)) {
        return 'markdown';
    }
    
    // Маркеры BBCode
    if (preg_match('/\[b\]/i', $text) || preg_match('/\[i\]/i', $text) || 
        preg_match('/\[url\]/i', $text) || preg_match('/\[quote\]/i', $text) ||
        preg_match('/\[ul\]/i', $text) || preg_match('/\[h1\]/i', $text)) {
        return 'bbcode';
    }
    
    return 'plain';
}