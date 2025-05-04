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
$page_title = "Sign Up";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CerveLingua - Sign Up</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    
<?php
// Include header
include 'includes/header.php';
?>

    <!-- Signup Container -->
    <br>
    <br>
    <br>
    <div class="login-container"> <!-- Reusing login container style -->
        <div class="login-card"> <!-- Reusing login card style -->
            <div class="login-header">
                <img src="img/Generiertes Bild.jpeg" alt="CerveLingua Logo" class="login-logo">
                <h2>Create Your Account</h2>
                <p>Start your Spanish learning journey today</p>
            </div>
            <div class="login-body">
                 <!-- Error message container -->
                <div id="signup-error" class="alert alert-danger" style="display: none;"></div>

                <form id="signupForm"> <!-- Removed action and method -->
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" placeholder="Choose a username" required>
                        </div>
                    </div>
                     <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Create a password (min. 6 characters)" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        </div>
                    </div>
                    <!-- Optional: Add First Name / Last Name fields if needed -->
                    <!-- <div class="form-group"> ... </div> -->

                    <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
                </form>
                <div class="login-link"> <!-- Changed class from signup-link -->
                    <p>Already have an account? <a href="login.php">Log in</a></p>
                </div>
            </div>
        </div>
    </div>

<?php
// Include footer
include 'includes/footer.php';
?>

<!-- Include the signup JavaScript -->
<script src="js/signup.js"></script> <!-- Make sure this file exists and contains the fetch logic targeting register.php -->

</body>
</html>