/**
 * Alpha Book - Precise Scroll Sequence
 * Sequence: Open -> Page 1 (Welcome) -> Page 2 (Thoughts) -> Close
 */

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('alpha-book-canvas-container');
    if (!container) return;

    console.log("Alpha Book: Initializing Scroll Animation...");

    // --- SCENE SETUP ---
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(35, container.clientWidth / container.clientHeight, 0.1, 1000);
    camera.position.set(0, 0, 10);

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.shadowMap.enabled = true;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    container.appendChild(renderer.domElement);

    // --- LIGHTING ---
    scene.add(new THREE.AmbientLight(0xffffff, 0.8));
    const spotlight = new THREE.SpotLight(0xffffff, 50);
    spotlight.position.set(5, 10, 10);
    spotlight.castShadow = true;
    scene.add(spotlight);

    // --- TEXTURE GENERATOR ---
    const createPageTexture = (text, isCover = false) => {
        const canvas = document.createElement('canvas');
        canvas.width = 1024; canvas.height = 1400;
        const ctx = canvas.getContext('2d');

        if (isCover) {
            ctx.fillStyle = '#4f46e5';
            ctx.fillRect(0, 0, 1024, 1400);
            ctx.fillStyle = "white";
            ctx.textAlign = "center";
            ctx.font = "bold 100px serif";
            ctx.fillText("ALPHA BOOK", 512, 700);
        } else {
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, 1024, 1400);
            ctx.fillStyle = "#1e293b";
            ctx.textAlign = "center";
            ctx.font = "60px serif";

            // Handle multi-line text
            const lines = Array.isArray(text) ? text : [text];
            lines.forEach((line, i) => ctx.fillText(line, 512, 600 + (i * 100)));
        }
        return new THREE.CanvasTexture(canvas);
    };

    // --- BOOK MODEL ---
    const bookGroup = new THREE.Group();
    scene.add(bookGroup);
    const w = 2.4, h = 3.4, d = 0.2;

    const coverMat = new THREE.MeshPhysicalMaterial({ map: createPageTexture("", true), roughness: 0.2 });
    const page1Mat = new THREE.MeshStandardMaterial({ map: createPageTexture("Welcome to Alpha Book"), side: THREE.DoubleSide });
    const page2Mat = new THREE.MeshStandardMaterial({ map: createPageTexture(["You can share your", "thoughts in Alpha Book"]), side: THREE.DoubleSide });

    // Front Cover
    const frontCoverPivot = new THREE.Group();
    frontCoverPivot.position.set(-w / 2, 0, d / 2);
    bookGroup.add(frontCoverPivot);
    const frontCover = new THREE.Mesh(new THREE.BoxGeometry(w, h, 0.05), coverMat);
    frontCover.position.x = w / 2;
    frontCoverPivot.add(frontCover);

    // Page 1
    const p1Pivot = new THREE.Group();
    p1Pivot.position.set(-w / 2 + 0.01, 0, d / 2 - 0.02);
    bookGroup.add(p1Pivot);
    const p1Mesh = new THREE.Mesh(new THREE.PlaneGeometry(w - 0.1, h - 0.1), page1Mat);
    p1Mesh.position.x = (w - 0.1) / 2;
    p1Pivot.add(p1Mesh);

    // Page 2
    const p2Pivot = new THREE.Group();
    p2Pivot.position.set(-w / 2 + 0.01, 0, d / 2 - 0.04);
    bookGroup.add(p2Pivot);
    const p2Mesh = new THREE.Mesh(new THREE.PlaneGeometry(w - 0.1, h - 0.1), page2Mat);
    p2Mesh.position.x = (w - 0.1) / 2;
    p2Pivot.add(p2Mesh);

    // Back Cover
    const backCover = new THREE.Mesh(new THREE.BoxGeometry(w, h, 0.05), coverMat);
    backCover.position.z = -d / 2;
    bookGroup.add(backCover);

    // --- SCROLL ANIMATION ---
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
        console.error("Alpha Book: GSAP or ScrollTrigger not found!");
        return;
    }

    gsap.registerPlugin(ScrollTrigger);
    const tl = gsap.timeline({
        scrollTrigger: {
            trigger: "#alpha-book-full-container",
            start: "top top",
            end: "+=3000",
            scrub: 1,
            pin: true,
            onRefresh: () => console.log("ScrollTrigger Refreshed"),
            onLeave: () => console.log("Animation Finished")
        }
    });

    // 1. Open Cover & Show Welcome
    tl.to(frontCoverPivot.rotation, { y: -Math.PI * 0.9, duration: 2 });
    tl.to("#overlay-text-1", { opacity: 1, y: 0, duration: 1 }, "-=1");

    // 2. Hide Welcome, Flip Page & Show Thoughts
    tl.to("#overlay-text-1", { opacity: 0, y: -20, duration: 0.8 }, "+=1");
    tl.to(p1Pivot.rotation, { y: -Math.PI * 0.85, duration: 2 });
    tl.to("#overlay-text-2", { opacity: 1, y: 0, duration: 1 }, "-=1");

    // 3. Flip Page 2 (Keep text 2 or transition)
    tl.to(p2Pivot.rotation, { y: -Math.PI * 0.8, duration: 2 }, "+=0.5");

    // 4. Close the Book (Hide text 2 and Reverse everything)
    tl.to("#overlay-text-2", { opacity: 0, y: -20, duration: 0.8 }, "+=1");
    tl.to(p2Pivot.rotation, { y: 0, duration: 1.5 });
    tl.to(p1Pivot.rotation, { y: 0, duration: 1.5 }, "-=1");
    tl.to(frontCoverPivot.rotation, { y: 0, duration: 1.5 }, "-=1");

    // Reveal Action Buttons at the very end
    tl.to("#home-action-buttons", { opacity: 1, pointerEvents: "auto", duration: 1 });

    function animate() {
        requestAnimationFrame(animate);
        renderer.render(scene, camera);
    }
    animate();

    window.addEventListener('resize', () => {
        renderer.setSize(container.clientWidth, container.clientHeight);
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        ScrollTrigger.refresh();
    });

    // Ensure everything is ready
    window.addEventListener('load', () => {
        console.log("Alpha Book: Page Loaded, refreshing ScrollTrigger");
        ScrollTrigger.refresh();
    });
});