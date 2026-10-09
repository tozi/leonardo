<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

$menu_id = (int)($_GET['id'] ?? 0);
$menu = db()->prepare('SELECT * FROM menus WHERE id = ?');
$menu->execute([$menu_id]);
$menu = $menu->fetch();
if (!$menu) {
    set_flash('error', 'Menu neexistuje.');
    redirect(ADMIN_URL . '/menus.php');
}

// AJAX reorder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorder'])) {
    header('Content-Type: application/json');
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        echo json_encode(['ok' => false]);
        exit;
    }
    $order = json_decode($_POST['order'] ?? '[]', true);
    if (is_array($order)) {
        $stmt = db()->prepare('UPDATE menu_items SET sort_order = ? WHERE id = ? AND menu_id = ?');
        foreach ($order as $i => $item_id) {
            $stmt->execute([$i, (int)$item_id, $menu_id]);
        }
    }
    echo json_encode(['ok' => true]);
    exit;
}

$tr_langs = get_translation_langs();

/** Uloží preklady názvu položky menu z tr[<jazyk>] */
function save_menu_item_translations($item_id, $tr_langs) {
    $posted = $_POST['tr'] ?? [];
    if (!is_array($posted)) return;
    foreach ($tr_langs as $l) {
        $v = isset($posted[$l['code']]) && is_string($posted[$l['code']]) ? mb_substr(trim($posted[$l['code']]), 0, 255) : '';
        save_translations('menu_item', (int)$item_id, $l['code'], ['title' => $v]);
    }
}

// Add item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    if (verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $title = trim($_POST['title'] ?? '');
        $page_id = !empty($_POST['page_id']) ? (int)$_POST['page_id'] : null;
        $url = trim($_POST['url'] ?? '') ?: null;
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $target = $_POST['target'] ?? '_self';
        if ($title) {
            db()->prepare('INSERT INTO menu_items (menu_id, title, url, page_id, target, sort_order) VALUES (?,?,?,?,?,?)')
               ->execute([$menu_id, $title, $url, $page_id, $target, $sort_order]);
            save_menu_item_translations(db()->lastInsertId(), $tr_langs);
            set_flash('success', 'Položka pridaná.');
        }
    }
    redirect(ADMIN_URL . '/menu-edit.php?id=' . $menu_id);
}

// Update item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    if (verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $item_id = (int)$_POST['item_id'];
        $title = trim($_POST['title'] ?? '');
        $page_id = !empty($_POST['page_id']) ? (int)$_POST['page_id'] : null;
        $url = trim($_POST['url'] ?? '') ?: null;
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        db()->prepare('UPDATE menu_items SET title=?, page_id=?, url=?, sort_order=?, is_active=? WHERE id=? AND menu_id=?')
           ->execute([$title, $page_id, $url, $sort_order, $is_active, $item_id, $menu_id]);
        $owned = db()->prepare('SELECT id FROM menu_items WHERE id = ? AND menu_id = ?');
        $owned->execute([$item_id, $menu_id]);
        if ($owned->fetch()) save_menu_item_translations($item_id, $tr_langs);
        set_flash('success', 'Položka aktualizovaná.');
    }
    redirect(ADMIN_URL . '/menu-edit.php?id=' . $menu_id);
}

// Delete item
if (isset($_GET['delete_item']) && verify_csrf($_GET['token'] ?? '')) {
    db()->prepare('DELETE FROM menu_items WHERE id = ? AND menu_id = ?')->execute([(int)$_GET['delete_item'], $menu_id]);
    set_flash('success', 'Položka zmazaná.');
    redirect(ADMIN_URL . '/menu-edit.php?id=' . $menu_id);
}

$items = db()->prepare('SELECT mi.*, p.title as page_title FROM menu_items mi LEFT JOIN pages p ON mi.page_id = p.id WHERE mi.menu_id = ? ORDER BY mi.sort_order, mi.title');
$items->execute([$menu_id]);
$items = $items->fetchAll();
foreach ($items as &$it) {
    $it['tr'] = [];
    foreach ($tr_langs as $l) {
        $it['tr'][$l['code']] = get_translations('menu_item', $it['id'], $l['code'])['title'] ?? '';
    }
}
unset($it);
$pages = get_all_pages('published');
$csrf = generate_csrf();

admin_header('Menu: ' . $menu['name']);
?>

