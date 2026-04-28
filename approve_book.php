<?php
session_start();
include 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = $_POST['book_id'];

    // Update book status and track update time for notifications
    $stmt = $conn->prepare("UPDATE books SET status = 'approved', status_updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();

    // Get user_id and book_name of book owner
    $userStmt = $conn->prepare("SELECT user_id, book_name FROM books WHERE id = ?");
    $userStmt->bind_param("i", $book_id);
    $userStmt->execute();
    $result = $userStmt->get_result();
    $row = $result->fetch_assoc();
    $user_id = $row['user_id'];
    $book_name = $row['book_name'];

    // Get user email
    $emailStmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $emailStmt->bind_param("i", $user_id);
    $emailStmt->execute();
    $emailResult = $emailStmt->get_result();
    $emailRow = $emailResult->fetch_assoc();

    if ($emailRow) {
        $user_email = $emailRow['email'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'deepprajapati1012@gmail.com';
            $mail->Password = 'ybfv bmrc rjno bvkv';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('YOUR_GMAIL@gmail.com', 'Alpha Book');
            $mail->addAddress($user_email);

            $mail->Subject = 'Alpha Book - Book Approved';
            $mail->Body = "Your Book has been approve by the Admin with book name " . $book_name;

            $mail->send();
        } catch (Exception $e) {
            // Ignore email errors to prevent blocking approval process
        }
    }

    // Redirect back to admin home with the specified tab, or default
    $return_to = $_POST['return_to'] ?? 'Admin_home.php';
    header("Location: $return_to");
    exit();
}
?>