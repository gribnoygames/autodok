<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ADMIN_LOGIN', 'avto2027');
define('ADMIN_PASSWORD', 'Servis26');

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /autodok/login.php');
        exit;
    }
}

function require_admin() {
    if (!is_admin()) {
        header('Location: /autodok/login.php');
        exit;
    }
}

function flash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

function get_flash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}