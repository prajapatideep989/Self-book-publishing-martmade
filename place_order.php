<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'];

$conn->begin_transaction();

try {
    foreach ($cart as $book_id => $qty) {

        // Get book details
        $stmt = $conn->prepare("SELECT price, user_id FROM books WHERE id=? AND quantity >= ?");
        $stmt->bind_param("ii", $book_id, $qty);
        $stmt->execute();
        $res = $stmt->get_result();

        if (!$book = $res->fetch_assoc()) {
            throw new Exception("Stock not available");
        }

        $price = $book['price'];
        $publisher_id = $book['user_id'];

        $total_price = $price * $qty;

        // Example earnings logic
        $admin_earning = $total_price * 0.20;
        $publisher_earning = $total_price * 0.80;

        // Insert order
        $addr = $_SESSION['delivery_details'] ?? [];
        $shipping_name = $addr['name'] ?? '';
        $shipping_phone = $addr['phone'] ?? '';
        $house_no = $addr['house_no'] ?? '';
        $apartment_society = $addr['apartment_society'] ?? '';
        $street = $addr['street'] ?? '';
        $area = $addr['area'] ?? '';
        $landmark = $addr['landmark'] ?? '';
        $pincode = $addr['pincode'] ?? '';
        $city = $addr['city'] ?? '';
        $state = $addr['state'] ?? '';

        $insert = $conn->prepare("
            INSERT INTO orders 
            (user_id, book_id, quantity, total_price, admin_earning, publisher_earning, order_date,
             shipping_name, shipping_phone, house_no, apartment_society, street, area, landmark, pincode, city, state)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->bind_param(
            "iiidddssssssssss",
            $user_id,
            $book_id,
            $qty,
            $total_price,
            $admin_earning,
            $publisher_earning,
            $shipping_name,
            $shipping_phone,
            $house_no,
            $apartment_society,
            $street,
            $area,
            $landmark,
            $pincode,
            $city,
            $state
        );
        $insert->execute();

        // Reduce stock
        $update = $conn->prepare("UPDATE books SET quantity = quantity - ? WHERE id=?");
        $update->bind_param("ii", $qty, $book_id);
        $update->execute();
    }

    $conn->commit();

    // Clear cart
    unset($_SESSION['cart']);

    header("Location: Order.php?success=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    header("Location: cart.php?error=order_failed");
    exit();
}
