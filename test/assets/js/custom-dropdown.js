/**
 * Simple Custom Dropdown
 * A lightweight implementation that enhances select elements with custom styling
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get all custom select elements
    const customSelects = document.querySelectorAll('.custom-select');
    
    // Process each custom select
    customSelects.forEach(select => {
        // Create elements
        const selectWrapper = document.createElement('div');
        selectWrapper.className = 'select-wrapper';
        
        const selectedDisplay = document.createElement('div');
        selectedDisplay.className = 'selected-option';
        
        // Set initial selected text
        const selectedOption = select.options[select.selectedIndex];
        selectedDisplay.textContent = selectedOption ? selectedOption.textContent : 'Select an option';
        
        // Create dropdown container
        const dropdownContainer = document.createElement('div');
        dropdownContainer.className = 'custom-dropdown';
        
        
        // Add options to dropdown
        const optionsContainer = document.createElement('div');
        optionsContainer.className = 'dropdown-options';
        
        Array.from(select.options).forEach(option => {
            const optionElement = document.createElement('div');
            optionElement.className = 'dropdown-option';
            optionElement.textContent = option.textContent;
            
            if (option.disabled) {
                optionElement.classList.add('disabled');
            } else {
                if (option.selected) {
                    optionElement.classList.add('selected');
                }
                
                // Store data attributes
                if (option.hasAttribute('data-city-id')) {
                    optionElement.dataset.cityId = option.getAttribute('data-city-id');
                }
                optionElement.dataset.value = option.value;
                
                // Handle option selection
                optionElement.addEventListener('click', function() {
                    if (this.classList.contains('disabled')) return;
                    
                    // Update display
                    selectedDisplay.textContent = this.textContent;
                    
                    // Update original select
                    select.value = this.dataset.value;
                    
                    // Update selected class
                    optionsContainer.querySelectorAll('.dropdown-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.classList.add('selected');
                    
                    // Hide dropdown
                    dropdownContainer.classList.remove('show');
                    
                    // Trigger change event on the original select
                    const event = new Event('change', { bubbles: true });
                    select.dispatchEvent(event);
                });
            }
            
            optionsContainer.appendChild(optionElement);
        });
        
        dropdownContainer.appendChild(optionsContainer);
        
        
        // Toggle dropdown on click
        selectedDisplay.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownContainer.classList.toggle('show');
            
            if (dropdownContainer.classList.contains('show')) {
                // Show all options when dropdown opens
                optionsContainer.querySelectorAll('.dropdown-option').forEach(option => {
                    option.style.display = 'flex';
                });
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!selectWrapper.contains(e.target)) {
                dropdownContainer.classList.remove('show');
            }
        });
        
        // Hide the original select
        select.style.display = 'none';
        
        // Append elements to DOM
        selectWrapper.appendChild(selectedDisplay);
        selectWrapper.appendChild(dropdownContainer);
        select.parentNode.insertBefore(selectWrapper, select);
        selectWrapper.appendChild(select);
    });
});


