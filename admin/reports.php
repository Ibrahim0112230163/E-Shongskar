<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $id = (int) input('report_id');
    $stmt = $pdo->prepare('SELECT * FROM reports WHERE id = ?');
    $stmt->execute([$id]);
    $report = $stmt->fetch();

    if ($report && input('action') === 'update') {
        $status = in_array(input('status'), REPORT_STATUSES, true) ? input('status') : $report['status'];
        $note = mb_substr(input('admin_note'), 0, 500) ?: null;
        $pdo->prepare('UPDATE reports SET status = ?, admin_note = ? WHERE id = ?')->execute([$status, $note, $id]);

        if ($status !== $report['status']) {
            $msg = 'Your report "' . $report['title'] . '" is now ' . $status . '.';
            notify((int) $report['user_id'], 'report', $msg, 'report.php?id=' . $id);
            $voters = $pdo->prepare('SELECT user_id FROM report_votes WHERE report_id = ?');
            $voters->execute([$id]);
            foreach ($voters->fetchAll(PDO::FETCH_COLUMN) as $voterId) {
                notify((int) $voterId, 'report', 'A report you supported, "' . $report['title'] . '", is now ' . $status . '.', 'report.php?id=' . $id);
            }
        }
        flash('success', 'Report #' . $id . ' updated.');
    } elseif ($report && input('action') === 'delete') {
        $pdo->prepare('DELETE FROM reports WHERE id = ?')->execute([$id]);
        delete_upload($report['photo']);
        notify((int) $report['user_id'], 'report', 'Your report "' . $report['title'] . '" was removed by an administrator.');
        flash('success', 'Report #' . $id . ' deleted.');
    }
    redirect_back('admin/reports.php');
}

$status = in_array(input('status'), REPORT_STATUSES, true) ? input('status') : '';
$type = in_array(input('type'), ISSUE_TYPES, true) ? input('type') : '';
$area = input('area');
$q = input('q');
$sort = input('sort') === 'votes' ? 'votes' : 'newest';

