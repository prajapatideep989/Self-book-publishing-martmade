<?php
include 'db.php';

echo "<h1>Database Update Status</h1>";

// Check if columns exist
$check = $conn->query("SHOW COLUMNS FROM books LIKE 'image1'");
if ($check->num_rows > 0) {
    echo "<p style='color:green;'>Columns already exist. No changes needed.</p>";
} else {
    // Add columns
    $sql = "ALTER TABLE books 
            ADD COLUMN image1 VARCHAR(255) DEFAULT NULL AFTER cover_image,
            ADD COLUMN image2 VARCHAR(255) DEFAULT NULL AFTER image1,
            ADD COLUMN image3 VARCHAR(255) DEFAULT NULL AFTER image2";

    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>Success! Added image1, image2, image3 columns.</p>";
    } else {
        echo "<p style='color:red;'>Error adding columns: " . $conn->error . "</p>";
    }
}
$conn->close();
?>
<div style="margin-top:20px;">
    <a href="upload_book.php">Go back to Upload Book</a>
</div>