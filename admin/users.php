<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $id = (int) input('user_id');
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $target = $stmt->fetch();

    if ($target) {
        switch (input('action')) {
            case 'verify':
                $pdo->prepare('UPDATE users SET is_verified = 1, verified_at = NOW() WHERE id = ?')->execute([$id]);
                notify($id, 'system', 'Congratulations! You are now a verified local representative for ' . $target['area'] . '.', 'completed_events.php');
                flash('success', $target['full_name'] . ' is now a verified local representative.');
                break;
            case 'unverify':
                $pdo->prepare('UPDATE users SET is_verified = 0, verified_at = NULL WHERE id = ?')->execute([$id]);
                notify($id, 'system', 'Your local representative status has been removed by an administrator.');
                flash('success', 'Verification removed for ' . $target['full_name'] . '.');
                break;
            case 'make_admin':
                $exists = $pdo->prepare('SELECT 1 FROM admins WHERE user_id = ? OR username = ?');
                $exists->execute([$id, $target['username']]);
                if ($exists->fetch()) {
                    flash('warning', 'An admin account with this username already exists.');
                } else {
                    // The citizen logs in to the admin panel with the same username and password
                    $pdo->prepare('INSERT INTO admins (username, full_name, password_hash, user_id) VALUES (?, ?, ?, ?)')
                        ->execute([$target['username'], $target['full_name'], $target['password_hash'], $id]);
                    notify($id, 'system', 'You have been given administrator access. Use the Admin option on the login page.');
                    flash('success', $target['full_name'] . ' can now log in to the admin panel with their own credentials.');
                }
                break;
            case 'delete':
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
                delete_upload($target['avatar']);
                flash('success', 'User ' . $target['username'] . ' and all of their content were deleted.');
                break;
        }
    }
    redirect_back('admin/users.php');
}

$q = input('q');
$area = input('area');
$filter = in_array(input('filter'), ['verified', 'unverified', 'admins'], true) ? input('filter') : '';

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.nid LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%");
}
if ($area !== '') { $where[] = 'u.area = ?'; $params[] = $area; }
if ($filter === 'verified')   $where[] = 'u.is_verified = 1';
if ($filter === 'unverified') $where[] = 'u.is_verified = 0';
if ($filter === 'admins')     $where[] = 'a.id IS NOT NULL';
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count = $pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN admins a ON a.user_id = u.id $whereSql");
$count->execute($params);
$p = paginate((int) $count->fetchColumn(), 20);

$stmt = $pdo->prepare("SELECT u.*, a.id AS admin_id, s.total_score
    FROM users u LEFT JOIN admins a ON a.user_id = u.id JOIN user_scores s ON s.id = u.id
    $whereSql ORDER BY u.area, u.full_name LIMIT {$p['limit']} OFFSET {$p['offset']}");
$stmt->execute($params);
$users = $stmt->fetchAll();

$areas = $pdo->query('SELECT area, COUNT(*) c FROM users GROUP BY area ORDER BY area')->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Users';
$adminNav = 'users';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="card mb-3">
    <form class="filters" method="get">
        <div class="field"><label for="q">Search</label><input class="input" id="q" name="q" value="<?= e($q) ?>" placeholder="Name, username, email, phone or NID"></div>
        <div class="field"><label for="area">Area</label>
            <select class="select" id="area" name="area"><option value="">All areas</option>
                <?php foreach ($areas as $a => $c): ?><option value="<?= e($a) ?>" <?= selected($a, $area) ?>><?= e($a) ?> (<?= $c ?>)</option><?php endforeach; ?></select></div>
        <div class="field"><label for="filter">Show</label>
            <select class="select" id="filter" name="filter">
                <option value="">Everyone</option>
                <option value="verified" <?= selected('verified', $filter) ?>>Verified representatives</option>
                <option value="unverified" <?= selected('unverified', $filter) ?>>Not verified</option>
                <option value="admins" <?= selected('admins', $filter) ?>>Also admins</option>
            </select></div>
        <button class="btn btn-primary">Filter</button>
        <a class="btn btn-ghost" href="<?= url('admin/users.php') ?>">Reset</a>
    </form>
</div>

<div class="card card-flush">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>User</th><th>Contact</th><th>Area</th><th>Points</th><th>Last seen</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><div class="user-cell"><?= avatar($u, 'sm') ?><div>
                        <a href="<?= url('admin/user.php?id=' . $u['id']) ?>"><strong><?= e($u['full_name']) ?></strong></a><?= verified_tick($u) ?>
                        <?php if ($u['admin_id']): ?><span class="badge badge-primary">Admin</span><?php endif; ?>
                        <small>@<?= e($u['username']) ?> · NID <?= e($u['nid']) ?></small></div></div></td>
                    <td><?= e($u['phone']) ?><br><small class="muted"><?= e($u['email']) ?></small></td>
                    <td><?= e($u['area']) ?><br><small class="muted"><?= e($u['division']) ?></small></td>
                    <td><?= $u['total_score'] ?></td>
                    <td class="nowrap"><small><?= $u['last_seen'] ? time_ago($u['last_seen']) : 'Never' ?></small></td>
                    <td class="actions">
                        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <?php if ($u['is_verified']): ?>
                                <input type="hidden" name="action" value="unverify"><button class="btn btn-outline btn-sm">Unverify</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="verify"><button class="btn btn-primary btn-sm"><i class="fa-solid fa-user-check"></i> Verify</button>
                            <?php endif; ?>
                        </form>
                        <?php if (!$u['admin_id']): ?>
                            <form method="post" class="inline-form" data-confirm="Give <?= e($u['full_name']) ?> full administrator access?">
                                <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="make_admin">
                                <button class="btn btn-ghost btn-sm" title="Make admin"><i class="fa-solid fa-user-gear"></i></button></form>
                        <?php endif; ?>
                        <form method="post" class="inline-form" data-confirm="Delete <?= e($u['username']) ?> and ALL their reports, events, messages and donations?">
                            <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="delete">
                            <button class="btn btn-danger-outline btn-sm" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?><tr><td colspan="6"><div class="empty"><i class="fa-solid fa-user-slash"></i><p>No users found.</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= pagination_links($p) ?>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
