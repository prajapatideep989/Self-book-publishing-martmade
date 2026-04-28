<?php
include 'db.php';

echo "<h2>Starting Notification Migration...</h2>";

// 1. Add status_updated_at to books
$check_books = $conn->query("SHOW COLUMNS FROM books LIKE 'status_updated_at'");
if ($check_books->num_rows == 0) {
    $conn->query("ALTER TABLE books ADD COLUMN status_updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    echo "Added status_updated_at to books table.<br>";
} else {
    echo "status_updated_at already exists in books.<br>";
}

// 2. Add seller_notified to orders
$check_orders = $conn->query("SHOW COLUMNS FROM orders LIKE 'seller_notified'");
if ($check_orders->num_rows == 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN seller_notified TINYINT(1) DEFAULT 0");
    echo "Added seller_notified to orders table.<br>";
} else {
    echo "seller_notified already exists in orders.<br>";
}

// Initialize status_updated_at with updated_at for existing books
$conn->query("UPDATE books SET status_updated_at = updated_at WHERE status_updated_at IS NULL");

echo "<h3>Migration Complete! Please delete this file.</h3>";
?>
