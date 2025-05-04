<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if user is already logged in
if(is_logged_in()) {
    // Redirect to dashboard
    header("Location: dashboard.php");
    exit;
}

// Set page title
$page_title = "Login";

// Include header
include 'includes/header.php';
?>

<!-- Login Form Section -->
<section class="auth-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-form-container">
                <h2>Welcome Back!</h2>
                <p class="auth-subtitle">Log in to continue your language learning journey</p>
                
                <?php if(isset($_GET['registered']) && $_GET['registered'] == 'true'): ?>
                <div class="alert alert-success">
                    Registration successful! Please log in with your new account.
                </div>
                <?php endif; ?>
                
                <?php if(isset($_GET['logout']) && $_GET['logout'] == 'true'): ?>
                <div class="alert alert-info">
                    You have been successfully logged out.
                </div>
                <?php endif; ?>
                
                <div id="login-error" class="alert alert-danger" style="display: none;"></div>
                
                <form id="loginForm" method="post" action="login_process.php">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                            <span class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Log In</button>
                </form>
                
                <div class="auth-separator">
                    <span>or</span>
                </div>
                
                <div class="social-login">
                    <button class="btn btn-google">
                        <i class="fab fa-google"></i> Continue with Google
                    </button>
                    <button class="btn btn-facebook">
                        <i class="fab fa-facebook-f"></i> Continue with Facebook
                    </button>
                </div>
                
                <div class="auth-footer">
                    Don't have an account? <a href="signup.php">Sign up</a>
                </div>
            </div>
            
            <div class="auth-image">
                <img src="img/CerveLingua_Avatar.png" alt="Login Illustration">
                <div class="auth-testimonial">
                    <p>"CerveLingua helped me become conversational in Spanish in just 3 months. The camera learning feature is revolutionary!"</p>
                    <div class="testimonial-author">
                        <img src="img/rock.webp" alt="User Avatar">
                        <div>
                            <h4>Sarah Johnson</h4>
                            <span>Spanish Learner</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Create login_process.php file for form processing -->
<script>
    // Toggle password visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>