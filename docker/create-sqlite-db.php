<?php

$database = $argv[1] ?? '';

if ($database === '') {
    fwrite(STDERR, "Usage: php create-sqlite-db.php <database>\n");
    exit(1);
}

if (file_exists($database)) {
    unlink($database);
}

$pdo = new PDO('sqlite:'.$database);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec(file_get_contents(dirname(__DIR__).'/database/schema/sqlite-demo.sql'));

echo "Created SQLite demo schema.\n";
