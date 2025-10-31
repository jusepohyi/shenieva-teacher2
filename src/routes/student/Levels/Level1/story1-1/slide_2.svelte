<script>
    import { fade } from "svelte/transition";
    import { language } from "$lib/store/story_lang_audio";
    import { onDestroy, onMount } from 'svelte';
    import { audioStore } from '$lib/store/audio_store';

    // Audio state
    let speed = 'normal'; // 'slow' | 'normal' | 'fast'
    let isPlaying = false;
    /** @type {HTMLAudioElement | null} */
    let audioEl = null;

    // compute audio src based on language and speed (M2 files for slide 2)
    $: audioSrc = (() => {
        const langFolder = $language === 'english' ? 'english' : 'cebuano';
    const base = '/assets/audio/story-telling/Level_1/story_1';
        const speedFolder = speed === 'slow' ? 'slow' : (speed === 'fast' ? 'fast' : 'normal');
        return `${base}/${speedFolder}/slide_2/M2.mp3`;
    })();

    // play/pause toggle
    function togglePlay() {
        if (!audioEl) return;
        if (isPlaying) {
            audioEl.pause();
            isPlaying = false;
        } else {
            // attempt to start; safeStart will set isPlaying when play resolves
            safeStart(audioSrc);
        }
    }

    // removed reactive restart to avoid play/pause race; safeStart() handles playback sequencing

    onDestroy(() => {
        if (audioEl) {
            try { audioEl.pause(); } catch (e) {}
            audioEl = null;
        }
    });

    // Story mode state and BGM ducking (duck to 9%)
    /** @type {number | null} */
    let _savedBgmVolume = null;
    /** @type {boolean} */
    let storyModeActive = false;
    // narration volume is left at its normal level; do not override
    /** @type {number | null} */
    let _startTimer = null;
    /** @type {number} */
    let playToken = 0;
    /** @param {string} src */
    function safeStart(src) {
        if (!audioEl) return;
        if (_startTimer) { clearTimeout(_startTimer); _startTimer = null; }
        // increase play token to invalidate previous attempts
        playToken++;
        const localToken = playToken;
        try { audioEl.pause(); } catch (e) {}
        audioEl.src = src;
        // small debounce to avoid play/pause race
        _startTimer = setTimeout(() => {
            if (!audioEl) { _startTimer = null; return; }
            if (localToken !== playToken) { _startTimer = null; return; }
            // no narration-volume modification here; keep audioEl volume as-is
            audioEl.play().then(() => { isPlaying = true; }).catch((/** @type {any} */ err) => { console.warn('safeStart play failed', err); isPlaying = false; });
            _startTimer = null;
        }, 150);
    }

    function enterStoryMode() {
        if (!storyModeActive) {
            try { audioStore.init(); } catch (e) {}
            try { _savedBgmVolume = audioStore.getVolume(); } catch (e) { _savedBgmVolume = 0.7; }
            // lock the bgm volume so other code cannot override it while narration is active
            try { audioStore.lockVolume(0.09); } catch (e) { audioStore.setVolume(0.09, true); }
            storyModeActive = true;
        }
    }
    async function lowerBgm() {
        // kept for compatibility; enterStoryMode preferred
        enterStoryMode();
    }

    function exitStoryMode() {
        if (storyModeActive) {
            const v = typeof _savedBgmVolume === 'number' ? _savedBgmVolume : 0.7;
            try { audioStore.unlockVolume(); } catch (e) { audioStore.setVolume(v, true); }
            _savedBgmVolume = null;
            // narration volume left unchanged; restore only BGM
            storyModeActive = false;
        }
    }

    // container ref to detect visibility
    let containerEl = null;
    /** @type {IntersectionObserver | null} */
    let io = null;

    function startNarration() {
        if (!audioEl) return;
        console.log('[story] startNarration called, audioEl:', audioEl, 'audioSrc:', audioSrc);
        enterStoryMode();
        safeStart(audioSrc);
    }

    // autoplay narration when component mounts and when slide becomes visible
    onMount(() => {
        // attempt a quick autoplay on mount (may be blocked by browser)
        setTimeout(() => { console.log('[story] onMount - attempting startNarration'); startNarration(); }, 80);

        // Setup IntersectionObserver to autoplay when slide becomes visible
        if (typeof IntersectionObserver !== 'undefined') {
            io = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    console.log('[story] IntersectionObserver entry', entry.intersectionRatio, entry.isIntersecting);
                    if (entry.isIntersecting && entry.intersectionRatio > 0.5) {
                        startNarration();
                    }
                });
            }, { threshold: [0.5] });
            if (containerEl) {
                io.observe(containerEl);
                console.log('[story] IntersectionObserver observing containerEl', containerEl);
            }
        }

        // If autoplay is blocked, listen for first user gesture and then start narration.
        const userGestureHandler = () => {
            console.log('[story] user gesture detected - attempting startNarration');
            try { startNarration(); } catch (e) { console.warn('startNarration failed on user gesture', e); }
            window.removeEventListener('pointerdown', userGestureHandler);
            window.removeEventListener('keydown', userGestureHandler);
        };

        window.addEventListener('pointerdown', userGestureHandler, { once: true });
        window.addEventListener('keydown', userGestureHandler, { once: true });
    });

    // watch for speed changes to auto-play new narration
    $: if (audioEl && speed) {
        // set new src and start playback whenever user selects a speed
        enterStoryMode();
        safeStart(audioSrc);
    }

    // On narration end/stop — leave story mode active (do not restore BGM yet)
    function handleAudioEnd() {
        isPlaying = false;
        // don't restore BGM here: keep low while user is in story mode
    }

    onDestroy(() => {
        if (io) {
            try { io.disconnect(); } catch (e) {}
            io = null;
        }
        // exit story mode and restore bgm
        exitStoryMode();
    });

    const slide = {
        text: {
            english: "One afternoon, a regular customer and her trusted friend, Maria, came to Lena's store and bought a pack of salt, one kilo of rice, and a pack of sugar.",
            cebuano: "Sa usa ka hapon, usa sa iyang suki ug kasaligan nga higala nga si Maria miabot sa tindahan ug nipalit ug usa ka pakete sa asin, usa ka kilo nga bugas, ug usa ka pakete nga asukal."
        },
    image: "/assets/LEVEL_1/STORY_1/PIC2.jpg"
    };