<p class="mb-3"><a href="menus.php" class="text-decoration-none">&larr; Späť na menu</a></p>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Položky menu</span>
                <span class="badge text-bg-secondary">Presuňte myšou pre zmenu poradia</span>
            </div>
            <div class="card-body">
                <?php if (empty($items)): ?>
                    <p class="text-muted mb-0">Zatiaľ žiadne položky.</p>
                <?php else: ?>
                <ul class="sortable-list" id="menuSortable">
                    <?php foreach ($items as $item): ?>
                    <li data-id="<?= $item['id'] ?>">
                        <div class="d-flex align-items-center">
                            <span class="handle"><i class="bi bi-grip-vertical"></i></span>
                            <div>
                                <strong><?= e($item['title']) ?></strong>
                                <span class="text-muted small ms-1">
                                    <?php if ($item['page_title']): ?>→ <?= e($item['page_title']) ?>
                                    <?php elseif ($item['url']): ?>→ <?= e($item['url']) ?><?php endif; ?>
                                    <?php if (!$item['is_active']): ?><span class="badge text-bg-warning ms-1">neaktívne</span><?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick='editItem(<?= json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Upraviť</button>
                            <a href="?id=<?= $menu_id ?>&delete_item=<?= $item['id'] ?>&token=<?= e($csrf) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Zmazať?')">&times;</a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div id="sortStatus" class="form-text mt-2"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" id="addForm">
            <div class="card-header bg-white fw-semibold" id="formTitle">Pridať položku</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="add_item" id="formAction" value="1">
                    <input type="hidden" name="item_id" id="itemId" value="">
                    <input type="hidden" name="update_item" id="updateFlag" value="">
                    <div class="mb-3">
                        <label class="form-label">Názov *</label>
                        <input type="text" name="title" id="itemTitle" class="form-control" required>
                    </div>
                    <?php foreach ($tr_langs as $l): ?>
                    <div class="mb-3">
                        <label class="form-label">Názov – <?= e(strtoupper($l['code'])) ?> (<?= e($l['name']) ?>)</label>
                        <input type="text" name="tr[<?= e($l['code']) ?>]" id="itemTr_<?= e($l['code']) ?>" class="form-control" maxlength="255" placeholder="Nepovinné – ak je prázdne, použije sa základný názov">
                    </div>
                    <?php endforeach; ?>
                    <div class="mb-3">
                        <label class="form-label">Stránka</label>
                        <select name="page_id" id="itemPage" class="form-select">
                            <option value="">— Vlastný odkaz —</option>
                            <?php foreach ($pages as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vlastná URL</label>
                        <input type="text" name="url" id="itemUrl" class="form-control" placeholder="https://...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Poradie</label>
                        <input type="number" name="sort_order" id="itemOrder" class="form-control" value="0">
                    </div>
                    <div class="mb-3 form-check" id="activeGroup" style="display:none">
                        <input type="checkbox" name="is_active" id="itemActive" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="itemActive">Aktívne</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target</label>
                        <select name="target" id="itemTarget" class="form-select">
                            <option value="_self">Rovnaké okno</option>
                            <option value="_blank">Nové okno</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Pridať</button>
                    <button type="button" class="btn btn-outline-secondary" id="cancelBtn" style="display:none" onclick="resetForm()">Zrušiť</button>
                </form>
            </div>
        </div>
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Použitie v šablóne</div>
            <div class="card-body">
                <code>&lt;?= render_menu('<?= e($menu['slug']) ?>') ?&gt;</code>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const csrfToken = <?= json_encode($csrf) ?>;
const menuId = <?= (int)$menu_id ?>;

const el = document.getElementById('menuSortable');
if (el) {
    Sortable.create(el, {
        handle: '.handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onEnd: function() {
            const order = [...el.querySelectorAll('li')].map(li => li.dataset.id);
            const fd = new FormData();
            fd.append('reorder', '1');
            fd.append('<?= CSRF_TOKEN_NAME ?>', csrfToken);
            fd.append('order', JSON.stringify(order));
            fetch('?id=' + menuId, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    const s = document.getElementById('sortStatus');
                    s.textContent = d.ok ? 'Poradie uložené ✓' : 'Chyba pri ukladaní';
                    s.className = 'form-text mt-2 ' + (d.ok ? 'text-success' : 'text-danger');
                    setTimeout(() => s.textContent = '', 2000);
                });
        }
    });
}

function editItem(item) {
    document.getElementById('formTitle').textContent = 'Upraviť položku';
    document.getElementById('formAction').name = '';
    document.getElementById('updateFlag').name = 'update_item';
    document.getElementById('updateFlag').value = '1';
    document.getElementById('itemId').value = item.id;
    document.getElementById('itemTitle').value = item.title;
    document.getElementById('itemPage').value = item.page_id || '';
    document.getElementById('itemUrl').value = item.url || '';
    document.getElementById('itemOrder').value = item.sort_order;
    document.getElementById('itemTarget').value = item.target || '_self';
    document.getElementById('itemActive').checked = item.is_active == 1;
    Object.keys(item.tr || {}).forEach(function (c) {
        var f = document.getElementById('itemTr_' + c);
        if (f) f.value = item.tr[c] || '';
    });
    document.getElementById('activeGroup').style.display = 'block';
    document.getElementById('submitBtn').textContent = 'Uložiť';
    document.getElementById('cancelBtn').style.display = 'inline-block';
}
function resetForm() {
    document.getElementById('formTitle').textContent = 'Pridať položku';
    document.getElementById('formAction').name = 'add_item';
    document.getElementById('updateFlag').name = '';
    document.getElementById('updateFlag').value = '';
    document.getElementById('itemId').value = '';
    document.getElementById('itemTitle').value = '';
    document.getElementById('itemPage').value = '';
    document.getElementById('itemUrl').value = '';
    document.getElementById('itemOrder').value = '0';
    document.querySelectorAll('[id^="itemTr_"]').forEach(function (f) { f.value = ''; });
    document.getElementById('activeGroup').style.display = 'none';
    document.getElementById('submitBtn').textContent = 'Pridať';
    document.getElementById('cancelBtn').style.display = 'none';
}
</script>

<?php admin_footer(); ?>
