document.addEventListener('DOMContentLoaded', function() {
    fetchProgress();
    fetchAchievements();
    // Fetch other necessary data like vocabulary, lessons etc.
});

function fetchProgress() {
    fetch('get_progress.php') // Call the progress API
        .then(response => response.json())
        .then(data => {
            if (data.success && data.progress) {
                updateProgressUI(data.progress);
            } else {
                console.error('Failed to fetch progress:', data.message);
                // Handle error display in UI
            }
        })
        .catch(error => {
            console.error('Error fetching progress:', error);
            // Handle error display in UI
        });
}

function fetchAchievements() {
    fetch('get_achievements.php') // Call the achievements API
        .then(response => response.json())
        .then(data => {
            if (data.success && data.achievements) {
                updateAchievementsUI(data.achievements);
            } else {
                console.error('Failed to fetch achievements:', data.message);
                // Handle error display in UI
            }
        })
        .catch(error => {
            console.error('Error fetching achievements:', error);
            // Handle error display in UI
        });
}

function updateProgressUI(progress) {
    // Example: Update elements with IDs like 'user-xp', 'user-level', 'xp-bar' etc.
    const xpElement = document.getElementById('user-xp');
    const levelElement = document.getElementById('user-level');
    const progressBar = document.getElementById('xp-progress-bar'); // Assuming you have a progress bar element

    if (xpElement) xpElement.textContent = progress.xp_total || 0;
    if (levelElement) levelElement.textContent = progress.current_level_name || 'Level ' + progress.current_level;

    if (progressBar) {
         const percentage = progress.xp_progress_percentage || 0;
         progressBar.style.width = percentage + '%';
         progressBar.textContent = Math.round(percentage) + '%'; // Optional: display text on bar
         // You might also want to display XP like "150 / 200 XP"
         const xpText = document.getElementById('xp-progress-text');
         if(xpText && progress.current_level_xp_required) {
             xpText.textContent = `${progress.xp_total} / ${progress.current_level_xp_required} XP`;
         }
    }

    // Update other progress elements (streak, words learned, etc.)
    // ...
}

function updateAchievementsUI(achievements) {
    const container = document.getElementById('achievements-list'); // Adjust ID
    if (!container) return;

    if (achievements.length === 0) {
        container.innerHTML = '<p>No achievements unlocked yet.</p>';
        return;
    }

    let html = '';
    achievements.forEach(ach => {
        html += `
            <div class="achievement-item">
                <img src="${ach.icon_url || 'img/default_achievement.png'}" alt="${ach.name}" class="achievement-icon">
                <div class="achievement-details">
                    <span class="achievement-name">${ach.name}</span>
                    <span class="achievement-desc">${ach.description}</span>
                    <span class="achievement-date">Unlocked: ${formatTimestamp(ach.unlocked_at)}</span>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;
}

// Helper function to format timestamp (you might already have one)
function formatTimestamp(isoString) {
    if (!isoString) return 'N/A';
    try {
        const date = new Date(isoString);
        // Adjust formatting as needed
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch (e) {
        return 'Invalid Date';
    }
}