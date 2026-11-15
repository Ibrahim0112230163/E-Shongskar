<?php
/**
 * Admin layout header. Set $pageTitle and $adminNav before including.
 */
$admin = require_admin();
$badge = $pdo->query("SELECT
    (SELECT COUNT(*) FROM reports WHERE status = 'Pending') AS reports,
    (SELECT COUNT(*) FROM events WHERE status IN ('completed','verified')) + (SELECT COUNT(*) FROM event_funds WHERE status = 'pending') AS events,
    (SELECT COUNT(*) FROM sos_requests WHERE status = 'open') AS sos")->fetch();

$menu = [
    'dashboard' => ['admin/index.php', 'fa-gauge-high', 'Dashboard', 0],
    'reports'   => ['admin/reports.php', 'fa-flag', 'Reports', $badge['reports']],
    'events'    => ['admin/events.php', 'fa-calendar-check', 'Events & funds', $badge['events']],
    'users'     => ['admin/users.php', 'fa-users', 'Users', 0],
    'sos'       => ['admin/sos.php', 'fa-tower-broadcast', 'SOS requests', $badge['sos']],
    'alerts'    => ['admin/alerts.php', 'fa-triangle-exclamation', 'Alerts', 0],
    'contacts'  => ['admin/contacts.php', 'fa-address-book', 'Emergency contacts', 0],
];
$adminNav = $adminNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($pageTitle ?? 'Admin') . ' · Admin · ' . APP_NAME) ?></title>
    <link rel="icon" href="<?= asset('img/logo-mark.jpg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&family=Hind+Siliguri:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-side">
        <a class="brand" href="<?= url('admin/index.php') ?>">
            <img src="<?= asset('img/logo-mark.jpg') ?>" alt="">
            <span>E-Shongskar <small>Admin</small></span>
        </a>
        <nav>
            <?php foreach ($menu as $key => [$href, $icon, $label, $count]): ?>
                <a href="<?= url($href) ?>" class="<?= $adminNav === $key ? 'active' : '' ?>">
                    <i class="fa-solid <?= $icon ?>"></i> <?= $label ?>
                    <?php if ($count): ?><span class="count"><?= $count ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
            <div class="side-label">Account</div>
            <a href="<?= url('admin/account.php') ?>" class="<?= $adminNav === 'account' ? 'active' : '' ?>"><i class="fa-solid fa-user-gear"></i> Admin accounts</a>
            <a href="<?= url('index.php') ?>"><i class="fa-solid fa-globe"></i> View site</a>
            <a href="<?= url('logout.php?scope=admin') ?>"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
        </nav>
    </aside>

    <div class="admin-main">
        <header class="admin-top">
            <div class="row">
                <button class="admin-menu-btn" type="button" data-admin-menu aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
                <h1><?= e($pageTitle ?? 'Admin') ?></h1>
            </div>
            <div class="user-cell">
                <span class="avatar avatar-sm avatar-initials" style="--hue:150"><?= e(mb_strtoupper(mb_substr($admin['full_name'], 0, 1))) ?></span>
                <div><strong><?= e($admin['full_name']) ?></strong><small>@<?= e($admin['username']) ?></small></div>
            </div>
        </header>
        <div class="admin-content">
            <?= render_flashes() ?>
