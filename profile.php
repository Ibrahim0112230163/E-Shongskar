<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$uid = (int) $user['id'];
$errors = [];

if (is_post()) {
    verify_csrf();
    switch (input('action')) {
        case 'details':
            $fullName = input('full_name');
            $phone = preg_replace('/[\s-]/', '', input('phone'));
            $blood = input('blood_group');
            $division = input('division');
            $area = normalize_area(input('area'));
            if (mb_strlen($fullName) < 3)                             $errors[] = 'Please enter your full name.';
            if (!preg_match('/^(\+?88)?01[3-9]\d{8}$/', $phone))      $errors[] = 'Please enter a valid Bangladeshi mobile number.';
            if (!in_array($blood, array_merge(BLOOD_GROUPS, ['Unknown']), true)) $errors[] = 'Please choose a blood group.';
            if (!in_array($division, DIVISIONS, true))                 $errors[] = 'Please choose a division.';
            if (mb_strlen($area) < 2)                                  $errors[] = 'Please enter your area.';
            if (!$errors) {
                $movedArea = strcasecmp($area, $user['area']) !== 0;
                $pdo->prepare('UPDATE users SET full_name = ?, phone = ?, blood_group = ?, occupation = ?, bio = ?, division = ?, area = ?, street = ?, house_no = ?'
                    . ($movedArea ? ', is_verified = 0, verified_at = NULL' : '') . ' WHERE id = ?')
                    ->execute([$fullName, $phone, $blood, input('occupation') ?: null, mb_substr(input('bio'), 0, 500) ?: null,
                        $division, $area, input('street') ?: null, input('house_no') ?: null, $uid]);
                flash('success', $movedArea && $user['is_verified']
                    ? 'Profile updated. Because you moved to a new area, your representative verification was removed.'
                    : 'Your profile was updated.');
                redirect('profile.php');
            }
            break;

        case 'avatar':
            try {
                $path = save_upload($_FILES['avatar'] ?? null, 'avatars');
                if ($path) {
                    $pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([$path, $uid]);
                    delete_upload($user['avatar']);
                    flash('success', 'Profile picture updated.');
                }
            } catch (RuntimeException $ex) {
                flash('error', $ex->getMessage());
            }
            redirect('profile.php');

        case 'remove_avatar':
            $pdo->prepare('UPDATE users SET avatar = NULL WHERE id = ?')->execute([$uid]);
            delete_upload($user['avatar']);
            redirect('profile.php');

        case 'socials':
            $links = (array) ($_POST['social'] ?? []);
            $bad = false;
            foreach (array_keys(SOCIAL_PLATFORMS) as $platform) {
                $link = trim($links[$platform] ?? '');
                if ($link === '') {
                    $pdo->prepare('DELETE FROM social_links WHERE user_id = ? AND platform = ?')->execute([$uid, $platform]);
                } elseif (filter_var($link, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $link)) {
                    $pdo->prepare('INSERT INTO social_links (user_id, platform, url) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE url = VALUES(url)')
                        ->execute([$uid, $platform, $link]);
                } else {
                    flash('error', $platform . ' link must be a full web address starting with https://');
                    $bad = true;
                }
            }
            if (!$bad) {
                flash('success', 'Social links saved.');
            }
            redirect('profile.php#socials');

        case 'interests':
            $chosen = array_intersect((array) ($_POST['interests'] ?? []), array_keys(EVENT_CATEGORIES));
            $pdo->prepare('DELETE FROM volunteer_interests WHERE user_id = ?')->execute([$uid]);
            $ins = $pdo->prepare('INSERT INTO volunteer_interests (user_id, category) VALUES (?, ?)');
            foreach ($chosen as $c) {
                $ins->execute([$uid, $c]);
            }
            flash('success', 'Volunteer interests saved. Matching events will now appear first.');
            redirect('profile.php#interests');

        case 'password':
            if (!password_verify($_POST['current_password'] ?? '', $user['password_hash'])) {
                $errors[] = 'Your current password is incorrect.';
            } elseif (strlen($_POST['password'] ?? '') < 6) {
                $errors[] = 'The new password must be at least 6 characters.';
            } elseif ($_POST['password'] !== ($_POST['password_confirm'] ?? '')) {
                $errors[] = 'The new passwords do not match.';
            } else {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $uid]);
                flash('success', 'Your password was changed.');
                redirect('profile.php');
            }
            break;
    }
}

$stmt = $pdo->prepare('SELECT * FROM user_scores WHERE id = ?');
$stmt->execute([$uid]);
$score = $stmt->fetch();

