// Dashboard Enhancements JavaScript

// 1. Loading State Management
document.addEventListener('DOMContentLoaded', function() {
    // Show loading overlay on page navigation
    const links = document.querySelectorAll('a[href^="/doctor"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.target || this.target === '_self') {
                showLoadingOverlay();
            }
        });
    });
});

function showLoadingOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay show';
    overlay.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(overlay);
}

// 2. Keyboard Shortcuts
document.addEventListener('keydown', function(e) {
    // Alt + A: View appointments
    if (e.altKey && e.key === 'a') {
        e.preventDefault();
        window.location.href = '/doctor/appointments';
    }
    
    // Alt + N: Add note
    if (e.altKey && e.key === 'n') {
        e.preventDefault();
        window.location.href = '/doctor/notes/create';
    }
    
    // Alt + P: View profile
    if (e.altKey && e.key === 'p') {
        e.preventDefault();
        window.location.href = '/doctor/profile';
    }
    
    // Alt + H: Go to dashboard (home)
    if (e.altKey && e.key === 'h') {
        e.preventDefault();
        window.location.href = '/doctor/dashboard';
    }
});

// 3. Stats Card Animations
const statsCards = document.querySelectorAll('.stats-card');
statsCards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        card.style.transition = 'all 0.5s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, index * 100);
});

// 4. Smooth Scroll for Internal Links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// 5. Auto-refresh Stats (every 5 minutes)
setInterval(function() {
    const statsNumbers = document.querySelectorAll('.stats-number');
    statsNumbers.forEach(stat => {
        stat.style.transition = 'transform 0.3s ease';
        stat.style.transform = 'scale(1.1)';
        setTimeout(() => {
            stat.style.transform = 'scale(1)';
        }, 300);
    });
}, 300000);

// 6. Notification Pulse Click Handler
document.querySelectorAll('.notification-pulse').forEach(pulse => {
    pulse.parentElement.style.cursor = 'pointer';
    pulse.parentElement.addEventListener('click', function() {
        const label = this.querySelector('.stats-label').textContent;
        if (label.includes('Pending')) {
            window.location.href = '/doctor/appointments?status=pending';
        } else if (label.includes('Today')) {
            window.location.href = '/doctor/appointments?date=today';
        }
    });
});
