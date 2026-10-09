<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$gallery = $id ? get_gallery($id) : null;
$is_new = !$gallery;

// Save gallery meta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gallery'])) {
    if (verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '') ?: create_slug($title);
        $description = trim($_POST['description'] ?? '');
        $page_id = !empty($_POST['page_id']) ? (int)$_POST['page_id'] : null;

        if ($title) {
            if ($is_new) {
                db()->prepare('INSERT INTO galleries (title, slug, description, page_id) VALUES (?,?,?,?)')
                   ->execute([$title, $slug, $description, $page_id]);
                $id = db()->lastInsertId();
                set_flash('success', 'Galéria vytvorená.');
                redirect(ADMIN_URL . '/gallery-edit.php?id=' . $id);
            } else {
                db()->prepare('UPDATE galleries SET title=?, slug=?, description=?, page_id=? WHERE id=?')
                   ->execute([$title, $slug, $description, $page_id, $id]);
                set_flash('success', 'Galéria uložená.');
                redirect(ADMIN_URL . '/gallery-edit.php?id=' . $id);
            }
        }
    }
}

// Upload images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_images']) && $id) {
    if (verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $uploaded = 0;
        if (!empty($_FILES['images']['name'][0])) {
            $count = count($_FILES['images']['name']);
            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i],
                ];
                $up = upload_image($file);
                if ($up['success']) {
                    db()->prepare('INSERT INTO gallery_images (gallery_id, filename, title, sort_order) VALUES (?,?,?,?)')
                       ->execute([$id, $up['filename'], pathinfo($file['name'], PATHINFO_FILENAME), $i]);
                    $uploaded++;
                }
            }
        }
        set_flash('success', "Nahraných $uploaded obrázkov.");
        redirect(ADMIN_URL . '/gallery-edit.php?id=' . $id);
    }
}

// Delete image
if (isset($_GET['delete_image']) && $id && verify_csrf($_GET['token'] ?? '')) {
    $img_id = (int)$_GET['delete_image'];
    $img = db()->prepare('SELECT * FROM gallery_images WHERE id = ? AND gallery_id = ?');
    $img->execute([$img_id, $id]);
    $img = $img->fetch();
    if ($img) {
        $path = UPLOADS_PATH . '/' . $img['filename'];
        if (file_exists($path)) unlink($path);
        db()->prepare('DELETE FROM gallery_images WHERE id = ?')->execute([$img_id]);
        set_flash('success', 'Obrázok zmazaný.');
    }
    redirect(ADMIN_URL . '/gallery-edit.php?id=' . $id);
}

$images = $id ? get_gallery_images($id) : [];
$pages = get_all_pages();

admin_header($is_new ? 'Nová galéria' : 'Galéria: ' . ($gallery['title'] ?? ''));
?>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="save_gallery" value="1">
    <div class="card">
        <div class="card-header"><h2>Informácie o galérii</h2></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Názov *</label>
                    <input type="text" name="title" required value="<?= e($gallery['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= e($gallery['slug'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Popis</label>
                <textarea name="description" rows="2"><?= e($gallery['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Prepojiť so stránkou</label>
                <select name="page_id">
                    <option value="">— Žiadna —</option>
                    <?php foreach ($pages as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($gallery['page_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['title']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-hint">Ak stránka používa šablónu „Galéria“, zobrazia sa tieto obrázky.</div>
            </div>
            <button type="submit" class="btn btn-primary">Uložiť</button>
            <a href="galleries.php" class="btn btn-secondary">Späť</a>
        </div>
    </div>
</form>

<?php if ($id): ?>
<div class="card">
    <div class="card-header"><h2>Obrázky (<?= count($images) ?>)</h2></div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" style="margin-bottom:20px">
            <?= csrf_field() ?>
            <input type="hidden" name="upload_images" value="1">
            <div class="form-group">
                <label>Nahrať obrázky (viac naraz)</label>
                <input type="file" name="images[]" accept="image/*" multiple>
            </div>
            <button type="submit" class="btn btn-primary">Nahrať</button>
        </form>

        <?php if (!empty($images)): ?>
        <div class="gallery-admin-grid">
            <?php foreach ($images as $img): ?>
            <div class="gallery-admin-item">
                <img src="<?= e(UPLOADS_URL . '/' . $img['filename']) ?>" alt="">
                <div class="actions">
                    <a href="?id=<?= $id ?>&delete_image=<?= $img['id'] ?>&token=<?= generate_csrf() ?>"
                       onclick="return confirm('Zmazať obrázok?')"
                       style="width:28px;height:28px;border-radius:50%;background:rgba(0,0,0,0.6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.75rem;text-decoration:none">×</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color:var(--text-muted)">Zatiaľ žiadne obrázky. Nahrajte ich vyššie.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php admin_footer(); ?>
