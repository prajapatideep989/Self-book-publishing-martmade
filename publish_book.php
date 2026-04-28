<?php
include 'db.php';
session_start();

/* CHECK LOGIN */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION['user_id'];
    $book_name = trim($_POST['book_name']);
    $category = trim($_POST['category']);
    $author_name = trim($_POST['author_name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    /* IMAGE UPLOAD FUNCTION */
    function uploadImage($fileKey)
    {
        if (!empty($_FILES[$fileKey]['name'])) {
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $filename = time() . "_" . uniqid() . "_" . basename($_FILES[$fileKey]['name']);
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
                return $targetPath;
            }
        }
        return null;
    }

    $cover_image = uploadImage('cover_image');
    $image1 = uploadImage('image1');
    $image2 = uploadImage('image2');
    $image3 = uploadImage('image3');

    /* VALIDATE ALL IMAGES UPLOADED */
    if (!$cover_image || !$image1 || !$image2 || !$image3) {
        header("Location: upload_book.php?msg=All 4 images are required");
        exit();
    }

    /* INSERT QUERY */
    $sql = "INSERT INTO books 
        (user_id, book_name, category, author_name, description, cover_image, image1, image2, image3, price, quantity, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

    $stmt = $conn->prepare($sql);

    // DEBUGGING: Check if prepare failed
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error . " (SQL: $sql)");
    }
    $stmt->bind_param(
        "issssssssdi",
        $user_id,
        $book_name,
        $category,
        $author_name,
        $description,
        $cover_image,
        $image1,
        $image2,
        $image3,
        $price,
        $quantity
    );

   // ... after successful database insertion ...
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "🚀 Book submitted! It is now waiting for admin approval.";
            $_SESSION['flash_type'] = "success";
            header("Location: myaccount.php");
        } else {
        // Debugging: Show exact error
        echo "Database Error: " . $stmt->error;
        // header("Location: myaccount.php?msg=Database error: " . urlencode($stmt->error)); // Alternative
    }
    exit();
}



?>