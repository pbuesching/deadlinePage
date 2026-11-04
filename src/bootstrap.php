<?php
declare(strict_types=1);
date_default_timezone_set('Europe/Vienna');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function db(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = __DIR__ . '/../config/database.php';
    if (!is_file($path)) {
        throw new RuntimeException('Die lokale Datenbankkonfiguration fehlt. Siehe README.md.');
    }
    $config = require $path;
    if (!is_array($config)) {
        throw new RuntimeException('Die Datenbankkonfiguration muss ein Array zurückgeben.');
    }
    foreach (['host', 'database', 'username', 'password'] as $key) {
        if (!isset($config[$key]) || !is_string($config[$key]) || ($key !== 'password' && $config[$key] === '')) {
            throw new RuntimeException('Die Datenbankkonfiguration ist unvollständig.');
        }
    }
    // DSN-Trennzeichen sind in Host und Datenbankname nicht erlaubt.
    if (strpbrk($config['host'] . $config['database'], ";\r\n") !== false) {
        throw new RuntimeException('Host oder Datenbankname ist ungültig.');
    }
    $port = filter_var($config['port'] ?? 3306, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    if ($port === false) {
        throw new RuntimeException('Der Datenbankport ist ungültig.');
    }
    $dsn = 'mysql:host=' . $config['host'] . ';port=' . $port . ';dbname=' . $config['database'] . ';charset=utf8mb4';
    return $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
function parseDate(string $value): ?DateTimeImmutable {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
    return $date && $date->format('Y-m-d\TH:i') === $value ? $date : null;
}
