<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if (str_contains($path, '..')) {
    http_response_code(400);

    return;
}

$blocked = [
    '/.env',
    '/.git',
    '/.htaccess',
    '/.user.ini',
    '/artisan',
    '/bootstrap',
    '/composer.json',
    '/composer.lock',
    '/config',
    '/database',
    '/package.json',
    '/package-lock.json',
    '/phpunit.xml',
    '/public',
    '/rector.php',
    '/storage',
    '/tests',
    '/vendor',
];

foreach ($blocked as $prefix) {
    if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
        http_response_code(404);

        return;
    }
}

$root = dirname(__DIR__);
$file = $root.$path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require $root.'/index.php';
