<?php
require_once __DIR__ . '/../config/auth.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>АвтоДок</title>
    <link rel="stylesheet" href="/autodok/assets/frameworks/bootstrap-5.3.8-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/autodok/assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="/autodok/">АвтоДок</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (is_admin()): ?>
            <li class="nav-item"><a class="nav-link" href="/autodok/admin/panel.php">Панель админа</a></li>
            <li class="nav-item"><a class="nav-link" href="/autodok/logout.php">Выход</a></li>
        <?php elseif (is_logged_in()): ?>
            <li class="nav-item"><a class="nav-link" href="/autodok/dashboard.php">Мои записи</a></li>
            <li class="nav-item"><a class="nav-link" href="/autodok/create_request.php">Новая запись</a></li>
            <li class="nav-item"><a class="nav-link" href="/autodok/logout.php">Выход</a></li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="/autodok/login.php">Вход</a></li>
            <li class="nav-item"><a class="nav-link" href="/autodok/register.php">Регистрация</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container">