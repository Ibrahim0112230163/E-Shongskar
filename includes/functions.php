<?php
/**
 * Shared helpers for every page. Including this file also opens the
 * session and the database connection ($pdo).
 */
require_once __DIR__ . '/db.php';

/* ------------------------------------------------------------------
 *  Reference lists (must match the ENUMs in database/e_shongskar.sql)
 * ------------------------------------------------------------------ */

const DIVISIONS = ['Dhaka', 'Chattogram', 'Rajshahi', 'Khulna', 'Barishal', 'Sylhet', 'Rangpur', 'Mymensingh'];

const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

const ISSUE_TYPES = [
    'Damaged Roads', 'Infrastructure Issues', 'Harassment Concerns', 'Hygiene Problems', 'Market Issues',
    'Health Problems', 'Community Issues', 'Waste Disposal', 'Poor Parks', 'Overcrowding', 'Housing Shortage',
    'Education Gaps', 'Corruption', 'Service Inefficiency', 'Flooding Challenges', 'Others',
];

const REPORT_STATUSES = ['Pending', 'In Progress', 'Solved', 'Denied'];

const EVENT_CATEGORIES = [
    'Community Development'          => 'fa-people-roof',
    'Health and Wellness'            => 'fa-heart-pulse',
    'Education and Awareness'        => 'fa-graduation-cap',
    'Social and Cultural'            => 'fa-masks-theater',
    'Environment and Sustainability' => 'fa-leaf',
    'Others'                         => 'fa-star',
];

const CONTACT_CATEGORIES = ['Police', 'Fire Service', 'Hospital', 'Ambulance', 'Helpline', 'Utility', 'Other'];

const SOCIAL_PLATFORMS = [
    'Facebook'  => 'fa-brands fa-facebook',
    'LinkedIn'  => 'fa-brands fa-linkedin',
    'Instagram' => 'fa-brands fa-instagram',
    'X'         => 'fa-brands fa-x-twitter',
    'Website'   => 'fa-solid fa-globe',
];

const DONATION_METHODS = ['bKash', 'Nagad', 'Rocket', 'Bank', 'Cash'];

// Recipient blood group => groups that can donate to it
const BLOOD_COMPATIBILITY = [
    'O-'  => ['O-'],
    'O+'  => ['O+', 'O-'],
    'A-'  => ['A-', 'O-'],
    'A+'  => ['A+', 'A-', 'O+', 'O-'],
    'B-'  => ['B-', 'O-'],
    'B+'  => ['B+', 'B-', 'O+', 'O-'],
    'AB-' => ['AB-', 'A-', 'B-', 'O-'],
    'AB+' => ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'],
];

const DONATION_GAP_DAYS = 90;

/* ------------------------------------------------------------------
 *  Output, URLs and requests
 * ------------------------------------------------------------------ */

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

/** Redirect back to the page the request came from (same site only). */
function redirect_back(string $fallback = 'dashboard.php'): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === $host) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, $default = '')
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

/** Previously submitted value, used to refill a form after a validation error. */
function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

function selected($value, $current): string
{
    return (string) $value === (string) $current ? 'selected' : '';
}

function checked(bool $condition): string
{
    return $condition ? 'checked' : '';
}

/* ------------------------------------------------------------------
 *  Flash messages
 * ------------------------------------------------------------------ */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function render_flashes(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $icon = [
            'success' => 'fa-circle-check',
            'error'   => 'fa-circle-exclamation',
            'warning' => 'fa-triangle-exclamation',
        ][$f['type']] ?? 'fa-circle-info';
        $html .= '<div class="alert alert-' . e($f['type']) . '" role="status"><i class="fa-solid ' . $icon . '"></i>'
            . '<span>' . e($f['message']) . '</span>'
            . '<button type="button" class="alert-close" aria-label="Dismiss">&times;</button></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/* ------------------------------------------------------------------
 *  CSRF protection
 * ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        flash('error', 'Your session expired. Please try again.');
        redirect_back();
    }
}

/* ------------------------------------------------------------------
 *  Authentication
 * ------------------------------------------------------------------ */

