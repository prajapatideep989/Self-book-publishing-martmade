<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$isLoggedIn = true;
$userName = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Fetch orders
$stmt = $conn->prepare("
    SELECT 
        orders.id,
        books.book_name,
        books.author_name,
        books.price AS book_price,
        orders.quantity,
        (books.price * orders.quantity) AS total_price,
        orders.status,
        orders.status_update_seen,
        orders.order_date,
        orders.shipping_name,
        orders.shipping_phone,
        orders.house_no,
        orders.apartment_society,
        orders.street,
        orders.area,
        orders.landmark,
        orders.pincode,
        orders.city,
        orders.state
    FROM orders
    JOIN books ON orders.book_id = books.id
    WHERE orders.user_id = ?
    ORDER BY orders.order_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// --- CLEAR RED DOTS ---
// Mark all orders of this buyer as seen
$update_seen = $conn->prepare("UPDATE orders SET status_update_seen = 1 WHERE user_id = ?");
$update_seen->bind_param("i", $user_id);
$update_seen->execute();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Orders | Alpha Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        /* --- ORIGINAL PAGE STYLES --- */
        body {
            font-family: 'Outfit', sans-serif;
            background: #f1f5f9;
            margin: 0;
            color: #1f2937;
        }

        .page-header {
            padding: 3rem 2rem 1.5rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #111827;
            margin: 0;
        }

        .orders-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 2rem 5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .order-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 1.5rem 2rem;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .order-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-icon {
            width: 50px;
            height: 50px;
            background: #4f46e5;
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .order-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: #4f46e5;
        }

        .shipping-details {
            background: #f8fafc;
            border-radius: 10px;
            padding: 12px 15px;
            border-left: 4px solid #4f46e5;
            font-size: 0.85rem;
        }

        .shipping-details h4 {
            margin: 0 0 5px 0;
            color: #4f46e5;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        footer {
            background: #1e293b;
            color: white;
            padding: 2rem;
            text-align: center;
        }

        /* Status Styling */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            gap: 6px;
        }

        .status-Pending {
            background: #fffbeb;
            color: #f59e0b;
        }

        .status-Shifting {
            background: #eff6ff;
            color: #3b82f6;
        }

        .status-Reach {
            background: #f5f3ff;
            color: #8b5cf6;
        }

        .status-Delivered {
            background: #ecfdf5;
            color: #10b981;
        }

        .red-dot {
            height: 10px;
            width: 10px;
            background-color: #ef4444;
            border-radius: 50%;
            display: inline-block;
            margin-left: 5px;
            box-shadow: 0 0 5px rgba(239, 68, 68, 0.5);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <div class="page-header">
        <h1>My Orders</h1>
        <p>Your reading journey, tracked here.</p>
    </div>

    <div class="orders-container">
        <?php if ($result->num_rows > 0):
            while ($row = $result->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-main">
                        <div style="display:flex; align-items:center; gap:1.5rem;">
                            <div class="order-icon"><i class='bx bx-book'></i></div>
                            <div>
                                <h3 style="margin:0;">
                                    <?= htmlspecialchars($row['book_name']) ?>
                                    <?php if ($row['status_update_seen'] == 0 && $row['status'] != 'Pending'): ?>
                                        <span class="red-dot" title="New Update!"></span>
                                    <?php endif; ?>
                                </h3>
                                <p style="margin:4px 0; color:#6b7280; font-size:0.9rem;">Qty: <?= $row['quantity'] ?></p>
                                <div class="status-badge status-<?= $row['status'] ?>">
                                    <i class='bx bx-time-five'></i> <?= $row['status'] ?>
                                </div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div class="order-price">₹<?= number_format($row['total_price'], 2) ?></div>
                            <div style="font-size:0.8rem; color:#9ca3af;"><?= date("d M Y", strtotime($row['order_date'])) ?></div>
                        </div>
                    </div>

                    <?php if ($row['shipping_name']): ?>
                    <div class="shipping-details">
                        <h4><i class='bx bxs-truck'></i> Delivery Address</h4>
                        <p style="margin:0; color:#4b5563;">
                            <strong><?= htmlspecialchars($row['shipping_name']) ?></strong> | <?= htmlspecialchars($row['shipping_phone']) ?><br>
                            <?= htmlspecialchars($row['house_no']) ?>, <?= htmlspecialchars($row['apartment_society']) ?>, 
                            <?= htmlspecialchars($row['street']) ?>, <?= htmlspecialchars($row['area']) ?>, <?= htmlspecialchars($row['city']) ?>, <?= htmlspecialchars($row['state']) ?> - <?= htmlspecialchars($row['pincode']) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; background:#fff; padding:3rem; border-radius:12px;">
                <h2>No Orders Yet</h2>
                <a href="book.php" style="color:#4f46e5; font-weight:600;">Start Browsing →</a>
            </div>
        <?php endif; ?>
    </div>

    <footer>&copy; 2025 Alpha Book. All Rights Reserved.</footer>
</body>

</html>