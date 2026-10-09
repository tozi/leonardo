<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

// Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    if (verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        if (!empty($_FILES['file']['name'])) {
            $up = upload_image($_FILES['file']);
            if ($up['success']) {
                set_flash('success', 'Súbor nahraný: ' . $up['filename']);
            } else {
                set_flash('error', $up['error']);
            }
        }
    }
    redirect(ADMIN_URL . '/media.php');
}

// Delete
if (isset($_GET['delete']) && verify_csrf($_GET['token'] ?? '')) {
    $mid = (int)$_GET['delete'];
    $stmt = db()->prepare('SELECT * FROM media WHERE id = ?');
    $stmt->execute([$mid]);
    $m = $stmt->fetch();
    if ($m) {
        $path = UPLOADS_PATH . '/' . $m['filename'];
        if (file_exists($path)) unlink($path);
        db()->prepare('DELETE FROM media WHERE id = ?')->execute([$mid]);
        set_flash('success', 'Súbor zmazaný.');
    }
    redirect(ADMIN_URL . '/media.php');
}

$media = db()->query('SELECT * FROM media ORDER BY uploaded_at DESC')->fetchAll();
admin_header('Médiá');
?>

<div class="card border-0 shadow-sm">
    <div class="card-header"><h2>Nahrať súbor</h2></div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:end">
            <?= csrf_field() ?>
            <input type="hidden" name="upload" value="1">
            <div class="form-group" style="margin:0">
                <label>Obrázok (JPG, PNG, GIF, WebP – max 5 MB)</label>
                <input type="file" name="file" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-primary">Nahrať</button>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header"><h2>Knižnica médií (<?= count($media) ?>)</h2></div>
    <div class="card-body">
        <?php if (empty($media)): ?>
            <p style="color:var(--text-muted)">Zatiaľ žiadne súbory.</p>
        <?php else: ?>
        <div class="gallery-admin-grid">
            <?php foreach ($media as $m): ?>
            <div class="gallery-admin-item" title="<?= e($m['original_name']) ?>">
                <img src="<?= e(UPLOADS_URL . '/' . $m['filename']) ?>" alt="">
                <div class="actions">
                    <a href="?delete=<?= $m['id'] ?>&token=<?= generate_csrf() ?>"
                       onclick="return confirm('Zmazať?')"
                       style="width:28px;height:28px;border-radius:50%;background:rgba(0,0,0,0.6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;text-decoration:none">×</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p style="margin-top:16px;font-size:0.85rem;color:var(--text-muted)">
            URL obrázkov: <code>/assets/uploads/nazov-suboru.jpg</code> – použite v editore obsahu.
        </p>
        <?php endif; ?>
    </div>
</div>

<?php admin_footer(); ?>
