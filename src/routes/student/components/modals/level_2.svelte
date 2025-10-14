<script lang="ts">
    // transitions removed to disable animations
    import Slide1 from "../../Levels/Level2/slide_1.svelte";
    import { language } from "$lib/store/story_lang_audio";
    import { goto } from '$app/navigation';
    import { studentData } from '$lib/store/student_data';
    import { onMount, onDestroy } from 'svelte';

    import FinalSlideFallback from '../../Levels/Level2/slide_last.svelte';
    let StorySlide: any = null;
    export let storyKey: string = '';
    export let showModal: boolean = false;
    export let onClose: () => void = () => {};

    let currentSlide: number = 1;
    let isLoading: boolean = true;
    let showLanguageModal: boolean = false;
    let errorMessage: string = ''; // To display feedback when trying to skip
    // Track readiness signalled by the currently-mounted slide component
    let slideAllAnswered: boolean = true;

    // Define max slides per story
    const maxSlidesMap: Record<string, number> = {
        'story2-1': 8,
        'story2-2': 8,
        'story2-3': 8
    };

    // Define quiz slides per story
    const quizSlidesMap: Record<string, number[]> = {
        'story2-1': [6],
        'story2-2': [5],
        'story2-3': [7]
    };

    const questionIdMap: Record<string, Record<number, string>> = {
        'story2-1': {
            6: 'story2-1_slide6'
        },
        'story2-2': {
            5: 'story2-2_slide5'
        }
        ,
        'story2-3': {
            7: 'story2-3_slide7'
        }
    };

    // Correct answers map (used only to compute correctness; we will NOT display the right answers)
    const correctAnswersMap: Record<string, Record<string, Record<number, string>>> = {
        'story2-1': {
            'story2-1_slide6': {
                1: 'His mother will bring him to the doctor.',
                2: "The doctor will say that Hector's weight is above average for his age right now.",
                3: "He will tell Hector that he needs to improve his eating habits and start exercising. Avoid too many sweets like chocolates and he should start eating more vegetables and fruits.",
                4: "He will have a healthy body."
            }
        }
        ,
        'story2-2': {
            'story2-2_slide5': {
                1: 'Maya will run and check the old woman.',
                2: 'By helping her to get up and asking someone who passes by to help the old woman too.',
                3: 'She will feel thankful to Maya for her kindness and for being helpful.',
                4: 'She will praise and thank Maya.'
            }
        }
    };

    // Map of question prompt text so the review can show the question along with the student's answer
    const questionTextMap: Record<string, Record<number, string>> = {
        'story2-1': {
            1: "Based on the scenario, what will be the response of Hector’s mother?",
            2: "What will the doctor say about Hector’s health?",
            3: "What do you think will be the doctor's advice to Hector?",
            4: "What do you think is the result once Hector follows the doctor's advice?"
        }
        ,
        'story2-2': {
            1: "What actions do you think Maya will take?",
            2: "How will Maya help the old woman?",
            3: "Once the old woman wakes up, how will she feel about Maya?",
            4: "What will the old woman do after?"
        }
        ,
        'story2-3': {
            1: "What do you think Royce will do next after he finds out the situation of his brother?",
            2: "How will Royce feel if he buys the medicine?",
            3: "What do you think his mother will feel after?",
            4: "After what happened, what do you think Royce will do?"
        }
    };

    // Get maxSlides based on storyKey, default to 8 if unknown
    $: maxSlides = maxSlidesMap[storyKey] || 8;

    // Check if the current slide is answered
    $: isCurrentSlideAnswered = (() => {
        const quizSlides = quizSlidesMap[storyKey] || [];
        if (quizSlides.includes(currentSlide)) {
            const qid = questionIdMap[storyKey]?.[currentSlide];
            return qid && $studentData?.answeredQuestions?.[qid] !== undefined;
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
            const path = `../../Levels/Level2/${key}/slide_${slideNumber}.svelte`;
            console.log('Loading slide from path:', path);
            const module = await import(/* @vite-ignore */ path);
            console.log('Module loaded:', module);
            StorySlide = module.default;
            currentSlide = slideNumber;
            console.log('Slide loaded successfully, current slide:', currentSlide);
        } catch (error) {
            console.error('Failed to load story2 slide', error);
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
            const qid = questionIdMap[storyKey]?.[currentSlide];
            if (qid && (!$studentData?.answeredQuestions?.[qid] || !slideAllAnswered)) {
                errorMessage = 'Please select an answer before proceeding.';
                setTimeout(() => (errorMessage = ''), 3000);
                return;
            }
            // If we've reached a quiz slide and it is answered, first show a review sheet
            if (qid && $studentData?.answeredQuestions?.[qid] && slideAllAnswered) {
                try {
                    const saved = JSON.parse($studentData.answeredQuestions[qid] || '{}');
                    // Build results: for each question id, include the student's answer and whether it's correct
                    quizReviewResults = Object.keys(saved).map(k => {
                        const qnum = Number(k);
                        const studentAnswer = saved[k];
                        const correctText = correctAnswersMap[storyKey]?.[qid]?.[qnum];
                        const isCorrect = !!(correctText && studentAnswer === correctText);
                        const questionText = questionTextMap[storyKey]?.[qnum] || `Question ${qnum}`;
                        return { qnum, questionText, studentAnswer, isCorrect };
                    }).sort((a,b) => a.qnum - b.qnum);
                    showQuizReview = true;
                } catch (e) {
                    console.error('Failed to build quiz review', e);
                    // fallback: proceed normally
                }
                return;
            }
        }

        errorMessage = '';
        if (currentSlide < maxSlides) {
            console.log('Moving to next slide:', currentSlide + 1);
            // Keep the current StorySlide visible until the new slide is ready to avoid blanking
            language.set('english');
            await loadStorySlide(storyKey, currentSlide + 1);
        } else {
            await loadFinalSlide();
        }
    }

    // Quiz review state and handlers
    let showQuizReview: boolean = false;
    /** @type {{qnum:number,questionText:string,studentAnswer:string,isCorrect:boolean}[]} */
    let quizReviewResults: Array<{qnum:number,questionText:string,studentAnswer:string,isCorrect:boolean}> = [];

    async function continueAfterReview(): Promise<void> {
        showQuizReview = false;
        // Proceed to the next slide (reuse the same advance logic)
        if (currentSlide < maxSlides) {
            language.set('english');
            await loadStorySlide(storyKey, currentSlide + 1);
        } else {
            await loadFinalSlide();
        }
    }

    async function loadFinalSlide(): Promise<void> {
        try {
            console.log('Loading final slide (helper)');
            // set the currentSlide immediately so the template can show the static fallback while import resolves
            currentSlide = maxSlides + 1;
            const module = await import(/* @vite-ignore */ '../../Levels/Level2/slide_last.svelte');
            StorySlide = module.default;
            // ensure any optimistic flags reset
            slideAllAnswered = true;
            showQuizReview = false;
        } catch (error) {
            console.error('Failed to load final slide', error);
        }
    }

    function returnToSlideFromReview(): void {
        showQuizReview = false;
    }

    async function prevSlide(): Promise<void> {
        if (currentSlide > 1) {
            console.log('Moving to previous slide:', currentSlide - 1);
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
            localStorage.removeItem('retakeLevel2');
            localStorage.removeItem('openStory2Modal');
        } catch {}
        await goto('/student/dashboard');
    }

    $: if (showModal && isLoading) {
        setTimeout(() => {
            isLoading = false;
        }, 1000);
    }

    $: if (showModal && storyKey) {
        console.log('Modal and storyKey detected, loading slide');
        language.set('english');
        try { localStorage.setItem('pending_story', storyKey); } catch {}
        loadStorySlide(storyKey, 1);
    }

    // Listen for slide-level answered state events dispatched from slide components
    function handleSlideAnsweredEvent(evt: Event): void {
        try {
            const ev = evt as CustomEvent<{ allAnswered: boolean }>;
            slideAllAnswered = !!(ev && ev.detail && ev.detail.allAnswered);
        } catch (e) {
            console.error('slideAnswered handler error', e);
            slideAllAnswered = true;
        }
    }

    onMount(() => {
        if (typeof window !== 'undefined') {
            window.addEventListener('slideAnswered', handleSlideAnsweredEvent as EventListener);
        }
    });

    onDestroy(() => {
        if (typeof window !== 'undefined') {
            window.removeEventListener('slideAnswered', handleSlideAnsweredEvent as EventListener);
        }
    });

    // Reset slideAllAnswered when we load a new slide
    $: if (StorySlide) {
        slideAllAnswered = true; // optimistic until the slide signals otherwise
    }

    $: console.log('Story2 modal state:', { storyKey, currentSlide, showModal, isLoading, StorySlide, isCurrentSlideAnswered });
</script>

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
                <!-- slide-host prevents children animations from briefly hiding the slide (overrides any per-slide animations) -->
                <div class="w-[100%] h-[100%] flex flex-col items-center justify-center slide-host">
                    {#key `${storyKey}-${currentSlide}`}
                        {#if !storyKey}
                            <Slide1 />
                        {:else if StorySlide}
                            <!-- Pass storyKey so slides that expect it (like slide_last) receive the prop -->
                            <svelte:component this={StorySlide} {storyKey} />
                        {:else if currentSlide >= maxSlides + 1}
                            <!-- Fallback to static import if dynamic import isn't available -->
                            <svelte:component this={FinalSlideFallback} {storyKey} />
                        {/if}
                    {/key}
                </div>

                {#if showQuizReview}
                    <div class="quiz-review-overlay fixed inset-0 bg-gradient-to-b from-white/40 to-white/30 flex items-center justify-center z-60">
                        <div class="quiz-review card bg-gradient-to-br from-yellow-50 to-white rounded-2xl p-6 w-[92%] max-w-[760px] shadow-lg border-2 border-yellow-200">
                            <h3 class="text-2xl font-extrabold mb-2 text-rose-600">Quiz Review</h3>
                            <p class="text-sm mb-4 text-rose-700">Here are your answers. You'll see whether each choice was correct, but the right answers are kept hidden — try to think about why you got something wrong before continuing.</p>
                            <div class="review-list space-y-3 max-h-[52vh] overflow-auto mb-4">
                                {#each quizReviewResults as r}
                                    <div class="review-item flex flex-col md:flex-row items-start justify-between gap-3 p-3 rounded-lg bg-white/60 border border-rose-50">
                                        <div class="flex-1 text-left">
                                            <div class="font-bold text-rose-700">Question {r.qnum}</div>
                                            <div class="text-sm text-rose-800 mb-1">{r.questionText}</div>
                                            <div class="text-sm text-700">Your answer: <span class="font-semibold text-rose-900">{r.studentAnswer}</span></div>
                                        </div>
                                        <div class="flex items-start md:items-center md:ml-4">
                                            {#if r.isCorrect}
                                                <span class="badge-correct bg-lime-200 text-lime-800 px-3 py-1 rounded-full text-sm font-bold">✓ Correct</span>
                                            {:else}
                                                <span class="badge-wrong bg-red-200 text-red-800 px-3 py-1 rounded-full text-sm font-bold">✕ Incorrect</span>
                                            {/if}
                                        </div>
                                    </div>
                                {/each}
                            </div>
                            <div class="flex justify-end gap-3">
                                <button class="btn-secondary px-4 py-2 rounded bg-white border" on:click={returnToSlideFromReview}>Return</button>
                                <button class="btn-primary px-4 py-2 rounded bg-rose-600 text-white" on:click={continueAfterReview}>Continue</button>
                            </div>
                        </div>
                    </div>
                {/if}

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
                    <!-- Background overlay is focusable and closable via Enter/Escape for accessibility -->
                    <div class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="language-dialog-title">
                        <div class="modal-content" role="document" tabindex="-1">
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
                        </div>
                    </div>
                {/if}

                <!-- Next / Previous Buttons: show when a story is selected (even if slide component hasn't mounted yet) -->
                {#if storyKey}
                    <button
                        class="absolute right-[1vw] top-1/2 transform -translate-y-1/2 text-[8vw] md:text-6xl text-lime-500 hover:text-lime-600 transition-transform duration-200"
                        on:click={nextSlide}
                        disabled={!isCurrentSlideAnswered || !slideAllAnswered || currentSlide >= maxSlides + 1}
                    >
                        ➡️
                    </button>

                    {#if currentSlide > 0}
                        <button
                            class="absolute left-[1vw] top-1/2 transform -translate-y-1/2 text-[8vw] md:text-6xl text-lime-500 hover:text-lime-600 transition-transform duration-200"
                            on:click={prevSlide}
                        >
                            ⬅️
                        </button>
                    {/if}
                {/if}

                {#if storyKey && !StorySlide}
                    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-yellow-100 text-yellow-900 px-4 py-2 rounded shadow">
                        <div>Slide failed to load or is still initializing.</div>
                        <button class="mt-2 underline text-sm" on:click={() => loadStorySlide(storyKey, currentSlide)}>Retry</button>
                    </div>
                {/if}
            </div>
        </div>

        <!-- Exit Button -->
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

    /* Quiz review UI styles */
    .quiz-review-overlay { backdrop-filter: blur(4px); }
    .quiz-review { font-family: 'Century Gothic', 'CenturyGothic', 'AppleGothic', Arial, sans-serif; }
    .quiz-review .review-item { transition: transform 120ms ease; }
    .quiz-review .review-item:hover { transform: translateY(-3px); }
    .badge-correct { box-shadow: 0 4px 8px rgba(16, 185, 129, 0.12); }
    .badge-wrong { box-shadow: 0 4px 8px rgba(244, 63, 94, 0.12); }

    /* Prevent any child slide or global rule from animating opacity/transform during slide swaps */
    .slide-host {
        animation: none !important;
        transition: none !important;
        opacity: 1 !important;
    }
</style>