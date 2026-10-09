<?php
/**
 * Admin – správa blogových článkov
 */
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

// Zmazanie
if (isset($_GET['delete']) && verify_csrf($_GET['token'] ?? '')) {
    $id = (int)$_GET['delete'];
    db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
    set_flash('success', 'Článok bol zmazaný.');
    redirect(ADMIN_URL . '/posts.php');
}

// Hromadné akcie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $action = $_POST['bulk_action'];
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            if ($action === 'publish') {
                db()->prepare("UPDATE posts SET status='published', published_at=COALESCE(published_at, NOW()) WHERE id IN ($placeholders)")->execute($ids);
                set_flash('success', 'Označené články publikované.');
            } elseif ($action === 'draft') {
                db()->prepare("UPDATE posts SET status='draft' WHERE id IN ($placeholders)")->execute($ids);
                set_flash('success', 'Označené články nastavené ako koncept.');
            } elseif ($action === 'delete') {
                db()->prepare("DELETE FROM posts WHERE id IN ($placeholders)")->execute($ids);
                set_flash('success', 'Označené články zmazané.');
            }
        }
    }
    redirect(ADMIN_URL . '/posts.php');
}

// Filtre
$status_filter = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');
$category_filter = (int)($_GET['category'] ?? 0);

$sql = 'SELECT DISTINCT p.* FROM posts p';
$params = [];
if ($category_filter > 0) {
    $sql .= ' INNER JOIN post_categories pc ON p.id = pc.post_id AND pc.category_id = ?';
    $params[] = $category_filter;
}
$sql .= ' WHERE 1=1';
if ($status_filter === 'published' || $status_filter === 'draft') {
    $sql .= ' AND p.status = ?';
    $params[] = $status_filter;
}
if ($q !== '') {
    $sql .= ' AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$sql .= ' ORDER BY p.updated_at DESC';

$posts = [];
$total_all = 0;
$total_pub = 0;
$total_draft = 0;
try {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
    $total_all = (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    $total_pub = (int) db()->query('SELECT COUNT(*) FROM posts WHERE status="published"')->fetchColumn();
    $total_draft = (int) db()->query('SELECT COUNT(*) FROM posts WHERE status="draft"')->fetchColumn();
} catch (Exception $e) {
    set_flash('error', 'Tabuľka posts neexistuje. Importujte aktualizovanú sql/schema.sql.');
}

admin_header('Blog – správa článkov');
?>

<div class="row g-3 mb-4">
    <div class="col-4 col-md-auto">
        <div class="stat-card">
            <div class="stat-value"><?= $total_all ?></div>
            <div class="text-muted small">Celkom</div>
        </div>
    </div>
    <div class="col-4 col-md-auto">
        <div class="stat-card">
            <div class="stat-value"><?= $total_pub ?></div>
            <div class="text-muted small">Publikované</div>
        </div>
    </div>
    <div class="col-4 col-md-auto">
        <div class="stat-card">
            <div class="stat-value"><?= $total_draft ?></div>
            <div class="text-muted small">Koncepty</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Vyhľadávanie</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Názov, text…" value="<?= e($q) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Stav</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Všetky</option>
                    <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Publikované</option>
                    <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Koncepty</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Kategória</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">Všetky</option>
                    <?php foreach (get_all_categories() as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrovať</button>
                <?php if ($q || $status_filter || $category_filter): ?>
                <a href="posts.php" class="btn btn-sm btn-link">Zrušiť</a>
                <?php endif; ?>
            </div>
            <div class="col-md-auto ms-md-auto">
                <a href="post-edit.php" class="btn btn-primary btn-sm">+ Nový článok</a>
            </div>
        </form>
    </div>
</div>

<form method="post" id="bulkForm">
    <?= csrf_field() ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-semibold">Články (<?= count($posts) ?>)</span>
            <div class="d-flex gap-2 align-items-center">
                <select name="bulk_action" class="form-select form-select-sm" style="width:auto">
                    <option value="">Hromadná akcia…</option>
                    <option value="publish">Publikovať</option>
                    <option value="draft">Nastaviť ako koncept</option>
                    <option value="delete">Zmazať</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirmBulk()">Vykonať</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                            <th>Názov</th>
                            <th>URL</th>
                            <th>Stav</th>
                            <th>Publikované</th>
                            <th>Upravené</th>
                            <th style="width:200px">Akcie</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($posts)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Žiadne články. <a href="post-edit.php">Vytvorte prvý článok</a>.</td></tr>
                    <?php else: foreach ($posts as $p): ?>
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="<?= $p['id'] ?>" class="form-check-input row-check"></td>
                            <td>
                                <strong><?= e($p['title']) ?></strong>
                                <?php if ($p['excerpt']): ?>
                                <div class="text-muted small text-truncate" style="max-width:280px"><?= e($p['excerpt']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><code class="small">/blog/<?= e($p['slug']) ?></code></td>
                            <td>
                                <span class="badge text-bg-<?= $p['status'] === 'published' ? 'success' : 'warning' ?>">
                                    <?= $p['status'] === 'published' ? 'Publikované' : 'Koncept' ?>
                                </span>
                            </td>
                            <td class="small"><?= $p['published_at'] ? date('d.m.Y H:i', strtotime($p['published_at'])) : '—' ?></td>
                            <td class="small"><?= date('d.m.Y H:i', strtotime($p['updated_at'])) ?></td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="post-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Upraviť</a>
                                    <?php if ($p['status'] === 'published'): ?>
                                    <a href="/blog/<?= e($p['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Zobraziť</a>
                                    <?php endif; ?>
                                    <a href="?delete=<?= $p['id'] ?>&token=<?= e(generate_csrf()) ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Naozaj zmazať článok «<?= e(addslashes($p['title'])) ?>»?')">Zmazať</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('checkAll')?.addEventListener('change', function() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
});
function confirmBulk() {
    const action = document.querySelector('[name=bulk_action]').value;
    const checked = document.querySelectorAll('.row-check:checked').length;
    if (!action) { alert('Vyberte akciu.'); return false; }
    if (!checked) { alert('Vyberte aspoň jeden článok.'); return false; }
    if (action === 'delete') return confirm('Naozaj zmazať ' + checked + ' článkov?');
    return true;
}
</script>

<?php admin_footer(); ?>
