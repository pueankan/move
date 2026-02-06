<?php
require_once '../config/database.php';

echo "<h2>Users in Database:</h2>";
echo "<pre>";

try {
    $sql = "SELECT id, username, email, full_name, role, created_at FROM users";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "❌ NO USERS FOUND!\n\n";
        echo "Run the SQL script to create default users.";
    } else {
        foreach ($users as $user) {
            echo "ID: {$user['id']}\n";
            echo "Username: {$user['username']}\n";
            echo "Email: {$user['email']}\n";
            echo "Name: {$user['full_name']}\n";
            echo "Role: {$user['role']}\n";
            echo "Created: {$user['created_at']}\n";
            echo "---\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "</pre>";