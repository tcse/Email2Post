<?php
// /plugins/tcse/tmh2.0/core/actions/moderate.php

require_once dirname(__DIR__) . '/init.php';

$action = $_GET['action'] ?? '';
$postId = $_GET['id'] ?? '';
$token = $_GET['token'] ?? '';

if (!$action || !$postId || !$token) {
    die('Неверные параметры запроса.');
}

// ✅ Загружаем пути из конфига
$pendingFile = $config['pending_posts_file'] ?? TCSE_DATA . 'pending_posts.json';

if (!file_exists($pendingFile)) {
    die('Нет постов на модерации.');
}

$pending = json_decode(file_get_contents($pendingFile), true);

if (!isset($pending[$postId])) {
    die('Пост не найден или уже обработан.');
}

$post = $pending[$postId];

// Проверка токена
if ($post['moderation_token'] !== $token) {
    die('Неверный токен модерации.');
}

// Проверка срока действия токена
if (($post['moderation_token_expires'] ?? 0) < time()) {
    unset($pending[$postId]);
    file_put_contents($pendingFile, json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    die('Срок действия ссылки истёк (7 дней).');
}

// Обработка действия
if ($action === 'approve') {
    // ✅ Берём путь из конфига
    $postsFile = $config['channel']['posts_email_file'] ?? TCSE_DATA . 'posts_email.json';
    $posts = file_exists($postsFile) ? json_decode(file_get_contents($postsFile), true) : [];
    
    $post['date_published'] = date('Y-m-d H:i:s');
    $post['status'] = 'published';
    unset($post['moderation_token'], $post['moderation_token_expires']);
    
    $posts[$postId] = $post;
    
    // Сортировка по дате
    uasort($posts, fn($a, $b) => strtotime($b['date_published']) - strtotime($a['date_published']));
    
    file_put_contents($postsFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    
    // Удаляем из pending
    unset($pending[$postId]);
    file_put_contents($pendingFile, json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    
    echo "<h1>✅ Пост опубликован</h1>";
    echo "<p>Заголовок: <strong>" . htmlspecialchars($post['title']) . "</strong></p>";
    echo "<p><a href=\"" . BASE_URL . "/post/{$postId}\">Перейти к посту</a></p>";
    
} elseif ($action === 'reject') {
    // Отклонение поста
    unset($pending[$postId]);
    file_put_contents($pendingFile, json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    
    echo "<h1>❌ Пост отклонён</h1>";
    echo "<p>Заголовок: <strong>" . htmlspecialchars($post['title']) . "</strong></p>";
    echo "<p>Пост был удалён и не будет опубликован.</p>";
    
} else {
    die('Неизвестное действие.');
}