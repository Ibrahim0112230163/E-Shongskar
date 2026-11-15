<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];

if (is_post()) {
    verify_csrf();

    $data = [
        'username'    => input('username'),
        'full_name'   => input('full_name'),
        'email'       => strtolower(input('email')),
        'phone'       => preg_replace('/[\s-]/', '', input('phone')),
        'nid'         => preg_replace('/\s/', '', input('nid')),
        'dob'         => input('dob'),
        'gender'      => input('gender'),
        'blood_group' => input('blood_group', 'Unknown'),
        'occupation'  => input('occupation'),
        'division'    => input('division'),
        'area'        => normalize_area(input('area')),
        'street'      => input('street'),
        'house_no'    => input('house_no'),
    ];
    $password = $_POST['password'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $data['username'])) {
        $errors[] = 'Username must be 3–30 characters: letters, numbers, dots or underscores.';
    }
    if (mb_strlen($data['full_name']) < 3) {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^(\+?88)?01[3-9]\d{8}$/', $data['phone'])) {
        $errors[] = 'Please enter a valid Bangladeshi mobile number (e.g. 01712345678).';
    }
    if (!preg_match('/^\d{10}$|^\d{13}$|^\d{17}$/', $data['nid'])) {
        $errors[] = 'NID must be 10 or 13 digits, or a 17-digit birth certificate number.';
    }
    $dob = DateTime::createFromFormat('Y-m-d', $data['dob']);
    if (!$dob || $dob > new DateTime('-12 years')) {
        $errors[] = 'You must be at least 12 years old to register.';
    }
    if (!in_array($data['gender'], ['Male', 'Female', 'Other'], true)) {
        $errors[] = 'Please choose your gender.';
    }
    if (!in_array($data['blood_group'], array_merge(BLOOD_GROUPS, ['Unknown']), true)) {
        $data['blood_group'] = 'Unknown';
    }
    if (!in_array($data['division'], DIVISIONS, true)) {
        $errors[] = 'Please choose your division.';
    }
    if (mb_strlen($data['area']) < 2) {
        $errors[] = 'Please enter your area (thana / upazila / neighbourhood).';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } elseif ($password !== ($_POST['password_confirm'] ?? '')) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $dup = $pdo->prepare('SELECT username, email, nid FROM users WHERE username = ? OR email = ? OR nid = ?');
        $dup->execute([$data['username'], $data['email'], $data['nid']]);
        foreach ($dup->fetchAll() as $row) {
            if (strcasecmp($row['username'], $data['username']) === 0) $errors[] = 'That username is already taken.';
            if (strcasecmp($row['email'], $data['email']) === 0)       $errors[] = 'An account with this email already exists.';
            if ($row['nid'] === $data['nid'])                           $errors[] = 'An account with this NID already exists.';
        }
    }

    if (!$errors) {
        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        foreach (['occupation', 'street', 'house_no'] as $optional) {
            $data[$optional] = $data[$optional] !== '' ? $data[$optional] : null;
        }
        $cols = array_keys($data);
        $pdo->prepare('INSERT INTO users (' . implode(',', $cols) . ') VALUES (' . rtrim(str_repeat('?,', count($cols)), ',') . ')')
            ->execute(array_values($data));

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $pdo->lastInsertId();
        flash('success', 'Welcome to E-Shongskar, ' . $data['full_name'] . '! You are now part of the ' . $data['area'] . ' community.');
        redirect('dashboard.php');
    }
}

$areas = $pdo->query('SELECT DISTINCT area FROM users ORDER BY area')->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account · <?= APP_NAME ?></title>
    <link rel="icon" href="<?= asset('img/logo-mark.jpg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&family=Hind+Siliguri:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="auth-body">
