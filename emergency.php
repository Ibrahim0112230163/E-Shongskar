<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$uid = (int) $user['id'];

if (is_post()) {
    verify_csrf();
    if (input('action') === 'sos') {
        $lat = is_numeric(input('latitude')) ? (float) input('latitude') : null;
        $lng = is_numeric(input('longitude')) ? (float) input('longitude') : null;
        $message = mb_substr(input('message'), 0, 500);

        // Guard against accidental repeated taps
        $recent = $pdo->prepare("SELECT id FROM sos_requests WHERE user_id = ? AND status = 'open' AND created_at >= NOW() - INTERVAL 2 MINUTE");
        $recent->execute([$uid]);
        if ($recent->fetch()) {
            flash('warning', 'Your SOS was already sent a moment ago. Help has been notified.');
            redirect('emergency.php#sos');
        }

        $pdo->prepare('INSERT INTO sos_requests (user_id, message, latitude, longitude, area) VALUES (?, ?, ?, ?, ?)')
            ->execute([$uid, $message ?: null, $lat, $lng, $user['area']]);

        $text = 'SOS from ' . $user['full_name'] . ' (' . $user['phone'] . ') in ' . $user['area']
            . ($message ? ': "' . $message . '"' : '') . '.';
        $link = $lat !== null ? 'https://maps.google.com/?q=' . $lat . ',' . $lng : 'chat.php?with=' . $uid;
        $reps = $pdo->prepare('SELECT id FROM users WHERE area = ? AND is_verified = 1 AND id <> ?');
        $reps->execute([$user['area'], $uid]);
        foreach ($reps->fetchAll(PDO::FETCH_COLUMN) as $repId) {
            notify((int) $repId, 'sos', $text, $link);
        }
        flash('success', 'SOS sent to the local representatives of ' . $user['area'] . ' and the admin team. If you are in danger, call 999 now.');
    } elseif (input('action') === 'resolve') {
        $pdo->prepare("UPDATE sos_requests SET status = 'resolved', resolved_at = NOW() WHERE id = ? AND user_id = ?")
            ->execute([(int) input('sos_id'), $uid]);
        flash('success', 'Glad you are safe. The SOS was marked as resolved.');
    }
    redirect('emergency.php#sos');
}

$q = input('q');
$sql = "SELECT *, CASE WHEN area IS NOT NULL THEN 1 WHEN division IS NOT NULL THEN 2 ELSE 3 END AS scope_rank
        FROM emergency_contacts
        WHERE (division IS NULL OR (division = ? AND (area IS NULL OR area = ?)))";
$params = [$user['division'], $user['area']];
if ($q !== '') {
    $sql .= ' AND (name LIKE ? OR category LIKE ? OR address LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
$stmt = $pdo->prepare($sql . ' ORDER BY scope_rank, category, name');
$stmt->execute($params);
$groups = [1 => [], 2 => [], 3 => []];
foreach ($stmt->fetchAll() as $c) {
    $groups[$c['scope_rank']][] = $c;
}
$groupTitles = [1 => 'In ' . $user['area'], 2 => $user['division'] . ' division', 3 => 'Nationwide'];

$stmt = $pdo->prepare('SELECT * FROM sos_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
$stmt->execute([$uid]);
$mySos = $stmt->fetchAll();

$categoryIcons = ['Police' => 'fa-building-shield', 'Fire Service' => 'fa-fire-extinguisher', 'Hospital' => 'fa-hospital',
    'Ambulance' => 'fa-truck-medical', 'Helpline' => 'fa-headset', 'Utility' => 'fa-bolt', 'Other' => 'fa-phone'];

$pageTitle = 'Emergency';
$activeNav = 'emergency';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>Emergency help</h1>
        <p>Send an SOS to your local representatives or call an emergency service directly.</p>
    </div>
</section>

<div class="container layout-sidebar">
    <div class="stack">
        <div class="card">
            <form class="filters" method="get">
                <div class="field">
                    <label for="q">Search contacts</label>
                    <div class="input-icon"><i class="fa-solid fa-magnifying-glass"></i>
                        <input class="input" id="q" name="q" value="<?= e($q) ?>" placeholder="Hospital, police, fire…"></div>
                </div>
                <button class="btn btn-primary">Search</button>
                <?php if ($q !== ''): ?><a class="btn btn-ghost" href="<?= url('emergency.php') ?>">Clear</a><?php endif; ?>
            </form>
        </div>

        <?php foreach ($groups as $rank => $contacts): if (!$contacts) continue; ?>
            <div class="card">
                <h3 class="card-title"><i class="fa-solid fa-location-dot"></i> <?= e($groupTitles[$rank]) ?></h3>
                <ul class="list mt-2">
                    <?php foreach ($contacts as $c): ?>
                        <li class="list-item" style="align-items:center">
                            <span class="list-icon"><i class="fa-solid <?= $categoryIcons[$c['category']] ?? 'fa-phone' ?>"></i></span>
                            <div class="list-body">
                                <strong><?= e($c['name']) ?></strong>
                                <small class="muted"><?= e($c['category']) ?><?= $c['address'] ? ' · ' . e($c['address']) : '' ?></small>
                            </div>
                            <a class="btn btn-outline btn-sm" href="tel:<?= e($c['phone']) ?>"><i class="fa-solid fa-phone"></i> <?= e($c['phone']) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
        <?php if (!array_filter($groups)): ?>
            <div class="card empty"><i class="fa-solid fa-address-book"></i><p>No contacts match your search.</p></div>
        <?php endif; ?>
    </div>

    <aside class="stack" id="sos">
        <div class="card sos-wrap">
            <h3>Emergency SOS</h3>
            <p class="muted small">Don't panic. Tap the button to alert the verified representatives of <?= e($user['area']) ?> and the admin team with your location.</p>
            <form method="post" id="sosForm" class="stack">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="sos">
                <input type="hidden" name="latitude">
                <input type="hidden" name="longitude">
                <button type="submit" class="sos-button">SOS<small>Tap to send</small></button>
                <input class="input" name="message" maxlength="500" placeholder="What is happening? (optional)">
            </form>
            <a href="tel:999" class="btn btn-danger btn-block mt-2"><i class="fa-solid fa-phone"></i> Call 999</a>
        </div>

        <?php if ($mySos): ?>
            <div class="card">
                <h3 class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> My recent SOS</h3>
                <ul class="list mt-2">
                    <?php foreach ($mySos as $s): ?>
                        <li class="list-item" style="align-items:center">
                            <div class="list-body">
                                <strong><?= fmt_date($s['created_at'], 'd M, g:i A') ?></strong>
                                <small class="muted"><?= e($s['message'] ?: 'No message') ?></small>
                            </div>
                            <?php if ($s['status'] === 'open'): ?>
                                <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="resolve"><input type="hidden" name="sos_id" value="<?= $s['id'] ?>">
                                    <button class="btn btn-outline btn-sm">I'm safe</button></form>
                            <?php else: ?>
                                <?= status_badge('resolved') ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
