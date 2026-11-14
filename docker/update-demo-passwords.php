<?php

$database = $argv[1] ?? '';
$password = $argv[2] ?? '';

if ($database === '' || $password === '') {
    fwrite(STDERR, "Usage: php update-demo-passwords.php <database> <password>\n");
    exit(1);
}

$pdo = new PDO('sqlite:'.$database);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$statement = $pdo->prepare('UPDATE users SET password = ?');
$statement->execute([$hash]);

echo 'Updated '.$statement->rowCount()." demo user password(s).\n";
