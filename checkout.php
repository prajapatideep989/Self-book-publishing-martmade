<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Guest';
$cart = $_SESSION['cart'] ?? [];
$books = [];
$grandTotal = 0;

// Fetch User Profile for Autofill
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userProfile = $stmt->get_result()->fetch_assoc();

// Handle Address Submission (via AJAX or POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_address'])) {
    if (!preg_match('/^\d{6}$/', $_POST['pincode'])) {
        echo json_encode(['status' => 'error', 'message' => 'Pincode must be exactly 6 digits.']);
        exit();
    }
    $_SESSION['delivery_details'] = [
        'name' => $_POST['shipping_name'],
        'phone' => $_POST['shipping_phone'],
        'house_no' => $_POST['house_no'],
        'apartment_society' => $_POST['apartment_society'],
        'street' => $_POST['street'],
        'area' => $_POST['area'],
        'landmark' => $_POST['landmark'],
        'pincode' => $_POST['pincode'],
        'city' => $_POST['city'],
        'state' => $_POST['state']
    ];
    
    // If "save to profile" is checked, update the users table
    if (isset($_POST['save_to_profile'])) {
        $upd = $conn->prepare("UPDATE users SET shipping_name=?, shipping_phone=?, house_no=?, apartment_society=?, street=?, area=?, landmark=?, pincode=?, city=?, state=? WHERE id=?");
        if ($upd) {
            $upd->bind_param("ssssssssssi", 
                $_POST['shipping_name'], $_POST['shipping_phone'], $_POST['house_no'], $_POST['apartment_society'], 
                $_POST['street'], $_POST['area'], $_POST['landmark'], 
                $_POST['pincode'], $_POST['city'], $_POST['state'], $user_id
            );
            $upd->execute();
            $upd->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
            exit();
        }
    }
    
    echo json_encode(['status' => 'success']);
    exit();
}

if (!empty($cart)) {
    $ids = implode(",", array_keys($cart));
    $query = "SELECT * FROM books WHERE id IN ($ids)";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $bookId = $row['id'];
        $qty = $cart[$bookId];
        $row['quantity_ordered'] = $qty;
        $row['total_price'] = $qty * $row['price'];
        $grandTotal += $row['total_price'];
        $books[] = $row;
    }
} else {
    echo "<script>alert('Your cart is empty'); window.location.href='cart.php';</script>";
    exit();
}

