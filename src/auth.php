<?php
// src/auth.php
session_start();

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        // Assuming called from a subdirectory (admin/ or cutter/)
        header('Location: ../login.php');
        exit;
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION['role'] !== $role) {
        // Redirect based on actual role or show error
        if ($_SESSION['role'] === 'admin') {
            header('Location: ../admin/index.php');
        } else {
            header('Location: ../cutter/index.php');
        }
        exit;
    }
}

function require_any_role($roles = []) {
    require_login();
    if (!in_array($_SESSION['role'], $roles)) {
         // Redirect based on actual role or show error
         if ($_SESSION['role'] === 'admin') {
            header('Location: ../admin/index.php');
        } else {
            header('Location: ../cutter/index.php');
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
