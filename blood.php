<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$uid = (int) $user['id'];

$stmt = $pdo->prepare('SELECT * FROM blood_donors WHERE user_id = ?');
$stmt->execute([$uid]);
$myDonor = $stmt->fetch() ?: null;

if (is_post()) {
    verify_csrf();
    switch (input('action')) {
        case 'register':
            if ($user['blood_group'] === 'Unknown') {
                flash('error', 'Please set your blood group in your profile first.');
                redirect('profile.php');
            }
            $last = input('last_donation_date');
            $last = $last && strtotime($last) && strtotime($last) <= time() ? date('Y-m-d', strtotime($last)) : null;
            $next = $last ? date('Y-m-d', strtotime($last . ' +' . DONATION_GAP_DAYS . ' days')) : null;
            $pdo->prepare('INSERT INTO blood_donors (user_id, last_donation_date, next_eligible_date) VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE last_donation_date = VALUES(last_donation_date), next_eligible_date = VALUES(next_eligible_date)')
                ->execute([$uid, $last, $next]);
            flash('success', 'You are now a registered blood donor. Thank you!');
            break;

        case 'log_donation':
            $date = input('donation_date') ?: date('Y-m-d');
            if ($myDonor && strtotime($date) && strtotime($date) <= time()) {
                $pdo->prepare('UPDATE blood_donors SET last_donation_date = ?, next_eligible_date = ? WHERE user_id = ?')
                    ->execute([date('Y-m-d', strtotime($date)), date('Y-m-d', strtotime($date . ' +' . DONATION_GAP_DAYS . ' days')), $uid]);
                flash('success', 'Donation recorded. You can donate again after ' . DONATION_GAP_DAYS . ' days.');
            }
            break;

        case 'toggle':
            $pdo->prepare('UPDATE blood_donors SET is_available = 1 - is_available WHERE user_id = ?')->execute([$uid]);
            flash('info', 'Your availability was updated.');
            break;

        case 'leave':
            $pdo->prepare('DELETE FROM blood_donors WHERE user_id = ?')->execute([$uid]);
            flash('info', 'You have left the donor network.');
            break;

        case 'request':
            $donorId = (int) input('donor_id');
            $chk = $pdo->prepare("SELECT u.full_name FROM blood_donors d JOIN users u ON u.id = d.user_id WHERE d.user_id = ? AND d.user_id <> ?");
            $chk->execute([$donorId, $uid]);
            $donorName = $chk->fetchColumn();
            if ($donorName) {
                $dup = $pdo->prepare("SELECT 1 FROM blood_requests WHERE requester_id = ? AND donor_id = ? AND status = 'pending'");
                $dup->execute([$uid, $donorId]);
                if ($dup->fetch()) {
                    flash('info', 'You already have a pending request with ' . $donorName . '.');
                } else {
                    $pdo->prepare('INSERT INTO blood_requests (requester_id, donor_id, message) VALUES (?, ?, ?)')
                        ->execute([$uid, $donorId, mb_substr(input('message'), 0, 500) ?: null]);
                    notify($donorId, 'blood', $user['full_name'] . ' needs blood and asked for your contact number.', 'blood.php#incoming');
                    flash('success', 'Request sent to ' . $donorName . '. You will be notified when they respond.');
                }
            }
            break;

        case 'respond':
            $reqId = (int) input('request_id');
            $answer = input('answer') === 'accept' ? 'accepted' : 'declined';
            $stmt = $pdo->prepare("UPDATE blood_requests SET status = ?, responded_at = NOW() WHERE id = ? AND donor_id = ? AND status = 'pending'");
            $stmt->execute([$answer, $reqId, $uid]);
            if ($stmt->rowCount()) {
                $req = $pdo->prepare('SELECT requester_id FROM blood_requests WHERE id = ?');
                $req->execute([$reqId]);
                notify((int) $req->fetchColumn(), 'blood', $answer === 'accepted'
                    ? $user['full_name'] . ' accepted your blood request. Their number is now visible to you.'
                    : $user['full_name'] . ' is unable to donate right now.', 'blood.php#sent');
                flash('success', $answer === 'accepted' ? 'Your number has been shared with the requester.' : 'Request declined.');
            }
            break;
    }
    redirect('blood.php');
}

// Search
$need = in_array(input('group'), BLOOD_GROUPS, true) ? input('group') : (in_array($user['blood_group'], BLOOD_GROUPS, true) ? $user['blood_group'] : 'O+');
$scope = input('scope') === 'division' ? 'division' : 'area';
$compatible = BLOOD_COMPATIBILITY[$need];
$in = rtrim(str_repeat('?,', count($compatible)), ',');

