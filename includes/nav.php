<nav class="navbar ipad-style">
    <div class="container">
        <div class="logo">
            <a href="index.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                <img src="img/Generiertes Bild.jpeg" alt="CerveLingua Logo">
                <span>CerveLingua</span>
            </a>
        </div>
        <div class="nav-links">
            <!-- Links relevant to logged-in user -->
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="lessons.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'lessons.php' ? 'active' : ''; ?>">Lessons</a>
            <a href="practice.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'practice.php' ? 'active' : ''; ?>">Practice</a>
            <a href="ai-conversation.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ai-conversation.php' ? 'active' : ''; ?>">AI Conversation</a>
            <a href="camera-learning.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'camera-learning.php' ? 'active' : ''; ?>">Visual Learning</a>
        </div>
        <div class="cta-buttons user-menu-container">
            <!-- <div class="theme-toggle">
                <i class="fas fa-sun sun-icon"></i>
                <label>
                    <input type="checkbox" id="dark-mode-toggle">
                    <span class="slider"></span>
                </label>
                <i class="fas fa-moon moon-icon"></i>
            </div>
            -->
            <div class="user-profile">
                <?php if(!empty($user['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image">
                <?php else: ?>
                    <div class="profile-initial"><?php echo strtoupper(substr(htmlspecialchars($user['username'] ?? 'U'), 0, 1)); ?></div>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($user['username'] ?? 'User'); ?></span>
                <i class="fas fa-chevron-down"></i>
                <!-- Dropdown Menu -->
                <div class="dropdown-menu">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
        <div class="menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</nav>
<script src="js/dark-mode.js"></script>
<script src="js/user-dropdown.js"></script>
<script src="js/mobile-nav.js"></script>
