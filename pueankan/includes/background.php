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

<!-- Load Background CSS -->
<link rel="stylesheet" href="../assets/css/background.css">

<!-- Load Background JS -->
<script src="../assets/js/background.js"></script>

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
</style>
