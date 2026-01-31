/**
 * Animated Background with Light/Dark Theme
 * Beautiful Particle System inspired by Hearts, Stars & Modern Design
 */

(function () {
    'use strict';

    const canvas = document.getElementById('background-canvas');
    if (!canvas) {
        console.error('Canvas element not found!');
        return;
    }

    const ctx = canvas.getContext('2d');
    let particles = [];
    let animationId;
    let currentTheme = 'light'; // 'light' or 'dark'
    let mouse = { x: null, y: null, radius: 150 };

    // กำหนดสี Theme
    const themes = {
        light: {
            particles: [
                { r: 100, g: 100, b: 255 },    // Blue
                { r: 255, g: 100, b: 150 },    // Pink
                { r: 100, g: 200, b: 100 },    // Green
                { r: 255, g: 200, b: 0 },      // Yellow
                { r: 150, g: 100, b: 255 }     // Purple
            ],
            connection: 'rgba(100, 100, 255, 0.15)',
            glow: true
        },
        dark: {
            particles: [
                { r: 0, g: 173, b: 255 },      // Cyan
                { r: 255, g: 107, b: 0 },      // Orange
                { r: 255, g: 193, b: 7 },      // Gold
                { r: 147, g: 112, b: 219 },    // Purple
                { r: 50, g: 255, b: 150 }      // Mint
            ],
            connection: 'rgba(255, 255, 255, 0.1)',
            glow: true
        }
    };

    // Resize Canvas
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    // Particle Class
    class Particle {
        constructor() {
            this.reset();
            this.y = Math.random() * canvas.height;
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
            const themeColors = themes[currentTheme].particles;
            this.color = themeColors[Math.floor(Math.random() * themeColors.length)];

            // Opacity
            this.opacity = Math.random() * 0.6 + 0.3;
            this.baseOpacity = this.opacity;
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
            const alpha = Math.max(0, Math.min(1, this.opacity));

            if (themes[currentTheme].glow) {
                // Glow effect
                const gradient = ctx.createRadialGradient(
                    this.x, this.y, 0,
                    this.x, this.y, this.size * 4
                );
                gradient.addColorStop(0, `rgba(${this.color.r}, ${this.color.g}, ${this.color.b}, ${alpha * 0.6})`);
                gradient.addColorStop(0.5, `rgba(${this.color.r}, ${this.color.g}, ${this.color.b}, ${alpha * 0.3})`);
                gradient.addColorStop(1, `rgba(${this.color.r}, ${this.color.g}, ${this.color.b}, 0)`);

                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size * 4, 0, Math.PI * 2);
                ctx.fill();
            }

            // Core
            ctx.fillStyle = `rgba(${this.color.r}, ${this.color.g}, ${this.color.b}, ${alpha})`;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    // Create particles
    function createParticles() {
        particles = [];
        const density = (canvas.width * canvas.height) / 20000;
        const count = Math.min(Math.floor(density), 150); // Max 150 particles

        for (let i = 0; i < count; i++) {
            particles.push(new Particle());
        }
    }

    // Connect nearby particles
    function connectParticles() {
        const maxDistance = 120;

        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < maxDistance) {
                    const opacity = (1 - distance / maxDistance) * 0.3;

                    ctx.strokeStyle = themes[currentTheme].connection.replace('0.1', opacity.toString());
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                }
            }
        }
    }

    // Animation loop
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

    // Initialize
    function init() {
        resizeCanvas();
        createParticles();

        // Set initial theme
        document.body.classList.add('theme-' + currentTheme);

        animate();
        console.log('Background animation initialized with', particles.length, 'particles');
    }

    // Switch theme
    window.switchBackgroundTheme = function (theme) {
        if (theme !== 'light' && theme !== 'dark') {
            console.warn('Invalid theme:', theme);
            return;
        }

        currentTheme = theme;

        // Update body class
        document.body.classList.remove('theme-light', 'theme-dark');
        document.body.classList.add('theme-' + theme);

        // Recreate particles with new colors
        createParticles();

        console.log('Theme switched to:', theme);
    };

    // Event Listeners
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
        }
    });

    canvas.addEventListener('touchend', () => {
        mouse.x = null;
        mouse.y = null;
    });

    // Initialize when ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Cleanup
    window.addEventListener('beforeunload', () => {
        if (animationId) {
            cancelAnimationFrame(animationId);
        }
    });

})();