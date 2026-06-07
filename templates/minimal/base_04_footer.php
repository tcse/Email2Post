</main>

<footer style="
    border-top: 1px solid var(--border);
    padding: 2rem 0;
    text-align: center;
    color: var(--text-muted);
    font-size: 0.8rem;
">
    <div class="container">
        <p>© <?= date('Y') ?> <?= htmlspecialchars($config['channel']['blog_title'] ?? 'Minimal Blog') ?></p>
        <p>Все записи блога публикуются из email сообщений. </p>
        <p style="margin-top: 0.5rem;">
            <a href="https://github.com/tcse/php-TMH" target="_blank" style="color: var(--text-muted);">
                email2post by TCSE
            </a>
        </p>
    </div>
</footer>