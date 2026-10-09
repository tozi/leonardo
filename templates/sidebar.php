<?php include __DIR__ . '/header.php'; ?>
<main class="page-content">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <?php if (!empty($page['featured_image'])): ?>
                    <img src="<?= e(UPLOADS_URL . '/' . $page['featured_image']) ?>" alt="<?= e($page['title']) ?>" class="img-fluid rounded mb-4 w-100" style="max-height:350px;object-fit:cover">
                <?php endif; ?>
                <div class="content-body">
                    <?= $page['content'] ?? '' ?>
                </div>
                <?php include __DIR__ . '/share.php'; ?>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Navigácia</h5>
                        <?= render_menu('main-menu', 'nav flex-column') ?>
                    </div>
                </div>
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <h5 class="card-title">Kontakt</h5>
                        <p class="small text-muted mb-2"><?= e(get_setting('site_phone')) ?></p>
                        <a href="/kontakt" class="btn btn-primary btn-sm">Napíšte nám</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
