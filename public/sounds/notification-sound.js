// Notification Sound System
// Check if NotificationSound is already defined
if (typeof NotificationSound === 'undefined') {
    class NotificationSound {
    constructor() {
        this.audioContext = null;
        this.enabled = true;
        this.volume = 0.5;
        this.init();
    }

    init() {
        // Check if Web Audio API is supported
        if (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined') {
            this.audioContext = new (AudioContext || webkitAudioContext)();
        }

        // Check user preference for sound
        this.enabled = localStorage.getItem('notificationSoundEnabled') !== 'false';
    }

    play() {
        if (!this.enabled) return;

        try {
            if (this.audioContext) {
                // Use Web Audio API for better control
                this.playTone();
            } else {
                // Fallback to HTML5 audio
                this.playAudioFile();
            }
        } catch (error) {
            console.warn('Failed to play notification sound:', error);
        }
    }

    playTone() {
        if (!this.audioContext) return;

        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);

        // Create a pleasant notification sound
        oscillator.frequency.setValueAtTime(800, this.audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(400, this.audioContext.currentTime + 0.1);

        gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(this.volume * 0.3, this.audioContext.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.001, this.audioContext.currentTime + 0.1);

        oscillator.start(this.audioContext.currentTime);
        oscillator.stop(this.audioContext.currentTime + 0.1);
    }

    playAudioFile() {
        const audio = new Audio('/sounds/notification.mp3');
        audio.volume = this.volume;
        audio.play().catch(e => console.warn('Audio play failed:', e));
    }

    setEnabled(enabled) {
        this.enabled = enabled;
        localStorage.setItem('notificationSoundEnabled', enabled);
    }

    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
    }
}

// Initialize notification sound
window.notificationSound = new NotificationSound();

// Integrate with enhanced notification system
if (typeof window.enhancedNotificationSystem !== 'undefined') {
    const originalPlayNotificationSound = window.enhancedNotificationSystem.playNotificationSound;
    
    window.enhancedNotificationSystem.playNotificationSound = function() {
        console.log('🔊 Playing notification sound using enhanced system');
        try {
            // Use the notification sound if available
            if (window.notificationSound && typeof window.notificationSound.play === 'function') {
                window.notificationSound.play();
                console.log('✅ Sound played successfully using notification sound');
            } else {
                // Fallback to original method
                originalPlayNotificationSound.call(this);
            }
        } catch (error) {
            console.error('❌ Sound error:', error);
            // Try fallback method
            this.playFallbackSound();
        }
    };
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationSound;
}
} // Close the if statement that checks for NotificationSound definition
