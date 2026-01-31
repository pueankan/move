<!-- 
    Theme Switcher Component
    Light/Dark Mode Toggle
-->
<div class="theme-switcher">
    <button class="theme-btn theme-light-btn active" onclick="switchBackgroundTheme('light')" title="Light Theme">
        <i class="fas fa-sun"></i>
    </button>
    <button class="theme-btn theme-dark-btn" onclick="switchBackgroundTheme('dark')" title="Dark Theme">
        <i class="fas fa-moon"></i>
    </button>
</div>

<style>
.theme-switcher {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
    display: flex;
    gap: 10px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    padding: 8px;
    border-radius: 50px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

body.theme-dark .theme-switcher {
    background: rgba(30, 30, 30, 0.9);
    border-color: rgba(255, 255, 255, 0.2);
}

.theme-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    position: relative;
    overflow: hidden;
}

.theme-light-btn {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #ff6b00;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.theme-dark-btn {
    background: linear-gradient(135deg, #1a1a2e, #2d2d44);
    color: #64b5f6;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.theme-btn:hover {
    transform: translateY(-3px) scale(1.1);
}

.theme-btn.active {
    transform: scale(1.15);
}

.theme-light-btn.active {
    box-shadow: 0 6px 25px rgba(255, 215, 0, 0.6);
    animation: pulseLight 2s ease-in-out infinite;
}

.theme-dark-btn.active {
    box-shadow: 0 6px 25px rgba(100, 181, 246, 0.6);
    animation: pulseDark 2s ease-in-out infinite;
}

@keyframes pulseLight {
    0%, 100% {
        box-shadow: 0 6px 25px rgba(255, 215, 0, 0.6);
    }
    50% {
        box-shadow: 0 6px 35px rgba(255, 215, 0, 0.9);
    }
}

@keyframes pulseDark {
    0%, 100% {
        box-shadow: 0 6px 25px rgba(100, 181, 246, 0.6);
    }
    50% {
        box-shadow: 0 6px 35px rgba(100, 181, 246, 0.9);
    }
}

.theme-btn i {
    transition: transform 0.3s ease;
}

.theme-btn:hover i {
    transform: rotate(20deg) scale(1.2);
}

.theme-btn.active i {
    animation: spin 10s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .theme-switcher {
        bottom: 20px;
        right: 20px;
        padding: 6px;
    }
    
    .theme-btn {
        width: 45px;
        height: 45px;
        font-size: 1.1rem;
    }
}
</style>

<script>
// Update active button on theme change
const originalSwitchTheme = window.switchBackgroundTheme;
window.switchBackgroundTheme = function(theme) {
    // Call original function
    if (originalSwitchTheme) {
        originalSwitchTheme(theme);
    }
    
    // Update active state
    const lightBtn = document.querySelector('.theme-light-btn');
    const darkBtn = document.querySelector('.theme-dark-btn');
    
    if (theme === 'light') {
        lightBtn.classList.add('active');
        darkBtn.classList.remove('active');
    } else {
        darkBtn.classList.add('active');
        lightBtn.classList.remove('active');
    }
    
    // Update main.css colors
    updateMainColors(theme);
};

// Update main CSS variables based on theme
function updateMainColors(theme) {
    const root = document.documentElement;
    
    if (theme === 'light') {
        root.style.setProperty('--text-light', '#000000');
        root.style.setProperty('--text-gray', 'rgba(0, 0, 0, 0.7)');
        
        // Update navbar
        const navbar = document.getElementById('main-navbar');
        if (navbar) {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.borderBottom = '2px solid rgba(255, 107, 0, 0.3)';
        }
        
        // Update cards
        document.querySelectorAll('.content-wrapper, .card-custom').forEach(el => {
            el.style.background = 'rgba(255, 255, 255, 0.9)';
            el.style.color = '#000000';
        });
    } else {
        root.style.setProperty('--text-light', '#ffffff');
        root.style.setProperty('--text-gray', 'rgba(255, 255, 255, 0.8)');
        
        // Restore dark theme
        const navbar = document.getElementById('main-navbar');
        if (navbar) {
            navbar.style.background = 'rgba(26, 26, 46, 0.95)';
        }
        
        document.querySelectorAll('.content-wrapper, .card-custom').forEach(el => {
            el.style.background = 'rgba(26, 26, 46, 0.8)';
            el.style.color = '#ffffff';
        });
    }
}
</script>