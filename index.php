<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';
$featured_books = $conn->query("SELECT * FROM books WHERE status='approved' ORDER BY created_at DESC LIMIT 15");
// Fetch 3 latest feedbacks for the home page
$home_feedbacks = $conn->query("SELECT name, message, created_at FROM feedback ORDER BY created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alpha Book - Experience Stories in 3D</title>
    <!-- Observer is unique to Home.php, others are in header.php -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/Observer.min.js"></script>
    <title>Alpha Book - Experience Stories in 3D</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #2e1065;
            --accent: #a855f7;
            --white: #ffffff;
            --text: #1e293b;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--white);
            color: var(--text);
            overflow-x: hidden;
        }

        /* HOME FEEDBACK STYLES */
        .home-feedback-section {
            padding: 8rem 0;
            background: #f8fafc;
            position: relative;
            overflow: hidden;
        }

        .feedback-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .feedback-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .feedback-title h2 {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .feedback-grid-home {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
        }

        .feedback-card-home {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            opacity: 0;
            /* For GSAP */
            transform: translateY(30px);
            /* For GSAP */
        }

        .feedback-card-home:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
        }

        .fb-quote {
            color: var(--accent);
            font-size: 2rem;
            line-height: 1;
        }

        .fb-content {
            font-style: italic;
            color: #475569;
            line-height: 1.7;
            flex: 1;
        }

        .fb-author-home {
            display: flex;
            align-items: center;
            gap: 12px;
            border-top: 1px solid #f1f5f9;
            padding-top: 1.5rem;
        }

        .fb-avatar-home {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .fb-author-info h4 {
            margin: 0;
            font-size: 1rem;
            color: var(--primary);
        }

        .fb-author-info span {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        #alpha-book-viewer {
            position: relative;
            width: 100%;
            height: 100vh;
            /* Vibrant yet Simple Indigo-Purple Mesh Gradient */
            background-color: #ffffff;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(99, 102, 241, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(168, 85, 247, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 50% 10%, rgba(243, 232, 255, 0.3) 0%, transparent 70%);
            overflow: hidden;
        }

        /* Geometric Dot-Grid Pattern & Subtle Grain Overlay */
        #alpha-book-viewer::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            /* Layer 1: Dot Grid Pattern */
            background-image: radial-gradient(rgba(99, 102, 241, 0.15) 1px, transparent 1px);
            background-size: 30px 30px;
            /* Layer 2: Subtle Noise */
            mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0.5));
        }

        #alpha-book-viewer::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.02;
            pointer-events: none;
            z-index: 1;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3%3Ffilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3Csvg%3E");
        }

        #canvas-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        .content-overlay {
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem 5% 2rem 10rem;
            z-index: 10;
            pointer-events: none;
            backdrop-filter: blur(0px);
            /* Clean hook for future glass effects if needed */
        }

        .text-block {
            opacity: 0;
            transform: translateX(40px);
            position: absolute;
            max-width: 550px;
            transition: all 0.8s ease;
        }

        .text-block.active {
            opacity: 1;
            transform: translateY(0);
        }

        .text-block h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            color: var(--primary);
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .text-block p {
            font-size: 1.1rem;
            color: #64748b;
            line-height: 1.6;
        }

        .badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(168, 85, 247, 0.1);
            color: var(--accent);
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cta-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 2rem;
            opacity: 0;
            transform: translateY(20px);
            margin-top: 2rem;
            pointer-events: auto;
        }

        .cta-headline {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary);
            line-height: 1.2;
            max-width: 400px;
            background: linear-gradient(135deg, #2e1065, #4f46e5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
        }

        .btn-cta {
            padding: 1.2rem 2.8rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-size: 1.05rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            /* Space for icon */
            overflow: hidden;
            position: relative;
        }

        .btn-cta i {
            transition: transform 0.3s ease;
            font-size: 1.3rem;
        }

        .btn-cta.primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #a855f7 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-cta.primary:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(124, 58, 237, 0.45);
        }

        .btn-cta.primary:hover i {
            transform: translateX(5px);
        }

        .btn-cta.secondary {
            background: rgba(255, 255, 255, 0.8);
            color: var(--primary);
            border: 2px solid #e2e8f0;
            backdrop-filter: blur(10px);
        }

        .btn-cta.secondary:hover {
            background: #ffffff;
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-6px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .btn-cta.secondary:hover i {
            transform: scale(1.2);
        }

        /* Featured Books Ticker Styles */
        .featured-books-section {
            padding: 8rem 0;
            background: #ffffff;
            overflow: hidden;
            position: relative;
        }

        .featured-title {
            text-align: center;
            margin-bottom: 4rem;
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary);
        }

        .ticker-container {
            width: 100%;
            overflow: hidden;
            padding: 2rem 0;
            cursor: grab;
        }

        .ticker-track {
            display: flex;
            gap: 2.5rem;
            width: max-content;
            will-change: transform;
        }

        .book-card-ticker {
            width: 220px;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.1);
            border-radius: 16px;
            padding: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            transition: all 0.4s ease;
            text-decoration: none;
            color: inherit;
        }

        .book-card-ticker:hover {
            transform: translateY(-10px) scale(1.05);
            border-color: var(--accent);
            box-shadow: 0 20px 40px rgba(124, 58, 237, 0.15);
        }

        .ticker-img-wrapper {
            width: 100%;
            height: 280px;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .ticker-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ticker-book-info h4 {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 0.3rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ticker-book-info p {
            font-size: 0.8rem;
            color: #64748b;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <section id="book-section" style="position: relative; height: 400vh;">
        <div id="alpha-book-viewer" style="position: relative; width: 100%; height: 100vh; overflow: hidden;">
            <div id="canvas-container" style="width: 100%; height: 100%;"></div>
            <div class="content-overlay">
                <div id="text-1" class="text-block active">
                    <span class="badge">Evolving Stories</span>
                    <h1>Alpha Book</h1>
                    <p>Welcome to the next generation of digital publishing. Experience literature in a premium 3D
                        environment.</p>
                </div>
                <div id="text-2" class="text-block">
                    <span class="badge">Pure Imagination</span>
                    <h1>Welcome to Alpha Books</h1>
                    <p>Dive deep into curated content that feels as real as a physical book, right in your browser.</p>
                </div>
                <div id="text-3" class="text-block">
                    <span class="badge">Your Voice</span>
                    <h1>Share Your Thoughts</h1>
                    <p>You can share your thoughts via books in Alpha Book. Connect with readers globally and make your
                        mark.</p>
                </div>
                <div id="cta-block" class="cta-container">
                    <h2 class="cta-headline">Ready to Start Your Story?</h2>
                    <div class="cta-buttons">
                        <a href="register.php" class="btn-cta primary">
                            Join Us <i class='bx bx-right-arrow-alt'></i>
                        </a>
                        <a href="book.php" class="btn-cta secondary">
                            Browse the Book <i class='bx bx-book-open'></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="featured-books-section">
        <div class="container">
            <h2 class="featured-title">Featured Collections</h2>
        </div>
        <div class="ticker-container" id="ticker-container">
            <div class="ticker-track" id="ticker-track">
                <?php
                if ($featured_books && $featured_books->num_rows > 0):
                    // Double the items for seamless loop
                    $books_array = [];
                    while ($row = $featured_books->fetch_assoc()) {
                        $books_array[] = $row;
                    }
                    $display_books = array_merge($books_array, $books_array, $books_array);
                    foreach ($display_books as $book):
                        ?>
                        <a href="book_details.php?id=<?= $book['id'] ?>" class="book-card-ticker">
                            <div class="ticker-img-wrapper">
                                <img src="<?= htmlspecialchars($book['cover_image']) ?>"
                                    alt="<?= htmlspecialchars($book['book_name']) ?>">
                            </div>
                            <div class="ticker-book-info">
                                <h4><?= htmlspecialchars($book['book_name']) ?></h4>
                                <p>By <?= htmlspecialchars($book['author_name']) ?></p>
                            </div>
                        </a>
                    <?php endforeach; else: ?>
                    <p style="text-align: center; width: 100%; color: #94a3b8;">New stories coming soon...</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- USER FEEDBACK SECTION -->
    <section class="home-feedback-section">
        <div class="feedback-container">
            <div class="feedback-title">
                <h2>Reader Love</h2>
                <p style="color: #64748b;">What our amazing community has to say about Alpha Book.</p>
            </div>
            <div class="feedback-grid-home">
                <?php if ($home_feedbacks && $home_feedbacks->num_rows > 0): ?>
                    <?php while ($fb = $home_feedbacks->fetch_assoc()): ?>
                        <div class="feedback-card-home">
                            <i class='bx bxs-quote-left fb-quote'></i>
                            <div class="fb-content">
                                "<?= nl2br(htmlspecialchars($fb['message'])) ?>"
                            </div>
                            <div class="fb-author-home">
                                <div class="fb-avatar-home"><?= strtoupper(substr($fb['name'], 0, 1)) ?></div>
                                <div class="fb-author-info">
                                    <h4><?= htmlspecialchars($fb['name']) ?></h4>
                                    <span>Verified Reader</span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; grid-column: 1/-1; color: #94a3b8;">Be the first to share your experience!
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer style="background: #0f172a; color: white; padding: 4rem 1rem;">
        <div class="container"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem;">
            <div>
                <a href="index.php" class="logo" style="color: white; margin-bottom: 1.5rem;">
                    <i class='bx bxs-book-heart'></i> Alpha Book
                </a>
                <p style="color: #94a3b8; font-size: 0.9rem;">The premium destination for 3D literature and
                    self-publishing.</p>
            </div>
            <div>
                <h4 style="margin-bottom: 1.5rem;">Quick Links</h4>
                <div style="display: flex; flex-direction: column; gap: 0.8rem; font-size: 0.9rem;">
                    <a href="book.php" style="color: #cbd5e1; text-decoration: none;">Browse Store</a>
                    <a href="About_us.php" style="color: #cbd5e1; text-decoration: none;">Our Story</a>
                    <a href="register.php" style="color: #cbd5e1; text-decoration: none;">Start Writing</a>
                </div>
            </div>
            <div>
                <h4 style="margin-bottom: 1.5rem;">Connect</h4>
                <div style="display: flex; gap: 1rem; font-size: 1.5rem;">
                    <a href="#" style="color: #94a3b8;"><i class='bx bxl-instagram'></i></a>
                    <a href="#" style="color: #94a3b8;"><i class='bx bxl-twitter'></i></a>
                    <a href="#" style="color: #94a3b8;"><i class='bx bxl-facebook'></i></a>
                </div>
            </div>
        </div>
        <div class="container"
            style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; color: #64748b; font-size: 0.8rem;">
            &copy; 2026 Alpha Book. All rights reserved.
        </div>
    </footer>

    <script>
        gsap.registerPlugin(ScrollTrigger, Observer);

        const container = document.getElementById('canvas-container');

        // ================= AUDIO SETUP (FIXED) =================

// Use ABSOLUTE PATH (important for hosting)
const bookOpenSound = new Audio('/Tables/box1.mp3');
const pageFlipSound = new Audio('/Tables/page.mp3');

let audioUnlocked = false;

// Unlock audio ONLY on first click
document.addEventListener("click", () => {
    if (audioUnlocked) return;

    audioUnlocked = true;

    bookOpenSound.load();
    pageFlipSound.load();

    console.log("Audio Unlocked ✅");
}, { once: true });

// Safe Play Function
function playSound(audio) {
    if (!audioUnlocked) return;

    audio.currentTime = 0;
    audio.volume = 0.7;

    audio.play().catch(err => {
        console.log("Playback blocked:", err);
    });
}

        const scene = new THREE.Scene();
        // Background remains null/transparent to show CSS blobs

        const camera = new THREE.PerspectiveCamera(35, container.clientWidth / container.clientHeight, 0.1, 1000);
        camera.position.set(3.4, 0, 12); // Pushed model further left in frame

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setPixelRatio(window.devicePixelRatio);
        renderer.setClearColor(0x000000, 0); // Explicitly transparent
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        renderer.useLegacyLights = false;
        renderer.toneMapping = THREE.ReinhardToneMapping;
        renderer.toneMappingExposure = 1.25;
        container.appendChild(renderer.domElement);

        const ambientLight = new THREE.AmbientLight(0xffffff, 0.9); // Brighter fill
        scene.add(ambientLight);

        const dirLight = new THREE.DirectionalLight(0xffffff, 1.2);
        dirLight.position.set(2, 8, 12); // Front-top lighting for better clarity
        dirLight.castShadow = true;
        dirLight.shadow.mapSize.width = 1024; // Balanced resolution
        dirLight.shadow.mapSize.height = 1024;
        dirLight.shadow.camera.near = 0.1;
        dirLight.shadow.camera.far = 40;
        dirLight.shadow.camera.left = -10;
        dirLight.shadow.camera.right = 10;
        dirLight.shadow.camera.top = 10;
        dirLight.shadow.camera.bottom = -10;
        dirLight.shadow.bias = -0.0005; // Prevent shadow acne artifacts
        scene.add(dirLight);

        const bookGroup = new THREE.Group();
        scene.add(bookGroup);

        const w = 2.2, h = 3.2, d = 0.6;
        const overhang = 0.1;

        // --- SHADOW SYSTEM (HYBRID) ---
        function createRadialShadowTexture() {
            const canvas = document.createElement('canvas');
            canvas.width = 256; canvas.height = 256;
            const ctx = canvas.getContext('2d');
            const grd = ctx.createRadialGradient(128, 128, 0, 128, 128, 128);
            grd.addColorStop(0, 'rgba(0,0,0,0.5)'); // Darkest in center
            grd.addColorStop(0.3, 'rgba(0,0,0,0.2)');
            grd.addColorStop(1, 'rgba(0,0,0,0)'); // Transparent edges
            ctx.fillStyle = grd;
            ctx.fillRect(0, 0, 256, 256);
            return new THREE.CanvasTexture(canvas);
        }

        const shadowTex = createRadialShadowTexture();
        const contactShadow = new THREE.Mesh(
            new THREE.PlaneGeometry(6, 6),
            new THREE.MeshBasicMaterial({ map: shadowTex, transparent: true, blending: THREE.MultiplyBlending })
        );
        contactShadow.position.z = -2.49; // Placed just above the real shadow plane
        scene.add(contactShadow);

        const shadowPlaneGeo = new THREE.PlaneGeometry(50, 50);
        const shadowPlaneMat = new THREE.ShadowMaterial({ opacity: 0.05 }); // Real-time shadow for subtle structure
        const shadowPlane = new THREE.Mesh(shadowPlaneGeo, shadowPlaneMat);
        shadowPlane.position.z = -2.5;
        shadowPlane.receiveShadow = true;
        scene.add(shadowPlane);

        // --- TEXTURES ---
        function createPageTexture() {
            const canvas = document.createElement('canvas');
            canvas.width = 512; canvas.height = 700;
            const ctx = canvas.getContext('2d');

            // Paper base
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, 512, 700);

            // Subtle paper grain
            for (let i = 0; i < 5000; i++) {
                ctx.fillStyle = `rgba(0,0,0,${Math.random() * 0.02})`;
                ctx.fillRect(Math.random() * 512, Math.random() * 700, 1, 1);
            }

            // Simulated text lines
            ctx.fillStyle = '#475569';
            for (let i = 60; i < 600; i += 25) {
                const lineW = 300 + Math.random() * 100;
                ctx.fillRect(50, i, lineW, 4);
            }

            // Page number
            ctx.font = 'normal 14px sans-serif';
            ctx.fillText('Alpha Book | 42', 200, 660);

            const tex = new THREE.CanvasTexture(canvas);
            tex.anisotropy = 16;
            return tex;
        }

        function createPageEdgeTexture() {
            const canvas = document.createElement('canvas');
            canvas.width = 128; canvas.height = 1024;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, 128, 1024);
            ctx.strokeStyle = '#f1f5f9';
            ctx.lineWidth = 1;
            for (let i = 0; i < 1024; i += 4) {
                ctx.beginPath();
                ctx.moveTo(0, i); ctx.lineTo(128, i);
                ctx.stroke();
            }
            const tex = new THREE.CanvasTexture(canvas);
            tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
            return tex;
        }

        function createCoverTexture(title) {
            const canvas = document.createElement('canvas');
            canvas.width = 1024; canvas.height = 1365;
            const ctx = canvas.getContext('2d');

            // LUXURY GRADIENT BACKGROUND
            const bgGrd = ctx.createLinearGradient(0, 0, 1024, 1365);
            bgGrd.addColorStop(0, '#4f46e5');
            bgGrd.addColorStop(0.5, '#1e1b4b');
            bgGrd.addColorStop(1, '#6366f1');
            ctx.fillStyle = bgGrd;
            ctx.fillRect(0, 0, 1024, 1365);

            // GOLD FOIL DECORATIVE BORDER
            ctx.strokeStyle = '#fbbf24'; // Gold
            ctx.lineWidth = 15;
            ctx.strokeRect(50, 50, 924, 1265);
            ctx.lineWidth = 3;
            ctx.strokeRect(70, 70, 884, 1225);

            // CORNER ACCENTS
            const drawCorner = (x, y, r1, r2) => {
                ctx.beginPath();
                ctx.moveTo(x, y);
                ctx.lineTo(x + r1, y);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo(x, y);
                ctx.lineTo(x, y + r2);
                ctx.stroke();
            };
            drawCorner(70, 70, 150, 150);
            drawCorner(884 + 70, 70, -150, 150);
            drawCorner(70, 1225 + 70, 150, -150);
            drawCorner(884 + 70, 1225 + 70, -150, -150);

            // MAIN TITLE
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 120px "Playfair Display", serif';
            ctx.textAlign = 'center';
            ctx.shadowColor = 'rgba(0,0,0,0.6)';
            ctx.shadowBlur = 35;
            ctx.fillText(title, 512, 550);

            // SUBTITLE
            ctx.font = 'italic 50px "Poppins", sans-serif';
            ctx.fillStyle = '#fbbf24'; // Gold subtitle
            ctx.fillText('Self-Publishing Reimagined', 512, 650);

            // LOGO ICON
            ctx.font = '90px BoxIcons';
            ctx.fillStyle = 'white';
            ctx.fillText('A', 512, 850);

            return new THREE.CanvasTexture(canvas);
        }

        const edgeTex = createPageEdgeTexture();
        const pageContentTex = createPageTexture();

        const coverPhysMat = new THREE.MeshPhysicalMaterial({
            color: 0x1e1b4b,
            roughness: 0.1, metalness: 0.2,
            clearcoat: 1.0, clearcoatRoughness: 0.05
        });
        const frontPhysMat = new THREE.MeshPhysicalMaterial({
            map: createCoverTexture('Alpha Book'),
            roughness: 0.1, metalness: 0.2,
            clearcoat: 1.0, clearcoatRoughness: 0.05
        });
        const pageEdgeMat = new THREE.MeshStandardMaterial({ map: edgeTex, color: 0xffffff, roughness: 0.7 });
        const pageFaceMat = new THREE.MeshStandardMaterial({ map: pageContentTex, color: 0xffffff, roughness: 0.4 });

        // Back Cover 
        const backCover = new THREE.Mesh(new THREE.BoxGeometry(w + overhang, h + overhang * 2, 0.1), coverPhysMat);
        backCover.position.set(w / 2, 0, -d / 2);
        backCover.castShadow = true;
        backCover.receiveShadow = true;
        bookGroup.add(backCover);

        // Spine
        const spineGeo = new THREE.CylinderGeometry(d / 2, d / 2, h + overhang * 2, 32, 1, false, Math.PI / 2, Math.PI);
        const spine = new THREE.Mesh(spineGeo, coverPhysMat);
        spine.rotation.x = Math.PI;
        spine.castShadow = true;
        spine.receiveShadow = true;
        bookGroup.add(spine);

        const coverPivot = new THREE.Group();
        coverPivot.position.set(0, 0, d / 2);
        bookGroup.add(coverPivot);

        const frontCover = new THREE.Mesh(new THREE.BoxGeometry(w + overhang, h + overhang * 2, 0.1), frontPhysMat);
        frontCover.position.set(w / 2, 0, 0);
        frontCover.castShadow = true;
        frontCover.receiveShadow = true;
        coverPivot.add(frontCover);

        // Realistic Page Block
        const pageGroup = new THREE.Group();
        bookGroup.add(pageGroup);

        const sideGeo = new THREE.BoxGeometry(w, h, d - 0.1);
        const sideMats = [
            pageEdgeMat, // x+
            pageFaceMat, // x- 
            pageEdgeMat, // y+
            pageEdgeMat, // y-
            pageFaceMat, // z+ (With Text!)
            pageFaceMat  // z- (With Text!)
        ];
        const pageBlock = new THREE.Mesh(sideGeo, sideMats);
        pageBlock.position.set(w / 2, 0, 0);
        pageBlock.receiveShadow = true;
        pageGroup.add(pageBlock);

        const pages = [];
        for (let i = 0; i < 4; i++) {
            const pPivot = new THREE.Group();
            pPivot.position.set(0.05, 0, d / 2 - 0.1 - (i * 0.04));
            bookGroup.add(pPivot);
            const pMesh = new THREE.Mesh(new THREE.PlaneGeometry(w - 0.05, h - 0.05), pageFaceMat);
            pMesh.position.set((w - 0.05) / 2, 0, 0);
            pMesh.castShadow = true;
            pPivot.add(pMesh);
            pages.push(pPivot);
        }

        // --- SCROLLTRIGGER PINNING ARCHITECTURE ---
        const tl = gsap.timeline({
            scrollTrigger: {
                trigger: "#book-section",
                start: "top top",
                end: "bottom bottom",
                scrub: 1,
                pin: "#alpha-book-viewer",
                anticipatePin: 1
            }
        });

        // Step 1: Open Cover & Shift Left
        tl.to(bookGroup.position, { x: -1.0, duration: 1, ease: "none" })
            .to(bookGroup.rotation, { y: 0.1, duration: 1, ease: "none" }, 0)
            .to(coverPivot.rotation, { y: -Math.PI * 0.9, duration: 1, ease: "none" }, 0)
            .to("#text-1", { opacity: 0, y: -20, duration: 0.3 }, 0.1)
            .to("#text-2", { opacity: 1, y: 0, duration: 0.3 }, 0.5);

        // Step 2: First Page Flip
        tl.to(pages[0].rotation, { y: -Math.PI * 0.85, duration: 1, ease: "none" })
            .to("#text-2", { opacity: 0, y: -20, duration: 0.3 }, "-=0.8")
            .to("#text-3", { opacity: 1, y: 0, duration: 0.3 }, "-=0.3");

        // Step 3: Second Page Flip
        tl.to(pages[1].rotation, { y: -Math.PI * 0.82, duration: 1, ease: "none" });

        // Step 4: Close & Show CTA
        tl.to(pages.map(p => p.rotation), { y: 0, duration: 0.8, ease: "none", stagger: 0.1 })
            .to(coverPivot.rotation, { y: 0, duration: 0.8, ease: "none" }, "-=0.6")
            .to(bookGroup.position, { x: 0, duration: 0.8, ease: "none" }, "-=0.6")
            .to(bookGroup.rotation, { y: 0, duration: 0.8, ease: "none" }, "-=0.8")
            .to("#text-3", { opacity: 0, y: -20, duration: 0.3 }, "-=0.8")
            .to("#cta-block", { opacity: 1, y: 0, duration: 0.5, ease: "none" }, "-=0.2");

        // --- REMOVED OBSERVER LOGIC ---
        // ScrollTrigger pinning handles everything now.



        // --- FINAL LIGHTING POLISH ---
        const accentLight = new THREE.PointLight(0xa855f7, 1.5, 12);
        accentLight.position.set(-4, 4, 6);
        scene.add(accentLight);

        function animate() {
            requestAnimationFrame(animate);
            const time = Date.now() * 0.0008; // Slower, more elegant time

            // Continuous smooth floating
            const floatY = Math.sin(time * 1.2) * 0.2;
            bookGroup.position.y = floatY;

            // Subtle rotation sway
            bookGroup.rotation.z = Math.sin(time * 0.8) * 0.04;
            bookGroup.rotation.x = 0.05 + Math.cos(time * 0.5) * 0.03;

            // Hybrid Shadow Logic: procedural + real-time
            const shadowDist = 1 + (Math.abs(floatY) * 0.8);
            contactShadow.scale.set(shadowDist, shadowDist, 1);
            contactShadow.material.opacity = 0.5 - (Math.abs(floatY) * 0.6);

            // Real shadow structure plane
            shadowPlane.material.opacity = 0.06 - (Math.abs(floatY) * 0.04);

            // Camera subtle follow
            camera.position.y = floatY * 0.1;

            renderer.render(scene, camera);
        }
        animate();

        window.addEventListener('resize', () => {
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        });
        // --- TICKER ANIMATION (DELAYED UNTIL VIEW) ---
        const tickerTrack = document.getElementById('ticker-track');
        let tickerPos = 0;
        const speed = 0.5;
        let isAnimatingTicker = false;
        let tickerInterval;
        let tickerRAF;

        function updateTicker() {
            if (!isAnimatingTicker) {
                tickerPos -= speed;
                const trackWidth = tickerTrack.offsetWidth / 3;
                if (Math.abs(tickerPos) >= trackWidth) {
                    tickerPos = 0;
                }
                tickerTrack.style.transform = `translateX(${tickerPos}px)`;
            }
            tickerRAF = requestAnimationFrame(updateTicker);
        }

        function pushTicker() {
            if (isAnimatingTicker) return;
            isAnimatingTicker = true;
            const pushAmount = 480;

            gsap.to(tickerTrack, {
                x: `-=${pushAmount}`,
                duration: 1.2,
                ease: "power2.inOut",
                onUpdate: function () {
                    tickerPos = gsap.getProperty(tickerTrack, "x");
                },
                onComplete: () => {
                    isAnimatingTicker = false;
                    const trackWidth = tickerTrack.offsetWidth / 3;
                    if (Math.abs(tickerPos) >= trackWidth) {
                        tickerPos += trackWidth;
                        gsap.set(tickerTrack, { x: tickerPos });
                    }
                }
            });
        }

        // Only start animations when section is reached
        ScrollTrigger.create({
            trigger: ".featured-books-section",
            start: "top 80%",
            onEnter: () => {
                if (!tickerRAF) updateTicker();
                if (!tickerInterval) tickerInterval = setInterval(pushTicker, 5000);
            },
            onLeaveBack: () => {
                cancelAnimationFrame(tickerRAF);
                clearInterval(tickerInterval);
                tickerRAF = null;
                tickerInterval = null;
            }
        });

        // Animation for Feedback Cards
        gsap.to(".feedback-card-home", {
            scrollTrigger: {
                trigger: ".home-feedback-section",
                start: "top 75%",
                toggleActions: "play none none none"
            },
            opacity: 1,
            y: 0,
            duration: 0.8,
            stagger: 0.2,
            ease: "power2.out"
        });

    </script>
</body>

</html>