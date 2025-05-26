<?php
// Include configuration
require_once "includes/config.php";
require_once "includes/functions.php";

// Set page title
$page_title = "Home";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CerveLingua - Unlock Your Language Potential</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/scrollreveal"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
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
                <a href="#features" class="nav-link">Features</a>
                <a href="#how-it-works" class="nav-link">How It Works</a>
                <a href="#languages" class="nav-link">Spanish</a>
                <a href="#testimonials" class="nav-link">Testimonials</a>
                <a href="#pricing" class="nav-link">Pricing</a>
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

    <!-- Hero Section - Update hero image -->
    <div class="wave-effect">
    <div class="wave wave1"></div>
    <div class="wave wave2"></div>
    <div class="wave wave3"></div>
    <div class="wave wave4"></div>
</div>
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="animated-title">Learn Spanish <span class="gradient-text">Smarter</span>, Not Harder with AI</h1>
                <p class="hero-subtitle">CerveLingua combines neuroscience and AI to create a personalized Spanish learning experience that adapts to your brain's unique learning style.</p>
                <div class="hero-cta">
                    <a href="signup.php" class="btn btn-primary btn-lg">Start Learning Free</a>
                    <a href="#how-it-works" class="btn btn-video">
                        <i class="fas fa-play-circle"></i>
                        See how it works
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">5+</span>
                        <span class="stat-text">Games</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">100+</span>
                        <span class="stat-text">Active Learners</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">90%</span>
                        <span class="stat-text">Success Rate</span>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <div class="floating-elements">
                    <img src="img/CerveLingua_Avatar.png" alt="CerveLingua App" class="main-image">
                    <div class="floating-card card-1">
                        <i class="fas fa-brain"></i>
                        <span>Gamified-learning</span>
                    </div>
                    <div class="floating-card card-2">
                        <i class="fas fa-robot"></i>
                        <span>AI-Powered</span>
                    </div>
                    <div class="floating-card card-3">
                        <i class="fas fa-chart-line"></i>
                        <span>Progress Tracking</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="wave-divider">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="#ffffff" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,149.3C960,160,1056,160,1152,138.7C1248,117,1344,75,1392,53.3L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
    </section>

    <!-- Trusted By Section -->
    <section class="trusted-by">
        <div class="container">
            <h2>Sponsored by a leading organization</h2>
            <div class="logos-container">
                <img src="img/htl_anich.png" alt="Company 1" class="company-logo">
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose <span class="gradient-text">CerveLingua</span>?</h2>
                <p>Our platform is designed with your brain in mind, making language learning efficient and enjoyable.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Neuroscience-Based</h3>
                    <p>Our methods are built on the latest research in cognitive science and memory formation.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>AI-Powered Learning</h3>
                    <p>Our AI adapts to your learning style, focusing on what you need most.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headphones"></i>
                    </div>
                    <h3>Immersive Audio</h3>
                    <p>Train your ear with native speakers and perfect your pronunciation.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <h3>Gamified Experience</h3>
                    <p>Learn through fun challenges and earn rewards as you progress.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community Learning</h3>
                    <p>Practice with other learners and get feedback from native speakers.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Learn Anywhere</h3>
                    <p>Seamlessly switch between devices without losing your progress.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works">
        <div class="container">
            <div class="section-header">
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <h1>How <span class="gradient-text">CerveLingua</span> Works</h1>
                <p>Our 4-step process makes language learning effective and enjoyable</p>
            </div>
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Assessment</h3>
                        <p>We analyze your current level, learning style, and goals to create your personalized plan.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Personalized Learning</h3>
                        <p>Our AI creates a custom curriculum that adapts to your progress and focuses on your needs.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Practice & Feedback</h3>
                        <p>Engage with interactive exercises and receive instant feedback on your performance.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="wave-divider inverted">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="#f8f9fa" fill-opacity="1" d="M0,160L48,170.7C96,181,192,203,288,202.7C384,203,480,181,576,165.3C672,149,768,139,864,154.7C960,171,1056,213,1152,218.7C1248,224,1344,192,1392,176L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
    </section>

    <!-- Languages Section - Update to show only Spanish -->
    <section id="languages" class="languages">
        <div class="container">
            <div class="section-header">
                <h2>Spanish Learning Excellence</h2>
                <p>We specialize exclusively in Spanish to provide the highest quality learning experience</p>
            </div>
            <div class="languages-grid">
                <div class="language-card" style="grid-column: span 3;">
                    <img src="img/spain.webp" alt="Spanish">
                    <h3>Spanish</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials">
        <div class="container">
            <div class="section-header">
                <h2>What Our Users Say</h2>
                <p>Join thousands of satisfied language learners</p>
            </div>
            <div class="testimonial-slider">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p>CerveLingua helped me become conversational in Spanish in just 3 months. The personalized approach made all the difference!</p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/testimonials/user-1.jpg" alt="Sarah M.">
                        <div class="author-info">
                            <h4>Sarah M.</h4>
                            <p>Learning Spanish</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p>As someone who struggled with traditional language classes, CerveLingua's approach was a game-changer. I'm finally making progress with Japanese!</p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/testimonials/user-2.jpg" alt="David K.">
                        <div class="author-info">
                            <h4>David K.</h4>
                            <p>Learning Japanese</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <div class="quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p>I've tried many language apps, but CerveLingua is the only one that actually helped me retain what I learned. The neuroscience approach works!</p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/testimonials/user-3.jpg" alt="Elena R.">
                        <div class="author-info">
                            <h4>Elena R.</h4>
                            <p>Learning French</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="testimonial-controls">
                <button class="prev-btn"><i class="fas fa-chevron-left"></i></button>
                <div class="testimonial-dots">
                    <span class="dot active"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
                <button class="next-btn"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>

    <!-- Pricing Section - Update to reflect Spanish-only focus -->
    <section id="pricing" class="pricing">
        <div class="container">
            <div class="section-header">
                <h2>Simple, Transparent Pricing</h2>
                <p>Choose the plan that fits your learning goals</p>
            </div>
            <div class="pricing-grid">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Free</h3>
                        <div class="price">
                            <span class="currency">$</span>
                            <span class="amount">0</span>
                            <span class="period">/month</span>
                        </div>
                    </div>
                    <div class="pricing-features">
                        <ul>
                            <li><i class="fas fa-check"></i> Basic Spanish learning modules</li>
                            <li><i class="fas fa-check"></i> Limited daily exercises</li>
                            <li><i class="fas fa-check"></i> Basic progress tracking</li>
                            <li class="disabled"><i class="fas fa-times"></i> Personalized learning path</li>
                            <li class="disabled"><i class="fas fa-times"></i> Advanced speech recognition</li>
                            <li class="disabled"><i class="fas fa-times"></i> Community features</li>
                        </ul>
                    </div>
                    <div class="pricing-cta">
                        <a href="signup.php" class="btn btn-outline btn-block">Get Started</a>
                    </div>
                </div>
                <!-- Add other pricing cards if needed -->
            </div>
        </div>
    </section>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/script.js"></script>
</body>
</html>