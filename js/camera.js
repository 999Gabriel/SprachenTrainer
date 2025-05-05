// Global variables
let model;
let webcam;
let canvas;
let ctx;
let detectedObjects = [];
let currentChallenge = null;
let xpEarned = 0;
let cameraStream = null;
let facingMode = 'environment'; // Start with back camera

// Spanish translations for common objects (simplified for demo)
const translations = {
    'person': 'persona',
    'bicycle': 'bicicleta',
    'car': 'coche',
    'motorcycle': 'motocicleta',
    'airplane': 'avión',
    'bus': 'autobús',
    'train': 'tren',
    'truck': 'camión',
    'boat': 'barco',
    'traffic light': 'semáforo',
    'fire hydrant': 'boca de incendio',
    'stop sign': 'señal de stop',
    'parking meter': 'parquímetro',
    'bench': 'banco',
    'bird': 'pájaro',
    'cat': 'gato',
    'dog': 'perro',
    'horse': 'caballo',
    'sheep': 'oveja',
    'cow': 'vaca',
    'elephant': 'elefante',
    'bear': 'oso',
    'zebra': 'cebra',
    'giraffe': 'jirafa',
    'backpack': 'mochila',
    'umbrella': 'paraguas',
    'handbag': 'bolso',
    'tie': 'corbata',
    'suitcase': 'maleta',
    'frisbee': 'frisbee',
    'skis': 'esquís',
    'snowboard': 'snowboard',
    'sports ball': 'pelota',
    'kite': 'cometa',
    'baseball bat': 'bate de béisbol',
    'baseball glove': 'guante de béisbol',
    'skateboard': 'monopatín',
    'surfboard': 'tabla de surf',
    'tennis racket': 'raqueta de tenis',
    'bottle': 'botella',
    'wine glass': 'copa de vino',
    'cup': 'taza',
    'fork': 'tenedor',
    'knife': 'cuchillo',
    'spoon': 'cuchara',
    'bowl': 'bol',
    'banana': 'plátano',
    'apple': 'manzana',
    'sandwich': 'sándwich',
    'orange': 'naranja',
    'broccoli': 'brócoli',
    'carrot': 'zanahoria',
    'hot dog': 'perrito caliente',
    'pizza': 'pizza',
    'donut': 'donut',
    'cake': 'pastel',
    'chair': 'silla',
    'couch': 'sofá',
    'potted plant': 'planta',
    'bed': 'cama',
    'dining table': 'mesa',
    'toilet': 'inodoro',
    'tv': 'televisión',
    'laptop': 'portátil',
    'mouse': 'ratón',
    'remote': 'mando',
    'keyboard': 'teclado',
    'cell phone': 'teléfono móvil',
    'microwave': 'microondas',
    'oven': 'horno',
    'toaster': 'tostadora',
    'sink': 'fregadero',
    'refrigerator': 'nevera',
    'book': 'libro',
    'clock': 'reloj',
    'vase': 'jarrón',
    'scissors': 'tijeras',
    'teddy bear': 'oso de peluche',
    'hair drier': 'secador',
    'toothbrush': 'cepillo de dientes'
};

// Recently learned words
const recentlyLearned = [];

// DOM elements
document.addEventListener('DOMContentLoaded', () => {
    // Initialize elements
    webcam = document.getElementById('webcam');
    canvas = document.getElementById('canvas');
    ctx = canvas.getContext('2d');
    
    // Buttons
    const startCameraBtn = document.getElementById('startCamera');
    const captureImageBtn = document.getElementById('captureImage');
    const switchCameraBtn = document.getElementById('switchCamera');
    const checkAnswerBtn = document.getElementById('checkAnswer');
    const getHintBtn = document.getElementById('getHint');
    const userAnswerInput = document.getElementById('userAnswer');
    
    // Event listeners
    startCameraBtn.addEventListener('click', toggleCamera);
    captureImageBtn.addEventListener('click', captureImage);
    switchCameraBtn.addEventListener('click', switchCamera);
    checkAnswerBtn.addEventListener('click', checkAnswer);
    getHintBtn.addEventListener('click', showHint);
    userAnswerInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            checkAnswer();
        }
    });
    
    // Load COCO-SSD model
    loadModel();
});

