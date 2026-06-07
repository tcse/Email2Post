<?php
// /plugins/tcse/tmh2.0/core/TagParser.php

namespace TCSE;

class TagParser {
    private array $data;
    private array $tags = [];
    private array $blocks = [];
    
    public function __construct(array $data = []) {
        $this->data = $data;
        $this->registerDefaultTags();
    }
    
    /**
     * Регистрация простого тега {tag}
     */
    public function registerTag(string $name, callable $handler): void {
        $this->tags[$name] = $handler;
    }
    
    /**
     * Регистрация блочного тега [tag]...[/tag]
     */
    public function registerBlock(string $name, callable $handler): void {
        $this->blocks[$name] = $handler;
    }
    
    /**
     * Парсинг строки с тегами
     */
    public function parse(string $template): string {
        // 1. Обработка блочных тегов [tag]...[/tag]
        foreach ($this->blocks as $name => $handler) {
            $template = preg_replace_callback(
                '/\[' . preg_quote($name) . '(?:\s+([^\]]+))?\](.*?)\[\/' . preg_quote($name) . '\]/is',
                function($matches) use ($handler) {
                    $params = $this->parseParams($matches[1] ?? '');
                    $content = $matches[2];
                    return $handler($content, $params);
                },
                $template
            );
        }
        
        // 2. Обработка простых тегов {tag}
        foreach ($this->tags as $name => $handler) {
            $template = preg_replace_callback(
                '/\{' . preg_quote($name) . '(?:\s+([^\}]+))?\}/',
                function($matches) use ($handler) {
                    $params = $this->parseParams($matches[1] ?? '');
                    return $handler($params);
                },
                $template
            );
        }
        
        return $template;
    }
    
    /**
     * Парсинг параметров limit="200" index="2"
     */
    private function parseParams(string $paramString): array {
        $params = [];
        if (preg_match_all('/(\w+)="([^"]+)"/', $paramString, $matches)) {
            foreach ($matches[1] as $i => $key) {
                $params[$key] = $matches[2][$i];
            }
        }
        return $params;
    }
    
