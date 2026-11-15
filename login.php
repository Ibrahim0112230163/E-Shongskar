<?php
require_once __DIR__ . '/includes/functions.php';

$mode = input('mode') === 'admin' ? 'admin' : 'user';

if ($mode === 'user' && current_user()) {
    redirect('dashboard.php');
}
if ($mode === 'admin' && current_admin()) {
    redirect('admin/index.php');
}

$error = '';

if (is_post()) {
    verify_csrf();
    $login = input('login');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } elseif ($mode === 'admin') {
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
        $stmt->execute([$login]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $pdo->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);
            flash('success', 'Welcome back, ' . $admin['full_name'] . '.');
            redirect('admin/index.php');
        }
        $error = 'Invalid admin username or password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $pdo->prepare('UPDATE users SET last_seen = NOW() WHERE id = ?')->execute([$user['id']]);
            flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect('dashboard.php');
        }
        $error = 'Invalid username/email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $mode === 'admin' ? 'Admin login' : 'Log in' ?> · <?= APP_NAME ?></title>
    <link rel="icon" href="<?= asset('img/logo-mark.jpg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&family=Hind+Siliguri:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<div class="auth-shell">
    <div class="auth-card">
        <div class="auth-media">
            <video autoplay loop muted playsinline poster="<?= asset('img/logo-mark.jpg') ?>">
                <source src="<?= asset('video/community.mp4') ?>" type="video/mp4">
            </video>
            <div class="overlay"></div>
            <div class="content">
                <a class="brand" href="<?= url('index.php') ?>">
                    <img src="<?= asset('img/logo-mark.jpg') ?>" alt="">
                    <span>E-Shongskar <small>সংস্কার</small></span>
                </a>
                <h2>Let's reform together.</h2>
                <p>Report local problems, organise your neighbours and keep your community safe.</p>
                <div class="auth-points">
                    <span><i class="fa-solid fa-check"></i> Local first</span>
                    <span><i class="fa-solid fa-check"></i> Easy reporting</span>
                    <span><i class="fa-solid fa-check"></i> Verified representatives</span>
                </div>
            </div>
        </div>

        <div class="auth-form">
            <h1><?= $mode === 'admin' ? 'Admin access' : 'Welcome back' ?></h1>
            <p class="muted"><?= $mode === 'admin' ? 'For authorised administrators only.' : 'Log in to see what is happening in your area.' ?></p>

            <div class="auth-switch">
                <a href="<?= url('login.php') ?>" class="<?= $mode === 'user' ? 'active' : '' ?>"><i class="fa-solid fa-user"></i> Citizen</a>
                <a href="<?= url('login.php?mode=admin') ?>" class="<?= $mode === 'admin' ? 'active' : '' ?>"><i class="fa-solid fa-shield-halved"></i> Admin</a>
            </div>

            <?= render_flashes() ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div>
            <?php endif; ?>

            <form method="post" class="stack">
                <?= csrf_field() ?>
                <input type="hidden" name="mode" value="<?= $mode ?>">
                <div class="field">
                    <label for="login"><?= $mode === 'admin' ? 'Admin username' : 'Username or email' ?></label>
                    <div class="input-icon">
                        <i class="fa-solid fa-user"></i>
                        <input class="input" id="login" name="login" value="<?= old('login') ?>" required autofocus autocomplete="username">
                    </div>
                </div>
                <div class="field">
                    <label for="pw">Password</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-lock"></i>
                        <input class="input" type="password" id="pw" name="password" required autocomplete="current-password">
                    </div>
                </div>
                <button class="btn btn-primary btn-lg btn-block" type="submit">
                    <i class="fa-solid fa-right-to-bracket"></i> <?= $mode === 'admin' ? 'Log in as admin' : 'Log in' ?>
                </button>
            </form>

            <?php if ($mode === 'user'): ?>
                <p class="text-center mt-3 muted">New to E-Shongskar? <a href="<?= url('register.php') ?>"><strong>Create an account</strong></a></p>
            <?php endif; ?>
            <p class="text-center mt-2"><a href="<?= url('index.php') ?>" class="muted small"><i class="fa-solid fa-arrow-left"></i> Back to home</a></p>
        </div>
    </div>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
