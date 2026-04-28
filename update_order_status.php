<?php
session_start();
include 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (!$order_id || !$status) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }

    // Verify the order belongs to a book owned by this user and fetch buyer details
    $check = $conn->prepare("
        SELECT o.id, o.status, u.email AS buyer_email, u.name AS buyer_name, b.book_name 
        FROM orders o 
        JOIN books b ON o.book_id = b.id 
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND b.user_id = ?
    ");
    $check->bind_param("ii", $order_id, $user_id);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access to order']);
        exit();
    }

    $current_order = $res->fetch_assoc();
    $current_status = $current_order['status'];
    $buyer_email = $current_order['buyer_email'];
    $buyer_name = $current_order['buyer_name'];
    $book_name = $current_order['book_name'];

    // Status sequence mapping
    $status_sequence = [
        'Pending' => 0,
        'Shifting' => 1,
        'Reach' => 2,
        'Delivered' => 3
    ];

    // Prevent backward movement
    if ($status_sequence[$status] <= $status_sequence[$current_status]) {
        echo json_encode(['success' => false, 'message' => 'Status can only move forward (Current: ' . $current_status . ')']);
        exit();
    }

    // Update status and set seen to 0
    $update = $conn->prepare("UPDATE orders SET status = ?, status_update_seen = 0 WHERE id = ?");
    $update->bind_param("si", $status, $order_id);

    if ($update->execute()) {
        // Send Email Notification to Buyer
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'deepprajapati1012@gmail.com';
            $mail->Password = 'ybfv bmrc rjno bvkv';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('no-reply@alphabook.com', 'Alpha Book');
            $mail->addAddress($buyer_email, $buyer_name);
            $mail->isHTML(true);
            $mail->Subject = "📦 Order Update: Your book '{$book_name}' is now {$status}";

            $mail->Body = "
                <html>
                <body style='font-family:Arial,sans-serif;'>
                    <div style='max-width:600px;margin:auto;padding:20px;border:1px solid #ddd;border-radius:12px;'>
                        <h2 style='color:#4f46e5;'>Order Status Updated!</h2>
                        <p>Hi <strong>{$buyer_name}</strong>,</p>
                        <p>The status of your order for <strong>{$book_name}</strong> has been updated.</p>
                        <p style='font-size:1.2rem;'>New Status: <strong style='color:#4f46e5;'>{$status}</strong></p>
                        <p>You can track your orders in your account section.</p>
                        <p>– Alpha Book Team</p>
                    </div>
                </body>
                </html>
            ";
            $mail->send();
        } catch (Exception $e) {
            // Log error but don't fail the AJAX response as the status was updated successfully
            error_log("Status Update Email Error: " . $mail->ErrorInfo);
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>