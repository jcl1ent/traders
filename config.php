<?php
// Automatically detect the base URL from the server
// This works whether the app is at / or /traders/ or any subdirectory
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);

// Walk up to find the app root (the folder containing config.php)
$appRoot = str_replace('\\', '/', realpath(__DIR__));
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));

// Remove the docRoot from appRoot to get the relative basePath
// Case-insensitive replace for Windows environments
$basePath = str_ireplace($docRoot, '', $appRoot);

// Ensure it starts with / and has no trailing slash
$basePath = '/' . ltrim(str_replace('\\', '/', $basePath), '/');
$basePath = rtrim($basePath, '/');

// Note: include trailing slash to simplify concatenation in templates
// e.g. BASE_URL === 'http://localhost:3000/traders/'
define('BASE_URL', $protocol . '://' . $host . $basePath . '/');
define('BASE_PATH', $basePath);

// helper for building URLs and performing redirects
if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path = ''): void
    {
        header('Location: ' . url($path));
        exit();
    }
}
