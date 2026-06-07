<div class="container">
    <?php if ($pageType === 'post' && isset($post)): ?>
        <?php include __DIR__ . '/blog_post.php'; ?>
    <?php elseif ($pageType === 'list'): ?>
        <?php include __DIR__ . '/blog_list.php'; ?>
    <?php else: ?>
        <p>Нет контента для отображения.</p>
    <?php endif; ?>
</div>