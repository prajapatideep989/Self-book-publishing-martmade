<?php
session_start();
include 'db.php';
$res = $conn->query("SELECT id, name FROM users LIMIT 1");
if ($user = $res->fetch_assoc()) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    echo "Logged in as " . $user['name'] . ". Redirecting to index.php...";
} else {
    echo "No users found in database.";
}
?>
<meta http-equiv="refresh" content="2;url=index.php">
