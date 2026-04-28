<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alpha Book | Cinematic Experience</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/0.160.0/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@300;400&display=swap"
        rel="stylesheet">
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: #000;
            font-family: 'Inter', sans-serif;
        }

        #intro-container {
            width: 100%;
            height: 100%;
            position: relative;
        }

        #canvas-container {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .overlay-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #fff;
            opacity: 0;
            pointer-events: none;
            z-index: 10;
        }

        .overlay-text h1 {
            font-family: 'Playfair Display', serif;
            font-size: 4rem;
            letter-spacing: 0.2rem;
            margin: 0;
            text-transform: uppercase;
            font-weight: 700;
        }

        .overlay-text p {
            font-size: 1.5rem;
            font-weight: 300;
            margin-top: 1rem;
            opacity: 0.8;
            max-width: 600px;
        }

        #start-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            cursor: pointer;
            transition: opacity 1s;
        }

        #start-overlay h2 {
            color: #fff;
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            letter-spacing: 0.5rem;
            text-transform: uppercase;
            margin-bottom: 2rem;
        }

        .play-icon {
            width: 80px;
            height: 80px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s;
        }

        #start-overlay:hover .play-icon {
            background: #fff;
            border-color: #fff;
        }

        #start-overlay:hover .play-icon svg {
            fill: #000;
        }

        .play-icon svg {
            width: 30px;
            height: 30px;
            fill: #fff;
            margin-left: 5px;
        }

        #flash-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #fff;
            opacity: 0;
            z-index: 1100;
            pointer-events: none;
        }

        /* Skip button if needed, otherwise lean into the cinema experience */
        #skip-btn {
            position: absolute;
            bottom: 30px;
            right: 30px;
            color: rgba(255, 255, 255, 0.4);
            text-decoration: none;
            font-size: 0.8rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            z-index: 110;
            transition: color 0.3s;
        }

        #skip-btn:hover {
            color: #fff;
        }
    </style>
</head>

<body>

    <div id="intro-container">
        <div id="start-overlay">
            <h2>Experience Alpha</h2>
            <div class="play-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
            </div>
        </div>

        <div id="canvas-container"></div>

        <div id="text-sequence-1" class="overlay-text">
            <h1>Welcome to Alpha Book</h1>
        </div>

        <div id="text-sequence-2" class="overlay-text">
            <p>Share your thoughts. Shape your stories. <br> Discover imagination.</p>
        </div>

        <div id="flash-overlay"></div>
        <a href="index.php" id="skip-btn">Skip Intro</a>
    </div>

    <!-- Audio Elements (Placeholders or synthesized) -->
    <audio id="ambient-music" loop>
        <!-- User could provide a path, or we use a public slow-burn ambient track -->
        <source src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3" type="audio/mpeg">
    </audio>

    <script src="alpha_intro.js"></script>
</body>

</html>