$stmt = $pdo->prepare("SELECT u.id, u.full_name, u.username, u.avatar, u.blood_group, u.area, u.is_verified,
        d.is_available, d.last_donation_date, d.next_eligible_date,
        (SELECT status FROM blood_requests r WHERE r.requester_id = ? AND r.donor_id = u.id ORDER BY r.id DESC LIMIT 1) AS my_request
    FROM blood_donors d JOIN users u ON u.id = d.user_id
    WHERE u.id <> ? AND u.blood_group IN ($in) AND " . ($scope === 'area' ? 'u.area = ?' : 'u.division = ?') . "
    ORDER BY d.is_available DESC, (d.next_eligible_date IS NULL OR d.next_eligible_date <= CURDATE()) DESC, u.blood_group = ? DESC, u.full_name");
$stmt->execute(array_merge([$uid, $uid], $compatible, [$scope === 'area' ? $user['area'] : $user['division'], $need]));
$donors = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT r.*, u.full_name, u.avatar, u.blood_group, u.area FROM blood_requests r JOIN users u ON u.id = r.requester_id
    WHERE r.donor_id = ? ORDER BY r.status = 'pending' DESC, r.created_at DESC LIMIT 20");
$stmt->execute([$uid]);
$incoming = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT r.*, u.full_name, u.avatar, u.blood_group, u.phone FROM blood_requests r JOIN users u ON u.id = r.donor_id
    WHERE r.requester_id = ? ORDER BY r.created_at DESC LIMIT 20");
$stmt->execute([$uid]);
$sent = $stmt->fetchAll();

$canDonateNow = $myDonor && (!$myDonor['next_eligible_date'] || $myDonor['next_eligible_date'] <= date('Y-m-d'));

$pageTitle = 'Blood donors';
$activeNav = 'blood';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>Blood donor network</h1>
        <p>Find compatible donors near you. Phone numbers are shared only when a donor accepts your request.</p>
    </div>
</section>

<div class="container layout-sidebar">
    <div class="stack">
        <div class="card">
            <form class="filters" method="get">
                <div class="field">
                    <label for="group">Blood group needed</label>
                    <select class="select" id="group" name="group" data-autosubmit>
                        <?php foreach (BLOOD_GROUPS as $bg): ?><option <?= selected($bg, $need) ?>><?= $bg ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="scope">Search in</label>
                    <select class="select" id="scope" name="scope" data-autosubmit>
                        <option value="area" <?= selected('area', $scope) ?>><?= e($user['area']) ?> (my area)</option>
                        <option value="division" <?= selected('division', $scope) ?>><?= e($user['division']) ?> division</option>
                    </select>
                </div>
                <noscript><button class="btn btn-primary">Search</button></noscript>
            </form>
            <p class="small muted mt-2 mb-0"><i class="fa-solid fa-circle-info"></i> A patient with <strong><?= $need ?></strong> can receive from: <?= implode(', ', $compatible) ?>.</p>
        </div>

        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-droplet"></i> <?= count($donors) ?> compatible donor<?= count($donors) == 1 ? '' : 's' ?></h3>
            <?php if (!$donors): ?>
                <div class="empty"><i class="fa-solid fa-droplet-slash"></i><p>No compatible donors found. Try searching the whole division.</p></div>
            <?php else: ?>
                <ul class="list mt-2">
                    <?php foreach ($donors as $d):
                        $eligible = !$d['next_eligible_date'] || $d['next_eligible_date'] <= date('Y-m-d'); ?>
                        <li class="list-item" style="align-items:center">
                            <span class="blood-tag"><?= e($d['blood_group']) ?></span>
                            <div class="list-body">
                                <strong><?= e($d['full_name']) ?><?= verified_tick($d) ?></strong>
                                <div class="meta small muted">
                                    <span><i class="fa-solid fa-location-dot"></i> <?= e($d['area']) ?></span>
                                    <?php if (!$d['is_available']): ?><span class="text-danger">Not available</span>
                                    <?php elseif ($eligible): ?><span class="text-success">Can donate now</span>
                                    <?php else: ?><span>Eligible from <?= fmt_date($d['next_eligible_date']) ?></span><?php endif; ?>
                                </div>
                            </div>
                            <?php if ($d['my_request'] === 'pending'): ?>
                                <span class="badge badge-warning">Requested</span>
                            <?php elseif ($d['my_request'] === 'accepted'): ?>
                                <a class="btn btn-outline btn-sm" href="#sent">See number</a>
                            <?php elseif ($d['is_available']): ?>
                                <button class="btn btn-primary btn-sm" data-open="requestModal" data-fill-donor-id="<?= $d['id'] ?>" data-fill-donor-name="<?= e($d['full_name']) ?>">
                                    <i class="fa-solid fa-phone"></i> Request contact
                                </button>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card" id="sent">
            <h3 class="card-title"><i class="fa-solid fa-paper-plane"></i> My requests</h3>
            <?php if (!$sent): ?>
                <p class="muted mb-0 mt-1">You haven't asked anyone yet.</p>
            <?php else: ?>
                <ul class="list mt-2">
                    <?php foreach ($sent as $r): ?>
                        <li class="list-item" style="align-items:center">
                            <span class="blood-tag"><?= e($r['blood_group']) ?></span>
                            <div class="list-body">
                                <strong><?= e($r['full_name']) ?></strong>
                                <small class="muted"><?= time_ago($r['created_at']) ?></small>
                            </div>
                            <?php if ($r['status'] === 'accepted'): ?>
                                <a class="btn btn-accent btn-sm" href="tel:<?= e($r['phone']) ?>"><i class="fa-solid fa-phone"></i> <?= e($r['phone']) ?></a>
                            <?php else: ?>
                                <?= status_badge($r['status']) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <aside class="stack">
        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-hand-holding-medical"></i> Me as a donor</h3>
            <?php if (!$myDonor): ?>
                <p class="muted small mt-1">Your blood group: <strong><?= e($user['blood_group']) ?></strong>. Join the network so neighbours can find you in an emergency.</p>
                <form method="post" class="stack">
                    <?= csrf_field() ?><input type="hidden" name="action" value="register">
                    <div class="field">
                        <label for="last_donation_date">Last donation (if any)</label>
                        <input class="input" type="date" id="last_donation_date" name="last_donation_date" max="<?= date('Y-m-d') ?>">
                    </div>
                    <button class="btn btn-accent btn-block"><i class="fa-solid fa-heart"></i> Become a donor</button>
                </form>
            <?php else: ?>
                <div class="row mt-1">
                    <span class="blood-tag"><?= e($user['blood_group']) ?></span>
                    <div>
                        <strong><?= $myDonor['is_available'] ? 'Available' : 'Unavailable' ?></strong><br>
                        <small class="muted"><?= $canDonateNow ? 'You can donate now' : 'Eligible from ' . fmt_date($myDonor['next_eligible_date']) ?></small>
                    </div>
                </div>
                <p class="small muted mt-2">Last donation: <?= fmt_date($myDonor['last_donation_date']) ?></p>
                <form method="post" class="stack">
                    <?= csrf_field() ?><input type="hidden" name="action" value="log_donation">
                    <div class="field"><label for="donation_date">I donated on</label>
                        <input class="input" type="date" id="donation_date" name="donation_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></div>
                    <button class="btn btn-outline btn-sm btn-block">Record donation</button>
                </form>
                <div class="row mt-2">
                    <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="toggle">
                        <button class="btn btn-ghost btn-sm"><?= $myDonor['is_available'] ? 'Pause availability' : 'Mark available' ?></button></form>
                    <form method="post" class="inline-form" data-confirm="Leave the donor network?"><?= csrf_field() ?><input type="hidden" name="action" value="leave">
                        <button class="btn btn-ghost btn-sm text-danger">Leave network</button></form>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" id="incoming">
            <h3 class="card-title"><i class="fa-solid fa-inbox"></i> Requests to me</h3>
            <?php if (!$incoming): ?>
                <p class="muted small mb-0 mt-1">No one has asked for your contact yet.</p>
            <?php else: ?>
                <ul class="list mt-2">
                    <?php foreach ($incoming as $r): ?>
                        <li class="list-item">
                            <?= avatar($r, 'sm') ?>
                            <div class="list-body">
                                <strong><?= e($r['full_name']) ?></strong>
                                <small class="muted"><?= e($r['area']) ?> · <?= time_ago($r['created_at']) ?></small>
                                <?php if ($r['message']): ?><p class="small mt-1 mb-1">"<?= e($r['message']) ?>"</p><?php endif; ?>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <div class="row mt-1">
                                        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="respond"><input type="hidden" name="request_id" value="<?= $r['id'] ?>"><input type="hidden" name="answer" value="accept">
                                            <button class="btn btn-primary btn-sm">Share my number</button></form>
                                        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="respond"><input type="hidden" name="request_id" value="<?= $r['id'] ?>"><input type="hidden" name="answer" value="decline">
                                            <button class="btn btn-ghost btn-sm">Decline</button></form>
                                    </div>
                                <?php else: ?>
                                    <?= status_badge($r['status']) ?>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </aside>
</div>

<dialog class="modal" id="requestModal">
    <form method="post">
        <div class="modal-head"><h3>Request contact</h3><button type="button" class="modal-close" data-close>&times;</button></div>
        <div class="modal-body stack">
            <?= csrf_field() ?><input type="hidden" name="action" value="request">
            <input type="hidden" name="donor_id" data-field="donorId">
            <p class="mb-0">Ask <strong data-field="donorName"></strong> to share their phone number with you.</p>
            <div class="field">
                <label for="message">Message</label>
                <textarea class="textarea" id="message" name="message" rows="3" maxlength="500" placeholder="Patient, hospital, how many bags, when…"></textarea>
            </div>
            <div class="form-actions"><button type="button" class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary">Send request</button></div>
        </div>
    </form>
</dialog>

<?php include __DIR__ . '/includes/footer.php'; ?>