// Load TensorFlow model
async function loadModel() {
    try {
        model = await cocoSsd.load();
        console.log('Model loaded successfully');
    } catch (error) {
        console.error('Error loading model:', error);
        showError('Failed to load object detection model. Please refresh the page and try again.');
    }
}

// Toggle camera on/off
async function toggleCamera() {
    const startCameraBtn = document.getElementById('startCamera');
    const captureImageBtn = document.getElementById('captureImage');
    const switchCameraBtn = document.getElementById('switchCamera');
    
    if (cameraStream) {
        // Stop camera
        stopCamera();
        startCameraBtn.innerHTML = '<i class="fas fa-video"></i> Start Camera';
        captureImageBtn.disabled = true;
        switchCameraBtn.disabled = true;
        return;
    }
    
    try {
        // Start camera
        await startCamera();
        startCameraBtn.innerHTML = '<i class="fas fa-video-slash"></i> Stop Camera';
        captureImageBtn.disabled = false;
        switchCameraBtn.disabled = false;
    } catch (error) {
        console.error('Error accessing camera:', error);
        showError('Could not access camera. Please ensure you have granted camera permissions.');
    }
}

// Start camera
async function startCamera() {
    try {
        const constraints = {
            video: {
                facingMode: facingMode,
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        };
        
        cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
        webcam.srcObject = cameraStream;
        
        return new Promise((resolve) => {
            webcam.onloadedmetadata = () => {
                // Set canvas dimensions to match video
                canvas.width = webcam.videoWidth;
                canvas.height = webcam.videoHeight;
                
                // Start detection loop
                detectObjects();
                resolve();
            };
        });
    } catch (error) {
        throw error;
    }
}

// Stop camera
function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
        webcam.srcObject = null;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Clear detected objects
        document.getElementById('detectedObjects').innerHTML = `
            <div class="empty-state">
                <i class="fas fa-camera-retro"></i>
                <p>Start the camera and point it at objects to detect them</p>
            </div>
        `;
    }
}

// Switch between front and back camera
async function switchCamera() {
    facingMode = facingMode === 'environment' ? 'user' : 'environment';
    
    // Stop current camera
    stopCamera();
    
    // Restart with new facing mode
    await startCamera();
}

// Detect objects in video stream
async function detectObjects() {
    if (!model || !cameraStream) return;
    
    try {
        const predictions = await model.detect(webcam);
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw bounding boxes and labels
        ctx.strokeStyle = '#00BFFF';
        ctx.lineWidth = 3;
        ctx.font = '16px Arial';
        ctx.fillStyle = '#00BFFF';
        
        detectedObjects = [];
        
        predictions.forEach(prediction => {
            // Get prediction data
            const [x, y, width, height] = prediction.bbox;
            const label = prediction.class;
            const score = prediction.score;
            
            // Only show predictions with high confidence
            if (score > 0.6) {
                // Draw bounding box
                ctx.strokeRect(x, y, width, height);
                
                // Draw label background
                ctx.fillStyle = 'rgba(0, 191, 255, 0.7)';
                const textWidth = ctx.measureText(label).width;
                ctx.fillRect(x, y - 25, textWidth + 10, 25);
                
                // Draw label text
                ctx.fillStyle = '#FFFFFF';
                ctx.fillText(label, x + 5, y - 8);
                
                // Add to detected objects
                if (translations[label]) {
                    detectedObjects.push({
                        label: label,
                        translation: translations[label],
                        confidence: score
                    });
                }
            }
        });
        
        // Update detected objects list
        updateDetectedObjectsList();
        
        // Continue detection loop
        if (cameraStream) {
            requestAnimationFrame(detectObjects);
        }
    } catch (error) {
        console.error('Detection error:', error);
    }
}

