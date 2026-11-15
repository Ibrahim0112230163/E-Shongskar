<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    $pdo->exec("SET time_zone = '+06:00'");
} catch (PDOException $e) {
    http_response_code(500);
    exit('<div style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:24px;border:1px solid #f1c0c0;border-radius:12px;background:#fff5f5;color:#7a1f1f">'
        . '<h2 style="margin-top:0">Database connection failed</h2>'
        . '<p>Make sure MySQL is running in XAMPP and that the <code>' . DB_NAME . '</code> database has been created '
        . 'from <code>database/e_shongskar.sql</code>.</p>'
        . '<p style="font-size:13px;color:#a33">' . htmlspecialchars($e->getMessage()) . '</p></div>');
}
