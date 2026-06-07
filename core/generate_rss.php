<?php
// /tmh2.1/core/generate_rss.php
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=1800');

$configFile = __DIR__ . '/../data/config.php';
if (!file_exists($configFile)) {
    die('<?xml version="1.0" encoding="UTF-8"?><error>Config not found</error>');
}

$config = require_once $configFile;
$channel = $config['telegram']['channel'] ?? [];
$rss = $config['rss'] ?? [];

// === Пути к файлам (из секции paths) ===
$paths = $config['paths'] ?? [];
$postsFile = $paths['posts_file'] ?? __DIR__ . '/../data/posts.json';
$postsEmailFile = $paths['posts_email_file'] ?? __DIR__ . '/../data/posts_email.json';

// === Если RSS отключён ===
if (!($rss['enable'] ?? true)) {
    http_response_code(404);
    echo '<?xml version="1.0" encoding="UTF-8"?><error>RSS disabled</error>';
    exit;
}

// === Параметры из config ===
$siteConfig = $config['site'] ?? [];
$feedTitle = $rss['title'] ?? ($siteConfig['title'] ?? 'Блог проекта');
$feedDescription = $rss['description'] ?? ($siteConfig['description'] ?? 'Обновления из Telegram');

$blogBaseUrl = rtrim($config['base_url'], '/');
$postUrlTemplate = $blogBaseUrl . '/post/%s';  // %s поддерживает строковые ID (email_xxx)

$feedLink = $rss['link'] ?? ($blogBaseUrl . '/');
$feedUrl = $rss['feed_url'] ?? $blogBaseUrl . '/core/generate_rss.php';

$maxItems = $rss['max_items'] ?? 20;
$includePhotos = $rss['include_photos'] ?? true;
$includeAudio = $rss['include_audio'] ?? false;
$showFullText = $rss['show_full_text'] ?? true;
$language = $rss['language'] ?? 'ru-RU';
$updatePeriod = $rss['update_period'] ?? 'hourly';
$generator = $rss['generator'] ?? 'TMH by TCSE';

// === Загружаем посты из Telegram ===
$allPosts = [];
if (file_exists($postsFile)) {
    $tgPosts = json_decode(file_get_contents($postsFile), true);
    if (is_array($tgPosts)) {
        foreach ($tgPosts as $id => $post) {
            // Пропускаем пустые
            if (empty($post['text']) && empty($post['caption'])) continue;
            
            $allPosts[] = [
                'id' => $id,
                'date' => $post['date'] ?? date('Y-m-d H:i:s'),
                'title' => $post['caption'] ?? $post['text'] ?? 'Запись',
                'content' => ($post['text'] ?? '') . "\n\n" . ($post['caption'] ?? ''),
                'image' => $post['photo_file_id'] ?? null,
                'audio' => $post['audio'] ?? null,
                'source' => 'telegram'
            ];
        }
    }
}

// === Загружаем посты из Email ===
if (file_exists($postsEmailFile)) {
    $emailPosts = json_decode(file_get_contents($postsEmailFile), true);
    if (is_array($emailPosts)) {
        foreach ($emailPosts as $id => $post) {
            if (empty($post['text']) && empty($post['title'])) continue;
            
            $allPosts[] = [
                'id' => $id,
                'date' => $post['date'] ?? date('Y-m-d H:i:s'),
                'title' => $post['title'] ?? 'Письмо',
                'content' => $post['text'] ?? '',
                'image' => $post['photo_file_id'] ?? null,
                'audio' => null,
                'source' => 'email'
            ];
        }
    }
}

// Сортируем посты от новых к старым
usort($allPosts, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
$allPosts = array_slice($allPosts, 0, $maxItems);

// === Вывод RSS ===
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/">
<channel>
    <title><![CDATA[<?= htmlspecialchars($feedTitle, ENT_XML1) ?>]]></title>
    <link><?= htmlspecialchars($feedLink, ENT_XML1) ?></link>
    <description><![CDATA[<?= htmlspecialchars($feedDescription, ENT_XML1) ?>]]></description>
    <language><?= htmlspecialchars($language, ENT_XML1) ?></language>
    <pubDate><?= date(DATE_RSS) ?></pubDate>
    <generator><?= htmlspecialchars($generator, ENT_XML1) ?></generator>
    <sy:updatePeriod><?= htmlspecialchars($updatePeriod, ENT_XML1) ?></sy:updatePeriod>
    <atom:link href="<?= htmlspecialchars($feedUrl, ENT_XML1) ?>" rel="self" type="application/rss+xml" />

    <?php foreach ($allPosts as $post):
        $postId = $post['id'];
        $title = trim(strip_tags($post['title']));
        $title = mb_strlen($title) > 100 ? mb_substr($title, 0, 100) . '...' : $title;

        $fullText = '';
        if ($showFullText) {
            $fullText = $post['content'];
        } else {
            $fullText = mb_substr($post['content'], 0, 300) . '...';
        }
        $fullText = nl2br(htmlspecialchars($fullText, ENT_XML1));

        $postUrl = sprintf($postUrlTemplate, urlencode($postId));
        $pubDate = date(DATE_RSS, strtotime($post['date']));

        $imageTag = '';
        $enclosureUrl = '';
        $baseUrl = rtrim($config['base_url'], '/');
        
        if ($includePhotos && !empty($post['image'])) {
            $imageUrl = "$baseUrl/core/blog_cover.php?file_id=" . urlencode($post['image']);
            $imageTag = "<p><img src=\"$imageUrl\" alt=\"Изображение\" style=\"max-width:100%;height:auto;\" /></p>";
            $enclosureUrl = $imageUrl;
        }

        $audioTag = '';
        if ($includeAudio && !empty($post['audio']['file_id'])) {
            $audioUrl = "$baseUrl/core/stream.php?id=" . urlencode($post['audio']['file_id']);
            $audioTag = "<p><audio controls src=\"$audioUrl\"></audio></p>";
            $enclosureUrl = $audioUrl;
        }
    ?>
    <item>
        <title><![CDATA[<?= $title ?>]]></title>
        <link><?= htmlspecialchars($postUrl, ENT_XML1) ?></link>
        <guid isPermaLink="true"><?= htmlspecialchars($postUrl, ENT_XML1) ?></guid>
        <pubDate><?= $pubDate ?></pubDate>
        <description><![CDATA[
            <?= $imageTag ?>
            <p><?= $fullText ?></p>
            <?= $audioTag ?>
            <p><a href="<?= htmlspecialchars($postUrl, ENT_XML1) ?>">Читать далее</a></p>
        ]]></description>
        <?php if ($enclosureUrl): ?>
        <enclosure url="<?= htmlspecialchars($enclosureUrl, ENT_XML1) ?>" length="0" type="<?= $includeAudio ? 'audio/mpeg' : 'image/jpeg' ?>" />
        <?php endif; ?>
    </item>
    <?php endforeach; ?>
</channel>
</rss>