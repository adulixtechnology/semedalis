<?php
/**
 * Admin — Users management.
 *
 * List users (id, username, email, role, created_at) ORDER BY id ASC.
 * Create a new user (username + PHP-enforced uniqueness because username is NOT
 * a unique key in the DB; email UNIQUE enforced by the DB — catch SQLSTATE 23000;
 * password hashed with PASSWORD_BCRYPT at cost 10; role admin|user).
 * Delete a user (cannot delete the current admin — self-delete guard).
 *
 * Every POST handler calls verify_csrf(); every id is cast to int and rejected
 * if <= 0 before any query. All output escaped with e().
 */
require __DIR__ . '/../config/config.php';
$adminTitle = 'Users';

$errors = [];

// --- POST handlers ----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $role     = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
        set_old('username', $username);
        set_old('email', $email);
        set_old('role', $role);

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } elseif (mb_strlen($username) > 100) {
            $errors['username'] = 'Username must be 100 characters or fewer.';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (mb_strlen($email) > 150) {
            $errors['email'] = 'Email must be 150 characters or fewer.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (mb_strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        } elseif (mb_strlen($password) > 72) {
            $errors['password'] = 'Password must be 72 characters or fewer.';
        }

        // PHP-enforced username uniqueness (the DB does NOT have a UNIQUE key on username).
        if (empty($errors['username'])) {
            try {
                $chkStmt = db()->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                $chkStmt->execute([$username]);
                if ((int) $chkStmt->fetchColumn() > 0) {
                    $errors['username'] = 'That username is already taken.';
                }
            } catch (PDOException $e) {
                error_log('Users username check: ' . $e->getMessage());
                $errors['form'] = 'Could not verify the username. Please try again.';
            }
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            try {
                $stmt = db()->prepare(
                    "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$username, $email, $hash, $role]);
                flash('success', 'User created.');
                header('Location: ' . url('admin/users.php'));
                exit;
            } catch (PDOException $e) {
                // Catch email UNIQUE constraint violation (SQLSTATE 23000).
                if ($e->getCode() === '23000') {
                    $errors['email'] = 'That email address is already in use.';
                } else {
                    error_log('Users create: ' . $e->getMessage());
                    $errors['form'] = 'Could not create the user. Please try again.';
                }
            }
        }

    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Invalid record.');
            header('Location: ' . url('admin/users.php'));
            exit;
        }
        // Refuse to delete the currently logged-in admin.
        if ($id === (int) $_SESSION['admin_id']) {
            flash('error', 'You cannot delete your own account.');
            header('Location: ' . url('admin/users.php'));
            exit;
        }
        try {
            $del = db()->prepare("DELETE FROM users WHERE id=?");
            $del->execute([$id]);
            flash('success', 'User deleted.');
        } catch (PDOException $e) {
            error_log('Users delete: ' . $e->getMessage());
            flash('error', 'Could not delete the user.');
        }
        header('Location: ' . url('admin/users.php'));
        exit;
    }
}

// --- Load the list ----------------------------------------------------------
$users = [];
try {
    $users = db()->query("SELECT id, username, email, role, created_at FROM users ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    error_log('Users list: ' . $e->getMessage());
}

require __DIR__ . '/../includes/admin_header.php';

$success = flash('success');
$error   = flash('error');
?>
<div class="admin-content">
    <div class="admin-page-head">
        <h1><?= e(t('admin.users.title')) ?></h1>
        <span class="text-muted"><?= e(t('admin.users.create_sub')) ?></span>
    </div>

    <?php if ($success): ?><div class="alert alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
    <?php if (!empty($errors['form'])): ?><div class="alert alert--error"><?= e($errors['form']) ?></div><?php endif; ?>

    <!-- Two-Column Layout: Form + Users List -->
    <div class="users-layout">
        <!-- Left Column: Create User Form -->
        <div class="users-layout__form">
            <div class="admin-form">
                <div class="users-create__head">
                    <h2><?= e(t('admin.users.create')) ?></h2>
                    <p><?= e(t('admin.users.create_sub')) ?></p>
                </div>

                <form action="<?= e(url('admin/users.php')) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">

                    <div class="users-create__grid">
                        <div class="form-group">
                            <label for="u_username"><?= e(t('admin.users.username')) ?> <span class="field-req">*</span></label>
                            <input type="text" id="u_username" name="username" value="<?= e(old('username')) ?>" maxlength="100" placeholder="e.g. john_admin" required autocomplete="off">
                            <?php if (!empty($errors['username'])): ?><div class="field-error"><?= e($errors['username']) ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="u_email"><?= e(t('admin.users.email')) ?> <span class="field-req">*</span></label>
                            <input type="email" id="u_email" name="email" value="<?= e(old('email')) ?>" maxlength="150" placeholder="admin@example.com" required autocomplete="off">
                            <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="u_password"><?= e(t('admin.users.password')) ?> <span class="field-req">*</span></label>
                            <input type="password" id="u_password" name="password" minlength="6" maxlength="72" placeholder="••••••" required autocomplete="new-password">
                            <div class="form-hint">6–72 characters. Use a strong password.</div>
                            <?php if (!empty($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="u_role"><?= e(t('admin.users.role')) ?> <span class="field-req">*</span></label>
                            <select id="u_role" name="role">
                                <option value="user"<?= old('role', 'user') === 'user' ? ' selected' : '' ?>><?= e(t('admin.users.role_user')) ?></option>
                                <option value="admin"<?= old('role', 'user') === 'admin' ? ' selected' : '' ?>><?= e(t('admin.users.role_admin')) ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions mt-2">
                        <button type="submit" class="btn btn--primary"><?= e(t('admin.users.create_btn')) ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Users Table -->
        <div class="users-layout__list">
            <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; color: var(--primary);"><?= e(t('admin.users.all')) ?> (<?= count($users) ?>)</h2>

            <?php if (empty($users)): ?>
            <div class="empty-state"><?= e(t('admin.users.no_users')) ?></div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= e(t('admin.users.username')) ?></th>
                            <th><?= e(t('admin.users.email')) ?></th>
                            <th><?= e(t('admin.users.role')) ?></th>
                            <th><?= e(t('admin.users.created')) ?></th>
                            <th><?= e(t('admin.actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= (int) $u['id'] ?></td>
                            <td><strong><?= e($u['username'] ?? '') ?></strong></td>
                            <td><?= e($u['email'] ?? '') ?></td>
                            <td>
                                <span class="pill<?= ($u['role'] ?? '') === 'admin' ? ' pill--admin' : ' pill--user' ?>"><?= e($u['role'] ?? 'user') ?></span>
                            </td>
                            <td><span class="text-muted"><?= e(format_date($u['created_at'] ?? '')) ?></span></td>
                            <td class="row-actions">
                                <?php if ((int) $u['id'] === (int) $_SESSION['admin_id']): ?>
                                <span class="pill pill--info" title="This is your account"><?= e(t('admin.users.you')) ?></span>
                                <?php else: ?>
                                <form method="post" action="<?= e(url('admin/users.php')) ?>" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn btn--danger btn--sm" data-confirm="Delete user '<?= e($u['username'] ?? '') ?>'? This cannot be undone."><?= e(t('admin.delete')) ?></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
