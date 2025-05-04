<?php
// Include configuration
require_once "includes/config.php";
require_once "includes/functions.php";

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    // Redirect to dashboard
    header("Location: dashboard.php");
    exit;
}

// Set page title
$page_title = "Forgot Password";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CerveLingua - Forgot Password</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation - Modern Floating iPad Style -->
    <nav class="navbar ipad-style">
        <div class="container">
            <div class="logo">
                <img src="img/Generiertes Bild.jpeg" alt="CerveLingua Logo">
                <span>CerveLingua</span>
            </div>
            <div class="nav-links">
                <a href="index.php#features" class="nav-link">Features</a>
                <a href="index.php#how-it-works" class="nav-link">How It Works</a>
                <a href="index.php#languages" class="nav-link">Spanish</a>
                <a href="index.php#testimonials" class="nav-link">Testimonials</a>
                <a href="index.php#pricing" class="nav-link">Pricing</a>
            </div>
            <div class="cta-buttons">
                <a href="login.php" class="btn btn-outline">Log In</a>
                <a href="signup.php" class="btn btn-primary">Sign Up Free</a>
            </div>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Forgot Password Container -->
    <br>
    <br>
    <br>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="img/Generiertes Bild.jpeg" alt="CerveLingua Logo" class="login-logo">
                <h2>Forgot Password</h2>
                <p>Enter your email to reset your password</p>
            </div>
            <div class="login-body">
                <?php
                // Display error message if any
                if(isset($_SESSION['reset_error'])) {
                    echo '<div class="alert alert-danger">' . $_SESSION['reset_error'] . '</div>';
                    unset($_SESSION['reset_error']);
                }
                
                // Display success message if any
                if(isset($_SESSION['reset_success'])) {
                    echo '<div class="alert alert-success">' . $_SESSION['reset_success'] . '</div>';
                    unset($_SESSION['reset_success']);
                }
                ?>
                <form id="forgotPasswordForm" action="process_forgot_password.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                </form>
                <div class="signup-link">
                    <p>Remember your password? <a href="login.php">Log in</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-top">
                <div class="footer-logo">
                    <img src="img/Generiertes Bild.jpeg" alt="CerveLingua Logo">
                    <h3>CerveLingua</h3>
                    <p>Learn Spanish the way your brain works.</p>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                                                <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                                                <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                                            </div>
                                        </div>
                                        <div class="footer-links">
                                            <div class="footer-links-column">
                                                <h4>Company</h4>
                                                <ul>
                                                    <li><a href="#">About Us</a></li>
                                                    <li><a href="#">Careers</a></li>
                                                    <li><a href="#">Blog</a></li>
                                                    <li><a href="#">Press</a></li>
                                                </ul>
                                            </div>
                                            <div class="footer-links-column">
                                                <h4>Resources</h4>
                                                <ul>
                                                    <li><a href="#">Help Center</a></li>
                                                    <li><a href="#">Community</a></li>
                                                    <li><a href="#">Webinars</a></li>
                                                    <li><a href="#">Tutorials</a></li>
                                                </ul>
                                            </div>
                                            <div class="footer-links-column">
                                                <h4>Legal</h4>
                                                <ul>
                                                    <li><a href="#">Privacy Policy</a></li>
                                                    <li><a href="#">Terms of Service</a></li>
                                                    <li><a href="#">Cookie Policy</a></li>
                                                    <li><a href="#">GDPR</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="footer-bottom">
                                        <p>&copy; 2023 CerveLingua. All rights reserved.</p>
                                        <div class="language-selector">
                                            <i class="fas fa-globe"></i>
                                            <select>
                                                <option value="en">English</option>
                                                <option value="es">Español</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </footer>
                    <script src="js/forgot-password.js"></script>
                </body>
                </html>