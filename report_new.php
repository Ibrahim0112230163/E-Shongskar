<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$errors = [];

if (is_post()) {
    verify_csrf();
    $title = input('title');
    $type = input('issue_type');
    $description = input('description');
    $address = input('address');

    if (mb_strlen($title) < 5)      $errors[] = 'Please give the issue a short, clear title (at least 5 characters).';
    if (mb_strlen($title) > 150)    $errors[] = 'The title is too long.';
    if (!in_array($type, ISSUE_TYPES, true)) $errors[] = 'Please choose an issue type.';
    if (mb_strlen($description) < 15) $errors[] = 'Please describe the issue in a bit more detail.';

    $photo = null;
    if (!$errors) {
        try {
            $photo = save_upload($_FILES['photo'] ?? null, 'reports');
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $pdo->prepare('INSERT INTO reports (user_id, title, issue_type, description, division, area, address, photo) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$user['id'], $title, $type, $description, $user['division'], $user['area'], $address ?: null, $photo]);
        $id = (int) $pdo->lastInsertId();

        notify_area($user['area'], 'report', $user['full_name'] . ' reported "' . $title . '" in your area. Add your support if it affects you.', 'report.php?id=' . $id, (int) $user['id']);

        flash('success', 'Thank you! Your report has been submitted and your neighbours have been notified.');
        redirect('report.php?id=' . $id);
    }
}

$pageTitle = 'Report an issue';
$activeNav = 'reports';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container container-narrow">
        <div class="breadcrumb"><a href="<?= url('reports.php') ?>">Reports</a> / New</div>
        <h1>Report an issue</h1>
        <p>Tell your community and the authorities about a problem in <?= e($user['area']) ?>. A clear photo helps a lot.</p>
    </div>
</section>

<div class="container container-narrow">
    <div class="card">
        <?php if ($errors): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= implode('<br>', array_map('e', $errors)) ?></span></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?>
            <div class="field full">
                <label for="title">Title <span class="req">*</span></label>
                <input class="input" id="title" name="title" maxlength="150" value="<?= old('title') ?>" placeholder="e.g. Large pothole in front of the primary school" required>
            </div>
            <div class="field">
                <label for="issue_type">Issue type <span class="req">*</span></label>
                <select class="select" id="issue_type" name="issue_type" required>
                    <option value="">Select…</option>
                    <?php foreach (ISSUE_TYPES as $t): ?><option <?= selected($t, $_POST['issue_type'] ?? '') ?>><?= $t ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="address">Exact location</label>
                <input class="input" id="address" name="address" maxlength="255" value="<?= old('address') ?>" placeholder="Road, landmark…">
            </div>
            <div class="field full">
                <label for="description">Description <span class="req">*</span></label>
                <textarea class="textarea" id="description" name="description" rows="6" placeholder="What is the problem, since when, who is affected?" required><?= old('description') ?></textarea>
            </div>
            <div class="field full">
                <label for="photo">Photo</label>
                <input class="input input-file" type="file" id="photo" name="photo" accept="image/*" data-preview="photoPreview">
                <span class="hint">JPG, PNG, GIF or WEBP — up to 5 MB.</span>
                <img id="photoPreview" class="detail-photo hidden mt-1" alt="">
            </div>
            <div class="full">
                <div class="alert alert-info mb-0"><i class="fa-solid fa-location-dot"></i>
                    <span>This report will be filed under <strong><?= e($user['area']) ?>, <?= e($user['division']) ?></strong> and shared with your neighbours.</span>
                </div>
            </div>
            <div class="form-actions full">
                <a href="<?= url('reports.php') ?>" class="btn btn-ghost">Cancel</a>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-paper-plane"></i> Submit report</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
