<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/translations.php';

$tr_fields = [
    'title'            => ['label' => 'Názov stránky', 'type' => 'text', 'max' => 255],
    'content'          => ['label' => 'Obsah', 'type' => 'editor'],
    'meta_title'       => ['label' => 'Meta title', 'type' => 'text', 'max' => 255],
    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'rows' => 2, 'max' => 1000],
];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page = $id ? get_page($id) : null;
$is_new = !$page;
$templates = get_all_templates();
$all_pages = get_all_pages();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        set_flash('error', 'Neplatný CSRF token.');
        redirect(ADMIN_URL . '/pages.php');
    }
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: create_slug($title);
    $content = sanitize_html($_POST['content'] ?? '');
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $template_id = (int)($_POST['template_id'] ?? 2);
    $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft';
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $featured_image = $page['featured_image'] ?? null;

    if (!empty($_FILES['featured_image']['name'])) {
        $up = upload_image($_FILES['featured_image']);
        if ($up['success']) $featured_image = $up['filename'];
    }

    if (!$title) {
        set_flash('error', 'Názov je povinný.');
    } else {
        if ($is_new) {
            $stmt = db()->prepare('INSERT INTO pages (parent_id, title, slug, content, meta_title, meta_description, template_id, status, sort_order, featured_image) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$parent_id, $title, $slug, $content, $meta_title, $meta_description, $template_id, $status, $sort_order, $featured_image]);
            $new_id = (int) db()->lastInsertId();
            save_posted_translations('page', $new_id, $tr_fields);
            set_flash('success', 'Stránka bola vytvorená.');
            redirect(ADMIN_URL . '/page-edit.php?id=' . $new_id);
        } else {
            $stmt = db()->prepare('UPDATE pages SET parent_id=?, title=?, slug=?, content=?, meta_title=?, meta_description=?, template_id=?, status=?, sort_order=?, featured_image=? WHERE id=?');
            $stmt->execute([$parent_id, $title, $slug, $content, $meta_title, $meta_description, $template_id, $status, $sort_order, $featured_image, $id]);
            save_posted_translations('page', $id, $tr_fields);
            set_flash('success', 'Stránka bola uložená.');
            redirect(ADMIN_URL . '/page-edit.php?id=' . $id);
        }
    }
}

admin_header($is_new ? 'Nová stránka' : 'Upraviť: ' . ($page['title'] ?? ''));
?>

<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?= honeypot_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Názov stránky *</label>
                        <input type="text" name="title" class="form-control" required value="<?= e($page['title'] ?? '') ?>" id="pageTitle">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug (URL)</label>
                        <input type="text" name="slug" class="form-control" value="<?= e($page['slug'] ?? '') ?>" id="pageSlug">
                        <div class="form-text">Nechajte prázdne pre automatické generovanie</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Obsah</label>
                        <textarea name="content" id="contentEditor" data-rw-editor><?= e($page['content'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <?php render_translations_card('page', $id, $tr_fields, 'pgtr'); ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Publikovanie</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Stav</label>
                        <select name="status" class="form-select">
                            <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Koncept</option>
                            <option value="published" <?= ($page['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Publikované</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Šablóna</label>
                        <select name="template_id" class="form-select">
                            <?php foreach ($templates as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= ($page['template_id'] ?? 2) == $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nadradená stránka</label>
                        <select name="parent_id" class="form-select">
                            <option value="">— Žiadna —</option>
                            <?php foreach ($all_pages as $p): if ($p['id'] != $id): ?>
                            <option value="<?= $p['id'] ?>" <?= ($page['parent_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poradie</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= (int)($page['sort_order'] ?? 0) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Uložiť</button>
                    <a href="pages.php" class="btn btn-outline-secondary w-100 mt-2">Zrušiť</a>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">SEO</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Meta title</label>
                        <input type="text" name="meta_title" class="form-control" value="<?= e($page['meta_title'] ?? '') ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Meta description</label>
                        <textarea name="meta_description" class="form-control" rows="3"><?= e($page['meta_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Náhľadový obrázok</div>
                <div class="card-body">
                    <?php if (!empty($page['featured_image'])): ?>
                        <img src="<?= e(UPLOADS_URL . '/' . $page['featured_image']) ?>" class="img-fluid rounded mb-2">
                    <?php endif; ?>
                    <input type="file" name="featured_image" class="form-control" accept="image/*">
                </div>
            </div>
        </div>
    </div>
</form>

<?php render_quill_assets(generate_csrf(), 0); ?>
<script>
document.getElementById('pageTitle')?.addEventListener('input', function() {
    const slugField = document.getElementById('pageSlug');
    if (!slugField.dataset.manual) {
        slugField.value = this.value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '-').replace(/^-|-$/g, '');
    }
});
document.getElementById('pageSlug')?.addEventListener('input', function() { this.dataset.manual = '1'; });
</script>

<?php admin_footer(); ?>
