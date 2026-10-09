<?php include __DIR__ . '/header.php'; ?>
<main class="page-content">
    <div class="container">
        <?php if (!empty($page['featured_image'])): ?>
            <img src="<?= e(UPLOADS_URL . '/' . $page['featured_image']) ?>" alt="<?= e($page['title']) ?>" class="img-fluid mb-4 w-100" style="max-height:400px;object-fit:cover">
        <?php endif; ?>
        <div class="content-body">
            <?= $page['content'] ?? '' ?>
        </div>
        <?php include __DIR__ . '/share.php'; ?>
     <?php /*
if (!empty($page['slug']) && ($page['slug'] ?? '') !== '404'):
?>
<div class="mt-4">
    <a href="/pdf/<?= e($page['slug']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bi bi-file-pdf"></i> Export do PDF</a>
</div>
<?php endif;
*/ ?>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
