// Minimal fix to ensure AI Analysis and Clinical Doc buttons are enabled after recording stops
(function() {
    'use strict';

    console.log('🔧 Button enabler fix loaded');

    // Function to enable buttons
    function enableAnalysisButtons() {
        const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
        const generateClinicalDocBtn = document.getElementById('generateClinicalDocBtn');

        console.log('Enabling analysis buttons...');

        if (generateAnalysisBtn) {
            generateAnalysisBtn.disabled = false;
            generateAnalysisBtn.style.opacity = '1';
            console.log('✅ Analysis button enabled');
        }

        if (generateClinicalDocBtn) {
            generateClinicalDocBtn.disabled = false;
            generateClinicalDocBtn.style.opacity = '1';
            console.log('✅ Clinical doc button enabled');
        }
    }

    // Listen for multiple events that indicate recording has stopped
    window.addEventListener('serverTranscriptReady', function() {
        console.log('📥 Server transcript ready - enabling buttons');
        enableAnalysisButtons();
    });

    window.addEventListener('statusUpdate', function(event) {
        if (event.detail.status === 'stopped') {
            console.log('⏹️ Recording stopped - enabling buttons');
            setTimeout(enableAnalysisButtons, 500); // Small delay to ensure state is set
        }
    });

    // Check on page load if there's already content
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const transcriptContainer = document.querySelector('.transcript-container');
            const hasContent = transcriptContainer && transcriptContainer.innerText.trim().length > 50;

            if (hasContent) {
                console.log('📄 Content detected on load - enabling buttons');
                enableAnalysisButtons();
            }
        }, 1000);
    });

    // Fallback: Check every 2 seconds if there's content and buttons are disabled
    setInterval(function() {
        const generateAnalysisBtn = document.getElementById('generateAnalysisBtn');
        const transcriptContainer = document.querySelector('.transcript-container');
        const hasContent = transcriptContainer && transcriptContainer.innerText.trim().length > 50;

        if (hasContent && generateAnalysisBtn && generateAnalysisBtn.disabled) {
            console.log('🔄 Fallback: Enabling buttons (content detected, buttons disabled)');
            enableAnalysisButtons();
        }
    }, 2000);
})();
