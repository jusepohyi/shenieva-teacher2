// @ts-check
import { writable } from 'svelte/store';

/**
 * @typedef {{ isMuted: boolean, currentTrack: 'default'|'village'|null, isPlaying: boolean }} AudioState
 */

// Audio state store
function createAudioStore() {
    /** @type {import('svelte/store').Writable<AudioState>} */
    const store = writable(/** @type {AudioState} */({
        isMuted: false,
        currentTrack: /** @type {'default' | 'village' | null} */ (null),
        isPlaying: false
    }));
    const { subscribe, update, set } = store;

    /** @type {HTMLAudioElement | null} */
    let defaultBGM = null;
    /** @type {HTMLAudioElement | null} */
    let villageBGM = null;
    let initialized = false;
    /** @type {'default' | 'village' | null} */
    let currentlyPlaying = null;

    // story mode lock prevents other callers from changing BGM volume while narration plays
    // story mode lock prevents other callers from changing BGM volume while narration plays
    // Use a reference count so multiple slides/components can request story mode without
    // restoring the BGM until all have released it. Also add a short debounce on restore
    // so quick transitions (next slide) don't cause flicker.
    let storyModeLock = false;
    let storyModeCount = 0;
    /** @type {number | null} */
    let _lockedPrevVolume = null;
    /** @type {number | null} */
    let _restoreTimer = null;

    function initAudio() {
        if (initialized) return;
        if (typeof window === 'undefined' || typeof document === 'undefined') return;

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

        defaultBGM.addEventListener('play', () => {
            currentlyPlaying = 'default';
            if (villageBGM && !villageBGM.paused) villageBGM.pause();
        });

        villageBGM.addEventListener('play', () => {
            currentlyPlaying = 'village';
            if (defaultBGM && !defaultBGM.paused) defaultBGM.pause();
        });

        defaultBGM.addEventListener('pause', () => {
            if (currentlyPlaying === 'default') currentlyPlaying = null;
        });
        villageBGM.addEventListener('pause', () => {
            if (currentlyPlaying === 'village') currentlyPlaying = null;
        });

        defaultBGM.addEventListener('error', () => console.error('❌ Error loading background music:', defaultBGM && defaultBGM.error));
        villageBGM.addEventListener('error', () => console.error('❌ Error loading village music:', villageBGM && villageBGM.error));

        // preload
        defaultBGM.load();
        villageBGM.load();

        initialized = true;
    }

    return {
        subscribe,

        init() {
            initAudio();
        },

        /**
         * Play specific track
         * @param {'default'|'village'} trackName
         */
        playTrack(trackName) {
            initAudio();

            if (currentlyPlaying === trackName) {
                // already playing
                return;
            }

            // stop both first
            if (defaultBGM && !defaultBGM.paused) {
                defaultBGM.pause();
                defaultBGM.currentTime = 0;
            }
            if (villageBGM && !villageBGM.paused) {
                villageBGM.pause();
                villageBGM.currentTime = 0;
            }

            // small delay to ensure previous audio finishes stopping
            setTimeout(() => {
            update((/** @type {AudioState} */ state) => {
                    const shouldPlay = !state.isMuted;
                    if (shouldPlay) {
                        if (trackName === 'default' && defaultBGM) {
                            defaultBGM.currentTime = 0;
                            defaultBGM.play().catch(err => console.warn('⚠️ Audio play blocked:', err && err.message));
                        }
                        if (trackName === 'village' && villageBGM) {
                            villageBGM.currentTime = 0;
                            villageBGM.play().catch(err => console.warn('⚠️ Audio play blocked:', err && err.message));
                        }
                    }

                    return {
                        ...state,
                        currentTrack: trackName,
                        isPlaying: shouldPlay
                    };
                });
            }, 50);
        },

        toggleMute() {
            update((/** @type {AudioState} */ state) => {
                const newMuted = !state.isMuted;

                if (newMuted) {
                    if (defaultBGM) defaultBGM.pause();
                    if (villageBGM) villageBGM.pause();
                } else {
                    if (state.currentTrack === 'default' && defaultBGM) defaultBGM.play().catch(err => console.warn('Audio play failed:', err));
                    if (state.currentTrack === 'village' && villageBGM) villageBGM.play().catch(err => console.warn('Audio play failed:', err));
                }

                return {
                    ...state,
                    isMuted: newMuted,
                    isPlaying: !newMuted && state.currentTrack !== null
                };
            });
        },

        stopAll() {
            if (defaultBGM) {
                defaultBGM.pause();
                defaultBGM.currentTime = 0;
            }
            if (villageBGM) {
                villageBGM.pause();
                villageBGM.currentTime = 0;
            }
            currentlyPlaying = null;
            set({ isMuted: false, currentTrack: null, isPlaying: false });
        },

        saveCurrentTrack() {
            let snapshot;
            const unsubscribe = subscribe((/** @type {AudioState} */ s) => (snapshot = s));
            unsubscribe();
            if (snapshot && snapshot.currentTrack && typeof sessionStorage !== 'undefined') {
                sessionStorage.setItem('audioTrack', snapshot.currentTrack);
                sessionStorage.setItem('audioMuted', String(snapshot.isMuted));
            }
        },

        restoreSavedTrack() {
            if (typeof sessionStorage === 'undefined') return;
            const savedTrack = sessionStorage.getItem('audioTrack');
            const savedMuted = sessionStorage.getItem('audioMuted') === 'true';
            if (savedTrack) {
                update((/** @type {AudioState} */ state) => ({ ...state, isMuted: savedMuted }));
                if (!savedMuted && (savedTrack === 'default' || savedTrack === 'village')) {
                    // play after updating state
                    setTimeout(() => this.playTrack(savedTrack === 'default' ? 'default' : 'village'), 10);
                }
                sessionStorage.removeItem('audioTrack');
                sessionStorage.removeItem('audioMuted');
            }
        },

        destroy() {
            if (defaultBGM) {
                defaultBGM.pause();
                defaultBGM.src = '';
                try { defaultBGM.load(); } catch (e) {}
                defaultBGM = null;
            }
            if (villageBGM) {
                villageBGM.pause();
                villageBGM.src = '';
                try { villageBGM.load(); } catch (e) {}
                villageBGM = null;
            }
            initialized = false;
            currentlyPlaying = null;
            set({ isMuted: false, currentTrack: null, isPlaying: false });
        },

        /**
         * Set volume on both BGMs. If storyModeLock is active and force is false, this is a no-op.
         * @param {number} volume
         * @param {boolean} [force]
         */
        setVolume(volume, force = false) {
            if (storyModeLock && !force) return;
            if (defaultBGM) defaultBGM.volume = volume;
            if (villageBGM) villageBGM.volume = volume;
        },

    /** Lock volume to a specific value and remember previous volume */
    /** @param {number} volume */
    lockVolume(volume) {
            // clear any pending restore so lock is immediate
            if (_restoreTimer) {
                clearTimeout(_restoreTimer);
                _restoreTimer = null;
            }

            try {
                if (storyModeCount === 0) {
                    _lockedPrevVolume = this.getVolume();
                    storyModeLock = true;
                }
                storyModeCount++;
            } catch (e) {
                _lockedPrevVolume = 0.7;
                storyModeLock = true;
                storyModeCount = Math.max(1, storyModeCount);
            }
            this.setVolume(volume, true);
        },

        /** Unlock and restore previous volume */
        unlockVolume() {
            // decrease the reference count and only restore when it reaches zero.
            if (storyModeCount > 0) storyModeCount--;

            if (storyModeCount > 0) return;

            // debounce the restore to avoid flashing when navigating between slides.
            if (_restoreTimer) clearTimeout(_restoreTimer);
            _restoreTimer = setTimeout(() => {
                const prev = typeof _lockedPrevVolume === 'number' ? _lockedPrevVolume : 0.7;
                storyModeLock = false;
                _lockedPrevVolume = null;
                _restoreTimer = null;
                this.setVolume(prev, true);
            }, 260);
        },

        /** @returns {number} */
        getVolume() {
            if (defaultBGM) return defaultBGM.volume;
            if (villageBGM) return villageBGM.volume;
            return 0.7;
        }
    };
}

export const audioStore = createAudioStore();
