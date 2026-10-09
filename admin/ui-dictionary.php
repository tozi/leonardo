<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ui'])) {
    if (verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $langs = get_active_languages();

        foreach ($_POST['translation'] ?? [] as $dict_id => $values) {
            $dict_id = (int)$dict_id;
            if ($dict_id <= 0) continue;

            foreach ($langs as $lang) {
                $code = $lang['code'];
                $value = trim((string)($values[$code] ?? ''));
                if ($value === '') {
                    db()->prepare('DELETE FROM ui_dictionary_translations WHERE dict_id = ? AND lang_code = ?')->execute([$dict_id, $code]);
                } else {
                    db()->prepare('INSERT INTO ui_dictionary_translations (dict_id, lang_code, translated_text)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE translated_text = VALUES(translated_text)')->execute([$dict_id, $code, $value]);
                }
            }
        }

        set_flash('success', 'Preklady UI textov boli uložené.');
    } else {
        set_flash('error', 'Neplatný token formulára.');
    }
    redirect(ADMIN_URL . '/ui-dictionary.php');
}

$rows = db()->query('SELECT * FROM ui_dictionary ORDER BY category, sort_order, dict_key')->fetchAll();
$langs = get_active_languages();
$translations = [];
if ($rows) {
    $ids = array_map(fn($r) => (int)$r['id'], $rows);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare('SELECT dict_id, lang_code, translated_text FROM ui_dictionary_translations WHERE dict_id IN (' . $in . ')');
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $tr) {
        $translations[(int)$tr['dict_id']][$tr['lang_code']] = $tr['translated_text'];
    }
}

$csrf = generate_csrf();
admin_header('UI texty');
?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>UI slovník</span>
        <span class="small text-muted"><?= count($rows) ?> položiek</span>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">Tu upravujete pevné texty, tlačidlá a správy, ktoré sa prekladajú podľa aktívneho jazyka. Kľúč zostáva konštantný, zmení sa iba text v danom jazyku.</p>

        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="save_ui" value="1">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th style="min-width:220px;">Kľúč</th>
                        <th style="min-width:180px;">Predvolený text</th>
                        <?php foreach ($langs as $lang): ?>
                        <th style="min-width:180px;"><?= e($lang['name']) ?> (<?= e($lang['code']) ?>)</th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><code><?= e($row['dict_key']) ?></code></td>
                            <td class="text-muted small"><?= e($row['default_text']) ?></td>
                            <?php foreach ($langs as $lang): ?>
                            <td>
                                <input type="text"
                                    class="form-control form-control-sm"
                                    name="translation[<?= (int)$row['id'] ?>][<?= e($lang['code']) ?>]"
                                    value="<?= e($translations[(int)$row['id']][$lang['code']] ?? '') ?>"
                                    placeholder="<?= e($row['default_text']) ?>">
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">Uložiť preklady</button>
            </div>
        </form>
    </div>
</div>

<?php admin_footer(); ?>
