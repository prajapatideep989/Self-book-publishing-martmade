/**
 * Alpha Book - Cinematic Intro Experience
 * Style: Apple Product Launch / Hollywood Studio Reveal
 * Technology: Three.js, GSAP
 */

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('canvas-container');
    if (!container) return;

    // --- SCENE SETUP ---
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(35, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 0, 15);

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.2;
    container.appendChild(renderer.domElement);

    // --- PREMIUM MATERIALS ---
    const createTexture = (text, isCover = false, isPage = false) => {
        const canvas = document.createElement('canvas');
        canvas.width = 1024; canvas.height = 1400;
        const ctx = canvas.getContext('2d');

        if (isCover) {
            ctx.fillStyle = '#0a0a0a'; // Matte Black
            ctx.fillRect(0, 0, 1024, 1400);

            // Typography
            ctx.fillStyle = "white";
            ctx.textAlign = "center";
            ctx.font = "italic bold 80px 'Playfair Display', serif";
            ctx.fillText("ALPHA BOOK", 512, 700);

            // Subtle embossed effect
            ctx.strokeStyle = "rgba(255,255,255,0.1)";
            ctx.lineWidth = 2;
            ctx.strokeText("ALPHA BOOK", 514, 702);
        } else if (isPage) {
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, 1024, 1400);

            // Subtle parchment texture
            for (let i = 0; i < 3000; i++) {
                ctx.fillStyle = `rgba(0,0,0,${Math.random() * 0.01})`;
                ctx.fillRect(Math.random() * 1024, Math.random() * 1400, 1, 1);
            }

            if (text) {
                ctx.fillStyle = "#111";
                ctx.textAlign = "center";
                ctx.font = "italic 40px 'Playfair Display', serif";
                const lines = Array.isArray(text) ? text : [text];
                lines.forEach((line, i) => {
                    ctx.fillText(line, 512, 600 + (i * 80));
                });
            }
        }
        return new THREE.CanvasTexture(canvas);
    };

    const matteBlackMat = new THREE.MeshPhysicalMaterial({
        color: 0x111111,
        roughness: 0.6,
        metalness: 0.1,
        reflectivity: 0.2,
        clearcoat: 0.05
    });

    const coverArtMat = new THREE.MeshPhysicalMaterial({
        map: createTexture("ALPHA BOOK", true),
        roughness: 0.4,
        metalness: 0.2,
        clearcoat: 0.1
    });

    const goldEdgeMat = new THREE.MeshStandardMaterial({
        color: 0xd4af37,
        metalness: 0.9,
        roughness: 0.2
    });

    const page1Mat = new THREE.MeshStandardMaterial({
        map: createTexture("Welcome to Alpha Book", false, true),
        roughness: 0.8,
        side: THREE.DoubleSide
    });

    const page2Mat = new THREE.MeshStandardMaterial({
        map: createTexture(["Share your thoughts.", "Shape your stories.", "Discover imagination."], false, true),
        roughness: 0.8,
        side: THREE.DoubleSide
    });

    // --- BOOK MODEL ---
    const bookGroup = new THREE.Group();
    scene.add(bookGroup);

    const w = 4.5, h = 6.4, d = 0.5;

    // Back Cover
    const backCover = new THREE.Mesh(new THREE.BoxGeometry(w, h, 0.08), matteBlackMat);
    backCover.position.z = -d / 2;
    bookGroup.add(backCover);

    // Spine
    const spine = new THREE.Mesh(new THREE.BoxGeometry(0.15, h, d), matteBlackMat);
    spine.position.x = -w / 2;
    bookGroup.add(spine);

    // Gold Pages (The block of pages)
    const pagesBlock = new THREE.Mesh(new THREE.BoxGeometry(w - 0.1, h - 0.1, d - 0.1), goldEdgeMat);
    pagesBlock.position.x = 0.05;
    bookGroup.add(pagesBlock);

    // Front Cover Pivot
    const frontCoverPivot = new THREE.Group();
    frontCoverPivot.position.set(-w / 2, 0, d / 2);
    bookGroup.add(frontCoverPivot);

    const frontCover = new THREE.Mesh(new THREE.BoxGeometry(w, h, 0.08), coverArtMat);
    frontCover.position.x = w / 2;
    frontCoverPivot.add(frontCover);

    // Individual Pages
    const p1Pivot = new THREE.Group();
    p1Pivot.position.set(-w / 2 + 0.02, 0, d / 2 - 0.02);
    bookGroup.add(p1Pivot);
    const p1Mesh = new THREE.Mesh(new THREE.PlaneGeometry(w - 0.2, h - 0.2), page1Mat);
    p1Mesh.position.x = (w - 0.2) / 2;
    p1Pivot.add(p1Mesh);

    const p2Pivot = new THREE.Group();
    p2Pivot.position.set(-w / 2 + 0.02, 0, d / 2 - 0.04);
    bookGroup.add(p2Pivot);
    const p2Mesh = new THREE.Mesh(new THREE.PlaneGeometry(w - 0.2, h - 0.2), page2Mat);
    p2Mesh.position.x = (w - 0.2) / 2;
    p2Pivot.add(p2Mesh);

    // --- LIGHTING ---
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.1);
    scene.add(ambientLight);

    const rimLight = new THREE.SpotLight(0xffffff, 100);
    rimLight.position.set(-10, 10, -10);
    scene.add(rimLight);

    const mainLight = new THREE.SpotLight(0xffffff, 150);
    mainLight.position.set(10, 15, 20);
    mainLight.castShadow = true;
    mainLight.shadow.mapSize.width = 2048;
    mainLight.shadow.mapSize.height = 2048;
    scene.add(mainLight);

    // Volumetric Light Effect (Simplified God Ray simulation with a sprite/cone)
    const coneGeometry = new THREE.CylinderGeometry(0.1, 5, 20, 32, 1, true);
    const coneMaterial = new THREE.MeshBasicMaterial({
        color: 0xffffff,
        transparent: true,
        opacity: 0.05,
        side: THREE.DoubleSide,
        blending: THREE.AdditiveBlending
    });
    const ray = new THREE.Mesh(coneGeometry, coneMaterial);
    ray.position.set(0, 15, 0);
    ray.rotation.x = Math.PI;
    scene.add(ray);

    // --- PARTICLES ---
    const partCount = 500;
    const partGeo = new THREE.BufferGeometry();
    const partPos = new Float32Array(partCount * 3);
    for (let i = 0; i < partCount * 3; i++) {
        partPos[i] = (Math.random() - 0.5) * 20;
    }
    partGeo.setAttribute('position', new THREE.BufferAttribute(partPos, 3));
    const partMat = new THREE.PointsMaterial({
        color: 0xffffff,
        size: 0.02,
        transparent: true,
        opacity: 0.3,
        blending: THREE.AdditiveBlending
    });
    const particles = new THREE.Points(partGeo, partMat);
    scene.add(particles);

    // --- ANIMATION TIMELINE ---
    const tl = gsap.timeline({
        onComplete: () => {
            // Flash to white and redirect
            gsap.to("#flash-overlay", {
                opacity: 1,
                duration: 1.5,
                ease: "power2.in",
                onComplete: () => window.location.href = "index.php"
            });
        }
    });

    // 1. Initial State
    gsap.set(bookGroup.scale, { x: 0, y: 0, z: 0 });
    gsap.set(bookGroup.rotation, { y: Math.PI });

    // 2. Start (Darkness to Fade In)
    tl.to(ray.material, { opacity: 0.15, duration: 3, ease: "power1.inOut" });

    // 3. Book Formations
    tl.to(bookGroup.scale, { x: 1, y: 1, z: 1, duration: 4, ease: "expo.out" }, "-=1");
    tl.to(bookGroup.rotation, { y: 0.2, x: 0.1, duration: 5, ease: "power2.inOut" }, "-=4");

    // 4. Camera Pushes Forward
    tl.to(camera.position, { z: 10, duration: 6, ease: "sine.inOut" }, "-=5");

    // 5. Open Book
    tl.to(frontCoverPivot.rotation, { y: -Math.PI * 0.85, duration: 3, ease: "power2.inOut" }, "+=1");

    // 6. Text Sequence 1 Reveal (on first page)
    tl.to("#text-sequence-1", { opacity: 1, duration: 1.5 }, "-=1");
    tl.to("#text-sequence-1", { opacity: 0, duration: 1, delay: 2 });

    // 7. Pages Flip
    tl.to(p1Pivot.rotation, { y: -Math.PI * 0.8, duration: 2.5, ease: "power1.inOut" });

    // 8. Text Sequence 2 Reveal
    tl.to("#text-sequence-2", { opacity: 1, duration: 1.5 }, "-=1");

    // 9. Camera Zoom In Deeply
    tl.to(camera.position, { z: 4, duration: 4, ease: "power2.in" }, "+=1");

    // --- AUDIO & SFX ---
    const playSFX = (type) => {
        // Simplified SFX: Use an oscillator or a placeholder sound if available
        // In a real studio product, we'd have external .wav files
        console.log("SFX Triggered:", type);
    };

    const speak = (text) => {
        if (!window.speechSynthesis) return;
        const speech = new SpeechSynthesisUtterance(text);
        speech.rate = 0.8;
        speech.pitch = 0.8;
        speech.volume = 1.0;
        const voices = window.speechSynthesis.getVoices();
        // Look for premium sounding voices
        const premiumVoice = voices.find(v => v.name.includes('Premium') || v.name.includes('Enhanced')) || voices[0];
        speech.voice = premiumVoice;
        window.speechSynthesis.speak(speech);
    };

    const startExperience = () => {
        document.getElementById('start-overlay').style.opacity = '0';
        setTimeout(() => {
            document.getElementById('start-overlay').style.display = 'none';
            tl.play(); // Start the timeline
        }, 1000);

        const music = document.getElementById('ambient-music');
        if (music) {
            music.volume = 0.2;
            music.play().catch(e => console.log("Audio block"));
        }

        // SYNC VOICE WITH TIMELINE
        setTimeout(() => speak("Welcome to Alpha Book."), 4000);
        setTimeout(() => speak("Where stories come alive."), 10000);
        setTimeout(() => speak("This is more than a book. This is your imagination in motion."), 15000);
    };

    // Initialize timeline paused
    tl.pause();
    document.getElementById('start-overlay').addEventListener('click', startExperience, { once: true });
    // Maybe show a "Click to Experience" overlay if needed, or just auto-start if allowed

    // --- RENDER LOOP ---
    function animate() {
        requestAnimationFrame(animate);

        // Particles drift
        particles.rotation.y += 0.001;
        particles.position.y += Math.sin(Date.now() * 0.001) * 0.002;

        renderer.render(scene, camera);
    }
    animate();

    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
});
