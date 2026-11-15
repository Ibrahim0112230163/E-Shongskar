<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$area = $user['area'];

$stmt = $pdo->prepare("SELECT
    (SELECT COUNT(*) FROM reports WHERE area = ? AND status IN ('Pending','In Progress')) AS open_reports,
    (SELECT COUNT(*) FROM reports WHERE area = ? AND status = 'Solved')                  AS solved_reports,
    (SELECT COUNT(*) FROM events  WHERE area = ? AND status = 'active')                   AS active_events,
    (SELECT COUNT(*) FROM users   WHERE area = ? AND id <> ? AND last_seen >= NOW() - INTERVAL " . ONLINE_WINDOW_MINUTES . " MINUTE) AS online");
$stmt->execute([$area, $area, $area, $area, $user['id']]);
$stats = $stmt->fetch();

$alerts = active_alerts($user, 3);

$stmt = $pdo->prepare("SELECT r.*, u.full_name, u.username,
        (SELECT COUNT(*) FROM report_votes v WHERE v.report_id = r.id) AS votes
    FROM reports r JOIN users u ON u.id = r.user_id
    WHERE r.area = ? AND r.status IN ('Pending','In Progress')
    ORDER BY votes DESC, r.created_at DESC LIMIT 4");
$stmt->execute([$area]);
$reports = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT e.*, u.full_name,
        (SELECT COUNT(*) FROM event_participants p WHERE p.event_id = e.id) AS participants
    FROM events e JOIN users u ON u.id = e.user_id
    WHERE e.area = ? AND e.status = 'active'
    ORDER BY e.created_at DESC LIMIT 4");
$stmt->execute([$area]);
$events = $stmt->fetchAll();

$best = leaderboard($area, 1)[0] ?? null;
$monthly = leaderboard(null, 1, date('Y-m-01'))[0] ?? null;

$stmt = $pdo->prepare('SELECT id, username, full_name, avatar, is_verified FROM users WHERE area = ? AND is_verified = 1 AND id <> ? ORDER BY full_name LIMIT 5');
$stmt->execute([$area, $user['id']]);
$representatives = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Home';
$activeNav = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container row-between">
        <div>
            <div class="breadcrumb"><i class="fa-solid fa-location-dot"></i> <?= e($area) ?>, <?= e($user['division']) ?></div>
            <h1>Hello, <?= e(explode(' ', $user['full_name'])[0]) ?> 👋</h1>
            <p>Here is what is happening in your community today.</p>
        </div>
        <a href="<?= url('report_new.php') ?>" class="btn btn-accent"><i class="fa-solid fa-plus"></i> Report an issue</a>
    </div>
</section>

<div class="container stack">
    <div class="grid grid-4">
        <div class="stat"><div class="stat-icon amber"><i class="fa-solid fa-flag"></i></div><div><div class="stat-value"><?= $stats['open_reports'] ?></div><div class="stat-label">Open issues in your area</div></div></div>
        <div class="stat"><div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div><div><div class="stat-value"><?= $stats['solved_reports'] ?></div><div class="stat-label">Issues solved</div></div></div>
        <div class="stat"><div class="stat-icon blue"><i class="fa-solid fa-calendar-days"></i></div><div><div class="stat-value"><?= $stats['active_events'] ?></div><div class="stat-label">Active events</div></div></div>
        <a class="stat card-link" href="<?= url('neighbours.php') ?>"><div class="stat-icon green"><i class="fa-solid fa-users"></i></div><div><div class="stat-value"><?= $stats['online'] ?></div><div class="stat-label">Neighbours online now</div></div></a>
    </div>

    <?php foreach ($alerts as $a): ?>
        <a class="alert-strip <?= $a['severity'] === 'critical' ? '' : e($a['severity']) ?>" href="<?= url('alerts.php') ?>">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><strong><?= e($a['title']) ?></strong> — <?= e(excerpt($a['description'], 110)) ?></span>
        </a>
    <?php endforeach; ?>

    <div class="quick-actions">
        <a class="quick-action" href="<?= url('report_new.php') ?>"><i class="fa-solid fa-flag"></i>Report issue</a>
        <a class="quick-action" href="<?= url('event_new.php') ?>"><i class="fa-solid fa-calendar-plus"></i>Create event</a>
        <a class="quick-action" href="<?= url('blood.php') ?>"><i class="fa-solid fa-droplet"></i>Find blood</a>
        <a class="quick-action" href="<?= url('chat.php') ?>"><i class="fa-solid fa-comments"></i>Messages</a>
        <a class="quick-action" href="<?= url('leaderboard.php') ?>"><i class="fa-solid fa-trophy"></i>Leaderboard</a>
        <a class="quick-action sos" href="<?= url('emergency.php#sos') ?>"><i class="fa-solid fa-tower-broadcast"></i>SOS</a>
    </div>

    <div class="layout-sidebar">
        <div class="stack">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-flag"></i> Top issues near you</h3>
                    <a href="<?= url('reports.php') ?>" class="btn btn-ghost btn-sm">View all</a>
                </div>
                <?php if (!$reports): ?>
                    <div class="empty"><i class="fa-regular fa-face-smile"></i><p>No open issues in <?= e($area) ?>. Spot a problem? <a href="<?= url('report_new.php') ?>">Report it</a>.</p></div>
                <?php else: ?>
                    <ul class="list">
                        <?php foreach ($reports as $r): ?>
                            <li class="list-item">
                                <span class="list-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                                <div class="list-body">
                                    <a href="<?= url('report.php?id=' . $r['id']) ?>"><strong><?= e($r['title']) ?></strong></a>
                                    <div class="meta small muted">
                                        <span><?= e($r['issue_type']) ?></span>
                                        <span><i class="fa-solid fa-thumbs-up"></i> <?= $r['votes'] ?></span>
                                        <span><?= time_ago($r['created_at']) ?></span>
                                    </div>
                                </div>
                                <?= status_badge($r['status']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-calendar-days"></i> Community events</h3>
                    <a href="<?= url('events.php') ?>" class="btn btn-ghost btn-sm">View all</a>
                </div>
                <?php if (!$events): ?>
                    <div class="empty"><i class="fa-regular fa-calendar"></i><p>No active events yet. <a href="<?= url('event_new.php') ?>">Start one</a> and bring neighbours together.</p></div>
                <?php else: ?>
                    <ul class="list">
                        <?php foreach ($events as $ev): ?>
                            <li class="list-item">
                                <span class="list-icon"><i class="fa-solid <?= EVENT_CATEGORIES[$ev['category']] ?? 'fa-star' ?>"></i></span>
                                <div class="list-body">
                                    <a href="<?= url('event.php?id=' . $ev['id']) ?>"><strong><?= e($ev['title']) ?></strong></a>
                                    <div class="meta small muted">
                                        <span>by <?= e($ev['full_name']) ?></span>
                                        <?php if ($ev['event_date']): ?><span><i class="fa-regular fa-clock"></i> <?= fmt_date($ev['event_date'], 'd M, g:i A') ?></span><?php endif; ?>
                                        <span><i class="fa-solid fa-users"></i> <?= $ev['participants'] ?> joined</span>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <aside class="stack">
            <div class="card">
                <h3 class="card-title"><i class="fa-solid fa-award"></i> Community heroes</h3>
                <div class="list mt-2">
                    <div class="list-item">
                        <?= $best ? avatar($best, 'md') : '<span class="list-icon"><i class="fa-solid fa-medal"></i></span>' ?>
                        <div class="list-body">
                            <small class="muted">Best citizen in <?= e($area) ?></small>
                            <strong><?= $best ? e($best['full_name']) . ' · ' . $best['total_score'] . ' pts' : 'Be the first!' ?></strong>
                        </div>
                    </div>
                    <div class="list-item">
                        <?= $monthly ? avatar($monthly, 'md') : '<span class="list-icon"><i class="fa-solid fa-calendar-check"></i></span>' ?>
                        <div class="list-body">
                            <small class="muted">Citizen of the month (<?= date('F') ?>)</small>
                            <strong><?= $monthly ? e($monthly['full_name']) . ' · ' . $monthly['total_score'] . ' pts' : 'Not decided yet' ?></strong>
                        </div>
                    </div>
                </div>
                <a href="<?= url('leaderboard.php') ?>" class="btn btn-outline btn-sm btn-block mt-2">See leaderboard</a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-bell"></i> Latest updates</h3>
                    <a href="<?= url('notifications.php') ?>" class="btn btn-ghost btn-sm">All</a>
                </div>
                <?php if (!$notifications): ?>
                    <p class="muted mb-0">You're all caught up.</p>
                <?php else: ?>
                    <ul class="list">
                        <?php foreach ($notifications as $n): ?>
                            <li class="list-item">
                                <div class="list-body">
                                    <a href="<?= url('notifications.php?open=' . $n['id']) ?>" class="<?= $n['is_read'] ? 'muted' : '' ?>"><?= e($n['message']) ?></a>
                                    <div class="small muted"><?= time_ago($n['created_at']) ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3 class="card-title"><i class="fa-solid fa-user-shield"></i> Local representatives</h3>
                <?php if (!$representatives): ?>
                    <p class="muted mb-0 mt-1">No verified representatives in your area yet.</p>
                <?php else: ?>
                    <ul class="list mt-2">
                        <?php foreach ($representatives as $rep): ?>
                            <li class="list-item" style="align-items:center">
                                <?= avatar($rep, 'sm') ?>
                                <div class="list-body"><strong><?= e($rep['full_name']) ?><?= verified_tick($rep) ?></strong></div>
                                <a class="btn btn-outline btn-sm" href="<?= url('chat.php?with=' . $rep['id']) ?>"><i class="fa-solid fa-message"></i></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
