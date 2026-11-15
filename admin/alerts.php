<?php
require_once __DIR__ . '/../includes/functions.php';
$admin = require_admin();
$errors = [];

if (is_post()) {
    verify_csrf();
    if (input('action') === 'delete') {
        $pdo->prepare('DELETE FROM alerts WHERE id = ?')->execute([(int) input('alert_id')]);
        flash('success', 'Alert deleted.');
        redirect('admin/alerts.php');
    }

    $title = input('title');
    $description = input('description');
    $severity = in_array(input('severity'), ['info', 'warning', 'critical'], true) ? input('severity') : 'warning';
    $division = in_array(input('division'), DIVISIONS, true) ? input('division') : null;
    $area = $division && input('area') !== '' ? normalize_area(input('area')) : null;
    $start = input('start_date') ?: date('Y-m-d');
    $end = input('end_date') ?: $start;

    if (mb_strlen($title) < 3)       $errors[] = 'Please enter a title.';
    if (mb_strlen($description) < 5) $errors[] = 'Please enter a description.';
    if (!strtotime($start) || !strtotime($end) || $end < $start) $errors[] = 'The end date must be on or after the start date.';

    if (!$errors) {
        $pdo->prepare('INSERT INTO alerts (admin_id, title, description, severity, division, area, start_date, end_date) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$admin['id'], $title, $description, $severity, $division, $area, $start, $end]);

        // Tell the affected people right away if the alert is already in effect
        if ($start <= date('Y-m-d')) {
            $sql = 'INSERT INTO notifications (user_id, type, message, link) SELECT id, ?, ?, ? FROM users';
            $params = ['alert', ucfirst($severity) . ' alert: ' . $title, 'alerts.php'];
            if ($division) { $sql .= ' WHERE division = ?'; $params[] = $division; }
            if ($area)     { $sql .= ' AND area = ?';       $params[] = $area; }
            $pdo->prepare($sql)->execute($params);
        }
        flash('success', 'Alert published.');
        redirect('admin/alerts.php');
    }
}

$alerts = $pdo->query("SELECT a.*, ad.full_name AS author,
        CASE WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active' WHEN start_date > CURDATE() THEN 'upcoming' ELSE 'ended' END AS state
    FROM alerts a LEFT JOIN admins ad ON ad.id = a.admin_id
    ORDER BY state = 'active' DESC, state = 'upcoming' DESC, a.start_date DESC LIMIT 100")->fetchAll();
$areas = $pdo->query('SELECT DISTINCT area FROM users ORDER BY area')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Alerts';
$adminNav = 'alerts';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="layout-sidebar" style="grid-template-columns:minmax(0,1fr) 380px">
    <div class="card card-flush">
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Alert</th><th>Scope</th><th>Dates</th><th>State</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($alerts as $a): ?>
                    <tr>
                        <td style="max-width:340px"><?= severity_badge($a['severity']) ?> <strong><?= e($a['title']) ?></strong>
                            <div class="small muted"><?= e(excerpt($a['description'], 120)) ?></div></td>
                        <td><?= $a['division'] ? e(($a['area'] ? $a['area'] . ', ' : 'All of ') . $a['division']) : 'Nationwide' ?></td>
                        <td class="nowrap"><?= fmt_date($a['start_date'], 'd M') ?> – <?= fmt_date($a['end_date'], 'd M Y') ?></td>
                        <td><span class="badge badge-<?= ['active' => 'success', 'upcoming' => 'info', 'ended' => 'muted'][$a['state']] ?>"><?= ucfirst($a['state']) ?></span></td>
                        <td class="actions"><form method="post" class="inline-form" data-confirm="Delete this alert?"><?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete"><input type="hidden" name="alert_id" value="<?= $a['id'] ?>">
                            <button class="btn btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i></button></form></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$alerts): ?><tr><td colspan="5"><div class="empty"><i class="fa-regular fa-bell"></i><p>No alerts published yet.</p></div></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3 class="card-title"><i class="fa-solid fa-bullhorn"></i> Publish an alert</h3>
        <?php if ($errors): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= implode('<br>', array_map('e', $errors)) ?></span></div><?php endif; ?>
        <form method="post" class="stack mt-2">
            <?= csrf_field() ?><input type="hidden" name="action" value="create">
            <div class="field"><label for="title">Title</label><input class="input" id="title" name="title" maxlength="150" value="<?= old('title') ?>" required></div>
            <div class="field"><label for="description">Description</label><textarea class="textarea" id="description" name="description" rows="4" required><?= old('description') ?></textarea></div>
            <div class="field"><label for="severity">Severity</label>
                <select class="select" id="severity" name="severity">
                    <?php foreach (['info' => 'Information', 'warning' => 'Warning', 'critical' => 'Critical'] as $k => $v): ?>
                        <option value="<?= $k ?>" <?= selected($k, $_POST['severity'] ?? 'warning') ?>><?= $v ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="division">Division</label>
                <select class="select" id="division" name="division"><option value="">Whole country</option>
                    <?php foreach (DIVISIONS as $d): ?><option <?= selected($d, $_POST['division'] ?? '') ?>><?= $d ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="area">Area (optional)</label>
                <input class="input" id="area" name="area" list="areaList" value="<?= old('area') ?>" placeholder="Leave blank for the whole division">
                <datalist id="areaList"><?php foreach ($areas as $a): ?><option value="<?= e($a) ?>"><?php endforeach; ?></datalist></div>
            <div class="grid grid-2" style="gap:12px">
                <div class="field"><label for="start_date">From</label><input class="input" type="date" id="start_date" name="start_date" value="<?= old('start_date', date('Y-m-d')) ?>" required></div>
                <div class="field"><label for="end_date">Until</label><input class="input" type="date" id="end_date" name="end_date" value="<?= old('end_date', date('Y-m-d', strtotime('+2 days'))) ?>" required></div>
            </div>
            <button class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Publish alert</button>
            <p class="small muted mb-0">People in the selected scope are notified immediately if the alert starts today.</p>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
