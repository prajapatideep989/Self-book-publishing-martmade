<?php
include 'db.php';

// 1. Session must start before ANY HTML/Output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Data Fetching Logic (Move this to the top)
if (!isset($_GET['id'])) {
    header("Location: book.php");
    exit;
}

$book_id = (int) $_GET['id'];

// Fetch Book Details
$sql = "SELECT books.*, users.name AS seller_name
        FROM books
        JOIN users ON books.user_id = users.id
        WHERE books.id = ? AND books.status='approved'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Book not found";
    exit;
}

$book = $result->fetch_assoc();
$qty = (int) $book['quantity'];

/* =========================
   ADD REVIEW LOGIC
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $rating = (int) $_POST['rating'];
    $review = trim($_POST['review']);
    $user_id = $_SESSION['user_id'];

    if ($rating >= 1 && $rating <= 5 && !empty($review)) {
        $stmt_ins = $conn->prepare("INSERT INTO books_reviews (book_id, user_id, rating, review) VALUES (?, ?, ?, ?)");
        $stmt_ins->bind_param("iiis", $book_id, $user_id, $rating, $review);
        $stmt_ins->execute();
    }
    header("Location: book_details.php?id=" . $book_id);
    exit;
}

/* =========================
   FETCH REVIEWS LOGIC
   ========================= */
$reviews = [];
$avgRating = 0;
$stmt_rev = $conn->prepare("SELECT r.*, u.name FROM books_reviews r JOIN users u ON r.user_id = u.id WHERE r.book_id = ? ORDER BY r.created_at DESC");
$stmt_rev->bind_param("i", $book_id);
$stmt_rev->execute();
$res_rev = $stmt_rev->get_result();

$totalRating = 0;
while ($row = $res_rev->fetch_assoc()) {
    $reviews[] = $row;
    $totalRating += $row['rating'];
}
if (count($reviews) > 0) {
    $avgRating = round($totalRating / count($reviews), 1);
}

