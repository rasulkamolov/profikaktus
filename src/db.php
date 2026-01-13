<?php
// src/db.php
try {
    // Determine the absolute path to the database file
    $dbPath = __DIR__ . '/../db.sqlite';
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    $db->exec('PRAGMA foreign_keys = ON;');
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
