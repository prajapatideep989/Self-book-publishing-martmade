<?php
session_start();
include 'db.php';

// 1️⃣ Security check
if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    die("Invalid session or empty cart");
}

// 2️⃣ Calculate total amount
$total = 0;
$ids = implode(',', array_keys($_SESSION['cart']));
$res = $conn->query("SELECT id, price FROM books WHERE id IN ($ids)");
while($row = $res->fetch_assoc()){
    $total += $row['price'] * $_SESSION['cart'][$row['id']];
}

if($total <= 0){
    die("Invalid total amount");
}

// 3️⃣ Razorpay test keys
$keyId = "rzp_test_RyD18OGwpj6MwQ";
$keySecret = "7RWs04bVenMzwuUP2SyjqiAv";

// Amount in paise
$amount = round($total * 100);

// 4️⃣ Prepare order data
$data = [
    "amount"=>$amount,
    "currency"=>"INR",
    "receipt"=>"ORD_".time(),
    "payment_capture"=>1
];

// 5️⃣ Initialize cURL
$ch = curl_init("https://api.razorpay.com/v1/orders");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => "$keyId:$keySecret",
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYHOST => 0, // 🔹 Temporary SSL fix for localhost
    CURLOPT_SSL_VERIFYPEER => 0  // 🔹 Temporary SSL fix for localhost
]);

$response = curl_exec($ch);

// Check for cURL errors
if(curl_errno($ch)){
    die("cURL Error: " . curl_error($ch));
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decode response
$order = json_decode($response, true);

// Debug errors
if(!$order){
    die("Invalid JSON response: $response");
}
if(isset($order['error'])){
    die("Razorpay API Error: " . $order['error']['description']);
}
if(!isset($order['id'])){
    die("Razorpay order creation failed. HTTP code: $http_code. Response: " . $response);
}

// 6️⃣ Save order info in session
$_SESSION['razorpay_order_id'] = $order['id'];
$_SESSION['razorpay_amount'] = $amount;

// 7️⃣ Redirect to Razorpay popup page
header("Location: process_razorpay.php");
exit();
