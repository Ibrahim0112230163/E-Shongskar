<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$uid = (int) $user['id'];

/*
 * Messaging rule: neighbours in the same area can talk when at least one of
 * them is a verified local representative. This keeps chat useful for
 * coordination while protecting people from unsolicited messages.
 */
function can_message(array $me, ?array $other): bool
{
    return $other
        && (int) $other['id'] !== (int) $me['id']
        && strcasecmp($other['area'], $me['area']) === 0
        && ($me['is_verified'] || $other['is_verified']);
}

$other = null;
if ($withId = (int) input('with')) {
    $stmt = $pdo->prepare('SELECT id, username, full_name, avatar, area, is_verified, last_seen FROM users WHERE id = ?');
    $stmt->execute([$withId]);
    $other = $stmt->fetch() ?: null;
}

if (is_post()) {
    verify_csrf();
    if (!can_message($user, $other)) {
        flash('error', 'You can only message people in your area when one of you is a verified representative.');
        redirect('chat.php');
    }
    $body = input('body');
    try {
        $attachment = save_upload($_FILES['attachment'] ?? null, 'chat');
    } catch (RuntimeException $ex) {
        flash('error', $ex->getMessage());
        redirect('chat.php?with=' . $other['id']);
    }
    if ($body !== '' || $attachment) {
        $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, body, attachment) VALUES (?, ?, ?, ?)')
            ->execute([$uid, $other['id'], $body !== '' ? mb_substr($body, 0, 2000) : null, $attachment]);
    }
    redirect('chat.php?with=' . $other['id']);
}

// Conversation list: the latest message with each person
$stmt = $pdo->prepare("SELECT u.id, u.username, u.full_name, u.avatar, u.is_verified, u.last_seen,
        m.body, m.attachment, m.created_at, m.sender_id,
        (SELECT COUNT(*) FROM messages x WHERE x.sender_id = u.id AND x.receiver_id = ? AND x.is_read = 0) AS unread
    FROM messages m
    JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
    WHERE m.id IN (
        SELECT MAX(id) FROM messages WHERE sender_id = ? OR receiver_id = ?
        GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id))
    ORDER BY m.created_at DESC");
$stmt->execute([$uid, $uid, $uid, $uid]);
$conversations = $stmt->fetchAll();
$talkingTo = array_column($conversations, 'id');

// People the user may start a conversation with
$sql = 'SELECT id, username, full_name, avatar, is_verified, last_seen FROM users WHERE area = ? AND id <> ?'
     . ($user['is_verified'] ? '' : ' AND is_verified = 1') . ' ORDER BY is_verified DESC, full_name LIMIT 50';
$stmt = $pdo->prepare($sql);
$stmt->execute([$user['area'], $uid]);
$contacts = array_filter($stmt->fetchAll(), fn ($c) => !in_array($c['id'], $talkingTo));

$thread = [];
if ($other) {
    $pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0')->execute([$other['id'], $uid]);
    $stmt = $pdo->prepare('SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at, id');
    $stmt->execute([$uid, $other['id'], $other['id'], $uid]);
    $thread = $stmt->fetchAll();
}

function is_online(?string $lastSeen): bool
{
    return $lastSeen && strtotime($lastSeen) >= time() - ONLINE_WINDOW_MINUTES * 60;
}

$pageTitle = 'Messages';
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-3">
    <div class="card card-flush chat <?= $other ? 'has-thread' : '' ?>">
        <div class="chat-list">
            <div class="chat-list-head">
                <h3 class="mb-0">Messages</h3>
                <small class="muted">Neighbours in <?= e($user['area']) ?></small>
            </div>
            <?php foreach ($conversations as $c): ?>
                <a class="chat-contact <?= $other && (int) $other['id'] === (int) $c['id'] ? 'active' : '' ?>" href="?with=<?= $c['id'] ?>">
                    <?= avatar($c, 'md') ?>
                    <div class="list-body">
                        <strong><?= e($c['full_name']) ?><?= verified_tick($c) ?></strong>
                        <small><?= (int) $c['sender_id'] === $uid ? 'You: ' : '' ?><?= e($c['body'] ? excerpt($c['body'], 40) : '📷 Photo') ?></small>
                    </div>
                    <?php if ($c['unread']): ?><span class="unread-dot"><?= $c['unread'] ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>

            <?php if ($contacts): ?>
                <div class="chat-list-head" style="position:static"><small class="muted"><strong>Start a conversation</strong></small></div>
                <?php foreach ($contacts as $c): ?>
                    <a class="chat-contact <?= $other && (int) $other['id'] === (int) $c['id'] ? 'active' : '' ?>" href="?with=<?= $c['id'] ?>">
                        <?= avatar($c, 'sm') ?>
                        <div class="list-body">
                            <strong><?= e($c['full_name']) ?><?= verified_tick($c) ?></strong>
                            <small><?= is_online($c['last_seen']) ? 'Online' : '@' . e($c['username']) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!$conversations && !$contacts): ?>
                <div class="empty"><i class="fa-regular fa-comments"></i>
                    <p class="small">There are no verified representatives in your area yet, so there is nobody to message.</p></div>
            <?php endif; ?>
        </div>

        <div class="chat-main">
            <?php if (!$other): ?>
                <div class="empty" style="margin:auto">
                    <i class="fa-regular fa-comment-dots"></i>
                    <h3>Select a conversation</h3>
                    <p>You can message neighbours when one of you is a verified local representative.</p>
                </div>
            <?php else: ?>
                <div class="chat-head">
                    <a href="<?= url('chat.php') ?>" class="btn btn-ghost btn-sm" title="Back"><i class="fa-solid fa-arrow-left"></i></a>
                    <?= avatar($other, 'md') ?>
                    <div>
                        <strong><?= e($other['full_name']) ?><?= verified_tick($other) ?></strong><br>
                        <small class="muted"><span class="<?= is_online($other['last_seen']) ? 'online-dot' : 'offline-dot' ?>"></span>
                            <?= is_online($other['last_seen']) ? 'Online' : ($other['last_seen'] ? 'Last seen ' . time_ago($other['last_seen']) : 'Offline') ?></small>
                    </div>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <?php foreach ($thread as $m): ?>
                        <div class="bubble <?= (int) $m['sender_id'] === $uid ? 'mine' : '' ?>">
                            <?php if ($m['attachment']): ?>
                                <a href="<?= e(url($m['attachment'])) ?>" target="_blank" rel="noopener"><img src="<?= e(url($m['attachment'])) ?>" alt="Attachment"></a>
                            <?php endif; ?>
                            <?php if ($m['body']): ?><div class="prose" style="color:inherit"><?= e($m['body']) ?></div><?php endif; ?>
                            <time><?= fmt_date($m['created_at'], 'd M, g:i A') ?></time>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$thread): ?><p class="muted text-center">Say hello 👋</p><?php endif; ?>
                </div>
                <?php if (can_message($user, $other)): ?>
                    <form class="chat-compose" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <label class="btn btn-outline chat-attach" title="Attach a photo">
                            <i class="fa-solid fa-image"></i><input type="file" name="attachment" accept="image/*">
                        </label>
                        <textarea class="textarea" name="body" rows="1" placeholder="Write a message…" maxlength="2000"></textarea>
                        <button class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning" style="margin:14px"><i class="fa-solid fa-lock"></i>
                        <span>You can't reply here: messaging is open between neighbours in the same area when at least one is a verified representative.</span></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
