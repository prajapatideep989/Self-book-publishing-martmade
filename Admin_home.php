<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['user_name'];

/* STATS */
function getCount($conn, $sql)
{
    $res = $conn->query($sql);
    return ($res && $res->num_rows > 0) ? $res->fetch_row()[0] : 0;
}

$total_books = getCount($conn, "SELECT COUNT(*) FROM books");
$total_users = getCount($conn, "SELECT COUNT(*) FROM users");
$total_orders = getCount($conn, "SELECT COUNT(*) FROM orders");
$pending_books_count = getCount($conn, "SELECT COUNT(*) FROM books WHERE status='pending'");

/* ADMIN EARNING (10% COMMISSION) */
$admin_commission_rate = 0.10;
$admin_earning = 0;

$admin_res = $conn->query("SELECT total_price FROM orders");
while ($row = $admin_res->fetch_assoc()) {
    $admin_earning += ($row['total_price'] * $admin_commission_rate);
}


/* DATA */
$all_users = $conn->query("SELECT * FROM users");
$all_books = $conn->query("SELECT books.*, users.name AS publisher FROM books JOIN users ON books.user_id = users.id");
$pending_books = $conn->query("SELECT books.*, users.name AS publisher FROM books JOIN users ON books.user_id = users.id WHERE books.status='pending'");

