import React from 'react';
import { createRoot } from 'react-dom/client';
import RealTimeTranscript from './components/RealTimeTranscript';
import AmbientAudioRecorder from './components/AmbientAudioRecorder';

// Function to initialize the React components in the voice assistant page
function initializeVoiceAssistantComponents() {
    // Check if we're on the voice assistant page
    const container = document.querySelector('[data-session-id]');
    if (!container) {
        console.log('Not on voice assistant page, skipping React component initialization');
        return;
    }

    // Initialize RealTimeTranscript component if its container exists
    const transcriptContainer = document.getElementById('react-transcript-container');
    if (transcriptContainer) {
        const root = createRoot(transcriptContainer);
        root.render(<RealTimeTranscript />);
    }

    // Initialize AmbientAudioRecorder component if its container exists
    const recorderContainer = document.getElementById('react-audio-recorder-container');
    if (recorderContainer) {
        // Get visitId and authToken from data attributes or global variables
        const visitId = container.getAttribute('data-session-id') || window.sessionId || '';
        const authToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        const root = createRoot(recorderContainer);
        root.render(
            <AmbientAudioRecorder 
                visitId={visitId} 
                authToken={authToken} 
            />
        );
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeVoiceAssistantComponents);

// Also try to initialize when the page is ready (in case DOM is already loaded)
if (document.readyState === 'loading') {
    // Still loading, DOMContentLoaded will fire
} else {
    // DOM is already ready, initialize immediately
    initializeVoiceAssistantComponents();
}