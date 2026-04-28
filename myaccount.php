<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$isLoggedIn = true;
$userName = $_SESSION['user_name'] ?? 'User';
$user_id = $_SESSION['user_id'];

// --- NEW: FETCH BOOK STATUS UPDATES ---
// Get books updated AFTER the user last viewed this page
$update_check = $conn->prepare("
    SELECT book_name, status 
    FROM books 
    WHERE user_id = ? 
    AND status != 'pending' 
    AND status_updated_at > (SELECT last_account_view FROM users WHERE id = ?)
");

if ($update_check) {
    $update_check->bind_param("ii", $user_id, $user_id);
    $update_check->execute();
    $recent_updates = $update_check->get_result();
} else {
    // Falls back to empty result if migration hasn't been run
    $recent_updates = false;
}

// RESET NOTIFICATION: Update the view timer so the red dot disappears
$conn->query("UPDATE users SET last_account_view = NOW() WHERE id = $user_id");

// Handle Address Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_address'])) {
    $house_no = trim($_POST['house_no']);
    $apartment_society = trim($_POST['apartment_society']);
    $street = trim($_POST['street']);
    $area = trim($_POST['area']);
    $landmark = trim($_POST['landmark']);
    $pincode = trim($_POST['pincode']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);

    if (!preg_match('/^\d{6}$/', $pincode)) {
        $_SESSION['flash_message'] = "Pincode must be exactly 6 digits.";
        $_SESSION['flash_type'] = "danger";
        header("Location: myaccount.php");
        exit();
    }

    $update = $conn->prepare("UPDATE users SET house_no=?, apartment_society=?, street=?, area=?, landmark=?, pincode=?, city=?, state=? WHERE id=?");
    $update->bind_param("ssssssssi", $house_no, $apartment_society, $street, $area, $landmark, $pincode, $city, $state, $user_id);
    
    if ($update->execute()) {
        $_SESSION['flash_message'] = "Address updated successfully!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Failed to update address.";
        $_SESSION['flash_type'] = "danger";
    }
    header("Location: myaccount.php");
    exit();
}

