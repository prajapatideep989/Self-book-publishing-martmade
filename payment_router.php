<?php
session_start();

if (!isset($_POST['payment_method'])) {
    header("Location: checkout.php");
    exit();
}

if ($_POST['payment_method'] === 'online') {
    header("Location: create_razorpay_order.php");
    exit();
}

if ($_POST['payment_method'] === 'cod') {
    // Directly go to success page for COD
    header("Location: success.php?method=COD&payment_id=Manual/UPI");
    exit();
}
