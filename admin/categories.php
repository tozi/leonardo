<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

// Delete
if (isset($_GET['delete']) && verify_csrf($_GET['token'] ?? '')) {
    $id = (int)$_GET['delete'];
    db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    set_flash('success', 'Kategória zmazaná.');
    redirect(ADMIN_URL . '/categories.php');
}

// Create / update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        set_flash('error', 'Neplatný CSRF token.');
        redirect(ADMIN_URL . '/categories.php');
    }
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: create_slug($name);
    $description = trim($_POST['description'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    // Sanitize lengths
    $name = mb_substr($name, 0, 100);
    $slug = mb_substr(preg_replace('/[^a-z0-9\-]/', '', strtolower($slug)), 0, 100);
    $description = mb_substr($description, 0, 1000);

    if ($name === '') {
        set_flash('error', 'Názov kategórie je povinný.');
    } else {
        if ($id > 0) {
            db()->prepare('UPDATE categories SET name=?, slug=?, description=?, sort_order=? WHERE id=?')
               ->execute([$name, $slug, $description, $sort_order, $id]);
            set_flash('success', 'Kategória uložená.');
        } else {
            // unique slug
            $check = db()->prepare('SELECT id FROM categories WHERE slug = ?');
            $check->execute([$slug]);
            if ($check->fetch()) $slug .= '-' . time();
            db()->prepare('INSERT INTO categories (name, slug, description, sort_order) VALUES (?,?,?,?)')
               ->execute([$name, $slug, $description, $sort_order]);
            set_flash('success', 'Kategória vytvorená.');
        }
    }
    redirect(ADMIN_URL . '/categories.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = get_category((int)$_GET['edit']);
}

$categories = get_all_categories();
admin_header('Kategórie článkov');
?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Zoznam kategórií</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Názov</th><th>Slug</th><th>Články</th><th>Poradie</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Žiadne kategórie</td></tr>
                        <?php else: foreach ($categories as $c): ?>
                            <tr>
                                <td><strong><?= e($c['name']) ?></strong>
                                    <?php if ($c['description']): ?><div class="small text-muted"><?= e(mb_substr($c['description'], 0, 60)) ?></div><?php endif; ?>
                                </td>
                                <td><code><?= e($c['slug']) ?></code></td>
                                <td><?= (int)$c['post_count'] ?></td>
                                <td><?= (int)$c['sort_order'] ?></td>
                                <td class="d-flex gap-1">
                                    <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">Upraviť</a>
                                    <a href="?delete=<?= $c['id'] ?>&token=<?= e(generate_csrf()) ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Zmazať kategóriu? Väzby na články sa odstránia.')">Zmazať</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold"><?= $edit ? 'Upraviť kategóriu' : 'Nová kategória' ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label class="form-label">Názov *</label>
                        <input type="text" name="name" class="form-control" required maxlength="100" value="<?= e($edit['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" maxlength="100" value="<?= e($edit['slug'] ?? '') ?>" pattern="[a-z0-9\-]*">
                        <div class="form-text">Len malé písmená, čísla a pomlčky</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Popis</label>
                        <textarea name="description" class="form-control" rows="2" maxlength="1000"><?= e($edit['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poradie</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= (int)($edit['sort_order'] ?? 0) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><?= $edit ? 'Uložiť' : 'Vytvoriť' ?></button>
                    <?php if ($edit): ?><a href="categories.php" class="btn btn-outline-secondary">Zrušiť</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
