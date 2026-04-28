<?php
session_start();
include 'db.php';

/* ------------------ SECURITY CHECK ------------------ */
if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$method = $_GET['method'] ?? 'Online';
$payment_id = $_GET['payment_id'] ?? 'Manual/UPI';

/* ------------------ DELIVERY DETAILS ------------------ */
$delivery = $_SESSION['delivery_details'] ?? [];
$shipping_name = $delivery['name'] ?? '';
$shipping_phone = $delivery['phone'] ?? '';
$house_no = $delivery['house_no'] ?? '';
$apartment_society = $delivery['apartment_society'] ?? '';
$street = $delivery['street'] ?? '';
$area = $delivery['area'] ?? '';
$landmark = $delivery['landmark'] ?? '';
$pincode = $delivery['pincode'] ?? '';
$city = $delivery['city'] ?? '';
$state = $delivery['state'] ?? '';

// Build a formatted address for emails
$full_shipping_address = "$house_no, $apartment_society, $street, $area, $landmark, $city, $state - $pincode";

/* ------------------ SAVE TO PROFILE IF REQUESTED ------------------ */
if (isset($_SESSION['save_to_profile']) && $_SESSION['save_to_profile'] === true) {
    $update_user = $conn->prepare("
        UPDATE users SET 
        shipping_name = ?, shipping_phone = ?, house_no = ?, apartment_society = ?, 
        street = ?, area = ?, landmark = ?, pincode = ?, city = ?, state = ?
        WHERE id = ?
    ");
    $update_user->bind_param("ssssssssssi", 
        $shipping_name, $shipping_phone, $house_no, $apartment_society, 
        $street, $area, $landmark, $pincode, $city, $state, $user_id
    );
    $update_user->execute();
    unset($_SESSION['save_to_profile']);
}

/* ------------------ COMMISSION ------------------ */
$admin_percent = 0.10;
$pub_percent = 0.90;

/* ------------------ FETCH BUYER EMAIL ------------------ */
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$buyer = $stmt->get_result()->fetch_assoc();
$buyer_email = $buyer['email'];

/* ------------------ COLLECT PUBLISHER DATA ------------------ */
$publisherOrders = [];

/* ------------------ PROCESS CART ------------------ */
foreach ($_SESSION['cart'] as $book_id => $qty) {

    $stmt = $conn->prepare("
        SELECT 
            b.book_name,
            b.price,
            b.quantity,
            u.name  AS publisher_name,
            u.email AS publisher_email
        FROM books b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();

    if ($book) {

        $total_price = $book['price'] * $qty;
        $admin_earning = $total_price * $admin_percent;
        $publisher_earning = $total_price * $pub_percent;

        /* INSERT ORDER WITH DELIVERY DETAILS */
        $insert = $conn->prepare("
            INSERT INTO orders 
            (user_id, book_id, quantity, total_price, admin_earning, publisher_earning, order_date,
             shipping_name, shipping_phone, house_no, apartment_society, street, area, landmark, pincode, city, state)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->bind_param(
            "iiidddssssssssss",
            $user_id, $book_id, $qty, $total_price, $admin_earning, $publisher_earning,
            $shipping_name, $shipping_phone, $house_no, $apartment_society, $street, $area, $landmark, $pincode, $city, $state
        );
        $insert->execute();

        /* UPDATE STOCK */
        $updateQty = $conn->prepare("
            UPDATE books 
            SET quantity = quantity - ? 
            WHERE id = ? AND quantity >= ?
        ");
        $updateQty->bind_param("iii", $qty, $book_id, $qty);
        $updateQty->execute();

        /* STORE PUBLISHER INFO */
        $publisherOrders[$book['publisher_email']]['publisher_name'] = $book['publisher_name'];
        $publisherOrders[$book['publisher_email']]['books'][] = [
            'book_name' => $book['book_name'],
            'quantity' => $qty,
            'price' => $total_price
        ];
    }
}

/* CLEAR CART AND DELIVERY INFO */
unset($_SESSION['cart']);
unset($_SESSION['delivery_details']);

$payment_time = date("d M Y, h:i A");

/* ------------------ PHPMailer ------------------ */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

/* ------------------ BUYER EMAIL ------------------ */
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
    $mail->addAddress($buyer_email, $shipping_name);
    $mail->isHTML(true);
    $mail->Subject = '😊 Your Alpha Book Order Confirmation';

    $mail->Body = "
        <html>
        <body style='font-family:Arial,sans-serif;'>
            <div style='max-width:600px;margin:auto;padding:20px;border:1px solid #ddd;border-radius:12px;'>
                <h2 style='color:#4f46e5;'>Payment Successful!</h2>
                <p>Hi <strong>{$shipping_name}</strong>,</p>
                <p>Your order has been successfully processed and will be delivered to:</p>
                <p style='background:#f9fafb; padding:15px; border-radius:8px;'>
                    {$full_shipping_address}
                </p>
                <p><strong>Payment Method:</strong> {$method}</p>
                <p><strong>Ref ID:</strong> {$payment_id}</p>
                <p>We hope you enjoy your books! 📖</p>
                <p>– Alpha Book Team</p>
            </div>
        </body>
        </html>
    ";
    $mail->send();
} catch (Exception $e) {
    error_log("Buyer Mail Error: " . $mail->ErrorInfo);
}

/* ------------------ PUBLISHER EMAIL ------------------ */
foreach ($publisherOrders as $publisher_email => $data) {

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
        $mail->addAddress($publisher_email, $data['publisher_name']);
        $mail->isHTML(true);
        $mail->Subject = '📦 New Book Order Received';

        $rows = "";
        foreach ($data['books'] as $b) {
            $rows .= "
                <tr>
                    <td>{$b['book_name']}</td>
                    <td>{$b['quantity']}</td>
                    <td>₹{$b['price']}</td>
                </tr>
            ";
        }

        $mail->Body = "
            <html>
            <body style='font-family:Arial,sans-serif;'>
                <h2>New Order Details</h2>
                <p><b>Receiver Name:</b> {$shipping_name}</p>
                <p><b>Receiver Phone:</b> {$shipping_phone}</p>
                <p><b>Shipping Address:</b><br>{$full_shipping_address}</p>

                <table border='1' cellpadding='8' cellspacing='0' style='width:100%;'>
                    <tr style='background:#f3f4f6;'>
                        <th>Book</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                    {$rows}
                </table>

                <p>Please prepare the shipment for this address.</p>
                <p>– Alpha Book Team</p>
            </body>
            </html>
        ";
        $mail->send();
    } catch (Exception $e) {
        error_log("Publisher Mail Error: " . $mail->ErrorInfo);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Successful - Alpha Book</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #f3f4f6, #e0f2fe);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            background: white;
            padding: 3rem 2rem;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            max-width: 420px;
            width: 100%;
        }

        .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            background: #4f46e5;
        }

        .checkmark i {
            color: #fff;
            font-size: 3rem;
        }

        h2 {
            font-weight: 700;
            color: #111827;
        }

        p {
            color: #4b5563;
            font-size: 0.95rem;
        }

        .btn {
            display: inline-block;
            margin-top: 25px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            padding: 12px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
        }

        .info {
            background: #f5f3ff;
            padding: 12px 15px;
            border-radius: 12px;
            margin-top: 15px;
            font-size: 0.85rem;
            color: #4f46e5;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="checkmark"><i class='bx bxs-check-circle'></i></div>
        <h2>Payment Successful!</h2>
        <p>Order processed via <strong><?php echo htmlspecialchars($method); ?></strong></p>
        <p>Ref ID: <strong><?php echo htmlspecialchars($payment_id); ?></strong></p>
        <p class="info">Paid on <?php echo $payment_time; ?></p>
        <a href="Order.php" class="btn">View My Orders</a>
    </div>
</body>

</html>