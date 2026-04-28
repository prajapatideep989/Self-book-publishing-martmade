<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$grandTotal = $_POST['total_amount'] ?? 0;
// Basic validation: Ensure total is valid
if ($grandTotal <= 0) {
    echo "<script>alert('Invalid amount.'); window.location.href='cart.php';</script>";
    exit();
}

// Convert to paise
$totalInPaise = $grandTotal * 100;

// Order ID generation can happen here if using Razorpay Orders API, 
// but for standard integration we can just pass the amount.
// We'll generate a receipt ID for our tracking
$receiptId = 'ord_' . uniqid();

// User details for pre-fill
$userName = $_SESSION['user_name'] ?? 'Guest';
$userEmail = $_SESSION['user_email'] ?? 'guest@example.com'; 
$userPhone = $_SESSION['user_phone'] ?? '9999999999';

// RAZORPAY KEYS - TO BE FILLED BY USER
$keyId = 'rzp_test_YOUR_KEY_HERE'; 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment - Alpha Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
        }
        .payment-card {
            background: white;
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .amount-display {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 1.5rem 0;
        }
    </style>
</head>
<body>

<div class="payment-card">
    <h2 style="margin-bottom: 0.5rem; color: var(--text-dark);">Complete Payment</h2>
    <p style="color: var(--text-light);">Total Amount Payable</p>
    
    <div class="amount-display">₹<?= number_format($grandTotal, 2) ?></div>

    <!-- Razorpay Button -->
    <form action="payment_success.php" method="POST">
        <script
            src="https://checkout.razorpay.com/v1/checkout.js"
            data-key="<?= $keyId ?>" 
            data-amount="<?= $totalInPaise ?>" 
            data-currency="INR"
            data-name="Alpha Book"
            data-description="Book Purchase"
            data-image="https://cdn-icons-png.flaticon.com/512/2232/2232688.png"
            data-prefill.name="<?= htmlspecialchars($userName) ?>"
            data-prefill.email="<?= htmlspecialchars($userEmail) ?>"
            data-prefill.contact="<?= htmlspecialchars($userPhone) ?>"
            data-theme.color="#3b82f6"
        ></script>
        
        <!-- Pass necessary data to success page via Hidden inputs -->
         <input type="hidden" name="total_amount" value="<?= $grandTotal ?>">
    </form>
    
    <div style="margin-top: 2rem;">
        <a href="cart.php" style="color: var(--text-light); text-decoration: none; font-size: 0.9rem;">Cancel Payment</a>
    </div>
</div>

</body>
</html>
