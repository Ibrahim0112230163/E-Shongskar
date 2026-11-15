<?php
require_once __DIR__ . '/../includes/functions.php';
$admin = require_admin();
$errors = [];

if (is_post()) {
    verify_csrf();
    switch (input('action')) {
        case 'password':
            $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
            $stmt->execute([$admin['id']]);
            if (!password_verify($_POST['current_password'] ?? '', $stmt->fetchColumn())) {
                $errors[] = 'Your current password is incorrect.';
            } elseif (strlen($_POST['password'] ?? '') < 8) {
                $errors[] = 'Admin passwords must be at least 8 characters.';
            } elseif ($_POST['password'] !== ($_POST['password_confirm'] ?? '')) {
                $errors[] = 'The new passwords do not match.';
            } else {
                $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $admin['id']]);
                flash('success', 'Your password was changed.');
                redirect('admin/account.php');
            }
            break;

        case 'create':
            $username = input('username');
            $fullName = input('full_name');
            $password = $_POST['new_password'] ?? '';
            if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) $errors[] = 'Username must be 3–30 letters, numbers, dots or underscores.';
            if (mb_strlen($fullName) < 3)                           $errors[] = 'Please enter a full name.';
            if (strlen($password) < 8)                              $errors[] = 'The password must be at least 8 characters.';
            $dup = $pdo->prepare('SELECT 1 FROM admins WHERE username = ?');
            $dup->execute([$username]);
            if ($dup->fetch())                                      $errors[] = 'That admin username is taken.';
            if (!$errors) {
                $pdo->prepare('INSERT INTO admins (username, full_name, password_hash) VALUES (?, ?, ?)')
                    ->execute([$username, $fullName, password_hash($password, PASSWORD_DEFAULT)]);
                flash('success', 'Admin account "' . $username . '" created.');
                redirect('admin/account.php');
            }
            break;

        case 'delete':
            $id = (int) input('admin_id');
            $total = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
            if ($id === (int) $admin['id']) {
                flash('error', 'You cannot remove your own account.');
            } elseif ($total <= 1) {
                flash('error', 'At least one admin account must remain.');
            } else {
                $pdo->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
                flash('success', 'Admin access removed.');
            }
            redirect('admin/account.php');
    }
}

$admins = $pdo->query('SELECT a.*, u.username AS citizen FROM admins a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at')->fetchAll();

$pageTitle = 'Admin accounts';
$adminNav = 'account';
include __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($errors): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= implode('<br>', array_map('e', $errors)) ?></span></div><?php endif; ?>

<div class="grid grid-2">
    <div class="card card-flush">
        <h3 class="card-title" style="padding:18px 22px 0"><i class="fa-solid fa-user-shield"></i> Administrators</h3>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Admin</th><th>Last login</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($admins as $a): ?>
                    <tr>
                        <td><strong><?= e($a['full_name']) ?></strong><br><small class="muted">@<?= e($a['username']) ?><?= $a['citizen'] ? ' · citizen account' : '' ?></small></td>
                        <td class="nowrap"><small><?= $a['last_login'] ? time_ago($a['last_login']) : 'Never' ?></small></td>
                        <td class="actions">
                            <?php if ((int) $a['id'] !== (int) $admin['id']): ?>
                                <form method="post" class="inline-form" data-confirm="Remove admin access for <?= e($a['username']) ?>?"><?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="admin_id" value="<?= $a['id'] ?>">
                                    <button class="btn btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></button></form>
                            <?php else: ?><span class="badge badge-primary">You</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-key"></i> Change my password</h3>
            <form method="post" class="stack mt-2">
                <?= csrf_field() ?><input type="hidden" name="action" value="password">
                <div class="field"><label for="current_password">Current password</label><input class="input" type="password" id="current_password" name="current_password" required></div>
                <div class="field"><label for="password">New password</label><input class="input" type="password" id="password" name="password" minlength="8" required></div>
                <div class="field"><label for="password_confirm">Confirm new password</label><input class="input" type="password" id="password_confirm" name="password_confirm" required></div>
                <button class="btn btn-primary">Update password</button>
            </form>
        </div>

        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-user-plus"></i> New admin account</h3>
            <form method="post" class="stack mt-2">
                <?= csrf_field() ?><input type="hidden" name="action" value="create">
                <div class="field"><label for="username">Username</label><input class="input" id="username" name="username" required></div>
                <div class="field"><label for="full_name">Full name</label><input class="input" id="full_name" name="full_name" required></div>
                <div class="field"><label for="new_password">Password</label><input class="input" type="password" id="new_password" name="new_password" minlength="8" required></div>
                <button class="btn btn-primary">Create admin</button>
                <p class="small muted mb-0">To give an existing citizen admin access, use <a href="<?= url('admin/users.php') ?>">Users</a> instead.</p>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
