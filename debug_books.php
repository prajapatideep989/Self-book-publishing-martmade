<?php
include 'db.php';
session_start();

echo "Current User ID: " . ($_SESSION['user_id'] ?? 'Not Set') . "<br>";

$sql = "SELECT id, book_name, user_id, status FROM books";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>User ID</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row["id"] . "</td><td>" . $row["book_name"] . "</td><td>" . $row["user_id"] . "</td><td>" . $row["status"] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "0 results in books table";
}
?>