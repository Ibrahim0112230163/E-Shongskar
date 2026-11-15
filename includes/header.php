<?php
/**
 * Site header. Set $pageTitle and $activeNav before including.
 */
$authUser = current_user();
$counts = $authUser ? unread_counts((int) $authUser['id']) : ['notifications' => 0, 'messages' => 0];
$activeNav = $activeNav ?? '';

$navItems = $authUser
    ? [
        'dashboard' => ['dashboard.php', 'fa-house', 'Home'],
        'reports'   => ['reports.php', 'fa-flag', 'Reports'],
        'events'    => ['events.php', 'fa-calendar-days', 'Events'],
        'blood'     => ['blood.php', 'fa-droplet', 'Blood'],
        'alerts'    => ['alerts.php', 'fa-bell', 'Alerts'],
        'emergency' => ['emergency.php', 'fa-truck-medical', 'Emergency'],
    ]
    : [
        'home'     => ['index.php', 'fa-house', 'Home'],
        'services' => ['index.php#services', 'fa-layer-group', 'Services'],
        'impact'   => ['index.php#impact', 'fa-chart-simple', 'Impact'],
        'about'    => ['index.php#about', 'fa-circle-info', 'About'],
    ];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(isset($pageTitle) ? $pageTitle . ' · ' . APP_NAME : APP_NAME . ' — ' . APP_TAGLINE) ?></title>
    <link rel="icon" href="<?= asset('img/logo-mark.jpg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&family=Hind+Siliguri:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">

<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="<?= url($authUser ? 'dashboard.php' : 'index.php') ?>">
            <img src="<?= asset('img/logo-mark.jpg') ?>" alt="">
            <span>E-Shongskar <small>সংস্কার</small></span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Menu" aria-expanded="false" data-nav-toggle>
            <i class="fa-solid fa-bars"></i>
        </button>

        <nav class="main-nav" data-nav>
            <?php foreach ($navItems as $key => [$href, $icon, $label]): ?>
                <a href="<?= url($href) ?>" class="<?= $activeNav === $key ? 'active' : '' ?>">
                    <i class="fa-solid <?= $icon ?>"></i><span><?= $label ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="topbar-actions">
            <?php if ($authUser): ?>
                <a class="icon-btn" href="<?= url('chat.php') ?>" title="Messages">
                    <i class="fa-solid fa-comment-dots"></i>
                    <?php if ($counts['messages']): ?><span class="dot"><?= $counts['messages'] ?></span><?php endif; ?>
                </a>
                <a class="icon-btn" href="<?= url('notifications.php') ?>" title="Notifications">
                    <i class="fa-solid fa-bell"></i>
                    <?php if ($counts['notifications']): ?><span class="dot"><?= $counts['notifications'] ?></span><?php endif; ?>
                </a>
                <details class="dropdown">
                    <summary class="avatar-btn" title="Account"><?= avatar($authUser, 'sm') ?></summary>
                    <div class="dropdown-menu">
                        <div class="dropdown-head">
                            <strong><?= e($authUser['full_name']) ?><?= verified_tick($authUser) ?></strong>
                            <small>@<?= e($authUser['username']) ?> · <?= e($authUser['area']) ?></small>
                        </div>
                        <a href="<?= url('profile.php') ?>"><i class="fa-solid fa-user"></i> My profile</a>
                        <a href="<?= url('leaderboard.php') ?>"><i class="fa-solid fa-trophy"></i> Leaderboard</a>
                        <a href="<?= url('completed_events.php') ?>"><i class="fa-solid fa-circle-check"></i> Completed events</a>
                        <a href="<?= url('neighbours.php') ?>"><i class="fa-solid fa-users"></i> Neighbours</a>
                        <?php if (current_admin()): ?>
                            <a href="<?= url('admin/index.php') ?>"><i class="fa-solid fa-shield-halved"></i> Admin panel</a>
                        <?php endif; ?>
                        <a href="<?= url('logout.php') ?>" class="danger"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
                    </div>
                </details>
            <?php else: ?>
                <a class="btn btn-ghost-light btn-sm" href="<?= url('login.php') ?>">Log in</a>
                <a class="btn btn-accent btn-sm" href="<?= url('register.php') ?>">Join now</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="site-main">
    <?php $flashes = render_flashes(); if ($flashes): ?>
        <div class="container flash-stack"><?= $flashes ?></div>
    <?php endif; ?>