<div class="auth-shell">
    <div class="auth-card wide">
        <div class="auth-media">
            <img src="<?= asset('img/service-events.jpg') ?>" alt="">
            <div class="overlay"></div>
            <div class="content">
                <a class="brand" href="<?= url('index.php') ?>">
                    <img src="<?= asset('img/logo-mark.jpg') ?>" alt="">
                    <span>E-Shongskar <small>সংস্কার</small></span>
                </a>
                <h2>Join your community.</h2>
                <p>Your area decides what you see: reports, events, alerts and blood donors from the people around you.</p>
                <div class="auth-points">
                    <span><i class="fa-solid fa-shield"></i> NID-verified members</span>
                    <span><i class="fa-solid fa-lock"></i> Your number stays private</span>
                </div>
            </div>
        </div>

        <div class="auth-form">
            <h1>Create your account</h1>
            <p class="muted">Already registered? <a href="<?= url('login.php') ?>"><strong>Log in</strong></a></p>

            <?php if ($errors): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= implode('<br>', array_map('e', array_unique($errors))) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" class="form-grid" novalidate>
                <?= csrf_field() ?>

                <div class="form-section-title">Account</div>
                <div class="field">
                    <label for="username">Username <span class="req">*</span></label>
                    <input class="input" id="username" name="username" value="<?= old('username') ?>" required pattern="[A-Za-z0-9_.]{3,30}" autocomplete="username">
                </div>
                <div class="field">
                    <label for="email">Email <span class="req">*</span></label>
                    <input class="input" type="email" id="email" name="email" value="<?= old('email') ?>" required autocomplete="email">
                </div>
                <div class="field">
                    <label for="password">Password <span class="req">*</span></label>
                    <input class="input" type="password" id="password" name="password" minlength="6" required autocomplete="new-password">
                    <span class="hint">At least 6 characters.</span>
                </div>
                <div class="field">
                    <label for="password_confirm">Confirm password <span class="req">*</span></label>
                    <input class="input" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
                </div>

                <div class="form-section-title">Personal details</div>
                <div class="field">
                    <label for="full_name">Full name <span class="req">*</span></label>
                    <input class="input" id="full_name" name="full_name" value="<?= old('full_name') ?>" required autocomplete="name">
                </div>
                <div class="field">
                    <label for="nid">NID / Birth certificate no. <span class="req">*</span></label>
                    <input class="input" id="nid" name="nid" value="<?= old('nid') ?>" inputmode="numeric" required>
                </div>
                <div class="field">
                    <label for="phone">Mobile number <span class="req">*</span></label>
                    <input class="input" id="phone" name="phone" value="<?= old('phone') ?>" placeholder="01XXXXXXXXX" inputmode="tel" required autocomplete="tel">
                </div>
                <div class="field">
                    <label for="dob">Date of birth <span class="req">*</span></label>
                    <input class="input" type="date" id="dob" name="dob" value="<?= old('dob') ?>" max="<?= date('Y-m-d', strtotime('-12 years')) ?>" required>
                </div>
                <div class="field">
                    <label for="gender">Gender <span class="req">*</span></label>
                    <select class="select" id="gender" name="gender" required>
                        <option value="">Select…</option>
                        <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                            <option <?= selected($g, $_POST['gender'] ?? '') ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="blood_group">Blood group</label>
                    <select class="select" id="blood_group" name="blood_group">
                        <option value="Unknown">I don't know</option>
                        <?php foreach (BLOOD_GROUPS as $bg): ?>
                            <option <?= selected($bg, $_POST['blood_group'] ?? '') ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field full">
                    <label for="occupation">Occupation</label>
                    <input class="input" id="occupation" name="occupation" value="<?= old('occupation') ?>">
                </div>

                <div class="form-section-title">Where you live</div>
                <div class="field">
                    <label for="division">Division <span class="req">*</span></label>
                    <select class="select" id="division" name="division" required>
                        <option value="">Select…</option>
                        <?php foreach (DIVISIONS as $d): ?>
                            <option <?= selected($d, $_POST['division'] ?? '') ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="area">Area <span class="req">*</span></label>
                    <input class="input" id="area" name="area" list="areaList" value="<?= old('area') ?>" placeholder="e.g. Mirpur, Dhanmondi, Agrabad" required>
                    <datalist id="areaList">
                        <?php foreach ($areas as $a): ?><option value="<?= e($a) ?>"><?php endforeach; ?>
                    </datalist>
                    <span class="hint">Pick an existing area if yours is listed, so you join the same community.</span>
                </div>
                <div class="field">
                    <label for="street">Road / street</label>
                    <input class="input" id="street" name="street" value="<?= old('street') ?>">
                </div>
                <div class="field">
                    <label for="house_no">House no.</label>
                    <input class="input" id="house_no" name="house_no" value="<?= old('house_no') ?>">
                </div>

                <div class="full mt-2">
                    <button class="btn btn-primary btn-lg btn-block" type="submit"><i class="fa-solid fa-user-plus"></i> Create account</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
