<?php

$config = require __DIR__ . '/config.php';
$db = $config['mysql'];

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $db['host'],
    $db['port'],
    $db['database']
);

try {
    $pdo = new PDO(
        $dsn,
        $db['username'],
        $db['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    fwrite(STDERR, 'Database connection failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

return $pdo;
