<?php
require_once __DIR__ . '/config.php';

echo '<h2>MEDITrack database connection successful.</h2>';
echo '<p>Database: ' . htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') . '</p>';
?>
