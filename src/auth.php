<?php
// src/auth.php
session_start();

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION['role'] !== $role) {
        // Redirect based on actual role or show error
        if ($_SESSION['role'] === 'admin') {
            header('Location: /admin/index.php');
        } else {
            header('Location: /cutter/index.php');
        }
        exit;
    }
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_current_role() {
    return $_SESSION['role'] ?? null;
}
?>
