<?php
// /plugins/tcse/tmh2.0/core/handlers/EmailHandler.php
/** 
 * =====================================================
 * TMH Email Parser v1.2 (2026-05-28)
 * =====================================================
 * 
 * ОСНОВНЫЕ ВОЗМОЖНОСТИ:
 * ────────────────────────────────────────────────────
 * ✅ Приём писем в форматах Plain-text и HTML
 * ✅ Автоматическое преобразование HTML → Plain-text с сохранением структуры
 * ✅ Извлечение и сохранение вложенных изображений
 * ✅ Автоматическая вставка изображений в тело публикации
 * ✅ Генерация галереи из нескольких изображений
 * 
 * МОДЕРАЦИЯ:
 * ────────────────────────────────────────────────────
 * ✅ Проверка пароля [p:пароль] в теле письма
 * ✅ Автоматическая публикация при правильном пароле
 * ✅ Отправка на модерацию при отсутствии пароля
 * ✅ Email-уведомления администратора со ссылками Approve/Reject
 * ✅ Генерация уникальных токенов для безопасных ссылок
 * ✅ Срок действия ссылок (7 дней)
 * ✅ Сохранение pending-постов в отдельный файл
 * 
 * SHORTCODE ПОДДЕРЖКА:
 * ────────────────────────────────────────────────────
 * ✅ [p:пароль]     - Пароль для автоматической публикации
 * ✅ [format:markdown]  Или   [format markdown] - Установка формата записи (markdown/bbcode/plain)
 * ✅ [status ...]   - Установка статуса (published/pending/draft)
 * ✅ [cut]          - Разделение на анонс и полную версию
 * ✅ [end]          - Игнорирование всего, что после маркера
 * ✅ [tags ...]     - Добавление тегов через shortcode
 * ✅ #хештег        - Автоматическое извлечение хештегов из текста
 * 
 * ТРЕБОВАНИЯ:
 * ────────────────────────────────────────────────────
 * 🔧 PHP 8.0+
 * 🔧 IMAP extension (php-imap)
 * 🔧 Настроенный почтовый ящик для входящих писем
 * 
 * КОНФИГУРАЦИЯ:
 * ────────────────────────────────────────────────────
 * 📁 config.php → секция 'email'
 * 
 *   'email' => [
 *       'enabled' => true,
 *       'imap' => [...],
 *       'publishing' => [
 *           'require_password' => true,
 *           'default_status' => 'pending',
 *       ],
 *       'moderation' => [
 *           'admin_email' => 'admin@example.com',
 *           'secret_link_ttl' => 604800,
 *           'base_url' => 'https://your-site.com/...',
 *       ],
 *       'pending_posts_file' => __DIR__ . '/pending_posts.json',
 *       'posts_email_file' => __DIR__ . '/posts_email.json',
 *   ]
 * 
 * =====================================================
 * @author TCSE
 * @version 1.2
 * @date 2026-05-28
 * =====================================================
 */

namespace TCSE\Handlers;

class EmailHandler {
    private array $config;           // полный конфиг (доступ ко всем секциям)
    private array $emailConfig;      // только секция 'email' для удобства
    private $mailbox = null;
    
    public function __construct(array $config) {
        $this->config = $config;                    // сохраняем полный конфиг
        $this->emailConfig = $config['email'] ?? []; // секция email для быстрого доступа
    }
    
    private function decodeHeader(string $header): string {
        if (strpos($header, '=?') === false) {
            return $header;
        }
        $decoded = '';
        $parts = imap_mime_header_decode($header);
        foreach ($parts as $part) {
            $decoded .= $part->text;
        }
        return trim($decoded);
    }
    
    private function connect(): bool {
        if (!($this->emailConfig['enabled'] ?? false)) {
            return false;
        }
        
        $imapConfig = $this->emailConfig['imap'] ?? [];
        
        $this->mailbox = imap_open(
            $imapConfig['host'],
            $imapConfig['username'],
            $imapConfig['password']
        );
        
        if (!$this->mailbox) {
            $this->log("IMAP connection failed: " . imap_last_error(), 'error');
            return false;
        }
        
        return true;
    }
    
