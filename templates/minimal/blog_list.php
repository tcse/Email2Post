<?php
// /templates/minimal/blog_list.php

// Подключаем общие функции для очистки текста
require_once TCSE_CORE . 'parsers/common.php';

/**
 * Очистка текста от Markdown и BBCode разметки для отображения в списке
 */
function cleanForExcerpt(string $text): string {
    // Удаляем изображения
    $text = preg_replace('/!\[.*?\]\(.*?\)/', '', $text);
    $text = preg_replace('/\[img\].*?\[\/img\]/is', '', $text);
    
    // Удаляем ссылки, оставляя текст
    $text = preg_replace('/\[url\](.*?)\[\/url\]/is', '$1', $text);
    $text = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/is', '$2', $text);
    $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $text);
    
    // Удаляем BBCode теги
    $text = preg_replace('/\[\/?[a-z]+[=0-9a-z]*\]/i', '', $text);
    
    // Удаляем Markdown разметку
    $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
    $text = preg_replace('/__(.*?)__/', '$1', $text);
    $text = preg_replace('/\*(.*?)\*/', '$1', $text);
    $text = preg_replace('/_(.*?)_/', '$1', $text);
    $text = preg_replace('/`(.*?)`/', '$1', $text);
    
    // Удаляем заголовки Markdown
    $text = preg_replace('/^#{1,6}\s+/m', '', $text);
    
    // Удаляем горизонтальные линии
    $text = preg_replace('/^---+$/m', '', $text);
    $text = preg_replace('/^\*\*\*+$/m', '', $text);
    
    // Удаляем цитаты
    $text = preg_replace('/^>\s+/m', '', $text);
    $text = preg_replace('/^&gt;\s+/m', '', $text);
    
    // Удаляем лишние пробелы и переносы
    $text = preg_replace('/\s+/', ' ', $text);
    
    return trim($text);
}

$title = $config['channel']['blog_title'] ?? 'Записи блога';
?>

<h1 style="
    font-size: 2rem;
    margin-bottom: 2rem;
    text-align: center;
"><?= htmlspecialchars($title) ?></h1>

<div class="posts-feed">
    <?php if (empty($posts)): ?>
        <p style="text-align: center; color: var(--text-muted);">Пока нет записей.</p>
    <?php else: ?>
        <?php foreach ($posts as $item): 
            // Получаем заголовок
            $title = $item['title'] ?? '';
            if (empty($title) && !empty($item['caption'])) {
                $title = trim(explode("\n", $item['caption'])[0]);
                $title = cleanForExcerpt($title);
            }
            if (empty($title) && !empty($item['text'])) {
                $title = trim(explode("\n", strip_tags($item['text']))[0]);
                $title = cleanForExcerpt($title);
            }
            if (empty($title)) $title = 'Запись без названия';
            
            $date = date('d.m.Y', strtotime($item['date']));
            $url = BASE_URL . '/post/' . $item['id'];
            
            // Получаем и очищаем текст для анонса
            $excerpt = '';

            // ПРИОРИТЕТ 1: Используем поле caption (если есть и не пустое)
            if (!empty($item['caption']) && trim($item['caption']) !== '...') {
                $excerpt = $item['caption'];
            } 
            // ПРИОРИТЕТ 2: Если caption пустой, используем text
            elseif (!empty($item['text'])) {
                $excerpt = $item['text'];
                // Обрезаем до 200 символов
                if (mb_strlen($excerpt) > 200) {
                    $excerpt = mb_substr($excerpt, 0, 197) . '...';
                }
            }

            // Очищаем от разметки (Markdown/BBCode)
            $excerpt = cleanForExcerpt($excerpt);
            $excerpt = strip_tags($excerpt);
        ?>
            <article class="post-item">
                <h2 class="post-title">
                    <a href="<?= $url ?>"><?= htmlspecialchars($title) ?></a>
                </h2>
                <div class="post-meta">
                    <time datetime="<?= $item['date'] ?>"><?= $date ?></time>
                    <?php if (!empty($item['tags']) && is_array($item['tags'])): ?>
                        <span>•</span>
                        <span>
                            <?php foreach (array_slice($item['tags'], 0, 3) as $tag): ?>
                                <a href="<?= BASE_URL ?>/tag/<?= urlencode($tag) ?>" style="color: var(--text-muted);">#<?= htmlspecialchars($tag) ?></a>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="post-excerpt">
                    <?= $excerpt ?>
                </div>
                <a href="<?= $url ?>" class="post-read-more">Читать далее →</a>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i == $currentPage): ?>
            <span class="active"><span><?= $i ?></span></span>
        <?php else: ?>
            <a href="<?= BASE_URL . ($i === 1 ? '' : '/page/' . $i) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>