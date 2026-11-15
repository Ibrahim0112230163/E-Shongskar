<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$view = input('view') === 'all' ? 'all' : 'mine';

if ($view === 'mine') {
    $active = active_alerts($user);
    $stmt = $pdo->prepare("SELECT * FROM alerts
        WHERE end_date < CURDATE() AND end_date >= CURDATE() - INTERVAL 30 DAY
          AND (division IS NULL OR (division = ? AND (area IS NULL OR area = ?)))
        ORDER BY end_date DESC");
    $stmt->execute([$user['division'], $user['area']]);
    $past = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT * FROM alerts WHERE start_date > CURDATE()
          AND (division IS NULL OR (division = ? AND (area IS NULL OR area = ?))) ORDER BY start_date");
    $stmt->execute([$user['division'], $user['area']]);
    $upcoming = $stmt->fetchAll();
} else {
    $active = $pdo->query("SELECT * FROM alerts WHERE CURDATE() BETWEEN start_date AND end_date
        ORDER BY FIELD(severity,'critical','warning','info'), division, area")->fetchAll();
    $upcoming = $pdo->query('SELECT * FROM alerts WHERE start_date > CURDATE() ORDER BY start_date')->fetchAll();
    $past = [];
}

function alert_scope(array $a): string
{
    if (!$a['division']) return 'All of Bangladesh';
    return $a['area'] ? $a['area'] . ', ' . $a['division'] : 'All of ' . $a['division'] . ' division';
}

function alert_card(array $a, bool $faded = false): string
{
    ob_start(); ?>
    <article class="card alert-card <?= e($a['severity']) ?>" <?= $faded ? 'style="opacity:.7"' : '' ?>>
        <div class="row-between mb-1">
            <?= severity_badge($a['severity']) ?>
            <span class="chip"><i class="fa-regular fa-calendar"></i> <?= fmt_date($a['start_date'], 'd M') ?> – <?= fmt_date($a['end_date'], 'd M Y') ?></span>
        </div>
        <h3><?= e($a['title']) ?></h3>
        <p class="prose mb-1"><?= e($a['description']) ?></p>
        <span class="chip"><i class="fa-solid fa-location-dot"></i> <?= e(alert_scope($a)) ?></span>
    </article>
    <?php return ob_get_clean();
}

$pageTitle = 'Public alerts';
$activeNav = 'alerts';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>Public awareness alerts</h1>
        <p>Official notices from the authority — floods, fires, road closures, outbreaks and other warnings.</p>
    </div>
</section>

<div class="container container-narrow">
    <div class="card mb-3" style="padding:8px 22px 0">
        <div class="tabs mb-0" style="border:0">
            <a href="?view=mine" class="<?= $view === 'mine' ? 'active' : '' ?>"><i class="fa-solid fa-location-dot"></i> For <?= e($user['area']) ?></a>
            <a href="?view=all" class="<?= $view === 'all' ? 'active' : '' ?>"><i class="fa-solid fa-earth-asia"></i> All active alerts</a>
        </div>
    </div>

    <h2>In effect now <span class="badge badge-<?= $active ? 'danger' : 'muted' ?>"><?= count($active) ?></span></h2>
    <div class="stack">
        <?php foreach ($active as $a) echo alert_card($a); ?>
        <?php if (!$active): ?>
            <div class="card empty"><i class="fa-solid fa-shield-heart"></i><h3>All clear</h3><p>There are no active alerts<?= $view === 'mine' ? ' for your area' : '' ?>. Stay safe!</p></div>
        <?php endif; ?>
    </div>

    <?php if ($upcoming): ?>
        <h2 class="mt-4">Upcoming</h2>
        <div class="stack"><?php foreach ($upcoming as $a) echo alert_card($a); ?></div>
    <?php endif; ?>

    <?php if ($past): ?>
        <h2 class="mt-4">Ended in the last 30 days</h2>
        <div class="stack"><?php foreach ($past as $a) echo alert_card($a, true); ?></div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
