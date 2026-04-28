<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['user_name'] ?? '';
$cart = $_SESSION['cart'] ?? [];
$books = [];
$grandTotal = 0;
$allOutOfStock = true;

if (!empty($cart)) {
    $ids = implode(",", array_keys($cart));
    $query = "SELECT * FROM books WHERE id IN ($ids)";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $bookId = $row['id'];
        $quantityOrdered = $cart[$bookId];
        $row['quantity_ordered'] = $quantityOrdered;

        if ($row['quantity'] > 0) {
            $row['total_price'] = $quantityOrdered * $row['price'];
            $grandTotal += $row['total_price'];
            $allOutOfStock = false;
        } else {
            $row['total_price'] = 0;
        }

        $books[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bill - Alpha Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .bill-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 2.5rem;
            max-width: 900px;
            margin: 2rem auto;
            border: 1px solid var(--border-color);
        }

        .bill-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .bill-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .bill-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bill-table th {
            text-align: left;
            padding: 1rem;
            color: var(--text-light);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border-color);
        }

        .bill-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .bill-table tr:last-child td {
            border-bottom: none;
        }

        .out-of-stock {
            color: var(--danger);
            font-weight: 600;
        }

        .grand-total {
            text-align: right;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid var(--border-color);
        }

        .checkout-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 2rem;
            gap: 1rem;
        }

        .btn-download {
            background-color: var(--success);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-download:hover {
            filter: brightness(0.9);
        }
    </style>
</head>

<body
    style="background: linear-gradient(135deg, #eef2ff, #f3f4f6); min-height: 100vh; display: flex; flex-direction: column;">

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <i class='bx bxs-book-heart'></i> Alpha Book
            </a>

            <div class="nav-menu">
                <a href="index.php" class="nav-link">Home</a>
                <a href="book.php" class="nav-link">Books</a>
                <a href="cart.php" class="nav-link active">Cart</a>
            </div>

            <div class="nav-icons">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-weight: 500; font-size: 0.9rem;"><?= htmlspecialchars($userName) ?></span>
                    <a href="logout.php" class="btn btn-outline"
                        style="padding: 0.3rem 1rem; border-radius: 50px; font-size: 0.85rem;">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ===== MAIN CONTENT ===== -->
    <div style="flex: 1; padding: 6rem 1rem 3rem;">
        <div class="bill-card">
            <div class="bill-header">
                <h2 class="bill-title">Order Billing</h2>
                <?php if (!empty($books) && !$allOutOfStock): ?>
                    <form action="invoice.php" method="post" target="_blank">
                        <input type="hidden" name="books" value='<?= json_encode($books) ?>'>
                        <input type="hidden" name="grand_total" value="<?= $grandTotal ?>">
                        <button type="submit" class="btn-download">
                            <i class='bx bx-download'></i> Invoice
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (!empty($books)): ?>
                <div class="table-responsive">
                    <table class="bill-table">
                        <thead>
                            <tr>
                                <th>Book Name</th>
                                <th>Author</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($book['book_name']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($book['author_name']) ?></td>
                                    <?php if ($book['quantity'] == 0): ?>
                                        <td colspan="3" class="out-of-stock">Out of Stock</td>
                                    <?php else: ?>
                                        <td>₹<?= $book['price'] ?></td>
                                        <td><?= $book['quantity_ordered'] ?></td>
                                        <td style="font-weight: 600;">₹<?= $book['total_price'] ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="grand-total">Total: ₹<?= $grandTotal ?></div>

                <div class="checkout-actions">
                    <?php if (!$allOutOfStock): ?>
                        <a href="cart.php" class="btn btn-outline" style="border-radius: 50px;">Back to Cart</a>
                        <form id="paymentForm" action="payment.php" method="POST">
                            <input type="hidden" name="total_amount" value="<?= $grandTotal ?>">

                            <div style="margin-bottom: 1.5rem; text-align: left;">
                                <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Select Payment
                                    Method:</label>
                                <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
                                    <label
                                        style="cursor: pointer; display: flex; align-items: center; gap: 5px; background: white; padding: 10px 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                                        <input type="radio" name="payment_method" value="phonepe" checked
                                            onchange="updateAction()">
                                        <span><i class='bx bx-mobile-alt'></i> Online Payment (UPI / PhonePe)</span>
                                    </label>
                                    <label
                                        style="cursor: pointer; display: flex; align-items: center; gap: 5px; background: white; padding: 10px 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                                        <input type="radio" name="payment_method" value="cod" onchange="updateAction()">
                                        <span><i class='bx bx-money'></i> Cash on Delivery</span>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary"
                                style="padding: 0.8rem 2rem; border-radius: 50px; font-size: 1rem;">
                                Proceed <i class='bx bx-right-arrow-alt'></i>
                            </button>
                        </form>

                        <script>
                            function updateAction() {
                                const method = document.querySelector('input[name="payment_method"]:checked').value;
                                const form = document.getElementById('paymentForm');
                                if (method === 'cod') {
                                    form.action = 'payment_success.php';
                                } else {
                                    form.action = 'pay_phonepe.php';
                                }
                            }
                        </script>
                    <?php else: ?>
                        <p style="color: var(--danger); font-weight: 500; margin-right: auto;">All selected books are out of
                            stock.</p>
                        <a href="cart.php" class="btn btn-outline">Back to Cart</a>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div style="text-align: center; padding: 3rem;">
                    <p style="color: var(--text-light); font-size: 1.2rem;">Your cart is empty.</p>
                    <a href="book.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Books</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>