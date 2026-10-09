<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/translations.php';

$tr_fields = [
    'title'   => ['label' => 'Názov článku', 'type' => 'text', 'max' => 255],
    'excerpt' => ['label' => 'Perex', 'type' => 'textarea', 'rows' => 2, 'max' => 2000],
    'content' => ['label' => 'Obsah', 'type' => 'editor'],
];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    $post = $stmt->fetch();
}
$is_new = !$post;
$all_categories = get_all_categories();
$selected_cats = $id ? get_post_category_ids($id) : [];

// Delete attached image
if (isset($_GET['delete_image']) && $id && verify_csrf($_GET['token'] ?? '')) {
    $img_id = (int)$_GET['delete_image'];
    try {
        $stmt = db()->prepare('SELECT * FROM post_images WHERE id = ? AND post_id = ?');
        $stmt->execute([$img_id, $id]);
        $img = $stmt->fetch();
        if ($img) {
            $path = UPLOADS_PATH . '/' . $img['filename'];
            if (is_file($path)) @unlink($path);
            db()->prepare('DELETE FROM post_images WHERE id = ?')->execute([$img_id]);
            set_flash('success', 'Obrázok zmazaný.');
        }
    } catch (Exception $e) {}
    redirect(ADMIN_URL . '/post-edit.php?id=' . $id);
}

