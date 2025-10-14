<script lang="ts">
    import { language, isFast, audioEnabled } from '$lib/store/story_lang_audio';

    const slide = {
        english: {
            text: "Lola Gloria's Flowerpot",
        },
        cebuano: {
            text: "Ang Paso ni Lola Gloria",
        },
        image: '/src/assets/LEVEL_3/STORY_2/PIC4.jpg'
    };

    let currentLanguage: 'english' | 'cebuano' = 'english';
    let currentIsFast: boolean = false;
    let audio: HTMLAudioElement | null = null;
    let isPlaying = false;
    let audioOn = false;

    language.subscribe((value: 'english' | 'cebuano') => {
        currentLanguage = value;
        if (audioOn) updateAudio();
    });
    isFast.subscribe((value: boolean) => {
        currentIsFast = value;
        if (audioOn) updateAudio();
    });
    audioEnabled.subscribe((v: boolean) => {
        audioOn = Boolean(v);
    });

    $: currentText = slide[currentLanguage].text;

    function updateAudio() {
        // No audio files specified for this slide in spec
        if (!audioOn) return;
        if (audio) audio.pause();
        audio = null;
        isPlaying = false;
    }

    function playAudio() {
        if (!audioOn || !audio) return;
        stopAudio();
        audio.currentTime = 0;
        audio.play();
        isPlaying = true;
    }

    function stopAudio() {
        if (!audioOn) return;
        if (audio) audio.pause();
        isPlaying = false;
    }

    function repeatSlide() {
        playAudio();
    }

    // Auto-play if enabled at render time
    if (audioOn) {
        updateAudio();
        playAudio();
    }
</script>

<div class="slide-container">
    <div class="image-wrapper">
        <img src={slide.image} alt="Story Scene" class="story-image" />
    </div>

    <div class="story-text">
        {currentText}
    </div>

    <div class="controls">
        {#if $audioEnabled}
        <button on:click={repeatSlide} class="kid-button bg-yellow-400 hover:bg-yellow-500 repeat-button">
            <span class="icon">🔄</span>
        </button>
        <button on:click={() => isFast.set(!$isFast)} class="kid-button bg-purple-400 hover:bg-purple-500 speed-button" class:fast={$isFast} class:slow={!$isFast}>
            <span class="icon">{$isFast ? '🐇' : '🐢'}</span>
        </button>
        {/if}
    </div>
</div>

<style>
    .slide-container {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        box-sizing: border-box;
    }

    .image-wrapper {
        flex: 1;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 0;
    }

    .story-image {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
        border-radius: 0.5rem;
        box-shadow: 0 6px 12px rgba(0,0,0,0.1);
    }

    .story-text {
        font-size: clamp(1rem, 2vw, 1.25rem);
        line-height: 1.6;
        color: #1f2937;
        text-align: center;
        padding: 0 1rem;
        margin: 0;
    }

    .controls {
        position: absolute;
        top: 1rem;
        right: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        z-index: 50;
    }

    .kid-button {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 3px 5px rgba(0, 0, 0, 0.2);
        display: flex;
        justify-content: center;
        align-items: center;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: linear-gradient(135deg,#fbbf24,#f59e0b);
    }

    .kid-button:hover { transform: scale(1.05); }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>
