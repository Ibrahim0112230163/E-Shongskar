<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

if (is_post()) {
    verify_csrf();
    $id = (int) input('event_id');
    $event = find_event($id);
    if ($event && $user['is_verified'] && $event['status'] === 'completed'
        && (int) $event['user_id'] !== (int) $user['id'] && strcasecmp($event['area'], $user['area']) === 0) {
        $pdo->prepare("UPDATE events SET status = 'verified', verified_by = ?, verified_at = NOW() WHERE id = ?")->execute([$user['id'], $id]);
        notify((int) $event['user_id'], 'event', '"' . $event['title'] . '" was verified by ' . $user['full_name'] . '.', 'event.php?id=' . $id);
        flash('success', 'Event verified. An admin will give the final approval.');
    }
    redirect('completed_events.php');
}

$stmt = $pdo->prepare("SELECT e.*, u.full_name, u.avatar, u.is_verified, v.full_name AS verifier,
        (SELECT COUNT(*) FROM event_participants p WHERE p.event_id = e.id) AS participants
    FROM events e
    JOIN users u ON u.id = e.user_id
    LEFT JOIN users v ON v.id = e.verified_by
    WHERE e.area = ? AND e.status IN ('completed','verified','approved')
    ORDER BY e.status = 'completed' DESC, e.completed_at DESC
    LIMIT 50");
$stmt->execute([$user['area']]);
$events = $stmt->fetchAll();

$pageTitle = 'Completed events';
$activeNav = 'events';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?= url('events.php') ?>">Events</a> / Completed</div>
        <h1>Completed community work</h1>
        <p>Each finished event is confirmed by a verified local representative and then approved by an admin.</p>
    </div>
</section>

<div class="container">
    <?php if ($user['is_verified']): ?>
        <div class="alert alert-info"><i class="fa-solid fa-user-shield"></i><span>You are a verified representative for <?= e($user['area']) ?>. Please verify events only after checking that the work was really done.</span></div>
    <?php endif; ?>

    <div class="card card-flush">
        <?php if (!$events): ?>
            <div class="empty"><i class="fa-solid fa-flag-checkered"></i><h3>Nothing completed yet</h3><p>Finished events from <?= e($user['area']) ?> will appear here.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Event</th><th>Organiser</th><th>Completed</th><th>People</th><th>Progress</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td><a href="<?= url('event.php?id=' . $ev['id']) ?>"><strong><?= e($ev['title']) ?></strong></a><br><small class="muted"><?= e($ev['category']) ?></small></td>
                            <td><div class="user-cell"><?= avatar($ev, 'xs') ?><?= e($ev['full_name']) ?></div></td>
                            <td class="nowrap"><?= fmt_date($ev['completed_at']) ?></td>
                            <td><?= $ev['participants'] ?></td>
                            <td>
                                <?= status_badge($ev['status']) ?>
                                <?php if ($ev['verifier']): ?><br><small class="muted">by <?= e($ev['verifier']) ?></small><?php endif; ?>
                            </td>
                            <td class="actions">
                                <?php if ($ev['status'] === 'completed' && $user['is_verified'] && (int) $ev['user_id'] !== (int) $user['id']): ?>
                                    <form method="post" class="inline-form" data-confirm="Confirm that this work was really done?">
                                        <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                        <button class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i> Verify</button>
                                    </form>
                                <?php elseif ($ev['status'] === 'completed'): ?>
                                    <span class="muted small">Awaiting a representative</span>
                                <?php elseif ($ev['status'] === 'verified'): ?>
                                    <span class="muted small">Awaiting admin</span>
                                <?php else: ?>
                                    <i class="fa-solid fa-circle-check text-success" title="Approved"></i>
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

<?php include __DIR__ . '/includes/footer.php'; ?>
