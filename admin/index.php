<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$s = $pdo->query("SELECT
    (SELECT COUNT(*) FROM users) AS users,
    (SELECT COUNT(*) FROM users WHERE is_verified = 1) AS reps,
    (SELECT COUNT(*) FROM users WHERE created_at >= CURDATE() - INTERVAL 7 DAY) AS new_users,
    (SELECT COUNT(*) FROM reports WHERE status IN ('Pending','In Progress')) AS open_reports,
    (SELECT COUNT(*) FROM reports WHERE status = 'Solved') AS solved_reports,
    (SELECT COUNT(*) FROM events WHERE status = 'active') AS active_events,
    (SELECT COUNT(*) FROM events WHERE status IN ('completed','verified')) AS awaiting_events,
    (SELECT COUNT(*) FROM sos_requests WHERE status = 'open') AS open_sos,
    (SELECT COUNT(*) FROM blood_donors) AS donors,
    (SELECT COALESCE(SUM(amount), 0) FROM donations) AS donated")->fetch();

// Reports per day for the last 14 days
$daily = $pdo->query("SELECT DATE(created_at) d, COUNT(*) c FROM reports WHERE created_at >= CURDATE() - INTERVAL 13 DAY GROUP BY d")->fetchAll(PDO::FETCH_KEY_PAIR);
$labels = $values = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d M', strtotime($d));
    $values[] = (int) ($daily[$d] ?? 0);
}
$byType = $pdo->query('SELECT issue_type, COUNT(*) c FROM reports GROUP BY issue_type ORDER BY c DESC LIMIT 8')->fetchAll(PDO::FETCH_KEY_PAIR);
$byArea = $pdo->query("SELECT area, COUNT(*) c FROM reports WHERE status IN ('Pending','In Progress') GROUP BY area ORDER BY c DESC LIMIT 6")->fetchAll();

