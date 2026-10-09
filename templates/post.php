<?php include __DIR__ . '/header.php'; ?>
<main class="page-content">
    <div class="container" style="max-width:800px">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Domov</a></li>
                <li class="breadcrumb-item"><a href="/blog">Blog</a></li>
                <li class="breadcrumb-item active"><?= e($page['title']) ?></li>
            </ol>
        </nav>
        <p class="text-muted small"><?= !empty($page['published_at']) ? date('d.m.Y', strtotime($page['published_at'])) : '' ?></p>
        <?php $pcats = get_categories_for_post($page['id'] ?? 0); if ($pcats): ?>
        <div class="mb-2">
            <?php foreach ($pcats as $pc): ?>
            <a href="/blog?category=<?= e($pc['slug']) ?>" class="badge text-bg-secondary text-decoration-none me-1"><?= e($pc['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <h1 class="mb-4"><?= e($page['title']) ?></h1>
        <?php if (!empty($page['featured_image'])): ?>
            <img src="<?= e(UPLOADS_URL . '/' . $page['featured_image']) ?>" class="img-fluid rounded mb-4 w-100" style="max-height:400px;object-fit:cover" alt="">
        <?php endif; ?>
        <div class="content-body">
            <?= $page['content'] ?? '' ?>
        </div>
        
        <?php
        $post_images = [];
        try {
            if (!empty($page['id'])) {
                $st = db()->prepare('SELECT * FROM post_images WHERE post_id = ? ORDER BY sort_order, id');
                $st->execute([(int)$page['id']]);
                $post_images = $st->fetchAll();
            }
        } catch (Exception $e) {}
        if ($post_images):
        ?>
        <div class="row g-3 mt-4 mb-3">
            <?php foreach ($post_images as $img): ?>
            <div class="col-6 col-md-4">
                <div class="gallery-item">
                    <img src="<?= e(UPLOADS_URL . '/' . $img['filename']) ?>" alt="<?= e($img['alt_text'] ?? $img['title'] ?? '') ?>" loading="lazy">
                    <?php if (!empty($img['title'])): ?><div class="caption"><?= e($img['title']) ?></div><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php include __DIR__ . '/share.php'; ?>
        <hr class="my-4">
        <a href="/blog" class="btn btn-outline-secondary btn-sm">&larr; Späť na blog</a>
        <?php /*if (!empty($page['slug'])): ?>
        <a href="/pdf/<?= e($page['slug']) ?>" class="btn btn-outline-secondary btn-sm ms-2" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
        <?php endif;*/ ?>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
