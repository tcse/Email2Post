<?php
// /plugins/tcse/tmh2.0/templates/minimal/player.php
// Этот файл подключается через PlayerHandler.php
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <base href="<?= BASE_URL ?>/">
    
    <title><?= htmlspecialchars($pageTitle ?? 'Музыкальный плеер') ?> | Minimal Player</title>
    <meta name="description" content="Минималистичный музыкальный плеер">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
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
            --header-height: 64px;
            --border-radius: 12px;
            --border-radius-sm: 8px;
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
            line-height: 1.5;
            font-size: 16px;
            transition: background 0.3s ease, color 0.3s ease;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }
        
        /* Шапка */
        .header {
            position: sticky;
            top: 0;
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(10px);
            z-index: 100;
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: var(--header-height);
        }
        
        .logo {
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--text-primary);
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .nav-links a {
            color: var(--text-secondary);
            font-size: 0.9rem;
            transition: var(--transition);
            text-decoration: none;
        }
        
        .nav-links a:hover {
            color: var(--accent);
        }
        
        .theme-btn {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            transition: var(--transition);
        }
        
        .theme-btn:hover {
            color: var(--accent);
        }
        
        /* Основной контент — две колонки */
        .player-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            min-height: calc(100vh - var(--header-height) - 80px);
        }
        
        .player-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        /* Левая колонка — плеер */
        .player-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .player-card {
            width: 100%;
            max-width: 400px;
            background: var(--bg-card);
            border-radius: var(--border-radius);
            padding: 2rem;
            border: 1px solid var(--border);
        }
        
        .player-cover {
            width: 100%;
            aspect-ratio: 1;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 1.5rem;
            background: var(--bg-secondary);
        }
        
        .player-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .player-info {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .player-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.25rem;
        }
        
        .player-artist {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .player-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .player-btn {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--text-primary);
            font-size: 1.1rem;
        }
        
        .player-btn:hover {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
            transform: scale(1.05);
        }
        
        .player-play {
            width: 56px;
            height: 56px;
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
        
        .player-play:hover {
            background: var(--accent-hover);
            transform: scale(1.05);
        }
        
        .player-progress {
            width: 100%;
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
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }
        
        /* Правая колонка — плейлист */
        .playlist-col {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            border: 1px solid var(--border);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
            max-height: 500px;
        }
        
        .playlist-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
            background: var(--bg-secondary);
        }
        
        .playlist-header h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
        }
        
        .playlist-header p {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
        
        .tracklist {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem;
        }
        
        .track-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 0.25rem;
        }
        
        .track-item:hover {
            background: var(--bg-secondary);
        }
        
        .track-item.active {
            background: var(--accent-soft);
            border-left: 2px solid var(--accent);
        }
        
        .track-cover {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--bg-secondary);
        }
        
        .track-info {
            flex: 1;
            min-width: 0;
        }
        
        .track-title {
            font-size: 0.9rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .track-artist {
            font-size: 0.75rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .track-duration {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-family: monospace;
        }
        
        .track-actions {
            display: flex;
            gap: 0.5rem;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .track-item:hover .track-actions {
            opacity: 1;
        }
        
        .track-action-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            transition: var(--transition);
            font-size: 0.8rem;
        }
        
        .track-action-btn:hover {
            color: var(--accent);
        }
        
        /* Подвал */
        .footer {
            border-top: 1px solid var(--border);
            padding: 1.5rem 0;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        
        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        /* Адаптивность для мобильных */
        @media (max-width: 768px) {
            .player-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .player-card {
                max-width: 100%;
                padding: 1.5rem;
            }
            
            .player-col {
                order: 1;
            }
            
            .playlist-col {
                order: 2;
                max-height: 400px;
            }
            
            .header-container,
            .player-wrapper,
            .footer-container {
                padding: 0 1rem;
            }
            
            .nav-links {
                gap: 1rem;
            }
            
            .track-cover {
                width: 36px;
                height: 36px;
            }
        }
        
        /* Для небольших десктопов */
        @media (max-width: 1024px) and (min-width: 769px) {
            .player-grid {
                gap: 1.5rem;
            }
            
            .player-card {
                padding: 1.5rem;
            }
            
            .player-btn {
                width: 40px;
                height: 40px;
            }
            
            .player-play {
                width: 48px;
                height: 48px;
            }
        }
        
        /* Анимации */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .player-wrapper {
            animation: fadeInUp 0.4s ease-out;
        }
        
        /* Скролл */
        .tracklist::-webkit-scrollbar {
            width: 4px;
        }
        
        .tracklist::-webkit-scrollbar-track {
            background: var(--border);
            border-radius: 2px;
        }
        
        .tracklist::-webkit-scrollbar-thumb {
            background: var(--accent);
            border-radius: 2px;
        }
        .background-blur {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            filter: blur(40px) brightness(0.5);
            z-index: -1;
        }
    </style>
</head>
<body data-theme="<?= $_COOKIE['theme'] ?? 'dark' ?>">

<!-- Шапка -->
<header class="header">
    <div class="header-container">
        <div class="header-content">
            <a href="<?= BASE_URL ?>/" class="logo">🎵 TMH</a>
            <div class="nav-links">
                <a href="<?= BASE_URL ?>/">Блог</a>
                <a href="https://t.me/<?= htmlspecialchars($config['channel']['channel_username'] ?? 'tcsecms') ?>" target="_blank">Telegram</a>
                <button id="themeToggle" class="theme-btn">
                    <svg id="themeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Основной контент -->
<main>
    <div class="player-wrapper">
        <div class="player-grid">
            
            <!-- Левая колонка: плеер -->
            <div class="player-col">
                <div class="player-card">
                    <div class="player-cover">
                        <img id="playerCover" src="https://placehold.co/400x400/1a1a1a/e8e8e8?text=TMH" alt="Обложка">
                    </div>
                    
                    <div class="player-info">
                        <h3 id="playerTitle" class="player-title">Название трека</h3>
                        <p id="playerArtist" class="player-artist">Исполнитель</p>
                    </div>
                    
                    <div class="player-controls">
                        <button class="player-btn" id="prevBtn">⏮</button>
                        <button class="player-btn player-play" id="playPauseBtn">▶</button>
                        <button class="player-btn" id="nextBtn">⏭</button>
                    </div>
                    
                    <div class="player-progress">
                        <div class="progress-bar" id="progressBar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        <div class="player-time">
                            <span id="currentTime">0:00</span>
                            <span id="totalTime">0:00</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Правая колонка: плейлист -->
            <div class="playlist-col">
                <div class="playlist-header">
                    <h3>Плейлист</h3>
                    <p id="trackCount">Загрузка...</p>
                </div>
                <div class="tracklist" id="tracklist">
                    <div style="text-align: center; padding: 2rem; color: var(--text-muted);">Загрузка треков...</div>
                </div>
            </div>
            
        </div>
    </div>
</main>

<!-- Подвал -->
<footer class="footer">
    <div class="footer-container">
        <p>© <?= date('Y') ?> TMH by TCSE</p>
    </div>
</footer>

<script>
    // === Конфигурация ===
    const BASE_URL = '<?= BASE_URL ?>';
    
    // === Переключение темы ===
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const themeIcon = document.getElementById('themeIcon');
    
    function setTheme(theme) {
        body.setAttribute('data-theme', theme);
        document.cookie = `theme=${theme}; path=/; max-age=31536000`;
        
        if (theme === 'dark') {
            themeIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
        } else {
            themeIcon.innerHTML = '<circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>';
        }
    }
    
    const savedTheme = document.cookie.replace(/(?:(?:^|.*;\s*)theme\s*\=\s*([^;]*).*$)|^.*$/, "$1") || 'dark';
    setTheme(savedTheme);
    
    themeToggle?.addEventListener('click', () => {
        const currentTheme = body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    });
    
    // === Плеер ===
    let tracks = [];
    let currentTrack = 0;
    let audio = null;
    let isPlaying = false;
    
    // Форматирование времени
    function formatTime(secs) {
        if (isNaN(secs)) return '0:00';
        const mins = Math.floor(secs / 60);
        const sec = Math.floor(secs % 60);
        return `${mins}:${sec < 10 ? '0' : ''}${sec}`;
    }
    
    // Экранирование HTML
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    
    // Рендер плейлиста
    function renderTracklist() {
        const container = document.getElementById('tracklist');
        const trackCountSpan = document.getElementById('trackCount');
        
        if (!tracks.length) {
            container.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-muted);">Нет треков</div>';
            trackCountSpan.textContent = '0 треков';
            return;
        }
        
        trackCountSpan.textContent = `${tracks.length} ${tracks.length === 1 ? 'трек' : (tracks.length < 5 ? 'трека' : 'треков')}`;
        
        container.innerHTML = tracks.map((track, i) => `
            <div class="track-item ${i === currentTrack ? 'active' : ''}" data-index="${i}">
                <img class="track-cover" src="${track.cover || 'https://placehold.co/44x44/1a1a1a/e8e8e8?text=TMH'}" alt="">
                <div class="track-info">
                    <div class="track-title">${escapeHtml(track.title)}</div>
                    <div class="track-artist">${escapeHtml(track.performer || 'Неизвестный')}</div>
                </div>
                <div class="track-duration">${formatTime(track.duration || 0)}</div>
                <div class="track-actions">
                    <button class="track-action-btn" data-download="${i}" title="Скачать">⬇</button>
                </div>
            </div>
        `).join('');
        
        // Обработчики кликов по трекам
        document.querySelectorAll('.track-item').forEach(el => {
            el.addEventListener('click', (e) => {
                if (e.target.classList.contains('track-action-btn')) return;
                playTrack(parseInt(el.dataset.index));
            });
        });
        
        // Обработчики скачивания
        document.querySelectorAll('[data-download]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const index = parseInt(btn.dataset.download);
                const track = tracks[index];
                if (track && track.url) {
                    const a = document.createElement('a');
                    a.href = track.url;
                    a.download = `${track.title} - ${track.performer || 'track'}.mp3`;
                    a.click();
                }
            });
        });
    }
    
    // Воспроизведение трека
    function playTrack(index) {
        if (index < 0 || index >= tracks.length) return;
        if (audio) audio.pause();
        
        currentTrack = index;
        const track = tracks[currentTrack];
        
        audio = new Audio(track.url);
        audio.addEventListener('timeupdate', updateProgress);
        audio.addEventListener('loadedmetadata', () => {
            document.getElementById('totalTime').textContent = formatTime(audio.duration);
        });
        audio.addEventListener('ended', () => playNext());
        audio.addEventListener('error', () => {
            console.error('Ошибка воспроизведения');
            document.getElementById('playerTitle').textContent = 'Ошибка';
        });
        
        document.getElementById('playerTitle').textContent = track.title;
        document.getElementById('playerArtist').textContent = track.performer || 'Неизвестный';
        
        if (track.cover) {
            document.getElementById('playerCover').src = track.cover;
        }
        
        audio.play().catch(e => console.log('Автовоспроизведение заблокировано'));
        isPlaying = true;
        updatePlayButton();
        renderTracklist();
        
        // Отправка статистики
        fetch(BASE_URL + '/core/update_play.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ file_id: track.id })
        }).catch(e => console.log('Статистика не отправлена'));
    }
    
    function playPause() {
        if (!audio) return;
        if (isPlaying) {
            audio.pause();
        } else {
            audio.play().catch(e => console.log('Ошибка воспроизведения'));
        }
        isPlaying = !isPlaying;
        updatePlayButton();
    }
    
    function playNext() {
        if (!tracks.length) return;
        let next = (currentTrack + 1) % tracks.length;
        playTrack(next);
    }
    
    function playPrev() {
        if (!tracks.length) return;
        let prev = (currentTrack - 1 + tracks.length) % tracks.length;
        playTrack(prev);
    }
    
    function updateProgress() {
        if (audio && !isNaN(audio.duration)) {
            const percent = (audio.currentTime / audio.duration) * 100;
            document.getElementById('progressFill').style.width = percent + '%';
            document.getElementById('currentTime').textContent = formatTime(audio.currentTime);
        }
    }
    
    function updatePlayButton() {
        const btn = document.getElementById('playPauseBtn');
        btn.innerHTML = isPlaying ? '⏸' : '▶';
    }
    
    // Загрузка плейлиста
    async function loadPlaylist() {
        try {
            const response = await fetch(BASE_URL + '/core/proxy.php');
            const data = await response.json();
            tracks = data.tracks || [];
            renderTracklist();
            if (tracks.length) playTrack(0);
        } catch (err) {
            console.error('Ошибка загрузки плейлиста:', err);
            document.getElementById('tracklist').innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-muted);">Ошибка загрузки треков</div>';
        }
    }
    
    // Обработчики событий
    document.getElementById('playPauseBtn').addEventListener('click', playPause);
    document.getElementById('nextBtn').addEventListener('click', playNext);
    document.getElementById('prevBtn').addEventListener('click', playPrev);
    document.getElementById('progressBar').addEventListener('click', (e) => {
        if (audio && !isNaN(audio.duration)) {
            const rect = e.currentTarget.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            audio.currentTime = percent * audio.duration;
        }
    });
    
    // Запуск
    loadPlaylist();
</script>

</body>
</html>