<?php
include 'db.php';
$result = $conn->query("SHOW COLUMNS FROM orders");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
}
?>