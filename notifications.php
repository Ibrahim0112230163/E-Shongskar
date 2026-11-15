<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$uid = (int) $user['id'];

// Open one notification: mark it read and follow its link
if ($openId = (int) input('open')) {
    $stmt = $pdo->prepare('SELECT link FROM notifications WHERE id = ? AND user_id = ?');
    $stmt->execute([$openId, $uid]);
    $link = $stmt->fetchColumn();
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$openId, $uid]);
    redirect($link ?: 'notifications.php');
}

if (is_post()) {
    verify_csrf();
    if (input('action') === 'read_all') {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$uid]);
        flash('success', 'All notifications marked as read.');
    } elseif (input('action') === 'clear_read') {
        $pdo->prepare('DELETE FROM notifications WHERE user_id = ? AND is_read = 1')->execute([$uid]);
        flash('success', 'Read notifications cleared.');
    }
    redirect('notifications.php');
}

$count = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
$count->execute([$uid]);
$p = paginate((int) $count->fetchColumn(), 20);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY is_read, created_at DESC LIMIT {$p['limit']} OFFSET {$p['offset']}");
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

$icons = ['report' => 'fa-flag', 'event' => 'fa-calendar-days', 'fund' => 'fa-hand-holding-dollar', 'blood' => 'fa-droplet',
    'alert' => 'fa-triangle-exclamation', 'sos' => 'fa-tower-broadcast', 'system' => 'fa-circle-info'];

$pageTitle = 'Notifications';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container container-narrow row-between">
        <div>
            <h1>Notifications</h1>
            <p>Updates about your reports, events, blood requests and your community.</p>
        </div>
        <div class="row">
            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="read_all">
                <button class="btn btn-ghost-light btn-sm"><i class="fa-solid fa-check-double"></i> Mark all read</button></form>
            <form method="post" class="inline-form" data-confirm="Delete all read notifications?"><?= csrf_field() ?><input type="hidden" name="action" value="clear_read">
                <button class="btn btn-ghost-light btn-sm"><i class="fa-solid fa-broom"></i> Clear read</button></form>
        </div>
    </div>
</section>

<div class="container container-narrow">
    <div class="card">
        <?php if (!$items): ?>
            <div class="empty"><i class="fa-regular fa-bell"></i><h3>Nothing here yet</h3><p>We'll let you know when something happens in your area.</p></div>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($items as $n): ?>
                    <li class="list-item <?= $n['is_read'] ? '' : 'unread' ?>">
                        <span class="list-icon" <?= $n['type'] === 'sos' ? 'style="background:var(--danger-bg);color:var(--danger)"' : '' ?>>
                            <i class="fa-solid <?= $icons[$n['type']] ?? 'fa-bell' ?>"></i></span>
                        <div class="list-body">
                            <a href="?open=<?= $n['id'] ?>" style="color:var(--ink)"><?= $n['is_read'] ? e($n['message']) : '<strong>' . e($n['message']) . '</strong>' ?></a>
                            <small class="muted"><?= time_ago($n['created_at']) ?></small>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?= pagination_links($p) ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
