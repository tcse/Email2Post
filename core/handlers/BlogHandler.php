<?php
// /plugins/tcse/email2post/core/handlers/BlogHandler.php

namespace TCSE\Handlers;

class BlogHandler {
    private array $config;
    private array $posts = [];
    private string $templatePath;
    
    public function __construct(array $config) {
        $this->config = $config;
        $this->templatePath = TEMPLATE_PATH;
        $this->loadPosts();
    }
    
    private function loadPosts(): void {
        $postsFile = POSTS_EMAIL_FILE;
        
        if (!file_exists($postsFile)) {
            return;
        }
        
        $data = json_decode(file_get_contents($postsFile), true);
        $this->posts = is_array($data) ? $data : [];
        
        // Сортировка по дате
        usort($this->posts, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
    }
    
    public function renderList(): void {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = $this->config['blog']['posts_per_page'] ?? 12;
        $offset = ($page - 1) * $perPage;
        
        $posts = array_slice($this->posts, $offset, $perPage);
        $totalPages = (int)ceil(count($this->posts) / $perPage);
        $currentPage = $page;
        $pageType = 'list';
        $title = $this->config['site']['title'] ?? 'Блог';
        $description = $this->config['site']['description'] ?? '';
        
        $this->renderTemplate('base_01_head', compact('title', 'description', 'pageType', 'config'));
        $this->renderTemplate('base_02_header', compact('config'));
        $this->renderTemplate('base_03_main', compact('pageType', 'posts', 'currentPage', 'totalPages', 'config'));
        $this->renderTemplate('base_04_footer', compact('config'));
        $this->renderTemplate('base_05_bottom', compact('config'));
    }
    
    public function renderPost(string $postId): void {
        $post = $this->findPostById($postId);
        
        if (!$post) {
            http_response_code(404);
            echo '<h1>Пост не найден</h1>';
            return;
        }
        
        $pageType = 'post';
        $title = $post['title'] ?? 'Без названия';
        $description = mb_substr(strip_tags($post['text'] ?? ''), 0, 150);
        
        $this->renderTemplate('base_01_head', compact('title', 'description', 'pageType', 'post', 'config'));
        $this->renderTemplate('base_02_header', compact('config'));
        $this->renderTemplate('base_03_main', compact('pageType', 'post', 'config'));
        $this->renderTemplate('base_04_footer', compact('config'));
        $this->renderTemplate('base_05_bottom', compact('config'));
    }
    
    public function renderByTag(string $tag): void {
        $filtered = array_filter($this->posts, function($post) use ($tag) {
            $text = ($post['title'] ?? '') . ' ' . ($post['text'] ?? '') . ' ' . ($post['caption'] ?? '');
            if (!empty($post['tags']) && is_array($post['tags'])) {
                $text .= ' ' . implode(' ', $post['tags']);
            }
            return stripos($text, '#' . $tag) !== false;
        });
        
        $this->posts = array_values($filtered);
        $this->renderList();
    }
    
    private function findPostById(string $id): ?array {
        foreach ($this->posts as $post) {
            if ((string)$post['id'] === $id) {
                return $post;
            }
        }
        return null;
    }
    
    private function renderTemplate(string $name, array $variables = []): void {
        $file = $this->templatePath . '/' . $name . '.php';
        if (file_exists($file)) {
            extract($variables);
            include $file;
        }
    }
}