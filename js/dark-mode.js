document.addEventListener('DOMContentLoaded', function() {
    // Check for saved theme preference or use device preference
    const savedTheme = localStorage.getItem('theme') || 
                      (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    
    // Apply the theme
    document.body.classList.toggle('dark-mode', savedTheme === 'dark');
    
    // Update toggle if it exists
    const toggle = document.getElementById('dark-mode-toggle');
    if (toggle) {
        toggle.checked = savedTheme === 'dark';
        
        // Add event listener to toggle
        toggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        });
    }
});