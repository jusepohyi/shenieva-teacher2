// @ts-check
import { writable } from 'svelte/store';

// Audio state store
function createAudioStore() {
    const { subscribe, set, update } = writable({
        isMuted: false,
        currentTrack: /** @type {string | null} */ (null), // 'default' or 'village'
        isPlaying: false
    });

    // Audio elements (singleton instances)
    /** @type {HTMLAudioElement | null} */
    let defaultBGM = null;
    /** @type {HTMLAudioElement | null} */
    let villageBGM = null;
    let initialized = false;

    function initAudio() {
        if (initialized) return;
        
        console.log('🎵 Initializing audio system...');
        
        // Use relative paths from static/public folder
        defaultBGM = new Audio('/assets/audio/bgm/bgm-default.mp3');
        villageBGM = new Audio('/assets/audio/bgm/bgm-ingame.mp3');
        
        defaultBGM.loop = true;
        villageBGM.loop = true;
        
        defaultBGM.volume = 0.5;
        villageBGM.volume = 0.5;
        
        // Add error handlers for debugging
        defaultBGM.addEventListener('error', (e) => {
            console.error('❌ Error loading default BGM:', e);
            console.error('Attempted path: /assets/audio/bgm/bgm-default.mp3');
        });
        
        villageBGM.addEventListener('error', (e) => {
            console.error('❌ Error loading village BGM:', e);
            console.error('Attempted path: /assets/audio/bgm/bgm-ingame.mp3');
        });
        
        // Add loaded handlers
        defaultBGM.addEventListener('loadeddata', () => {
            console.log('✅ Default BGM loaded successfully');
        });
        
        villageBGM.addEventListener('loadeddata', () => {
            console.log('✅ Village BGM loaded successfully');
        });
        
        initialized = true;
        console.log('✅ Audio system initialized');
    }

    return {
        subscribe,
        
        // Initialize audio (call this once on app mount)
        init() {
            initAudio();
        },

        // Play specific track
        /**
         * @param {'default' | 'village'} trackName
         */
        playTrack(trackName) {
            initAudio();
            
            update(state => {
                // Stop all tracks first
                if (defaultBGM) defaultBGM.pause();
                if (villageBGM) villageBGM.pause();
                
                // Play the requested track if not muted
                if (!state.isMuted) {
                    if (trackName === 'default' && defaultBGM) {
                        defaultBGM.currentTime = 0;
                        defaultBGM.play().catch(/** @param {any} err */ err => console.warn('Audio play failed:', err));
                    } else if (trackName === 'village' && villageBGM) {
                        villageBGM.currentTime = 0;
                        villageBGM.play().catch(/** @param {any} err */ err => console.warn('Audio play failed:', err));
                    }
                }
                
                return {
                    ...state,
                    currentTrack: trackName,
                    isPlaying: !state.isMuted
                };
            });
        },

        // Toggle mute/unmute
        toggleMute() {
            update(state => {
                const newMutedState = !state.isMuted;
                
                if (newMutedState) {
                    // Mute - pause current track
                    if (defaultBGM) defaultBGM.pause();
                    if (villageBGM) villageBGM.pause();
                } else {
                    // Unmute - resume current track
                    if (state.currentTrack === 'default' && defaultBGM) {
                        defaultBGM.play().catch(/** @param {any} err */ err => console.warn('Audio play failed:', err));
                    } else if (state.currentTrack === 'village' && villageBGM) {
                        villageBGM.play().catch(/** @param {any} err */ err => console.warn('Audio play failed:', err));
                    }
                }
                
                return {
                    ...state,
                    isMuted: newMutedState,
                    isPlaying: !newMutedState && state.currentTrack !== null
                };
            });
        },

        // Stop all music
        stopAll() {
            if (defaultBGM) defaultBGM.pause();
            if (villageBGM) villageBGM.pause();
            
            update(state => ({
                ...state,
                currentTrack: null,
                isPlaying: false
            }));
        },

        // Set volume (0.0 to 1.0)
        /**
         * @param {number} volume
         */
        setVolume(volume) {
            if (defaultBGM) defaultBGM.volume = volume;
            if (villageBGM) villageBGM.volume = volume;
        }
    };
}

export const audioStore = createAudioStore();
