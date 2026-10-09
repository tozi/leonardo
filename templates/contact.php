<?php
include __DIR__ . '/header.php';
$form_sent = false;
$form_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (honeypot_failed()) {
        $form_error = 'Neplatná požiadavka.';
    } elseif (!check_form_rate_limit('contact', 15)) {
        $form_error = 'Počkajte chvíľu pred ďalším odoslaním.';
    } else {
        $name = mb_substr(trim($_POST['name'] ?? ''), 0, 100);
        $email = mb_substr(trim($_POST['email'] ?? ''), 0, 100);
        $phone = mb_substr(trim($_POST['phone'] ?? ''), 0, 30);
        $message = mb_substr(trim($_POST['message'] ?? ''), 0, 5000);
        if (!$name || !$email || !$message) {
            $form_error = 'Vyplňte prosím všetky povinné polia.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form_error = 'Zadajte platný e-mail.';
        } else {
            // In production: send mail / save to DB – never echo raw input unescaped
            $form_sent = true;
        }
    }
}
?>
<main class="page-content">
    <div class="container">
        <h1 class="mb-3"><?= e($page['title']) ?></h1>
        <?= $page['content'] ?? '' ?>
        <div class="row g-5 mt-2">
            <div class="col-lg-6">
                <?php if ($form_sent): ?>
                    <div class="alert alert-success">Ďakujeme! Vaša správa bola odoslaná. Ozveme sa vám do 24 hodín.</div>
                <?php else: ?>
                    <?php if ($form_error): ?><div class="alert alert-danger"><?= e($form_error) ?></div><?php endif; ?>
                    <form method="post">
                        <?= honeypot_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Meno a priezvisko *</label>
                            <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefón</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Správa *</label>
                            <textarea name="message" class="form-control" rows="5" required><?= e($_POST['message'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">Odoslať</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="col-lg-5">
                <h4>Kontaktné údaje</h4>
                <p class="text-muted mt-3">
                    <strong><?= e(get_setting('site_name')) ?></strong><br>
                    <?= e(get_setting('site_address')) ?><br><br>
                    Tel: <a href="tel:<?= e(get_setting('site_phone')) ?>"><?= e(get_setting('site_phone')) ?></a><br>
                    E-mail: <a href="mailto:<?= e(get_setting('site_email')) ?>"><?= e(get_setting('site_email')) ?></a>
                </p>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