// Upload multiple images to post (form, not AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_post_images']) && $id) {
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        set_flash('error', 'Neplatný CSRF token.');
        redirect(ADMIN_URL . '/post-edit.php?id=' . $id);
    }
    $uploaded = 0;
    if (!empty($_FILES['post_images']['name'][0])) {
        $count = count($_FILES['post_images']['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($_FILES['post_images']['tmp_name'][$i])) continue;
            $file = [
                'name' => $_FILES['post_images']['name'][$i],
                'type' => $_FILES['post_images']['type'][$i],
                'tmp_name' => $_FILES['post_images']['tmp_name'][$i],
                'error' => $_FILES['post_images']['error'][$i],
                'size' => $_FILES['post_images']['size'][$i],
            ];
            $up = upload_image($file);
            if ($up['success']) {
                try {
                    $max = db()->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM post_images WHERE post_id = ?');
                    $max->execute([$id]);
                    $order = (int)$max->fetchColumn();
                    db()->prepare('INSERT INTO post_images (post_id, filename, title, sort_order) VALUES (?,?,?,?)')
                       ->execute([$id, $up['filename'], pathinfo($file['name'], PATHINFO_FILENAME), $order]);
                    $uploaded++;
                } catch (Exception $e) {
                    set_flash('error', 'Tabuľka post_images chýba – importujte schema.sql');
                    break;
                }
            }
        }
    }
    if ($uploaded) set_flash('success', "Nahraných $uploaded obrázkov.");
    redirect(ADMIN_URL . '/post-edit.php?id=' . $id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['upload_post_images'])) {
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        set_flash('error', 'Neplatný CSRF token.');
        redirect(ADMIN_URL . '/posts.php');
    }
    if (honeypot_failed()) {
        set_flash('error', 'Neplatná požiadavka.');
        redirect(ADMIN_URL . '/posts.php');
    }

    $title = mb_substr(trim($_POST['title'] ?? ''), 0, 255);
    $slug = trim($_POST['slug'] ?? '') ?: create_slug($title);
    $slug = mb_substr(preg_replace('/[^a-z0-9\-]/', '', strtolower($slug)), 0, 255);
    $excerpt = mb_substr(trim($_POST['excerpt'] ?? ''), 0, 2000);
    $content = sanitize_html($_POST['content'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'draft';
    $cat_ids = array_map('intval', $_POST['categories'] ?? []);
    $featured_image = $post['featured_image'] ?? null;

    $published_at = null;
    if ($status === 'published') {
        $published_at = !empty($_POST['published_at'])
            ? date('Y-m-d H:i:s', strtotime($_POST['published_at']))
            : ($post['published_at'] ?? date('Y-m-d H:i:s'));
    }

    if (!empty($_FILES['featured_image']['name'])) {
        $up = upload_image($_FILES['featured_image']);
        if ($up['success']) $featured_image = $up['filename'];
        else set_flash('error', $up['error'] ?? 'Chyba nahrávania.');
    }
    if (!empty($_POST['remove_image'])) $featured_image = null;

    if ($title === '') {
        set_flash('error', 'Názov článku je povinný.');
    } else {
        $check = db()->prepare('SELECT id FROM posts WHERE slug = ? AND id != ?');
        $check->execute([$slug, $id ?: 0]);
        if ($check->fetch()) $slug .= '-' . time();

        if ($is_new) {
            db()->prepare('INSERT INTO posts (title, slug, excerpt, content, featured_image, status, published_at) VALUES (?,?,?,?,?,?,?)')
               ->execute([$title, $slug, $excerpt, $content, $featured_image, $status, $published_at]);
            $newId = (int) db()->lastInsertId();
            set_post_categories($newId, $cat_ids);
            save_posted_translations('post', $newId, $tr_fields);
            set_flash('success', 'Článok bol vytvorený. Teraz môžete nahrať ďalšie obrázky.');
            redirect(ADMIN_URL . '/post-edit.php?id=' . $newId);
        } else {
            db()->prepare('UPDATE posts SET title=?, slug=?, excerpt=?, content=?, featured_image=?, status=?, published_at=? WHERE id=?')
               ->execute([$title, $slug, $excerpt, $content, $featured_image, $status, $published_at, $id]);
            set_post_categories($id, $cat_ids);
            save_posted_translations('post', $id, $tr_fields);
            set_flash('success', 'Článok bol uložený.');
            redirect(ADMIN_URL . '/post-edit.php?id=' . $id);
        }
    }
    $selected_cats = $cat_ids;
    $post = array_merge($post ?: [], compact('title', 'slug', 'excerpt', 'content', 'status', 'featured_image'));
}

$post_images = [];
if ($id) {
    try {
        $stmt = db()->prepare('SELECT * FROM post_images WHERE post_id = ? ORDER BY sort_order, id');
        $stmt->execute([$id]);
        $post_images = $stmt->fetchAll();
    } catch (Exception $e) {}
}

$csrf = generate_csrf();
admin_header($is_new ? 'Nový článok' : 'Upraviť: ' . ($post['title'] ?? ''));
?>

<form method="post" enctype="multipart/form-data" id="postForm">
    <?= csrf_field() ?>
    <?= honeypot_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Názov článku *</label>
                        <input type="text" name="title" id="postTitle" class="form-control form-control-lg" required maxlength="255"
                               value="<?= e($post['title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug (URL)</label>
                        <div class="input-group">
                            <span class="input-group-text">/blog/</span>
                            <input type="text" name="slug" id="postSlug" class="form-control" maxlength="255" value="<?= e($post['slug'] ?? '') ?>" pattern="[a-z0-9\-]*">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Perex</label>
                        <textarea name="excerpt" class="form-control" rows="2" maxlength="2000"><?= e($post['excerpt'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Obsah</label>
                        <p class="form-text small mb-1">Obrázok do textu: tlačidlo obrázka v editore alebo ho presuňte/vložte do textu (nahrá sa na server).</p>
                        <textarea name="content" id="contentEditor" data-rw-editor><?= e($post['content'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <?php if ($id): ?>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Obrázky článku (<?= count($post_images) ?>)</div>
                <div class="card-body">
                    <p class="text-muted small">Ďalšie fotky pripojené k článku (galéria pod obsahom na webe). Do textu vložte obrázok cez editor vyššie.</p>

                    <?php if ($post_images): ?>
                    <div class="gallery-admin-grid mb-3">
                        <?php foreach ($post_images as $img): ?>
                        <div class="gallery-admin-item" title="<?= e($img['title'] ?? '') ?>">
                            <img src="<?= e(UPLOADS_URL . '/' . $img['filename']) ?>" alt="">
                            <div class="actions">
                                <a href="?id=<?= $id ?>&delete_image=<?= $img['id'] ?>&token=<?= e($csrf) ?>"
                                   onclick="return confirm('Zmazať tento obrázok?')"
                                   class="btn btn-sm btn-danger rounded-circle p-0 d-flex align-items-center justify-content-center"
                                   style="width:28px;height:28px;font-size:14px;text-decoration:none">×</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted small">Zatiaľ žiadne priložené obrázky.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info small">Po uložení článku budete môcť nahrať viacero obrázkov do galérie článku.</div>
            <?php endif; ?>

            <?php render_translations_card('post', $id, $tr_fields, 'pstr'); ?>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Publikovanie</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Stav</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Koncept</option>
                            <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publikované</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dátum publikovania</label>
                        <input type="datetime-local" name="published_at" class="form-control"
                               value="<?= !empty($post['published_at']) ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : date('Y-m-d\TH:i') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-2"><?= $is_new ? 'Vytvoriť článok' : 'Uložiť zmeny' ?></button>
                    <a href="posts.php" class="btn btn-outline-secondary w-100">Späť na zoznam</a>
                    <?php if (!$is_new && ($post['status'] ?? '') === 'published'): ?>
                    <a href="/blog/<?= e($post['slug']) ?>" target="_blank" class="btn btn-outline-primary w-100 mt-2">Zobraziť na webe ↗</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Kategórie</span>
                    <a href="categories.php" class="btn btn-sm btn-link p-0">Spravovať</a>
                </div>
                <div class="card-body">
                    <?php if (empty($all_categories)): ?>
                        <p class="text-muted small mb-0">Žiadne kategórie. <a href="categories.php">Vytvoriť</a></p>
                    <?php else: foreach ($all_categories as $c): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="categories[]" value="<?= $c['id'] ?>"
                                   id="cat<?= $c['id'] ?>" <?= in_array($c['id'], $selected_cats) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="cat<?= $c['id'] ?>"><?= e($c['name']) ?></label>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Hlavný obrázok (náhľad)</div>
                <div class="card-body">
                    <?php if (!empty($post['featured_image'])): ?>
                    <img src="<?= e(UPLOADS_URL . '/' . $post['featured_image']) ?>" class="img-fluid rounded mb-2" alt="">
                    <div class="form-check mb-2">
                        <input type="checkbox" name="remove_image" value="1" class="form-check-input" id="removeImg">
                        <label class="form-check-label small" for="removeImg">Odstrániť</label>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="featured_image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">Zobrazí sa v zozname blogu a v hlavičke článku</div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if ($id): ?>
<form method="post" enctype="multipart/form-data" class="mt-2">
    <?= csrf_field() ?>
    <input type="hidden" name="upload_post_images" value="1">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Nahrať obrázky do galérie článku</div>
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <div class="flex-grow-1">
                <input type="file" name="post_images[]" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                <div class="form-text">Môžete vybrať viac súborov naraz (max 5 MB / súbor)</div>
            </div>
            <button type="submit" class="btn btn-primary">Nahrať</button>
        </div>
    </div>
</form>
<?php endif; ?>

<?php render_quill_assets($csrf, $id); ?>
<script>
document.getElementById('postTitle')?.addEventListener('input', function() {
    const s = document.getElementById('postSlug');
    if (!s.dataset.manual) {
        s.value = this.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'')
            .replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-|-$/g,'');
    }
});
document.getElementById('postSlug')?.addEventListener('input', function(){ this.dataset.manual='1'; });
</script>
<?php admin_footer(); ?>
