<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Publisher';

// --- NEW: CLEAR RED DOT ON PAGE LOAD ---
// This updates the 'last_earnings_view' to current time so the dot disappears
$update_view = $conn->prepare("UPDATE users SET last_earnings_view = NOW() WHERE id = ?");
$update_view->bind_param("i", $user_id);
$update_view->execute();
// --- END UPDATE ---

// Data Fetching
$q = $conn->prepare("
SELECT u.name AS buyer_name, u.email AS buyer_email, u.phone AS buyer_phone,
       o.id AS order_id, o.quantity, o.publisher_earning, o.status, o.order_date, b.book_name, b.price,
       o.house_no, o.apartment_society, o.street, o.area, o.landmark, o.pincode, o.city, o.state,
       u.house_no AS u_house, u.apartment_society AS u_apartment_society, u.street AS u_street, u.area AS u_area, u.landmark AS u_landmark, u.pincode AS u_pincode, u.city AS u_city, u.state AS u_state
FROM orders o
JOIN books b ON o.book_id = b.id
JOIN users u ON o.user_id = u.id
WHERE b.user_id = ?
ORDER BY o.order_date DESC");
$q->bind_param("i", $user_id);
$q->execute();
$r = $q->get_result();

$total_earning = 0;
$total_qty = 0;
$orders_array = [];
while ($row = $r->fetch_assoc()) {
    $total_earning += $row['publisher_earning'];
    $total_qty += $row['quantity'];
    $orders_array[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Earnings | Alpha Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            color: #1c1e21;
        }

        .dashboard-container {
            max-width: 950px;
            margin: 30px auto;
            padding: 0 15px;
        }

        /* Top Bar */
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: #fff;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .header-flex h2 {
            margin: 0;
            font-size: 1.1rem;
            color: #4b4b4b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Stats Section */
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
            background: #4f46e5;
        }

        .stat-card small {
            color: #8d949e;
            text-transform: uppercase;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .stat-card div {
            font-size: 1.6rem;
            font-weight: 800;
            color: #4f46e5;
            margin-top: 5px;
        }

        /* Order Cards */
        .order-item {
            background: #fff;
            margin-bottom: 15px;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .order-header {
            padding: 12px 20px;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-header b {
            font-size: 0.9rem;
            color: #1c1e21;
        }

        .earnings-tag {
            background: #e7f3ff;
            color: #1877f2;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .order-body {
            padding: 15px 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .info-col label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.65rem;
            color: #8d949e;
            text-transform: uppercase;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .info-col span {
            font-size: 0.85rem;
            color: #1c1e21;
            font-weight: 500;
        }

        .address-box {
            grid-column: span 3;
            background: #f7f8fa;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #4b4b4b;
            line-height: 1.4;
            border-left: 3px solid #ced4da;
        }

        @media (max-width: 768px) {
            .order-body {
                grid-template-columns: 1fr;
            }

            .address-box {
                grid-column: span 1;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Status Select Styling */
        .status-select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            font-size: 0.8rem;
            background: #fff;
            color: #4b4b4b;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
        }

        .status-select:focus {
            border-color: #4f46e5;
        }

        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-Pending {
            color: #f59e0b;
        }

        .status-Shifting {
            color: #3b82f6;
        }

        .status-Reach {
            color: #8b5cf6;
        }

        .status-Delivered {
            color: #10b981;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <div class="dashboard-container">

        <div class="header-flex">
            <h2><i class='bx bx-stats'></i> Sales Report</h2>
            <span style="font-size: 0.85rem; color: #606770;">Welcome, <b><?= htmlspecialchars($userName) ?></b></span>
        </div>

        <div class="summary-grid">
            <div class="stat-card">
                <small>Total Earnings</small>
                <div>₹<?= number_format($total_earning, 2); ?></div>
            </div>
            <div class="stat-card">
                <small>Units Sold</small>
                <div><?= $total_qty; ?> <span
                        style="font-size: 0.8rem; color: #8d949e; font-weight: normal;">Books</span></div>
            </div>
        </div>

        <?php if (count($orders_array) > 0): ?>
            <?php foreach ($orders_array as $row): ?>
                <div class="order-item">
                    <div class="order-header">
                        <b><i class='bx bx-book-content' style="color: #4f46e5; vertical-align: middle;"></i>
                            <?= htmlspecialchars($row['book_name']) ?></b>
                        <span class="earnings-tag">Profit: ₹<?= number_format($row['publisher_earning'], 2) ?></span>
                    </div>

                    <div class="order-body">
                        <div class="info-col">
                            <label><i class='bx bx-user-circle'></i> Buyer</label>
                            <span><?= htmlspecialchars($row['buyer_name']) ?></span>
                        </div>

                        <div class="info-col">
                            <label><i class='bx bx-phone'></i> Contact</label>
                            <span><?= htmlspecialchars($row['buyer_phone']) ?></span>
                        </div>

                        <div class="info-col">
                            <label><i class='bx bx-shopping-bag'></i> Quantity</label>
                            <span><?= $row['quantity'] ?> pcs</span>
                        </div>

                        <div class="info-col">
                            <label><i class='bx bx-loader-circle'></i> Order Status</label>
                            <select class="status-select status-<?= $row['status'] ?>"
                                onchange="updateStatus(<?= $row['order_id'] ?>, this)">
                                <?php
                                $statuses = ['Pending', 'Shifting', 'Reach', 'Delivered'];
                                foreach ($statuses as $st):
                                    $isDisabled = ($row['status'] !== 'Pending' && $st === 'Pending') ? 'disabled' : '';
                                    ?>
                                    <option value="<?= $st ?>" <?= ($row['status'] == $st) ? 'selected' : '' ?>             <?= $isDisabled ?>>
                                        <?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="address-box">
                            <label><i class='bx bx-map-pin'></i> Delivery Address</label>
                            <?php 
                            // Use order address if available, else fallback to user profile
                            $h = !empty($row['house_no']) ? $row['house_no'] : $row['u_house'];
                            $aps = !empty($row['apartment_society']) ? $row['apartment_society'] : ($row['u_apartment_society'] ?? '');
                            $s = !empty($row['street']) ? $row['street'] : $row['u_street'];
                            $a = !empty($row['area']) ? $row['area'] : $row['u_area'];
                            $l = !empty($row['landmark']) ? $row['landmark'] : $row['u_landmark'];
                            $c = !empty($row['city']) ? $row['city'] : $row['u_city'];
                            $st = !empty($row['state']) ? $row['state'] : $row['u_state'];
                            $p = !empty($row['pincode']) ? $row['pincode'] : $row['u_pincode'];
                            
                            if (!empty($h)): ?>
                                <b><?= htmlspecialchars($h) ?></b>, <?= htmlspecialchars($aps) ?><br>
                                <?= htmlspecialchars($s) ?>, <?= htmlspecialchars($a) ?><br>
                                <?= htmlspecialchars($l) ?><br>
                                <?= htmlspecialchars($c) ?>, <?= htmlspecialchars($st) ?> - <?= htmlspecialchars($p) ?>
                            <?php else: ?>
                                <?= nl2br(htmlspecialchars('No detailed address available')) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="background:white; padding:50px; text-align:center; border-radius:12px; border: 1px solid #ddd;">
                <i class='bx bx-folder-open' style="font-size: 3rem; color: #ccc;"></i>
                <p style="color:#8d949e; margin-top: 10px;">No sales found for your books.</p>
            </div>
        <?php endif; ?>

    </div>

    <script>
        function updateStatus(orderId, selectElement) {
            const newStatus = selectElement.value;

            // Update class for styling
            selectElement.className = 'status-select status-' + newStatus;

            // Send AJAX request
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', newStatus);

            fetch('update_order_status.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update status');
                });
        }
    </script>

</body>

</html>