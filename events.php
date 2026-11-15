<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$scope = in_array(input('scope'), ['area', 'mine', 'joined'], true) ? input('scope') : 'area';
$category = array_key_exists(input('category'), EVENT_CATEGORIES) ? input('category') : '';

$params = [$user['id'], $user['id']];
if ($scope === 'mine') {
    $where = 'e.user_id = ?';
    $params[] = $user['id'];
} elseif ($scope === 'joined') {
    $where = 'EXISTS (SELECT 1 FROM event_participants p WHERE p.event_id = e.id AND p.user_id = ?)';
    $params[] = $user['id'];
} else {
    $where = "e.area = ? AND e.status = 'active'";
    $params[] = $user['area'];
}
if ($category) {
    $where .= ' AND e.category = ?';
    $params[] = $category;
}

// Events matching the user's volunteer interests come first, then the most engaged ones.
$stmt = $pdo->prepare("SELECT e.*, u.full_name, u.avatar, u.is_verified,
        (SELECT COUNT(*) FROM event_likes l WHERE l.event_id = e.id)        AS likes,
        (SELECT COUNT(*) FROM event_comments c WHERE c.event_id = e.id)     AS comments,
        (SELECT COUNT(*) FROM event_participants p WHERE p.event_id = e.id) AS participants,
        EXISTS (SELECT 1 FROM volunteer_interests vi WHERE vi.user_id = ? AND vi.category = e.category) AS interest_match,
        EXISTS (SELECT 1 FROM event_participants p WHERE p.event_id = e.id AND p.user_id = ?) AS joined,
        f.target_amount, f.status AS fund_status,
        (SELECT COALESCE(SUM(d.amount), 0) FROM donations d WHERE d.event_id = e.id) AS raised
    FROM events e
    JOIN users u ON u.id = e.user_id
    LEFT JOIN event_funds f ON f.event_id = e.id
    WHERE $where
    ORDER BY interest_match DESC, (likes + comments + participants) DESC, e.created_at DESC");
$stmt->execute($params);
$events = $stmt->fetchAll();

$pageTitle = 'Community events';
$activeNav = 'events';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container row-between">
        <div>
            <h1>Community events</h1>
            <p>Clean-ups, health camps, awareness drives and more in <?= e($user['area']) ?>. Join in, comment, or help raise funds.</p>
        </div>
        <div class="row">
            <a href="<?= url('completed_events.php') ?>" class="btn btn-ghost-light"><i class="fa-solid fa-circle-check"></i> Completed</a>
            <a href="<?= url('event_new.php') ?>" class="btn btn-accent"><i class="fa-solid fa-plus"></i> Create event</a>
        </div>
    </div>
</section>

<div class="container">
    <div class="card mb-3">
        <div class="tabs">
            <a href="?scope=area" class="<?= $scope === 'area' ? 'active' : '' ?>">In my area</a>
            <a href="?scope=joined" class="<?= $scope === 'joined' ? 'active' : '' ?>">Joined</a>
            <a href="?scope=mine" class="<?= $scope === 'mine' ? 'active' : '' ?>">Organised by me</a>
        </div>
        <form class="filters" method="get">
            <input type="hidden" name="scope" value="<?= $scope ?>">
            <div class="field">
                <label for="category">Category</label>
                <select class="select" name="category" id="category" data-autosubmit>
                    <option value="">All categories</option>
                    <?php foreach (EVENT_CATEGORIES as $c => $icon): ?><option <?= selected($c, $category) ?>><?= $c ?></option><?php endforeach; ?>
                </select>
            </div>
            <p class="muted small mb-0" style="flex:2">
                <i class="fa-solid fa-lightbulb"></i> Events matching your <a href="<?= url('profile.php#interests') ?>">volunteer interests</a> are shown first.
            </p>
        </form>
    </div>

    <?php if (!$events): ?>
        <div class="card empty">
            <i class="fa-regular fa-calendar-xmark"></i>
            <h3>No events here yet</h3>
            <p>Be the one who brings your neighbours together.</p>
            <a href="<?= url('event_new.php') ?>" class="btn btn-primary">Create an event</a>
        </div>
    <?php else: ?>
        <div class="grid grid-auto">
            <?php foreach ($events as $ev): ?>
                <article class="card card-flush item-card">
                    <div class="event-band">
                        <i class="fa-solid <?= EVENT_CATEGORIES[$ev['category']] ?? 'fa-star' ?>"></i>
                        <div>
                            <small><?= e($ev['category']) ?></small>
                            <strong><?= $ev['event_date'] ? fmt_date($ev['event_date'], 'D, d M · g:i A') : 'Date to be announced' ?></strong>
                        </div>
                    </div>
                    <div class="body">
                        <div class="row-between">
                            <?= status_badge($ev['status']) ?>
                            <?php if ($ev['interest_match']): ?><span class="badge badge-primary"><i class="fa-solid fa-heart"></i> Your interest</span><?php endif; ?>
                        </div>
                        <h3><a href="<?= url('event.php?id=' . $ev['id']) ?>"><?= e($ev['title']) ?></a></h3>
                        <p><?= e(excerpt($ev['description'], 120)) ?></p>
                        <div class="meta">
                            <span class="chip"><i class="fa-solid fa-user"></i> <?= e($ev['full_name']) ?><?= verified_tick($ev) ?></span>
                            <?php if ($ev['venue']): ?><span class="chip"><i class="fa-solid fa-location-dot"></i> <?= e($ev['venue']) ?></span><?php endif; ?>
                        </div>
                        <?php if ($ev['fund_status'] === 'open' || $ev['fund_status'] === 'closed'): ?>
                            <div>
                                <div class="row-between small"><span><?= money($ev['raised']) ?> raised</span><span class="muted">of <?= money($ev['target_amount']) ?></span></div>
                                <div class="progress mt-1"><span style="width:<?= min(100, round($ev['raised'] / $ev['target_amount'] * 100)) ?>%"></span></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="foot">
                        <div class="meta">
                            <span class="chip"><i class="fa-solid fa-heart"></i> <?= $ev['likes'] ?></span>
                            <span class="chip"><i class="fa-solid fa-comment"></i> <?= $ev['comments'] ?></span>
                            <span class="chip"><i class="fa-solid fa-users"></i> <?= $ev['participants'] ?></span>
                        </div>
                        <a class="btn btn-sm <?= $ev['joined'] ? 'btn-outline' : 'btn-primary' ?>" href="<?= url('event.php?id=' . $ev['id']) ?>">
                            <?= $ev['joined'] ? '<i class="fa-solid fa-check"></i> Joined' : 'Open' ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
