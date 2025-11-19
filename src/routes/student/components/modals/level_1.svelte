<script lang="ts">
    import { slide, fade } from "svelte/transition";
    import Slide1 from "../../Levels/Level1/slide_1.svelte";
    import { language } from "$lib/store/story_lang_audio";
    import { goto } from '$app/navigation';
    import { studentData } from '$lib/store/student_data';
    import { preloadLevelAssets } from '$lib/utils/story_assets';

    type SvelteComponent = any;

    let StorySlide: SvelteComponent | null = null;
    export let storyKey: string = '';
    export let showModal: boolean = false;
    export let onClose: () => void = () => {};

    let currentSlide: number = 1;
    let isLoading: boolean = true;
    let showLanguageModal: boolean = false;
    let errorMessage: string = ''; // To display feedback when trying to skip
    
    // Asset preloading state
    let loadingProgress = 0;
    let loadingText = 'Loading story assets...';
    let totalAssets = 0;
    let loadedAssets = 0;

    // Define max slides per story (including the title slide at UI index 1)
    const maxSlidesMap: Record<string, number> = {
        'story1-1': 11, // was 10 narration slides -> +1 title
        'story1-2': 10,
        'story1-3': 10
    };

    // Define quiz slides per story (shifted by +1 because UI slide 1 is the title)
    const quizSlidesMap: Record<string, number[]> = {
        'story1-1': [6, 9, 10],
        'story1-2': [5, 7, 8],
        'story1-3': [5, 7, 8]
    };

    // Map slide numbers to question IDs
    const questionIdMap: Record<string, Record<number, string>> = {
        // shifted by +1 to match the UI indexing where slide 1 is the title
        'story1-1': {
            6: 'story1_q1',
            9: 'story1_q2',
            10: 'story1_q3'
        },
        'story1-2': {
            5: 'story1_2_q1',
            7: 'story1_2_q2a',
            8: 'story1_2_q2b'
        },
        'story1-3': {
            5: 'story1_3_q1',
            7: 'story1_3_q2a',
            8: 'story1_3_q2b'
        }
    };

    // Get maxSlides based on storyKey, default to 10 if unknown
    $: maxSlides = maxSlidesMap[storyKey] || 10;

    // Check if the current slide is answered
    $: isCurrentSlideAnswered = (() => {
        const quizSlides = quizSlidesMap[storyKey] || [];
        if (quizSlides.includes(currentSlide)) {
            const questionId = questionIdMap[storyKey]?.[currentSlide];
            return questionId && $studentData?.answeredQuestions?.[questionId] !== undefined;
        }
        return true; // Non-quiz slides are always "answered"
    })();

    async function loadStorySlide(key: string, slideNumber: number = 1): Promise<void> {
        console.log('Attempting to load story slide:', { key, slideNumber, currentSlide });
        
        if (slideNumber > maxSlides) {
            console.log('Slide number exceeds max slides');
            return;
        }

        try {
            // UI slide 1 is the title-only slide (slide_title.svelte); subsequent UI slides map to existing slide_N files
            let path;
            if (slideNumber === 1) {
                path = `../../Levels/Level1/${key}/slide_title.svelte`;
            } else {
                // shift UI index to file index: UI 2 -> slide_1.svelte, UI 3 -> slide_2.svelte, etc.
                const fileIndex = slideNumber - 1;
                path = `../../Levels/Level1/${key}/slide_${fileIndex}.svelte`;
            }
            console.log('Loading slide from path:', path);
            const module = await import(/* @vite-ignore */ path);
            console.log('Module loaded:', module);
            StorySlide = module.default;
            currentSlide = slideNumber;
            console.log('Slide loaded successfully, current slide:', currentSlide);
        } catch (error) {
            console.error("Failed to load story slide:", error);
            StorySlide = null;
            if (slideNumber > 1) {
                console.log('Attempting to load previous slide');
                currentSlide = slideNumber - 1;
                await loadStorySlide(key, currentSlide);
            }
        }
    }

    function toggleLanguageModal(): void {
        showLanguageModal = !showLanguageModal;
    }

    function setLanguage(lang: string): void {
        language.set(lang);
        showLanguageModal = false;
    }

    async function nextSlide(): Promise<void> {
        const quizSlides = quizSlidesMap[storyKey] || [];
        if (quizSlides.includes(currentSlide)) {
            const questionId = questionIdMap[storyKey]?.[currentSlide];
            if (questionId && !$studentData?.answeredQuestions?.[questionId]) {
                // Prevent navigation and show error message
                errorMessage = 'Please select an answer before proceeding.';
                setTimeout(() => (errorMessage = ''), 3000); // Clear message after 3 seconds
                return;
            }
        }

        errorMessage = ''; // Clear any existing error message
        if (currentSlide < maxSlides) {
            console.log('Moving to next slide:', currentSlide + 1);
            language.set('english');
            await loadStorySlide(storyKey, currentSlide + 1);
        } else {
            try {
                console.log('Loading final slide');
                const module = await import('../../Levels/Level1/slide_last.svelte');
                StorySlide = module.default;
                currentSlide = maxSlides + 1;
            } catch (error) {
                console.error("Failed to load final slide:", error);
            }
        }
    }

    async function prevSlide(): Promise<void> {
        if (currentSlide > 1) {
            console.log('Moving to previous slide:', currentSlide - 1);
            StorySlide = null;
            language.set('english');
            if (currentSlide === maxSlides + 1) {
                await loadStorySlide(storyKey, maxSlides);
            } else {
                await loadStorySlide(storyKey, currentSlide - 1);
            }
        }
    }

    async function closeModal(): Promise<void> {
        onClose();
        showModal = false;
        currentSlide = 1;
        isLoading = true;
        StorySlide = null;
        showLanguageModal = false;
        storyKey = '';
        try {
            localStorage.removeItem('retakeLevel1');
            localStorage.removeItem('openStory1Modal');
        } catch {}
        await goto('/student/dashboard');
    }

    $: if (showModal && isLoading) {
        // Preload all Level 1 assets when modal opens
        preloadLevelAssets('Level1', (loaded, total, type, url) => {
            loadedAssets = loaded;
            totalAssets = total;
            loadingProgress = Math.floor((loaded / total) * 100);
            
            // Update loading text based on what's being loaded
            if (type === 'image') {
                loadingText = `Loading images... ${loaded}/${total}`;
            } else {
                loadingText = `Loading audio... ${loaded}/${total}`;
            }
        }).then(() => {
            loadingText = 'Ready!';
            loadingProgress = 100;
            // Small delay to show 100% before hiding
            setTimeout(() => {
                isLoading = false;
            }, 300);
        }).catch(err => {
            console.error('Asset preloading failed:', err);
            // Continue anyway
            isLoading = false;
        });
    }

    $: if (showModal && storyKey) {
        console.log('Modal and storyKey detected, loading slide');
        language.set('english');
        try { localStorage.setItem('pending_story', storyKey); } catch {}
        loadStorySlide(storyKey, 1);
    }

    $: console.log('Current state:', { storyKey, currentSlide, showModal, isLoading, StorySlide, isCurrentSlideAnswered });
