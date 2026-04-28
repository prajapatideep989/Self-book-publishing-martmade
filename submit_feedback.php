<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $sql = "INSERT INTO feedback (name, email, subject, message, created_at) 
            VALUES ('$name', '$email', '$subject', '$message', NOW())";

    if ($conn->query($sql)) {
        header("Location: feedback.php?success=1");
    } else {
        header("Location: feedback.php?error=1");
    }
    exit();
}
?>
