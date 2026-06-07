<?php
// /plugins/tcse/email2post/core/parsers/bbcode.php

/**
 * Парсинг BBCode в HTML (упрощённый и надёжный)
 */
function bbcodeToHtml(string $text): string {
    // Экранируем HTML (безопасность)
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // Сохраняем переносы строк - заменяем \n на специальный маркер
    // Это предотвратит их "склеивание" при обработке BBCode
    $text = str_replace("\n", '___LINE_BREAK___', $text);
    
    // Порядок важен: сначала заменяем парные теги
    $pairs = [
        '/\[b\](.*?)\[\/b\]/is' => '<strong>$1</strong>',
        '/\[i\](.*?)\[\/i\]/is' => '<em>$1</em>',
        '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
        '/\[s\](.*?)\[\/s\]/is' => '<del>$1</del>',
        '/\[code\](.*?)\[\/code\]/is' => '<code>$1</code>',
        '/\[pre\](.*?)\[\/pre\]/is' => '<pre><code>$1</code></pre>',
        '/\[quote\](.*?)\[\/quote\]/is' => '<blockquote>$1</blockquote>',
        '/\[center\](.*?)\[\/center\]/is' => '<div style="text-align: center">$1</div>',
        '/\[h1\](.*?)\[\/h1\]/is' => '<h1>$1</h1>',
        '/\[h2\](.*?)\[\/h2\]/is' => '<h2>$1</h2>',
        '/\[h3\](.*?)\[\/h3\]/is' => '<h3>$1</h3>',
        '/\[hr\]/is' => '<hr>',
    ];
    
    foreach ($pairs as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }
    
    // === ВИДЕО (поддержка [video=URL] и [video]URL[/video]) ===
    // [video=URL] формат
    $text = preg_replace_callback('/\[video=(.*?)\]/is', function($m) {
        return embedVideo($m[1]);
    }, $text);
    
    // [video]URL[/video] формат
    $text = preg_replace_callback('/\[video\](.*?)\[\/video\]/is', function($m) {
        return embedVideo($m[1]);
    }, $text);
    
    // Ссылки
    $text = preg_replace('/\[url\](.*?)\[\/url\]/is', '<a href="$1" target="_blank" rel="noopener">$1</a>', $text);
    $text = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/is', '<a href="$1" target="_blank" rel="noopener">$2</a>', $text);
    
    // Изображения img
    $text = preg_replace('/\[img\](.*?)\[\/img\]/is', '<img src="$1" alt="Изображение" class="img-fluid">', $text);
    // Изображения thumb
    $text = preg_replace('/\[thumb\](.*?)\[\/thumb\]/is', '<img src="$1" alt="Изображение" class="img-fluid">', $text);
    
    // Списки LIST
    $text = preg_replace_callback('/\[list\](.*?)\[\/list\]/is', function($m) {
        $items = preg_replace_callback('/\[\*\](.*?)(?=\[\*\]|$)/is', function($item) {
            return '<li>' . trim($item[1]) . '</li>';
        }, $m[1]);
        return '<ul>' . $items . '</ul>';
    }, $text);

    // Списки UL
    $text = preg_replace_callback('/\[ul\](.*?)\[\/ul\]/is', function($m) {
        $items = preg_replace_callback('/\[li\](.*?)\[\/li\]/is', function($item) {
            return '<li>' . $item[1] . '</li>';
        }, $m[1]);
        return '<ul>' . $items . '</ul>';
    }, $text);
    
    // Списки OL
    $text = preg_replace_callback('/\[ol\](.*?)\[\/ol\]/is', function($m) {
        $items = preg_replace_callback('/\[li\](.*?)\[\/li\]/is', function($item) {
            return '<li>' . $item[1] . '</li>';
        }, $m[1]);
        return '<ol>' . $items . '</ol>';
    }, $text);
    
    // Поддержка [ol=1] и [ol=a] (DLE-стиль)
    $text = preg_replace_callback('/\[ol=([1a])\](.*?)\[\/ol\]/is', function($m) {
        $type = $m[1] === '1' ? '1' : 'a';
        $items = preg_replace_callback('/\[\*\](.*?)(?=\[\*\]|$)/is', function($item) {
            return '<li>' . trim($item[1]) . '</li>';
        }, $m[2]);
        return '<ol type="' . $type . '">' . $items . '</ol>';
    }, $text);
    
    // Цвет и размер
    $text = preg_replace('/\[color=(.*?)\](.*?)\[\/color\]/is', '<span style="color: $1">$2</span>', $text);
    $text = preg_replace('/\[size=(.*?)\](.*?)\[\/size\]/is', '<span style="font-size: $1px">$2</span>', $text);
    
    // Восстанавливаем переносы строк обратно в \n
    $text = str_replace('___LINE_BREAK___', "\n", $text);
    
    // Теперь преобразуем \n в <br> (но не дублируем уже существующие <br>)
    $lines = explode("\n", $text);
    $result = [];
    foreach ($lines as $line) {
        // Если строка не пустая и не начинается с HTML-тега (который уже обработан)
        if (trim($line) !== '' && !preg_match('/^<(?:p|div|h[1-6]|ul|ol|table|blockquote|pre)/i', trim($line))) {
            $result[] = $line . '<br>';
        } elseif (trim($line) !== '') {
            $result[] = $line;
        } else {
            // Пустые строки становятся <br> для сохранения отступов
            $result[] = '<br>';
        }
    }
    
    $text = implode("\n", $result);
    
    return $text;
}

/**
 * Встраивание видео из URL
 */
function embedVideo(string $url): string {
    $url = trim($url);
    
    // YouTube
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
        return '<div class="video-wrapper"><iframe src="https://www.youtube.com/embed/' . $videoId . '" frameborder="0" allowfullscreen></iframe></div>';
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        $videoId = $matches[1];
        return '<div class="video-wrapper"><iframe src="https://player.vimeo.com/video/' . $videoId . '" frameborder="0" allowfullscreen></iframe></div>';
    }
    
    // Rutube
    if (preg_match('/rutube\.ru\/video\/([a-f0-9]+)/', $url, $matches)) {
        $videoId = $matches[1];
        return '<div class="video-wrapper"><iframe src="https://rutube.ru/play/embed/' . $videoId . '" frameborder="0" allowfullscreen></iframe></div>';
    }
    
    // Если не распознали - просто ссылка
    return '<a href="' . $url . '" target="_blank" rel="noopener">' . $url . '</a>';
}