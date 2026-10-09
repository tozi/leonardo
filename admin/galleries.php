<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

if (isset($_GET['delete']) && verify_csrf($_GET['token'] ?? '')) {
    db()->prepare('DELETE FROM galleries WHERE id = ?')->execute([(int)$_GET['delete']]);
    set_flash('success', 'Galéria zmazaná.');
    redirect(ADMIN_URL . '/galleries.php');
}

$galleries = get_all_galleries();
admin_header('Galérie');
?>

<div class="card border-0 shadow-sm">
    <div class="card-header">
        <h2>Všetky galérie</h2>
        <a href="gallery-edit.php" class="btn btn-primary">+ Nová galéria</a>
    </div>
    <div class="card-body table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Názov</th>
                    <th>Slug</th>
                    <th>Obrázky</th>
                    <th>Vytvorené</th>
                    <th>Akcie</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($galleries)): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted)">Zatiaľ žiadne galérie.</td></tr>
                <?php endif; ?>
                <?php foreach ($galleries as $g): ?>
                <tr>
                    <td><strong><?= e($g['title']) ?></strong></td>
                    <td><code><?= e($g['slug']) ?></code></td>
                    <td><?= (int)$g['image_count'] ?></td>
                    <td><?= date('d.m.Y', strtotime($g['created_at'])) ?></td>
                    <td style="display:flex;gap:6px">
                        <a href="gallery-edit.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-primary">Upraviť</a>
                        <a href="?delete=<?= $g['id'] ?>&token=<?= generate_csrf() ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Zmazať galériu a všetky obrázky?')">Zmazať</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>
