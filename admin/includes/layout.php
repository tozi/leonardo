<?php
function admin_header($page_title = 'Dashboard') {
    require_once dirname(__DIR__, 2) . '/includes/security_headers.php';
    send_security_headers();
    $user = current_user();
    $current = basename($_SERVER['PHP_SELF']);
    $nav = [
        'index.php' => ['📊', 'Dashboard'],
        'pages.php' => ['📄', 'Stránky'],
        'posts.php' => ['📝', 'Blog'],
        'categories.php' => ['🏷', 'Kategórie'],
        'menus.php' => ['☰', 'Menu'],
        'galleries.php' => ['🖼', 'Galérie'],
        'languages.php' => ['🌍', 'Jazyky'],
        'ui-dictionary.php' => ['📝', 'UI texty'],
        'templates.php' => ['🎨', 'Šablóny'],
        'media.php' => ['📁', 'Médiá'],
        'settings.php' => ['⚙', 'Nastavenia'],
        'profile.php' => ['👤', 'Profil / Heslo'],
    ];
    $active_map = [
        'page-edit.php' => 'pages.php',
        'post-edit.php' => 'posts.php',
        'menu-edit.php' => 'menus.php',
        'gallery-edit.php' => 'galleries.php',
    ];
    $active = $active_map[$current] ?? $current;
    ?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> – Leonardowin CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<div class="admin-backdrop" id="adminBackdrop"></div>
<div class="d-flex">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="brand">Leonardo<span>win</span> CMS</div>
        <nav class="nav flex-column py-2">
            <?php foreach ($nav as $file => [$icon, $label]): ?>
            <a href="<?= $file ?>" class="nav-link <?= $active === $file ? 'active' : '' ?>">
                <span class="icon me-2"><?= $icon ?></span><span><?= $label ?></span>
            </a>
            <?php endforeach; ?>
            <a href="/" target="_blank" class="nav-link"><span class="icon me-2">🌐</span><span>Zobraziť web</span></a>
            <a href="logout.php" class="nav-link"><span class="icon me-2">🚪</span><span>Odhlásiť</span></a>
        </nav>
    </aside>
    <div class="admin-main flex-grow-1">
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <button type="button" class="btn btn-sm btn-outline-secondary admin-menu-toggle" id="adminMenuToggle" aria-label="Menu" aria-controls="adminSidebar" aria-expanded="false">☰</button>
                <h1 class="h5 mb-0 fw-semibold text-truncate"><?= e($page_title) ?></h1>
            </div>
            <div class="d-flex align-items-center gap-3 text-muted small flex-shrink-0">
                <span class="d-none d-sm-inline"><?= e($user['username'] ?? '') ?></span>
                <a href="logout.php" class="text-muted">Odhlásiť</a>
            </div>
        </header>
        <div class="admin-content">
            <?php $flash = get_flash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
<?php
}

function admin_footer($extra_js = '') {
    ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var sb = document.getElementById('adminSidebar'), bd = document.getElementById('adminBackdrop'),
        tg = document.getElementById('adminMenuToggle');
    function set(open) {
        document.body.classList.toggle('admin-nav-open', open);
        if (tg) tg.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if (tg) tg.addEventListener('click', function () { set(!document.body.classList.contains('admin-nav-open')); });
    if (bd) bd.addEventListener('click', function () { set(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
    if (sb) sb.addEventListener('click', function (e) { if (e.target.closest('a')) set(false); });
    // tabuľky: vodorovný posun na malých displejoch
    document.querySelectorAll('.admin-content table').forEach(function (t) {
        if (t.closest('.table-responsive, .table-wrap')) return;
        var w = document.createElement('div');
        w.className = 'table-responsive';
        t.parentNode.insertBefore(w, t);
        w.appendChild(t);
    });
})();
</script>
<?= $extra_js ?>
</body>
</html>
<?php
}
