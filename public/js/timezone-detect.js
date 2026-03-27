document.addEventListener('DOMContentLoaded', function() {
    // Get user's timezone
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    
    // Store in session via AJAX
    fetch('/timezone-detect', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({ timezone: timezone })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Timezone set to:', timezone);
        
        // Update any DateTimePicker components on the page
        const dateTimePickers = document.querySelectorAll('[data-flatpickr]');
        dateTimePickers.forEach(function(picker) {
            if (picker._flatpickr) {
                picker._flatpickr.set('timezone', timezone);
            }
        });
    })
    .catch(error => console.log('Timezone detection failed:', error));
});