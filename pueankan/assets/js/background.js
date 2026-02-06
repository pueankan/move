/**
 * Animated Background with Light/Dark Theme - UPDATED VERSION
 * Beautiful Particle System for Industrial Construction Theme
 * รองรับการสลับธีม Light/Dark แบบ Smooth
 */

(function () {
    'use strict';

    const canvas = document.getElementById('particle-canvas');
    if (!canvas) {
        console.error('Canvas element #particle-canvas not found!');
        return;
    }

    const ctx = canvas.getContext('2d');
    let particles = [];
    let animationId;
    let currentTheme = detectInitialTheme();
    let mouse = { x: null, y: null, radius: 150 };

    /**
     * ตรวจสอบ Theme เริ่มต้นจาก body class
     */
    function detectInitialTheme() {
        if (document.body.classList.contains('theme-light')) {
            return 'light';
        } else if (document.body.classList.contains('theme-dark')) {
            return 'dark';
        }
        // Default to dark if no class
        return 'dark';
    }

    /**
     * กำหนดสี Theme - ปรับให้เข้ากับ Industrial Construction
     */
    const themes = {
        light: {
            particles: [
                { r: 255, g: 107, b: 0 },      // Orange - Primary
                { r: 0, g: 173, b: 181 },      // Cyan - Secondary
                { r: 255, g: 193, b: 7 },      // Yellow - Accent
                { r: 100, g: 100, b: 100 },    // Gray
                { r: 255, g: 140, b: 0 }       // Dark Orange
            ],
            connection: 'rgba(255, 107, 0, 0.12)',
            glow: true,
            glowIntensity: 0.5,
            particleOpacity: 0.7
        },
        dark: {
            particles: [
                { r: 255, g: 107, b: 0 },      // Orange - Primary
                { r: 0, g: 173, b: 181 },      // Cyan - Secondary
                { r: 255, g: 193, b: 7 },      // Yellow - Accent
                { r: 147, g: 112, b: 219 },    // Purple
                { r: 50, g: 255, b: 150 }      // Mint
            ],
            connection: 'rgba(255, 255, 255, 0.1)',
            glow: true,
            glowIntensity: 0.8,
            particleOpacity: 0.5
        }
    };

    /**
     * Resize Canvas
     */
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    /**
     * Particle Class
     */
    class Particle {
        constructor(preservePosition = false) {
            if (preservePosition && this.x && this.y) {
                // Keep position when switching theme
                this.updateColor();
            } else {
                this.reset();
            }
            this.opacity = Math.random() * 0.6 + 0.3;
        }

        reset() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.size = Math.random() * 3 + 1;
            this.baseSize = this.size;

            // Floating movement
            this.vx = (Math.random() - 0.5) * 0.5;
            this.vy = (Math.random() - 0.5) * 0.5;

            // Pulse
            this.pulseSpeed = Math.random() * 0.02 + 0.01;
            this.pulsePhase = Math.random() * Math.PI * 2;

            // Color
            this.updateColor();

            // Opacity
            this.opacity = Math.random() * 0.6 + 0.3;
            this.baseOpacity = this.opacity;
        }

        updateColor() {
            const themeColors = themes[currentTheme].particles;
            this.color = themeColors[Math.floor(Math.random() * themeColors.length)];
        }

        update() {
            // Movement
            this.x += this.vx;
            this.y += this.vy;

            // Pulse effect
            this.pulsePhase += this.pulseSpeed;
            this.size = this.baseSize + Math.sin(this.pulsePhase) * 0.5;
            this.opacity = this.baseOpacity + Math.sin(this.pulsePhase) * 0.2;

            // Mouse interaction
            if (mouse.x !== null && mouse.y !== null) {
                const dx = mouse.x - this.x;
                const dy = mouse.y - this.y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < mouse.radius) {
                    const angle = Math.atan2(dy, dx);
                    const force = (mouse.radius - distance) / mouse.radius;
                    this.vx -= Math.cos(angle) * force * 0.5;
                    this.vy -= Math.sin(angle) * force * 0.5;
                }
            }

            // Damping
            this.vx *= 0.98;
            this.vy *= 0.98;

            // Keep speed reasonable
            const speed = Math.sqrt(this.vx * this.vx + this.vy * this.vy);
            if (speed > 2) {
                this.vx = (this.vx / speed) * 2;
                this.vy = (this.vy / speed) * 2;
            }

            // Wrap around edges
            if (this.x < -10) this.x = canvas.width + 10;
            if (this.x > canvas.width + 10) this.x = -10;
            if (this.y < -10) this.y = canvas.height + 10;
            if (this.y > canvas.height + 10) this.y = -10;
        }

        draw() {
            const themeSettings = themes[currentTheme];
            const alpha = Math.max(0, Math.min(1, this.opacity * themeSettings.particleOpacity));

            if (themeSettings.glow) {
                // Glow effect - adjust based on theme
                const glowSize = this.size * (currentTheme === 'light' ? 3 : 4);
                const glowIntensity = themeSettings.glowIntensity;

                const gradient = ctx.createRadialGradient(
                    this.x, this.y, 0,
                    this.x, this.y, glowSize
                );
                gradient.addColorStop(0, `rgba(${this.color.r}, ${this.color.g}, ${this.color.b}, ${alpha * glowIntensity})`);
                gradient.addColorStop(0.5, `rgba(${this.color.r}, ${this.color.g}, ${this.color.b}, ${alpha * glowIntensity * 0.5})`);
                gradient.addColorStop(1, `rgba(${this.color.r}, ${this.color.g}, ${this.color.b}, 0)`);

                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(this.x, this.y, glowSize, 0, Math.PI * 2);
                ctx.fill();
            }

            // Core particle
            ctx.fillStyle = `rgba(${this.color.r}, ${this.color.g}, ${this.color.b}, ${alpha})`;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    /**
     * Create particles
     */
    function createParticles(recreate = false) {
        if (recreate) {
            // Preserve positions when switching theme
            particles.forEach(particle => particle.updateColor());
        } else {
            particles = [];
            const density = (canvas.width * canvas.height) / 20000;
            const count = Math.min(Math.floor(density), 150); // Max 150 particles

            for (let i = 0; i < count; i++) {
                particles.push(new Particle());
            }
        }
    }

    /**
     * Connect nearby particles
     */
    function connectParticles() {
        const maxDistance = 120;
        const themeSettings = themes[currentTheme];

        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < maxDistance) {
                    const opacity = (1 - distance / maxDistance) * 0.3;
                    const connectionColor = themeSettings.connection.replace(/[\d.]+\)/, `${opacity})`);

                    ctx.strokeStyle = connectionColor;
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                }
            }
        }
    }

    /**
     * Animation loop
     */
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Update and draw particles
        particles.forEach(particle => {
            particle.update();
            particle.draw();
        });

        // Connect particles
        connectParticles();

        animationId = requestAnimationFrame(animate);
    }

    /**
     * Initialize
     */
    function init() {
        resizeCanvas();
        createParticles();
        animate();
        console.log('✨ Background animation initialized:', {
            theme: currentTheme,
            particles: particles.length,
            canvas: `${canvas.width}x${canvas.height}`
        });
    }

    /**
     * Switch theme - เรียกได้จากภายนอก
     */
    window.switchBackgroundTheme = function (theme) {
        if (theme !== 'light' && theme !== 'dark') {
            console.warn('❌ Invalid theme:', theme);
            return;
        }

        const oldTheme = currentTheme;
        currentTheme = theme;

        // Update body class
        document.body.classList.remove('theme-light', 'theme-dark');
        document.body.classList.add('theme-' + theme);

        // Update particle colors (preserve positions for smooth transition)
        createParticles(true);

        console.log('🎨 Theme switched:', oldTheme, '→', theme);
    };

    /**
     * Auto-detect theme changes from MutationObserver
     */
    const themeObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const newTheme = detectInitialTheme();
                if (newTheme !== currentTheme) {
                    console.log('🔄 Theme auto-detected:', newTheme);
                    window.switchBackgroundTheme(newTheme);
                }
            }
        });
    });

    // Start observing body class changes
    themeObserver.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });

    /**
     * Event Listeners
     */
    
    // Resize
    window.addEventListener('resize', () => {
        resizeCanvas();
        createParticles();
    });

    // Mouse tracking
    canvas.addEventListener('mousemove', (e) => {
        mouse.x = e.x;
        mouse.y = e.y;
    });

    canvas.addEventListener('mouseleave', () => {
        mouse.x = null;
        mouse.y = null;
    });

    // Touch support
    canvas.addEventListener('touchmove', (e) => {
        if (e.touches.length > 0) {
            mouse.x = e.touches[0].clientX;
            mouse.y = e.touches[0].clientY;
            e.preventDefault(); // Prevent scrolling
        }
    }, { passive: false });

    canvas.addEventListener('touchend', () => {
        mouse.x = null;
        mouse.y = null;
    });

    /**
     * Initialize when ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /**
     * Cleanup
     */
    window.addEventListener('beforeunload', () => {
        if (animationId) {
            cancelAnimationFrame(animationId);
        }
        themeObserver.disconnect();
    });

    /**
     * Performance monitoring (optional)
     */
    if (window.location.search.includes('debug')) {
        let frameCount = 0;
        let lastTime = performance.now();
        
        setInterval(() => {
            const now = performance.now();
            const fps = Math.round(frameCount / ((now - lastTime) / 1000));
            console.log(`FPS: ${fps}, Particles: ${particles.length}`);
            frameCount = 0;
            lastTime = now;
        }, 1000);
        
        const originalAnimate = animate;
        animate = function() {
            frameCount++;
            originalAnimate();
        };
    }

})();