    /**
     * Экранирование HTML
     */
    private function escape(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Обрезка строки
     */
    private function truncate(string $text, int $limit): string {
        if (mb_strlen($text) <= $limit) return $text;
        return mb_substr($text, 0, $limit) . '...';
    }
    
    /**
     * Регистрация всех стандартных тегов системы
     */
    private function registerDefaultTags(): void {
        // ========================================
        // ГЛОБАЛЬНЫЕ ТЕГИ (доступны везде)
        // ========================================
        $this->registerTag('base_url', fn() => BASE_URL);
        $this->registerTag('current_year', fn() => date('Y'));
        $this->registerTag('site_title', fn() => $this->data['site_title'] ?? 'TMH by TCSE');
        $this->registerTag('site_description', fn() => $this->data['site_description'] ?? '');
        $this->registerTag('channel_username', fn() => $this->data['channel_username'] ?? 'tcsecms');
        
        // ========================================
        // ТЕГИ ДЛЯ ГЛАВНОЙ СТРАНИЦЫ (blog_list)
        // ========================================
        $this->registerTag('blog_title', fn() => $this->data['blog_title'] ?? 'Блог');
        $this->registerTag('blog_description', fn() => $this->data['blog_description'] ?? '');
        
        // ========================================
        // ТЕГИ ДЛЯ КАРТОЧКИ ПОСТА (blog_list_card)
        // ========================================
        $this->registerTag('title', function() {
            return $this->escape($this->data['title'] ?? 'Без названия');
        });
        
        $this->registerTag('full_link', function() {
            return $this->data['full_link'] ?? '#';
        });
        
        $this->registerTag('image_1', function() {
            return $this->data['image_1'] ?? 'https://placehold.co/400x400/1a1a1a/e8e8e8?text=No+Image';
        });
        
        $this->registerTag('date', function() {
            return $this->data['date'] ?? '';
        });
        
        $this->registerTag('date_iso', function() {
            return $this->data['date_iso'] ?? '';
        });
        
        $this->registerTag('short_story', function($params = []) {
            $limit = (int)($params['limit'] ?? 150);
            $text = strip_tags($this->data['short_story'] ?? $this->data['excerpt'] ?? '');
            return $this->truncate($text, $limit);
        });
        
        $this->registerTag('excerpt', function($params = []) {
            $limit = (int)($params['limit'] ?? 200);
            $text = strip_tags($this->data['excerpt'] ?? $this->data['short_story'] ?? '');
            return $this->truncate($text, $limit);
        });
        
        $this->registerTag('post_id', function() {
            return $this->data['post_id'] ?? $this->data['id'] ?? '';
        });
        
        // ========================================
        // ТЕГИ ДЛЯ СТРАНИЦЫ ПОСТА (blog_post)
        // ========================================
        $this->registerTag('author', function() {
            return $this->escape($this->data['author'] ?? 'Неизвестный');
        });
        
        $this->registerTag('content', function() {
            return $this->data['content'] ?? '';
        });
        
        $this->registerTag('text', function() {
            return $this->data['text'] ?? $this->data['content'] ?? '';
        });
        
        $this->registerTag('caption', function() {
            return $this->data['caption'] ?? '';
        });
        
        // ========================================
		// БЛОЧНЫЕ ТЕГИ ДЛЯ ИЗОБРАЖЕНИЙ (единая логика)
		// ========================================

		// Галерея (есть больше 1 фото)
		$this->registerBlock('gallery', function($content, $params) {
		    $images = $this->data['images'] ?? [];
		    $hasGallery = count($images) > 1;
		    
		    if (!$hasGallery) return '';
		    
		    // Генерируем слайды для галереи
		    $slides = '';
		    foreach ($images as $img) {
		        $slides .= '<div class="swiper-slide"><img src="' . $img . '" alt="Фото"></div>';
		    }
		    
		    // Заменяем {gallery_items} на сгенерированные слайды
		    return str_replace('{gallery_items}', $slides, $content);
		});

		// Одиночное изображение (ровно 1 фото)
		$this->registerBlock('single_image', function($content, $params) {
		    $images = $this->data['images'] ?? [];
		    $hasSingleImage = count($images) === 1;
		    
		    if (!$hasSingleImage) return '';
		    return $content;
		});

		// Нет изображений
		$this->registerBlock('no_images', function($content, $params) {
		    $images = $this->data['images'] ?? [];
		    $hasNoImages = count($images) === 0;
		    
		    if (!$hasNoImages) return '';
		    return $content;
		});

		// Есть аудио
		$this->registerBlock('has_audio', function($content, $params) {
		    $hasAudio = $this->data['has_audio'] ?? false;
		    if (!$hasAudio) return '';
		    return $content;
		});

		// Есть теги
		$this->registerBlock('has_tags', function($content, $params) {
		    $hasTags = !empty($this->data['tags']);
		    if (!$hasTags) return '';
		    return $content;
		});

		// ========================================
		// ТЕГИ ДЛЯ ПРОВЕРКИ КОНКРЕТНЫХ ИЗОБРАЖЕНИЙ (как в DLE)
		// ========================================
		for ($i = 1; $i <= 20; $i++) {
		    $num = $i;
		    $this->registerBlock("image-{$num}", function($content, $params) use ($num) {
		        $images = $this->data['images'] ?? [];
		        if (isset($images[$num - 1])) {
		            return $content;
		        }
		        return '';
		    });
		    
		    $this->registerBlock("not-image-{$num}", function($content, $params) use ($num) {
		        $images = $this->data['images'] ?? [];
		        if (!isset($images[$num - 1])) {
		            return $content;
		        }
		        return '';
		    });
		}

		// ========================================
		// ТЕГ IF/ELSE (с поддержкой else)
		// ========================================
		$this->registerBlock('if', function($content, $params) {
		    $condition = $params['condition'] ?? '';
		    $value = $this->data[$condition] ?? false;
		    
		    // Поддержка [else] внутри
		    if (strpos($content, '[else]') !== false) {
		        $parts = explode('[else]', $content, 2);
		        $ifTrue = $parts[0];
		        $ifFalse = $parts[1] ?? '';
		        
		        if ($value) {
		            return $ifTrue;
		        } else {
		            return $ifFalse;
		        }
		    }
		    
		    return $value ? $content : '';
		});
        
        // ========================================
        // ТЕГИ ДЛЯ АУДИО
        // ========================================
        $this->registerTag('audio_url', function() {
            return $this->data['audio_url'] ?? '';
        });
        
        $this->registerTag('has_audio', function() {
            return !empty($this->data['audio_url']) ? 'true' : 'false';
        });
        
        $this->registerTag('audio_title', function() {
            return $this->escape($this->data['audio_title'] ?? 'Аудиозапись');
        });
        
        $this->registerTag('audio_performer', function() {
            return $this->escape($this->data['audio_performer'] ?? '');
        });
        
        // ========================================
        // ТЕГИ ДЛЯ ТЕГОВ (taxonomy)
        // ========================================
        $this->registerTag('tags', function() {
            $tags = $this->data['tags'] ?? [];
            if (empty($tags)) return '';
            $html = '<div class="post-tags">';
            foreach ($tags as $tag) {
                $html .= '<a href="' . BASE_URL . '/tag/' . urlencode($tag) . '" class="post-tag">#' . $this->escape($tag) . '</a> ';
            }
            $html .= '</div>';
            return $html;
        });
        
        $this->registerTag('tag_list', function() {
            $tags = $this->data['tags'] ?? [];
            if (empty($tags)) return '';
            $html = '';
            foreach ($tags as $tag) {
                $html .= '<a href="' . BASE_URL . '/tag/' . urlencode($tag) . '" class="post-tag">#' . $this->escape($tag) . '</a> ';
            }
            return $html;
        });
        
        // ========================================
        // ТЕГИ ДЛЯ ГАЛЕРЕИ (блочный тег)
        // ========================================
        $this->registerBlock('gallery', function($content, $params) {
            $images = $this->data['images'] ?? [];
            if (count($images) <= 1) return '';
            
            $html = '<div class="swiper"><div class="swiper-wrapper">';
            foreach ($images as $img) {
                $src = (strpos($img, '/data/') === 0) ? BASE_URL . $img : BASE_URL . '/core/blog_cover.php?file_id=' . urlencode($img);
                $html .= '<div class="swiper-slide"><img src="' . $src . '" alt="Фото"></div>';
            }
            $html .= '</div><div class="swiper-pagination"></div>';
            $html .= '<div class="swiper-button-next"></div><div class="swiper-button-prev"></div></div>';
            return $html;
        });
        
        // ========================================
        // ТЕГИ ДЛЯ АУДИО (блочный тег)
        // ========================================
        $this->registerBlock('audio', function($content, $params) {
            $audioUrl = $this->data['audio_url'] ?? '';
            if (empty($audioUrl)) return '';
            
            $title = $this->data['audio_title'] ?? 'Аудиозапись';
            $performer = $this->data['audio_performer'] ?? '';
            
            $html = '<div class="mb-4">';
            $html .= '<h4>🎧 ' . $this->escape($title) . '</h4>';
            if ($performer) {
                $html .= '<p><strong>' . $this->escape($performer) . '</strong></p>';
            }
            $html .= '<audio controls style="width:100%;" preload="metadata">';
            $html .= '<source src="' . $audioUrl . '" type="audio/mpeg">';
            $html .= 'Ваш браузер не поддерживает аудио.';
            $html .= '</audio></div>';
            return $html;
        });
        
        // ========================================
        // ТЕГИ ДЛЯ ПАГИНАЦИИ
        // ========================================
        $this->registerTag('pagination', function() {
            return $this->data['pagination'] ?? '';
        });
        
        $this->registerTag('current_page', function() {
            return $this->data['current_page'] ?? 1;
        });
        
        $this->registerTag('total_pages', function() {
            return $this->data['total_pages'] ?? 1;
        });
        
        // ========================================
        // УСЛОВНЫЕ ТЕГИ (if/else)
        // ========================================
        $this->registerBlock('if', function($content, $params) {
            $condition = $params['condition'] ?? '';
            $value = $this->data[$condition] ?? false;
            
            if ($value) {
                return $content;
            }
            return '';
        });
        
        $this->registerBlock('if_not', function($content, $params) {
            $condition = $params['condition'] ?? '';
            $value = $this->data[$condition] ?? false;
            
            if (!$value) {
                return $content;
            }
            return '';
        });
    }
}