<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = $_POST['book_id'];

    // Update book status
    $stmt = $conn->prepare("UPDATE books SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();

    // Redirect back to admin home with the specified tab, or default
    $return_to = $_POST['return_to'] ?? 'Admin_home.php';
    header("Location: $return_to");
    exit();
}
?>