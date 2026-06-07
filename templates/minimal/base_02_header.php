<header class="header" style="
    position: sticky;
    top: 0;
    background: var(--bg-primary);
    border-bottom: 1px solid var(--border);
    backdrop-filter: blur(10px);
    z-index: 1000;
">
    <div class="container">
        <div style="
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: var(--header-height);
        ">
            <!-- Логотип -->
            <a href="<?= BASE_URL ?>/" style="
                font-size: 1.25rem;
                font-weight: 600;
                letter-spacing: -0.02em;
                color: var(--text-primary);
                text-decoration: none;
            ">
                <?= htmlspecialchars($config['site']['title'] ?? $config['site']['blog_title'] ?? 'Minimal Blog') ?>
            </a>
            
            <!-- Десктопное меню (скрывается на мобильных) -->
            <nav class="desktop-nav" style="
                display: flex;
                align-items: center;
                gap: 1.5rem;
            ">
                <a href="<?= BASE_URL ?>/player" style="
                    color: var(--text-secondary);
                    font-size: 0.9rem;
                    text-decoration: none;
                    transition: var(--transition);
                " onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'">Плеер</a>
                
                <a href="mailto:<?= htmlspecialchars($config['moderation']['admin_email'] ?? 'admin@example.com') ?>" style="
                    color: var(--text-secondary);
                    font-size: 0.9rem;
                    text-decoration: none;
                    transition: var(--transition);
                " onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'">Почта</a>
                
                <a href="https://t.me/<?= htmlspecialchars($config['channel']['channel_username'] ?? '') ?>" 
                   target="_blank" style="
                    color: var(--text-secondary);
                    font-size: 0.9rem;
                    text-decoration: none;
                    transition: var(--transition);
                " onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'">Telegram</a>
                
                <button id="themeToggle" style="
                    background: none;
                    border: none;
                    color: var(--text-secondary);
                    cursor: pointer;
                    font-size: 1.1rem;
                    display: flex;
                    align-items: center;
                    transition: var(--transition);
                " onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-secondary)'">
                    <svg id="themeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>
            </nav>
            
            <!-- Кнопка бургер-меню (только на мобильных) -->
            <button id="menuToggle" class="mobile-menu-btn" style="
                display: none;
                background: none;
                border: none;
                color: var(--text-primary);
                cursor: pointer;
                font-size: 1.5rem;
                padding: 0.5rem;
                transition: var(--transition);
            ">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12h18M3 6h18M3 18h18"/>
                </svg>
            </button>
        </div>
    </div>
</header>

<!-- Offcanvas меню для мобильных -->
<div id="offcanvasMenu" style="
    position: fixed;
    top: 0;
    right: -100%;
    width: 280px;
    height: 100%;
    background: var(--bg-primary);
    border-left: 1px solid var(--border);
    z-index: 1001;
    transition: right 0.3s ease;
    box-shadow: -2px 0 10px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
">
    <div style="
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border);
    ">
        <span style="font-weight: 600;">Меню</span>
        <button id="closeMenuBtn" style="
            background: none;
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            font-size: 1.2rem;
        ">&times;</button>
    </div>
    <nav style="
        display: flex;
        flex-direction: column;
        padding: 1rem 0;
    ">
        <a href="<?= BASE_URL ?>/" style="
            padding: 0.75rem 1.25rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        " onmouseover="this.style.backgroundColor='var(--bg-secondary)'; this.style.color='var(--accent)'" 
           onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--text-secondary)'">Главная</a>
        
        <a href="<?= BASE_URL ?>/player" style="
            padding: 0.75rem 1.25rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        " onmouseover="this.style.backgroundColor='var(--bg-secondary)'; this.style.color='var(--accent)'" 
           onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--text-secondary)'">Плеер</a>
        
        <a href="mailto:<?= htmlspecialchars($config['moderation']['admin_email'] ?? 'admin@example.com') ?>" style="
            padding: 0.75rem 1.25rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        " onmouseover="this.style.backgroundColor='var(--bg-secondary)'; this.style.color='var(--accent)'" 
           onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--text-secondary)'">Почта</a>
        
        <a href="https://t.me/<?= htmlspecialchars($config['channel']['channel_username'] ?? '') ?>" 
           target="_blank" style="
            padding: 0.75rem 1.25rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        " onmouseover="this.style.backgroundColor='var(--bg-secondary)'; this.style.color='var(--accent)'" 
           onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--text-secondary)'">Telegram</a>
        
        <div style="
            margin: 1rem 1.25rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border);
        ">
            <button id="mobileThemeToggle" style="
                background: none;
                border: none;
                color: var(--text-secondary);
                cursor: pointer;
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                transition: var(--transition);
            ">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
                Сменить тему
            </button>
        </div>
    </nav>
