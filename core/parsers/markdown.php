<?php
// /plugins/tcse/email2post/core/parsers/markdown.php

/**
 * Простой парсер Markdown (исправленная версия)
 */
function simpleMarkdown(string $text): string {
    // === НОРМАЛИЗАЦИЯ ПЕРЕНОСОВ СТРОК ===
    // Сначала преобразуем \r\n в \n
    $text = str_replace("\r\n", "\n", $text);
    // Затем убираем лишние \r
    $text = str_replace("\r", "\n", $text);
    // Заменяем множественные переносы на двойные (но не более)
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    
    // Экранируем HTML (безопасность)
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // === УДАЛЯЕМ ЛИШНИЕ ```php В НАЧАЛЕ И КОНЦЕ ===
    $text = preg_replace('/^```php\s*\n/', '', $text);
    $text = preg_replace('/\n```\s*$/', '', $text);
    
    // === ИЗОБРАЖЕНИЯ (ДОЛЖНЫ БЫТЬ ПЕРВЫМИ) ===
    // BBCode: [img]url[/img]
    $text = preg_replace_callback('/\[img\](.*?)\[\/img\]/is', function($m) {
        $url = trim($m[1]);
        $url = htmlspecialchars_decode($url);
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return '<img src="' . $url . '" alt="Изображение" class="img-fluid" loading="lazy">';
        }
        return '';
    }, $text);
    
    // Markdown: ![alt](url)
    $text = preg_replace_callback('/!\[(.*?)\]\((.*?)\)/', function($m) {
        $alt = htmlspecialchars($m[1]);
        $url = htmlspecialchars($m[2]);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return '<img src="' . $url . '" alt="' . $alt . '" class="img-fluid" loading="lazy">';
        }
        return '';
    }, $text);
    
    // === ЗАГОЛОВКИ ===
    $text = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $text);
    
    // === ЖИРНЫЙ И КУРСИВ ===
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.*?)__/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/_(.*?)_/', '<em>$1</em>', $text);
    
    // === МОНОШИРИННЫЙ ТЕКСТ ===
    $text = preg_replace('/`(.*?)`/', '<code>$1</code>', $text);
    $text = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $text);
    
    // === ССЫЛКИ ===
    $text = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function($m) {
        return '<a href="' . htmlspecialchars($m[2]) . '" target="_blank" rel="noopener">' . $m[1] . '</a>';
    }, $text);
    
    // === ТАБЛИЦЫ (исправленная версия) ===
    $text = parseMarkdownTables($text);
    
    // === СПИСКИ ===
    // Нумерованные списки
    $text = preg_replace_callback('/(?:^[0-9]+\. .*$\n?)+/m', function($m) {
        $items = explode("\n", trim($m[0]));
        $html = "<ol>\n";
        foreach ($items as $item) {
            $content = preg_replace('/^[0-9]+\. (.*)$/', '$1', $item);
            $html .= "<li>" . trim($content) . "</li>\n";
        }
        $html .= "</ol>\n";
        return $html;
    }, $text);
    
    // Маркированные списки
    $text = preg_replace_callback('/(?:^[-*] .*$\n?)+/m', function($m) {
        $items = explode("\n", trim($m[0]));
        $html = "<ul>\n";
        foreach ($items as $item) {
            $content = preg_replace('/^[-*] (.*)$/', '$1', $item);
            $html .= "<li>" . trim($content) . "</li>\n";
        }
        $html .= "</ul>\n";
        return $html;
    }, $text);
    
    // === ЦИТАТЫ ===
    $text = preg_replace('/^&gt; (.*?)$/m', '<blockquote>$1</blockquote>', $text);
    $text = preg_replace('/^> (.*?)$/m', '<blockquote>$1</blockquote>', $text);
    
    // === ГОРИЗОНТАЛЬНАЯ ЛИНИЯ ===
    $text = preg_replace('/^---\s*$/m', '<hr>', $text);
    $text = preg_replace('/^\*\*\*\s*$/m', '<hr>', $text);
    
    // === АВТОМАТИЧЕСКИЕ ССЫЛКИ ===
    $text = preg_replace_callback(
        '/(https?:\/\/[^\s<]+[^<.,:;"\')\]\s])/',
        function($m) {
            $url = $m[1];
            $display = mb_strlen($url) > 50 ? mb_substr($url, 0, 40) . '...' : $url;
            return '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener">' . $display . '</a>';
        },
        $text
    );
    
    // === ПАРАГРАФЫ (но не для блочных элементов) ===
    $paragraphs = explode("\n\n", $text);
    $result = '';
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if (empty($para)) continue;
        
        // Блочные элементы, которые не нужно оборачивать в <p>
        $blockElements = [
            '/^<(table|ul|ol|blockquote|pre|h[1-6]|hr|div)/i',
            '/^<li>/i',
            '/^<thead>/i',
            '/^<tbody>/i',
            '/^<tr>/i',
            '/^<th>/i',
            '/^<td>/i'
        ];
        
        $isBlockElement = false;
        foreach ($blockElements as $pattern) {
            if (preg_match($pattern, $para)) {
                $isBlockElement = true;
                break;
            }
        }
        
        if ($isBlockElement) {
            $result .= $para . "\n";
        } else {
            // Обычный текст - просто оборачиваем в <p>
            $result .= '<p>' . $para . '</p>' . "\n";
        }
    }
    
    return trim($result);
}

/**
 * Парсинг Markdown таблиц (рабочая версия, которую вы подтвердили)
 */
function parseMarkdownTables(string $text): string {
    $lines = explode("\n", $text);
    $inTable = false;
    $headers = [];
    $rows = [];
    $tableStartIndex = 0;
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        
        if (preg_match('/^\|(.+)\|$/', $line, $match)) {
            $cells = explode('|', $match[1]);
            $cells = array_map('trim', $cells);
            
            // Удаляем пустые ячейки в конце
            while (count($cells) > 0 && end($cells) === '') {
                array_pop($cells);
            }
            
            // Строка-разделитель
            if (count($cells) == 1 && preg_match('/^[\s\-:]+$/', $cells[0])) {
                $inTable = true;
                continue;
            }
            
            if (!$inTable) {
                $headers = $cells;
                $inTable = true;
                $tableStartIndex = $i;
            } else {
                if (count($cells) > 0) {
                    $rows[] = $cells;
                }
            }
        } else {
            if ($inTable && !empty($headers) && count($rows) > 0) {
                $html = '<table class="markdown-table">';
                $html .= '<thead><tr>';
                foreach ($headers as $h) {
                    $html .= '<th>' . $h . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                
                foreach ($rows as $row) {
                    $html .= '<tr>';
                    for ($j = 0; $j < count($headers); $j++) {
                        $cell = isset($row[$j]) ? $row[$j] : '';
                        $html .= '<td>' . $cell . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
                
                $endIndex = $i - 1;
                $tableLines = array_slice($lines, $tableStartIndex, $endIndex - $tableStartIndex + 1);
                $tableText = implode("\n", $tableLines);
                
                if (strpos($text, $tableText) !== false) {
                    $text = str_replace($tableText, $html, $text);
                    $lines = explode("\n", $text);
                }
            }
            $inTable = false;
            $headers = [];
            $rows = [];
        }
    }
    
    return $text;
}