$stmt = $pdo->prepare('SELECT platform, url FROM social_links WHERE user_id = ?');
$stmt->execute([$uid]);
$socials = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare('SELECT category FROM volunteer_interests WHERE user_id = ?');
$stmt->execute([$uid]);
$interests = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT id, title, status, created_at FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$uid]);
$myReports = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT id, title, status, created_at FROM events WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$uid]);
$myEvents = $stmt->fetchAll();

$pageTitle = 'My profile';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>My profile</h1>
        <p>Keep your details up to date so your neighbours and representatives can reach you.</p>
    </div>
</section>

<div class="container layout-sidebar" style="grid-template-columns:320px minmax(0,1fr)">
    <aside class="stack">
        <div class="card profile-card">
            <?= avatar($user, 'xl') ?>
            <h2 class="mb-0"><?= e($user['full_name']) ?><?= verified_tick($user) ?></h2>
            <p class="muted">@<?= e($user['username']) ?></p>
            <?php if ($user['is_verified']): ?><p><span class="badge badge-primary"><i class="fa-solid fa-user-shield"></i> Local representative</span></p><?php endif; ?>
            <?php if ($user['bio']): ?><p class="small"><?= e($user['bio']) ?></p><?php endif; ?>
            <div class="socials mb-2">
                <?php foreach ($socials as $platform => $link): ?>
                    <a href="<?= e($link) ?>" target="_blank" rel="noopener" title="<?= e($platform) ?>"><i class="<?= SOCIAL_PLATFORMS[$platform] ?>"></i></a>
                <?php endforeach; ?>
            </div>
            <dl class="kv" style="text-align:left">
                <dt>Area</dt><dd><?= e($user['area']) ?>, <?= e($user['division']) ?></dd>
                <dt>Blood group</dt><dd><?= e($user['blood_group']) ?></dd>
                <dt>Phone</dt><dd><?= e($user['phone']) ?></dd>
                <dt>Member since</dt><dd><?= fmt_date($user['created_at'], 'M Y') ?></dd>
            </dl>
            <form method="post" enctype="multipart/form-data" class="stack mt-3">
                <?= csrf_field() ?><input type="hidden" name="action" value="avatar">
                <input class="input input-file" type="file" name="avatar" accept="image/*" required>
                <button class="btn btn-outline btn-sm"><i class="fa-solid fa-camera"></i> Upload new picture</button>
            </form>
            <?php if ($user['avatar']): ?>
                <form method="post" class="mt-1"><?= csrf_field() ?><input type="hidden" name="action" value="remove_avatar">
                    <button class="btn btn-ghost btn-sm">Remove picture</button></form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-star"></i> My contribution</h3>
            <div class="stat-value mt-1"><?= (int) $score['total_score'] ?> <small class="muted" style="font-size:.9rem">points</small></div>
            <dl class="kv mt-2">
                <dt>Reports</dt><dd><?= $score['reports_count'] ?></dd>
                <dt>Events</dt><dd><?= $score['events_count'] ?> (<?= $score['events_approved'] ?> approved)</dd>
                <dt>Fundraisers</dt><dd><?= $score['funds_count'] ?></dd>
                <dt>Joined</dt><dd><?= $score['participations'] ?> events</dd>
            </dl>
        </div>
    </aside>

    <div class="stack">
        <?php if ($errors): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= implode('<br>', array_map('e', $errors)) ?></span></div>
        <?php endif; ?>

        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-id-card"></i> Personal details</h3>
            <form method="post" class="form-grid mt-2">
                <?= csrf_field() ?><input type="hidden" name="action" value="details">
                <div class="field"><label for="full_name">Full name</label><input class="input" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" required></div>
                <div class="field"><label for="phone">Mobile</label><input class="input" id="phone" name="phone" value="<?= e($user['phone']) ?>" required></div>
                <div class="field"><label>Email</label><input class="input" value="<?= e($user['email']) ?>" disabled></div>
                <div class="field"><label>NID</label><input class="input" value="<?= e($user['nid']) ?>" disabled></div>
                <div class="field">
                    <label for="blood_group">Blood group</label>
                    <select class="select" id="blood_group" name="blood_group">
                        <option value="Unknown">I don't know</option>
                        <?php foreach (BLOOD_GROUPS as $bg): ?><option <?= selected($bg, $user['blood_group']) ?>><?= $bg ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label for="occupation">Occupation</label><input class="input" id="occupation" name="occupation" value="<?= e($user['occupation']) ?>"></div>
                <div class="field">
                    <label for="division">Division</label>
                    <select class="select" id="division" name="division">
                        <?php foreach (DIVISIONS as $d): ?><option <?= selected($d, $user['division']) ?>><?= $d ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label for="area">Area</label><input class="input" id="area" name="area" value="<?= e($user['area']) ?>" required>
                    <?php if ($user['is_verified']): ?><span class="hint">Changing your area removes your representative status.</span><?php endif; ?></div>
                <div class="field"><label for="street">Road / street</label><input class="input" id="street" name="street" value="<?= e($user['street']) ?>"></div>
                <div class="field"><label for="house_no">House no.</label><input class="input" id="house_no" name="house_no" value="<?= e($user['house_no']) ?>"></div>
                <div class="field full"><label for="bio">About me</label><textarea class="textarea" id="bio" name="bio" rows="3" maxlength="500"><?= e($user['bio']) ?></textarea></div>
                <div class="form-actions full"><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save details</button></div>
            </form>
        </div>

        <div class="card" id="interests">
            <h3 class="card-title"><i class="fa-solid fa-hand-holding-heart"></i> Volunteer interests</h3>
            <p class="muted small">Events in these categories will be shown to you first.</p>
            <form method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="interests">
                <div class="chips-select">
                    <?php foreach (EVENT_CATEGORIES as $c => $icon): ?>
                        <label><input type="checkbox" name="interests[]" value="<?= e($c) ?>" <?= checked(in_array($c, $interests, true)) ?>><span><i class="fa-solid <?= $icon ?>"></i> <?= e($c) ?></span></label>
                    <?php endforeach; ?>
                </div>
                <div class="form-actions"><button class="btn btn-primary btn-sm">Save interests</button></div>
            </form>
        </div>

        <div class="card" id="socials">
            <h3 class="card-title"><i class="fa-solid fa-share-nodes"></i> Social links</h3>
            <form method="post" class="form-grid mt-2">
                <?= csrf_field() ?><input type="hidden" name="action" value="socials">
                <?php foreach (SOCIAL_PLATFORMS as $platform => $icon): ?>
                    <div class="field">
                        <label for="s_<?= $platform ?>"><i class="<?= $icon ?>"></i> <?= $platform ?></label>
                        <input class="input" type="url" id="s_<?= $platform ?>" name="social[<?= $platform ?>]" value="<?= e($socials[$platform] ?? '') ?>" placeholder="https://">
                    </div>
                <?php endforeach; ?>
                <div class="form-actions full"><button class="btn btn-primary btn-sm">Save links</button></div>
            </form>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-flag"></i> My reports</h3><a class="btn btn-ghost btn-sm" href="<?= url('reports.php?scope=mine') ?>">All</a></div>
                <?php if (!$myReports): ?><p class="muted mb-0">No reports yet.</p><?php endif; ?>
                <ul class="list">
                    <?php foreach ($myReports as $r): ?>
                        <li class="list-item"><div class="list-body"><a href="<?= url('report.php?id=' . $r['id']) ?>"><?= e($r['title']) ?></a><small class="muted"><?= time_ago($r['created_at']) ?></small></div><?= status_badge($r['status']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-calendar"></i> My events</h3><a class="btn btn-ghost btn-sm" href="<?= url('events.php?scope=mine') ?>">All</a></div>
                <?php if (!$myEvents): ?><p class="muted mb-0">No events yet.</p><?php endif; ?>
                <ul class="list">
                    <?php foreach ($myEvents as $ev): ?>
                        <li class="list-item"><div class="list-body"><a href="<?= url('event.php?id=' . $ev['id']) ?>"><?= e($ev['title']) ?></a><small class="muted"><?= time_ago($ev['created_at']) ?></small></div><?= status_badge($ev['status']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title"><i class="fa-solid fa-key"></i> Change password</h3>
            <form method="post" class="form-grid mt-2">
                <?= csrf_field() ?><input type="hidden" name="action" value="password">
                <div class="field full"><label for="current_password">Current password</label><input class="input" type="password" id="current_password" name="current_password" required autocomplete="current-password"></div>
                <div class="field"><label for="password">New password</label><input class="input" type="password" id="password" name="password" minlength="6" required autocomplete="new-password"></div>
                <div class="field"><label for="password_confirm">Confirm new password</label><input class="input" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"></div>
                <div class="form-actions full"><button class="btn btn-primary btn-sm">Change password</button></div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
