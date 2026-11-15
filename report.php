<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$id = (int) input('id');
$stmt = $pdo->prepare('SELECT r.*, u.full_name, u.username, u.avatar, u.is_verified FROM reports r JOIN users u ON u.id = r.user_id WHERE r.id = ?');
$stmt->execute([$id]);
$report = $stmt->fetch();
if (!$report) {
    flash('error', 'That report could not be found.');
    redirect('reports.php');
}
$isOwner = (int) $report['user_id'] === (int) $user['id'];

if (is_post()) {
    verify_csrf();
    $action = input('action');

    if ($action === 'vote' && !$isOwner) {
        $intensity = in_array(input('intensity'), ['Low', 'Medium', 'High'], true) ? input('intensity') : 'Medium';
        $pdo->prepare('INSERT INTO report_votes (report_id, user_id, intensity) VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE intensity = VALUES(intensity)')
            ->execute([$id, $user['id'], $intensity]);
        flash('success', 'Thanks — your support has been recorded.');
    } elseif ($action === 'unvote') {
        $pdo->prepare('DELETE FROM report_votes WHERE report_id = ? AND user_id = ?')->execute([$id, $user['id']]);
        flash('info', 'Your vote was removed.');
    } elseif ($action === 'delete' && $isOwner && $report['status'] === 'Pending') {
        $pdo->prepare('DELETE FROM reports WHERE id = ?')->execute([$id]);
        delete_upload($report['photo']);
        flash('success', 'Your report was deleted.');
        redirect('reports.php?scope=mine');
    }
    redirect('report.php?id=' . $id);
}

$stmt = $pdo->prepare("SELECT intensity, COUNT(*) c FROM report_votes WHERE report_id = ? GROUP BY intensity");
$stmt->execute([$id]);
$tally = ['Low' => 0, 'Medium' => 0, 'High' => 0];
foreach ($stmt->fetchAll() as $row) {
    $tally[$row['intensity']] = (int) $row['c'];
}
$totalVotes = array_sum($tally);

$stmt = $pdo->prepare('SELECT intensity FROM report_votes WHERE report_id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);
$myVote = $stmt->fetchColumn();

$pageTitle = $report['title'];
$activeNav = 'reports';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?= url('reports.php') ?>">Reports</a> / #<?= $report['id'] ?></div>
        <h1><?= e($report['title']) ?></h1>
        <p><i class="fa-solid fa-location-dot"></i> <?= e($report['area']) ?>, <?= e($report['division']) ?><?= $report['address'] ? ' · ' . e($report['address']) : '' ?></p>
    </div>
</section>

<div class="container layout-sidebar">
    <div class="card">
        <?php if ($report['photo']): ?>
            <a href="<?= e(url($report['photo'])) ?>" target="_blank" rel="noopener"><img class="detail-photo" src="<?= e(url($report['photo'])) ?>" alt="Photo of the issue"></a>
        <?php endif; ?>
        <div class="row mb-2">
            <span class="badge badge-muted"><?= e($report['issue_type']) ?></span>
            <?= status_badge($report['status']) ?>
        </div>
        <div class="prose"><?= e($report['description']) ?></div>

        <?php if ($report['admin_note']): ?>
            <div class="alert alert-info mt-3 mb-0"><i class="fa-solid fa-shield-halved"></i>
                <span><strong>Update from the authority:</strong> <?= e($report['admin_note']) ?></span>
            </div>
        <?php endif; ?>

        <div class="row-between mt-3">
            <div class="user-cell">
                <?= avatar($report, 'md') ?>
                <div>
                    <strong><?= e($report['full_name']) ?><?= verified_tick($report) ?></strong>
                    <small>Reported <?= time_ago($report['created_at']) ?> · <?= fmt_date($report['created_at'], 'd M Y, g:i A') ?></small>
                </div>
            </div>
            <?php if ($isOwner && $report['status'] === 'Pending'): ?>
                <form method="post" data-confirm="Delete this report permanently?" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <button class="btn btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i> Delete</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <aside class="stack">
        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-people-group"></i> Community support</h3>
            <div class="stat-value mt-1"><?= $totalVotes ?></div>
            <p class="muted small">neighbour<?= $totalVotes == 1 ? '' : 's' ?> confirmed this issue</p>
            <?php foreach (['High' => 'danger', 'Medium' => 'warning', 'Low' => 'info'] as $level => $cls): ?>
                <div class="row-between small mt-1"><span><?= $level ?> urgency</span><span class="badge badge-<?= $cls ?>"><?= $tally[$level] ?></span></div>
                <div class="progress mt-1"><span style="width:<?= $totalVotes ? round($tally[$level] / $totalVotes * 100) : 0 ?>%"></span></div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <?php if ($isOwner): ?>
                <p class="muted mb-0"><i class="fa-solid fa-circle-info"></i> This is your report. Share it with neighbours so they can add their support.</p>
            <?php elseif (in_array($report['status'], ['Solved', 'Denied'], true)): ?>
                <p class="muted mb-0">This report is closed and no longer accepts votes.</p>
            <?php else: ?>
                <h3><?= $myVote ? 'Update your vote' : 'Does this affect you?' ?></h3>
                <form method="post" class="stack">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="vote">
                    <div class="chips-select">
                        <?php foreach (['Low', 'Medium', 'High'] as $level): ?>
                            <label><input type="radio" name="intensity" value="<?= $level ?>" <?= checked(($myVote ?: 'Medium') === $level) ?>><span><?= $level ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-primary btn-block"><i class="fa-solid fa-thumbs-up"></i> <?= $myVote ? 'Update vote' : 'Support this report' ?></button>
                </form>
                <?php if ($myVote): ?>
                    <form method="post" class="mt-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="unvote">
                        <button class="btn btn-ghost btn-sm btn-block">Remove my vote</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
