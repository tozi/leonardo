<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

// Delete
if (isset($_GET['delete']) && verify_csrf($_GET['token'] ?? '')) {
    $id = (int)$_GET['delete'];
    if ($id > 1) { // protect home
        db()->prepare('DELETE FROM pages WHERE id = ?')->execute([$id]);
        set_flash('success', 'Stránka bola zmazaná.');
    }
    redirect(ADMIN_URL . '/pages.php');
}

$pages = get_all_pages();
admin_header('Stránky');
?>

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h2>Všetky stránky</h2>
        <a href="page-edit.php" class="btn btn-primary">+ Nová stránka</a>
    </div>
    <div class="card-body table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Názov</th>
                    <th>Slug</th>
                    <th>Šablóna</th>
                    <th>Stav</th>
                    <th>Poradie</th>
                    <th>Akcie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                <tr>
                    <td><strong><?= e($p['title']) ?></strong></td>
                    <td><code>/<?= e($p['slug']) ?></code></td>
                    <td><?= e($p['template_name'] ?? '—') ?></td>
                    <td>
                        <span class="badge badge-<?= $p['status'] === 'published' ? 'success' : 'warning' ?>">
                            <?= $p['status'] === 'published' ? 'Publikované' : 'Koncept' ?>
                        </span>
                    </td>
                    <td><?= (int)$p['sort_order'] ?></td>
                    <td style="display:flex;gap:6px">
                        <a href="page-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">Upraviť</a>
                        <a href="/<?= e($p['slug'] === 'home' ? '' : $p['slug']) ?>" target="_blank" class="btn btn-sm btn-outline">Zobraziť</a>
                        <?php if ($p['id'] > 1): ?>
                        <a href="?delete=<?= $p['id'] ?>&token=<?= generate_csrf() ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Naozaj zmazať túto stránku?')">Zmazať</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>
