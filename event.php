<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$id = (int) input('id');
$event = find_event($id);
if (!$event) {
    flash('error', 'That event could not be found.');
    redirect('events.php');
}

$uid = (int) $user['id'];
$isOwner = (int) $event['user_id'] === $uid;
$isLocalRep = $user['is_verified'] && strcasecmp($user['area'], $event['area']) === 0;

$stmt = $pdo->prepare('SELECT * FROM event_funds WHERE event_id = ?');
$stmt->execute([$id]);
$fund = $stmt->fetch() ?: null;

if (is_post()) {
    verify_csrf();
    $action = input('action');
    $back = 'event.php?id=' . $id;

    switch ($action) {
        case 'like':
            $del = $pdo->prepare('DELETE FROM event_likes WHERE event_id = ? AND user_id = ?');
            $del->execute([$id, $uid]);
            if (!$del->rowCount()) {
                $pdo->prepare('INSERT INTO event_likes (event_id, user_id) VALUES (?, ?)')->execute([$id, $uid]);
            }
            break;

        case 'join':
            if ($event['status'] === 'active') {
                $pdo->prepare('INSERT IGNORE INTO event_participants (event_id, user_id) VALUES (?, ?)')->execute([$id, $uid]);
                if (!$isOwner) {
                    notify((int) $event['user_id'], 'event', $user['full_name'] . ' joined your event "' . $event['title'] . '".', $back);
                }
                flash('success', 'You have joined this event. See you there!');
            }
            break;

        case 'leave':
            if (!$isOwner) {
                $pdo->prepare('DELETE FROM event_participants WHERE event_id = ? AND user_id = ?')->execute([$id, $uid]);
                flash('info', 'You are no longer participating.');
            }
            break;

        case 'comment':
            $body = input('body');
            if ($body !== '' && mb_strlen($body) <= 1000) {
                $pdo->prepare('INSERT INTO event_comments (event_id, user_id, body) VALUES (?, ?, ?)')->execute([$id, $uid, $body]);
                if (!$isOwner) {
                    notify((int) $event['user_id'], 'event', $user['full_name'] . ' commented on "' . $event['title'] . '".', $back . '#comments');
                }
            }
            $back .= '#comments';
            break;

        case 'delete_comment':
            $pdo->prepare('DELETE FROM event_comments WHERE id = ? AND event_id = ? AND (user_id = ? OR ?)')
                ->execute([(int) input('comment_id'), $id, $uid, $isOwner ? 1 : 0]);
            $back .= '#comments';
            break;

        case 'request_fund':
            $target = (float) input('target_amount');
            if ($isOwner && $event['status'] === 'active' && (!$fund || $fund['status'] === 'pending') && $target >= 100) {
                $pdo->prepare('INSERT INTO event_funds (event_id, target_amount, purpose) VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE target_amount = VALUES(target_amount), purpose = VALUES(purpose)')
                    ->execute([$id, $target, input('purpose') ?: null]);
                $reps = $pdo->prepare('SELECT id FROM users WHERE area = ? AND is_verified = 1 AND id <> ?');
                $reps->execute([$event['area'], $uid]);
                foreach ($reps->fetchAll(PDO::FETCH_COLUMN) as $repId) {
                    notify((int) $repId, 'fund', 'Fundraiser request of ' . money($target) . ' for "' . $event['title'] . '" needs your approval.', $back);
                }
                flash('success', 'Funding request sent. A verified local representative needs to approve it before donations open.');
            } else {
                flash('error', 'The funding target must be at least ৳100.');
            }
            break;

        case 'approve_fund':
            if ($fund && $fund['status'] === 'pending' && $isLocalRep && !$isOwner) {
                $pdo->prepare("UPDATE event_funds SET status = 'open', approved_by = ?, approved_at = NOW() WHERE event_id = ?")->execute([$uid, $id]);
                notify((int) $event['user_id'], 'fund', 'Your fundraiser for "' . $event['title'] . '" was approved by ' . $user['full_name'] . '. Donations are open.', $back);
                flash('success', 'Fundraiser approved. Donations are now open.');
            }
            break;

        case 'close_fund':
            if ($fund && $fund['status'] === 'open' && $isOwner) {
                $pdo->prepare("UPDATE event_funds SET status = 'closed' WHERE event_id = ?")->execute([$id]);
                flash('info', 'The fundraiser is now closed.');
            }
            break;

        case 'donate':
            $amount = (float) input('amount');
            $method = in_array(input('method'), DONATION_METHODS, true) ? input('method') : 'bKash';
            if ($fund && $fund['status'] === 'open' && $amount >= 10) {
                $pdo->prepare('INSERT INTO donations (event_id, user_id, amount, method, transaction_ref) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$id, $uid, $amount, $method, input('transaction_ref') ?: null]);
                if (!$isOwner) {
                    notify((int) $event['user_id'], 'fund', $user['full_name'] . ' donated ' . money($amount) . ' to "' . $event['title'] . '".', $back);
                }
                flash('success', 'Thank you for donating ' . money($amount) . '!');
            } else {
                flash('error', 'The minimum donation is ৳10.');
            }
            break;

        case 'complete':
            if ($isOwner && $event['status'] === 'active') {
                $pdo->prepare("UPDATE events SET status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$id]);
                $pdo->prepare("UPDATE event_funds SET status = 'closed' WHERE event_id = ? AND status = 'open'")->execute([$id]);
                $reps = $pdo->prepare('SELECT id FROM users WHERE area = ? AND is_verified = 1 AND id <> ?');
                $reps->execute([$event['area'], $uid]);
                foreach ($reps->fetchAll(PDO::FETCH_COLUMN) as $repId) {
                    notify((int) $repId, 'event', '"' . $event['title'] . '" was marked as completed. Please verify the work.', 'completed_events.php');
                }
                flash('success', 'Marked as completed. A local representative will verify it, then an admin will approve it.');
            }
            break;

        case 'verify':
            if ($isLocalRep && !$isOwner && $event['status'] === 'completed') {
                $pdo->prepare("UPDATE events SET status = 'verified', verified_by = ?, verified_at = NOW() WHERE id = ?")->execute([$uid, $id]);
                notify((int) $event['user_id'], 'event', '"' . $event['title'] . '" was verified by ' . $user['full_name'] . '.', $back);
                flash('success', 'Thank you for verifying this event.');
            }
            break;

        case 'delete':
            if ($isOwner && $event['status'] === 'active') {
                $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
                flash('success', 'The event was deleted.');
                redirect('events.php?scope=mine');
            }
            break;
    }
    redirect($back);
}

// Page data
$stmt = $pdo->prepare('SELECT
    (SELECT COUNT(*) FROM event_likes WHERE event_id = ?) AS likes,
    EXISTS(SELECT 1 FROM event_likes WHERE event_id = ? AND user_id = ?) AS liked,
    EXISTS(SELECT 1 FROM event_participants WHERE event_id = ? AND user_id = ?) AS joined,
    (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE event_id = ?) AS raised,
    (SELECT COUNT(DISTINCT user_id) FROM donations WHERE event_id = ?) AS donors');
$stmt->execute([$id, $id, $uid, $id, $uid, $id, $id]);
$s = $stmt->fetch();

$stmt = $pdo->prepare('SELECT u.id, u.username, u.full_name, u.avatar, u.is_verified FROM event_participants p JOIN users u ON u.id = p.user_id WHERE p.event_id = ? ORDER BY p.joined_at');
$stmt->execute([$id]);
$participants = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT c.*, u.full_name, u.username, u.avatar, u.is_verified FROM event_comments c JOIN users u ON u.id = c.user_id WHERE c.event_id = ? ORDER BY c.created_at');
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

$verifier = null;
if ($event['verified_by']) {
    $stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
    $stmt->execute([$event['verified_by']]);
    $verifier = $stmt->fetchColumn();
}
$fundApprover = null;
if ($fund && $fund['approved_by']) {
    $stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
    $stmt->execute([$fund['approved_by']]);
    $fundApprover = $stmt->fetchColumn();
}

$pageTitle = $event['title'];
$activeNav = 'events';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="breadcrumb"><a href="<?= url('events.php') ?>">Events</a> / #<?= $event['id'] ?></div>
        <h1><?= e($event['title']) ?></h1>
        <p><i class="fa-solid <?= EVENT_CATEGORIES[$event['category']] ?? 'fa-star' ?>"></i> <?= e($event['category']) ?> · <i class="fa-solid fa-location-dot"></i> <?= e($event['area']) ?>, <?= e($event['division']) ?></p>
    </div>
</section>

<div class="container layout-sidebar">
    <div class="stack">
        <div class="card">
            <div class="row-between mb-2">
                <?= status_badge($event['status']) ?>
                <div class="row">
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?><input type="hidden" name="action" value="like">
                        <button class="btn btn-sm <?= $s['liked'] ? 'is-on' : 'btn-outline' ?>"><i class="fa-<?= $s['liked'] ? 'solid' : 'regular' ?> fa-heart"></i> <?= $s['likes'] ?></button>
                    </form>
                    <?php if ($event['status'] === 'active'): ?>
                        <?php if (!$s['joined']): ?>
                            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="join">
                                <button class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Join event</button></form>
                        <?php elseif (!$isOwner): ?>
                            <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="leave">
                                <button class="btn btn-outline btn-sm"><i class="fa-solid fa-check"></i> Joined · Leave</button></form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <dl class="kv mb-3">
                <dt>When</dt><dd><?= $event['event_date'] ? fmt_date($event['event_date'], 'l, d F Y · g:i A') : 'To be announced' ?></dd>
                <dt>Where</dt><dd><?= e($event['venue'] ?: $event['area']) ?></dd>
                <dt>Organiser</dt><dd><?= e($event['full_name']) ?><?= verified_tick($event) ?></dd>
                <dt>Contact</dt><dd><a href="tel:<?= e($event['contact']) ?>"><?= e($event['contact']) ?></a></dd>
                <dt>Posted</dt><dd><?= time_ago($event['created_at']) ?></dd>
            </dl>
            <div class="prose"><?= e($event['description']) ?></div>

            <?php if ($event['status'] !== 'active'): ?>
                <div class="alert alert-success mt-3 mb-0"><i class="fa-solid fa-flag-checkered"></i><span>
                    Completed on <?= fmt_date($event['completed_at']) ?>.
                    <?php if ($verifier): ?>Verified by <?= e($verifier) ?> on <?= fmt_date($event['verified_at']) ?>.<?php endif; ?>
                    <?php if ($event['status'] === 'approved'): ?>Approved by the admin on <?= fmt_date($event['approved_at']) ?>.<?php endif; ?>
                    <?php if ($event['status'] === 'completed'): ?>Waiting for a verified local representative to confirm.<?php endif; ?>
                </span></div>
            <?php endif; ?>

            <?php if ($isOwner && $event['status'] === 'active'): ?>
                <div class="row mt-3">
                    <form method="post" class="inline-form" data-confirm="Mark this event as completed? Participants can no longer join.">
                        <?= csrf_field() ?><input type="hidden" name="action" value="complete">
                        <button class="btn btn-primary btn-sm"><i class="fa-solid fa-flag-checkered"></i> Mark as completed</button>
                    </form>
                    <form method="post" class="inline-form" data-confirm="Delete this event and all its comments, participants and donations?">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete">
                        <button class="btn btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i> Delete</button>
                    </form>
                </div>
            <?php endif; ?>
            <?php if ($isLocalRep && !$isOwner && $event['status'] === 'completed'): ?>
                <form method="post" class="mt-3" data-confirm="Confirm that this event really took place and the work was done?">
                    <?= csrf_field() ?><input type="hidden" name="action" value="verify">
                    <button class="btn btn-primary"><i class="fa-solid fa-circle-check"></i> Verify as local representative</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card" id="comments">
            <h3 class="card-title"><i class="fa-solid fa-comments"></i> Discussion (<?= count($comments) ?>)</h3>
            <?php foreach ($comments as $c): ?>
                <div class="comment">
                    <?= avatar($c, 'sm') ?>
                    <div class="comment-bubble">
                        <div class="row-between">
                            <strong><?= e($c['full_name']) ?><?= verified_tick($c) ?> <small class="muted"><?= time_ago($c['created_at']) ?></small></strong>
                            <?php if ((int) $c['user_id'] === $uid || $isOwner): ?>
                                <form method="post" class="inline-form" data-confirm="Delete this comment?">
                                    <?= csrf_field() ?><input type="hidden" name="action" value="delete_comment"><input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                    <button class="btn btn-ghost btn-sm" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="prose"><?= e($c['body']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$comments): ?><p class="muted">No comments yet. Start the conversation.</p><?php endif; ?>
            <form method="post" class="mt-2 stack">
                <?= csrf_field() ?><input type="hidden" name="action" value="comment">
                <textarea class="textarea" name="body" rows="3" maxlength="1000" placeholder="Ask a question or offer help…" required></textarea>
                <div class="text-center" style="text-align:right"><button class="btn btn-primary btn-sm"><i class="fa-solid fa-paper-plane"></i> Post comment</button></div>
            </form>
        </div>
    </div>

    <aside class="stack">
        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-hand-holding-dollar"></i> Fundraising</h3>
            <?php if ($fund && $fund['status'] !== 'pending'): ?>
                <div class="stat-value mt-1"><?= money($s['raised']) ?></div>
                <p class="muted small mb-1">raised of <?= money($fund['target_amount']) ?> from <?= $s['donors'] ?> donor<?= $s['donors'] == 1 ? '' : 's' ?></p>
                <div class="progress"><span style="width:<?= min(100, round($s['raised'] / $fund['target_amount'] * 100)) ?>%"></span></div>
                <?php if ($fund['purpose']): ?><p class="small mt-2 mb-0"><strong>Purpose:</strong> <?= e($fund['purpose']) ?></p><?php endif; ?>
                <p class="small muted mt-1 mb-0"><i class="fa-solid fa-user-check"></i> Approved by <?= e($fundApprover ?: 'admin') ?></p>
                <?php if ($fund['status'] === 'open'): ?>
                    <button class="btn btn-accent btn-block mt-2" data-open="donateModal"><i class="fa-solid fa-heart"></i> Donate</button>
                    <?php if ($isOwner): ?>
                        <form method="post" class="mt-1" data-confirm="Close the fundraiser? No more donations will be accepted.">
                            <?= csrf_field() ?><input type="hidden" name="action" value="close_fund">
                            <button class="btn btn-ghost btn-sm btn-block">Close fundraiser</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="badge badge-muted mt-2">Fundraiser closed</p>
                <?php endif; ?>
            <?php elseif ($fund): ?>
                <p class="mt-1"><?= status_badge('pending') ?> Request for <strong><?= money($fund['target_amount']) ?></strong></p>
                <?php if ($fund['purpose']): ?><p class="small"><?= e($fund['purpose']) ?></p><?php endif; ?>
                <?php if ($isLocalRep && !$isOwner): ?>
                    <form method="post" data-confirm="Approve this fundraiser for <?= e(money($fund['target_amount'])) ?>?">
                        <?= csrf_field() ?><input type="hidden" name="action" value="approve_fund">
                        <button class="btn btn-primary btn-block"><i class="fa-solid fa-check"></i> Approve fundraiser</button>
                    </form>
                <?php else: ?>
                    <p class="muted small mb-0">Waiting for a verified local representative to approve.</p>
                <?php endif; ?>
                <?php if ($isOwner): ?>
                    <button class="btn btn-outline btn-sm btn-block mt-2" data-open="fundModal">Edit request</button>
                <?php endif; ?>
            <?php elseif ($isOwner && $event['status'] === 'active'): ?>
                <p class="muted small mt-1">Need money for supplies or logistics? Request a fundraiser — a verified local representative approves it.</p>
                <button class="btn btn-outline btn-block" data-open="fundModal"><i class="fa-solid fa-plus"></i> Request funding</button>
            <?php else: ?>
                <p class="muted small mt-1 mb-0">This event is not raising funds.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-users"></i> Participants (<?= count($participants) ?>)</h3>
            <ul class="list mt-2">
                <?php foreach ($participants as $pt): ?>
                    <li class="list-item" style="align-items:center">
                        <?= avatar($pt, 'sm') ?>
                        <div class="list-body"><strong><?= e($pt['full_name']) ?><?= verified_tick($pt) ?></strong>
                            <small class="muted"><?= (int) $pt['id'] === (int) $event['user_id'] ? 'Organiser' : '@' . e($pt['username']) ?></small></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>
</div>

<?php if ($isOwner): ?>
<dialog class="modal" id="fundModal">
    <form method="post">
        <div class="modal-head"><h3>Request funding</h3><button type="button" class="modal-close" data-close>&times;</button></div>
        <div class="modal-body stack">
            <?= csrf_field() ?><input type="hidden" name="action" value="request_fund">
            <div class="field">
                <label for="target_amount">Target amount (৳)</label>
                <input class="input" type="number" min="100" step="1" id="target_amount" name="target_amount" value="<?= e($fund['target_amount'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="purpose">What is the money for?</label>
                <input class="input" id="purpose" name="purpose" maxlength="255" value="<?= e($fund['purpose'] ?? '') ?>" placeholder="e.g. gloves, bags and a waste van">
            </div>
            <div class="form-actions"><button type="button" class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-primary">Send request</button></div>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php if ($fund && $fund['status'] === 'open'): ?>
<dialog class="modal" id="donateModal">
    <form method="post">
        <div class="modal-head"><h3>Donate to this event</h3><button type="button" class="modal-close" data-close>&times;</button></div>
        <div class="modal-body stack">
            <?= csrf_field() ?><input type="hidden" name="action" value="donate">
            <p class="muted small mb-0">Send your donation to the organiser (<?= e($event['contact']) ?>) and record it here so everyone can see the progress.</p>
            <div class="field">
                <label for="amount">Amount (৳)</label>
                <input class="input" type="number" min="10" step="1" id="amount" name="amount" required>
            </div>
            <div class="field">
                <label for="method">Payment method</label>
                <select class="select" id="method" name="method">
                    <?php foreach (DONATION_METHODS as $m): ?><option><?= $m ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="transaction_ref">Transaction ID (optional)</label>
                <input class="input" id="transaction_ref" name="transaction_ref" maxlength="100">
            </div>
            <div class="form-actions"><button type="button" class="btn btn-ghost" data-close>Cancel</button><button class="btn btn-accent"><i class="fa-solid fa-heart"></i> Record donation</button></div>
        </div>
    </form>
</dialog>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
