<?php
session_start();
include 'db.php';

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch pending book requests
$sql = "SELECT books.*, users.name AS user_name, users.email
        FROM books
        JOIN users ON books.user_id = users.id
        WHERE books.status = 'pending'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin - Book Requests</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: var(--bg-color);
            padding: 2rem;
        }

        .admin-container {
            max-width: 1300px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            border: 1px solid var(--border-color);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .admin-header h2 {
            font-size: 1.8rem;
            color: var(--text-dark);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        th {
            background: var(--bg-color);
            color: var(--text-light);
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        tr:hover td {
            background: var(--bg-color);
        }

        /* Image Grid for Admin */
        .img-grid {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            max-width: 250px;
        }

        .img-thumb {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .img-thumb:hover {
            transform: scale(1.5);
            z-index: 10;
            position: relative;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .img-label {
            font-size: 0.7rem;
            color: #777;
            width: 100%;
            display: block;
            margin-bottom: 2px;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 5px;
        }

        .approve {
            background: var(--success);
        }

        .reject {
            background: var(--danger);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>

    <a href="Admin_home.php" class="back-link"><i class='bx bx-arrow-back'></i> Back to Dashboard</a>

    <div class="admin-container">
        <div class="admin-header">
            <h2>Pending Book Publish Requests</h2>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 250px;">Book Images (4)</th>
                        <th>Book Details</th>
                        <th>Author</th>
                        <th>Price/Qty</th>
                        <th>Publisher Info</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="img-grid">
                                        <div><span class="img-label">Cover</span><img
                                                src="<?= htmlspecialchars($row['cover_image']) ?>" class="img-thumb"></div>
                                        <?php if (!empty($row['image1'])): ?>
                                            <div><span class="img-label">Index</span><img
                                                    src="<?= htmlspecialchars($row['image1']) ?>" class="img-thumb"></div>
                                        <?php endif; ?>
                                        <?php if (!empty($row['image2'])): ?>
                                            <div><span class="img-label">Page 1</span><img
                                                    src="<?= htmlspecialchars($row['image2']) ?>" class="img-thumb"></div>
                                        <?php endif; ?>
                                        <?php if (!empty($row['image3'])): ?>
                                            <div><span class="img-label">Desc</span><img
                                                    src="<?= htmlspecialchars($row['image3']) ?>" class="img-thumb"></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:700; font-size: 1.1rem; color:var(--text-dark);">
                                        <?= htmlspecialchars($row['book_name']) ?></div>
                                    <div style="font-size:0.85rem; color:var(--primary); margin-bottom: 5px;">
                                        <?= htmlspecialchars($row['category']) ?></div>
                                    <div
                                        style="font-size:0.85rem; color:var(--text-light); max-height: 80px; overflow-y: auto;">
                                        <?= htmlspecialchars($row['description']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($row['author_name']) ?></td>
                                <td>
                                    <div style="font-weight:600;">₹<?= $row['price'] ?></div>
                                    <div style="font-size:0.8rem; color:var(--text-light);">Qty: <?= $row['quantity'] ?></div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($row['user_name']) ?></div>
                                    <div style="font-size:0.8rem; color:var(--text-light);">
                                        <?= htmlspecialchars($row['email']) ?></div>
                                </td>
                                <td>
                                    <form action="approve_book.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="action-btn approve">Approve</button>
                                    </form>
                                    <form action="reject_book.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="action-btn reject">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 3rem; color: var(--text-light);">No pending
                                requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>