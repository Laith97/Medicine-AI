// Head-to-Toe Assessment Normal Checkbox Handler
document.addEventListener('DOMContentLoaded', function() {
    // Get all normal checkboxes
    const normalCheckboxes = document.querySelectorAll('.section-normal-checkbox');

    // Add event listener to each checkbox
    normalCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const sectionContentId = this.getAttribute('data-section');
            const sectionContent = document.getElementById(sectionContentId);

            if (this.checked) {
                // If checked, hide the section content using vanilla JavaScript
                sectionContent.style.display = 'none';

                // Clear all inputs in this section
                const inputs = sectionContent.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = false;
                    } else if (input.tagName === 'SELECT') {
                        input.selectedIndex = 0;
                    } else {
                        input.value = '';
                    }
                });

                // Add a hidden input to indicate this section is normal
                const sectionId = this.id.replace('-normal', '');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = sectionId + '_status';
                hiddenInput.value = 'normal';
                hiddenInput.id = sectionId + '_status';
                sectionContent.parentNode.appendChild(hiddenInput);
            } else {
                // If unchecked, show the section content using vanilla JavaScript
                sectionContent.style.display = 'block';

                // Remove the hidden input if it exists
                const sectionId = this.id.replace('-normal', '');
                const hiddenInput = document.getElementById(sectionId + '_status');
                if (hiddenInput) {
                    hiddenInput.parentNode.removeChild(hiddenInput);
                }
            }
        });
    });
});
