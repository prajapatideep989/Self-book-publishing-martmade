<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Showcase - Alpha Book</title>
    <!-- CSS is in header or added here -->
    <style>
        body {
            margin: 0;
            overflow: hidden;
            background: #000;
            /* Dark background for better glow visibility */
        }

        #showcase-container {
            width: 100vw;
            height: 100vh;
            position: relative;
        }

        .back-btn {
            position: fixed;
            top: 100px;
            left: 30px;
            z-index: 1000;
            background: rgba(168, 85, 247, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .back-btn:hover {
            background: var(--primary);
            box-shadow: 0 0 20px var(--primary);
        }
    </style>
</head>

<body>

    <a href="index.php" class="back-btn"><i class='bx bx-left-arrow-alt'></i> Back Home</a>

    <div id="showcase-container"></div>

    <!-- 3D Scripts -->
    <script src="alpha_book_real.js"></script>

</body>

</html>