/**
 * Alpha Book 3D - Interactive Scroll Animation
 * Powered by Three.js & GSAP
 */

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('alpha-book-canvas-container');
    if (!container) return;

    // --- SCENE SETUP ---
    const scene = new THREE.Scene();
    // scene.background = new THREE.Color(0xfdf4ff); // Very light purple

    const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 0, 5);

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    container.appendChild(renderer.domElement);

    // --- LIGHTING ---
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);

    const pointLight = new THREE.PointLight(0xa855f7, 2, 10); // Purple glow
    pointLight.position.set(2, 3, 4);
    scene.add(pointLight);

    const softLight = new THREE.DirectionalLight(0xffffff, 0.5);
    softLight.position.set(-2, 2, 2);
    scene.add(softLight);

    // --- BOOK MATERIALS ---
    const purpleGradient = new THREE.MeshStandardMaterial({
        color: 0x4f46e5,
        roughness: 0.3,
        metalness: 0.2
    });

    const pageMaterial = new THREE.MeshStandardMaterial({
        color: 0xffffff,
        roughness: 0.8
    });

    // --- TEXTURES (Canvas for Cover) ---
    const createCanvasTexture = (text) => {
        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 768;
        const ctx = canvas.getContext('2d');

        // Gradient background
        const grd = ctx.createLinearGradient(0, 0, 0, 768);
        grd.addColorStop(0, "#4f46e5"); // Dark Purple
        grd.addColorStop(1, "#a855f7"); // Light Purple
        ctx.fillStyle = grd;
        ctx.fillRect(0, 0, 512, 768);

        // White highlight
        ctx.fillStyle = "rgba(255, 255, 255, 0.1)";
        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.lineTo(512, 0);
        ctx.lineTo(0, 300);
        ctx.fill();

        // Text
        ctx.fillStyle = "white";
        ctx.font = "bold 60px Poppins, Arial";
        ctx.textAlign = "center";
        ctx.fillText(text, 256, 384);

        const texture = new THREE.CanvasTexture(canvas);
        return texture;
    };

    const coverTexture = createCanvasTexture("Alpha Book");
    const coverMaterial = new THREE.MeshStandardMaterial({ map: coverTexture });

    // --- BOOK GEOMETRY ---
    const bookGroup = new THREE.Group();
    scene.add(bookGroup);

    const bookWidth = 2;
    const bookHeight = 3;
    const bookThickness = 0.4;

    // Cover (Back)
    const backCover = new THREE.Mesh(new THREE.BoxGeometry(bookWidth, bookHeight, 0.05), purpleGradient);
    backCover.position.z = -bookThickness / 2;
    bookGroup.add(backCover);

    // Spine
    const spine = new THREE.Mesh(new THREE.BoxGeometry(0.1, bookHeight, bookThickness), purpleGradient);
    spine.position.x = -bookWidth / 2;
    bookGroup.add(spine);

    // Pages (Closed block)
    const pagesBlock = new THREE.Mesh(new THREE.BoxGeometry(bookWidth - 0.1, bookHeight - 0.1, bookThickness - 0.05), pageMaterial);
    pagesBlock.position.x = 0.05;
    bookGroup.add(pagesBlock);

    // Front Cover (Pivot from Spine)
    const frontCoverPivot = new THREE.Group();
    frontCoverPivot.position.x = -bookWidth / 2;
    bookGroup.add(frontCoverPivot);

    const frontCover = new THREE.Mesh(new THREE.BoxGeometry(bookWidth, bookHeight, 0.05), coverMaterial);
    frontCover.position.x = bookWidth / 2;
    frontCover.position.z = bookThickness / 2;
    frontCoverPivot.add(frontCover);

    // Individual Pages for Flipping
    const pages = [];
    for (let i = 0; i < 3; i++) {
        const pagePivot = new THREE.Group();
        pagePivot.position.x = -bookWidth / 2 + 0.05;
        pagePivot.position.z = (bookThickness / 2) - 0.05 - (i * 0.02);
        bookGroup.add(pagePivot);

        const page = new THREE.Mesh(new THREE.PlaneGeometry(bookWidth - 0.1, bookHeight - 0.1), pageMaterial);
        page.position.x = (bookWidth - 0.1) / 2;
        page.rotation.y = 0;
        pagePivot.add(page);
        pages.push(pagePivot);
    }

    // Shadow
    const shadowGeo = new THREE.CircleGeometry(1.5, 32);
    const shadowMat = new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.1 });
    const shadow = new THREE.Mesh(shadowGeo, shadowMat);
    shadow.rotation.x = -Math.PI / 2;
    shadow.position.y = -2;
    scene.add(shadow);

    // Tilt camera slightly
    bookGroup.rotation.y = 0.2;
    bookGroup.rotation.x = 0.1;

    // --- ANIMATION LOOP ---
    function animate() {
        requestAnimationFrame(animate);

        // Subtle floating
        const time = Date.now() * 0.001;
        bookGroup.position.y = Math.sin(time) * 0.1;
        shadow.scale.set(1 + Math.sin(time) * 0.05, 1 + Math.sin(time) * 0.05, 1);

        renderer.render(scene, camera);
    }
    animate();

    // --- SCROLL ANIMATIONS ---
    gsap.registerPlugin(ScrollTrigger);

    const tl = gsap.timeline({
        scrollTrigger: {
            trigger: "#alpha-book-canvas-container",
            start: "top top",
            end: "+=6000",
            scrub: 2,
            pin: true,
            // markers: true
        }
    });

    // 1. Open Cover
    tl.to(frontCoverPivot.rotation, { y: -Math.PI * 0.8, duration: 2 })
        .to(camera.position, { z: 4, duration: 1 }, "-=1");

    // 2. Fade in Welcome Text
    tl.to("#overlay-text-1", { opacity: 1, y: 0, duration: 1 });

    // 3. Flip Pages
    pages.forEach((p, i) => {
        tl.to(p.rotation, { y: -Math.PI * 0.75, duration: 1.5 }, `+=${i * 0.2}`);
    });

    // 4. Zoom in more
    tl.to(camera.position, { z: 3, y: 0.5, duration: 2 }, "-=1");
    tl.to(bookGroup.rotation, { x: 0.2, y: 0, duration: 2 }, "-=2");

    // 5. Fade in Second Text
    tl.to("#overlay-text-2", { opacity: 1, y: 0, duration: 1 });

    // 6. Close Book
    tl.to(pages.map(p => p.rotation), { y: 0, duration: 2, stagger: 0.1 }, "+=1")
        .to(frontCoverPivot.rotation, { y: 0, duration: 2 }, "-=1.5")
        .to(camera.position, { z: 5, y: 0, duration: 2 }, "-=1");

    // --- RESIZE HANDLER ---
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
});