    private function disconnect(): void {
        if ($this->mailbox) {
            imap_close($this->mailbox);
            $this->mailbox = null;
        }
    }
    
    public function checkNewEmails(): array {
        $this->log("Starting email check...", 'info');
        
        if (!$this->connect()) {
            return ['success' => false, 'message' => 'Connection failed'];
        }
        
        $emails = imap_search($this->mailbox, 'UNSEEN');
        
        if (!$emails) {
            $this->disconnect();
            return ['success' => true, 'message' => 'No new emails', 'count' => 0];
        }
        
        sort($emails, SORT_NUMERIC);
        $emails = array_reverse($emails);
        
        $this->log("Found " . count($emails) . " new emails", 'info');
        
        $processed = 0;
        $limit = $this->config['limits']['max_emails_per_run'] ?? 10;
        $results = [];
        
        foreach ($emails as $emailId) {
            if ($processed >= $limit) break;
            
            $result = $this->processEmail($emailId);
            $results[] = $result;
            
            if ($result['success']) {
                $processed++;
                imap_setflag_full($this->mailbox, $emailId, "\\Seen", ST_UID);
            }
        }
        
        $this->disconnect();
        
        return [
            'success' => true,
            'processed' => $processed,
            'results' => $results
        ];
    }
    
    private function isPostImported(string $messageId): bool {
        $emailPostsFile = $this->config['channel']['posts_email_file'] ?? TCSE_DATA . 'posts_email.json';
        
        if (!file_exists($emailPostsFile)) {
            return false;
        }
        
        $posts = json_decode(file_get_contents($emailPostsFile), true);
        if (!is_array($posts)) {
            return false;
        }
        
        foreach ($posts as $post) {
            if (!empty($post['message_id']) && $post['message_id'] === $messageId) {
                return true;
            }
        }
        
        return false;
    }
    
    private function parseEmail(int $emailId): array {
        $structure = imap_fetchstructure($this->mailbox, $emailId);
        
        $result = [
            'text' => '',
            'attachments' => []
        ];
        
        $this->log("Email structure type: " . ($structure->type ?? 'unknown') . ", parts: " . (isset($structure->parts) ? count($structure->parts) : 0), 'debug');
        
        // Если это простое письмо без multipart структуры
        if (!isset($structure->parts) || count($structure->parts) == 0) {
            $body = imap_fetchbody($this->mailbox, $emailId, 1);
            $body = $this->decodeContent($body, $structure->encoding ?? 0);
            $body = $this->decodeCharset($body, $structure->parameters ?? []);
            
            if (($structure->subtype ?? '') == 'HTML') {
                $result['text'] = $this->htmlToPlainText($body);
            } else {
                $result['text'] = $body;
            }
            
            $this->log("Simple email, text length: " . strlen($result['text']), 'debug');
            return $result;
        }
        
        // Для составных писем собираем данные рекурсивно
        $textParts = ['plain' => '', 'html' => ''];
        $this->findTextPart($this->mailbox, $emailId, $structure, $result, '', $textParts);
        
        // Приоритет отдаем Plain Text, если пустой — берем HTML
        if (!empty(trim($textParts['plain']))) {
            $result['text'] = $textParts['plain'];
        } elseif (!empty(trim($textParts['html']))) {
            $result['text'] = $this->htmlToPlainText($textParts['html']);
            $this->log("Using HTML part converted to plain text", 'debug');
        }
        
        $this->log("Final text length: " . strlen($result['text']) . ", attachments: " . count($result['attachments']), 'debug');
        
        return $result;
    }
    
