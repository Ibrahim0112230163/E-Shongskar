<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$stmt = $pdo->prepare('SELECT s.*, u.occupation, u.last_seen, u.created_at
    FROM user_scores s JOIN users u ON u.id = s.id
    WHERE s.area = ? AND s.id <> ?
    ORDER BY (u.last_seen >= NOW() - INTERVAL ' . ONLINE_WINDOW_MINUTES . ' MINUTE) DESC, s.is_verified DESC, s.full_name');
$stmt->execute([$user['area'], $user['id']]);
$people = $stmt->fetchAll();
$online = array_filter($people, fn ($p) => $p['last_seen'] && strtotime($p['last_seen']) >= time() - ONLINE_WINDOW_MINUTES * 60);

$pageTitle = 'Neighbours';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>Neighbours in <?= e($user['area']) ?></h1>
        <p><?= count($people) ?> member<?= count($people) == 1 ? '' : 's' ?> · <?= count($online) ?> online now</p>
    </div>
</section>

<div class="container">
    <?php if (!$people): ?>
        <div class="card empty"><i class="fa-solid fa-user-group"></i><h3>You're the first one here</h3><p>Invite your neighbours to join E-Shongskar and choose "<?= e($user['area']) ?>" as their area.</p></div>
    <?php else: ?>
        <div class="grid grid-auto">
            <?php foreach ($people as $p):
                $isOnline = $p['last_seen'] && strtotime($p['last_seen']) >= time() - ONLINE_WINDOW_MINUTES * 60; ?>
                <div class="card row" style="align-items:flex-start">
                    <?= avatar($p, 'lg') ?>
                    <div class="list-body">
                        <strong><?= e($p['full_name']) ?><?= verified_tick($p) ?></strong>
                        <small class="muted">@<?= e($p['username']) ?><?= $p['occupation'] ? ' · ' . e($p['occupation']) : '' ?></small>
                        <div class="meta small mt-1">
                            <span><span class="<?= $isOnline ? 'online-dot' : 'offline-dot' ?>"></span> <?= $isOnline ? 'Online' : ($p['last_seen'] ? time_ago($p['last_seen']) : 'Never active') ?></span>
                            <span class="chip"><i class="fa-solid fa-star"></i> <?= $p['total_score'] ?> pts</span>
                        </div>
                        <?php if ($user['is_verified'] || $p['is_verified']): ?>
                            <a class="btn btn-outline btn-sm mt-1" href="<?= url('chat.php?with=' . $p['id']) ?>"><i class="fa-solid fa-message"></i> Message</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
