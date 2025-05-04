<?php
// Include configuration
require_once "config.php";
require_once "functions.php";

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Set default app name if not defined
if (!defined('APP_NAME')) {
    define('APP_NAME', 'CerveLingua');
}

// Set default app URL if not defined
if (!defined('APP_URL')) {
    // Determine the base URL dynamically
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    define('APP_URL', $protocol . '://' . $host);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . $site_name : $site_name; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/auth.css"> <!-- For login/signup pages -->
    
    <!-- Favicon -->
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Navigation - Modern Floating iPad Style -->
    <nav class="navbar ipad-style">
        <div class="container">
            <div class="logo">
                <a href="<?php echo APP_URL; ?>/index.php">
                    <img src="<?php echo APP_URL; ?>/img/Generiertes Bild.jpeg" alt="CerveLingua Logo">
                    <span>CerveLingua</span>
                </a>
            </div>
            <div class="nav-links">
                <?php if (is_logged_in()): ?>
                <a href="<?php echo APP_URL; ?>/dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
                <a href="<?php echo APP_URL; ?>/camera-learning.php" class="nav-link <?php echo ($current_page == 'camera-learning.php') ? 'active' : ''; ?>">Camera</a>
                <a href="<?php echo APP_URL; ?>/vocabulary.php" class="nav-link <?php echo ($current_page == 'vocabulary.php') ? 'active' : ''; ?>">Vocabulary</a>
                <a href="<?php echo APP_URL; ?>/minigames.php" class="nav-link <?php echo ($current_page == 'minigames.php') ? 'active' : ''; ?>">Games</a>
                <a href="<?php echo APP_URL; ?>/progress.php" class="nav-link <?php echo ($current_page == 'progress.php') ? 'active' : ''; ?>">Progress</a>
                <?php else: ?>
                <a href="<?php echo APP_URL; ?>/index.php#features" class="nav-link">Features</a>
                <a href="<?php echo APP_URL; ?>/index.php#how-it-works" class="nav-link">How It Works</a>
                <a href="<?php echo APP_URL; ?>/index.php#languages" class="nav-link">Spanish</a>
                <a href="<?php echo APP_URL; ?>/index.php#testimonials" class="nav-link">Testimonials</a>
                <a href="<?php echo APP_URL; ?>/index.php#pricing" class="nav-link">Pricing</a>
                <?php endif; ?>
            </div>
            <div class="cta-buttons">
                <?php if (is_logged_in()): ?>
                <a href="<?php echo APP_URL; ?>/profile.php" class="btn btn-outline">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="<?php echo APP_URL; ?>/logout.php" class="btn btn-primary">Log Out</a>
                <?php else: ?>
                <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-outline <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">Log In</a>
                <a href="<?php echo APP_URL; ?>/signup.php" class="btn btn-primary <?php echo ($current_page == 'signup.php') ? 'active' : ''; ?>">Sign Up Free</a>
                <?php endif; ?>
            </div>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>