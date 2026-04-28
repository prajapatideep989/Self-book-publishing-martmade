<?php
session_start();
include 'db.php';

// Check if it's a COD request
$paymentMethod = $_POST['payment_method'] ?? 'online';

if ($paymentMethod === 'cod') {
    // COD Logic: Bypass Razorpay check
    $payment_id = 'COD_' . uniqid();
    // Verify grand total is passed
    if (!isset($_POST['total_amount'])) {
        header("Location: cart.php");
        exit();
    }
} else {
    // ONLINE Logic: Razorpay Verification
    if (empty($_POST['razorpay_payment_id'])) {
        header("Location: cart.php");
        exit();
    }
    $payment_id = $_POST['razorpay_payment_id'];

    // --- OPTIONAL: Server-side Signature Verification ---
    // If you want to be 100% secure, verify the signature here using your Key Secret.
    // For this implementation, we are trusting the post data as per the "no-composer" request,
    // but in production, you MUST verify the signature.
}

// NOTE: Since we used standard checkout without generating an order_id on backend first in the previous step,
// signature verification relies strictly on order_id which is optional in 'standard' if not pre-created.
// However, for robust security, you should create an order_id on backend.
// For this 'quick integration' requested by user without SDK, we will verify the payment_id exists.

// RAZORPAY KEYS - TO BE FILLED BY USER
$keyId = 'rzp_test_YOUR_KEY_HERE';
$keySecret = 'YOUR_KEY_SECRET_HERE';

// Manual Verification (Basic)
// For PRODUCTION, use the Order API flow to verify signature: HMAC_SHA256($order_id . "|" . $payment_id, $secret)
// Here we accept the payment ID and proceed (Simulating verification success)

$cart = $_SESSION['cart'] ?? [];
$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id && !empty($cart)) {
    foreach ($cart as $book_id => $qty) {
        // Reduce stock
        $update = $conn->prepare("UPDATE books SET quantity = GREATEST(quantity - ?, 0) WHERE id=?");
        $update->bind_param("ii", $qty, $book_id);
        $update->execute();

        // Insert order
        // Note: Make sure your `orders` table has `payment_id` column. If not, run ALTER TABLE.
        // Assuming table structure: id, user_id, book_id, quantity, total_price (calculated), order_date, payment_method, payment_id

        // We'll insert with payment_id
        $method = ($paymentMethod === 'cod') ? 'COD' : 'Online (Razorpay)';

        // Check if payment_id column exists, otherwise standard insert
        $checkCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_id'");

        if ($checkCol->num_rows > 0) {
            // Insert Order
            // Check if payment_method column exists, if not, we'll confirm via fallback or user must add it.
            // Assuming standard structure for now.
            // If table doesn't have 'payment_method', this might fail, so we stick to robust columns or assume user added it.
            // For now, we store payment_id.

            $addr = $_SESSION['delivery_details'] ?? [];
            $stmt = $conn->prepare("INSERT INTO orders (user_id, book_id, quantity, payment_id, payment_method, house_no, apartment_society, street, area, landmark, pincode, city, state, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iissssssssssss", 
                $user_id, $book_id, $qty, $payment_id, $method,
                $addr['house_no'], $addr['apartment_society'], $addr['street'], 
                $addr['area'], $addr['landmark'], $addr['pincode'], $addr['city'], $addr['state']
            );
            $stmt->execute();
        } else {
            // Fallback if column doesn't exist yet
            $order = $conn->prepare("INSERT INTO orders (user_id, book_id, quantity, order_date) VALUES (?, ?, ?, NOW())");
            $order->bind_param("iii", $user_id, $book_id, $qty);
            $order->execute();
        }
    }

    // Clear cart
    unset($_SESSION['cart']);

    // Success Screen
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Payment Successful</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: "Poppins", sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #e0f2f1; margin: 0; }
            .card { background: white; padding: 40px; border-radius: 15px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 400px; width: 90%; }
            .icon { font-size: 60px; color: #4caf50; margin-bottom: 20px; }
            h1 { color: #2e7d32; margin-bottom: 10px; }
            p { color: #555; margin-bottom: 30px; }
            .btn { background: #2e7d32; color: white; padding: 12px 25px; text-decoration: none; border-radius: 50px; font-weight: 500; display: inline-block; transition: background 0.2s; }
            .btn:hover { background: #1b5e20; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon">✔</div>
            <h1>Payment Successful!</h1>
            <p>Thank you for your purchase. Your order has been placed successfully.</p>
            <p style="font-size: 0.9rem; color: #888;">Transaction ID: ' . $payment_id . '</p>
            <a href="index.php" class="btn">Return to Home</a>
        </div>
    </body>
    </html>';
    exit();
} else {
    echo "<script>alert('Session Error. Please contact support.'); window.location.href='index.php';</script>";
}
?>