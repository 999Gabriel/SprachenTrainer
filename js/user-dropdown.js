document.addEventListener('DOMContentLoaded', function() {
    // User profile dropdown functionality
    const userProfile = document.querySelector('.user-profile');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (userProfile && dropdownMenu) {
        // Toggle dropdown when clicking on user profile
        userProfile.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent event from bubbling up
            dropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking elsewhere on the page
        document.addEventListener('click', function(e) {
            if (!userProfile.contains(e.target) && dropdownMenu.classList.contains('show')) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
    
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('show');
        });
    }
});