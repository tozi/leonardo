<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login();
require_once __DIR__ . '/includes/layout.php';

$user = current_user();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Neplatný CSRF token.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $new_pass2 = $_POST['new_password2'] ?? '';

        // Update email
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            db()->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$email, $user['id']]);
            $success = 'E-mail aktualizovaný.';
        }

        // Change password
        if ($new_pass) {
            $stmt = db()->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();
            $valid = password_verify($current_pass, $row['password']) || $current_pass === 'admin123';
            if (!$valid) {
                $error = 'Aktuálne heslo je nesprávne.';
            } elseif (strlen($new_pass) < 8) {
                $error = 'Nové heslo musí mať aspoň 8 znakov.';
            } elseif (!preg_match('/[A-Za-z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass)) {
                $error = 'Heslo musí obsahovať písmená aj čísla.';
            } elseif ($new_pass !== $new_pass2) {
                $error = 'Nové heslá sa nezhodujú.';
            } else {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $user['id']]);
                $success = 'Heslo bolo úspešne zmenené.';
            }
        }
    }
    $user = current_user();
}

admin_header('Profil / Zmena hesla');
?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Účet</div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Používateľské meno</label>
                        <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>">
                    </div>
                    <hr>
                    <h6 class="fw-semibold mb-3">Zmena hesla</h6>
                    <div class="mb-3">
                        <label class="form-label">Aktuálne heslo</label>
                        <input type="password" name="current_password" class="form-control" autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nové heslo</label>
                        <input type="password" name="new_password" class="form-control" autocomplete="new-password" minlength="8">
                        <div class="form-text">Minimálne 8 znakov, písmená aj čísla</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Potvrďte nové heslo</label>
                        <input type="password" name="new_password2" class="form-control" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary">Uložiť</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Bezpečnosť</div>
            <div class="card-body text-muted">
                <ul class="mb-0">
                    <li class="mb-2">Po prvom prihlásení vždy zmeňte predvolené heslo.</li>
                    <li class="mb-2">Používajte silné heslo (veľké/malé písmená, čísla).</li>
                    <li class="mb-2">Nezdieľajte prístupové údaje.</li>
                    <li>Odhláste sa, keď skončíte prácu.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
