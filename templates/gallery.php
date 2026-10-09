<?php
include __DIR__ . '/header.php';
$gallery = null;
$stmt = db()->prepare('SELECT * FROM galleries WHERE page_id = ? OR slug = ? LIMIT 1');
$stmt->execute([$page['id'] ?? 0, $page['slug'] ?? '']);
$gallery = $stmt->fetch();
$images = $gallery ? get_gallery_images($gallery['id']) : [];
?>
<main class="page-content">
    <div class="container">
        <h1 class="mb-3"><?= e($page['title']) ?></h1>
        <?= $page['content'] ?? '' ?>
        <?php if ($gallery && !empty($images)): ?>
            <div class="row g-3 mt-4">
                <?php foreach ($images as $img): ?>
                    <?php $imgSrc = UPLOADS_URL . '/' . $img['filename']; ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <button
                            type="button"
                            class="gallery-item"
                            data-gallery-trigger
                            data-src="<?= e($imgSrc) ?>"
                            data-title="<?= e($img['title'] ?? '') ?>"
                            aria-label="Otvoriť fotku v zväčšenom okne"
                        >
                            <img src="<?= e($imgSrc) ?>" alt="<?= e($img['alt_text'] ?? $img['title'] ?? '') ?>" loading="lazy">
                            <?php if (!empty($img['title'])): ?><span class="caption"><?= e($img['title']) ?></span><?php endif; ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($gallery): ?>
            <p class="text-muted mt-3">Galéria zatiaľ neobsahuje žiadne obrázky.</p>
        <?php endif; ?>
        <?php include __DIR__ . '/share.php'; ?>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
