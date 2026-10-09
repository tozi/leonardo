<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

// Create new menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_menu'])) {
    if (verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $name = trim($_POST['name'] ?? '');
        $slug = create_slug($name);
        $location = trim($_POST['location'] ?? 'custom');
        if ($name) {
            db()->prepare('INSERT INTO menus (name, slug, location) VALUES (?, ?, ?)')->execute([$name, $slug, $location]);
            set_flash('success', 'Menu bolo vytvorené.');
        }
    }
    redirect(ADMIN_URL . '/menus.php');
}

// Delete menu
if (isset($_GET['delete_menu']) && verify_csrf($_GET['token'] ?? '')) {
    $mid = (int)$_GET['delete_menu'];
    if ($mid > 2) { // protect main & footer
        db()->prepare('DELETE FROM menus WHERE id = ?')->execute([$mid]);
        set_flash('success', 'Menu bolo zmazané.');
    }
    redirect(ADMIN_URL . '/menus.php');
}

$menus = db()->query('SELECT m.*, COUNT(mi.id) as items_count FROM menus m LEFT JOIN menu_items mi ON m.id = mi.menu_id GROUP BY m.id ORDER BY m.id')->fetchAll();

admin_header('Menu');
?>

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h2>Všetky menu</h2>
    </div>
    <div class="card-body table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Názov</th>
                    <th>Slug</th>
                    <th>Umiestnenie</th>
                    <th>Položky</th>
                    <th>Akcie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menus as $m): ?>
                <tr>
                    <td><strong><?= e($m['name']) ?></strong></td>
                    <td><code><?= e($m['slug']) ?></code></td>
                    <td><?= e($m['location']) ?></td>
                    <td><?= (int)$m['items_count'] ?></td>
                    <td style="display:flex;gap:6px">
                        <a href="menu-edit.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-primary">Upraviť položky</a>
                        <?php if ($m['id'] > 2): ?>
                        <a href="?delete_menu=<?= $m['id'] ?>&token=<?= generate_csrf() ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Zmazať toto menu?')">Zmazať</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header"><h2>Vytvoriť nové menu</h2></div>
    <div class="card-body">
        <form method="post" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
            <?= csrf_field() ?>
            <input type="hidden" name="create_menu" value="1">
            <div class="form-group" style="margin:0">
                <label>Názov</label>
                <input type="text" name="name" required placeholder="napr. Sidebar menu">
            </div>
            <div class="form-group" style="margin:0">
                <label>Umiestnenie (location)</label>
                <input type="text" name="location" value="custom" placeholder="header, footer, sidebar...">
            </div>
            <button type="submit" class="btn btn-primary">Vytvoriť</button>
        </form>
        <p class="form-hint" style="margin-top:12px">
            Nové menu môžete vložiť do šablóny cez PHP kód: <code>&lt;?= render_menu('slug-menu') ?&gt;</code>
        </p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header"><h2>Ako pridať menu do šablóny</h2></div>
    <div class="card-body">
        <p>V súboroch šablón (<code>templates/*.php</code>) použite:</p>
        <pre style="background:#f4f5f7;padding:12px;border-radius:6px;margin-top:8px;overflow-x:auto">
&lt;!-- Hlavné menu --&gt;
&lt;?= render_menu('main-menu', 'nav-menu') ?&gt;

&lt;!-- Ľubovoľné menu podľa slug --&gt;
&lt;?= render_menu('moje-menu', 'moja-css-trieda') ?&gt;
        </pre>
        <p style="margin-top:12px;color:var(--text-muted)">Menu môžete umiestniť kamkoľvek – do headeru, footeru, sidebaru alebo priamo do obsahu stránky.</p>
    </div>
</div>

<?php admin_footer(); ?>
