// Dashboard functionality

// Fetch progress data from the API
function fetchProgress() {
    console.log("Fetching real progress data from database...");
    return fetch('api/get_user_progress.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log("Progress data received: ", data);
            return data;
        })
        .catch(error => {
            console.error("Error fetching progress data:", error);
            // Return default data in case of error
            return {
                level: 1,
                xp: 0,
                streak: 0,
                words_learned: 0
            };
        });
}

// Fetch achievements data from the API
function fetchAchievements() {
    console.log("Fetching real achievements data from database...");
    return fetch('api/get_user_achievements.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log("Achievements data received: ", data);
            return data;
        })
        .catch(error => {
            console.error("Error fetching achievements data:", error);
            // Return empty array in case of error
            return [];
        });
}

// Function to fetch user data for the avatar
function fetchUserData() {
    console.log("Fetching user data for avatar...");
    return fetch('api/get_user_data.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log("User data for avatar: ", data);
            return data;
        })
        .catch(error => {
            console.error("Error fetching user data for avatar:", error);
            // Return default data in case of error
            return {
                username: 'User',
                level: 1,
                xp: 0,
                streak: 0,
                words_learned: 0,
                achievements: []
            };
        });
}

// Function to initialize avatar interaction
function initializeAvatarInteraction() {
    const avatar = document.getElementById('interactive-avatar');
    const speechBubble = document.getElementById('avatar-speech-bubble');
    
    if (!avatar || !speechBubble) {
        console.error("Avatar or speech bubble elements not found!");
        return;
    }
    
    // Fetch user data when page loads
    fetchUserData().then(userData => {
        // Create greeting message with user data
        const greeting = `
            <h4>Hello, ${userData.username}!</h4>
            <p>Level: ${userData.level}</p>
            <p>XP: ${userData.xp}</p>
            <p>Streak: ${userData.streak} days</p>
            <p>Words learned: ${userData.words_learned}</p>
        `;
        
        // Add recent achievements if available
        if (userData.achievements && userData.achievements.length > 0) {
            const achievementsList = userData.achievements
                .map(achievement => `<li>${achievement.name}</li>`)
                .join('');
            
            speechBubble.innerHTML = `
                ${greeting}
                <p>Recent achievements:</p>
                <ul>${achievementsList}</ul>
            `;
        } else {
            speechBubble.innerHTML = greeting;
        }
    });
    
    // Toggle speech bubble visibility on avatar click
    avatar.addEventListener('click', () => {
        console.log("Avatar clicked!");
        speechBubble.classList.toggle('show');
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded, initializing dashboard...");
    
    // Initialize avatar interaction
    initializeAvatarInteraction();
    
    // Fetch progress data
    fetchProgress();
    
    // Fetch achievements data
    fetchAchievements();
    
    // Add event listener to close speech bubble when clicking outside
    document.addEventListener('click', function(event) {
        const avatar = document.getElementById('interactive-avatar');
        const speechBubble = document.getElementById('avatar-speech-bubble');
        
        if (avatar && speechBubble && !avatar.contains(event.target) && !speechBubble.contains(event.target)) {
            speechBubble.classList.remove('show');
        }
    });
    
    // Initialize dark mode toggle
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    if (darkModeToggle) {
        // Check if user has a preference stored
        const darkModePreference = localStorage.getItem('darkMode') === 'true';
        
        // Apply preference
        if (darkModePreference) {
            document.body.classList.add('dark-mode');
            darkModeToggle.checked = true;
        }
        
        // Add event listener for toggle
        darkModeToggle.addEventListener('change', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', this.checked);
        });
    }
    
    // Initialize vocabulary cards
    const vocabCards = document.querySelectorAll('.vocab-card');
    vocabCards.forEach(card => {
        card.addEventListener('click', function() {
            this.classList.toggle('flipped');
        });
    });
});


// Add this to your existing dashboard.js file or create a new one

// Function to update XP display
function updateXpDisplay(earnedXp) {
    // Get current values
    let currentXp = parseInt(document.getElementById('current-xp').textContent);
    let xpForNextLevel = parseInt(document.getElementById('xp-for-next-level').textContent);
    
    // Update XP
    let newXp = currentXp + earnedXp;
    document.getElementById('current-xp').textContent = newXp;
    
    // Update progress bar
    let progressPercent = Math.min((newXp / xpForNextLevel) * 100, 100);
    document.getElementById('xp-progress-bar').style.width = progressPercent + '%';
    document.getElementById('xp-progress-bar').setAttribute('aria-valuenow', newXp);
    
    // Update XP needed for next level
    document.getElementById('xp-needed').textContent = xpForNextLevel - newXp;
    
    // Check if level up (if needed)
    if (newXp >= xpForNextLevel) {
        // You could add a level-up animation or notification here
        console.log('Level up!');
    }
}

// Listen for custom events from your exercise pages
document.addEventListener('xpEarned', function(event) {
    updateXpDisplay(event.detail.xp);
});