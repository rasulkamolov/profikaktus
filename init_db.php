<?php
try {
    $db = new SQLite3('db.sqlite');

    // Enable foreign keys
    $db->exec('PRAGMA foreign_keys = ON;');

    // Read and execute schema
    $schema = file_get_contents('schema.sql');
    if ($db->exec($schema)) {
        echo "Database schema initialized successfully.\n";
    } else {
        echo "Error initializing schema: " . $db->lastErrorMsg() . "\n";
        exit(1);
    }

    // Seed Admin User
    // Password: admin123(hashed)
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $check = $db->querySingle("SELECT count(*) FROM users WHERE username = 'admin'");

    if ($check == 0) {
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:u, :p, :r)");
        $stmt->bindValue(':u', 'admin', SQLITE3_TEXT);
        $stmt->bindValue(':p', $password, SQLITE3_TEXT);
        $stmt->bindValue(':r', 'admin', SQLITE3_TEXT);

        if ($stmt->execute()) {
            echo "Admin user created (user: admin, pass: admin123).\n";
        } else {
            echo "Error creating admin user.\n";
        }
    } else {
        echo "Admin user already exists.\n";
    }

    // Seed Cutter User
    // Password: cutter123(hashed)
    $c_password = password_hash('cutter123', PASSWORD_DEFAULT);
    $check_c = $db->querySingle("SELECT count(*) FROM users WHERE username = 'cutter'");

    if ($check_c == 0) {
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:u, :p, :r)");
        $stmt->bindValue(':u', 'cutter', SQLITE3_TEXT);
        $stmt->bindValue(':p', $c_password, SQLITE3_TEXT);
        $stmt->bindValue(':r', 'cutter', SQLITE3_TEXT);

        if ($stmt->execute()) {
            echo "Cutter user created (user: cutter, pass: cutter123).\n";
        } else {
            echo "Error creating cutter user.\n";
        }
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    exit(1);
}
?>
