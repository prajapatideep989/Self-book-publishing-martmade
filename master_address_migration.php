<?php
include 'db.php';

echo "<h2>Consolidated Address Migration</h2>";

$tables = ['users', 'orders'];
$columns = [
    'shipping_name' => "VARCHAR(255) DEFAULT NULL",
    'shipping_phone' => "VARCHAR(20) DEFAULT NULL",
    'house_no' => "VARCHAR(50) DEFAULT NULL",
    'apartment_society' => "VARCHAR(255) DEFAULT NULL",
    'street' => "VARCHAR(255) DEFAULT NULL",
    'area' => "VARCHAR(100) DEFAULT NULL",
    'landmark' => "VARCHAR(255) DEFAULT NULL",
    'pincode' => "VARCHAR(10) DEFAULT NULL",
    'city' => "VARCHAR(100) DEFAULT NULL",
    'state' => "VARCHAR(100) DEFAULT NULL"
];

foreach ($tables as $table) {
    echo "<h3>Updating table: $table</h3>";
    foreach ($columns as $column => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check->num_rows == 0) {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
            if ($conn->query($sql)) {
                echo "Added column '$column'<br>";
            } else {
                echo "Error adding column '$column': " . $conn->error . "<br>";
            }
        } else {
            echo "Column '$column' exists.<br>";
        }
    }
    
    // Cleanup old columns if they exist
    $old_cols = ['apartment', 'society', 'address'];
    foreach ($old_cols as $col) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($check->num_rows > 0) {
            echo "Removing old column: $col...<br>";
            if ($conn->query("ALTER TABLE `$table` DROP COLUMN `$col`")) {
                echo "Dropped '$col'<br>";
            } else {
                echo "Error dropping '$col': " . $conn->error . "<br>";
            }
        }
    }
}

echo "<h3>Migration Complete! Please try the checkout again.</h3>";
?>
