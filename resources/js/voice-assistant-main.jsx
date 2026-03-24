import React from 'react';
import { createRoot } from 'react-dom/client';
import RealTimeTranscript from './components/RealTimeTranscript';
import AmbientAudioRecorder from './components/AmbientAudioRecorder';

// Store references to prevent duplicate root creation
const componentRoots = {};

// Helper function to get regional default language (moved to top-level for better hoisting)
function getRegionalDefaultLanguage() {
    // Return 'auto' to enable automatic language detection
    // The backend will use Whisper to detect the actual spoken language
    return 'en-US'; // Default to English for UI, backend will auto-detect
}

// Function to initialize the React components in the voice assistant page
function initializeVoiceAssistantComponents() {
    // Check if we're on the voice assistant page
    const container = document.querySelector('[data-session-id]');
    if (!container) {
        // console.log('Not on voice assistant page, skipping React component initialization');
        return;
    }

    // Initialize RealTimeTranscript component if its container exists
    const transcriptContainer = document.getElementById('react-transcript-container');

    // Function to render the transcript with current language
    const renderTranscript = (language) => {
        if (transcriptContainer && componentRoots['transcript']) {
            const actualLanguage = language === 'auto' ? getRegionalDefaultLanguage().substring(0, 2) : language;
            componentRoots['transcript'].render(<RealTimeTranscript language={actualLanguage} />);
        }
    };

    if (transcriptContainer) {
        if (!componentRoots['transcript']) {
            const root = createRoot(transcriptContainer);
            componentRoots['transcript'] = root;

            // Get initial language
            const languageSelector = document.getElementById('languageSelector');
            const initialLanguage = languageSelector ? languageSelector.value : 'auto';
            const actualLanguage = initialLanguage === 'auto' ? getRegionalDefaultLanguage().substring(0, 2) : initialLanguage;

            root.render(<RealTimeTranscript language={actualLanguage} />);
        }
    }

    // Initialize AmbientAudioRecorder component if its container exists
    const recorderContainer = document.getElementById('react-audio-recorder-container');
    if (recorderContainer) {
        const visitId = container.getAttribute('data-session-id') || window.sessionId || '';
        const authToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const renderRecorder = (language) => {
            const actualLanguage = language === 'auto' ? getRegionalDefaultLanguage().substring(0, 2) : language;
            if (componentRoots['recorder']) {
                componentRoots['recorder'].render(
                    <AmbientAudioRecorder
                        visitId={visitId}
                        authToken={authToken}
                        language={actualLanguage}
                    />
                );
            }
        };

        if (!componentRoots['recorder']) {
            const root = createRoot(recorderContainer);
            componentRoots['recorder'] = root;

            const languageSelector = document.getElementById('languageSelector');
            const initialLanguage = languageSelector ? languageSelector.value : 'auto';
            renderRecorder(initialLanguage);

            // Listen for language changes
            if (languageSelector && !window._langListenerAdded) {
                languageSelector.addEventListener('change', (e) => {
                    const newLang = e.target.value;
                    renderRecorder(newLang);
                    renderTranscript(newLang);
                });
                window._langListenerAdded = true;
            }
        }
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