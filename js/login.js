/**
 * CerveLingua Login Page JavaScript
 * Handles login form submission and validation
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const errorDiv = document.getElementById('login-error');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous error messages
            errorDiv.style.display = 'none';
            errorDiv.textContent = '';
            
            // Get form data
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;
            
            // Basic validation
            if (!username || !password) {
                errorDiv.textContent = 'Please enter both username and password';
                errorDiv.style.display = 'block';
                return;
            }
            
            // Send login request
            fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    password: password,
                    remember: remember
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect on successful login
                    window.location.href = 'dashboard.php';
                } else {
                    // Show error message
                    errorDiv.textContent = data.message || 'Login failed. Please try again.';
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorDiv.textContent = 'An error occurred. Please try again later.';
                errorDiv.style.display = 'block';
            });
        });
    }
});
