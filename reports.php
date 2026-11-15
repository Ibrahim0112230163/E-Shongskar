<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$scope  = input('scope') === 'mine' ? 'mine' : 'area';
$type   = in_array(input('type'), ISSUE_TYPES, true) ? input('type') : '';
$status = in_array(input('status'), REPORT_STATUSES, true) ? input('status') : '';
$sort   = input('sort') === 'newest' ? 'newest' : 'votes';

$where = [$scope === 'mine' ? 'r.user_id = ?' : 'r.area = ?'];
$params = [$scope === 'mine' ? $user['id'] : $user['area']];
if ($type)   { $where[] = 'r.issue_type = ?'; $params[] = $type; }
if ($status) { $where[] = 'r.status = ?';     $params[] = $status; }
$whereSql = 'WHERE ' . implode(' AND ', $where);

$count = $pdo->prepare("SELECT COUNT(*) FROM reports r $whereSql");
$count->execute($params);
$p = paginate((int) $count->fetchColumn(), 12);

$order = $sort === 'newest' ? 'r.created_at DESC' : "votes DESC, FIELD(r.status,'Pending','In Progress','Solved','Denied'), r.created_at DESC";
$stmt = $pdo->prepare("SELECT r.*, u.full_name, u.username, u.avatar,
        (SELECT COUNT(*) FROM report_votes v WHERE v.report_id = r.id) AS votes,
        (SELECT COUNT(*) FROM report_votes v WHERE v.report_id = r.id AND v.intensity = 'High') AS high_votes,
        EXISTS(SELECT 1 FROM report_votes v WHERE v.report_id = r.id AND v.user_id = ?) AS my_vote
    FROM reports r JOIN users u ON u.id = r.user_id
    $whereSql ORDER BY $order LIMIT {$p['limit']} OFFSET {$p['offset']}");
$stmt->execute(array_merge([$user['id']], $params));
$reports = $stmt->fetchAll();

$pageTitle = 'Issue reports';
$activeNav = 'reports';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container row-between">
        <div>
            <h1>Issue reports</h1>
            <p>Problems reported by people in <?= e($user['area']) ?>. Vote on the issues that affect you so the most urgent ones get fixed first.</p>
        </div>
        <a href="<?= url('report_new.php') ?>" class="btn btn-accent"><i class="fa-solid fa-plus"></i> New report</a>
    </div>
</section>

<div class="container">
    <div class="card mb-3">
        <div class="tabs">
            <a href="?scope=area" class="<?= $scope === 'area' ? 'active' : '' ?>"><i class="fa-solid fa-location-dot"></i> My area</a>
            <a href="?scope=mine" class="<?= $scope === 'mine' ? 'active' : '' ?>"><i class="fa-solid fa-user"></i> My reports</a>
        </div>
        <form class="filters" method="get">
            <input type="hidden" name="scope" value="<?= $scope ?>">
            <div class="field">
                <label for="type">Issue type</label>
                <select class="select" name="type" id="type" data-autosubmit>
                    <option value="">All types</option>
                    <?php foreach (ISSUE_TYPES as $t): ?><option <?= selected($t, $type) ?>><?= $t ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select class="select" name="status" id="status" data-autosubmit>
                    <option value="">Any status</option>
                    <?php foreach (REPORT_STATUSES as $s): ?><option <?= selected($s, $status) ?>><?= $s ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="sort">Sort by</label>
                <select class="select" name="sort" id="sort" data-autosubmit>
                    <option value="votes" <?= selected('votes', $sort) ?>>Most supported</option>
                    <option value="newest" <?= selected('newest', $sort) ?>>Newest</option>
                </select>
            </div>
            <noscript><button class="btn btn-primary">Apply</button></noscript>
        </form>
    </div>

    <?php if (!$reports): ?>
        <div class="card empty">
            <i class="fa-regular fa-folder-open"></i>
            <h3>No reports found</h3>
            <p>Nothing matches these filters yet.</p>
            <a href="<?= url('report_new.php') ?>" class="btn btn-primary">Report an issue</a>
        </div>
    <?php else: ?>
        <div class="grid grid-auto">
            <?php foreach ($reports as $r): ?>
                <article class="card card-flush item-card">
                    <a href="<?= url('report.php?id=' . $r['id']) ?>">
                        <?php if ($r['photo']): ?>
                            <img class="cover" src="<?= e(url($r['photo'])) ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <div class="cover-placeholder"><i class="fa-solid fa-image"></i></div>
                        <?php endif; ?>
                    </a>
                    <div class="body">
                        <div class="row-between">
                            <span class="badge badge-muted"><?= e($r['issue_type']) ?></span>
                            <?= status_badge($r['status']) ?>
                        </div>
                        <h3><a href="<?= url('report.php?id=' . $r['id']) ?>"><?= e($r['title']) ?></a></h3>
                        <p><?= e(excerpt($r['description'], 120)) ?></p>
                        <div class="meta mt-1">
                            <span class="chip"><i class="fa-solid fa-user"></i> <?= e($r['full_name']) ?></span>
                            <span class="chip"><i class="fa-regular fa-clock"></i> <?= time_ago($r['created_at']) ?></span>
                        </div>
                    </div>
                    <div class="foot">
                        <span class="chip">
                            <i class="fa-solid fa-thumbs-up"></i> <?= $r['votes'] ?> support<?= $r['votes'] == 1 ? '' : 's' ?>
                            <?php if ($r['high_votes']): ?> · <span class="text-danger"><?= $r['high_votes'] ?> urgent</span><?php endif; ?>
                        </span>
                        <a href="<?= url('report.php?id=' . $r['id']) ?>" class="btn btn-sm <?= $r['my_vote'] ? 'btn-outline' : 'btn-primary' ?>">
                            <?= $r['my_vote'] ? '<i class="fa-solid fa-check"></i> Supported' : 'View & vote' ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?= pagination_links($p) ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
