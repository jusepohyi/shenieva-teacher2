<script>
    import { onMount, onDestroy } from 'svelte';
    import { fade } from 'svelte/transition';
    import { language, isFast, audioEnabled } from '$lib/store/story_lang_audio';
    import { audioStore } from '$lib/store/audio_store';

    const slide = {
        english: {
            text: "Hector's Health",
            audioFast: '/audio/fast.mp3',
            audioSlow: '/audio/slow.mp3'
        },
        cebuano: {
            text: "Ang Panlawas ni Hector",
            audioFast: '/audio/cebuano/fast.mp3',
            audioSlow: '/audio/cebuano/slow.mp3'
        },
        image: '/src/assets/LEVEL_2/STORY_1/PIC6.jpg'
    };

    let currentLanguage;
    let currentIsFast;
    let audio;
    let isPlaying = false;
    let audioOn = false;
    let bgMusicWasPlaying = false;

    language.subscribe(value => {
        currentLanguage = value;
        if (audioOn) updateAudio();
    });
    isFast.subscribe(value => {
        currentIsFast = value;
        if (audioOn) updateAudio();
    });
    audioEnabled.subscribe(v => {
        audioOn = !!v;
    });

    $: currentText = slide[currentLanguage].text;

    function updateAudio() {
        if (!audioOn) return;
        if (audio) audio.pause();
        audio = new Audio(
            currentIsFast 
                ? slide[currentLanguage].audioFast 
                : slide[currentLanguage].audioSlow
        );
        if (isPlaying) playAudio();
    }

    onMount(() => {
        // Pause background music when story starts
        const unsubscribe = audioStore.subscribe(state => {
            bgMusicWasPlaying = state.isPlaying;
        });
        
        if (audioOn) {
            updateAudio();
            playAudio();
        }
        
        return unsubscribe;
    });

    onDestroy(() => {
        stopAudio();
    });

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
    /* Animations removed for immediate rendering */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes textFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes fadeOut {
        from { opacity: 0; }
        to { opacity: 0; }
    }
</style>
