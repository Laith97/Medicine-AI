// Simple notification sound using Web Audio API
// This creates a pleasant notification beep sound programmatically
class NotificationSound {
    constructor() {
        this.audioContext = null;
        this.enabled = true;
        this.initAudioContext();
    }

    initAudioContext() {
        try {
            // Create AudioContext (cross-browser compatible)
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioContext = new AudioContext();
        } catch (e) {
            console.warn('Web Audio API not supported', e);
        }
    }

    async play() {
        if (!this.enabled || !this.audioContext) {
            return;
        }

        try {
            // Resume AudioContext if suspended (required by many browsers)
            if (this.audioContext.state === 'suspended') {
                await this.audioContext.resume();
            }

            // Create a pleasant two-tone notification sound
            this.playBeep(800, 0.1, 0.1);  // First tone
            setTimeout(() => this.playBeep(1000, 0.1, 0.1), 200); // Second tone
        } catch (e) {
            console.warn('Could not play notification sound', e);
        }
    }

    playBeep(frequency, duration, volume = 0.1) {
        if (!this.audioContext) return;

        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);

        oscillator.frequency.setValueAtTime(frequency, this.audioContext.currentTime);
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(volume, this.audioContext.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.001, this.audioContext.currentTime + duration);

        oscillator.start(this.audioContext.currentTime);
        oscillator.stop(this.audioContext.currentTime + duration);
    }

    setEnabled(enabled) {
        this.enabled = enabled;
    }
}

// Create global instance
window.notificationSound = new NotificationSound();