$has_address = isset($_SESSION['delivery_details']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Checkout – Alpha Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --bg: #f9fafb;
            --white: #ffffff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
        }

        .page-title-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        h1 { font-size: 2rem; margin: 0; }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: 0.3s;
        }

        .btn-back:hover { color: var(--primary); }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }

        .checkout-card {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .order-summary table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-summary th,
        .order-summary td {
            text-align: left;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--border);
        }

        .order-summary th {
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .total-row td {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .address-box {
            background: #f5f3ff;
            border: 2px dashed var(--primary);
            border-radius: 12px;
            padding: 1.5rem;
            position: relative;
        }

        .address-box.set {
            background: var(--white);
            border: 1px solid var(--border);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.2rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            justify-content: center;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .btn-outline { background: transparent; border: 1.5px solid var(--primary); color: var(--primary); }
        .btn-outline:hover { background: #f5f3ff; }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: white;
            padding: 2.5rem;
            border-radius: 24px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: modalIn 0.3s ease-out;
        }

        @keyframes modalIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 0.85rem; font-weight: 600; color: var(--text-light); }
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-family: inherit;
            transition: 0.3s;
        }
        .form-group input:focus { outline: none; border-color: var(--primary); background: #f5f3ff; }

        .full-width { grid-column: span 2; }

        .autofill-btn {
            background: #f5f3ff;
            color: var(--primary);
            border: 1px solid var(--primary);
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        @media(max-width:768px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .modal-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="page-title-area">
            <h1>Checkout</h1>
            <a href="cart.php" class="btn-back">
                <i class='bx bx-arrow-back'></i> Back to Cart
            </a>
        </div>

        <div class="checkout-grid">
            <div style="display:flex; flex-direction:column; gap:20px;">
                <!-- Delivery Address Section -->
                <div class="checkout-card">
                    <div class="section-title">
                        <i class='bx bxs-map-pin'></i> Delivery Address
                    </div>
                    
                    <?php if ($has_address): $addr = $_SESSION['delivery_details']; ?>
                        <div class="address-box set">
                            <h3 style="margin-top:0;"><?= htmlspecialchars($addr['name']) ?></h3>
                            <p style="margin:5px 0; color:var(--text-light);">
                                <?= htmlspecialchars($addr['house_no']) ?>, <?= htmlspecialchars($addr['apartment_society']) ?><br>
                                <?= htmlspecialchars($addr['street']) ?>, <?= htmlspecialchars($addr['area']) ?><br>
                                <?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['state']) ?> - <?= htmlspecialchars($addr['pincode']) ?><br>
                                <strong>Phone:</strong> <?= htmlspecialchars($addr['phone']) ?>
                            </p>
                            <button class="btn btn-outline" style="margin-top:15px; font-size:0.85rem; padding:5px 15px;" onclick="showAddressModal()">
                                <i class='bx bx-edit-alt'></i> Change Address
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="address-box" style="text-align:center;">
                            <p>No delivery address selected</p>
                            <button class="btn btn-primary" onclick="showAddressModal()">
                                <i class='bx bx-plus'></i> Add Delivery Address
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order Summary Section -->
                <div class="checkout-card">
                    <h2 style="margin-top:0;">Order Summary</h2>
                    <div class="order-summary">
                        <table>
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $b): ?>
                                    <tr>
                                        <td style="font-weight:500;"><?= htmlspecialchars($b['book_name']) ?></td>
                                        <td><?= $b['quantity_ordered'] ?></td>
                                        <td>₹<?= number_format($b['price'], 2) ?></td>
                                        <td>₹<?= number_format($b['total_price'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="3">Grand Total</td>
                                    <td>₹<?= number_format($grandTotal, 2) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="checkout-card" style="height:fit-content; position:sticky; top:20px;">
                <h3 style="margin-top:0; border-bottom:1px solid var(--border); padding-bottom:15px;">Payment</h3>
                <p style="font-size:0.9rem; color:var(--text-light); margin-bottom:20px;">
                    Select your preferred payment method to complete the order.
                </p>

                <form action="payment_router.php" method="POST" id="paymentForm">
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <button type="submit" name="payment_method" value="online" class="btn btn-primary" <?= !$has_address ? 'disabled title="Please add address first"' : '' ?>>
                            <i class='bx bx-credit-card'></i> Pay Online (Online Payment)
                        </button>

                        <button type="submit" name="payment_method" value="cod" class="btn btn-outline" <?= !$has_address ? 'disabled title="Please add address first"' : '' ?>>
                            <i class='bx bx-money'></i> Cash on Delivery
                        </button>
                    </div>
                </form>

                <div style="margin-top:25px; pt:15px; border-top:1px solid var(--border);">
                    <form action="invoice.php" method="POST" target="_blank">
                        <input type="hidden" name="books" value='<?= json_encode($books) ?>'>
                        <input type="hidden" name="grand_total" value="<?= $grandTotal ?>">
                        <button type="submit" class="btn-back" style="background:none; border:none; cursor:pointer; width:100%; justify-content:center; margin-top:15px;">
                            <i class='bx bx-file-blank'></i> Preview Invoice
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Address Modal -->
    <div class="modal-overlay" id="addressModal">
        <div class="modal-content">
            <h2 style="margin-top:0;">Delivery Details</h2>
            <p style="color:var(--text-light); font-size:0.9rem; margin-bottom:1.5rem;">Please provide your detailed shipping address.</p>
            
            <button class="autofill-btn" onclick="autofillFromProfile()">
                <i class='bx bx-user-circle'></i> Use Details from Profile
            </button>

            <form id="addressForm">
                <div class="modal-grid">
                    <div class="form-group full-width">
                        <label>Receiver Name</label>
                        <input type="text" name="shipping_name" id="shipping_name" required value="<?= htmlspecialchars($userProfile['shipping_name'] ?? $userProfile['name']) ?>">
                    </div>
                    <div class="form-group full-width">
                        <label>Mobile Number</label>
                        <input type="text" name="shipping_phone" id="shipping_phone" required value="<?= htmlspecialchars($userProfile['shipping_phone'] ?? $userProfile['phone']) ?>">
                    </div>
                    <div class="form-group">
                        <label>House/Flat No.</label>
                        <input type="text" name="house_no" id="house_no" required value="<?= htmlspecialchars($userProfile['house_no'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Apartment / Society Name</label>
                        <input type="text" name="apartment_society" id="apartment_society" required value="<?= htmlspecialchars($userProfile['apartment_society'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Street</label>
                         <input type="text" name="street" id="street" required value="<?= htmlspecialchars($userProfile['street'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" name="area" id="area" required value="<?= htmlspecialchars($userProfile['area'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Landmark</label>
                        <input type="text" name="landmark" id="landmark" required value="<?= htmlspecialchars($userProfile['landmark'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="position: relative;">
                        <label>Pincode</label>
                        <input type="text" name="pincode" id="pincode" pattern="\d{6}" maxlength="6" title="Please enter a 6 digit pincode" required value="<?= htmlspecialchars($userProfile['pincode'] ?? '') ?>">
                        <small id="chk-pincode-error" style="color: #e63946; display: none; font-size: 0.75rem; position: absolute; bottom: -18px; left: 0;">Must be exactly 6 digits</small>
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" id="city" required value="<?= htmlspecialchars($userProfile['city'] ?? '') ?>">
                    </div>
                    <div class="form-group full-width">
                        <label>State</label>
                        <input type="text" name="state" id="state" required value="<?= htmlspecialchars($userProfile['state'] ?? '') ?>">
                    </div>
                    <div class="form-group full-width" style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" name="save_to_profile" style="width:auto; cursor:pointer;">
                        <label style="margin:0; cursor:pointer;">Save these details to my profile</label>
                    </div>
                </div>
                
                <div style="margin-top:20px; display:flex; gap:10px;">
                    <button type="button" class="btn btn-primary" style="flex:1;" onclick="saveAddress()">Confirm Address</button>
                    <button type="button" class="btn btn-outline" style="width:100px;" onclick="hideAddressModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('addressModal');
        
        function showAddressModal() { modal.style.display = 'flex'; }
        function hideAddressModal() { modal.style.display = 'none'; }

        // Automatically show modal if address not set
        <?php if (!$has_address): ?>
            window.onload = showAddressModal;
        <?php endif; ?>

        function autofillFromProfile() {
            // Profile details are already echoed in the PHP value attributes
            // But we can reset if they've changed them
            document.getElementById('shipping_name').value = "<?= addslashes($userProfile['shipping_name'] ?? $userProfile['name']) ?>";
            document.getElementById('shipping_phone').value = "<?= addslashes($userProfile['shipping_phone'] ?? $userProfile['phone']) ?>";
            document.getElementById('house_no').value = "<?= addslashes($userProfile['house_no'] ?? '') ?>";
            document.getElementById('apartment_society').value = "<?= addslashes($userProfile['apartment_society'] ?? '') ?>";
            document.getElementById('street').value = "<?= addslashes($userProfile['street'] ?? '') ?>";
            document.getElementById('area').value = "<?= addslashes($userProfile['area'] ?? '') ?>";
            document.getElementById('landmark').value = "<?= addslashes($userProfile['landmark'] ?? '') ?>";
            document.getElementById('pincode').value = "<?= addslashes($userProfile['pincode'] ?? '') ?>";
            document.getElementById('city').value = "<?= addslashes($userProfile['city'] ?? '') ?>";
            document.getElementById('state').value = "<?= addslashes($userProfile['state'] ?? '') ?>";
        }

        function saveAddress() {
            const form = document.getElementById('addressForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            formData.append('save_address', '1');

            fetch('checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok');
                return res.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error occurred'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred. Please check if all fields are correct.');
            });
        }

        // Real-time Pincode Validation
        document.getElementById('pincode')?.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            let errorMsg = document.getElementById('chk-pincode-error');
            if (errorMsg) {
                if (this.value.length > 0 && this.value.length < 6) {
                    errorMsg.style.display = 'block';
                    this.style.borderColor = '#e63946';
                } else {
                    errorMsg.style.display = 'none';
                    this.style.borderColor = '';
                }
            }
        });
    </script>
</body>

</html>