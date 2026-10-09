<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

$pages_count = db()->query('SELECT COUNT(*) FROM pages')->fetchColumn();
$published = db()->query('SELECT COUNT(*) FROM pages WHERE status="published"')->fetchColumn();
$galleries_count = db()->query('SELECT COUNT(*) FROM galleries')->fetchColumn();
$menus_count = db()->query('SELECT COUNT(*) FROM menus')->fetchColumn();
$media_count = db()->query('SELECT COUNT(*) FROM media')->fetchColumn();

admin_header('Dashboard');
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md"><div class="stat-card"><div class="stat-value"><?= $pages_count ?></div><div class="text-muted small">Stránky</div></div></div>
    <div class="col-6 col-md"><div class="stat-card"><div class="stat-value"><?= $published ?></div><div class="text-muted small">Publikované</div></div></div>
    <div class="col-6 col-md"><div class="stat-card"><div class="stat-value"><?= $galleries_count ?></div><div class="text-muted small">Galérie</div></div></div>
    <div class="col-6 col-md"><div class="stat-card"><div class="stat-value"><?= $menus_count ?></div><div class="text-muted small">Menu</div></div></div>
    <div class="col-6 col-md"><div class="stat-card"><div class="stat-value"><?= $media_count ?></div><div class="text-muted small">Médiá</div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Rýchle akcie</div>
    <div class="card-body d-flex flex-wrap gap-2">
        <a href="page-edit.php" class="btn btn-primary">+ Nová stránka</a>
        <a href="post-edit.php" class="btn btn-primary">+ Nový článok</a>
        <a href="gallery-edit.php" class="btn btn-primary">+ Nová galéria</a>
        <a href="menus.php" class="btn btn-outline-secondary">Spravovať menu</a>
        <a href="media.php" class="btn btn-outline-secondary">Nahrať médiá</a>
        <a href="profile.php" class="btn btn-outline-secondary">Zmeniť heslo</a>
        <a href="/" target="_blank" class="btn btn-outline-secondary">Zobraziť web</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Posledné stránky</span>
        <a href="pages.php" class="btn btn-sm btn-outline-secondary">Všetky</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Názov</th><th>Slug</th><th>Stav</th><th>Upravené</th><th></th></tr></thead>
                <tbody>
                <?php foreach (db()->query('SELECT * FROM pages ORDER BY updated_at DESC LIMIT 8') as $p): ?>
                <tr>
                    <td><?= e($p['title']) ?></td>
                    <td><code>/<?= e($p['slug']) ?></code></td>
                    <td><span class="badge text-bg-<?= $p['status']==='published'?'success':'warning' ?>"><?= $p['status']==='published'?'Publikované':'Koncept' ?></span></td>
                    <td><?= date('d.m.Y H:i', strtotime($p['updated_at'])) ?></td>
                    <td><a href="page-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary">Upraviť</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$recent_posts = [];
try {
    $recent_posts = db()->query('SELECT * FROM posts ORDER BY updated_at DESC LIMIT 5')->fetchAll();
} catch (Exception $e) {}
?>
<?php if ($recent_posts): ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Posledné články</span>
        <a href="posts.php" class="btn btn-sm btn-outline-secondary">Všetky</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Názov</th><th>Stav</th><th>Upravené</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recent_posts as $rp): ?>
                <tr>
                    <td><?= e($rp['title']) ?></td>
                    <td><span class="badge text-bg-<?= $rp['status']==='published'?'success':'warning' ?>"><?= $rp['status']==='published'?'Publikované':'Koncept' ?></span></td>
                    <td><?= date('d.m.Y H:i', strtotime($rp['updated_at'])) ?></td>
                    <td><a href="post-edit.php?id=<?= $rp['id'] ?>" class="btn btn-sm btn-outline-secondary">Upraviť</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php admin_footer(); ?>
