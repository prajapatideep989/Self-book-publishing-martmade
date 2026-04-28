<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

/* DB Connection */
$conn = new mysqli("localhost", "root", "", "books_db");
if ($conn->connect_error) {
    die("DB Error");
}

$userName = "";
$userEmail = "";

if ($isLoggedIn) {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT name,email FROM users WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->bind_result($userName, $userEmail);
    $stmt->fetch();
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact & Feedback | Alpha Book</title>
    <style>
        :root {
            --purple-main: #6366f1;
            --purple-dark: #4f46e5;
            --accent: #a855f7;
            --text-slate: #1e293b;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f1f5f9;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
        }

        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            width: 100%;
            max-width: 1100px;
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.6s ease-out;
        }

        /* LEFT SIDE: PURPLE SECTION */
        .contact-info-side {
            background: linear-gradient(135deg, var(--purple-main), var(--accent));
            padding: 3.5rem;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .contact-info-side h2 {
            font-size: 2.2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .contact-info-side p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-bottom: 2rem;
        }

        .contact-item i {
            font-size: 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.8rem;
            border-radius: 12px;
            backdrop-filter: blur(5px);
        }

        .contact-text h4 {
            margin: 0;
            font-size: 1.1rem;
        }

        .contact-text p {
            margin: 0;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
        }

        /* RIGHT SIDE: WHITE SECTION */
        .feedback-form-side {
            padding: 3.5rem;
            background: #ffffff;
        }

        .feedback-form-side h1 {
            color: var(--text-slate);
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .input-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.2rem;
        }

        .input-group.textarea i {
            top: 1.5rem;
            transform: none;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: 1rem;
            font-family: inherit;
            box-sizing: border-box;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--purple-main);
            background: white;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .btn-send {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            border: none;
            background: var(--purple-main);
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-send:hover {
            background: var(--purple-dark);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 900px) {
            .contact-container {
                grid-template-columns: 1fr;
            }

            .contact-info-side {
                padding: 2.5rem;
            }

            .feedback-form-side {
                padding: 2.5rem;
            }
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <div class="main-wrapper">
        <div class="contact-container">

            <div class="contact-info-side">
                <h2>Contact Us</h2>
                <p>Have a question about a book or your order? Our team is here to help you 24/7.</p>

                <div class="contact-item">
                    <i class='bx bx-map-pin'></i>
                    <div class="contact-text">
                        <h4>Address</h4>
                        <p>Alpha Tower, Surat, India</p>
                    </div>
                </div>

                <div class="contact-item">
                    <i class='bx bx-phone'></i>
                    <div class="contact-text">
                        <h4>Phone</h4>
                        <p>+91 98765 43210</p>
                    </div>
                </div>

                <div class="contact-item">
                    <i class='bx bx-envelope'></i>
                    <div class="contact-text">
                        <h4>Email</h4>
                        <p>support@alphabook.com</p>
                    </div>
                </div>
            </div>

            <div class="feedback-form-side">
                <h1>Feedback</h1>
                <p style="color: #64748b; margin-bottom: 2rem;">We value your thoughts. Let us know how we're doing!</p>

                <form method="POST" action="submit_feedback.php">
                    <div class="input-group">
                        <i class='bx bx-user'></i>
                        <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($userName) ?>"
                            placeholder="Name" <?= $isLoggedIn ? 'readonly' : 'required' ?>>
                    </div>

                    <div class="input-group">
                        <i class='bx bx-envelope'></i>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($userEmail) ?>"
                            placeholder="Email" <?= $isLoggedIn ? 'readonly' : 'required' ?>>
                    </div>

                    <div class="input-group">
                        <i class='bx bx-bookmark'></i>
                        <input type="text" name="subject" class="form-input" placeholder="Subject" required>
                    </div>

                    <div class="input-group textarea">
                        <i class='bx bx-chat'></i>
                        <textarea name="message" class="form-input" rows="5" placeholder="Message..." required
                            style="height: 120px; resize: none;"></textarea>
                    </div>

                    <button type="submit" class="btn-send">
                        Send Message <i class='bx bx-paper-plane'></i>
                    </button>
                </form>
            </div>

        </div>
    </div>

    <footer style="background: #1e293b; color: white; padding: 2rem 1rem; text-align: center;">
        <p>&copy; 2025 Alpha Book. All Rights Reserved.</p>
    </footer>

</body>

</html>