function current_user(): ?array
{
    global $pdo;
    static $user = false;

    if ($user !== false) {
        return $user;
    }
    if (empty($_SESSION['user_id'])) {
        return $user = null;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }

    // Refresh "last seen" at most once a minute
    if (($_SESSION['seen_at'] ?? 0) < time() - 60) {
        $pdo->prepare('UPDATE users SET last_seen = NOW() WHERE id = ?')->execute([$user['id']]);
        $_SESSION['seen_at'] = time();
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
    return $user;
}

function current_admin(): ?array
{
    global $pdo;
    static $admin = false;

    if ($admin !== false) {
        return $admin;
    }
    if (empty($_SESSION['admin_id'])) {
        return $admin = null;
    }
    $stmt = $pdo->prepare('SELECT id, username, full_name, user_id FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch() ?: null;
    if (!$admin) {
        unset($_SESSION['admin_id']);
    }
    return $admin;
}

function require_admin(): array
{
    $admin = current_admin();
    if (!$admin) {
        flash('warning', 'Please log in as an administrator.');
        redirect('login.php?mode=admin');
    }
    return $admin;
}

/* ------------------------------------------------------------------
 *  Formatting
 * ------------------------------------------------------------------ */

function normalize_area(string $area): string
{
    $area = preg_replace('/\s+/u', ' ', trim($area));
    return mb_convert_case($area, MB_CASE_TITLE, 'UTF-8');
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'just now';
    }
    $units = [31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];
    foreach ($units as $secs => $name) {
        if ($diff >= $secs) {
            $n = intdiv($diff, $secs);
            return $n . ' ' . $name . ($n > 1 ? 's' : '') . ' ago';
        }
    }
    return '';
}

function fmt_date(?string $date, string $format = 'd M Y'): string
{
    return $date ? date($format, strtotime($date)) : '—';
}

function money($amount): string
{
    return '৳' . number_format((float) $amount, 0);
}

function excerpt(?string $text, int $length = 140): string
{
    $text = trim((string) $text);
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '…' : $text;
}

function status_badge(string $status): string
{
    $map = [
        'Pending'     => 'warning',
        'In Progress' => 'info',
        'Solved'      => 'success',
        'Denied'      => 'danger',
        'active'      => 'info',
        'completed'   => 'warning',
        'verified'    => 'primary',
        'approved'    => 'success',
        'pending'     => 'warning',
        'open'        => 'success',
        'closed'      => 'muted',
        'accepted'    => 'success',
        'declined'    => 'danger',
        'resolved'    => 'success',
    ];
    return '<span class="badge badge-' . ($map[$status] ?? 'muted') . '">' . e(ucfirst($status)) . '</span>';
}

function severity_badge(string $severity): string
{
    $map = ['info' => ['info', 'fa-circle-info'], 'warning' => ['warning', 'fa-triangle-exclamation'], 'critical' => ['danger', 'fa-radiation']];
    [$cls, $icon] = $map[$severity] ?? $map['info'];
    return '<span class="badge badge-' . $cls . '"><i class="fa-solid ' . $icon . '"></i> ' . e(ucfirst($severity)) . '</span>';
}

/** Profile picture, or a coloured circle with the user's initials. */
function avatar(?array $user, string $size = 'md'): string
{
    $name = $user['full_name'] ?? $user['username'] ?? '?';
    if (!empty($user['avatar'])) {
        return '<img class="avatar avatar-' . $size . '" src="' . e(url($user['avatar'])) . '" alt="' . e($name) . '">';
    }
    $parts = preg_split('/\s+/u', trim($name));
    $initials = mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    $hue = crc32($name) % 360;
    return '<span class="avatar avatar-' . $size . ' avatar-initials" style="--hue:' . $hue . '" aria-label="' . e($name) . '">' . e($initials) . '</span>';
}

function verified_tick(?array $user): string
{
    return !empty($user['is_verified'])
        ? ' <i class="fa-solid fa-circle-check verified-tick" title="Verified local representative"></i>'
        : '';
}

/* ------------------------------------------------------------------
 *  File uploads
 * ------------------------------------------------------------------ */

/**
 * Save an uploaded image into uploads/<folder>/ and return its relative path.
 * Returns null when no file was chosen; throws RuntimeException on bad files.
 */
function save_upload(?array $file, string $folder): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file could not be uploaded. Please try again.');
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Images must be smaller than 5 MB.');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, GIF or WEBP images are allowed.');
    }

    $dir = UPLOAD_DIR . '/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    return 'uploads/' . $folder . '/' . $name;
}

