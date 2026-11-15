<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int) input('id');
$stmt = $pdo->prepare('SELECT u.*, a.id AS admin_id FROM users u LEFT JOIN admins a ON a.user_id = u.id WHERE u.id = ?');
$stmt->execute([$id]);
$u = $stmt->fetch();
if (!$u) {
    flash('error', 'User not found.');
    redirect('admin/users.php');
}

$stmt = $pdo->prepare('SELECT * FROM user_scores WHERE id = ?');
$stmt->execute([$id]);
$score = $stmt->fetch();

$stmt = $pdo->prepare('SELECT r.*, (SELECT COUNT(*) FROM report_votes v WHERE v.report_id = r.id) votes FROM reports r WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$id]);
$reports = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT e.*, f.target_amount, f.status AS fund_status FROM events e LEFT JOIN event_funds f ON f.event_id = e.id WHERE e.user_id = ? ORDER BY e.created_at DESC');
$stmt->execute([$id]);
$events = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT d.*, e.title FROM donations d JOIN events e ON e.id = d.event_id WHERE d.user_id = ? ORDER BY d.created_at DESC');
$stmt->execute([$id]);
$donations = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT platform, url FROM social_links WHERE user_id = ?');
$stmt->execute([$id]);
$socials = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = $u['full_name'];
$adminNav = 'users';
include __DIR__ . '/../includes/admin_header.php';
?>

<p><a href="<?= url('admin/users.php') ?>"><i class="fa-solid fa-arrow-left"></i> All users</a></p>

<div class="layout-sidebar" style="grid-template-columns:320px minmax(0,1fr)">
    <aside class="card profile-card">
        <?= avatar($u, 'xl') ?>
        <h2 class="mb-0"><?= e($u['full_name']) ?><?= verified_tick($u) ?></h2>
        <p class="muted">@<?= e($u['username']) ?></p>
        <p>
            <?= $u['is_verified'] ? '<span class="badge badge-primary">Verified representative</span>' : '<span class="badge badge-muted">Citizen</span>' ?>
            <?= $u['admin_id'] ? '<span class="badge badge-info">Admin</span>' : '' ?>
        </p>
        <div class="socials mb-2">
            <?php foreach ($socials as $platform => $link): ?>
                <a href="<?= e($link) ?>" target="_blank" rel="noopener"><i class="<?= SOCIAL_PLATFORMS[$platform] ?>"></i></a>
            <?php endforeach; ?>
        </div>
        <dl class="kv" style="text-align:left">
            <dt>Email</dt><dd><?= e($u['email']) ?></dd>
            <dt>Phone</dt><dd><?= e($u['phone']) ?></dd>
            <dt>NID</dt><dd><?= e($u['nid']) ?></dd>
            <dt>Born</dt><dd><?= fmt_date($u['dob']) ?> (<?= e($u['gender']) ?>)</dd>
            <dt>Blood</dt><dd><?= e($u['blood_group']) ?></dd>
            <dt>Occupation</dt><dd><?= e($u['occupation'] ?: '—') ?></dd>
            <dt>Address</dt><dd><?= e(trim(($u['house_no'] ? 'House ' . $u['house_no'] . ', ' : '') . ($u['street'] ? $u['street'] . ', ' : '') . $u['area'] . ', ' . $u['division'])) ?></dd>
            <dt>Joined</dt><dd><?= fmt_date($u['created_at']) ?></dd>
            <dt>Last seen</dt><dd><?= $u['last_seen'] ? time_ago($u['last_seen']) : 'Never' ?></dd>
            <dt>Points</dt><dd><?= $score['total_score'] ?></dd>
        </dl>
        <form method="post" action="<?= url('admin/users.php') ?>" class="mt-3">
            <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <input type="hidden" name="action" value="<?= $u['is_verified'] ? 'unverify' : 'verify' ?>">
            <button class="btn <?= $u['is_verified'] ? 'btn-outline' : 'btn-primary' ?> btn-block"><?= $u['is_verified'] ? 'Remove verification' : 'Verify as local representative' ?></button>
        </form>
    </aside>

    <div class="stack">
        <div class="card card-flush">
            <h3 class="card-title" style="padding:18px 22px 0"><i class="fa-solid fa-flag"></i> Reports (<?= count($reports) ?>)</h3>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Title</th><th>Type</th><th>Votes</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($reports as $r): ?>
                    <tr><td><a href="<?= url('report.php?id=' . $r['id']) ?>" target="_blank"><?= e($r['title']) ?></a></td><td><?= e($r['issue_type']) ?></td>
                        <td><?= $r['votes'] ?></td><td><?= status_badge($r['status']) ?></td><td class="nowrap"><?= fmt_date($r['created_at']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$reports): ?><tr><td colspan="5" class="muted">No reports.</td></tr><?php endif; ?>
                </tbody></table></div>
        </div>

        <div class="card card-flush">
            <h3 class="card-title" style="padding:18px 22px 0"><i class="fa-solid fa-calendar"></i> Events (<?= count($events) ?>)</h3>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Title</th><th>Category</th><th>Fund</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($events as $ev): ?>
                    <tr><td><a href="<?= url('event.php?id=' . $ev['id']) ?>" target="_blank"><?= e($ev['title']) ?></a></td><td><?= e($ev['category']) ?></td>
                        <td><?= $ev['target_amount'] ? money($ev['target_amount']) . ' ' . status_badge($ev['fund_status']) : '—' ?></td>
                        <td><?= status_badge($ev['status']) ?></td><td class="nowrap"><?= fmt_date($ev['created_at']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$events): ?><tr><td colspan="5" class="muted">No events.</td></tr><?php endif; ?>
                </tbody></table></div>
        </div>

        <div class="card card-flush">
            <h3 class="card-title" style="padding:18px 22px 0"><i class="fa-solid fa-hand-holding-dollar"></i> Donations (<?= count($donations) ?>)</h3>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Event</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($donations as $d): ?>
                    <tr><td><?= e($d['title']) ?></td><td><?= money($d['amount']) ?></td><td><?= e($d['method']) ?></td><td><?= e($d['transaction_ref'] ?: '—') ?></td><td class="nowrap"><?= fmt_date($d['created_at']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$donations): ?><tr><td colspan="5" class="muted">No donations.</td></tr><?php endif; ?>
                </tbody></table></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