    private function findTextPart($mailbox, int $emailId, $structure, array &$result, string $partNumber = '', array &$textParts = []): void {
        if (isset($structure->parts) && is_array($structure->parts)) {
            foreach ($structure->parts as $index => $part) {
                $currentPartNum = empty($partNumber) ? ($index + 1) : $partNumber . '.' . ($index + 1);
                
                // Переходим на уровень глубже, если есть вложенные sub-parts
                if (isset($part->parts) && is_array($part->parts) && count($part->parts) > 0) {
                    $this->findTextPart($mailbox, $emailId, $part, $result, $currentPartNum, $textParts);
                } else {
                    // Обрабатываем единичную часть
                    $subtype = strtolower($part->subtype ?? '');
                    $mimeType = $part->type ?? 0;
                    
                    $filename = $this->getAttachmentFilename($part);
                    $isAttachment = !empty($filename) || (isset($part->disposition) && in_array(strtolower($part->disposition), ['attachment', 'inline']));
                    
                    // Если это вложение (картинка, файл) и тип НЕ текстовый (или это явно attachment)
                    if ($isAttachment && $mimeType !== 0) {
                        $this->processAttachment($mailbox, $emailId, $currentPartNum, $part, $filename, $result);
                    } // Если это текст (Plain или HTML) и не является файлом-вложением
                    elseif ($mimeType == 0 && !$isAttachment) {
                        $body = imap_fetchbody($mailbox, $emailId, $currentPartNum);
                        $body = $this->decodeContent($body, $part->encoding ?? 0);
                        $body = $this->decodeCharset($body, $part->parameters ?? []);
                        
                        if ($subtype == 'plain') {
                            $textParts['plain'] .= $body;
                            $this->log("Collected plain text part: {$currentPartNum}", 'debug');
                        } elseif ($subtype == 'html') {
                            $textParts['html'] .= $body;
                            $this->log("Collected HTML part: {$currentPartNum}", 'debug');
                        }
                    }
                }
            }
        }
    }

    private function processAttachment($mailbox, int $emailId, string $partNumber, $part, string $filename, array &$result): void {
        if (empty($filename)) {
            $filename = 'attachment_' . $partNumber;
        }
        
        $body = imap_fetchbody($mailbox, $emailId, $partNumber);
        $body = $this->decodeContent($body, $part->encoding ?? 0);
        
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = $this->config['limits']['allowed_attachments'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($extension, $allowed)) {
            $uploadDir = TCSE_DATA . 'uploads/email_attachments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $safeName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $filepath = $uploadDir . $safeName;
            file_put_contents($filepath, $body);
            
            $result['attachments'][] = [
                'filename' => $filename,
                'path' => '/data/uploads/email_attachments/' . $safeName,
                'type' => $extension,
                'size' => strlen($body)
            ];
            
            $this->log("Saved attachment: $filename from part $partNumber", 'debug');
        }
    }
    
    private function getAttachmentFilename($part): string {
        $filename = '';
        
        if (!empty($part->dparameters) && is_array($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtolower($param->attribute) == 'filename') {
                    $filename = $this->decodeHeader($param->value);
                    break;
                }
                if (strtolower($param->attribute) == 'filename*') {
                    $filename = $this->decodeRfc2231($param->value);
                    break;
                }
            }
        }
        