</script>

{#if showModal}
    {#if isLoading}
            <div
                class="fixed inset-0 bg-gray-800 bg-opacity-75 flex flex-col items-center justify-center z-50"
            >
            <div
                class="w-[10vw] h-[10vw] max-w-[60px] max-h-[60px] border-[1vw] border-t-lime-500 border-gray-300 rounded-full animate-spin"
            ></div>
            <p class="text-[4vw] md:text-xl text-white mt-[8vh] font-bold">
                Loading Adventure... ✨
            </p>
            
            <!-- Progress Bar -->
            <div class="w-[60vw] max-w-[400px] mt-6">
                <div class="bg-gray-700 rounded-full h-4 overflow-hidden">
                    <div 
                        class="bg-lime-500 h-full transition-all duration-300 ease-out"
                        style="width: {loadingProgress}%"
                    ></div>
                </div>
                <div class="text-white text-center mt-2 text-sm">
                    {loadingText}
                </div>
                <div class="text-white text-center text-xl font-bold mt-1">
                    {loadingProgress}%
                </div>
            </div>
        </div>
    {/if}

    {#if !isLoading}
        <div
            class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50"
        >
            <div
                class="modal-content-wrapper bg-white rounded-[2vw] shadow-2xl w-[90vw] max-w-[1000px] h-[90vh] max-h-[800px]"
            >
                <div class="content-wrapper">
                    {#key `${storyKey}-${currentSlide}`}
                        {#if !storyKey}
                            <Slide1 />
                        {:else if StorySlide}
                            <!-- Pass storyKey so slides that expect it (like slide_last) receive the prop -->
                            <svelte:component this={StorySlide} {storyKey} />
                        {/if}
                    {/key}
                </div>

                <!-- Language Button -->
                <div class="language-button-container">
                    <button 
                        on:click={toggleLanguageModal}
                        class="language-button bg-blue-500 hover:bg-blue-600"
                        aria-label="Change Language"
                    >
                        <span class="icon">{$language === 'english' ? '🔠' : '🔠'}</span>
                    </button>
                </div>

                <!-- Language Modal -->
                {#if showLanguageModal}
                    <div class="modal-overlay" role="dialog" aria-labelledby="language-dialog-title">
                        <div class="modal-content">
                            <h2 id="language-dialog-title" class="text-2xl font-bold mb-4">Choose Language</h2>
                            <button 
                                on:click={() => setLanguage('english')}
                                class="language-option"
                                class:active={$language === 'english'}
                                type="button"
                            >
                                English
                            </button>
                            <button 
                                on:click={() => setLanguage('cebuano')}
                                class="language-option"
                                class:active={$language === 'cebuano'}
                                type="button"
                            >
                                Cebuano
                            </button>
                            <button 
                                class="mt-4 px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                                on:click={toggleLanguageModal}
                            >
                                Close
                            </button>
                        </div>
                    </div>
                {/if}

                <!-- Navigation Buttons -->
                {#if StorySlide}
                    <button
                        class="nav-button right-button"
                        on:click={nextSlide}
                        aria-label="Next slide"
                        disabled={!isCurrentSlideAnswered}
                        class:disabled={!isCurrentSlideAnswered}
                    >
                        ➡️
                    </button>
                
                    {#if currentSlide > 1}
                        <button
                            class="nav-button left-button"
                            on:click={prevSlide}
                            aria-label="Previous slide"
                        >
                            ⬅️
                        </button>
                    {/if}
                {/if}

                <!-- Error Message for Unanswered Questions -->
                {#if errorMessage}
                    <div class="error-message">
                        {errorMessage}
                    </div>
                {/if}
            </div>

            <!-- Close Button -->
            <button
                class="exit-button"
                on:click={closeModal}
                aria-label="Close modal"
            >
                ✕
            </button>
        </div>
    {/if}
{/if}

<style>
    .modal-content-wrapper {
        display: flex;
        position: relative;
        overflow: hidden;
        padding: 2rem;
    }

    .content-wrapper {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .nav-button {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        font-size: clamp(2rem, 8vw, 4rem);
        color: #10B981;
        background: none;
        border: none;
        cursor: pointer;
        padding: 1rem;
        transition: all 0.2s;
        z-index: 10;
    }

    .nav-button:hover:not(.disabled) {
        color: #059669;
        transform: translateY(-50%) scale(1.1);
    }

    .nav-button.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .right-button {
        right: 1vw;
    }

    .left-button {
        left: 1vw;
    }

    .exit-button {
        position: fixed;
        top: 2vh;
        right: 2vw;
        background: #EF4444;
        color: white;
        border: none;
        border-radius: 50%;
        width: clamp(40px, 10vw, 60px);
        height: clamp(40px, 10vw, 60px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: clamp(1rem, 5vw, 1.5rem);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.2s;
    }

    .exit-button:hover {
        background: #DC2626;
        transform: scale(1.1);
    }

    .language-button-container {
        position: absolute;
        top: 1rem;
        right: 4rem;
        z-index: 20;
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

    .icon {
        font-size: 1.5rem;
        font-weight: bold;
    }

    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 30;
    }

    .modal-content {
        background: white;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        max-width: 90vw;
        width: 400px;
    }

    .language-option {
        width: 100%;
        padding: 0.75rem;
        margin: 0.5rem 0;
        border-radius: 0.5rem;
        border: none;
        color: white;
        font-size: 1.25rem;
        font-weight: bold;
        background-color: #10b98183;
        cursor: pointer;
        transition: all 0.2s;
    }

    .language-option:hover {
        transform: scale(1.05);
        background-color: #059669;
    }

    .language-option.active {
        background-color: #059669;
        border: 2px solid #047857;
    }

    .error-message { 
        position: absolute;
        bottom: 2rem;
        left: 50%;
        transform: translateX(-50%);
        background: #dc2626;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: bold;
        z-index: 20;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }
</style>