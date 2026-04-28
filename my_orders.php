<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch user orders
$order_stmt = $conn->prepare("
SELECT o.id as order_id, b.book_name, b.price, o.quantity, (b.price*o.quantity) as total, o.order_date
FROM orders o 
JOIN books b ON o.book_id=b.id
WHERE o.user_id=?
ORDER BY o.order_date DESC
");
$order_stmt->bind_param("i", $user_id);
$order_stmt->execute();
$orders = $order_stmt->get_result();
$order_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - Alpha Book</title>

<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
<?php include 'account_style.css'; ?>
table{width:100%; border-collapse:collapse; margin-top:20px;}
table th, table td{border:1px solid #ddd; padding:10px; text-align:center;}
table th{background:var(--primary); color:#fff;}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="account-card">
    <img src="pro.jpeg">
    <h1><?= htmlspecialchars($user['name']) ?></h1>
    <p><?= htmlspecialchars($user['email']) ?></p>
</div>

<div class="section" style="display:block; max-width:900px; margin:30px auto;">
<h2>My Orders</h2>
<?php if($orders->num_rows > 0): ?>
<table>
<tr><th>Order ID</th><th>Book</th><th>Price</th><th>Quantity</th><th>Total</th><th>Date</th></tr>
<?php while($row=$orders->fetch_assoc()): ?>
<tr>
    <td><?= $row['order_id'] ?></td>
    <td><?= htmlspecialchars($row['book_name']) ?></td>
    <td>₹<?= number_format($row['price'],2) ?></td>
    <td><?= $row['quantity'] ?></td>
    <td>₹<?= number_format($row['total'],2) ?></td>
    <td><?= $row['order_date'] ?></td>
</tr>
<?php endwhile; ?>
</table>
<?php else: ?>
<p style="text-align:center;">No orders found.</p>
<?php endif; ?>
</div>

</body>
</html>
