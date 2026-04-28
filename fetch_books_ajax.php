<?php
include 'db.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT * FROM books WHERE status='approved'";
$params = [];
$types = "";

if ($search) {
    $sql .= " AND (book_name LIKE ? OR author_name LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $types .= "ss";
}
if ($category) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0):
    $delay = 0;
    while ($row = $result->fetch_assoc()):
        $delay += 100; // 100ms increment for each card
        ?>
        <div class="book-card-unique" style="animation-delay: <?= $delay ?>ms;">
            <span class="badge <?= $row['quantity'] > 0 ? 'badge-stock' : 'badge-out' ?>">
                <?= $row['quantity'] > 0 ? 'In Stock' : 'Sold Out' ?>
            </span>

            <div class="book-cover-wrapper">
                <img src="<?= htmlspecialchars($row['cover_image']) ?: 'default_cover.png' ?>"
                    alt="<?= htmlspecialchars($row['book_name']) ?>">
                <div class="book-overlay">
                    <a href="book_details.php?id=<?= $row['id'] ?>" class="view-btn">View Details</a>
                </div>
            </div>

            <div class="book-details-unique">
                <div class="book-category">
                    <?= htmlspecialchars($row['category']) ?>
                </div>
                <h3 class="book-title" title="<?= htmlspecialchars($row['book_name']) ?>">
                    <?= htmlspecialchars($row['book_name']) ?>
                </h3>
                <div class="book-author">by
                    <?= htmlspecialchars($row['author_name']) ?>
                </div>

                <div class="book-footer">
                    <div class="book-price">
                        <span>₹</span><?= number_format($row['price'], 2) ?>
                    </div>

                    <form method="POST" action="add_to_cart.php" class="add-to-cart-form">
                        <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                        <button type="button" class="btn-add-unique" title="Add to Cart" onclick="addToCart(this)"
                            <?= $row['quantity'] <= 0 ? 'disabled' : '' ?>>
                            <i class='bx bx-plus'></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endwhile;
else: ?>
    <div style="grid-column: 1/-1; text-align: center; padding: 100px 20px;">
        <div
            style="background: #fff; display: inline-block; padding: 40px; border-radius: 30px; box-shadow: var(--card-shadow);">
            <i class='bx bx-search-alt'
                style="font-size: 4rem; color: var(--primary); opacity: 0.2; margin-bottom: 20px; display: block;"></i>
            <h3 style="font-size: 1.5rem; margin-bottom: 10px; color: var(--text-main);">No matches found</h3>
            <p style="color: var(--text-muted); margin-bottom: 25px;">We couldn't find any books that match your current
                filters.</p>
            <button
                onclick="document.getElementById('ajaxSearch').value=''; document.getElementById('ajaxCategory').value=''; applyFilters();"
                style="background: var(--primary); color: #fff; border: none; padding: 12px 30px; border-radius: 100px; font-weight: 600; cursor: pointer; transition: 0.3s;">
                Clear All Filters
            </button>
        </div>
    </div>
<?php endif; ?>