<?php
const DB_HOST = 'localhost';
const DB_NAME = 'portale_parrucchieri';
const DB_USER = 'lu3g_usr';
const DB_PASS = 'k8E7_li49';
const APP_NAME = 'Liquid Barber';

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