$recent = $pdo->query("SELECT r.id, r.title, r.issue_type, r.area, r.status, r.created_at, u.full_name,
        (SELECT COUNT(*) FROM report_votes v WHERE v.report_id = r.id) AS votes
    FROM reports r JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC LIMIT 6")->fetchAll();
$sos = $pdo->query("SELECT s.*, u.full_name, u.phone FROM sos_requests s JOIN users u ON u.id = s.user_id WHERE s.status = 'open' ORDER BY s.created_at DESC LIMIT 5")->fetchAll();
$top = leaderboard(null, 5);

$pageTitle = 'Dashboard';
$adminNav = 'dashboard';
include __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($sos): ?>
    <div class="alert alert-error"><i class="fa-solid fa-tower-broadcast"></i>
        <span><strong><?= count($sos) ?> open SOS request<?= count($sos) > 1 ? 's' : '' ?>.</strong> <a href="<?= url('admin/sos.php') ?>">Review now</a></span></div>
<?php endif; ?>

<div class="grid grid-4 mb-3">
    <div class="stat"><div class="stat-icon blue"><i class="fa-solid fa-users"></i></div><div><div class="stat-value"><?= $s['users'] ?></div><div class="stat-label">Citizens (+<?= $s['new_users'] ?> this week)</div></div></div>
    <div class="stat"><div class="stat-icon amber"><i class="fa-solid fa-flag"></i></div><div><div class="stat-value"><?= $s['open_reports'] ?></div><div class="stat-label">Open reports</div></div></div>
    <div class="stat"><div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div><div><div class="stat-value"><?= $s['solved_reports'] ?></div><div class="stat-label">Solved reports</div></div></div>
    <div class="stat"><div class="stat-icon red"><i class="fa-solid fa-tower-broadcast"></i></div><div><div class="stat-value"><?= $s['open_sos'] ?></div><div class="stat-label">Open SOS</div></div></div>
    <div class="stat"><div class="stat-icon green"><i class="fa-solid fa-user-shield"></i></div><div><div class="stat-value"><?= $s['reps'] ?></div><div class="stat-label">Verified representatives</div></div></div>
    <div class="stat"><div class="stat-icon blue"><i class="fa-solid fa-calendar-days"></i></div><div><div class="stat-value"><?= $s['active_events'] ?></div><div class="stat-label">Active events (<?= $s['awaiting_events'] ?> to review)</div></div></div>
    <div class="stat"><div class="stat-icon red"><i class="fa-solid fa-droplet"></i></div><div><div class="stat-value"><?= $s['donors'] ?></div><div class="stat-label">Blood donors</div></div></div>
    <div class="stat"><div class="stat-icon green"><i class="fa-solid fa-hand-holding-dollar"></i></div><div><div class="stat-value"><?= money($s['donated']) ?></div><div class="stat-label">Donations recorded</div></div></div>
</div>

<div class="grid grid-2 mb-3">
    <div class="card">
        <h3 class="card-title"><i class="fa-solid fa-chart-line"></i> Reports — last 14 days</h3>
        <div class="chart-box"><canvas id="dailyChart"></canvas></div>
    </div>
    <div class="card">
        <h3 class="card-title"><i class="fa-solid fa-chart-pie"></i> Reports by type</h3>
        <div class="chart-box"><canvas id="typeChart"></canvas></div>
    </div>
</div>

<div class="layout-sidebar">
    <div class="card card-flush">
        <div class="card-header" style="padding:18px 22px 0"><h3 class="card-title"><i class="fa-solid fa-clock"></i> Latest reports</h3><a class="btn btn-ghost btn-sm" href="<?= url('admin/reports.php') ?>">Manage</a></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Report</th><th>Area</th><th>Votes</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><a href="<?= url('admin/reports.php?q=' . $r['id']) ?>"><strong><?= e($r['title']) ?></strong></a><br><small class="muted"><?= e($r['issue_type']) ?> · <?= e($r['full_name']) ?> · <?= time_ago($r['created_at']) ?></small></td>
                        <td><?= e($r['area']) ?></td>
                        <td><?= $r['votes'] ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?><tr><td colspan="4" class="muted text-center">No reports yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="stack">
        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-map-location-dot"></i> Hotspots</h3>
            <?php if (!$byArea): ?><p class="muted mb-0 mt-1">No open reports.</p><?php endif; ?>
            <ul class="list mt-2">
                <?php foreach ($byArea as $a): ?>
                    <li class="list-item"><div class="list-body"><strong><?= e($a['area']) ?></strong></div><span class="badge badge-warning"><?= $a['c'] ?> open</span></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-trophy"></i> Top contributors</h3>
            <?php if (!$top): ?><p class="muted mb-0 mt-1">No activity yet.</p><?php endif; ?>
            <ul class="list mt-2">
                <?php foreach ($top as $i => $t): ?>
                    <li class="list-item" style="align-items:center"><span class="rank"><?= $i + 1 ?></span><?= avatar($t, 'sm') ?>
                        <div class="list-body"><a href="<?= url('admin/user.php?id=' . $t['id']) ?>"><strong><?= e($t['full_name']) ?></strong></a><small class="muted"><?= e($t['area']) ?></small></div>
                        <strong><?= $t['total_score'] ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php
$pageScripts = '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
if (window.Chart) {
    Chart.defaults.font.family = "Inter, sans-serif";
    new Chart(document.getElementById("dailyChart"), {
        type: "line",
        data: { labels: ' . json_encode($labels) . ', datasets: [{ label: "Reports", data: ' . json_encode($values) . ',
            borderColor: "#2f7d5c", backgroundColor: "rgba(47,125,92,.12)", fill: true, tension: .35, pointRadius: 3 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
    new Chart(document.getElementById("typeChart"), {
        type: "doughnut",
        data: { labels: ' . json_encode(array_keys($byType)) . ', datasets: [{ data: ' . json_encode(array_values(array_map('intval', $byType))) . ',
            backgroundColor: ["#1b4b32","#2f7d5c","#4caf50","#a1cdbe","#b7791f","#2b6cb0","#c53030","#6b7873"] }] },
        options: { maintainAspectRatio: false, plugins: { legend: { position: "right" } } }
    });
}
</script>';
include __DIR__ . '/../includes/admin_footer.php';