// Prepare Books per User for the Users Section
$user_books = [];
$ub_res = $conn->query("SELECT * FROM books"); // Fetch again for clean mapping
while ($b_row = $ub_res->fetch_assoc()) {
    $user_books[$b_row['user_id']][] = $b_row;
}
$all_orders = $conn->query("
    SELECT orders.*, users.name AS buyer_name, books.book_name, books.cover_image, books.author_name 
    FROM orders 
    JOIN users ON orders.user_id = users.id 
    JOIN books ON orders.book_id = books.id
    ORDER BY orders.order_date DESC
");

/* FEEDBACK & REVIEWS */
$all_feedback = $conn->query("SELECT * FROM feedback ORDER BY created_at DESC");
$all_book_reviews = $conn->query("
    SELECT r.*, b.book_name, b.author_name, b.cover_image, u.name AS user_name 
    FROM books_reviews r
    JOIN books b ON r.book_id = b.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Alpha Book | Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --bg: #f4f6fb;
            --dark: #0f172a;
            --light: #64748b;
            --card: #ffffff;
            --border: #e5e7eb;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
            --sidebar: 280px;
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            display: flex;
        }

        .sidebar {
            width: var(--sidebar);
            background: linear-gradient(180deg, #020617, #0f172a);
            color: white;
            height: 100vh;
            padding: 2rem;
            position: fixed;
        }

        .sidebar h2 {
            font-size: 1.7rem;
            margin-bottom: 3rem;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            margin-bottom: 8px;
            color: #cbd5f5;
            text-decoration: none;
            border-radius: 14px;
            transition: .3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: linear-gradient(90deg, #6366f1, #4f46e5);
            color: white;
        }

        .main {
            margin-left: var(--sidebar);
            padding: 2.5rem;
            width: calc(100% - var(--sidebar));
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 2rem;
        }

        .profile {
            background: white;
            padding: 10px 16px;
            border-radius: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.8rem;
            margin-bottom: 3rem;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }

        .icon {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
        }

        .blue {
            background: #4f46e5
        }

        .green {
            background: #22c55e
        }

        .purple {
            background: #a855f7
        }

        .orange {
            background: #f59e0b
        }

        .section {
            background: white;
            border-radius: 18px;
            padding: 2rem;
            margin-bottom: 3rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            font-size: .75rem;
            text-transform: uppercase;
            color: var(--light);
            padding: 1rem;
        }

        td {
            padding: 1rem;
            border-top: 1px solid var(--border);
        }

        .badge {
            padding: 6px 14px;
            border-radius: 40px;
            font-size: .7rem;
            font-weight: 600;
        }

        .success {
            background: #dcfce7;
            color: #166534
        }

        .pending {
            background: #ffedd5;
            color: #9a3412
        }

        .danger {
            background: #fee2e2;
            color: #991b1b
        }

        .content {
            display: none
        }

        .content.active {
            display: block
        }

        /* REQUESTS */
        #requests.content .section {
            background: transparent;
            padding: 0;
        }

        .requests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2.5rem;
        }

        .book-card-unique {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .book-card-unique:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .admin-carousel {
            position: relative;
            height: 350px;
            overflow: hidden;
            background: #f1f5f9;
        }

        .carousel-track {
            display: flex;
            height: 100%;
            transition: transform 0.5s ease;
        }

        .carousel-track img {
            min-width: 100%;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(4px);
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10;
            transition: 0.3s;
        }

        .nav-btn:hover {
            background: white;
            transform: translateY(-50%) scale(1.1);
        }

        .prev-btn {
            left: 10px;
        }

        .next-btn {
            right: 10px;
        }

        .book-details-unique {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .book-category {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .book-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.4rem;
            line-height: 1.3;
        }

        .book-author {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .book-publisher {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-bottom: 1.2rem;
        }

        .book-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 1rem;
        }

        .book-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
        }

        .btn-approve,
        .btn-reject {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            color: white;
            font-size: 1.2rem;
            transition: 0.3s;
        }

        .btn-approve {
            background: var(--success);
        }

        .btn-approve:hover {
            background: #16a34a;
            transform: scale(1.05);
        }

        .btn-reject {
            background: var(--danger);
        }

        .btn-reject:hover {
            background: #dc2626;
            transform: scale(1.05);
        }

        /* FEEDBACK & REVIEWS SPECIFIC STYLES */
        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .feedback-card {
            background: white;
            border-radius: 18px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: 0.3s;
        }

        .feedback-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border-color: var(--primary);
        }

        .fb-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .fb-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .fb-avatar {
            width: 40px;
            height: 40px;
            background: #eef2ff;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .fb-info h4 {
            margin: 0;
            font-size: 0.95rem;
            color: var(--dark);
        }

        .fb-info p {
            margin: 0;
            font-size: 0.75rem;
            color: var(--light);
        }

        .fb-subject {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: block;
        }

        .fb-message {
            font-size: 0.85rem;
            color: #475569;
            line-height: 1.5;
            background: #f8fafc;
            padding: 12px;
            border-radius: 10px;
        }

        .review-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .review-item {
            background: white;
            border-radius: 18px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            display: flex;
            gap: 1.5rem;
        }

        .rev-book-img {
            width: 70px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .rev-content {
            flex: 1;
        }

        .rev-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .rev-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--dark);
            margin-bottom: 2px;
        }

        .rev-user {
            font-size: 0.85rem;
            color: var(--light);
        }

        .stars {
            color: #fbbf24;
            font-size: 1.1rem;
        }

        .rev-text {
            font-size: 0.9rem;
            color: #475569;
            margin-top: 0.8rem;
            font-style: italic;
            border-left: 3px solid #e2e8f0;
            padding-left: 12px;
        }
    </style>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2>📚 Alpha Admin</h2>
        <a href="#" class="active" onclick="openTab('dashboard',this)"><i class='bx bxs-dashboard'></i>Dashboard</a>
        <a href="#" onclick="openTab('books',this)"><i class='bx bxs-book'></i>Books</a>
        <a href="#" onclick="openTab('orders',this)"><i class='bx bxs-shopping-bag'></i>Orders</a>
        <a href="#" onclick="openTab('requests',this)">
            <i class='bx bxs-bell'></i>Requests (<?= $pending_books_count ?>)
        </a>
        <a href="#" onclick="openTab('users',this)"><i class='bx bxs-user'></i>Users</a>
        <a href="#" onclick="openTab('feedback',this)"><i class='bx bxs-message-dots'></i>Feedback</a>
        <a href="#" onclick="openTab('reviews',this)"><i class='bx bxs-star-half'></i>Book Reviews</a>
        <a href="logout.php" style="color:#fca5a5"><i class='bx bx-log-out'></i>Logout</a>
    </div>

    <!-- MAIN -->
    <div class="main">

        <div class="header">
            <h1>Dashboard</h1>
            <div class="profile">
                <img src="pro.jpeg" width="40" style="border-radius:50%">
                <strong><?= htmlspecialchars($userName) ?></strong>
            </div>
        </div>

        <!-- DASHBOARD -->
        <div id="dashboard" class="content active">
            <div class="stats">
                <div class="card">
                    <div class="icon blue"><i class='bx bxs-book'></i></div>
                    <div>
                        <h2><?= $total_books ?></h2>
                        <p>Total Books</p>
                    </div>

                </div>
                <div class="card">
                    <div class="icon purple"><i class='bx bxs-user'></i></div>
                    <div>
                        <h2><?= $total_users ?></h2>
                        <p>Users</p>
                    </div>
                </div>
                <div class="card">
                    <div class="icon green"><i class='bx bxs-cart'></i></div>
                    <div>
                        <h2><?= $total_orders ?></h2>
                        <p>Orders</p>
                    </div>
                </div>
                <div class="card">
                    <div class="icon orange"><i class='bx bxs-time'></i></div>
                    <div>
                        <h2><?= $pending_books_count ?></h2>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="card">
                    <div class="icon green">
                        <i class='bx bx-rupee'></i>
                    </div>
                    <div>
                        <h2>₹<?= number_format($admin_earning, 2) ?></h2>
                        <p>Admin Earnings</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOOKS -->
        <div id="books" class="content">
            <div class="section">
                <h3>All Books</h3>
                <style>
                    .admin-book-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                        gap: 25px;
                    }

                    .admin-book-card {
                        background: white;
                        border-radius: 16px;
                        overflow: hidden;
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                        transition: transform 0.3s, box-shadow 0.3s;
                        border: 1px solid rgba(0, 0, 0, 0.05);
                        display: flex;
                        flex-direction: column;
                    }

                    .admin-book-card:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                    }

                    .abc-img-wrapper {
                        height: 350px;
                        overflow: hidden;
                        position: relative;
                        background: #f1f5f9;
                    }

                    .abc-img-wrapper img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                        transition: transform 0.5s;
                    }

                    .admin-book-card:hover .abc-img-wrapper img {
                        transform: scale(1.1);
                    }

                    .abc-status {
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        padding: 5px 12px;
                        border-radius: 20px;
                        font-size: 0.75rem;
                        font-weight: 700;
                        text-transform: uppercase;
                        background: rgba(255, 255, 255, 0.9);
                        backdrop-filter: blur(4px);
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    }

                    .abc-status.approved {
                        color: #166534;
                    }

                    .abc-status.pending {
                        color: #9a3412;
                    }

                    .abc-body {
                        padding: 18px;
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                    }

                    .abc-cat {
                        font-size: 0.75rem;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        color: #6366f1;
                        font-weight: 600;
                        margin-bottom: 6px;
                    }

                    .abc-title {
                        font-size: 1.1rem;
                        font-weight: 700;
                        color: #1e293b;
                        margin-bottom: 4px;
                        line-height: 1.3;
                    }

                    .abc-author {
                        font-size: 0.9rem;
                        color: #64748b;
                        margin-bottom: 15px;
                    }

                    .abc-footer {
                        margin-top: auto;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        border-top: 1px solid #f1f5f9;
                        padding-top: 12px;
                    }

                    .abc-price {
                        font-size: 1.2rem;
                        font-weight: 700;
                        color: #0f172a;
                    }

                    .abc-qty {
                        font-size: 0.8rem;
                        color: #64748b;
                        background: #f8fafc;
                        padding: 4px 10px;
                        border-radius: 6px;
                    }
                </style>

                <div class="admin-book-grid">
                    <?php while ($b = $all_books->fetch_assoc()): ?>
                        <div class="admin-book-card">
                            <div class="abc-img-wrapper">
                                <span class="abc-status <?= $b['status'] == 'approved' ? 'approved' : 'pending' ?>">
                                    <?= ucfirst($b['status']) ?>
                                </span>
                                <?php if (!empty($b['cover_image'])): ?>
                                    <img src="<?= htmlspecialchars($b['cover_image']) ?>" alt="Book Cover">
                                <?php else: ?>
                                    <div
                                        style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#ccc;">
                                        <i class='bx bxs-image' style="font-size:3rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="abc-body">
                                <div class="abc-cat"><?= htmlspecialchars($b['category'] ?? 'General') ?></div>
                                <h4 class="abc-title"><?= htmlspecialchars($b['book_name']) ?></h4>
                                <div class="abc-author">by <?= htmlspecialchars($b['author_name']) ?></div>

                                <div class="abc-footer">
                                    <div class="abc-price">₹<?= $b['price'] ?></div>
                                    <div class="abc-qty">Qty: <?= $b['quantity'] ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- ORDERS -->
        <div id="orders" class="content">
            <div class="section">
                <th>Admin (10%)</th>
                <h3>Recent Orders</h3>

                <style>
                    .order-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
                        gap: 25px;
                    }

                    .order-card {
                        background: white;
                        border-radius: 16px;
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                        border: 1px solid rgba(0, 0, 0, 0.05);
                        overflow: hidden;
                        display: flex;
                        align-items: center;
                        transition: transform 0.2s;
                    }

                    .order-card:hover {
                        transform: translateY(-3px);
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                    }

                    .oc-img {
                        width: 110px;
                        height: 160px;
                        object-fit: cover;
                        flex-shrink: 0;
                    }

                    .oc-details {
                        padding: 20px;
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                    }

                    .oc-date {
                        font-size: 0.75rem;
                        color: #94a3b8;
                        margin-bottom: 5px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        display: flex;
                        justify-content: space-between;
                    }

                    .oc-title {
                        font-family: 'Outfit', sans-serif;
                        font-weight: 700;
                        font-size: 1.15rem;
                        color: #1e293b;
                        margin-bottom: 4px;
                    }

                    .oc-author {
                        font-size: 0.9rem;
                        color: #64748b;
                        margin-bottom: 15px;
                    }

                    .oc-meta-row {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        background: #f8fafc;
                        padding: 10px 14px;
                        border-radius: 10px;
                    }

                    .oc-buyer {
                        font-size: 0.85rem;
                        font-weight: 600;
                        color: #475569;
                        display: flex;
                        flex-direction: column;
                    }

                    .oc-buyer span {
                        font-size: 0.7rem;
                        color: #94a3b8;
                        font-weight: 400;
                    }

                    .oc-price-tag {
                        text-align: right;
                    }

                    .oc-total {
                        font-weight: 800;
                        color: #059669;
                        font-size: 1.1rem;
                    }

                    .oc-qty {
                        font-size: 0.75rem;
                        color: #64748b;
                    }

                    .oc-admin-earning {
                        font-size: 0.85rem;
                        margin-top: 4px;
                        color: #16a34a;
                        font-weight: 600;
                    }
                </style>

                <div class="order-grid">
                    <?php if ($all_orders->num_rows > 0): ?>
                        <?php while ($o = $all_orders->fetch_assoc()): ?>
                            <div class="order-card">
                                <img src="<?= htmlspecialchars($o['cover_image']) ?: 'default_cover.png' ?>" class="oc-img"
                                    alt="Book">
                                <div class="oc-details">
                                    <div class="oc-date">
                                        <span><?= date('M d, Y', strtotime($o['order_date'])) ?></span>
                                        <span>#<?= $o['id'] ?></span>
                                    </div>
                                    <h4 class="oc-title"><?= htmlspecialchars($o['book_name']) ?></h4>
                                    <div class="oc-author">by <?= htmlspecialchars($o['author_name']) ?></div>

                                    <div class="oc-meta-row">
                                        <div class="oc-buyer">
                                            <span>Purchased by</span>
                                            <?= htmlspecialchars($o['buyer_name']) ?>
                                        </div>
                                        <div class="oc-price-tag">
                                            <div class="oc-total">₹<?= $o['total_price'] ?></div>
                                            <td style="color:#16a34a; font-weight:600;">
                                                ₹<?= number_format($o['total_price'] * 0.10, 2) ?>
                                            </td>
                                            <div class="oc-qty">Qty: <?= $o['quantity'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="grid-column:1/-1; text-align:center; padding:3rem; color:#94a3b8;">
                            <i class='bx bx-shopping-bag' style="font-size:3rem; margin-bottom:1rem; color:#e2e8f0;"></i>
                            <p>No orders found yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- REQUESTS -->
        <div id="requests" class="content">
            <div class="section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                    <h3>Pending Book Requests</h3>
                    <span class="badge pending"><?= $pending_books_count ?> pending</span>
                </div>

                <?php if ($pending_books->num_rows > 0): ?>
                    <div class="requests-grid">
                        <?php while ($p = $pending_books->fetch_assoc()): ?>
                            <div class="book-card-unique">
                                <!-- Image Carousel -->
                                <div class="admin-carousel" id="carousel-<?= $p['id'] ?>">
                                    <div class="carousel-track">
                                        <img src="<?= htmlspecialchars($p['cover_image']) ?: 'default_cover.png' ?>"
                                            alt="Cover">
                                        <?php foreach (['image1', 'image2', 'image3'] as $imgField): ?>
                                            <?php if (!empty($p[$imgField])): ?>
                                                <img src="<?= htmlspecialchars($p[$imgField]) ?>" alt="Addition Image">
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Only show arrows if there are more than 1 image -->
                                    <?php
                                    $imgCount = 1;
                                    foreach (['image1', 'image2', 'image3'] as $f)
                                        if (!empty($p[$f]))
                                            $imgCount++;
                                    if ($imgCount > 1):
                                        ?>
                                        <button class="nav-btn prev-btn" onclick="moveCarousel(<?= $p['id'] ?>, -1)"><i
                                                class='bx bx-chevron-left'></i></button>
                                        <button class="nav-btn next-btn" onclick="moveCarousel(<?= $p['id'] ?>, 1)"><i
                                                class='bx bx-chevron-right'></i></button>
                                    <?php endif; ?>
                                </div>

                                <div class="book-details-unique">
                                    <div class="book-category"><?= htmlspecialchars($p['category'] ?? 'General') ?></div>
                                    <h3 class="book-title"><?= htmlspecialchars($p['book_name']) ?></h3>
                                    <div class="book-author">by <?= htmlspecialchars($p['author_name']) ?></div>
                                    <div class="book-publisher">Publisher: <?= htmlspecialchars($p['publisher']) ?></div>

                                    <div class="book-footer">
                                        <div style="display:flex; flex-direction:column; gap:4px;">
                                            <div class="book-price">₹<?= $p['price'] ?></div>
                                            <div style="font-size: 0.8rem; color: var(--light); font-weight: 500;">
                                                Quantity: <?= $p['quantity'] ?>
                                            </div>
                                        </div>

                                        <div style="display:flex; gap:10px;">
                                            <form method="post" action="approve_book.php">
                                                <input type="hidden" name="book_id" value="<?= $p['id'] ?>">
                                                <button class="btn-approve" title="Approve Book">
                                                    <i class='bx bx-check'></i>
                                                </button>
                                            </form>

                                            <form method="post" action="reject_book.php">
                                                <input type="hidden" name="book_id" value="<?= $p['id'] ?>">
                                                <button class="btn-reject" title="Reject Book">
                                                    <i class='bx bx-x'></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div
                        style="text-align:center; padding:5rem 2rem; background:white; border-radius:18px; border: 1px dashed #cbd5e1;">
                        <i class='bx bx-check-circle' style="font-size:4rem; color:#22c55e; margin-bottom:1rem;"></i>
                        <h3 style="color:#1e293b;">All caught up!</h3>
                        <p style="color:#64748b;">No pending book requests at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- FEEDBACK -->
        <div id="feedback" class="content">
            <div class="section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                    <h3>User Feedback</h3>
                    <span class="badge success"><?= $all_feedback->num_rows ?> total</span>
                </div>

                <?php if ($all_feedback->num_rows > 0): ?>
                    <div class="feedback-grid">
                        <?php while ($fb = $all_feedback->fetch_assoc()): ?>
                            <div class="feedback-card">
                                <div class="fb-header">
                                    <div class="fb-user">
                                        <div class="fb-avatar"><?= strtoupper(substr($fb['name'], 0, 1)) ?></div>
                                        <div class="fb-info">
                                            <h4><?= htmlspecialchars($fb['name']) ?></h4>
                                            <p><?= htmlspecialchars($fb['email']) ?></p>
                                        </div>
                                    </div>
                                    <span style="font-size: 0.7rem; color: var(--light);">
                                        <?= date('M d, Y', strtotime($fb['created_at'])) ?>
                                    </span>
                                </div>
                                <span class="fb-subject"><?= htmlspecialchars($fb['subject']) ?></span>
                                <div class="fb-message">
                                    <?= nl2br(htmlspecialchars($fb['message'])) ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:5rem 2rem; background:white; border-radius:18px;">
                        <i class='bx bx-message-alt-x' style="font-size:4rem; color:#cbd5e1; margin-bottom:1rem;"></i>
                        <h3 style="color:#64748b;">No feedback yet</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- BOOK REVIEWS -->
        <div id="reviews" class="content">
            <div class="section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                    <h3>Book Reviews</h3>
                    <span class="badge success"><?= $all_book_reviews->num_rows ?> total</span>
                </div>

                <?php if ($all_book_reviews->num_rows > 0): ?>
                    <div class="review-list">
                        <?php while ($rev = $all_book_reviews->fetch_assoc()): ?>
                            <div class="review-item">
                                <img src="<?= htmlspecialchars($rev['cover_image']) ?: 'default_cover.png' ?>"
                                    class="rev-book-img">
                                <div class="rev-content">
                                    <div class="rev-header">
                                        <div>
                                            <div class="rev-title"><?= htmlspecialchars($rev['book_name']) ?></div>
                                            <div class="rev-user">Reviewed by
                                                <strong><?= htmlspecialchars($rev['user_name']) ?></strong></div>
                                        </div>
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class='bx <?= $i <= $rev['rating'] ? 'bxs-star' : 'bx-star' ?>'></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="rev-text">
                                        "<?= nl2br(htmlspecialchars($rev['review'])) ?>"
                                    </div>
                                    <div style="margin-top: 10px; font-size: 0.75rem; color: var(--light);">
                                        Posted on <?= date('F j, Y', strtotime($rev['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:5rem 2rem; background:white; border-radius:18px;">
                        <i class='bx bx-star' style="font-size:4rem; color:#cbd5e1; margin-bottom:1rem;"></i>
                        <h3 style="color:#64748b;">No reviews found</h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- USERS -->
        <div id="users" class="content">
            <div class="section">
                <h3>Registered Users</h3>

                <style>
                    .user-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                        gap: 25px;
                    }

                    .user-card {
                        background: white;
                        border-radius: 20px;
                        padding: 25px;
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                        border: 1px solid rgba(0, 0, 0, 0.05);
                        transition: transform 0.3s;
                        display: flex;
                        flex-direction: column;
                        gap: 20px;
                    }

                    .user-card:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                    }

                    .uc-header {
                        display: flex;
                        align-items: center;
                        gap: 15px;
                    }

                    .uc-icon {
                        width: 60px;
                        height: 60px;
                        background: linear-gradient(135deg, #6366f1, #a855f7);
                        color: white;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1.8rem;
                        font-weight: 700;
                    }

                    .uc-info h4 {
                        margin: 0;
                        font-size: 1.2rem;
                        color: #1e293b;
                    }

                    .uc-info p {
                        margin: 2px 0 0;
                        font-size: 0.9rem;
                        color: #64748b;
                    }

                    .uc-details {
                        display: grid;
                        gap: 10px;
                        font-size: 0.9rem;
                        color: #475569;
                        background: #f8fafc;
                        padding: 15px;
                        border-radius: 12px;
                    }

                    .uc-row {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    }

                    .uc-row i {
                        color: #818cf8;
                        width: 20px;
                    }

                    .uc-books h5 {
                        margin: 0 0 10px;
                        font-size: 0.85rem;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        color: #94a3b8;
                    }

                    .uc-book-list {
                        display: flex;
                        gap: 10px;
                        overflow-x: auto;
                        padding-bottom: 5px;
                    }

                    .uc-book-thumb {
                        width: 50px;
                        height: 75px;
                        border-radius: 6px;
                        overflow: hidden;
                        flex-shrink: 0;
                        border: 1px solid #e2e8f0;
                        position: relative;
                    }

                    .uc-book-thumb img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }

                    .uc-book-thumb:hover {
                        transform: scale(1.1);
                        transition: 0.2s;
                    }

                    .uc-empty-books {
                        font-size: 0.85rem;
                        font-style: italic;
                        color: #cbd5e1;
                    }

                    /* Scrollbar for book list */
                    .uc-book-list::-webkit-scrollbar {
                        height: 4px;
                    }

                    .uc-book-list::-webkit-scrollbar-thumb {
                        background: #cbd5e1;
                        border-radius: 4px;
                    }
                </style>

                <div class="user-grid">
                    <?php while ($u = $all_users->fetch_assoc()): ?>
                        <div class="user-card">
                            <div class="uc-header">
                                <div class="uc-icon"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                                <div class="uc-info">
                                    <h4><?= htmlspecialchars($u['name']) ?></h4>
                                    <span class="badge success"><?= ucfirst($u['role']) ?></span>
                                </div>
                            </div>

                            <div class="uc-details">
                                <div class="uc-row" title="Email"><i class='bx bxs-envelope'></i>
                                    <?= htmlspecialchars($u['email']) ?></div>
                                <div class="uc-row" title="Phone"><i class='bx bxs-phone'></i>
                                    <?= htmlspecialchars($u['phone'] ?? 'N/A') ?></div>
                                <div class="uc-row" title="Address"><i class='bx bxs-map'></i>
                                    <span
                                        style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">
                                        <?= htmlspecialchars($u['address'] ?? 'N/A') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="uc-books">
                                <?php
                                $u_books = $user_books[$u['id']] ?? [];
                                $book_count = count($u_books);
                                ?>
                                <h5>Uploaded Books (<?= $book_count ?>)</h5>

                                <?php if ($book_count > 0): ?>
                                    <div class="uc-book-list">
                                        <?php foreach ($u_books as $ub): ?>
                                            <div class="uc-book-thumb" title="<?= htmlspecialchars($ub['book_name']) ?>">
                                                <img src="<?= htmlspecialchars($ub['cover_image']) ?: 'default_cover.png' ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="uc-empty-books">No books uploaded yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        function openTab(id, el) {
            document.querySelectorAll('.content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            el.classList.add('active');
        }

        // Handle Image Carousels in cards
        window.carouselIndices = {};
        function moveCarousel(id, dir) {
            const carousel = document.getElementById('carousel-' + id);
            if (!carousel) return;
            const track = carousel.querySelector('.carousel-track');
            const slides = track.children.length;

            if (window.carouselIndices[id] === undefined) window.carouselIndices[id] = 0;

            window.carouselIndices[id] = (window.carouselIndices[id] + dir + slides) % slides;
            track.style.transform = `translateX(-${window.carouselIndices[id] * 100}%)`;
        }
    </script>

    </body>

</html>