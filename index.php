<?php
// /plugins/tcse/email2post/index.php

require_once __DIR__ . '/core/init.php';

$path = $_GET['p'] ?? '';

// Роутинг
if ($path === '' || $path === 'blog') {
    require_once TCSE_CORE . 'handlers/BlogHandler.php';
    $handler = new TCSE\Handlers\BlogHandler($config);
    $handler->renderList();
    
} elseif (preg_match('#^post/([a-zA-Z0-9_]+)$#', $path, $matches)) {
    require_once TCSE_CORE . 'handlers/BlogHandler.php';
    $handler = new TCSE\Handlers\BlogHandler($config);
    $handler->renderPost($matches[1]);
    
} elseif (preg_match('#^page/(\d+)$#', $path, $matches)) {
    $_GET['page'] = $matches[1];
    require_once TCSE_CORE . 'handlers/BlogHandler.php';
    $handler = new TCSE\Handlers\BlogHandler($config);
    $handler->renderList();
    
} elseif (preg_match('#^tag/([^/]+)$#', $path, $matches)) {
    require_once TCSE_CORE . 'handlers/BlogHandler.php';
    $handler = new TCSE\Handlers\BlogHandler($config);
    $handler->renderByTag($matches[1]);
    
} else {
    http_response_code(404);
    echo '<h1>404 - Страница не найдена</h1>';
}