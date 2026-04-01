<?php
session_start();

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'koro4ki_est');

// Подключение к базе данных
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8");
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функции валидации
function validateLogin($login) {
    return preg_match('/^[a-zA-Z0-9]{6,}$/', $login);
}

function validatePassword($password) {
    return strlen($password) >= 8;
}

function validateFullName($name) {
    return preg_match('/^[а-яА-ЯёЁ\s]+$/u', $name);
}

function validatePhone($phone) {
    return preg_match('/^8\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Перенаправление
function redirect($url) {
    header("Location: $url");
    exit();
}
?>