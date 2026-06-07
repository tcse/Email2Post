<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <base href="<?= BASE_URL ?>/">
    
    <title><?= htmlspecialchars($title ?? 'Блог') ?> | Minimal Blog</title>
    <meta name="description" content="<?= htmlspecialchars($description ?? 'Минималистичный блог из Telegram') ?>">
    <meta name="theme-color" content="#1a1a1a">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($title ?? 'Блог') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description ?? '') ?>">
    <meta property="og:type" content="<?= $pageType === 'post' ? 'article' : 'website' ?>">
    <meta property="og:url" content="<?= BASE_URL . ($_SERVER['REQUEST_URI'] ?? '') ?>">
    <?php if (!empty($post['image']) || !empty($post['photo_file_id'])): ?>
    <meta property="og:image" content="<?= BASE_URL . (!empty($post['image']) ? $post['image'] : '/core/blog_cover.php?file_id=' . urlencode($post['photo_file_id'])) ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/favicons/favicon.svg">
    <link rel="alternate icon" href="<?= BASE_URL ?>/assets/favicons/favicon.ico">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/favicons/apple-touch-icon.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Цветовая схема (тёмная тема по умолчанию) */
            --bg-primary: #0a0a0a;
            --bg-secondary: #111111;
            --bg-card: #1a1a1a;
            --text-primary: #e8e8e8;
            --text-secondary: #a0a0a0;
            --text-muted: #6c6c6c;
            --border: #2a2a2a;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --accent-soft: rgba(59, 130, 246, 0.1);
            --danger: #ef4444;
            --success: #22c55e;
            --warning: #f59e0b;
            
            /* Размеры */
            --container-width: 720px;
            --container-padding: 1rem;
            --header-height: 64px;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            
            /* Анимации */
            --transition: all 0.2s ease;
        }
        
        /* Светлая тема */
        [data-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --accent-soft: rgba(59, 130, 246, 0.08);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            font-size: 16px;
            transition: background 0.3s ease, color 0.3s ease;
        }
        
        /* Контейнеры */
        .container {
            max-width: var(--container-width);
            margin: 0 auto;
            padding: 0 var(--container-padding);
        }
        
        /* Типографика */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            line-height: 1.3;
            letter-spacing: -0.02em;
            color: var(--text-primary);
        }
        
        h1 { font-size: 2.2rem; margin-bottom: 1rem; }
        h2 { font-size: 1.8rem; margin-bottom: 0.75rem; }
        h3 { font-size: 1.4rem; margin-bottom: 0.5rem; }
        
        a {
            color: var(--accent);
            text-decoration: none;
            transition: var(--transition);
        }
        
        a:hover {
            color: var(--accent-hover);
        }
        
        /* Лента постов (список) */
        .posts-feed {
            display: flex;
            flex-direction: column;
            gap: 3rem;
        }
        
        .post-item {
            border-bottom: 1px solid var(--border);
            padding-bottom: 2rem;
        }
        
        .post-item:last-child {
            border-bottom: none;
        }
        
        .post-title {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .post-title a {
            color: var(--text-primary);
        }
        
        .post-title a:hover {
            color: var(--accent);
        }
        
        .post-meta {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .post-excerpt {
            color: var(--text-secondary);
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .post-read-more {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Отдельный пост */
        .post-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .post-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .post-content {
            font-size: 1.05rem;
            line-height: 1.75;
            color: var(--text-secondary);
        }
        
        .post-content p {
            margin-bottom: 1.5rem;
        }
        
        .post-content img {
            max-width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            margin: 1.5rem 0;
        }
        
        .post-content blockquote {
            border-left: 3px solid var(--accent);
            padding-left: 1.2rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: var(--text-secondary);
        }
        
        .post-content pre {
            background: var(--bg-secondary);
            padding: 1rem;
            border-radius: var(--border-radius-sm);
            overflow-x: auto;
            font-size: 0.85rem;
        }
        
        .post-content code {
            background: var(--bg-secondary);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        /* Галерея (Swiper) */
        .swiper {
            width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin: 1.5rem 0;
        }
        
        .swiper-slide img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        
        .swiper-button-next,
        .swiper-button-prev {
            color: var(--text-primary);
            background: rgba(0,0,0,0.5);
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        
        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 1.2rem;
        }
        
        .swiper-pagination-bullet {
            background: var(--text-primary);
        }
        
        /* Аудио/видео */
        audio, video {
            width: 100%;
            border-radius: var(--border-radius-sm);
            margin: 1rem 0;
        }
        
        /* Пагинация */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 3rem 0 1rem;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 0.75rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
            transition: var(--transition);
        }
        
        .pagination a {
            background: var(--bg-card);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        
        .pagination a:hover {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        
        .pagination .active span {
            background: var(--accent);
            color: white;
        }
        
        /* Теги */
        .post-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .post-tag {
            background: var(--bg-secondary);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        .post-tag:hover {
            background: var(--accent);
            color: white;
        }
        
        /* Кнопки */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .btn-outline {
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-secondary);
        }
        
        .btn-outline:hover {
            border-color: var(--accent);
            color: var(--accent);
        }
        
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--accent-hover);
        }
        
        /* Адаптивность */
        @media (max-width: 640px) {
            :root {
                --container-width: 100%;
                --container-padding: 1.25rem;
            }
            
            h1 { font-size: 1.8rem; }
            h2 { font-size: 1.5rem; }
            .post-title { font-size: 1.4rem; }
            .post-header h1 { font-size: 1.8rem; }
            .posts-feed { gap: 2rem; }
        }
        
        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .post-item, .post-header {
            animation: fadeIn 0.4s ease-out;
        }
        
        /* Плеер (минималистичный) */
        .player-container {
            max-width: 500px;
            margin: 0 auto;
            text-align: center;
        }
        
        .player-cover {
            width: 100%;
            max-width: 300px;
            aspect-ratio: 1;
            border-radius: var(--border-radius);
            margin: 0 auto 1.5rem;
            background: var(--bg-card);
            overflow: hidden;
        }
        
        .player-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .player-controls {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .player-btn {
            background: var(--bg-card);
            border: 1px solid var(--border);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .player-btn:hover {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
        
        .player-play {
            width: 56px;
            height: 56px;
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
        
        .player-progress {
            margin: 1rem 0;
        }
        
        .progress-bar {
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            overflow: hidden;
            cursor: pointer;
        }
        
        .progress-fill {
            width: 0%;
            height: 100%;
            background: var(--accent);
            transition: width 0.1s linear;
        }
        
        .player-time {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }
        
        .tracklist {
            margin-top: 2rem;
        }
        
        .track-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .track-item:hover {
            background: var(--bg-card);
        }
        
        .track-item.active {
            background: var(--accent-soft);
            border-left: 2px solid var(--accent);
        }
        
        .track-cover {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
        }
        
        .track-info {
            flex: 1;
            text-align: left;
        }
        
        .track-title {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .track-artist {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body data-theme="<?= $_COOKIE['theme'] ?? 'dark' ?>">