// Update the list of detected objects
function updateDetectedObjectsList() {
    const container = document.getElementById('detectedObjects');
    
    if (detectedObjects.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <p>No objects detected. Try pointing your camera at common objects.</p>
            </div>
        `;
        return;
    }
    
    // Sort by confidence
    detectedObjects.sort((a, b) => b.confidence - a.confidence);
    
    let html = '';
    detectedObjects.forEach(obj => {
        html += `
            <div class="object-item">
                <div class="object-name">${obj.label}</div>
                <div class="object-translation">${obj.translation}</div>
                <div class="object-confidence">${Math.round(obj.confidence * 100)}%</div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Capture image and start challenge
function captureImage() {
    if (detectedObjects.length === 0) {
        showNotification('No objects detected. Try pointing your camera at something else.');
        return;
    }
    
    // Select a random object for the challenge
    const randomIndex = Math.floor(Math.random() * detectedObjects.length);
    currentChallenge = detectedObjects[randomIndex];
    
    // Show challenge
    document.getElementById('challengeWord').textContent = currentChallenge.label;
    document.getElementById('learningChallenge').style.display = 'block';
    document.getElementById('userAnswer').value = '';
    document.getElementById('userAnswer').focus();
    document.getElementById('hintText').textContent = '';
}

// Check user's answer
function checkAnswer() {
    if (!currentChallenge) return;
    
    const userAnswer = document.getElementById('userAnswer').value.trim().toLowerCase();
    const correctAnswer = currentChallenge.translation.toLowerCase();
    
    const isCorrect = userAnswer === correctAnswer;
    
    if (isCorrect) {
        // Correct answer
        showNotification('¡Correcto! Great job!', 'success');
        
        // Add XP
        xpEarned += 10;
        document.getElementById('xpEarned').textContent = xpEarned;
        
        // Visual XP animation
        showXpAnimation(10);
        
        // Add to recently learned
        addToRecentlyLearned(currentChallenge.label, currentChallenge.translation);
        
        // Hide challenge
        document.getElementById('learningChallenge').style.display = 'none';
        
        // Save progress
        saveProgress(currentChallenge.translation)
            .then(response => {
                console.log('Word progress saved:', response);
            })
            .catch(error => {
                console.error('Error saving word progress:', error);
                showNotification('Error saving progress: ' + error.message, 'error');
            });
    } else {
        // Check for close match (simple implementation)
        if (isCloseMatch(userAnswer, correctAnswer)) {
            showNotification('Almost correct! The answer is: ' + correctAnswer, 'warning');
        } else {
            showNotification('Try again! Hint: ' + getPartialHint(correctAnswer), 'error');
        }
    }
}

// Show hint
function showHint() {
    if (!currentChallenge) return;
    
    const correctAnswer = currentChallenge.translation;
    const hintText = getFullHint(correctAnswer);
    
    document.getElementById('hintText').textContent = hintText;
}

// Get partial hint (first letter + length)
function getPartialHint(word) {
    return word.charAt(0) + '_____ (' + word.length + ' letters)';
}

// Get full hint (first and last letter + blanks)
function getFullHint(word) {
    if (word.length <= 3) return word; // For very short words, just show it
    
    let hint = word.charAt(0);
    for (let i = 1; i < word.length - 1; i++) {
        hint += word[i] === ' ' ? ' ' : '_';
    }
    hint += word.charAt(word.length - 1);
    
    return hint;
}

// Check if answer is close to correct (simple implementation)
function isCloseMatch(userAnswer, correctAnswer) {
    // If lengths are very different, not a close match
    if (Math.abs(userAnswer.length - correctAnswer.length) > 2) return false;
    
    // Count matching characters
    let matches = 0;
    for (let i = 0; i < Math.min(userAnswer.length, correctAnswer.length); i++) {
        if (userAnswer[i] === correctAnswer[i]) matches++;
    }
    
    // If more than 70% matches, consider it close
    return matches / correctAnswer.length > 0.7;
}

// Add word to recently learned list
function addToRecentlyLearned(english, spanish) {
    // Check if already in list
    const existingIndex = recentlyLearned.findIndex(item => item.english === english);
    if (existingIndex !== -1) {
        // Remove existing entry
        recentlyLearned.splice(existingIndex, 1);
    }
    
    // Add to beginning of array
    recentlyLearned.unshift({
        english: english,
        spanish: spanish,
        timestamp: new Date()
    });
    
    // Limit to 10 items
    if (recentlyLearned.length > 10) {
        recentlyLearned.pop();
    }
    
    // Update UI
    updateRecentlyLearned();
}

// Update recently learned UI
function updateRecentlyLearned() {
    const container = document.getElementById('recentlyLearned');
    
    if (recentlyLearned.length === 0) {
        container.innerHTML = '<p class="empty-message">No words learned yet. Start capturing objects!</p>';
        return;
    }
    
    let html = '';
    recentlyLearned.forEach(word => {
        html += `
            <div class="word-card">
                <div class="word-english">${word.english}</div>
                <div class="word-spanish">${word.spanish}</div>
                <div class="word-timestamp">${formatTimestamp(word.timestamp)}</div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Format timestamp
function formatTimestamp(timestamp) {
    const now = new Date();
    const diff = now - timestamp;
    
    // Less than a minute
    if (diff < 60000) {
        return 'Just now';
    }
    
    // Less than an hour
    if (diff < 3600000) {
        const minutes = Math.floor(diff / 60000);
        return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
    }
    
    // Today
    if (timestamp.toDateString() === now.toDateString()) {
        return 'Today ' + timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    // Otherwise show date
    return timestamp.toLocaleDateString();
}

// Save progress to server
function saveProgress(additionalData = {}) {
    if (xpEarned <= 0) return;
    
    // Show saving indicator
    const savingIndicator = document.createElement('div');
    savingIndicator.className = 'saving-indicator';
    savingIndicator.innerHTML = '<i class="fas fa-sync fa-spin"></i> Saving progress...';
    document.body.appendChild(savingIndicator);
    
    // Prepare data to send
    const data = {
        xp_earned: xpEarned,
        activity_type: 'camera_learning',
        ...additionalData
    };
    
    // Add vocabulary data if we have a current challenge
    if (currentChallenge && additionalData.activity_details && additionalData.activity_details.includes(currentChallenge.label)) {
        data.object_name = currentChallenge.label;
        data.object_translation = currentChallenge.translation;
    }
    
    // Use fetch to send XP to server
    fetch('save_progress.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Progress saved:', data);
        
        // Remove saving indicator
        document.body.removeChild(savingIndicator);
        
        if (data.success) {
            // Show success message
            showNotification(`Progress saved! Total XP: ${data.current_xp}`, 'success');
            
            // Update XP display if available
            const xpDisplay = document.querySelector('.user-xp');
            if (xpDisplay) {
                xpDisplay.textContent = data.current_xp;
            }
            
            // Show level up notification if applicable
            if (data.leveled_up) {
                showLevelUpNotification(data.current_level);
                
                // Check for new achievements
                if (data.achievements && data.achievements.length > 0) {
                    setTimeout(() => {
                        showAchievementNotification(data.achievements[0]);
                    }, 1500); // Show after level up notification
                }
            } else if (data.achievements && data.achievements.length > 0) {
                // Show achievement notification directly if no level up
                showAchievementNotification(data.achievements[0]);
            }
            
            // Reset XP earned in this session after successful save
            xpEarned = 0;
            document.getElementById('xpEarned').textContent = xpEarned;
        } else {
            showNotification(`Failed to save progress: ${data.message || 'Unknown error'}`, 'error');
            console.error('Save progress error:', data.message);
        }
    })
    .catch(error => {
        console.error('Error saving progress:', error);
        document.body.removeChild(savingIndicator);
        showNotification('Error saving progress. Please check your connection.', 'error');
    });
}

// Show XP animation
function showXpAnimation(amount) {
    // Create XP animation element
    const xpAnimation = document.createElement('div');
    xpAnimation.className = 'xp-animation';
    xpAnimation.innerHTML = `+${amount} XP`;
    
    // Add to body
    document.body.appendChild(xpAnimation);
    
    // Animate
    setTimeout(() => {
        xpAnimation.classList.add('show');
        
        // Remove after animation
        setTimeout(() => {
            xpAnimation.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(xpAnimation);
            }, 500);
        }, 1500);
    }, 10);
}

// Show level up notification
function showLevelUpNotification(newLevel) {
    // Create level up notification
    const levelUpNotification = document.createElement('div');
    levelUpNotification.className = 'level-up-notification';
    levelUpNotification.innerHTML = `
        <div class="level-up-content">
            <div class="level-up-icon">
                <i class="fas fa-award"></i>
            </div>
            <h3>Level Up!</h3>
            <p>Congratulations! You've reached <strong>${newLevel}</strong></p>
            <button class="btn btn-primary">Continue</button>
        </div>
    `;
    
    // Add to body
    document.body.appendChild(levelUpNotification);
    
    // Show with animation
    setTimeout(() => {
        levelUpNotification.classList.add('show');
    }, 10);
    
    // Add event listener to close button
    const closeButton = levelUpNotification.querySelector('button');
    closeButton.addEventListener('click', () => {
        levelUpNotification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(levelUpNotification);
        }, 300);
    });
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = message;
    
    // Add to body
    document.body.appendChild(notification);
    
    // Show with animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Remove after delay
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Show error message
function showError(message) {
    showNotification(message, 'error');
}

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    stopCamera();
    saveProgress();
});

// Show achievement notification
function showAchievementNotification(achievement) {
    // Create achievement notification
    const achievementNotification = document.createElement('div');
    achievementNotification.className = 'achievement-notification';
    achievementNotification.innerHTML = `
        <div class="achievement-content">
            <div class="achievement-icon">
                <img src="${achievement.icon_url || '/img/achievements/placeholder.png'}" alt="Achievement">
            </div>
            <div class="achievement-info">
                <h3>Achievement Unlocked!</h3>
                <h4>${achievement.name}</h4>
                <p>${achievement.description}</p>
                <div class="achievement-xp">+${achievement.xp_reward} XP</div>
            </div>
            <button class="btn-close">×</button>
        </div>
    `;
    
    // Add to body
    document.body.appendChild(achievementNotification);
    
    // Show with animation
    setTimeout(() => {
        achievementNotification.classList.add('show');
    }, 10);
    
    // Add event listener to close button
    const closeButton = achievementNotification.querySelector('.btn-close');
    closeButton.addEventListener('click', () => {
        achievementNotification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(achievementNotification);
        }, 300);
    });
    
    // Auto close after 5 seconds
    setTimeout(() => {
        if (document.body.contains(achievementNotification)) {
            achievementNotification.classList.remove('show');
            setTimeout(() => {
                if (document.body.contains(achievementNotification)) {
                    document.body.removeChild(achievementNotification);
                }
            }, 300);
        }
    }, 5000);
}

// Find the saveProgress function and update it
function saveProgress(word) {
    console.log('Saving progress for word:', word);
    
    return new Promise((resolve, reject) => {
        // Replace jQuery $.ajax with native fetch
        fetch('api/save-word-progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                word: word,
                action: 'mark_learned'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server responded with status: ${response.status}`);
            }
            return response.json();
        })
        .then(response => {
            console.log('Save progress response:', response);
            if (response && response.success) {
                resolve(response);
            } else {
                console.error('Error in response:', response);
                reject(new Error(response.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            reject(error);
        });
    });
}

// Remove this duplicate checkAnswer function
// function checkAnswer(userGuess, correctAnswer) {
//     // ... existing code ...
// }
    