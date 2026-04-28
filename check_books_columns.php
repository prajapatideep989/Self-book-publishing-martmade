<?php
include 'db.php';
$result = $conn->query("SHOW COLUMNS FROM books");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
}
?>