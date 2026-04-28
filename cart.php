<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['user_name'] ?? '';
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

// Handle messages
$error_msg = "";

/* =========================
   CART ACTIONS LOGIC 
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['book_id'])) {
    $book_id = (int)$_POST['book_id'];
    $action = $_POST['action'];

    // Fetch available quantity
    $stmt = $conn->prepare("SELECT quantity, book_name FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $bookInfo = $stmt->get_result()->fetch_assoc();
    $availableQty = $bookInfo['quantity'] ?? 0;
    $bName = $bookInfo['book_name'] ?? 'Book';

    if (array_key_exists($book_id, $_SESSION['cart'])) {
        if ($action === 'increase') {
            if ($_SESSION['cart'][$book_id] < $availableQty) {
                $_SESSION['cart'][$book_id]++;
            } else {
                if (isset($_POST['ajax_update'])) {
                    echo json_encode(['status' => 'error', 'message' => "Maximum quantity reached. No more quantity available.", 'new_qty' => $_SESSION['cart'][$book_id], 'price' => $bookInfo['price']]);
                    exit();
                }
                // SET ERROR MESSAGE IN SESSION
                $_SESSION['cart_error'] = "Maximum quantity reached. No more quantity available.";
            }
        }
        elseif ($action === 'decrease' && $_SESSION['cart'][$book_id] > 1) {
            $_SESSION['cart'][$book_id]--;
        }
        elseif ($action === 'remove' || ($action === 'decrease' && $_SESSION['cart'][$book_id] <= 1)) {
            unset($_SESSION['cart'][$book_id]);
        }
    }

    if (isset($_POST['ajax_update'])) {
        // Recalculate total if doing ajax
        $cart = $_SESSION['cart'];
        $total = 0;
        $new_qty = 0;
        $is_max = false;
        if (!empty($cart)) {
            $ids = array_keys($cart);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $resStmt = $conn->prepare("SELECT id, price, quantity FROM books WHERE id IN ($placeholders)");
            $resStmt->bind_param(str_repeat('i', count($ids)), ...$ids);
            $resStmt->execute();
            $resRes = $resStmt->get_result();
            while ($r = $resRes->fetch_assoc()) {
                $total += $r['price'] * $cart[$r['id']];
                if ($r['id'] == $book_id) {
                    $new_qty = $cart[$r['id']];
                    if ($new_qty >= $r['quantity']) $is_max = true;
                }
            }
        }
        echo json_encode([
            'status' => 'success', 
            'new_qty' => $new_qty, 
            'new_total' => $total, 
            'cart_empty' => empty($cart),
            'is_max' => $is_max
        ]);
        exit();
    }

    header("Location: cart.php");
    exit();
}

// Get and then clear error message from session
if (isset($_SESSION['cart_error'])) {
    $error_msg = $_SESSION['cart_error'];
    unset($_SESSION['cart_error']);
}

$cart = $_SESSION['cart'];
$bookData = [];
$total = 0;

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT id, book_name, author_name, price, cover_image, quantity FROM books WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $bookData[] = $row;
        $total += $row['price'] * $cart[$row['id']];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | Alpha Book</title>
    <style>
        /* ... existing styles ... */
        :root {
            --primary: #4f46e5;
            --danger: #ef4444;
            --bg: #f8fafc;
            --white: #ffffff;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); margin: 0; }
        .cart-wrapper { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        /* NEW STYLES FOR ERROR MESSAGE */
        .alert-box {
            background: #fee2e2;
            border-left: 5px solid var(--danger);
            color: #b91c1c;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .max-qty-notice {
            font-size: 0.8rem;
            color: var(--danger);
            margin-top: 5px;
            font-weight: 500;
        }

        /* Standard Cart Layout */
        .cart-title { font-size: 2rem; font-weight: 700; margin-bottom: 30px; display: flex; align-items: center; gap: 12px; }
        .cart-grid { display: grid; grid-template-columns: 1fr 380px; gap: 30px; }
        .cart-card { background: var(--white); border-radius: 20px; padding: 20px; display: flex; gap: 25px; box-shadow: var(--shadow); margin-bottom: 20px;}
        .book-img { width: 100px; height: 140px; border-radius: 10px; object-fit: cover; }
        .book-info { flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .price-tag { font-size: 1.2rem; font-weight: 700; color: var(--primary); }
        .quantity-control { display: flex; align-items: center; gap: 15px; background: #f1f5f9; width: fit-content; padding: 5px 15px; border-radius: 50px; }
        .qty-btn { background: none; border: none; cursor: pointer; color: var(--primary); font-size: 1.2rem; }
        .qty-btn:disabled { color: #cbd5e1; cursor: not-allowed; }
        .order-summary { background: var(--white); border-radius: 24px; padding: 30px; box-shadow: var(--shadow); position: sticky; top: 20px; }
        .checkout-btn { width: 100%; background: var(--primary); color: white; border: none; padding: 15px; border-radius: 12px; font-weight: 700; cursor: pointer; margin-top: 20px; }
    </style>
</head>
<body>

<?php 
$hide_loader = true;
include 'header.php'; 
?>

<div class="cart-wrapper">
    <?php if ($error_msg): ?>
        <div class="alert-box">
            <i class='bx bx-error-circle'></i>
            <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <div class="cart-title">
        <i class='bx bx-shopping-bag'></i> Your Shopping Cart
    </div>

    <div id="cart-main-content">
        <?php if (!empty($bookData)): ?>
    <div class="cart-grid">
        <div class="cart-items-list">
            <?php foreach ($bookData as $book): 
                $currentQty = $_SESSION['cart'][$book['id']];
                $availableQty = (int)$book['quantity'];
            ?>
            <div class="cart-card">
                <img src="<?= htmlspecialchars($book['cover_image']) ?>" class="book-img">
                <div class="book-info">
                    <div>
                        <h3 style="margin:0;"><?= htmlspecialchars($book['book_name']) ?></h3>
                        <p style="color:var(--text-light); margin:5px 0;">by <?= htmlspecialchars($book['author_name']) ?></p>
                    </div>

                    <div class="price-tag">₹<?= number_format($book['price'], 2) ?></div>

                    <div>
                        <div class="quantity-control">
                            <button type="button" class="qty-btn" onclick="updateCartQty(this, <?= $book['id'] ?>, 'decrease')"><i class='bx bx-minus'></i></button>

                            <span class="qty-display-<?= $book['id'] ?>" style="font-weight:600;"><?= $currentQty ?></span>

                            <button type="button" class="qty-btn inc-btn-<?= $book['id'] ?>" onclick="updateCartQty(this, <?= $book['id'] ?>, 'increase')" 
                                <?= ($currentQty >= $availableQty) ? 'disabled' : '' ?>>
                                <i class='bx bx-plus'></i>
                            </button>
                        </div>
                        
                        <div class="max-qty-notice-container-<?= $book['id'] ?>">
                            <?php if($currentQty >= $availableQty): ?>
                                <div class="max-qty-notice">
                                    <i class='bx bx-info-circle'></i> Maximum quantity reached. No more quantity available.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="order-summary">
            <h3>Order Summary</h3>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <span>Subtotal</span>
                <span id="cart-subtotal">₹<?= number_format($total, 2) ?></span>
            </div>
            <hr>
            <div style="display:flex; justify-content:space-between; font-size:1.4rem; font-weight:800;">
                <span>Total</span>
                <span id="cart-total">₹<?= number_format($total, 2) ?></span>
            </div>
            <form action="checkout.php" method="get">
                <button class="checkout-btn">Buy Now</button>
            </form>
        </div>
    </div>
        <?php else: ?>
            <div style="text-align:center; padding:50px;">
                <i class='bx bx-cart' style="font-size:4rem; color:#ccc;"></i>
                <h3>Your cart is empty</h3>
                <a href="book.php" style="color:var(--primary); font-weight:600;">Browse Books</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateCartQty(btn, bookId, action) {
    const originalHtml = btn.innerHTML;
    btn.innerHTML = "<i class='bx bx-loader bx-spin'></i>";
    btn.disabled = true;

    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `ajax_update=1&book_id=${bookId}&action=${action}`
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;

        if (data.status === 'error') {
            showToastMessage(data.message, 'warning');
            
            // If it was a max qty error, ensure the notice is shown
            const noticeContainer = document.querySelector('.max-qty-notice-container-' + bookId);
            if(noticeContainer && !noticeContainer.querySelector('.max-qty-notice')) {
                noticeContainer.innerHTML = `<div class="max-qty-notice"><i class='bx bx-info-circle'></i> Maximum quantity reached. No more quantity available.</div>`;
            }
            const incBtn = document.querySelector('.inc-btn-' + bookId);
            if(incBtn) incBtn.disabled = true;

        } else if (data.status === 'success') {
            if (data.cart_empty) {
                document.getElementById('cart-main-content').innerHTML = `
                    <div style="text-align:center; padding:50px;">
                        <i class='bx bx-cart' style="font-size:4rem; color:#ccc;"></i>
                        <h3>Your cart is empty</h3>
                        <a href="book.php" style="color:var(--primary); font-weight:600;">Browse Books</a>
                    </div>`;
                // Update header cart count
                const headerBadge = document.querySelector('.cart-count');
                if(headerBadge) headerBadge.remove();
                return;
            }

            if (data.new_qty === 0) {
                // Update display to 0 so total count is correct
                const qtyDisplay = document.querySelector('.qty-display-' + bookId);
                if(qtyDisplay) qtyDisplay.innerText = '0';
                
                // Remove the card with a slight fade
                const card = btn.closest('.cart-card');
                if(card) {
                    card.style.opacity = '0';
                    card.style.transform = 'translateX(20px)';
                    card.style.transition = 'all 0.3s ease';
                    setTimeout(() => card.remove(), 300);
                }
            } else {
                // Update quantity display
                const qtyDisplay = document.querySelector('.qty-display-' + bookId);
                if(qtyDisplay) qtyDisplay.innerText = data.new_qty;
                
                // Toggle max notice
                const noticeContainer = document.querySelector('.max-qty-notice-container-' + bookId);
                const incBtn = document.querySelector('.inc-btn-' + bookId);
                
                if (data.is_max) {
                    if(noticeContainer && !noticeContainer.querySelector('.max-qty-notice')) {
                        noticeContainer.innerHTML = `<div class="max-qty-notice"><i class='bx bx-info-circle'></i> Maximum quantity reached. No more quantity available.</div>`;
                    }
                    if(incBtn) incBtn.disabled = true;
                } else {
                    if(noticeContainer) noticeContainer.innerHTML = '';
                    if(incBtn) incBtn.disabled = false;
                }
            }
            
            // Update totals
            const totalFormatted = '₹' + parseFloat(data.new_total).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            const subtotalEl = document.getElementById('cart-subtotal');
            const totalEl = document.getElementById('cart-total');
            if(subtotalEl) subtotalEl.innerText = totalFormatted;
            if(totalEl) totalEl.innerText = totalFormatted;

            // Update header cart count
            let totalItemsCount = 0;
            // This is a bit hacky but we can sum the displays
            document.querySelectorAll('[class^="qty-display-"]').forEach(el => {
                totalItemsCount += parseInt(el.innerText);
            });
            const headerBadge = document.querySelector('.cart-count');
            if(headerBadge) {
                if(totalItemsCount > 0) headerBadge.innerText = totalItemsCount;
                else headerBadge.remove();
            }
        }
    })
    .catch(err => {
        console.error(err);
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}
</script>

</body>
</html>