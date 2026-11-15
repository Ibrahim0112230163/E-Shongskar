<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $id = (int) input('sos_id');
    $stmt = $pdo->prepare("UPDATE sos_requests SET status = 'resolved', resolved_at = NOW() WHERE id = ? AND status = 'open'");
    $stmt->execute([$id]);
    if ($stmt->rowCount()) {
        $owner = $pdo->prepare('SELECT user_id FROM sos_requests WHERE id = ?');
        $owner->execute([$id]);
        notify((int) $owner->fetchColumn(), 'sos', 'Your SOS has been marked as handled by the admin team. Stay safe.', 'emergency.php#sos');
        flash('success', 'SOS marked as resolved.');
    }
    redirect('admin/sos.php');
}

$status = input('status') === 'resolved' ? 'resolved' : 'open';
$stmt = $pdo->prepare("SELECT s.*, u.full_name, u.username, u.phone, u.street, u.house_no, u.division
    FROM sos_requests s JOIN users u ON u.id = s.user_id WHERE s.status = ? ORDER BY s.created_at DESC LIMIT 100");
$stmt->execute([$status]);
$items = $stmt->fetchAll();

$pageTitle = 'SOS requests';
$adminNav = 'sos';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="tabs">
    <a href="?status=open" class="<?= $status === 'open' ? 'active' : '' ?>">Open</a>
    <a href="?status=resolved" class="<?= $status === 'resolved' ? 'active' : '' ?>">Resolved</a>
</div>

<div class="card card-flush">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>When</th><th>Person</th><th>Address</th><th>Message</th><th>Location</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($items as $s): ?>
                <tr>
                    <td class="nowrap"><strong><?= fmt_date($s['created_at'], 'd M, g:i A') ?></strong><br><small class="muted"><?= time_ago($s['created_at']) ?></small></td>
                    <td><a href="<?= url('admin/user.php?id=' . $s['user_id']) ?>"><?= e($s['full_name']) ?></a><br>
                        <a href="tel:<?= e($s['phone']) ?>"><i class="fa-solid fa-phone"></i> <?= e($s['phone']) ?></a></td>
                    <td><?= e(trim(($s['house_no'] ? 'House ' . $s['house_no'] . ', ' : '') . ($s['street'] ? $s['street'] . ', ' : '') . $s['area'] . ', ' . $s['division'])) ?></td>
                    <td><?= e($s['message'] ?: '—') ?></td>
                    <td><?php if ($s['latitude'] !== null): ?>
                            <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="https://maps.google.com/?q=<?= e($s['latitude']) ?>,<?= e($s['longitude']) ?>"><i class="fa-solid fa-map-location-dot"></i> Map</a>
                        <?php else: ?><span class="muted small">Not shared</span><?php endif; ?></td>
                    <td class="actions">
                        <?php if ($s['status'] === 'open'): ?>
                            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="sos_id" value="<?= $s['id'] ?>">
                                <button class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i> Resolved</button></form>
                        <?php else: ?>
                            <small class="muted"><?= fmt_date($s['resolved_at'], 'd M, g:i A') ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="6"><div class="empty"><i class="fa-solid fa-shield-heart"></i><p>No <?= $status ?> SOS requests.</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
