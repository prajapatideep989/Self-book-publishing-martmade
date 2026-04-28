<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$isLoggedIn = true;
$userName = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Book - Alpha Book</title>

    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body
    style="background: linear-gradient(135deg, #eef2ff, #f3f4f6); min-height: 100vh; display: flex; flex-direction: column;">

    <!-- ===== NAVBAR ===== -->
    <?php include 'header.php'; ?>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="container" style="padding-top: 6rem; padding-bottom: 4rem;">

        <div style="margin-bottom: 2rem; text-align: left;">
            <h2 style="font-size: 2rem; color: var(--text-dark); margin-bottom: 0.5rem;">Publish Your Book</h2>
            <p style="color: var(--text-light); font-size: 1rem;">Fill in the details below to share your story.</p>
        </div>

        <form action="publish_book.php" method="post" enctype="multipart/form-data"
            style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 3rem; align-items: start;">

            <!-- LEFT COLUMN: Book Information -->
            <div class="form-section-left">
                <h3
                    style="font-size: 1.1rem; margin-bottom: 1.5rem; color: var(--primary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    Book Information
                </h3>

                <div class="form-group input-wrapper">
                    <i class='bx bx-book form-icon'></i>
                    <input type="text" name="book_name" class="form-control" placeholder="Book Title" required>
                </div>

                <div class="form-group input-wrapper">
                    <i class='bx bx-category form-icon'></i>
                    <select name="category" class="form-control" required style="cursor: pointer;">
                        <option value="" disabled selected>Select Category</option>
                        <option value="Fiction">Fiction</option>
                        <option value="Education">Education</option>
                        <option value="Technology">Technology</option>
                        <option value="Biography">Biography</option>
                        <option value="Comics">Comics</option>
                        <option value="Romance">Romance</option>
                        <option value="Adventure">Adventure</option>
                    </select>
                </div>

                <div class="form-group input-wrapper">
                    <i class='bx bx-user-pin form-icon'></i>
                    <input type="text" name="author_name" class="form-control" placeholder="Author Name" required>
                </div>

                <div class="form-group input-wrapper">
                    <i class='bx bx-text form-icon' style="top: 1.5rem;"></i>
                    <textarea name="description" class="form-control" rows="6" placeholder="Book Description"
                        style="resize: vertical; min-height: 120px;"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group input-wrapper">
                        <i class='bx bx-rupee form-icon'></i>
                        <input type="number" name="price" class="form-control" placeholder="Price" min="0" required>
                    </div>

                    <div class="form-group input-wrapper">
                        <i class='bx bx-layer form-icon'></i>
                        <input type="number" name="quantity" class="form-control" placeholder="Quantity" min="1"
                            required>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Images -->
            <div class="form-section-right"
                style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
                <h3
                    style="font-size: 1.1rem; margin-bottom: 1.5rem; color: var(--primary); border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    Book Images
                </h3>

                <div class="form-group">
                    <label
                        style="font-size: 0.9rem; margin-bottom: 0.5rem; display: block; color: var(--text-dark); font-weight: 500;">Cover
                        Image <span style="color: var(--danger);">*</span></label>
                    <input type="file" name="cover_image" class="form-control" required style="padding: 0.5rem;">
                </div>

                <div class="form-group">
                    <label
                        style="font-size: 0.9rem; margin-bottom: 0.5rem; display: block; color: var(--text-dark); font-weight: 500;">Index
                        Page <span style="color: var(--danger);">*</span></label>
                    <input type="file" name="image1" class="form-control" required style="padding: 0.5rem;">
                </div>

                <div class="form-group">
                    <label
                        style="font-size: 0.9rem; margin-bottom: 0.5rem; display: block; color: var(--text-dark); font-weight: 500;">First
                        Page <span style="color: var(--danger);">*</span></label>
                    <input type="file" name="image2" class="form-control" required style="padding: 0.5rem;">
                </div>

                <div class="form-group">
                    <label
                        style="font-size: 0.9rem; margin-bottom: 0.5rem; display: block; color: var(--text-dark); font-weight: 500;">Description
                        Page <span style="color: var(--danger);">*</span></label>
                    <input type="file" name="image3" class="form-control" required style="padding: 0.5rem;">
                </div>

                <button type="submit" class="btn btn-primary"
                    style="width: 100%; margin-top: 1.5rem; padding: 1rem; font-size: 1rem;">
                    <i class='bx bx-rocket'></i> Publish Book
                </button>

                <!-- Terms & Conditions Section -->
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <input type="checkbox" id="terms" required style="margin-top: 4px; cursor: pointer;">
                        <label for="terms"
                            style="font-size: 0.85rem; color: var(--text-light); line-height: 1.5; cursor: pointer;">
                            I agree to the <span style="color: var(--primary); font-weight: 600;">Terms &
                                Conditions</span>.
                        </label>
                    </div>
                    <div
                        style="margin-top: 1rem; background: #f8fafc; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0;">
                        <p style="font-size: 0.75rem; color: #64748b; margin: 0; line-height: 1.6;">
                            <i class='bx bx-info-circle' style="color: var(--primary); margin-right: 4px;"></i>
                            <strong>Price Policy:</strong> The book price listed above must be <strong>inclusive of
                                GST</strong> and all applicable <strong>publishing charges</strong>.
                            Books are subject to review and approval by the admin before they are live on the store.
                            Admin take 10% commission on very order.<b>" The publisher is responsible for ensuring the
                                prompt dispatch and secure delivery of books to the buyer’s registered address."</b>
                        </p>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <!-- Mobile Responsive Style for this page -->
    <style>
        @media(max-width: 900px) {
            form {
                grid-template-columns: 1fr !important;
                gap: 2rem !important;
            }
        }
    </style>

    <script>
        function toggleAccount() {
            document.getElementById("accountMenu").classList.toggle("show");
        }

        window.onclick = function (e) {
            if (!e.target.closest('.user-dropdown')) {
                var d = document.getElementById('accountMenu');
                if (d && d.classList.contains('show')) d.classList.remove('show');
            }
        }
    </script>
</body>

</html>