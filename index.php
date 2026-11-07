<?php
require_once __DIR__ . '/config/auth.php';

if (is_admin()) {
    header('Location: /autodok/admin/panel.php');
} elseif (is_logged_in()) {
    header('Location: /autodok/dashboard.php');
} else {
    header('Location: /autodok/login.php');
}
exit;