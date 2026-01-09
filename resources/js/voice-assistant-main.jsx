import React from 'react';
import { createRoot } from 'react-dom/client';
import RealTimeTranscript from './components/RealTimeTranscript';
import AmbientAudioRecorder from './components/AmbientAudioRecorder';

// Store references to prevent duplicate root creation
const componentRoots = {};

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
        // Check if root already exists for this container
        if (!componentRoots['transcript']) {
            const root = createRoot(transcriptContainer);
            componentRoots['transcript'] = root;
            root.render(<RealTimeTranscript />);
        }
    }

    // Initialize AmbientAudioRecorder component if its container exists
    const recorderContainer = document.getElementById('react-audio-recorder-container');
    if (recorderContainer) {
        // Check if root already exists for this container
        if (!componentRoots['recorder']) {
            // Get visitId and authToken from data attributes or global variables
            const visitId = container.getAttribute('data-session-id') || window.sessionId || '';
            const authToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const root = createRoot(recorderContainer);
            componentRoots['recorder'] = root;

            // Function to render the component with current language
            const renderRecorder = (language) => {
                // If language is 'auto', convert it to the regional language for the backend
                const actualLanguage = language === 'auto' ? getRegionalDefaultLanguage().substring(0, 2) : language;
                root.render(
                    <AmbientAudioRecorder
                        visitId={visitId}
                        authToken={authToken}
                        language={actualLanguage}
                    />
                );
            };

            // Get initial language
            const languageSelector = document.getElementById('languageSelector');
            const initialLanguage = languageSelector ? languageSelector.value : 'auto';

            // Initial render
            renderRecorder(initialLanguage);

            // Listen for language changes
            if (languageSelector) {
                languageSelector.addEventListener('change', (e) => {
                    renderRecorder(e.target.value);
                });
            }

            // Get regional default language (similar to the main script)
            function getRegionalDefaultLanguage() {
                try {
                    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    const userAgent = navigator.userAgent.toLowerCase();

                    // Arabic-speaking regions (Middle East, North Africa)
                    const arabicRegions = [
                        'asia/amman', 'asia/riyadh', 'asia/dubai', 'asia/kuwait', 'asia/qatar',
                        'asia/bahrain', 'asia/muscat', 'asia/beirut', 'asia/damascus', 'asia/baghdad',
                        'africa/cairo', 'africa/tunis', 'africa/algiers', 'africa/casablanca'
                    ];

                    // Check timezone
                    if (arabicRegions.some(region => timezone.toLowerCase().includes(region))) {
                        return 'ar-SA';
                    }

                    // Check browser language preferences
                    const browserLang = navigator.language || navigator.userLanguage;
                    if (browserLang && browserLang.startsWith('ar')) {
                        return 'ar-SA';
                    }

                    // Default to English for other regions
                    return 'en-US';
                } catch (error) {
                    console.warn('Language detection failed, defaulting to Arabic:', error);
                    return 'ar-SA'; // Safe default for this region
                }
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