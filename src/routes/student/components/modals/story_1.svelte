<script lang="ts">
    import { slide, fade } from "svelte/transition";
    import Slide1 from "../../Stories/Story1/slide_1.svelte";
    import { language } from "$lib/store/story_lang_audio";

    type SvelteComponent = any;

    let StorySlide: SvelteComponent | null = null;
    export let storyKey: string = '';
    export let showModal: boolean = false;
    export let onClose: () => void = () => {};

    let currentSlide: number = 1;
    let isLoading: boolean = true;
    let showLanguageModal: boolean = false;
    let maxSlides = 10;

    async function loadStorySlide(key: string, slideNumber: number = 1): Promise<void> {
        console.log('Attempting to load story slide:', { key, slideNumber, currentSlide });
        
        if (slideNumber > maxSlides) {
            console.log('Slide number exceeds max slides');
            return;
        }

        try {
            const path = `../../Stories/Story1/${key}/slide_${slideNumber}.svelte`;
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
        if (currentSlide < maxSlides) {
            console.log('Moving to next slide:', currentSlide + 1);
            StorySlide = null;
            language.set('english'); // Set English as default for new slide
            await loadStorySlide(storyKey, currentSlide + 1);
        } else if (storyKey === 'story1-1') {
            // Load the final slide when at the end of story1-1
            try {
                console.log('Loading final slide');
                const module = await import('../../Stories/Story1/slide_last.svelte');
                StorySlide = module.default;
            } catch (error) {
                console.error("Failed to load final slide:", error);
            }
        }
    }

    async function prevSlide(): Promise<void> {
        if (currentSlide > 1) {
            console.log('Moving to previous slide:', currentSlide - 1);
            StorySlide = null;
            language.set('english'); // Set English as default for new slide
            await loadStorySlide(storyKey, currentSlide - 1);
        }
    }

    function closeModal(): void {
        onClose();
        showModal = false;
        currentSlide = 1;
        isLoading = true;
        StorySlide = null;
        showLanguageModal = false;
        storyKey = '';
    }

    $: if (showModal && isLoading) {
        setTimeout(() => {
            isLoading = false;
        }, 1000);
    }

    $: if (showModal && storyKey) {
        console.log('Modal and storyKey detected, loading slide');
        language.set('english'); // Set English as default when starting story
        loadStorySlide(storyKey, 1);
    }

    $: console.log('Current state:', { storyKey, currentSlide, showModal, isLoading, StorySlide });
</script>

{#if showModal}
    {#if isLoading}
        <div
            class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50"
            transition:fade={{ duration: 300 }}
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
            transition:slide={{ duration: 300 }}
        >
            <div
                class="modal-content-wrapper bg-white rounded-[2vw] shadow-2xl w-[90vw] max-w-[1000px] h-[90vh] max-h-[800px]"
                transition:fade={{ duration: 500 }}
            >
                <div class="content-wrapper">
                    {#key `${storyKey}-${currentSlide}`}
                        {#if !storyKey}
                            <Slide1 />
                        {:else if StorySlide}
                            <svelte:component this={StorySlide} />
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
                    >
                        ➡️
                    </button>
                
                    {#if currentSlide > 1}
                        <button
                            class="nav-button left-button"
                            on:click={prevSlide}
                            aria-label="Previous slide"
                            in:fade={{ duration: 300 }}
                        >
                            ⬅️
                        </button>
                    {/if}
                {/if}
            </div>

            <!-- Close Button -->
            <button
                class="exit-button"
                on:click={closeModal}
                aria-label="Close modal"
                in:fade={{ duration: 300 }}
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

    .nav-button:hover {
        color: #059669;
        transform: translateY(-50%) scale(1.1);
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

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }
</style>
