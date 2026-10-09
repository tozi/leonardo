<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

$default = get_default_lang();

// Pridať jazyk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lang'])) {
    if (verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $code = strtolower(trim($_POST['code'] ?? ''));
        $name = mb_substr(trim($_POST['name'] ?? ''), 0, 50);
        if (!preg_match('/^[a-z]{2,3}(-[a-z]{2})?$/', $code) || strlen($code) > 5 || $name === '') {
            set_flash('error', 'Zadajte kód jazyka (napr. de, cs, hu, pt-br) a názov.');
        } else {
            $st = db()->prepare('SELECT id FROM languages WHERE code = ?');
            $st->execute([$code]);
            if ($st->fetch()) {
                set_flash('error', 'Jazyk s týmto kódom už existuje.');
            } else {
                db()->prepare('INSERT INTO languages (code, name, is_default, is_active) VALUES (?, ?, 0, 1)')->execute([$code, $name]);
                set_flash('success', 'Jazyk bol pridaný. Preklady upravíte pri stránkach, článkoch a menu.');
            }
        }
    }
    redirect(ADMIN_URL . '/languages.php');
}

// Premenovať / zapnúť / vypnúť
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lang'])) {
    if (verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $lid = (int)($_POST['lang_id'] ?? 0);
        $name = mb_substr(trim($_POST['name'] ?? ''), 0, 50);
        $st = db()->prepare('SELECT * FROM languages WHERE id = ?');
        $st->execute([$lid]);
        $lang = $st->fetch();
        if ($lang && $name !== '') {
            $active = ($lang['code'] === $default) ? 1 : (isset($_POST['is_active']) ? 1 : 0);
            db()->prepare('UPDATE languages SET name = ?, is_active = ? WHERE id = ?')->execute([$name, $active, $lid]);
            set_flash('success', 'Jazyk uložený.');
        }
    }
    redirect(ADMIN_URL . '/languages.php');
}

// Zmazať jazyk (aj jeho preklady)
if (isset($_GET['delete']) && verify_csrf($_GET['token'] ?? '')) {
    $st = db()->prepare('SELECT * FROM languages WHERE id = ?');
    $st->execute([(int)$_GET['delete']]);
    $lang = $st->fetch();
    if ($lang && $lang['code'] !== $default) {
        db()->prepare('DELETE FROM translations WHERE lang_code = ?')->execute([$lang['code']]);
        db()->prepare('DELETE FROM languages WHERE id = ?')->execute([$lang['id']]);
        set_flash('success', 'Jazyk a jeho preklady boli zmazané.');
    }
    redirect(ADMIN_URL . '/languages.php');
}

$rows = db()->query('SELECT l.*, (SELECT COUNT(*) FROM translations t WHERE t.lang_code = l.code) AS tr_count FROM languages l ORDER BY is_default DESC, name')->fetchAll();
$csrf = generate_csrf();
admin_header('Jazyky');
?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Jazyky webu</div>
            <div class="card-body">
                <p class="text-muted small">Základný jazyk (<?= e(lang_name($default)) ?>) sa upravuje priamo v stránkach a článkoch. Ostatné jazyky majú v editore vlastné záložky s prekladmi.</p>
                <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Kód</th><th>Názov</th><th>Prekladov</th><th>Aktívny</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): $is_def = $r['code'] === $default; $fid = 'lf' . (int)$r['id']; ?>
                    <tr>
                        <td><code><?= e($r['code']) ?></code><?= $is_def ? ' <span class="badge text-bg-primary">základný</span>' : '' ?></td>
                        <td><input form="<?= $fid ?>" type="text" name="name" class="form-control form-control-sm" value="<?= e($r['name']) ?>" maxlength="50" required></td>
                        <td><?= $is_def ? '—' : (int)$r['tr_count'] ?></td>
                        <td>
                            <?php if ($is_def): ?>
                                <input type="checkbox" class="form-check-input" checked disabled>
                            <?php else: ?>
                                <input form="<?= $fid ?>" type="checkbox" name="is_active" value="1" class="form-check-input" <?= $r['is_active'] ? 'checked' : '' ?>>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <button form="<?= $fid ?>" type="submit" class="btn btn-sm btn-outline-primary">Uložiť</button>
                            <?php if (!$is_def): ?>
                            <a href="?delete=<?= (int)$r['id'] ?>&token=<?= e($csrf) ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Zmazať jazyk a všetky jeho preklady?')">&times;</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php foreach ($rows as $r): ?>
                <form method="post" id="lf<?= (int)$r['id'] ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="update_lang" value="1">
                    <input type="hidden" name="lang_id" value="<?= (int)$r['id'] ?>">
                </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Pridať jazyk</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="add_lang" value="1">
                    <div class="mb-3">
                        <label class="form-label">Kód jazyka *</label>
                        <input type="text" name="code" class="form-control" maxlength="5" placeholder="de, cs, hu, pt-br" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Názov *</label>
                        <input type="text" name="name" class="form-control" maxlength="50" placeholder="Deutsch" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Pridať</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
