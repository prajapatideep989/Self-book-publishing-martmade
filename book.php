<?php
session_start();
include 'db.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Books | Alpha Book</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet"><link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'><style> :root {
            --primary: #6c63ff;
            --primary-dark: #5a52e0;
            --secondary: #764ba2;
            --accent: #5f6cffff;
            --bg-light: #f8faff;
            --text-main: #2d3436;
            --text-muted: #636e72;
            --glass: rgba(255, 255, 255, 0.8);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 20px 40px rgba(108, 99, 255, 0.15);
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        body {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            background: var(--bg-light);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* HERO SECTION */
        .books-hero {
            position: relative;
            text-align: center;
            padding: 100px 20px 60px;
            background: radial-gradient(circle at top right, rgba(108, 99, 255, 0.05), transparent),
                radial-gradient(circle at bottom left, rgba(118, 75, 162, 0.05), transparent);
            overflow: hidden;
        }

        .books-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -10%;
            width: 40%;
            height: 100%;
            background: linear-gradient(135deg, rgba(108, 99, 255, 0.1), transparent);
            filter: blur(80px);
            z-index: -1;
            border-radius: 50%;
        }

        .books-hero h1 {
            font-size: clamp(2.5rem, 5vw, 3.8rem);
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #2d3436 0%, #6c63ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: fadeInUp 0.8s ease-out;
        }

        .books-hero p {
            font-size: 1.1rem;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 40px;
            line-height: 1.6;
            animation: fadeInUp 0.8s ease-out 0.2s backwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* FILTER BAR */
        .filter-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
            animation: fadeInUp 0.8s ease-out 0.4s backwards;
        }

        .filter-bar {
            display: flex;
            gap: 15px;
            background: var(--glass);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 10px;
            border-radius: 100px;
            box-shadow: var(--card-shadow);
            flex-wrap: wrap;
        }

        .search-wrapper {
            flex: 1;
            position: relative;
            min-width: 280px;
        }

        .search-wrapper i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.2rem;
        }

        .search-input {
            width: 100%;
            padding: 15px 20px 15px 55px;
            border-radius: 100px;
            border: 1px solid transparent;
            background: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            font-family: inherit;
            transition: var(--transition);
            box-sizing: border-box;
        }

        .search-input:focus {
            outline: none;
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.1);
        }

        .category-select {
            padding: 0 25px;
            border-radius: 100px;
            border: 1px solid transparent;
            background: rgba(255, 255, 255, 0.9);
            font-size: 15px;
            font-family: inherit;
            cursor: pointer;
            transition: var(--transition);
            min-width: 180px;
        }

        .category-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .filter-btn {
            padding: 15px 35px;
            border: none;
            border-radius: 100px;
            background: linear-gradient(135deg, #845fffff 0%, #7b8ffeff 100%);
            color: #fff;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(255, 126, 95, 0.3);
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 126, 95, 0.4);
            filter: brightness(1.1);
        }

        /* GRID */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 40px;
            padding: 60px 5%;
            max-width: 1400px;
            margin: auto;
        }

        /* NEW CARD STYLES (Used in AJAX) */
        .book-card-unique {
            background: linear-gradient(145deg, #ffffff 0%, #f9fbff 100%);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04),
                inset 0 0 15px rgba(255, 255, 255, 0.6);
            transition: all 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255, 255, 255, 0.8);
            perspective: 1200px;
            opacity: 0;
            animation: fadeInSlide 0.7s cubic-bezier(0.23, 1, 0.32, 1) forwards;
        }

        .book-card-unique::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 30px;
            padding: 2px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.1));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
        }

        .book-card-unique:hover {
            transform: translateY(-15px) rotateX(2deg) rotateY(1deg);
            box-shadow: 0 30px 60px rgba(108, 99, 255, 0.1),
                inset 0 0 0px rgba(255, 255, 255, 1);
        }

        @keyframes fadeInSlide {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .badge {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .badge-stock {
            background: rgba(0, 184, 148, 0.9);
            color: #fff;
        }

        .badge-out {
            background: rgba(255, 118, 117, 0.9);
            color: #fff;
        }

        .book-cover-wrapper {
            position: relative;
            height: 380px;
            background: radial-gradient(circle at center, #ffffff 0%, #f0f4ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            overflow: hidden;
        }

        .book-card-unique img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            filter: drop-shadow(15px 15px 25px rgba(0, 0, 0, 0.15));
            transition: all 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
            border-radius: 4px;
        }

        .book-card-unique:hover img {
            transform: scale(1.1) rotateY(-10deg);
            filter: drop-shadow(25px 25px 40px rgba(0, 0, 0, 0.25));
        }

        .book-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.4s ease;
            z-index: 5;
        }

        .book-card-unique:hover .book-overlay {
            opacity: 1;
        }

        .view-btn {
            background: var(--text-main);
            color: #fff;
            padding: 14px 34px;
            border-radius: 100px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform: translateY(40px) scale(0.8);
            opacity: 0;
        }

        .book-card-unique:hover .view-btn {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .view-btn:hover {
            background: var(--primary);
            transform: scale(1.1);
        }

        .book-details-unique {
            padding: 30px;
            background: #fff;
            flex: 1;
            display: flex;
            flex-direction: column;
            border-top: 1px solid rgba(0, 0, 0, 0.03);
        }

        .book-category {
            font-size: 10px;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 1.5px;
            opacity: 0.8;
        }

        .book-title {
            font-size: 1.3rem;
            font-weight: 800;
            margin-bottom: 8px;
            line-height: 1.2;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .book-author {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-bottom: 25px;
            font-weight: 400;
        }

        .book-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 20px;
            border-top: 1px dashed rgba(0, 0, 0, 0.08);
        }

        .book-price {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-main);
            display: flex;
            align-items: baseline;
        }

        .book-price span {
            font-size: 1rem;
            margin-right: 4px;
            color: var(--primary);
        }

        .btn-add-unique {
            width: 50px;
            height: 50px;
            border-radius: 18px;
            border: none;
            background: #f1f4ff;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            box-shadow: 0 4px 10px rgba(108, 99, 255, 0.1);
        }

        .btn-add-unique:hover:not(:disabled) {
            background: #2a2580;
            color: #fff;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 20px rgba(42, 37, 128, 0.4);
        }

        @media (max-width: 768px) {
            .books-hero {
                padding: 80px 20px 40px;
            }

            .filter-bar {
                border-radius: 20px;
                padding: 15px;
            }

            .search-wrapper {
                order: 1;
                width: 100%;
            }

            .category-select {
                order: 2;
                flex: 1;
                height: 50px;
            }

            .filter-btn {
                order: 3;
                width: 100%;
            }

            .books-grid {
                gap: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>

    <?php
    $hide_loader = true;
    include 'header.php';
    ?>

    <section class="books-hero">
        <h1>Discover Your Next Adventure</h1>
        <p>Dive into a world of imagination. Explore our handpicked collection of stories, knowledge, and inspiration.
        </p>

        <div class="filter-container">
            <div class="filter-bar">
                <div class="search-wrapper">
                    <i class='bx bx-search'></i>
                    <input type="text" id="ajaxSearch" class="search-input"
                        placeholder="Search by title, author, or keyword..." value="<?= htmlspecialchars($search) ?>"
                        oninput="applyFilters()">
                </div>

                <select id="ajaxCategory" class="category-select" onchange="applyFilters()">
                    <option value="">All Categories</option>
                    <option value="Novel">Novel</option>
                    <option value="Fiction">Fiction</option>
                    <option value="Adventure">Adventure</option>
                    <option value="Romance">Romance</option>
                    <option value="Education">Education</option>
                    <option value="Technology">Technology</option>
                    <option value="Kids">Kids</option>
                    <option value="Biography">Biography</option>
                </select>

                <button class="filter-btn" onclick="applyFilters()">Search</button>
            </div>
        </div>
    </section>


    <section class="books-grid" id="booksGridContainer"></section>

    <script>
        function applyFilters() {
            const search = document.getElementById('ajaxSearch').value;
            const category = document.getElementById('ajaxCategory').value;

            fetch(`fetch_books_ajax.php?search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}`)
                .then(res => res.text())
                .then(data => {
                    document.getElementById('booksGridContainer').innerHTML = data;
                });
        }

        window.onload = applyFilters;
    </script>

</body>

</html>