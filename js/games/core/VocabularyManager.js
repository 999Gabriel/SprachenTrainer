/**
 * Vocabulary Manager for AntwortenTrainer Spanish Learning Games
 */
class VocabularyManager {
    constructor(difficulty = 'A1') {
        this.difficulty = difficulty;
        this.verbs = [];
        this.nouns = [];
        this.adjectives = [];
        this.phrases = [];
        this.loaded = false;
    }
    
    async loadAll() {
        await Promise.all([
            this.loadVerbs(),
            this.loadNouns(),
            this.loadAdjectives(),
            this.loadPhrases()
        ]);
        
        this.loaded = true;
        return {
            verbs: this.verbs,
            nouns: this.nouns,
            adjectives: this.adjectives,
            phrases: this.phrases
        };
    }
    
    async loadVerbs() {
        try {
            // In a real implementation, fetch from API
            // For now, use sample data
            this.verbs = [
                {
                    infinitive: 'hablar',
                    translation: 'to speak',
                    difficulty: 'A1',
                    tenses: {
                        present: {
                            'yo': 'hablo',
                            'tú': 'hablas',
                            'él/ella': 'habla',
                            'nosotros': 'hablamos',
                            'vosotros': 'habláis',
                            'ellos/ellas': 'hablan'
                        }
                    }
                },
                {
                    infinitive: 'comer',
                    translation: 'to eat',
                    difficulty: 'A1',
                    tenses: {
                        present: {
                            'yo': 'como',
                            'tú': 'comes',
                            'él/ella': 'come',
                            'nosotros': 'comemos',
                            'vosotros': 'coméis',
                            'ellos/ellas': 'comen'
                        }
                    }
                },
                {
                    infinitive: 'vivir',
                    translation: 'to live',
                    difficulty: 'A1',
                    tenses: {
                        present: {
                            'yo': 'vivo',
                            'tú': 'vives',
                            'él/ella': 'vive',
                            'nosotros': 'vivimos',
                            'vosotros': 'vivís',
                            'ellos/ellas': 'viven'
                        }
                    }
                }
            ];
            return this.verbs;
        } catch (error) {
            console.error('Error loading verbs:', error);
            return [];
        }
    }
    
    async loadNouns() {
        try {
            this.nouns = [
                { spanish: 'casa', translation: 'house', gender: 'f', difficulty: 'A1' },
                { spanish: 'perro', translation: 'dog', gender: 'm', difficulty: 'A1' },
                { spanish: 'gato', translation: 'cat', gender: 'm', difficulty: 'A1' },
                { spanish: 'libro', translation: 'book', gender: 'm', difficulty: 'A1' },
                { spanish: 'mesa', translation: 'table', gender: 'f', difficulty: 'A1' }
            ];
            return this.nouns;
        } catch (error) {
            console.error('Error loading nouns:', error);
            return [];
        }
    }
    
    async loadAdjectives() {
        try {
            this.adjectives = [
                { spanish: 'grande', translation: 'big', difficulty: 'A1' },
                { spanish: 'pequeño', translation: 'small', difficulty: 'A1' },
                { spanish: 'bueno', translation: 'good', difficulty: 'A1' },
                { spanish: 'malo', translation: 'bad', difficulty: 'A1' },
                { spanish: 'bonito', translation: 'pretty', difficulty: 'A1' }
            ];
            return this.adjectives;
        } catch (error) {
            console.error('Error loading adjectives:', error);
            return [];
        }
    }
    
    async loadPhrases() {
        try {
            this.phrases = [
                { spanish: 'Buenos días', translation: 'Good morning', difficulty: 'A1' },
                { spanish: '¿Cómo estás?', translation: 'How are you?', difficulty: 'A1' },
                { spanish: 'Me llamo...', translation: 'My name is...', difficulty: 'A1' },
                { spanish: 'Mucho gusto', translation: 'Nice to meet you', difficulty: 'A1' },
                { spanish: '¿Dónde está...?', translation: 'Where is...?', difficulty: 'A1' }
            ];
            return this.phrases;
        } catch (error) {
            console.error('Error loading phrases:', error);
            return [];
        }
    }
    
    getRandomVerb(count = 1) {
        if (this.verbs.length === 0) return [];
        
        const result = [];
        for (let i = 0; i < count; i++) {
            const randomIndex = Math.floor(Math.random() * this.verbs.length);
            result.push(this.verbs[randomIndex]);
        }
        
        return count === 1 ? result[0] : result;
    }
    
    getRandomNoun(count = 1) {
        if (this.nouns.length === 0) return [];
        
        const result = [];
        for (let i = 0; i < count; i++) {
            const randomIndex = Math.floor(Math.random() * this.nouns.length);
            result.push(this.nouns[randomIndex]);
        }
        
        return count === 1 ? result[0] : result;
    }
    
    getRandomAdjective(count = 1) {
        if (this.adjectives.length === 0) return [];
        
        const result = [];
        for (let i = 0; i < count; i++) {
            const randomIndex = Math.floor(Math.random() * this.adjectives.length);
            result.push(this.adjectives[randomIndex]);
        }
        
        return count === 1 ? result[0] : result;
    }
    
    getRandomPhrase(count = 1) {
        if (this.phrases.length === 0) return [];
        
        const result = [];
        for (let i = 0; i < count; i++) {
            const randomIndex = Math.floor(Math.random() * this.phrases.length);
            result.push(this.phrases[randomIndex]);
        }
        
        return count === 1 ? result[0] : result;
    }
}