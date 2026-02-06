<!--
========================================
Animated Background Component
========================================
Particle Animation System for Hardware Store
ใช้ Canvas API สร้าง Particle Effect
========================================
-->

<!-- Background Container -->
<div id="animated-background">
    <canvas id="particles-canvas"></canvas>
    <div class="gradient-overlay"></div>
</div>

<style>
/* Inline Critical CSS */
#animated-background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    overflow: hidden;
}

#particles-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.gradient-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        135deg,
        rgba(26, 26, 46, 0.95) 0%,
        rgba(22, 33, 62, 0.9) 50%,
        rgba(26, 26, 46, 0.95) 100%
    );
    pointer-events: none;
}

/* Light Theme Override */
body.theme-light .gradient-overlay {
    background: linear-gradient(
        135deg,
        rgba(248, 249, 250, 0.95) 0%,
        rgba(233, 236, 239, 0.9) 50%,
        rgba(248, 249, 250, 0.95) 100%
    );
}
</style>

<script>
/**
 * Particle Background Animation
 * สร้าง Particle Effect แบบ Hardware/Industrial Theme
 */
(function() {
    const canvas = document.getElementById('particles-canvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    let particles = [];
    let animationId;
    
    // Configuration
    const config = {
        particleCount: 50,
        particleSpeed: 0.5,
        particleSize: 2,
        connectionDistance: 150,
        colors: {
            particle: 'rgba(255, 107, 0, 0.6)',
            line: 'rgba(0, 173, 181, 0.2)'
        }
    };
    
    // Resize canvas
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    
    // Particle class
    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.vx = (Math.random() - 0.5) * config.particleSpeed;
            this.vy = (Math.random() - 0.5) * config.particleSpeed;
            this.size = Math.random() * config.particleSize + 1;
        }
        
        update() {
            this.x += this.vx;
            this.y += this.vy;
            
            // Wrap around edges
            if (this.x < 0) this.x = canvas.width;
            if (this.x > canvas.width) this.x = 0;
            if (this.y < 0) this.y = canvas.height;
            if (this.y > canvas.height) this.y = 0;
        }
        
        draw() {
            ctx.fillStyle = config.colors.particle;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
        }
    }
    
    // Initialize particles
    function initParticles() {
        particles = [];
        for (let i = 0; i < config.particleCount; i++) {
            particles.push(new Particle());
        }
    }
    
    // Draw connections between particles
    function drawConnections() {
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < config.connectionDistance) {
                    const opacity = 1 - (distance / config.connectionDistance);
                    ctx.strokeStyle = config.colors.line.replace('0.2', opacity * 0.2);
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
        
        // Draw connections
        drawConnections();
        
        animationId = requestAnimationFrame(animate);
    }
    
    // Initialize
    resizeCanvas();
    initParticles();
    animate();
    
    // Handle resize
    window.addEventListener('resize', () => {
        resizeCanvas();
        initParticles();
    });
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        cancelAnimationFrame(animationId);
    });
})();
</script>