<?php
include 'db.php';

echo "<h2>Starting Migration: Address Consolidation</h2>";

$tables = ['users', 'orders'];

foreach ($tables as $table) {
    echo "<h3>Processing table: $table</h3>";
    
    // 1. Add apartment_society column
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'apartment_society'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `apartment_society` VARCHAR(255) AFTER `house_no`";
        if ($conn->query($sql)) {
            echo "Added column 'apartment_society'<br>";
        } else {
            echo "Error adding column: " . $conn->error . "<br>";
        }
    }

    // 2. Migrate data
    // We combine apartment and society. If both exist, we separate with a comma.
    $sql = "UPDATE `$table` SET `apartment_society` = TRIM(BOTH ', ' FROM CONCAT(COALESCE(`apartment`, ''), ', ', COALESCE(`society`, '')))";
    if ($conn->query($sql)) {
        echo "Data migrated to 'apartment_society'<br>";
    } else {
        echo "Error migrating data: " . $conn->error . "<br>";
    }

    // 3. Drop old columns
    $old_cols = ['apartment', 'society'];
    foreach ($old_cols as $col) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($check->num_rows > 0) {
            if ($conn->query("ALTER TABLE `$table` DROP COLUMN `$col`")) {
                echo "Dropped column '$col'<br>";
            } else {
                echo "Error dropping column '$col': " . $conn->error . "<br>";
            }
        }
    }
}

// 4. Drop 'address' from users
echo "<h3>Cleaning up users table</h3>";
$check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'address'");
if ($check->num_rows > 0) {
    if ($conn->query("ALTER TABLE `users` DROP COLUMN `address`")) {
        echo "Dropped 'address' column from users table<br>";
    } else {
        echo "Error dropping 'address' column: " . $conn->error . "<br>";
    }
}

echo "<h3>Migration Complete!</h3>";
echo "<a href='index.php'>Go to Home</a>";
?>
