<?php
session_start();
$user_id = $_SESSION['user_id'] ?? null;
include 'db.php';

// Fetch user info with new detailed address columns
$user = ['name' => 'Guest', 'email' => '', 'phone' => ''];
if ($user_id) {
    $stmt = $conn->prepare("SELECT name, email, phone, shipping_name, shipping_phone, house_no, apartment_society, street, area, landmark, pincode, city, state FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

// Priority: session address (new order) > profile shipping address > profile generic address
$delivery = $_SESSION['delivery_details'] ?? null;
if (!$delivery && $user['shipping_name']) {
    $delivery = [
        'name' => $user['shipping_name'],
        'phone' => $user['shipping_phone'],
        'house_no' => $user['house_no'],
        'apartment_society' => $user['apartment_society'],
        'street' => $user['street'],
        'area' => $user['area'],
        'landmark' => $user['landmark'],
        'pincode' => $user['pincode'],
        'city' => $user['city'],
        'state' => $user['state']
    ];
}

$books = isset($_POST['books']) ? json_decode($_POST['books'], true) : [];
$grandTotal = $_POST['grand_total'] ?? 0;
$date = date("d-m-Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - Alpha Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: var(--bg-color);
            padding: 2rem;
            min-height: 100vh;
        }
        .invoice-card {
            background: #fff;
            padding: 3rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }
        .invoice-header h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            gap: 20px;
        }
        .customer-info p, .invoice-meta p {
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            line-height: 1.4;
        }
        .customer-info strong {
            color: var(--text-light);
            display: inline-block;
            width: 80px;
            font-size: 0.85rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background: var(--bg-color);
            color: var(--text-dark);
            font-weight: 600;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            text-align: right;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid var(--border-color);
        }
        .print-btn {
            display: block;
            width: 100%;
            background: var(--primary);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            margin-top: 2rem;
            transition: background 0.2s;
        }
        .print-btn:hover {
            background: var(--primary-dark);
        }
        @media print {
            body { background: white; padding: 0; }
            .invoice-card { box-shadow: none; border: none; padding: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

<div class="invoice-card">
    <div class="invoice-header">
        <h2>Alpha Book Invoice</h2>
        <p>Order Date: <?= $date ?></p>
    </div>

    <div class="invoice-details">
        <div class="customer-info" style="flex:1;">
            <h4 style="margin-bottom: 1rem; font-size: 1.2rem; color:var(--primary);">Deliver To:</h4>
            <?php if ($delivery): ?>
                <p><strong>Name:</strong> <?= htmlspecialchars($delivery['name']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($delivery['phone']) ?></p>
                <p><strong>Address:</strong><br>
                    <?= htmlspecialchars($delivery['house_no']) ?>, <?= htmlspecialchars($delivery['apartment_society']) ?><br>
                    <?= htmlspecialchars($delivery['street']) ?>, <?= htmlspecialchars($delivery['area']) ?><br>
                    <?= htmlspecialchars($delivery['landmark']) ?><br>
                    <?= htmlspecialchars($delivery['city']) ?>, <?= htmlspecialchars($delivery['state']) ?> - <?= htmlspecialchars($delivery['pincode']) ?>
                </p>
            <?php else: ?>
                <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($user['house_no']) ?>, <?= htmlspecialchars($user['apartment_society']) ?>, <?= htmlspecialchars($user['street']) ?>, <?= htmlspecialchars($user['area']) ?>, <?= htmlspecialchars($user['landmark']) ?>, <?= htmlspecialchars($user['city']) ?>, <?= htmlspecialchars($user['state']) ?> - <?= htmlspecialchars($user['pincode']) ?></p>
            <?php endif; ?>
        </div>
        <div class="invoice-meta" style="text-align: right; min-width: 150px;">
             <h4 style="margin-bottom: 1rem; font-size: 1.2rem; color:var(--primary);">Order Details:</h4>
             <p><strong>Status:</strong> Success</p>
             <p><strong>Ref ID:</strong> <?= hexdec(substr(md5(time()), 0, 8)) ?></p>
        </div>
    </div>

    <?php if (!empty($books)): ?>
    <table>
        <thead>
            <tr>
                <th>Book</th>
                <th>Author</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
                <tr>
                    <td><?= htmlspecialchars($book['book_name']) ?></td>
                    <td><?= htmlspecialchars($book['author_name']) ?></td>
                    <td>₹<?= $book['price'] ?></td>
                    <td><?= $book['quantity_ordered'] ?></td>
                    <td>₹<?= $book['total_price'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="total-section">
        Grand Total: ₹<?= $grandTotal ?>
    </div>

    <button onclick="window.print()" class="print-btn">Print / Download Invoice</button>
</div>

</body>
</html>
