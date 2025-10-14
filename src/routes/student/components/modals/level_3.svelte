<script lang="ts">
    import Slide1 from "../../Levels/Level3/slide_1.svelte";
    import { language } from "$lib/store/story_lang_audio";
    import { studentData } from '$lib/store/student_data';

    type SvelteComponent = any;
    let StorySlide: any = null;

    export let showModal: boolean = false;
    export let onClose: () => void = () => {};
    export let storyKey: string = '';

    let currentSlide: number = 1;
    let isLoading: boolean = true;
    let showLanguageModal: boolean = false;
    let errorMessage: string = '';

    const maxSlidesMap: Record<string, number> = {
        'story3-1': 8,
        'story3-2': 8,
        'story3-3': 8
    };

    $: maxSlides = maxSlidesMap[storyKey] || 8;

    // Determine if the current slide allows moving next
    $: isCurrentSlideAnswered = (() => {
        // Gate for story3-1 slide 8 (essay questions)
        if (storyKey === 'story3-1' && currentSlide === 8) {
            const answered = $studentData?.answeredQuestions || {};
            const a1 = (answered['story3-1_q1'] || '').trim();
            const a2 = (answered['story3-1_q2'] || '').trim();
            const a3 = (answered['story3-1_q3'] || '').trim();
            return Boolean(a1 && a2 && a3);
        }
        // Gate for story3-2 slide 8 (essay questions)
        if (storyKey === 'story3-2' && currentSlide === 8) {
            const answered = $studentData?.answeredQuestions || {};
            const a1 = (answered['story3-2_q1'] || '').trim();
            const a2 = (answered['story3-2_q2'] || '').trim();
            const a3 = (answered['story3-2_q3'] || '').trim();
            return Boolean(a1 && a2 && a3);
        }
        // Gate for story3-3 slide 8 (essay questions)
        if (storyKey === 'story3-3' && currentSlide === 8) {
            const answered = $studentData?.answeredQuestions || {};
            const a1 = (answered['story3-3_q1'] || '').trim();
            const a2 = (answered['story3-3_q2'] || '').trim();
            const a3 = (answered['story3-3_q3'] || '').trim();
            return Boolean(a1 && a2 && a3);
        }
        return true;
    })();

    async function loadStorySlide(key: string, slideNumber: number = 1): Promise<void> {
        try {
            const path = `../../Levels/Level3/${key}/slide_${slideNumber}.svelte`;
            const module = await import(/* @vite-ignore */ path);
            StorySlide = module.default;
            currentSlide = slideNumber;
            // Slide successfully loaded; ensure UI is interactive
            isLoading = false;
        } catch (error) {
            console.error('Failed to load story3 slide', error);
            StorySlide = null;
            if (slideNumber > 1) {
                currentSlide = slideNumber - 1;
                await loadStorySlide(key, currentSlide);
            }
        }
    }

    // Only keep the chooser slide statically for Level 3; story slides are dynamically imported per storyKey
    const baseSlides: SvelteComponent[] = [Slide1];
    let totalSlides: number = baseSlides.length - 1; // will be 0

    $: if (showModal && isLoading) {
        setTimeout(() => {
            isLoading = false;
        }, 1000);
    }

    async function nextSlide(): Promise<void> {
        // Gate progression when current slide requires answers
        if (!isCurrentSlideAnswered) {
            // Optional: set a message
            errorMessage = 'Please answer all questions before proceeding.';
            setTimeout(() => (errorMessage = ''), 2000);
            return;
        }
        if (!storyKey) return;
        const max = maxSlidesMap[storyKey] || 8;
        if (currentSlide < max) {
            await loadStorySlide(storyKey, currentSlide + 1);
        } else if (currentSlide === max) {
            // Load final slide when exceeding max
            try {
                const module = await import('../../Levels/Level3/slide_last.svelte');
                StorySlide = module.default;
                currentSlide = max + 1;
            } catch (e) {
                console.error('Failed to load Level 3 final slide', e);
            }
        }
    }

    async function prevSlide(): Promise<void> {
        if (!storyKey) return;
        const max = maxSlidesMap[storyKey] || 8;
        if (currentSlide === max + 1) {
            await loadStorySlide(storyKey, max);
            return;
        }
        if (currentSlide > 1) {
            await loadStorySlide(storyKey, currentSlide - 1);
        }
    }

    function closeModal(): void {
        onClose();
        showModal = false;
        currentSlide = 0;
        isLoading = true;
        totalSlides = baseSlides.length - 1;
        showLanguageModal = false;
        currentSlide = 1;
    }

    function toggleLanguageModal(): void {
        showLanguageModal = !showLanguageModal;
    }

    function setLanguage(lang: string): void {
        language.set(lang);
        showLanguageModal = false;
    }

    // Vendor logic removed for Level 3

    $: if (showModal && storyKey) {
        console.log('Level3 modal detected storyKey, loading story slide');
        language.set('english');
        try { localStorage.setItem('pending_story', storyKey); } catch {}
        loadStorySlide(storyKey, 1);
    }

    function handleKeydown(e: KeyboardEvent) {
        if (!showModal || !StorySlide) return;
        if (e.key === 'ArrowRight' || e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            nextSlide();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            prevSlide();
        }
    }
</script>

<svelte:window on:keydown={handleKeydown} />

