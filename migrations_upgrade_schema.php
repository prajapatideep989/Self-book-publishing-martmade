<?php
include 'db.php';

$tables = ['users', 'orders'];
$columns = [
    'shipping_name' => "VARCHAR(255) DEFAULT NULL",
    'shipping_phone' => "VARCHAR(20) DEFAULT NULL",
    'house_no' => "VARCHAR(50) DEFAULT NULL",
    'apartment' => "VARCHAR(100) DEFAULT NULL",
    'society' => "VARCHAR(100) DEFAULT NULL",
    'street' => "VARCHAR(255) DEFAULT NULL",
    'area' => "VARCHAR(100) DEFAULT NULL",
    'landmark' => "VARCHAR(255) DEFAULT NULL",
    'pincode' => "VARCHAR(10) DEFAULT NULL",
    'city' => "VARCHAR(100) DEFAULT NULL",
    'state' => "VARCHAR(100) DEFAULT NULL"
];

foreach ($tables as $table) {
    echo "Updating table: $table...<br>";
    foreach ($columns as $column => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($check->num_rows == 0) {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
            if ($conn->query($sql)) {
                echo "&nbsp;&nbsp;Added column '$column'<br>";
            } else {
                echo "&nbsp;&nbsp;Error adding column '$column': " . $conn->error . "<br>";
            }
        } else {
            echo "&nbsp;&nbsp;Column '$column' already exists.<br>";
        }
    }
}

echo "Migration completed!<br>";
echo "<a href='checkout.php'>Go back to Checkout</a>";
?>