$where = [];
$params = [];
if ($status) { $where[] = 'r.status = ?';     $params[] = $status; }
if ($type)   { $where[] = 'r.issue_type = ?'; $params[] = $type; }
if ($area)   { $where[] = 'r.area = ?';       $params[] = $area; }
if ($q !== '') {
    $where[] = '(r.id = ? OR r.title LIKE ? OR u.username LIKE ?)';
    array_push($params, (int) $q, "%$q%", "%$q%");
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count = $pdo->prepare("SELECT COUNT(*) FROM reports r JOIN users u ON u.id = r.user_id $whereSql");
$count->execute($params);
$p = paginate((int) $count->fetchColumn(), 15);

$order = $sort === 'votes' ? 'votes DESC, r.created_at DESC' : 'r.created_at DESC';
$stmt = $pdo->prepare("SELECT r.*, u.full_name, u.username, u.phone,
        (SELECT COUNT(*) FROM report_votes v WHERE v.report_id = r.id) AS votes,
        (SELECT COUNT(*) FROM report_votes v WHERE v.report_id = r.id AND v.intensity = 'High') AS urgent
    FROM reports r JOIN users u ON u.id = r.user_id
    $whereSql ORDER BY $order LIMIT {$p['limit']} OFFSET {$p['offset']}");
$stmt->execute($params);
$reports = $stmt->fetchAll();

$areas = $pdo->query('SELECT DISTINCT area FROM reports ORDER BY area')->fetchAll(PDO::FETCH_COLUMN);
$counts = $pdo->query('SELECT status, COUNT(*) FROM reports GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Reports';
$adminNav = 'reports';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="tabs">
    <a href="?" class="<?= $status === '' ? 'active' : '' ?>">All <span class="badge badge-muted"><?= array_sum($counts) ?></span></a>
    <?php foreach (REPORT_STATUSES as $st): ?>
        <a href="?status=<?= urlencode($st) ?>" class="<?= $status === $st ? 'active' : '' ?>"><?= $st ?> <span class="badge badge-muted"><?= $counts[$st] ?? 0 ?></span></a>
    <?php endforeach; ?>
</div>

<div class="card mb-3">
    <form class="filters" method="get">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <div class="field"><label for="q">Search</label><input class="input" id="q" name="q" value="<?= e($q) ?>" placeholder="ID, title or username"></div>
        <div class="field"><label for="type">Type</label>
            <select class="select" id="type" name="type"><option value="">All types</option>
                <?php foreach (ISSUE_TYPES as $t): ?><option <?= selected($t, $type) ?>><?= $t ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="area">Area</label>
            <select class="select" id="area" name="area"><option value="">All areas</option>
                <?php foreach ($areas as $a): ?><option <?= selected($a, $area) ?>><?= e($a) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="sort">Sort</label>
            <select class="select" id="sort" name="sort"><option value="newest">Newest</option><option value="votes" <?= selected('votes', $sort) ?>>Most votes</option></select></div>
        <button class="btn btn-primary">Filter</button>
        <a class="btn btn-ghost" href="<?= url('admin/reports.php') ?>">Reset</a>
    </form>
</div>

<div class="card card-flush">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>Report</th><th>Reporter</th><th>Area</th><th>Support</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td class="muted">#<?= $r['id'] ?></td>
                    <td style="max-width:360px">
                        <a href="<?= url('report.php?id=' . $r['id']) ?>" target="_blank"><strong><?= e($r['title']) ?></strong></a>
                        <?php if ($r['photo']): ?> <i class="fa-solid fa-image muted" title="Has photo"></i><?php endif; ?>
                        <br><small class="muted"><?= e($r['issue_type']) ?> · <?= fmt_date($r['created_at'], 'd M Y, g:i A') ?></small>
                        <div class="small"><?= e(excerpt($r['description'], 110)) ?></div>
                    </td>
                    <td><a href="<?= url('admin/user.php?id=' . $r['user_id']) ?>"><?= e($r['full_name']) ?></a><br><small class="muted"><?= e($r['phone']) ?></small></td>
                    <td><?= e($r['area']) ?><br><small class="muted"><?= e($r['address']) ?></small></td>
                    <td class="nowrap"><?= $r['votes'] ?> <?php if ($r['urgent']): ?><span class="badge badge-danger"><?= $r['urgent'] ?> urgent</span><?php endif; ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="actions">
                        <button class="btn btn-outline btn-sm" data-open="updateModal"
                                data-fill-report-id="<?= $r['id'] ?>" data-fill-title="<?= e($r['title']) ?>"
                                data-fill-status="<?= e($r['status']) ?>" data-fill-note="<?= e($r['admin_note']) ?>">
                            <i class="fa-solid fa-pen"></i> Update</button>
                        <form method="post" class="inline-form" data-confirm="Delete report #<?= $r['id'] ?> permanently?">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                            <button class="btn btn-danger-outline btn-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$reports): ?><tr><td colspan="7"><div class="empty"><i class="fa-regular fa-folder-open"></i><p>No reports match.</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= pagination_links($p) ?>

<dialog class="modal" id="updateModal">
    <form method="post">
        <div class="modal-head"><h3>Update report</h3><button type="button" class="modal-close" data-close>&times;</button></div>
        <div class="modal-body stack">
            <?= csrf_field() ?><input type="hidden" name="action" value="update">
            <input type="hidden" name="report_id" data-field="reportId">
            <p class="mb-0"><strong data-field="title"></strong></p>
            <div class="field"><label for="m_status">Status</label>
                <select class="select" id="m_status" name="status" data-field="status">
                    <?php foreach (REPORT_STATUSES as $st): ?><option><?= $st ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="m_note">Note to the community (optional)</label>
                <textarea class="textarea" id="m_note" name="admin_note" rows="3" maxlength="500" data-field="note" placeholder="e.g. City corporation has scheduled repairs for next week."></textarea></div>
            <p class="small muted mb-0">The reporter and everyone who supported this report will be notified of a status change.</p>
            <div class="form-actions"><button type="button" class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary">Save</button></div>
        </div>
    </form>
</dialog>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
