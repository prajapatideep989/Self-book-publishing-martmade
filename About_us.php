<?php
session_start();
// Use global helper for user validation/display if available, tailored here for standalone page
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>About Us | Alpha Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Specific Styles for About Page */
        .hero-about {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            padding: 5rem 1rem;
            text-align: center;
            border-radius: 0 0 50% 50% / 4rem;
            margin-bottom: 3rem;
        }

        .hero-about h1 {
            font-size: 3rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .about-card-unique {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
            margin-bottom: 3rem;
            transition: transform 0.3s ease;
        }

        .about-card-unique:hover {
            transform: translateY(-5px);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-item {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
            background: rgba(99, 102, 241, 0.1);
            width: 80px;
            height: 80px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        blockquote {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            text-align: center;
            margin: 4rem 0;
            position: relative;
        }

        blockquote::before {
            content: '"';
            font-size: 5rem;
            color: var(--primary);
            opacity: 0.2;
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <?php include 'header.php'; ?>

    <!-- HEADER -->
    <header class="hero-about">
        <div class="container">
            <h1 class="animate-gradient">We Are Alpha Book</h1>
            <p style="font-size: 1.1rem; color: var(--text-light); max-width: 600px; margin: 0 auto;"
                class="fade-in-up delay-1">
                Empowering authors, satisfying readers, and building a community around the love of books.
            </p>
        </div>
    </header>

    <!-- CONTENT -->
    <div class="container">

        <div class="about-card-unique fade-in-up">
            <div style="display: flex; flex-direction: column; gap: 2rem; align-items: center; text-align: center;">
                <div>
                    <span class="badge badge-stock" style="font-size: 0.9rem; margin-bottom: 1rem;">Our Story</span>
                    <h2 style="font-size: 2rem; margin-bottom: 1rem;" class="reveal-text">From Outline to Best-Seller
                    </h2>
                    <p style="color: var(--text-light); line-height: 1.8; font-size: 1.05rem;"
                        class="fade-in-up delay-1">
                        Alpha Book started with a simple mission: to make self-publishing accessible to everyone. We
                        noticed that many talented writers in our community had stories to tell but lacked the platform
                        to share them. Whether you are a student sharing notes, a novelist publishing your debut, or a
                        reader looking for hidden gems, Alpha Book is built for you.
                    </p>
                </div>
            </div>
        </div>

        <blockquote>
            Knowledge shared is knowledge multiplied.
        </blockquote>

        <div class="feature-grid">
            <div class="feature-item fade-in-up delay-1">
                <div class="feature-icon"><i class='bx bxs-cloud-upload'></i></div>
                <h3>Easy Publishing</h3>
                <p style="color: var(--text-light);">Upload your book in seconds and reach a global audience instantly.
                </p>
            </div>
            <div class="feature-item fade-in-up delay-2">
                <div class="feature-icon"><i class='bx bxs-rocket'></i></div>
                <h3>Fast Delivery</h3>
                <p style="color: var(--text-light);">Optimized logistics to ensure your books reach readers quickly.</p>
            </div>
            <div class="feature-item fade-in-up delay-3">
                <div class="feature-icon"><i class='bx bxs-check-shield'></i></div>
                <h3>Secure Payments</h3>
                <p style="color: var(--text-light);">Now supporting Online Payments and Cash on Delivery for
                    flexibility.</p>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <footer style="margin-top: 5rem; background: #1f2937; color: white; padding: 4rem 1rem;">
        <div class="container footer-content"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
            <div>
                <a href="#" class="logo"
                    style="color:white; font-size: 1.5rem; display: flex; align-items: center; gap: 10px; margin-bottom: 1rem;">
                    <i class='bx bxs-book-heart'></i> Alpha Book
                </a>
                <p style="color: #9ca3af;">Premium platform for book lovers.</p>
            </div>
            <div>
                <h4 style="margin-bottom: 1rem;">Quick Links</h4>
                <div style="display:flex; flex-direction:column; gap: 0.5rem;">
                    <a href="book.php" style="color: #d1d5db; text-decoration: none;">Browse Books</a>
                    <a href="register.php" style="color: #d1d5db; text-decoration: none;">Join Us</a>
                    <a href="feedback.php" style="color: #d1d5db; text-decoration: none;">Feedback</a>
                </div>
            </div>
            <div>
                <h4 style="margin-bottom: 1rem;">Contact</h4>
                <div style="display:flex; flex-direction:column; gap: 0.5rem; color: #d1d5db;">
                    <span>INDIA</span>
                    <span>support@alphabook.com</span>
                </div>
            </div>
        </div>
        <div class="container"
            style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #374151; color: #6b7280;">
            &copy; 2025 Alpha Book. All Rights Reserved.
        </div>
    </footer>

</body>

</html>