{#if showModal}
    {#if isLoading}
        <div
            class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50"
        >
            <div
                class="w-[10vw] h-[10vw] max-w-[60px] max-h-[60px] border-[1vw] border-t-lime-500 border-gray-300 rounded-full animate-spin"
            ></div>
            <p class="absolute text-[4vw] md:text-xl text-white mt-[15vh] font-bold">
                Loading Adventure... ✨
            </p>
        </div>
    {/if}

    {#if !isLoading}
        <div
            class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50"
        >
            <div
                class="bg-white p-0 rounded-[3vw] shadow-2xl w-[250vw] max-w-[1100px] h-[85vh] flex flex-col items-center justify-between relative"
            >
                <div class="w-[100%] h-[100%] flex flex-col items-center justify-center">
                        {#key `${storyKey}-${currentSlide}`}
                            {#if storyKey}
                                {#if StorySlide}
                                    <svelte:component this={StorySlide} {storyKey} />
                                {:else}
                                    <div class="p-4">Loading story...</div>
                                {/if}
                            {:else}
                                <!-- Show the Level 3 chooser slide only when no storyKey is provided -->
                                <Slide1 />
                            {/if}
                        {/key}
                        {#if StorySlide}
                            <!-- Invisible hotspots for navigation -->
                            <div aria-hidden="true" class="hotspot hotspot-right" on:click={nextSlide}></div>
                            {#if currentSlide > 1}
                                <div aria-hidden="true" class="hotspot hotspot-left" on:click={prevSlide}></div>
                            {/if}
                        {/if}
                </div>

                <!-- Language Button with Active Language Indicator -->
                <div class="language-button-container">
                    <button 
                        on:click={toggleLanguageModal}
                        class="language-button bg-blue-500 hover:bg-blue-600"
                    >
                        <span class="icon">{$language === 'english' ? '🔠' : '🔠'}</span>
                    </button>
                </div>

                <!-- Language Modal with Active Highlight -->
                {#if showLanguageModal}
                    <div class="modal-overlay">
                        <div class="modal-content" role="dialog" aria-modal="true">
                            <h2 class="text-2xl font-bold mb-4">Choose Language</h2>
                            <button 
                                on:click={() => setLanguage('english')}
                                class="language-option"
                                class:active={$language === 'english'}
                            >
                                English
                            </button>
                            <button 
                                on:click={() => setLanguage('cebuano')}
                                class="language-option"
                                class:active={$language === 'cebuano'}
                            >
                                Cebuano
                            </button>
                            <button class="mt-4 px-4 py-2 bg-gray-200 rounded hover:bg-gray-300" on:click={toggleLanguageModal} type="button">Close</button>
                        </div>
                    </div>
                {/if}

                {#if StorySlide}
                    <button
                        class="nav-button absolute right-[1vw] top-1/2 transform -translate-y-1/2 text-[8vw] md:text-6xl text-lime-500 hover:text-lime-600 transition-transform duration-200"
                        on:click={nextSlide}
                        disabled={!isCurrentSlideAnswered}
                        aria-label="Next slide"
                        type="button"
                    >
                        ➡️
                    </button>
                {/if}
                {#if StorySlide && currentSlide > 1}
                    <button
                        class="nav-button absolute left-[1vw] top-1/2 transform -translate-y-1/2 text-[8vw] md:text-6xl text-lime-500 hover:text-lime-600 transition-transform duration-200"
                        on:click={prevSlide}
                        aria-label="Previous slide"
                        type="button"
                    >
                        ⬅️
                    </button>
                {/if}
                {#if errorMessage}
                    <div class="error-msg">{errorMessage}</div>
                {/if}
            </div>
        </div>

        <button
            class="fixed top-[2vh] right-[2vw] bg-red-500 text-white rounded-full w-[10vw] h-[10vw] max-w-[60px] max-h-[60px] flex items-center justify-center text-[5vw] md:text-2xl shadow-lg hover:bg-red-600 transition-all duration-200 z-50 exit-button"
            on:click={closeModal}
        >
            ✕
        </button>
    {/if}
{/if}

<style>
    button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .nav-button { 
        z-index: 3000; 
        pointer-events: auto; 
    }

    /* Click hotspots to improve navigation on touch/overlays */
    .hotspot {
        position: absolute;
        top: 0;
        bottom: 0;
        pointer-events: auto;
        z-index: 2000;
    }
    .hotspot-right { right: 0; width: 25%; }
    .hotspot-left { left: 0; width: 25%; }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }

    button:active:not(:disabled) {
        transform: scale(1.1);
    }

    .exit-button:hover {
        transform: scale(1.1);
    }

    /* Language Button Styles */
    .language-button-container {
        position: absolute;
        top: 1rem;
        right: 4rem;
        z-index: 1000;
    }

    .language-button {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 3px 5px rgba(0, 0, 0, 0.2);
        display: flex;
        justify-content: center;
        align-items: center;
        transition: transform 0.2s ease;
    }

    .language-button:hover {
        transform: scale(1.1);
    }

    .language-button:active {
        animation: bounce 0.3s ease;
    }

    .icon {
        font-size: 1.5rem;
        font-weight: bold;
    }

    /* Language Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2000;
    }

    .modal-content {
        background: white;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        text-align: center;
    }

    .language-option {
        display: block;
        width: 100%;
        padding: 0.75rem;
        margin: 0.5rem 0;
        border-radius: 0.5rem;
        color: white;
        font-size: 1.25rem;
        font-weight: bold;
        transition: transform 0.2s ease;
        background-color: #10b98183; /* Default green */
    }

    .language-option:hover {
        transform: scale(1.05);
    }

    .language-option.active {
        background-color: #06d594; /* Darker green for active */
        border: 2px solid #047857; /* Border to emphasize active state */
    }

    @keyframes bounce {
        0% { transform: scale(1); }
        50% { transform: scale(0.9); }
        100% { transform: scale(1); }
    }

    .error-msg {
        position: absolute;
        bottom: 1rem;
        left: 50%;
        transform: translateX(-50%);
        background: #FDE68A;
        color: #92400E;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        z-index: 3001;
    }
</style>