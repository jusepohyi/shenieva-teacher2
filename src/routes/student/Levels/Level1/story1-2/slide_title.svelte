<script>
    import { language } from '$lib/store/story_lang_audio';
    import { onMount, onDestroy } from 'svelte';
    import { audioStore } from '$lib/store/audio_store';

    const story = {
        title: {
            english: "Candice and Candies",
            cebuano: "Si Candice ug ang mga Kendi"
        },
        image: '/src/assets/LEVEL_1/STORY_2/PIC1.jpg'
    };

    let audioEl = null;
    let isPlaying = false;
    $: audioSrc = encodeURI('/src/assets/audio/story-telling/Level_1/story_2/title/CANDIES AND CANDIES TITLE.mp3');

    let startTimer = null;
    let playToken = 0;
    function safeStart(src){
        if (!audioEl) return;
        playToken++;
        const token = playToken;
        try { audioEl.pause(); } catch(e){}
        audioEl.src = src;
        if (startTimer) { clearTimeout(startTimer); startTimer = null; }
        startTimer = setTimeout(()=>{
            if (token !== playToken) return;
            audioEl.play().then(()=>{ isPlaying = true; }).catch(()=>{ isPlaying = false; });
            startTimer = null;
        }, 120);
    }

    function enterStoryMode(){
        try { audioStore.init(); } catch(e){}
        try { audioStore.lockVolume(0.09); } catch(e){ audioStore.setVolume(0.09, true); }
    }

    function handleAudioEnd(){ isPlaying = false; }

    onMount(()=>{
        setTimeout(()=>{ enterStoryMode(); safeStart(audioSrc); }, 80);
        const userGesture = () => { enterStoryMode(); safeStart(audioSrc); window.removeEventListener('pointerdown', userGesture); window.removeEventListener('keydown', userGesture); };
        window.addEventListener('pointerdown', userGesture, { once: true });
        window.addEventListener('keydown', userGesture, { once: true });
    });

    onDestroy(()=>{
        try{ audioEl && audioEl.pause(); } catch(e){}
        try{ audioStore.unlockVolume(); } catch(e){}
    });
</script>

<div class="slide-container">
    <h1 class="title">
        {$language === 'english' ? story.title.english : story.title.cebuano}
    </h1>

    <div class="image-wrapper">
        <img src={story.image} alt="Story scene" class="story-image" />
    </div>

    <div class="story-text" aria-hidden="true"></div>
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
        min-height: 0;
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
</style>
<audio bind:this={audioEl} on:ended={handleAudioEnd} on:pause={()=>{ isPlaying = false; }}></audio>