        if (empty($filename) && !empty($part->parameters) && is_array($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute) == 'name') {
                    $filename = $this->decodeHeader($param->value);
                    break;
                }
                if (strtolower($param->attribute) == 'name*') {
                    $filename = $this->decodeRfc2231($param->value);
                    break;
                }
            }
        }
        
        return trim($filename);
    }
    
    private function decodeRfc2231(string $value): string {
        if (preg_match("/^([^']*)'([^']*)'(.*)$/", $value, $matches)) {
            $charset = $matches[1];
            $lang = $matches[2];
            $encoded = $matches[3];
            $decoded = rawurldecode($encoded);
            if (!empty($charset) && strtoupper($charset) != 'UTF-8') {
                $converted = iconv($charset, 'UTF-8//IGNORE', $decoded);
                if ($converted !== false) {
                    return $converted;
                }
            }
            return $decoded;
        }
        return $value;
    }
    
    private function decodeContent(string $body, int $encoding): string {
        switch ($encoding) {
            case 3:
                return base64_decode($body);
            case 4:
                return quoted_printable_decode($body);
            default:
                return $body;
            }
    }
    
    private function decodeCharset(string $text, array $parameters): string {
        $charset = 'UTF-8';
        foreach ($parameters as $param) {
            if (strtolower($param->attribute) == 'charset') {
                $charset = strtoupper($param->value);
                break;
            }
        }
        
        if ($charset == 'UTF-8' || $charset == 'UTF8') {
            return $text;
        }
        
        $converted = iconv($charset, 'UTF-8//IGNORE', $text);
        return $converted !== false ? $converted : $text;
    }
    
    private function htmlToPlainText(string $html): string {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "\n\n", $html);
        $html = preg_replace('/<\/div>/i', "\n", $html);
        $html = preg_replace('/<\/h[1-6]>/i', "\n", $html);
        $html = preg_replace('/<\/li>/i', "\n", $html);
        
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\r\n|\r/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        
        return trim($text);
    }
    
    private function extractShortcode(string $text, string $tag): ?string {
        if (preg_match('/\[' . preg_quote($tag) . '[:\\s]+([^\]]+)\]/i', $text, $matches)) {
            return trim(trim($matches[1], '"\''));
        }
        return null;
    }

    /**
     * Извлечение формата из шорткода [format:markdown] или [format markdown]
     */
    private function extractFormatShortcode(string $text): ?string {
        if (preg_match('/\[format[:\\s]+(markdown|bbcode|plain|auto)\]/i', $text, $matches)) {
            return strtolower(trim($matches[1]));
        }
        return null;
    }
    // .Извлечение формата из шорткода [format:markdown] или [format markdown]
    
    private function removeShortcodes(string $text): string {
        $text = preg_replace('/\[[a-z_]+[:\\s]+[^\]]+\]/i', '', $text);
        $text = preg_replace('/\[(cut|end)\]/i', '', $text);
        return trim($text);
    }
    
    // 📝 Исправленная processEmail с модерацией (только email)
    private function processEmail(int $emailId): array {
        $overview = imap_fetch_overview($this->mailbox, $emailId, 0)[0];
        
        $sender = $overview->from;
        $subject = $this->decodeHeader($overview->subject ?? 'Без темы');
        $messageId = $overview->message_id ?? '';
        $date = $overview->date ?? date('Y-m-d H:i:s');
        
        $this->log("Processing email: $subject", 'info');
        
        if ($this->isPostImported($messageId)) {
            $this->log("Duplicate email, skipping", 'warning');
            imap_setflag_full($this->mailbox, $emailId, "\\Seen", ST_UID);
            return ['success' => false, 'message' => 'Duplicate'];
        }
        
        if (!$this->isSenderAllowed($sender)) {
            $this->log("Sender not allowed: $sender", 'warning');
            imap_setflag_full($this->mailbox, $emailId, "\\Seen", ST_UID);
            return ['success' => false, 'message' => 'Sender not allowed'];
        }
        
        $parsed = $this->parseEmail($emailId);
        $rawText = $parsed['text'];
        $attachments = $parsed['attachments'];
        
        $this->log("Extracted text length: " . strlen($rawText), 'debug');
        $this->log("Found " . count($attachments) . " attachments", 'debug');
        
        if (empty(trim($rawText))) {
            $this->log("Empty content, skipping", 'warning');
            imap_setflag_full($this->mailbox, $emailId, "\\Seen", ST_UID);
            return ['success' => false, 'message' => 'Empty content'];
        }
        
        $endPos = stripos($rawText, '[end]');
        if ($endPos !== false) {
            $rawText = trim(substr($rawText, 0, $endPos));
        }

        // ============================================
        // === ИЗВЛЕКАЕМ ФОРМАТ ИЗ ШОРТКОДА ===
        // ============================================
        $forcedFormat = $this->extractFormatShortcode($rawText);
        if ($forcedFormat) {
            // Удаляем шорткод формата из текста
            $rawText = preg_replace('/\[format[:\\s]+(markdown|bbcode|plain|auto)\]/i', '', $rawText);
            $this->log("Format forced to: $forcedFormat", 'info');
        }
        
        // ============================================
        // === ЛОГИКА МОДЕРАЦИИ ===
        // ============================================
        $requirePassword = $this->config['publishing']['require_password'] ?? false;
        $author = null;
        $status = null;
        $moderationToken = null;
        
        if ($requirePassword) {
            $password = $this->extractShortcode($rawText, 'p');
            $author = $this->validatePassword($password);
            
            if (!$author) {
                // Нет пароля — отправляем на модерацию
                $this->log("No valid password, sending to moderation", 'info');
                $status = 'pending';
                $author = 'Неизвестный автор';
                $moderationToken = bin2hex(random_bytes(32));
            } else {
                $status = $this->extractShortcode($rawText, 'status') ?? $this->config['publishing']['default_status'] ?? 'pending';
            }
        } else {
            // Пароль не требуется
            if (preg_match('/^(.*?)\s*</', $sender, $matches)) {
                $author = trim($matches[1]);
            } else {
                $author = $sender;
            }
            $status = $this->extractShortcode($rawText, 'status') ?? $this->config['publishing']['default_status'] ?? 'published';
        }
        
        $tagsFromShortcode = $this->extractShortcode($rawText, 'tags');
        $cleanText = $this->removeShortcodes($rawText);
        $hashtags = $this->extractHashtags($cleanText);
        $allTags = array_unique(array_merge($hashtags, $this->parseTagsFromString($tagsFromShortcode)));
        
        [$excerpt, $fullContent] = $this->splitByCut($cleanText);
        
        $mainImage = null;
        if (!empty($attachments)) {
            $mainImage = $attachments[0]['path'];
        }
        
        $postId = 'email_' . time() . '_' . bin2hex(random_bytes(4));
        $parsedDate = date('Y-m-d H:i:s', strtotime($date));
        
        $post = [
            'id' => $postId,
            'message_id' => $messageId,
            'date' => $parsedDate,
            'title' => $subject,
            'text' => $fullContent ?: $excerpt,
            'caption' => $excerpt,
            'source' => 'email',
            'author' => $author,
            'author_email' => $sender,
            'tags' => $allTags,
            'photo_file_id' => $mainImage,
            'attachments' => $attachments,
            'audio' => null,
            'format' => $forcedFormat,
            'status' => $status,
            'moderation_token' => $moderationToken,
            'moderation_token_expires' => ($moderationToken ? time() + ($this->config['moderation']['secret_link_ttl'] ?? 604800) : null)
        ];
        
        // Сохраняем в зависимости от статуса
        if ($status === 'published') {
            $this->saveToBlog($post);
        } else {
            $this->savePendingPost($post);
        }
        
        // Отправляем уведомление на email (только для pending)
        if ($status === 'pending' && $moderationToken) {
            $this->sendModerationEmail($post);
        }
        
        $this->log("Saved: $subject (ID: $postId, status: $status)", 'success');
        
        return [
            'success' => true,
            'post_id' => $postId,
            'status' => $status,
            'title' => $subject
        ];
    }

    /**
     * Сохранение поста на модерацию
     */
    private function savePendingPost(array $post): bool {
        $pendingFile = $this->config['pending_posts_file'] ?? TCSE_DATA . 'pending_posts.json';
        
        $pending = [];
        if (file_exists($pendingFile)) {
            $pending = json_decode(file_get_contents($pendingFile), true);
        }
        
        $pending[$post['id']] = $post;
        
        $result = file_put_contents($pendingFile, json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        $this->log("Post saved to pending: {$post['title']}", 'info');
        
        return $result !== false;
    }

    /**
     * Отправка уведомления на email для модерации
     */
    private function sendModerationEmail(array $post): void {
        $adminEmail = $this->config['moderation']['admin_email'] ?? null;
        if (!$adminEmail) {
            $this->log("No admin email configured", 'warning');
            return;
        }
        
        $baseUrl = $this->config['moderation']['base_url'] ?? BASE_URL;
        $approveUrl = $baseUrl . '/core/actions/moderate.php?action=approve&id=' . $post['id'] . '&token=' . $post['moderation_token'];
        $rejectUrl = $baseUrl . '/core/actions/moderate.php?action=reject&id=' . $post['id'] . '&token=' . $post['moderation_token'];
        
        $subject = "📧 Новый пост на модерации: {$post['title']}";
        $message = "Новый пост ожидает модерации:\n\n";
        $message .= "📝 Тема: {$post['title']}\n";
        $message .= "👤 Автор: {$post['author']}\n";
        $message .= "📅 Получено: {$post['date']}\n\n";
        $message .= "--- Анонс ---\n" . mb_substr($post['caption'], 0, 300) . "\n\n";
        $message .= "✅ Одобрить: $approveUrl\n";
        $message .= "❌ Отклонить: $rejectUrl\n\n";
        $message .= "Ссылки действительны в течение 7 дней.";
        
        $headers = "From: noreply@" . parse_url(BASE_URL, PHP_URL_HOST) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
        
        $result = mail($adminEmail, $subject, $message, $headers);
        
        if ($result) {
            $this->log("Moderation email sent to $adminEmail", 'info');
        } else {
            $this->log("FAILED to send moderation email to $adminEmail", 'error');
        }
    }
    // .Исправленная processEmail с модерацией (только email)

    
    private function validatePassword(?string $password): ?string {
        if (!$password) return null;
        
        $passwords = $this->config['publish_passwords'] ?? [];
        foreach ($passwords as $validPassword => $authorName) {
            if (trim($password) === $validPassword) {
                return $authorName;
            }
        }
        return null;
    }
    
    private function extractHashtags(string $text): array {
        preg_match_all('/#([^\s#!@$%^&*()\[\]{}<>~`=+\\\|;:\'",.?\/]+)/u', $text, $matches);
        return array_unique($matches[1] ?? []);
    }
    
    private function parseTagsFromString(?string $tagsString): array {
        if (!$tagsString) return [];
        $tags = array_map('trim', explode(',', $tagsString));
        return array_filter($tags);
    }
    
    /**
     * Разделение текста по [cut]
     * Возвращает: [0 => краткий текст (caption), 1 => полный текст]
     */
    private function splitByCut(string $text): array {
        // Ищем [cut] (регистронезависимо)
        if (preg_match('/(.*?)\[cut\](.*)$/is', $text, $matches)) {
            $caption = trim($matches[1]);      // Всё что ДО [cut]
            $fullText = trim($matches[2]);      // Всё что ПОСЛЕ [cut]
            
            // Если после [cut] ничего нет, используем caption как полный текст
            if (empty($fullText)) {
                $fullText = $caption;
            }
            
            return [$caption, $fullText];
        }
        
        // Если [cut] нет: caption = первые 200 символов, fullText = весь текст
        $excerpt = mb_substr($text, 0, 200);
        if (mb_strlen($text) > 200) {
            $excerpt .= '...';
        }
        
        return [$excerpt, $text];
    }
    
    private function isSenderAllowed(string $sender): bool {
        $allowed = $this->config['security']['allowed_senders'] ?? [];
        if (empty($allowed)) return true;
        
        foreach ($allowed as $email) {
            if (stripos($sender, $email) !== false) return true;
        }
        return false;
    }
    
    private function saveToBlog(array $post): bool {
        $postsFile = $this->config['channel']['posts_email_file'] ?? TCSE_DATA . 'posts_email.json';
        
        $posts = [];
        if (file_exists($postsFile)) {
            $posts = json_decode(file_get_contents($postsFile), true);
        }
        
        // Определяем главное изображение
        $mainImage = null;
        $allImages = [];
        
        foreach ($post['attachments'] as $attachment) {
            $ext = strtolower($attachment['type']);
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                if (!$mainImage) {
                    $mainImage = $attachment['path'];
                }
                $allImages[] = $attachment['path'];
            }
        }
        
        $posts[$post['id']] = [
            'id' => $post['id'],
            'message_id' => $post['message_id'],
            'date' => $post['date'],
            'title' => $post['title'],
            'text' => $post['text'],           // полный текст (после [cut] или весь)
            'caption' => $post['caption'],     // краткий текст (до [cut] или первые 200 символов)
            'source' => 'email',
            'author' => $post['author'],
            'author_email' => $post['author_email'],
            'tags' => $post['tags'],
            'photo_file_id' => $mainImage,      // путь к главному изображению
            'images' => $allImages,              // все изображения
            'attachments' => $post['attachments'],
            'audio' => null,
            'format' => $post['format'] ?? null
        ];
        
        uasort($posts, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return file_put_contents($postsFile, json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    }
    
    private function log(string $message, string $level = 'info'): void {
        $logFile = TCSE_DATA . 'logs/email_parser.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] [$level] $message\n", FILE_APPEND | LOCK_EX);
    }
}