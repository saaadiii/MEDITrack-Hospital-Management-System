<?php
// MEDITrack operates in Bangladesh local time.
date_default_timezone_set('Asia/Dhaka');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'meditrack');
define('SESSION_TIMEOUT', 1800);
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die('Database connection failed. Please import database.sql and check config/config.php.');
}
mysqli_set_charset($conn, 'utf8mb4');
// Keep MySQL CURDATE()/CURTIME()/NOW() aligned with Bangladesh time.
@mysqli_query($conn, "SET time_zone = '+06:00'");
// Any appointment that stayed Booked through the end of its scheduled date is a no-show.
// This runs safely on each request after the no-show database update has been applied.
@mysqli_query(
    $conn,
    "UPDATE appointments SET status='Did not appear' WHERE status='Booked' AND appointment_date<CURDATE()"
);
if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
