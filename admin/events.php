<?php
require_once __DIR__ . '/../includes/functions.php';
$admin = require_admin();

if (is_post()) {
    verify_csrf();
    $id = (int) input('event_id');
    $event = find_event($id);

    if ($event) {
        switch (input('action')) {
            case 'approve':
                if (in_array($event['status'], ['completed', 'verified'], true)) {
                    $pdo->prepare("UPDATE events SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?")->execute([$admin['id'], $id]);
                    notify((int) $event['user_id'], 'event', 'Great work! "' . $event['title'] . '" has been approved by the admin.', 'event.php?id=' . $id);
                    flash('success', 'Event approved.');
                }
                break;
            case 'reopen':
                $pdo->prepare("UPDATE events SET status = 'active', completed_at = NULL, verified_by = NULL, verified_at = NULL WHERE id = ?")->execute([$id]);
                notify((int) $event['user_id'], 'event', '"' . $event['title'] . '" was sent back to active. Please check with the admin before marking it complete again.', 'event.php?id=' . $id);
                flash('info', 'Event reopened.');
                break;
            case 'approve_fund':
                $pdo->prepare("UPDATE event_funds SET status = 'open', approved_at = NOW() WHERE event_id = ? AND status = 'pending'")->execute([$id]);
                notify((int) $event['user_id'], 'fund', 'Your fundraiser for "' . $event['title'] . '" was approved by the admin. Donations are open.', 'event.php?id=' . $id);
                flash('success', 'Fundraiser approved.');
                break;
            case 'reject_fund':
                $pdo->prepare("DELETE FROM event_funds WHERE event_id = ? AND status = 'pending'")->execute([$id]);
                notify((int) $event['user_id'], 'fund', 'Your fundraiser request for "' . $event['title'] . '" was not approved.', 'event.php?id=' . $id);
                flash('info', 'Fundraiser request rejected.');
                break;
            case 'delete':
                $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
                notify((int) $event['user_id'], 'event', 'Your event "' . $event['title'] . '" was removed by an administrator.');
                flash('success', 'Event deleted.');
                break;
        }
    }
    redirect_back('admin/events.php');
}

$tab = in_array(input('tab'), ['review', 'funds', 'active', 'approved'], true) ? input('tab') : 'review';
$where = [
    'review'   => "e.status IN ('completed','verified')",
    'funds'    => "f.status = 'pending'",
    'active'   => "e.status = 'active'",
    'approved' => "e.status = 'approved'",
][$tab];

$events = $pdo->query("SELECT e.*, u.full_name, v.full_name AS verifier, f.target_amount, f.purpose, f.status AS fund_status,
        (SELECT COUNT(*) FROM event_participants p WHERE p.event_id = e.id) AS participants,
        (SELECT COALESCE(SUM(amount), 0) FROM donations d WHERE d.event_id = e.id) AS raised
    FROM events e
    JOIN users u ON u.id = e.user_id
    LEFT JOIN users v ON v.id = e.verified_by
    LEFT JOIN event_funds f ON f.event_id = e.id
    WHERE $where
    ORDER BY e.status = 'verified' DESC, COALESCE(e.completed_at, e.created_at) DESC
    LIMIT 100")->fetchAll();

$counts = $pdo->query("SELECT
    (SELECT COUNT(*) FROM events WHERE status IN ('completed','verified')) AS review,
    (SELECT COUNT(*) FROM event_funds WHERE status = 'pending') AS funds,
    (SELECT COUNT(*) FROM events WHERE status = 'active') AS active,
    (SELECT COUNT(*) FROM events WHERE status = 'approved') AS approved")->fetch();

$pageTitle = 'Events & funds';
$adminNav = 'events';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="tabs">
    <a href="?tab=review" class="<?= $tab === 'review' ? 'active' : '' ?>">Completion review <span class="badge badge-warning"><?= $counts['review'] ?></span></a>
    <a href="?tab=funds" class="<?= $tab === 'funds' ? 'active' : '' ?>">Fundraiser requests <span class="badge badge-warning"><?= $counts['funds'] ?></span></a>
    <a href="?tab=active" class="<?= $tab === 'active' ? 'active' : '' ?>">Active <span class="badge badge-muted"><?= $counts['active'] ?></span></a>
    <a href="?tab=approved" class="<?= $tab === 'approved' ? 'active' : '' ?>">Approved <span class="badge badge-muted"><?= $counts['approved'] ?></span></a>
</div>

<?php if ($tab === 'review'): ?>
    <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i><span>Events marked "verified" were confirmed by a local representative. You can also approve "completed" events directly when an area has no representative.</span></div>
<?php endif; ?>

<div class="card card-flush">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Event</th><th>Organiser</th><th>Area</th><th>People</th><th>Funding</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td style="max-width:320px"><a href="<?= url('event.php?id=' . $ev['id']) ?>" target="_blank"><strong><?= e($ev['title']) ?></strong></a>
                        <br><small class="muted"><?= e($ev['category']) ?> · created <?= fmt_date($ev['created_at']) ?><?= $ev['completed_at'] ? ' · done ' . fmt_date($ev['completed_at']) : '' ?></small></td>
                    <td><a href="<?= url('admin/user.php?id=' . $ev['user_id']) ?>"><?= e($ev['full_name']) ?></a></td>
                    <td><?= e($ev['area']) ?></td>
                    <td><?= $ev['participants'] ?></td>
                    <td class="nowrap">
                        <?php if ($ev['target_amount']): ?>
                            <?= money($ev['raised']) ?> / <?= money($ev['target_amount']) ?> <?= status_badge($ev['fund_status']) ?>
                            <?php if ($ev['purpose']): ?><br><small class="muted"><?= e(excerpt($ev['purpose'], 40)) ?></small><?php endif; ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= status_badge($ev['status']) ?><?php if ($ev['verifier']): ?><br><small class="muted">by <?= e($ev['verifier']) ?></small><?php endif; ?></td>
                    <td class="actions">
                        <?php if ($tab === 'funds'): ?>
                            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $ev['id'] ?>"><input type="hidden" name="action" value="approve_fund">
                                <button class="btn btn-primary btn-sm">Approve fund</button></form>
                            <form method="post" class="inline-form" data-confirm="Reject this fundraiser request?"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $ev['id'] ?>"><input type="hidden" name="action" value="reject_fund">
                                <button class="btn btn-ghost btn-sm">Reject</button></form>
                        <?php elseif (in_array($ev['status'], ['completed', 'verified'], true)): ?>
                            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $ev['id'] ?>"><input type="hidden" name="action" value="approve">
                                <button class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i> Approve</button></form>
                            <form method="post" class="inline-form" data-confirm="Send this event back to active?"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $ev['id'] ?>"><input type="hidden" name="action" value="reopen">
                                <button class="btn btn-ghost btn-sm">Reopen</button></form>
                        <?php endif; ?>
                        <form method="post" class="inline-form" data-confirm="Delete this event permanently?"><?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $ev['id'] ?>"><input type="hidden" name="action" value="delete">
                            <button class="btn btn-danger-outline btn-sm" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$events): ?><tr><td colspan="7"><div class="empty"><i class="fa-regular fa-calendar-check"></i><p>Nothing here right now.</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