// 3. Now include the header (only after all logic/redirects are done)
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($book['book_name']) ?> | Alpha Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --star: #f59e0b;
            --light-bg: #f8fafc;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Poppins', sans-serif;
            color: #334155;
        }

        .details-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Book Detail Section */
        .book-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 3rem;
            background: #fff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
        }

        /* Carousel Styles */
        .carousel {
            position: relative;
            border-radius: 0.75rem;
            overflow: hidden;
            background: #f1f5f9;
            height: 500px;
        }

        .carousel-track {
            display: flex;
            height: 100%;
            transition: transform 0.5s ease;
        }

        .carousel-track img {
            min-width: 100%;
            object-fit: contain;
        }

        .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .prev-btn {
            left: 10px;
        }

        .next-btn {
            right: 10px;
        }

        /* Review Card Styles */
        .review-section {
            margin-top: 3rem;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .review-form-card {
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            height: fit-content;
        }

        .review-item {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border-left: 4px solid var(--primary);
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stars {
            color: var(--star);
            margin-bottom: 0.5rem;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 900px) {

            .book-grid,
            .review-section {
                grid-template-columns: 1fr;
            }

            .carousel {
                height: 350px;
            }
        }
    </style>
</head>

<body>

    <?php
    $hide_loader = true;
    include 'header.php';
    ?>

    <div class="details-container">
        <div class="book-grid">
            <div class="carousel">
                <div class="carousel-track" id="track">
                    <img src="<?= htmlspecialchars($book['cover_image']) ?: 'default_cover.png' ?>">
                    <?php foreach (['image1', 'image2', 'image3'] as $imgField): ?>
                        <?php if (!empty($book[$imgField])): ?>
                            <img src="<?= htmlspecialchars($book[$imgField]) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <button class="nav-btn prev-btn" onclick="move( -1)"><i class='bx bx-chevron-left'></i></button>
                <button class="nav-btn next-btn" onclick="move(1)"><i class='bx bx-chevron-right'></i></button>
            </div>

            <div class="book-content">
                <h1 style="font-size: 2.5rem; line-height: 1.1;"><?= htmlspecialchars($book['book_name']) ?></h1>
                <p style="color: #64748b; margin: 1rem 0;">By
                    <strong><?= htmlspecialchars($book['author_name']) ?></strong> | Seller:
                    <?= htmlspecialchars($book['seller_name']) ?>
                </p>

                <div style="display:flex; align-items:center; gap:10px; margin-bottom:1.5rem;">
                    <div class="stars" style="font-size:1.2rem;">
                        <?= str_repeat('<i class="bx bxs-star"></i>', floor($avgRating)) ?>
                        <?= ($avgRating - floor($avgRating) > 0) ? '<i class="bx bxs-star-half"></i>' : '' ?>
                    </div>
                    <span style="font-weight:600;"><?= $avgRating ?> / 5</span>
                    <span style="color:#94a3b8">(<?= count($reviews) ?> reviews)</span>
                </div>

                <div style="font-size: 2.2rem; font-weight: 800; color: var(--primary); margin-bottom: 1rem;">
                    ₹<?= number_format($book['price'], 2) ?></div>

                <span class="badge <?= $qty > 0 ? 'badge-success' : 'badge-danger' ?>">
                    <i class='bx <?= $qty > 0 ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
                    <?= $qty > 0 ? 'Available: ' . $qty : 'Out of Stock' ?>
                </span>

                <div style="margin: 2rem 0; line-height: 1.7; color: #475569;">
                    <h4 style="margin-bottom: 0.5rem; color: #1e293b;">Description</h4>
                    <?= nl2br(htmlspecialchars($book['description'])) ?>
                </div>

                <div style="display:flex; gap: 1rem;">
                    <form action="add_to_cart.php" method="POST" style="flex: 1;" class="add-to-cart-form">
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <button type="button" class="btn btn-primary"
                            style="width:100%; padding: 1rem; border-radius: 0.5rem;" onclick="addToCart(this)" <?= $qty <= 0 ? 'disabled' : '' ?>>
                            <i class='bx bx-cart-add'></i> Add to Cart
                        </button>
                    </form>
                    <a href="book.php" class="btn btn-outline"
                        style="padding: 1rem 2rem; border-radius: 0.5rem;">Back</a>
                </div>
            </div>
        </div>

        <div class="review-section">
            <div class="review-form-card">
                <h3 style="margin-bottom:1.5rem">Write a Review</h3>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="post">
                        <input type="hidden" name="add_review" value="1">
                        <label>Rating</label>
                        <select name="rating" required
                            style="width:100%; padding:0.8rem; margin: 0.5rem 0 1.5rem; border-radius:0.5rem; border:1px solid #cbd5e1;">
                            <option value="5">⭐⭐⭐⭐⭐ (5/5)</option>
                            <option value="4">⭐⭐⭐⭐ (4/5)</option>
                            <option value="3">⭐⭐⭐ (3/5)</option>
                            <option value="2">⭐⭐ (2/5)</option>
                            <option value="1">⭐ (1/5)</option>
                        </select>
                        <label>Your Review</label>
                        <textarea name="review" required rows="4"
                            style="width:100%; padding:1rem; border-radius:0.5rem; border:1px solid #cbd5e1; margin-top:0.5rem;"></textarea>
                        <button class="btn btn-primary" style="width:100%; margin-top: 1rem;">Post Review</button>
                    </form>
                <?php else: ?>
                    <p>Please <a href="login.php" style="color:var(--primary); font-weight:600;">Login</a> to share your
                        review.</p>
                <?php endif; ?>
            </div>

            <div class="reviews-display">
                <h3 style="margin-bottom:1.5rem">Customer Reviews</h3>
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $r): ?>
                        <div class="review-item">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <strong><?= htmlspecialchars($r['name']) ?></strong>
                                <small style="color:#94a3b8"><?= date('M d, Y', strtotime($r['created_at'])) ?></small>
                            </div>
                            <div class="stars" style="font-size:0.8rem; margin-top:0.2rem;">
                                <?= str_repeat('<i class="bx bxs-star"></i>', $r['rating']) ?>
                            </div>
                            <p style="margin-top:0.75rem; color:#475569;"><?= nl2br(htmlspecialchars($r['review'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align:center; padding:3rem; background:#fff; border-radius:1rem;">
                        <i class='bx bx-message-dots' style="font-size:3rem; color:#cbd5e1;"></i>
                        <p style="color:#94a3b8; margin-top:1rem;">No reviews yet for this book.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let idx = 0;
        const track = document.getElementById('track');
        const slides = track.children.length;

        function move(dir) {
            idx = (idx + dir + slides) % slides;
            track.style.transform = `translateX(-${idx * 100}%)`;
        }
    </script>
</body>

</html>