function delete_upload(?string $path): void
{
    if ($path && strpos($path, 'uploads/') === 0 && strpos($path, '..') === false) {
        $full = dirname(__DIR__) . '/' . $path;
        if (is_file($full)) {
            unlink($full);
        }
    }
}

/* ------------------------------------------------------------------
 *  Notifications
 * ------------------------------------------------------------------ */

function notify(int $userId, string $type, string $message, ?string $link = null): void
{
    global $pdo;
    $pdo->prepare('INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)')
        ->execute([$userId, $type, $message, $link]);
}

/** Notify everybody living in an area, optionally skipping the person who acted. */
function notify_area(string $area, string $type, string $message, ?string $link = null, int $exceptUserId = 0): void
{
    global $pdo;
    $pdo->prepare('INSERT INTO notifications (user_id, type, message, link)
                   SELECT id, ?, ?, ? FROM users WHERE area = ? AND id <> ?')
        ->execute([$type, $message, $link, $area, $exceptUserId]);
}

function unread_counts(int $userId): array
{
    global $pdo;
    $n = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $n->execute([$userId]);
    $m = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0');
    $m->execute([$userId]);
    return ['notifications' => (int) $n->fetchColumn(), 'messages' => (int) $m->fetchColumn()];
}

/* ------------------------------------------------------------------
 *  Shared queries
 * ------------------------------------------------------------------ */

/** Alerts currently in effect for a user's division and area. */
function active_alerts(array $user, int $limit = 0): array
{
    global $pdo;
    $sql = "SELECT * FROM alerts
            WHERE CURDATE() BETWEEN start_date AND end_date
              AND (division IS NULL OR (division = ? AND (area IS NULL OR area = ?)))
            ORDER BY FIELD(severity, 'critical', 'warning', 'info'), start_date DESC";
    if ($limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user['division'], $user['area']]);
    return $stmt->fetchAll();
}

/** Leaderboard rows. Pass $since (Y-m-d) to only count activity from that date. */
function leaderboard(?string $area = null, int $limit = 10, ?string $since = null): array
{
    global $pdo;

    if ($since === null) {
        $sql = 'SELECT * FROM user_scores WHERE total_score > 0' . ($area ? ' AND area = ?' : '')
             . ' ORDER BY total_score DESC, username LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($area ? [$area] : []);
        return $stmt->fetchAll();
    }

    $sql = "SELECT u.id, u.username, u.full_name, u.avatar, u.area, u.is_verified,
              (SELECT COUNT(*) FROM reports r WHERE r.user_id = u.id AND r.created_at >= ?) * 5
            + (SELECT COUNT(*) FROM events e WHERE e.user_id = u.id AND e.created_at >= ?) * 4
            + (SELECT COUNT(*) FROM events e WHERE e.user_id = u.id AND e.status = 'approved' AND e.approved_at >= ?) * 6
            + (SELECT COUNT(*) FROM event_funds f JOIN events e ON e.id = f.event_id
                 WHERE e.user_id = u.id AND f.status IN ('open','closed') AND f.approved_at >= ?) * 10
            + (SELECT COUNT(*) FROM event_participants p WHERE p.user_id = u.id AND p.joined_at >= ?) * 2 AS total_score
            FROM users u" . ($area ? ' WHERE u.area = ?' : '') . "
            HAVING total_score > 0
            ORDER BY total_score DESC, u.username
            LIMIT " . (int) $limit;
    $params = array_fill(0, 5, $since);
    if ($area) {
        $params[] = $area;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function find_event(int $id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT e.*, u.username, u.full_name, u.avatar, u.is_verified, u.phone AS organiser_phone
                           FROM events e JOIN users u ON u.id = e.user_id WHERE e.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/* ------------------------------------------------------------------
 *  Pagination
 * ------------------------------------------------------------------ */

function paginate(int $total, int $perPage = 12): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage, 'limit' => $perPage];
}

function pagination_links(array $p): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $html = '<nav class="pagination" aria-label="Pages">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $query = http_build_query(array_merge($_GET, ['page' => $i]));
        $html .= $i === $p['page']
            ? '<span class="page current">' . $i . '</span>'
            : '<a class="page" href="?' . e($query) . '">' . $i . '</a>';
    }
    return $html . '</nav>';
}
