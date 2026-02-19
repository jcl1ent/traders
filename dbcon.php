<?php
// load .env values if environment vars are not already set
$env = [];
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($k,$v) = array_map('trim', explode('=', $line, 2));
        if ($k !== '') {
            $env[$k] = $v;
            // also putenv so getenv() works later
            putenv("$k=$v");
        }
    }
}

$host = getenv("DB_HOST") ?: ($env['DB_HOST'] ?? '');
$user = getenv("DB_USER") ?: ($env['DB_USER'] ?? '');
$pass = getenv("DB_PASS") ?: ($env['DB_PASS'] ?? '');
$name = getenv("DB_NAME") ?: ($env['DB_NAME'] ?? '');

// establish connection
$con = mysqli_connect($host, $user, $pass, $name);

if (!$con) {
    die('Database connection failed: ' . mysqli_connect_error());
}

?>