</div>

<!-- Оверлей для offcanvas -->
<div id="offcanvasOverlay" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: none;
    cursor: pointer;
"></div>

<script>
    // Мобильное меню
    const menuToggle = document.getElementById('menuToggle');
    const offcanvasMenu = document.getElementById('offcanvasMenu');
    const offcanvasOverlay = document.getElementById('offcanvasOverlay');
    const closeMenuBtn = document.getElementById('closeMenuBtn');
    
    function openMenu() {
        offcanvasMenu.style.right = '0';
        offcanvasOverlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    function closeMenu() {
        offcanvasMenu.style.right = '-100%';
        offcanvasOverlay.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    if (menuToggle) {
        menuToggle.addEventListener('click', openMenu);
    }
    if (closeMenuBtn) {
        closeMenuBtn.addEventListener('click', closeMenu);
    }
    if (offcanvasOverlay) {
        offcanvasOverlay.addEventListener('click', closeMenu);
    }
    
    // Закрытие по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && offcanvasMenu.style.right === '0px') {
            closeMenu();
        }
    });
    
    // Тема для мобильного меню
    const mobileThemeToggle = document.getElementById('mobileThemeToggle');
    if (mobileThemeToggle) {
        mobileThemeToggle.addEventListener('click', () => {
            const currentTheme = document.body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
            closeMenu();
        });
    }
    
    // Адаптивность: показываем/скрываем кнопку меню
    function checkMobile() {
        const desktopNav = document.querySelector('.desktop-nav');
        const mobileBtn = document.getElementById('menuToggle');
        
        if (window.innerWidth <= 768) {
            if (desktopNav) desktopNav.style.display = 'none';
            if (mobileBtn) mobileBtn.style.display = 'flex';
        } else {
            if (desktopNav) desktopNav.style.display = 'flex';
            if (mobileBtn) mobileBtn.style.display = 'none';
            // Закрываем меню при переходе на десктоп
            closeMenu();
        }
    }
    
    window.addEventListener('resize', checkMobile);
    checkMobile();
    
    // Тема
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    
    function setTheme(theme) {
        body.setAttribute('data-theme', theme);
        document.cookie = `theme=${theme}; path=/; max-age=31536000`;
        
        const themeIcon = document.getElementById('themeIcon');
        if (themeIcon) {
            if (theme === 'dark') {
                themeIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
            } else {
                themeIcon.innerHTML = '<circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>';
            }
        }
    }
    
    const savedTheme = document.cookie.replace(/(?:(?:^|.*;\s*)theme\s*\=\s*([^;]*).*$)|^.*$/, "$1") || 'dark';
    setTheme(savedTheme);
    
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        });
    }
</script>

<style>
    /* Стили для мобильного меню */
    @media (max-width: 768px) {
        .desktop-nav {
            display: none;
        }
        
        .mobile-menu-btn {
            display: flex !important;
        }
        
        .container {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }
    
    /* Анимация для оффканваса */
    #offcanvasMenu {
        will-change: right;
    }
    
    /* Улучшенные стили для мобильных устройств */
    @media (max-width: 480px) {
        .post-title {
            font-size: 1.4rem !important;
        }
        
        .post-header h1 {
            font-size: 1.6rem !important;
        }
        
        .pagination {
            gap: 0.3rem !important;
        }
        
        .pagination a, .pagination span {
            min-width: 32px !important;
            height: 32px !important;
            font-size: 0.8rem !important;
        }
    }
</style>

<main style="min-height: calc(100vh - var(--header-height) - 80px); padding: 2rem 0;">