</script>

<div class="slide-container">
    <div class="top-left-audio">
        <div class="audio-indicator">
            <span class="dot" class:playing={isPlaying}></span>
            <span class="label">Narration</span>
        </div>
        <div class="speed-select compact">
            <label class="chip {speed === 'normal' ? 'active' : ''}">
                <input type="radio" name="speed" bind:group={speed} value="normal" />
                <span class="txt">Normal</span>
            </label>
            <label class="chip {speed === 'slow' ? 'active' : ''}">
                <input type="radio" name="speed" bind:group={speed} value="slow" />
                <span class="txt">Slow</span>
            </label>
            <label class="chip {speed === 'fast' ? 'active' : ''}">
                <input type="radio" name="speed" bind:group={speed} value="fast" />
                <span class="txt">Fast</span>
            </label>
        </div>
    </div>

    <h1 class="title">
        {$language === 'english' ? "" : ""}
    </h1>

    <div class="image-wrapper">
        <img
            src={slide.image}
            alt="Story Scene"
            class="story-image"
        />
    </div>

    <div class="story-text" transition:fade>
        {$language === 'english' ? slide.text.english : slide.text.cebuano}
    </div>
    
    <!-- compact controls moved to top-left -->

    <!-- audio src is managed imperatively to avoid Svelte updating src while playback is in-flight -->
    <audio bind:this={audioEl} on:ended={handleAudioEnd} on:pause={() => { isPlaying = false; }}></audio>
</div>

<style>
    .slide-container {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        box-sizing: border-box;
    }

    .title {
        font-size: clamp(1.5rem, 3vw, 2.5rem);
        font-weight: bold;
        color: #1e40af;
        text-align: center;
        margin: 0;
    }

    .image-wrapper {
        flex: 1;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 0;  /* This is important for flex child to shrink */
    }

    .story-image {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
        border-radius: 0.5rem;
    }

    .story-text {
        font-size: clamp(1rem, 2vw, 1.25rem);
        line-height: 1.6;
        color: #1f2937;
        text-align: center;
        padding: 0 1rem;
        margin: 0;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .slide-container {
        animation: fadeIn 0.5s ease-in-out;
    }

    /* Audio control UI */
    .top-left-audio {
        position: absolute;
        top: 12px;
        left: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
        z-index: 30;
        background: rgba(255,255,255,0.85);
        padding: 6px 8px;
        border-radius: 10px;
        box-shadow: 0 6px 14px rgba(0,0,0,0.08);
    }

    .audio-indicator { display:flex; align-items:center; gap:8px; }
    .audio-indicator .dot { width:10px; height:10px; border-radius:50%; background:#d1d5db; display:inline-block; }
    .audio-indicator .dot.playing { background: #10b981; box-shadow:0 0 6px #10b981; }
    .audio-indicator .label { font-size:12px; color:#374151; font-weight:600; }

    .speed-select.compact { display:flex; gap:6px; }
    .speed-select.compact .chip { display:inline-flex; align-items:center; gap:6px; padding:6px 8px; border-radius:999px; background:#f3f4f6; cursor:pointer; font-size:12px; }
    .speed-select.compact .chip.active { background:#e6fffa; border:1px solid #10b981; }
    .speed-select.compact input { display:none; }
    .speed-select.compact .txt { color:#111827; font-weight:600; }
</style>