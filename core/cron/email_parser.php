<?php
// /plugins/tcse/tmh2.0/core/cron/email_parser.php

// Загрузка ядра
require_once dirname(__DIR__) . '/init.php';
require_once TCSE_CORE . 'handlers/EmailHandler.php';

use TCSE\Handlers\EmailHandler;

// Защита от прямого запуска (только CLI или с секретным ключом)
$secretKey = $config['secret_key'] ?? '';
$accessKey = $_GET['key'] ?? '';
$isCli = php_sapi_name() === 'cli';

if (!$isCli && $accessKey !== $secretKey) {
    http_response_code(403);
    die('Access denied');
}

// Запуск парсера
$handler = new EmailHandler($config);
$result = $handler->checkNewEmails();

// Вывод результата
if ($isCli) {
    echo date('Y-m-d H:i:s') . " - " . ($result['message'] ?? 'Done') . "\n";
    if (isset($result['processed'])) {
        echo "Processed: " . $result['processed'] . " emails\n";
    }
} else {
    header('Content-Type: application/json');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}