// Fetch User Info
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch User's Books
$book_stmt = $conn->prepare("SELECT * FROM books WHERE user_id = ? ORDER BY id DESC");
$book_stmt->bind_param("i", $user_id);
$book_stmt->execute();
$my_books = $book_stmt->get_result();
$book_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Alpha Book</title>

    <link rel="stylesheet" href="style.css">

    <style>
        /* Notification Message Styling */
        .flash-container {
            margin-bottom: 2rem;
            animation: fadeInDown 0.5s ease;
        }

        .flash-message {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .flash-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .flash-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 2.5rem;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            animation: modalIn 0.3s ease-out;
        }

        @keyframes modalIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text-dark);
        }

        .close {
            font-size: 2rem;
            cursor: pointer;
            color: var(--text-light);
            line-height: 1;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
        }

        .form-group {
            margin-bottom: 0.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-light);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            transition: 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .full-width {
            grid-column: span 2;
        }

        .modal-footer {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }

        .btn-save {
            background: var(--primary);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            flex: 1;
            transition: 0.3s;
        }

        .btn-save:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <div class="container" style="padding-top: 4rem; padding-bottom: 4rem;">

        <?php if ($recent_updates && $recent_updates->num_rows > 0): ?>
            <div class="flash-container">
                <?php while ($update = $recent_updates->fetch_assoc()): ?>
                    <div class="flash-message <?= $update['status'] == 'approved' ? 'flash-success' : 'flash-danger'; ?>">
                        <i class='bx <?= $update['status'] == 'approved' ? 'bx-check-circle' : 'bx-x-circle'; ?>'
                            style="font-size: 1.5rem;"></i>
                        <span>
                            Your book <strong>"<?= htmlspecialchars($update['book_name']) ?>"</strong>
                            has been <strong><?= strtoupper($update['status']) ?></strong> by the Admin.
                        </span>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-container" id="flashMsg">
                <div class="flash-message flash-<?= $_SESSION['flash_type']; ?>">
                    <i class='bx bx-check-circle' style="font-size: 1.5rem;"></i>
                    <?= $_SESSION['flash_message']; ?>
                </div>
            </div>
            <?php unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']); ?>
        <?php endif; ?>

        <div style="display: flex; gap: 2rem; align-items: flex-start; margin-bottom: 3rem; flex-wrap: wrap;">
            <div style="flex-shrink: 0;">
                <img src="pro.jpeg" alt="Profile"
                    style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary); padding: 4px;">
            </div>

            <div style="flex: 1; min-width: 250px;">
                <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem; color: var(--text-dark); line-height: 1.2;">
                    <?= htmlspecialchars($user['name']) ?>
                </h1>
                <p style="color: var(--text-light); font-size: 1.1rem; margin-bottom: 1.5rem;">
                    <?= htmlspecialchars($user['email']) ?>
                </p>

                <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <i class='bx bx-phone' style="font-size: 1.4rem; color: var(--primary);"></i>
                        <div>
                            <small style="display: block; color: var(--text-light); font-size: 0.8rem;">Phone</small>
                            <span
                                style="font-weight: 500; font-size: 1rem;"><?= htmlspecialchars($user['phone']) ?></span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.75rem; align-items: start;">
                        <i class='bx bx-map' style="font-size: 1.4rem; color: var(--primary); margin-top: 2px;"></i>
                        <div>
                            <small style="display: flex; align-items: center; justify-content: space-between; color: var(--text-light); font-size: 0.8rem; width: 100%;">
                                Address
                                <button onclick="openAddressModal()" style="background:none; border:none; color:var(--primary); font-size:0.75rem; font-weight:600; cursor:pointer; padding:0; margin-left:10px;">
                                    <i class='bx bx-edit-alt'></i> Edit
                                </button>
                            </small>
                            <span style="font-weight: 500; font-size: 1rem; line-height: 1.5; display: block; margin-top: 4px;">
                                <?php if (!empty($user['house_no'])): ?>
                                    <b><?= htmlspecialchars($user['house_no']) ?></b>, <?= htmlspecialchars($user['apartment_society'] ?? '') ?><br>
                                    <?= htmlspecialchars($user['street']) ?>, <?= htmlspecialchars($user['area']) ?><br>
                                    <?= htmlspecialchars($user['landmark']) ?><br>
                                    <?= htmlspecialchars($user['city']) ?>, <?= htmlspecialchars($user['state']) ?> - <?= htmlspecialchars($user['pincode']) ?>
                                <?php else: ?>
                                    No primary address set
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border-color); margin-bottom: 3rem;">

        <div>
            <h2 style="font-size: 1.75rem; margin-bottom: 2rem; color: var(--text-dark);">My Uploaded Books</h2>

            <?php if ($my_books->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column;">
                    <?php while ($book = $my_books->fetch_assoc()): ?>
                        <div class="book-list-item">
                            <img src="<?= htmlspecialchars($book['cover_image']) ?: 'default_cover.png' ?>"
                                alt="<?= htmlspecialchars($book['book_name']) ?>" class="book-list-img">

                            <div class="book-list-content">
                                <div class="book-list-header">
                                    <div>
                                        <div
                                            style="font-size: 0.85rem; color: var(--primary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">
                                            <?= htmlspecialchars($book['category']) ?>
                                        </div>
                                        <h3 class="book-list-title"><?= htmlspecialchars($book['book_name']) ?></h3>
                                        <div class="book-list-meta">by <span
                                                style="color: var(--text-dark); font-weight: 500;"><?= htmlspecialchars($book['author_name']) ?></span>
                                        </div>
                                    </div>

                                    <span
                                        class="badge <?= $book['status'] == 'approved' ? 'badge-success' : ($book['status'] == 'rejected' ? 'badge-danger' : 'badge-primary') ?>">
                                        <?= ucfirst($book['status']) ?>
                                    </span>
                                </div>

                                <div class="book-list-desc"><?= htmlspecialchars($book['description']) ?></div>

                                <div class="book-list-footer">
                                    <div style="display: flex; gap: 2rem; align-items: center;">
                                        <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary);">
                                            ₹<?= number_format($book['price'], 2) ?></div>
                                        <div style="font-size: 0.9rem; color: var(--text-light);">Quantity: <strong
                                                style="color: var(--text-dark);"><?= $book['quantity'] ?></strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div
                    style="text-align: left; padding: 4rem 2rem; background: #f8fafc; border-radius: 12px; border: 2px dashed #e2e8f0; display: flex; align-items: center; gap: 2rem;">
                    <i class='bx bx-library' style="font-size: 4rem; color: #cbd5e1;"></i>
                    <div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: var(--text-dark);">No books uploaded
                            yet</h3>
                        <p style="color: var(--text-light); margin-bottom: 0;">Your uploaded books will appear here with
                            full details and status tracking.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Address Modal -->
    <div id="addressModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Address</h2>
                <span class="close" onclick="closeAddressModal()">&times;</span>
            </div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>House/Flat No.</label>
                        <input type="text" name="house_no" value="<?= htmlspecialchars($user['house_no'] ?? '') ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Apartment / Society Name</label>
                        <input type="text" name="apartment_society" value="<?= htmlspecialchars($user['apartment_society'] ?? '') ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Street</label>
                        <input type="text" name="street" value="<?= htmlspecialchars($user['street'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" name="area" value="<?= htmlspecialchars($user['area'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Landmark</label>
                        <input type="text" name="landmark" value="<?= htmlspecialchars($user['landmark'] ?? '') ?>"
                            required>
                    </div>
                    <div class="form-group" style="position: relative;">
                        <label>Pincode</label>
                        <input type="text" name="pincode" id="acc-pincode" value="<?= htmlspecialchars($user['pincode'] ?? '') ?>"
                            pattern="\d{6}" maxlength="6" title="Please enter a 6 digit pincode" required>
                        <small id="acc-pincode-error" style="color: #e63946; display: none; font-size: 0.75rem; position: absolute; bottom: -18px; left: 0;">Must be exactly 6 digits</small>
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? '') ?>" required>
                    </div>
                    <div class="form-group full-width">
                        <label>State</label>
                        <input type="text" name="state" value="<?= htmlspecialchars($user['state'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update_address" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("addressModal");

        function openAddressModal() {
            modal.style.display = "flex";
        }

        function closeAddressModal() {
            modal.style.display = "none";
        }

        // Close when clicking outside
        window.onclick = function (event) {
            if (event.target == modal) {
                closeAddressModal();
            }
        }

        function toggleAccount() {
            document.getElementById("accountMenu").classList.toggle("show");
        }

        // Auto-hide session flash messages
        setTimeout(() => {
            const msg = document.getElementById('flashMsg');
            if (msg) {
                msg.style.transition = "opacity 0.5s ease";
                msg.style.opacity = "0";
                setTimeout(() => msg.remove(), 500);
            }
        }, 5000);

        // Real-time Pincode Validation
        document.getElementById('acc-pincode')?.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            let errorMsg = document.getElementById('acc-pincode-error');
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