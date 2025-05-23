/**
 * Vocabulary Manager for AntwortenTrainer Spanish Learning Games
 * Handles loading and managing Spanish vocabulary and verbs
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
                        },
                        preterite: {
                            'yo': 'hablé',
                            'tú': 'hablaste',
                            'él/ella': 'habló',
                            'nosotros': 'hablamos',
                            'vosotros': 'hablasteis',
                            'ellos/ellas': 'hablaron'
                        },
                        imperfect: {
                            'yo': 'hablaba',
                            'tú': 'hablabas',
                            'él/ella': 'hablaba',
                            'nosotros': 'hablábamos',
                            'vosotros': 'hablabais',
                            'ellos/ellas': 'hablaban'
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
                        },
                        preterite: {
                            'yo': 'comí',
                            'tú': 'comiste',
                            'él/ella': 'comió',
                            'nosotros': 'comimos',
                            'vosotros': 'comisteis',
                            'ellos/ellas': 'comieron'
                        },
                        imperfect: {
                            'yo': 'comía',
                            'tú': 'comías',
                            'él/ella': 'comía',
                            'nosotros': 'comíamos',
                            'vosotros': 'comíais',
                            'ellos/ellas': 'comían'
                        }
                    }
                },
                // Add more verbs based on difficulty
                {
                    infinitive: 'ser',
                    translation: 'to be',
                    difficulty: 'A1',
                    tenses: {
                        present: {
                            'yo': 'soy',
                            'tú': 'eres',
                            'él/ella': 'es',
                            'nosotros': 'somos',
                            'vosotros': 'sois',
                            'ellos/ellas': 'son'
                        },
                        preterite: {
                            'yo': 'fui',
                            'tú': 'fuiste',
                            'él/ella': 'fue',
                            'nosotros': 'fuimos',
                            'vosotros': 'fuisteis',
                            'ellos/ellas': 'fueron'
                        },
                        imperfect: {
                            'yo': 'era',
                            'tú': 'eras',
                            'él/ella': 'era',
                            'nosotros': 'éramos',
                            'vosotros': 'erais',
                            'ellos/ellas': 'eran'
                        }
                    }
                }
            ];
            
            // Add more complex verbs for higher levels
            if (this.difficulty !== 'A1') {
                this.verbs.push(
                    {
                        infinitive: 'decir',
                        translation: 'to say',
                        difficulty: 'A2',
                        tenses: {
                            present: {
                                'yo': 'digo',
                                'tú': 'dices',
                                'él/ella': 'dice',
                                'nosotros': 'decimos',
                                'vosotros': 'decís',
                                'ellos/ellas': 'dicen'
                            },
                            preterite: {
                                'yo': 'dije',
                                'tú': 'dijiste',
                                'él/ella': 'dijo',
                                'nosotros': 'dijimos',
                                'vosotros': 'dijisteis',
                                'ellos/ellas': 'dijeron'
                            }
                        }
                    }
                );
            }
            
            return this.verbs;
        } catch (error) {
            console.error('Error loading verbs:', error);
            return [];
        }
    }
    
    async loadNouns() {
        // Sample nouns data
        this.nouns = [
            { word: 'casa', translation: 'house', gender: 'f', difficulty: 'A1' },
            { word: 'perro', translation: 'dog', gender: 'm', difficulty: 'A1' },
            { word: 'gato', translation: 'cat', gender: 'm', difficulty: 'A1' },
            { word: 'libro', translation: 'book', gender: 'm', difficulty: 'A1' },
            { word: 'mesa', translation: 'table', gender: 'f', difficulty: 'A1' }
        ];
        
        return this.nouns;
    }
    
    async loadAdjectives() {
        // Sample adjectives data
        this.adjectives = [
            { word: 'grande', translation: 'big', difficulty: 'A1' },
            { word: 'pequeño', translation: 'small', difficulty: 'A1' },
            { word: 'bonito', translation: 'pretty', difficulty: 'A1' },
            { word: 'feo', translation: 'ugly', difficulty: 'A1' },
            { word: 'nuevo', translation: 'new', difficulty: 'A1' }
        ];
        
        return this.adjectives;
    }
    
    async loadPhrases() {
        // Sample phrases data
        this.phrases = [
            { 
                spanish: '¿Cómo estás?', 
                translation: 'How are you?', 
                difficulty: 'A1',
                context: 'greeting'
            },
            { 
                spanish: 'Me gustaría un café, por favor.', 
                translation: 'I would like a coffee, please.', 
                difficulty: 'A1',
                context: 'restaurant'
            },
            { 
                spanish: '¿Dónde está el baño?', 
                translation: 'Where is the bathroom?', 
                difficulty: 'A1',
                context: 'public place'
            }
        ];
        
        return this.phrases;
    }
    
    getRandomVerbs(count = 5, tense = null) {
        if (!this.loaded) {
            console.warn('Vocabulary not loaded yet');
            return [];
        }
        
        // Filter by difficulty if needed
        let filteredVerbs = this.verbs.filter(verb => 
            this.difficulty === 'A1' ? verb.difficulty === 'A1' : true
        );
        
        // Shuffle and take requested count
        return this.shuffle(filteredVerbs).slice(0, count);
    }
    
    getRandomNouns(count = 5) {
        if (!this.loaded) return [];
        
        let filteredNouns = this.nouns.filter(noun => 
            this.difficulty === 'A1' ? noun.difficulty === 'A1' : true
        );
        
        return this.shuffle(filteredNouns).slice(0, count);
    }
    
    getRandomAdjectives(count = 5) {
        if (!this.loaded) return [];
        
        let filteredAdjectives = this.adjectives.filter(adj => 
            this.difficulty === 'A1' ? adj.difficulty === 'A1' : true
        );
        
        return this.shuffle(filteredAdjectives).slice(0, count);
    }
    
    getRandomPhrases(count = 5, context = null) {
        if (!this.loaded) return [];
        
        let filteredPhrases = this.phrases;
        
        if (context) {
            filteredPhrases = filteredPhrases.filter(phrase => phrase.context === context);
        }
        
        filteredPhrases = filteredPhrases.filter(phrase => 
            this.difficulty === 'A1' ? phrase.difficulty === 'A1' : true
        );
        
        return this.shuffle(filteredPhrases).slice(0, count);
    }
    
    shuffle(array) {
        const newArray = [...array];
        for (let i = newArray.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
        }
        return newArray;
    }
}