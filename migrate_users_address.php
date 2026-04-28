<?php
include 'db.php';

echo "<h2>Migrating Users Table...</h2>";

$columns_to_add = [
    'phone' => "VARCHAR(20) NULL",
    'house_no' => "VARCHAR(255) NULL",
    'apartment_society' => "VARCHAR(255) NULL",
    'street' => "VARCHAR(255) NULL",
    'area' => "VARCHAR(255) NULL",
    'landmark' => "VARCHAR(255) NULL",
    'pincode' => "VARCHAR(10) NULL",
    'city' => "VARCHAR(100) NULL",
    'state' => "VARCHAR(100) NULL"
];

foreach ($columns_to_add as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
    if ($check->num_rows == 0) {
        if ($conn->query("ALTER TABLE users ADD COLUMN $col $definition")) {
            echo "Added $col to users table.<br>";
        } else {
            echo "Error adding $col: " . $conn->error . "<br>";
        }
    } else {
        echo "$col already exists in users.<br>";
    }
}

echo "<h3>Migration Complete! Please delete this file.</h3>";
?>
