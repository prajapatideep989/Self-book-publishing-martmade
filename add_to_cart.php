<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    require_once 'db.php';
    $book_id = (int) $_POST['book_id'];

    if ($book_id <= 0) {
        if ($isAjax) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid book ID']);
            exit;
        }
    }

    $book_name = "Book";
    $query = $conn->prepare("SELECT book_name FROM books WHERE id = ?");
    $query->bind_param("i", $book_id);
    $query->execute();
    $res = $query->get_result();
    if ($row = $res->fetch_assoc()) {
        $book_name = $row['book_name'];
    }

    if (!isset($_SESSION['cart'][$book_id])) {
        $_SESSION['cart'][$book_id] = 1;
        $msg = "✅ " . $book_name . " added to cart";
        $status = 'success';
    } else {
        $msg = "⚠️ " . $book_name . " already in cart";
        $status = 'warning';
    }

    if ($isAjax) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => $status, 'message' => $msg]);
        exit;
    } else {
        $_SESSION['flash_message'] = $msg;
        $_SESSION['flash_type'] = $status;
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'book.php'));
        exit;
    }
}

if ($isAjax) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
} else {
    header("Location: book.php");
    exit;
}
