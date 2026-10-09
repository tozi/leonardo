<?php include __DIR__ . '/header.php'; ?>
<main class="page-content">
    <?php if (!empty($page['featured_image'])): ?>
        <div class="container-fluid p-0 mb-4">
            <img src="<?= e(UPLOADS_URL . '/' . $page['featured_image']) ?>" alt="<?= e($page['title']) ?>" class="w-100" style="max-height:450px;object-fit:cover">
        </div>
    <?php endif; ?>
    <div class="container-fluid px-4 px-lg-5">
        <div class="content-body">
            <?= $page['content'] ?? '' ?>
        </div>
        <?php include __DIR__ . '/share.php'; ?>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
