<h1 style="font-size: 1.5rem; margin-bottom: 2rem;">
    Посты с тегом <span style="color: var(--accent);">#<?= htmlspecialchars($tag ?? $_GET['tag'] ?? '') ?></span>
</h1>

<div class="posts-feed">
    <?php if (empty($posts)): ?>
        <p style="text-align: center; color: var(--text-muted);">Нет постов с этим тегом.</p>
    <?php else: ?>
        <?php foreach ($posts as $item): 
            $title = $item['title'] ?? '';
            if (empty($title)) {
                $title = trim(explode("\n", $item['caption'] ?? $item['text'] ?? '')[0]) ?: 'Запись без названия';
            }
            $date = date('d.m.Y', strtotime($item['date']));
            $url = BASE_URL . '/post/' . $item['id'];
            $excerpt = htmlspecialchars(mb_substr(strip_tags($item['excerpt'] ?? $item['caption'] ?? $item['text'] ?? ''), 0, 200)) . '...';
        ?>
            <article class="post-item">
                <h2 class="post-title"><a href="<?= $url ?>"><?= htmlspecialchars($title) ?></a></h2>
                <div class="post-meta">
                    <time datetime="<?= $item['date'] ?>"><?= $date ?></time>
                </div>
                <div class="post-excerpt"><?= $excerpt ?></div>
                <a href="<?= $url ?>" class="post-read-more">Читать далее →</a>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div style="margin-top: 2rem;">
    <a href="<?= BASE_URL ?>/" class="btn btn-outline">← На главную</a>
</div>