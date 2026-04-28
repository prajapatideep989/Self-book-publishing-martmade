<?php
session_start();
include 'db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_captcha = strtoupper(trim($_POST['captcha']));

    if (empty($user_captcha) || $user_captcha !== $_SESSION['captcha_code']) {
        $error = "Invalid captcha code. Please try again.";
    } else {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                unset($_SESSION['captcha_code']);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                header("Location: " . ($user['role'] === 'admin' ? "Admin_home.php" : "index.php"));
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Secure Login | Alpha Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-light: #64748b;
            --danger: #ef4444;
            --border: #e2e8f0;
            --radius: 16px;
        }

        body {
            background: radial-gradient(circle at top right, #eef2ff, transparent),
                radial-gradient(circle at bottom left, #f1f5f9, transparent);
            background-color: var(--bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .form-card {
            background: var(--card-bg);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            border-radius: var(--radius);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            border: 1px solid var(--border);
        }

        .text-center {
            text-align: center;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        /* --- INPUT STYLING --- */
        .input-wrapper {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .form-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.2rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 12px 12px 42px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: all 0.2s;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        /* --- CAPTCHA STYLING --- */
        .captcha-container {
            display: grid;
            grid-template-columns: auto 40px 1fr;
            gap: 10px;
            align-items: center;
            margin-bottom: 1.5rem;
            background: #f1f5f9;
            padding: 8px;
            border-radius: 14px;
            border: 1px solid var(--border);
        }

        .captcha-img-box {
            height: 45px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            border: 1px solid var(--border);
        }

        .captcha-img-box img {
            height: 100%;
            width: 120px;
            object-fit: cover;
        }

        .refresh-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }

        .refresh-btn:hover {
            transform: rotate(180deg);
        }

        .captcha-input {
            padding: 10px !important;
            text-align: center;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* --- BUTTONS --- */
        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .error-alert {
            background-color: #fef2f2;
            color: var(--danger);
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #fee2e2;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <div class="main-content">
        <div class="form-card">
            <div class="text-center mb-4">
                <h1 style="margin:0; font-size: 1.75rem; color: var(--text-main);">Welcome Back</h1>
                <p style="color: var(--text-light); margin-top: 5px;">Securely login to Alpha Book</p>
            </div>

            <?php if ($error): ?>
                <div class="error-alert">
                    <i class='bx bxs-error-circle'></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-wrapper">
                    <i class='bx bx-envelope form-icon'></i>
                    <input type="email" name="email" class="form-control" placeholder="Email address" required>
                </div>

                <div class="input-wrapper">
                    <i class='bx bx-lock-alt form-icon'></i>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>

                <div class="captcha-container">
                    <div class="captcha-img-box">
                        <img src="captcha.php" alt="CAPTCHA" id="captcha-img">
                    </div>

                    <button type="button" class="refresh-btn"
                        onclick="document.getElementById('captcha-img').src='captcha.php?'+Math.random();">
                        <i class='bx bx-refresh'></i>
                    </button>

                    <input type="text" name="captcha" class="form-control captcha-input" placeholder="CODE"
                        maxlength="6" required>
                </div>

                <button type="submit" class="btn-primary">Sign In</button>
            </form>

            <div class="text-center" style="margin-top: 2rem; font-size: 0.9rem; color: var(--text-light);">
                Don't have an account? <a href="register.php"
                    style="color: var(--primary); font-weight: 700; text-decoration: none;">Create one</a>
            </div>
        </div>
    </div>

</body>

</html>