<?php
$localConfigPath = __DIR__ . '/config.local.php';
$localConfig = is_readable($localConfigPath) ? require $localConfigPath : [];

function config_value(array $localConfig, string $key, string $default = ''): string
{
    $envValue = getenv($key);
    if ($envValue !== false) {
        return $envValue;
    }

    return $localConfig[$key] ?? $default;
}

define('DB_HOST', config_value($localConfig, 'DB_HOST', '127.0.0.1'));
define('DB_NAME', config_value($localConfig, 'DB_NAME', 'portale_parrucchieri'));
define('DB_USER', config_value($localConfig, 'DB_USER', 'root'));
define('DB_PASS', config_value($localConfig, 'DB_PASS'));
define('APP_NAME', config_value($localConfig, 'APP_NAME', 'Liquid Barber'));

session_start();

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $pdo;
}
