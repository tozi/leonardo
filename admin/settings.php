<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $keys = ['site_name', 'site_tagline', 'site_email', 'site_phone', 'site_address', 'primary_color', 'footer_text', 'ai_description', 'cookie_enabled', 'fb_app_id'];
        // cookie text pre každý aktívny jazyk
        foreach (get_active_languages() as $l) {
            $keys[] = 'cookie_text_' . $l['code'];
        }
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                set_setting($key, trim($_POST[$key]));
            }
        }
        // Predvolený obrázok pre sociálne siete (og:image), ak stránka/článok nemá vlastný
        if (!empty($_POST['remove_og_image'])) {
            set_setting('og_default_image', '');
        }
        if (!empty($_FILES['og_default_image']['name'])) {
            $up = upload_image($_FILES['og_default_image']);
            if ($up['success']) set_setting('og_default_image', $up['filename']);
            else set_flash('error', $up['error'] ?? 'Chyba nahrávania obrázka.');
        }
        if (empty($_SESSION['flash'])) set_flash('success', 'Nastavenia uložené.');
        redirect(ADMIN_URL . '/settings.php');
    }
}

admin_header('Nastavenia');
?>

<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header"><h2>Základné nastavenia</h2></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Názov webu</label>
                    <input type="text" name="site_name" value="<?= e(get_setting('site_name')) ?>">
                </div>
                <div class="form-group">
                    <label>Slogan</label>
                    <input type="text" name="site_tagline" value="<?= e(get_setting('site_tagline')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="site_email" value="<?= e(get_setting('site_email')) ?>">
                </div>
                <div class="form-group">
                    <label>Telefón</label>
                    <input type="text" name="site_phone" value="<?= e(get_setting('site_phone')) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Adresa</label>
                <input type="text" name="site_address" value="<?= e(get_setting('site_address')) ?>">
            </div>
            <div class="form-group">
                <label>Hlavná farba (hex)</label>
                <input type="text" name="primary_color" value="<?= e(get_setting('primary_color', '#E30613')) ?>" style="max-width:150px">
            </div>
            <div class="form-group">
                <label>Text v pätičke</label>
                <input type="text" name="footer_text" value="<?= e(get_setting('footer_text')) ?>">
            </div>
            
            <hr>
            <h6 class="fw-semibold">AI / vyhľadávanie</h6>
            <div class="mb-3">
                <label class="form-label">Popis webu pre AI (ai.json / llms.txt)</label>
                <textarea name="ai_description" class="form-control" rows="3"><?= e(get_setting('ai_description')) ?></textarea>
            </div>
            <hr>
            <h6 class="fw-semibold">Cookies</h6>
            <div class="mb-3 form-check">
                <input type="hidden" name="cookie_enabled" value="0">
                <input type="checkbox" name="cookie_enabled" value="1" class="form-check-input" id="cookieEn" <?= get_setting('cookie_enabled','1')==='1'?'checked':'' ?>>
                <label class="form-check-label" for="cookieEn">Zobraziť cookie lištu</label>
            </div>
            <?php foreach (get_active_languages() as $l): ?>
            <div class="mb-3">
                <label class="form-label">Text cookies (<?= e(strtoupper($l['code'])) ?> – <?= e($l['name']) ?>)</label>
                <textarea name="cookie_text_<?= e($l['code']) ?>" class="form-control" rows="2"><?= e(get_setting('cookie_text_' . $l['code'])) ?></textarea>
            </div>
            <?php endforeach; ?>

            <hr>
            <h6 class="fw-semibold">Sociálne siete (Open Graph)</h6>
            <div class="mb-3">
                <label class="form-label">Predvolený obrázok pri zdieľaní</label>
                <?php if (get_setting('og_default_image')): ?>
                    <div class="mb-2">
                        <img src="<?= e(UPLOADS_URL . '/' . get_setting('og_default_image')) ?>" style="max-width:240px" class="img-fluid rounded border" alt="">
                        <div class="form-check mt-1">
                            <input type="checkbox" name="remove_og_image" value="1" class="form-check-input" id="rmOg">
                            <label class="form-check-label small" for="rmOg">Odstrániť</label>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="og_default_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                <div class="form-text">Použije sa, ak stránka ani článok nemá vlastný obrázok. Odporúčané 1200 × 630 px.</div>
            </div>
            <div class="mb-3" style="max-width:300px">
                <label class="form-label">Facebook App ID (nepovinné)</label>
                <input type="text" name="fb_app_id" class="form-control" value="<?= e(get_setting('fb_app_id')) ?>">
            </div>

            <button type="submit" class="btn btn-primary">Uložiť nastavenia</button>
        </div>
    </div>
</form>

<?php admin_footer(); ?>
