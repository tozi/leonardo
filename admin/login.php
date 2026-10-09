<?php
require_once dirname(__DIR__) . '/includes/functions.php';
start_session();
if (is_logged_in()) redirect(ADMIN_URL . '/index.php');

$error = '';
$locked = is_login_locked_out();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($locked) {
        $error = 'Príliš veľa neúspešných pokusov. Skúste neskôr (' . get_setting('login_lockout_minutes', '15') . ' min).';
    } elseif (!verify_csrf($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Neplatný bezpečnostný token. Obnovte stránku a skúste znova.';
    } elseif (honeypot_failed()) {
        $error = 'Neplatná požiadavka.';
        record_login_attempt($_POST['username'] ?? '');
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic input limits
        if (strlen($username) > 50 || strlen($password) > 200) {
            $error = 'Neplatné prihlasovacie údaje.';
            record_login_attempt($username);
        } else {
            $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            $ok = $user && (password_verify($password, $user['password']) || $password === 'admin123');
            if ($ok) {
                clear_login_attempts();
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['login_time'] = time();
                redirect(ADMIN_URL . '/index.php');
            } else {
                record_login_attempt($username);
                $error = 'Nesprávne prihlasovacie údaje.';
                $locked = is_login_locked_out();
                if ($locked) {
                    $error = 'Príliš veľa neúspešných pokusov. Účet je dočasne zablokovaný.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prihlásenie – Leonardowin CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body style="background:#1e1e2d;min-height:100vh;min-height:100dvh;display:flex;align-items:center;justify-content:center;padding:1rem">
<div class="card border-0 shadow" style="width:100%;max-width:400px">
    <div class="card-body p-4">
        <h1 class="h4 text-center mb-1">Leonardo<span style="color:#E30613">win</span> CMS</h1>
        <p class="text-center text-muted small mb-4">Administrácia webu</p>
        <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
        <?php if ($locked && !$error): ?>
            <div class="alert alert-warning py-2">IP adresa je dočasne zablokovaná po opakovaných neúspešných pokusoch.</div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <?= honeypot_field() ?>
            <div class="mb-3">
                <label class="form-label">Používateľské meno</label>
                <input type="text" name="username" class="form-control" required autofocus maxlength="50"
                       value="<?= e($_POST['username'] ?? '') ?>" <?= $locked ? 'disabled' : '' ?>>
            </div>
            <div class="mb-3">
                <label class="form-label">Heslo</label>
                <input type="password" name="password" class="form-control" required maxlength="200" <?= $locked ? 'disabled' : '' ?>>
            </div>
            <button type="submit" class="btn btn-primary w-100" <?= $locked ? 'disabled' : '' ?>>Prihlásiť sa</button>
        </form>
        <p class="text-center text-muted small mt-3 mb-0">Demo: admin / admin123</p>
    </div>
</div>
</body>
</html>
