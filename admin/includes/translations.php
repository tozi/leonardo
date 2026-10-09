<?php
/**
 * Admin – úprava prekladov (záložky pre každý aktívny nepredvolený jazyk)
 *
 * $fields = [
 *   'title'   => ['label' => 'Názov', 'type' => 'text', 'max' => 255],
 *   'content' => ['label' => 'Obsah', 'type' => 'editor'],
 *   'excerpt' => ['label' => 'Perex', 'type' => 'textarea', 'rows' => 2, 'max' => 2000],
 * ]
 * Polia sa odosielajú ako tr[<jazyk>][<pole>]. Prázdne pole = použije sa pôvodný text.
 */

/** Uloží preklady z $_POST['tr'] pre danú entitu. */
function save_posted_translations($type, $entity_id, array $fields) {
    $allowed = array_column(get_translation_langs(), 'code');
    foreach (($_POST['tr'] ?? []) as $lang => $vals) {
        if (!in_array($lang, $allowed, true) || !is_array($vals)) continue;
        $clean = [];
        foreach ($fields as $name => $spec) {
            $v = isset($vals[$name]) && is_string($vals[$name]) ? $vals[$name] : '';
            if (($spec['type'] ?? 'text') === 'editor') {
                $v = sanitize_html($v);
                // prázdny Quill dokument
                if (trim(strip_tags($v, '<img><iframe>')) === '') $v = '';
            } else {
                $v = trim($v);
                if (!empty($spec['max'])) $v = mb_substr($v, 0, (int)$spec['max']);
            }
            $clean[$name] = $v;
        }
        save_translations($type, (int)$entity_id, $lang, $clean);
    }
}

/** Vykreslí kartu "Preklady" so záložkami jazykov. */
function render_translations_card($type, $entity_id, array $fields, $id_prefix = 'tr') {
    $langs = get_translation_langs();
    ?>
    <div class="card border-0 shadow-sm mb-3 rw-translations">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">🌍 Preklady</span>
            <a href="languages.php" class="btn btn-sm btn-link p-0">Spravovať jazyky</a>
        </div>
        <div class="card-body">
        <?php if (!$langs): ?>
            <p class="text-muted small mb-0">Žiadny ďalší jazyk nie je aktívny. <a href="languages.php">Pridať jazyk</a></p>
        <?php else:
            $saved = $entity_id ? get_translations($type, $entity_id) : [];
            $posted = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tr']) && is_array($_POST['tr']) ? $_POST['tr'] : null;
            ?>
            <p class="text-muted small">Základný jazyk (<?= e(lang_name(get_default_lang())) ?>) upravujete v hlavnom formulári. Pole, ktoré v preklade nevyplníte, sa na webe zobrazí v základnom jazyku.</p>
            <ul class="nav nav-tabs flex-nowrap overflow-x-auto overflow-y-hidden" role="tablist">
                <?php foreach ($langs as $i => $l):
                    $has = !empty(array_filter($saved[$l['code']] ?? [], fn($v) => trim((string)$v) !== '')); ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-nowrap <?= $i === 0 ? 'active' : '' ?>" type="button" data-bs-toggle="tab"
                            data-bs-target="#<?= e($id_prefix . '-' . $l['code']) ?>" role="tab">
                        <?= e(strtoupper($l['code'])) ?> – <?= e($l['name']) ?>
                        <?php if ($has): ?><span class="badge text-bg-success badge-done">preložené</span><?php endif; ?>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="tab-content pt-3">
                <?php foreach ($langs as $i => $l):
                    $code = $l['code'];
                    $vals = $posted[$code] ?? ($saved[$code] ?? []); ?>
                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="<?= e($id_prefix . '-' . $code) ?>" role="tabpanel">
                    <?php foreach ($fields as $name => $spec):
                        $fname = 'tr[' . $code . '][' . $name . ']';
                        $val = is_string($vals[$name] ?? null) ? $vals[$name] : '';
                        $type_f = $spec['type'] ?? 'text'; ?>
                    <div class="mb-3">
                        <label class="form-label <?= $type_f === 'editor' ? 'fw-semibold' : '' ?>"><?= e($spec['label']) ?> (<?= e(strtoupper($code)) ?>)</label>
                        <?php if ($type_f === 'editor'): ?>
                            <textarea name="<?= e($fname) ?>" data-rw-editor><?= e($val) ?></textarea>
                        <?php elseif ($type_f === 'textarea'): ?>
                            <textarea name="<?= e($fname) ?>" class="form-control" rows="<?= (int)($spec['rows'] ?? 3) ?>"><?= e($val) ?></textarea>
                        <?php else: ?>
                            <input type="text" name="<?= e($fname) ?>" class="form-control" value="<?= e($val) ?>"<?= !empty($spec['max']) ? ' maxlength="' . (int)$spec['max'] . '"' : '' ?>>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>
    </div>
    <?php
}

/** Skripty Quill (CDN) + wrapper; volať tesne pred admin_footer(). */
function render_quill_assets($csrf, $post_id = 0) {
    ?>
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
window.RW_EDITOR_CONFIG = {
    uploadUrl: 'upload.php',
    csrf: <?= json_encode($csrf) ?>,
    csrfName: <?= json_encode(CSRF_TOKEN_NAME) ?>,
    postId: <?= (int)$post_id ?>
};
</script>
<script src="/assets/js/quill-editor.js"></script>
    <?php
}
