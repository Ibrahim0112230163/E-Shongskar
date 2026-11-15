<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();

$errors = [];

if (is_post()) {
    verify_csrf();
    $title = input('title');
    $category = input('category');
    $description = input('description');
    $venue = input('venue');
    $date = input('event_date');
    $contact = preg_replace('/[\s-]/', '', input('contact', $user['phone']));

    if (mb_strlen($title) < 5 || mb_strlen($title) > 150) $errors[] = 'The title should be between 5 and 150 characters.';
    if (!array_key_exists($category, EVENT_CATEGORIES))  $errors[] = 'Please choose a category.';
    if (mb_strlen($description) < 20)                    $errors[] = 'Please describe the event in a bit more detail.';
    if (!preg_match('/^\+?\d{6,15}$/', $contact))        $errors[] = 'Please enter a valid contact number.';
    $eventDate = null;
    if ($date !== '') {
        $ts = strtotime($date);
        if (!$ts)                       $errors[] = 'Please enter a valid date and time.';
        elseif ($ts < time() - 3600)    $errors[] = 'The event date cannot be in the past.';
        else                            $eventDate = date('Y-m-d H:i:s', $ts);
    }

    if (!$errors) {
        $pdo->prepare('INSERT INTO events (user_id, title, category, description, division, area, venue, event_date, contact) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([$user['id'], $title, $category, $description, $user['division'], $user['area'], $venue ?: null, $eventDate, $contact]);
        $id = (int) $pdo->lastInsertId();
        // The organiser takes part in their own event
        $pdo->prepare('INSERT INTO event_participants (event_id, user_id) VALUES (?, ?)')->execute([$id, $user['id']]);

        notify_area($user['area'], 'event', $user['full_name'] . ' created a new event: "' . $title . '". Join in!', 'event.php?id=' . $id, (int) $user['id']);

        flash('success', 'Your event is live. Neighbours in ' . $user['area'] . ' have been notified.');
        redirect('event.php?id=' . $id);
    }
}

$pageTitle = 'Create event';
$activeNav = 'events';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container container-narrow">
        <div class="breadcrumb"><a href="<?= url('events.php') ?>">Events</a> / New</div>
        <h1>Create a community event</h1>
        <p>Organise something good for <?= e($user['area']) ?>. You can request funding after the event is created.</p>
    </div>
</section>

<div class="container container-narrow">
    <div class="card">
        <?php if ($errors): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= implode('<br>', array_map('e', $errors)) ?></span></div>
        <?php endif; ?>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <div class="field full">
                <label for="title">Event title <span class="req">*</span></label>
                <input class="input" id="title" name="title" maxlength="150" value="<?= old('title') ?>" placeholder="e.g. Saturday canal clean-up" required>
            </div>
            <div class="field full">
                <span class="label">Category <span class="req">*</span></span>
                <div class="chips-select">
                    <?php foreach (EVENT_CATEGORIES as $c => $icon): ?>
                        <label><input type="radio" name="category" value="<?= e($c) ?>" <?= checked(($_POST['category'] ?? '') === $c) ?> required><span><i class="fa-solid <?= $icon ?>"></i> <?= e($c) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="field">
                <label for="event_date">Date &amp; time</label>
                <input class="input" type="datetime-local" id="event_date" name="event_date" value="<?= old('event_date') ?>" min="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="field">
                <label for="venue">Venue / meeting point</label>
                <input class="input" id="venue" name="venue" maxlength="255" value="<?= old('venue') ?>" placeholder="e.g. Block C playground">
            </div>
            <div class="field">
                <label for="contact">Contact number <span class="req">*</span></label>
                <input class="input" id="contact" name="contact" value="<?= old('contact', $user['phone']) ?>" required>
            </div>
            <div class="field">
                <label>Area</label>
                <input class="input" value="<?= e($user['area'] . ', ' . $user['division']) ?>" disabled>
            </div>
            <div class="field full">
                <label for="description">Description <span class="req">*</span></label>
                <textarea class="textarea" id="description" name="description" rows="7" placeholder="What will happen, what should people bring, who is it for?" required><?= old('description') ?></textarea>
            </div>
            <div class="form-actions full">
                <a href="<?= url('events.php') ?>" class="btn btn-ghost">Cancel</a>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-calendar-plus"></i> Publish event</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
