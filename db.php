<?php
// ── Database connection ───────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'acsci_sslg');
define('DB_USER', 'acsciadmin');
define('DB_PASS', 'Joshy202627@');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
?>