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
    /** @type {'default' | 'village' | null} */
    let currentlyPlaying = null; // Track which audio is currently playing

    function initAudio() {
        if (initialized) return;
        
        // Check if we're in browser context
        if (typeof window === 'undefined' || typeof document === 'undefined') {
            console.warn('⚠️ Audio not available (SSR context)');
            return;
        }
        
        // Use document.createElement instead of new Audio() to avoid Vite StubAudio
        defaultBGM = document.createElement('audio');
        villageBGM = document.createElement('audio');
        
        defaultBGM.src = '/assets/audio/bgm/bgm-default.mp3';
        villageBGM.src = '/assets/audio/bgm/bgm-ingame.mp3';
        
        defaultBGM.loop = true;
        villageBGM.loop = true;
        
        defaultBGM.volume = 0.7;
        villageBGM.volume = 0.7;
        
        defaultBGM.preload = 'auto';
        villageBGM.preload = 'auto';
        
        // Add ended event listeners as a backup to ensure looping
        defaultBGM.addEventListener('ended', () => {
            if (currentlyPlaying === 'default' && defaultBGM) {
                defaultBGM.currentTime = 0;
                defaultBGM.play().catch(err => console.warn('Loop restart failed:', err));
            }
        });
        
        villageBGM.addEventListener('ended', () => {
            if (currentlyPlaying === 'village' && villageBGM) {
                villageBGM.currentTime = 0;
                villageBGM.play().catch(err => console.warn('Loop restart failed:', err));
            }
        });
        
        // Add event listeners to track playback state
        defaultBGM.addEventListener('play', () => {
            currentlyPlaying = 'default';
            // Ensure village BGM is paused
            if (villageBGM && !villageBGM.paused) {
                villageBGM.pause();
            }
        });
        
        villageBGM.addEventListener('play', () => {
            currentlyPlaying = 'village';
            // Ensure default BGM is paused
            if (defaultBGM && !defaultBGM.paused) {
                defaultBGM.pause();
            }
        });
        
        defaultBGM.addEventListener('pause', () => {
            if (currentlyPlaying === 'default') {
                currentlyPlaying = null;
            }
        });
        
        villageBGM.addEventListener('pause', () => {
            if (currentlyPlaying === 'village') {
                currentlyPlaying = null;
            }
        });
        
        // Store references for event handlers
        const defaultAudio = defaultBGM;
        const villageAudio = villageBGM;
        
        // Add metadata loaded handlers
        defaultAudio.addEventListener('loadedmetadata', () => {
            console.log('🎵 Background music loaded');
        });
        
        villageAudio.addEventListener('loadedmetadata', () => {
            console.log('🎵 Village music loaded');
        });
        
        // Add error handlers
        defaultAudio.addEventListener('error', (e) => {
            console.error('❌ Error loading background music:', defaultAudio.error);
        });
        
        villageAudio.addEventListener('error', (e) => {
            console.error('❌ Error loading village music:', villageAudio.error);
        });
        
        // Force load the audio files
        defaultAudio.load();
        villageAudio.load();
        
        initialized = true;
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
            
            // If same track is already playing, don't restart it
            if (currentlyPlaying === trackName) {
                console.log(`🎵 Track "${trackName}" is already playing`);
                return;
            }
            
            console.log(`🎵 Switching to track: ${trackName}`);
            
            update(state => {
                // CRITICAL: Stop ALL tracks first to prevent simultaneous playback
                if (defaultBGM && !defaultBGM.paused) {
                    defaultBGM.pause();
                    defaultBGM.currentTime = 0;
                }
                if (villageBGM && !villageBGM.paused) {
                    villageBGM.pause();
                    villageBGM.currentTime = 0;
                }
                
                // Wait a moment to ensure previous audio is fully stopped
                setTimeout(() => {
                    // Play the requested track if not muted
                    if (!state.isMuted) {
                        if (trackName === 'default' && defaultBGM) {
                            defaultBGM.currentTime = 0;
                            defaultBGM.play()
                                .then(() => {
                                    console.log('🎵 Playing background music');
                                })
                                .catch(/** @param {any} err */ err => {
                                    console.warn('⚠️ Audio play blocked:', err.message);
                                });
                        } else if (trackName === 'village' && villageBGM) {
                            villageBGM.currentTime = 0;
                            villageBGM.play()
                                .then(() => {
                                    console.log('🎵 Playing village music');
                                })
                                .catch(/** @param {any} err */ err => {
                                    console.warn('⚠️ Audio play blocked:', err.message);
                                });
                        }
                    }
                }, 50); // 50ms delay to ensure clean transition
                
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
            console.log('🔇 Stopping all audio');
            if (defaultBGM) {
                defaultBGM.pause();
                defaultBGM.currentTime = 0;
            }
            if (villageBGM) {
                villageBGM.pause();
                villageBGM.currentTime = 0;
            }
            currentlyPlaying = null;
            
            update(state => ({
                ...state,
                currentTrack: null,
                isPlaying: false
            }));
        },

        // Save current track to sessionStorage (for preserving across page reloads)
        saveCurrentTrack() {
            update(state => {
                if (state.currentTrack && typeof sessionStorage !== 'undefined') {
                    sessionStorage.setItem('audioTrack', state.currentTrack);
                    sessionStorage.setItem('audioMuted', String(state.isMuted));
                    console.log(`💾 Saved audio state: ${state.currentTrack}, muted: ${state.isMuted}`);
                }
                return state;
            });
        },

        // Restore track from sessionStorage (after page reload)
        restoreSavedTrack() {
            if (typeof sessionStorage === 'undefined') return;
            
            const savedTrack = sessionStorage.getItem('audioTrack');
            const savedMuted = sessionStorage.getItem('audioMuted') === 'true';
            
            if (savedTrack) {
                console.log(`🔄 Restoring audio state: ${savedTrack}, muted: ${savedMuted}`);
                
                update(state => ({
                    ...state,
                    isMuted: savedMuted
                }));
                
                if (!savedMuted && (savedTrack === 'default' || savedTrack === 'village')) {
                    this.playTrack(savedTrack);
                }
                
                // Clear after restoring
                sessionStorage.removeItem('audioTrack');
                sessionStorage.removeItem('audioMuted');
            }
        },

        // Completely destroy audio elements (call on logout)
        destroy() {
            console.log('🗑️ Destroying audio elements');
            
            // Stop and remove all audio
            if (defaultBGM) {
                defaultBGM.pause();
                defaultBGM.src = '';
                defaultBGM.load();
                defaultBGM = null;
            }
            if (villageBGM) {
                villageBGM.pause();
                villageBGM.src = '';
                villageBGM.load();
                villageBGM = null;
            }
            
            currentlyPlaying = null;
            initialized = false;
            
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
