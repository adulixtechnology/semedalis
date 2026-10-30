<?php
/**
 * Admin login — email + password authentication.
 *
 * DESIGN FIX #1 (session fixation): AFTER password_verify AND role==='admin'
 * succeed, and BEFORE setting any $_SESSION admin var, session_regenerate_id(true)
 * is called. Only then are the admin session vars stored and the user redirected.
 *
 * Non-admins, unknown emails, and wrong passwords all receive the SAME generic
 * "Invalid email or password" message (no user enumeration).
 *
 * This page does NOT require admin (it is the entry point), so it renders its
 * own minimal HTML shell rather than using admin_header.php.
 */
require __DIR__ . '/../config/config.php';

// Already authenticated -> straight to the dashboard.
if (isset($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'admin') {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    // Input bounds aligned to the live columns (design §6).
    $valid = true;
    if ($email === '' || mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $valid = false;
    }
    if ($password === '' || mb_strlen($password) < 6 || mb_strlen($password) > 72) {
        $valid = false;
    }

    // Look up the user by email only if the input is well-formed.
    $row = null;
    if ($valid) {
        try {
            $stmt = db()->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $row = $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Login query failed: ' . $e->getMessage());
            $valid = false;
        }
    }

    // Single combined check: a real admin row AND a matching password.
    $ok = ($valid && $row && password_verify($password, (string) $row['password']) && $row['role'] === 'admin');

    if ($ok) {
        // DESIGN FIX #1: regenerate the session id AFTER auth succeeds and
        // BEFORE storing any admin var, to prevent session fixation.
        session_regenerate_id(true);
        $_SESSION['admin_id']       = $row['id'];
        $_SESSION['admin_role']     = 'admin';
        $_SESSION['admin_username'] = $row['username'];
        header('Location: ' . url('admin/index.php'));
        exit;
    }

    // Same generic message for bad input, no user, wrong password, or non-admin.
    $loginError = t('admin.login.error');
}
?>
<!doctype html>
<html lang="<?= (($_SESSION['lang'] ?? 'en') === 'am') ? 'am' : 'en' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login &middot; Semidalis</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="login-body">

<div class="login-wrap">

    <div class="login-card">

        <!-- Brand -->
        <div class="login-brand">
            <div class="login-brand__icon">S</div>
            <div>
                <div class="login-brand__name">Semidalis</div>
                <div class="login-brand__tag">Admin Panel</div>
            </div>
        </div>

        <h1 class="login-title"><?= e(t('admin.login.title')) ?></h1>
        <p class="login-sub"><?= e(t('admin.login.sub')) ?></p>

        <?php
        $curLang = $_SESSION['lang'] ?? 'en';
        ?>
        <div class="lang-toggle" style="justify-content:center;margin-bottom:16px">
            <a href="?lang=en" class="lang-toggle__btn<?= $curLang === 'en' ? ' lang-toggle__btn--active' : '' ?>">EN</a>
            <span class="lang-toggle__sep">|</span>
            <a href="?lang=am" class="lang-toggle__btn<?= $curLang === 'am' ? ' lang-toggle__btn--active' : '' ?>">አማ</a>
        </div>

        <?php if ($loginError !== ''): ?>
        <div class="alert alert--error"><?= e($loginError) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('admin/login.php')) ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email"><?= e(t('admin.login.email')) ?></label>
                <input type="email" id="email" name="email" maxlength="150" placeholder="admin@example.com" required autofocus>
            </div>

            <div class="form-group">
                <label for="password"><?= e(t('admin.login.password')) ?></label>
                <input type="password" id="password" name="password" maxlength="72" placeholder="••••••••" required>
            </div>

            <div class="login-actions">
                <button type="submit" class="btn btn--primary btn--block"><?= e(t('admin.login.btn')) ?></button>
            </div>
        </form>

    </div>

    <p class="login-footer">&copy; <?= date('Y') ?> Semidalis Mission and Volunteer Ministry</p>

</div>

</body>
</html>
