/**
 * Audio Manager for AntwortenTrainer Spanish Learning Games
 * Handles pronunciation and sound effects
 */

class AudioManager {
    constructor() {
        this.sounds = {};
        this.isMuted = false;
        this.volume = 0.8;
        
        // Preload common sounds
        this.preloadCommonSounds();
    }
    
    preloadCommonSounds() {
        this.loadSound('correct', '/audio/correct.mp3');
        this.loadSound('incorrect', '/audio/incorrect.mp3');
        this.loadSound('gameover', '/audio/game-over.mp3');
        this.loadSound('levelup', '/audio/level-up.mp3');
    }
    
    loadSound(name, path) {
        const audio = new Audio(path);
        audio.preload = 'auto';
        this.sounds[name] = audio;
        return audio;
    }
    
    play(name) {
        if (this.isMuted || !this.sounds[name]) return;
        
        const sound = this.sounds[name];
        sound.volume = this.volume;
        sound.currentTime = 0;
        sound.play().catch(e => console.warn('Audio play failed:', e));
    }
    
    stop(name) {
        if (!this.sounds[name]) return;
        
        const sound = this.sounds[name];
        sound.pause();
        sound.currentTime = 0;
    }
    
    mute() {
        this.isMuted = true;
    }
    
    unmute() {
        this.isMuted = false;
    }
    
    setVolume(level) {
        this.volume = Math.max(0, Math.min(1, level));
    }
    
    async speakText(text, options = {}) {
        if (this.isMuted) return;
        
        // Use Web Speech API for pronunciation
        if ('speechSynthesis' in window) {
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = options.lang || 'es-ES';
            utterance.rate = options.rate || 0.9;
            utterance.pitch = options.pitch || 1;
            utterance.volume = this.volume;
            
            speechSynthesis.speak(utterance);
            return utterance;
        }
    }
    
    async loadPronunciation(word) {
        // For future implementation: fetch pronunciation from API
        // For now, we'll use the Web Speech API
        return word;
    }
}