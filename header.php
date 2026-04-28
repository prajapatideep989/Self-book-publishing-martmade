<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$user_id = $isLoggedIn ? $_SESSION['user_id'] : 0;

// --- RED DOT LOGIC (Earnings & Book Status) ---
$show_red_dot = false;         // Main badge on User Icon
$show_earnings_dot = false;    // Dot for Earnings link
$show_account_dot = false;     // Dot for My Account link
$show_orders_dot = false;      // Dot for My Orders link

if ($isLoggedIn) {
    // 1. EARNINGS CHECK (New Sales)
    $notif_q = $conn->prepare("SELECT MAX(o.order_date) as last_sale FROM orders o JOIN books b ON o.book_id = b.id WHERE b.user_id = ?");
    $notif_q->bind_param("i", $user_id);
    $notif_q->execute();
    $notif_res = $notif_q->get_result()->fetch_assoc();
    $last_sale_time = $notif_res['last_sale'] ? strtotime($notif_res['last_sale']) : 0;

    // 2. BOOK STATUS CHECK (Admin Approval/Rejection)
    $status_q = $conn->prepare("SELECT MAX(status_updated_at) as last_update FROM books WHERE user_id = ? AND status != 'pending'");
    $last_book_update = 0;
    if ($status_q) {
        $status_q->bind_param("i", $user_id);
        $status_q->execute();
        $status_res = $status_q->get_result()->fetch_assoc();
        $last_book_update = $status_res['last_update'] ? strtotime($status_res['last_update']) : 0;
    }

    // 3. GET USER VIEW TIMES FROM DATABASE
    $user_q = $conn->prepare("SELECT last_earnings_view, last_account_view FROM users WHERE id = ?");
    $user_q->bind_param("i", $user_id);
    $user_q->execute();
    $u_res = $user_q->get_result()->fetch_assoc();

    $last_earn_view = isset($u_res['last_earnings_view']) ? strtotime($u_res['last_earnings_view']) : 0;
    $last_acc_view = isset($u_res['last_account_view']) ? strtotime($u_res['last_account_view']) : 0;

    // 4. ORDER STATUS CHECK (New statuses from author)
    $orders_q = $conn->prepare("SELECT COUNT(*) as unread FROM orders WHERE user_id = ? AND status_update_seen = 0 AND status != 'Pending'");
    $orders_q->bind_param("i", $user_id);
    $orders_q->execute();
    $orders_res = $orders_q->get_result()->fetch_assoc();
    if ($orders_res['unread'] > 0) {
        $show_orders_dot = true;
    }

    // Determine specific dots
    if ($last_sale_time > $last_earn_view) {
        $show_earnings_dot = true;
    }
    if ($last_book_update > $last_acc_view) {
        $show_account_dot = true;
    }

    // Show main red dot if any section has unread updates
    if ($show_earnings_dot || $show_account_dot || $show_orders_dot) {
        $show_red_dot = true;
    }

    // --- NEW: FETCH SPECIFIC SALE MESSAGES FOR TOASTS ---
    $sale_notifs = [];
    if ($show_earnings_dot) {
        $sales_q = $conn->prepare("
            SELECT u.name as buyer_name, b.book_name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            JOIN books b ON o.book_id = b.id 
            WHERE b.user_id = ? AND o.order_date > (SELECT last_earnings_view FROM users WHERE id = ?)
            ORDER BY o.order_date DESC LIMIT 3
        ");
        $sales_q->bind_param("ii", $user_id, $user_id);
        $sales_q->execute();
        $sales_res = $sales_q->get_result();
        while ($s = $sales_res->fetch_assoc()) {
            $sale_notifs[] = "User <b>{$s['buyer_name']}</b> bought your book <b>'{$s['book_name']}'</b>";
        }
    }
}

$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += (int) $qty;
    }
}
?>
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<!-- 3D & Animation Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/0.160.0/three.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<style>
    .navbar {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        padding: 1rem 2rem;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .nav-container {
        max-width: 1300px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        font-size: 1.8rem;
        font-weight: 800;
        color: #0f172a;
        /* Slate 900 */
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        letter-spacing: -0.5px;
    }

    .logo i {
        color: #6366f1;
        /* Indigo 500 */
    }

    .nav-menu {
        display: flex;
        gap: 2.5rem;
    }

    .nav-link {
        text-decoration: none;
        color: #64748b;
        /* Slate 500 */
        font-weight: 500;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        position: relative;
    }

    .nav-link::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 0;
        height: 2px;
        background: #6366f1;
        /* Indigo 500 */
        transition: width 0.3s ease;
    }

    .nav-link:hover {
        color: #6366f1;
        /* Indigo 500 */
    }

    .nav-link:hover::after {
        width: 100%;
    }

    .nav-icons {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .icon-btn {
        background: none;
        border: none;
        font-size: 1.6rem;
        cursor: pointer;
        color: #1e293b;
        /* Slate 800 */
        text-decoration: none;
        display: flex;
        align-items: center;
        position: relative;
        transition: color 0.3s;
    }

    .icon-btn:hover {
        color: #6366f1;
        /* Indigo 500 */
    }

    .cart-icon {
        position: relative;
    }

    .cart-count {
        position: absolute;
        top: -6px;
        right: -8px;
        background: #ef4444;
        /* Red 500 */
        color: #fff;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 50%;
        min-width: 18px;
        text-align: center;
        line-height: 1;
    }

    .user-dropdown {
        position: relative;
    }

    .dropdown-menu {
        position: absolute;
        right: 0;
        top: 130%;
        background: #fff;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        width: 220px;
        padding: 0.5rem 0;
        border: 1px solid #f3f4f6;
        z-index: 1001;

        /* Animation properties */
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px) scale(0.95);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
    }

    .dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0.7rem 1.2rem;
        color: #4b5563;
        /* Gray 600 */
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.2s;

        /* Staggered animation properties */
        opacity: 0;
        transform: translateY(10px);
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.4s;
    }

    .dropdown-menu.show a {
        opacity: 1;
        transform: translateY(0);
    }

    /* Staggered Delays for each link */
    .dropdown-menu.show a:nth-child(2) {
        transition-delay: 0.1s;
    }

    .dropdown-menu.show a:nth-child(3) {
        transition-delay: 0.15s;
    }

    .dropdown-menu.show a:nth-child(4) {
        transition-delay: 0.2s;
    }

    .dropdown-menu.show a:nth-child(5) {
        transition-delay: 0.25s;
    }

    .dropdown-menu.show a:nth-child(7) {
        transition-delay: 0.3s;
    }

    /* Skipping the divider */

    .dropdown-menu a:hover {
        background: #f9fafb;
        color: #6366f1;
        /* Indigo 500 */
    }

    .dropdown-header {
        padding: 0.7rem 1.2rem;
        font-weight: 700;
        border-bottom: 1px solid #f3f4f6;
        margin-bottom: 5px;
        color: #1f2937;
        opacity: 0;
        transform: translateY(5px);
        transition: all 0.3s ease 0.05s;
    }

    .dropdown-menu.show .dropdown-header {
        opacity: 1;
        transform: translateY(0);
    }

    .dropdown-divider {
        height: 1px;
        background: #f3f4f6;
        margin: 5px 0;
    }

    .btn-primary {
        background: #6366f1;
        /* Indigo 500 */
        color: white;
        text-decoration: none;
        padding: 0.7rem 1.8rem;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
    }

    .btn-primary:hover {
        background: #4f46e5;
        /* Indigo 600 */
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4);
    }

    /* Red Dot Badge */
    .red-dot-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        border: 2px solid white;
    }

    .inline-dot {
        display: inline-block;
        width: 6px;
        height: 6px;
        background: #ef4444;
        border-radius: 50%;
        margin-left: 5px;
    }

    /* PREMIUM PAGE LOADER */
    #alpha-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #0f172a;
        /* Dark Slate */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.8s;
    }

    .loader-content {
        text-align: center;
        position: relative;
    }

    .loader-icon {
        font-size: 4rem;
        color: #6366f1;
        margin-bottom: 1.5rem;
        display: block;
        animation: loader-pulse 2s infinite ease-in-out;
        filter: drop-shadow(0 0 15px rgba(99, 102, 241, 0.5));
    }

    .loader-text {
        font-family: 'Outfit', sans-serif;
        color: white;
        font-weight: 700;
        letter-spacing: 0.3rem;
        text-transform: uppercase;
        font-size: 1.2rem;
        opacity: 0;
        transform: translateY(10px);
        animation: loader-fade-up 0.8s forwards 0.3s;
    }

    .loader-bar-container {
        width: 200px;
        height: 2px;
        background: rgba(255, 255, 255, 0.1);
        margin-top: 1.5rem;
        border-radius: 5px;
        overflow: hidden;
    }

    .loader-bar {
        width: 0%;
        height: 100%;
        background: linear-gradient(90deg, #6366f1, #a855f7);
        transition: width 0.4s ease;
    }

    @keyframes loader-pulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 0.8;
        }

        50% {
            transform: scale(1.1);
            opacity: 1;
        }
    }

    @keyframes loader-fade-up {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    body.loading {
        overflow: hidden;
    }
</style>

<?php $hide_loader = isset($hide_loader) ? $hide_loader : false; ?>

<body class="<?= $hide_loader ? '' : 'loading' ?>">
    <?php if (!$hide_loader): ?>
        <div id="alpha-loader">
            <div class="loader-content">
                <i class='bx bxs-book-heart loader-icon'></i>
                <div class="loader-text">Alpha Book</div>
                <div class="loader-bar-container">
                    <div class="loader-bar" id="loaderBar"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class='bx bxs-book-heart'></i> Alpha Book
            </a>

            <div class="nav-menu">
                <a href="index.php" class="nav-link">Home</a>
                <a href="book.php" class="nav-link">Books</a>
                <a href="upload_book.php" class="nav-link">Upload Book</a>
                <a href="About_us.php" class="nav-link">About</a>
                <a href="feedback.php" class="nav-link">Feedback</a>
            </div>

            <div class="nav-icons">
                <a href="cart.php" class="icon-btn cart-icon">
                    <i class='bx bx-cart'></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-count">
                            <?= $cartCount ?>
                        </span>
                    <?php endif; ?>
                </a>

                <?php if ($isLoggedIn): ?>
                    <span class="user-name-header" style="font-weight:600;font-size:0.9rem; color: #4b5563;">
                        <?= htmlspecialchars($userName) ?>
                    </span>

                    <div class="user-dropdown">
                        <button class="icon-btn" onclick="toggleAccount()">
                            <i class='bx bx-user'></i>
                            <?php if ($show_red_dot): ?>
                                <span class="red-dot-badge"></span>
                            <?php endif; ?>
                        </button>

                        <div class="dropdown-menu" id="accountMenu">
                            <div class="dropdown-header">Hello,
                                <?= htmlspecialchars($userName) ?>
                            </div>

                            <a href="myaccount.php">
                                <i class='bx bx-user-circle'></i> My Account
                                <?php if ($show_account_dot): ?>
                                    <span class="inline-dot"></span>
                                <?php endif; ?>
                            </a>

                            <a href="upload_book.php"><i class='bx bx-cloud-upload'></i> Upload Book</a>
                            <a href="Order.php">
                                <i class='bx bx-package'></i> My Orders
                                <?php if ($show_orders_dot): ?>
                                    <span class="inline-dot"></span>
                                <?php endif; ?>
                            </a>

                            <a href="earnings.php">
                                <i class='bx bx-money'></i> My Earnings
                                <?php if ($show_earnings_dot): ?>
                                    <span class="inline-dot"></span>
                                <?php endif; ?>
                            </a>

                            <div class="dropdown-divider"></div>
                            <a href="logout.php" style="color:red;">
                                <i class='bx bx-log-out'></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-primary">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script>
        // --- LOADER LOGIC ---
        window.addEventListener('load', () => {
            const loader = document.getElementById('alpha-loader');
            const loaderBar = document.getElementById('loaderBar');

            // Simulating progressive fill
            if (loaderBar) loaderBar.style.width = '100%';

            setTimeout(() => {
                if (loader) {
                    loader.style.opacity = '0';
                    loader.style.visibility = 'hidden';
                    
                    // Trigger "ready" animations only if loader was shown
                    if (typeof gsap !== 'undefined') {
                        gsap.from(".navbar", { y: -50, opacity: 0, duration: 1, ease: "power3.out" });
                    }
                }
                document.body.classList.remove('loading');

                // EXTRA: Show sale notifications as toasts if any
                <?php if (!empty($sale_notifs)): ?>
                    <?php foreach ($sale_notifs as $index => $msg): ?>
                        setTimeout(() => {
                            showToastMessage("<i class='bx bx-party'></i> <?= addslashes($msg) ?>", "success");
                        }, <?= ($index + 1) * 1000 ?>);
                    <?php endforeach; ?>
                <?php endif; ?>
            }, 800);
        });

        function toggleAccount() {
            document.getElementById("accountMenu").classList.toggle("show");
        }
        window.onclick = function (e) {
            if (!e.target.closest('.user-dropdown')) {
                let m = document.getElementById("accountMenu");
                if (m && m.classList.contains("show")) m.classList.remove("show");
            }
        }
    </script>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div id="toast-message" class="toast <?= htmlspecialchars($_SESSION['flash_type'] ?? 'success') ?>">
            <?= $_SESSION['flash_message'] ?>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const toast = document.getElementById('toast-message');
                if (toast) {
                    setTimeout(() => { toast.classList.add('show'); }, 100);
                    setTimeout(() => { toast.classList.remove('show'); }, 3000);
                }
            });
        </script>
        <?php
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        ?>
    <?php endif; ?>

    <style>
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #fff;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-weight: 600;
            color: #1e293b;
            border-left: 5px solid #6366f1;
            font-family: 'Outfit', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toast.success {
            border-left-color: #10b981;
        }

        .toast.warning {
            border-left-color: #f59e0b;
        }

        .toast.error {
            border-left-color: #ef4444;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
    <script>
        function showToastMessage(msg, type) {
            let toast = document.getElementById('toast-message');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'toast-message';
                document.body.appendChild(toast);
            }
            toast.className = 'toast ' + type;
            toast.innerHTML = msg;

            // force reflow
            void toast.offsetWidth;

            toast.classList.add('show');
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        function addToCart(btn) {
            const form = btn.closest('form');
            if (!form) return;
            const bookId = form.querySelector('input[name="book_id"]').value;
            
            // Set visual loading state on button
            const originalHtml = btn.innerHTML;
            btn.innerHTML = "<i class='bx bx-loader bx-spin'></i>";
            btn.disabled = true;

            const formData = new URLSearchParams();
            formData.append('book_id', bookId);

            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error('Raw response:', text);
                    throw new Error('Invalid server response');
                }
            })
            .then(data => {
                showToastMessage(data.message, data.status);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                
                // If success, update cart count badge
                if(data.status === 'success') {
                    let cartCount = document.querySelector('.cart-count');
                    if(cartCount) {
                        cartCount.innerText = parseInt(cartCount.innerText) + 1;
                    } else {
                        const cartIcon = document.querySelector('.cart-icon');
                        if(cartIcon) {
                            const badge = document.createElement('span');
                            badge.className = 'cart-count';
                            badge.innerText = '1';
                            cartIcon.appendChild(badge);
                        }
                    }
                }
            })
            .catch(err => {
                console.error('Add to Cart Error:', err);
                showToastMessage('❌ Error adding item to cart', 'error');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
        }
    </script>