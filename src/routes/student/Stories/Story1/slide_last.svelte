<script lang="ts">
    import { goto } from '$app/navigation';
    import { resetLevelAnswers, studentData } from '$lib/store/student_data';
    import { onMount } from 'svelte';

    export let storyKey: string = ''; // Prop to identify the current story (story1-1, story1-2, story1-3)

    const slide = {
        text: "Yay! You finished your adventure in Readville Village! 🎉",
        image: "../../assets/school-bg.gif"
    };

    // Show modal to ask "Try Again or Go Next?" or congratulate for perfect score
    let showConfirm = false;

    // Attempt limiting (3 total tries; first play counts as try 1)
    const attemptsLimit = 3;
    let retakeCount = 0; // number of retakes performed
    try {
        const v = localStorage.getItem(`retake${storyKey}Count`);
        retakeCount = v ? parseInt(v, 10) || 0 : 0;
    } catch {}

    // Define correct answers for each story
    const correctAnswers: Record<string, Record<string, string>> = {
        'story1-1': {
            'story1_q1': 'b', // Slide 5
            'story1_q2': 'b', // Slide 8
            'story1_q3': 'c'  // Slide 9
        },
        'story1-2': {
            'story1_2_q1': 'a', // Slide 4
            'story1_2_q2a': 'b', // Slide 6
            'story1_2_q2b': 'c'  // Slide 7
        },
        'story1-3': {
            'story1_3_q1': 'c', // Slide 4
            'story1_3_q2a': 'b', // Slide 6
            'story1_3_q2b': 'b'  // Slide 7
        }
    };

    // Debug: Log initial data
    onMount(() => {
        console.log('slide_last.svelte mounted', { 
            storyKey, 
            answeredQuestions: $studentData?.answeredQuestions,
            retakeCount,
            attemptsLimit
        });
        return () => console.log('slide_last.svelte unmounted');
    });

    // Check if the user has a perfect score
    $: hasPerfectScore = (() => {
        if (!storyKey || !correctAnswers[storyKey]) {
            console.log('Invalid storyKey or no correct answers', { storyKey });
            return false;
        }
        const answers = $studentData?.answeredQuestions || {};
        const expectedQuestions = Object.keys(correctAnswers[storyKey]);
        const isPerfect = expectedQuestions.every(qid => {
            const userAnswer = answers[qid];
            if (userAnswer === undefined || userAnswer === '') {
                console.log('Question not answered, treating as valid for perfect score', { questionId: qid, userAnswer });
                return true; // Allow skipped questions for perfect score
            }
            const isCorrect = userAnswer === correctAnswers[storyKey][qid];
            console.log('Checking answer', { 
                questionId: qid, 
                userAnswer, 
                correctAnswer: correctAnswers[storyKey][qid], 
                isCorrect 
            });
            return isCorrect;
        });
        console.log('hasPerfectScore', isPerfect, { storyKey, answers, expectedQuestions });
        return isPerfect;
    })();

    function canRetake() {
        return (1 + retakeCount) < attemptsLimit;
    }

    function incRetakeCount() {
        retakeCount += 1;
        try { localStorage.setItem(`retake${storyKey}Count`, String(retakeCount)); } catch {}
    }

    // Derived try counters for UI
    let attemptsUsed = 1 + retakeCount;
    let attemptsRemaining = Math.max(0, attemptsLimit - attemptsUsed);
    $: attemptsUsed = Math.min(attemptsLimit, 1 + retakeCount);
    $: attemptsRemaining = Math.max(0, attemptsLimit - attemptsUsed);

    function continueToQuiz() {
        showConfirm = false;
        goto('/student/game/trash_1');
    }

    function retakeLevel() {
        if (!canRetake()) {
            showConfirm = false;
            continueToQuiz();
            return;
        }
        // Reset answers for the specific story
        const questionIds = Object.keys(correctAnswers[storyKey] || {});
        studentData.update(data => {
            const updatedAnswers = { ...data?.answeredQuestions };
            questionIds.forEach(qid => delete updatedAnswers[qid]);
            return { ...data, answeredQuestions: updatedAnswers };
        });
        // Increment retake count and persist
        incRetakeCount();
        // Flag retake and delegate to /student/play with story-specific query
        try { localStorage.setItem(`retake${storyKey}`, 'true'); } catch {}
        showConfirm = false;
        goto(`/student/play?story=${storyKey}&retake=${Date.now()}`);
    }
</script>

<div class="flex flex-col justify-center items-center text-center slide">
    {#if slide.image}
        <div class="image-container">
            <img
                src="/src/assets/school-bg.gif"
                alt="Congrats Scene"
                class="block mx-auto rounded-[2vw] shadow-lg"
            />
        </div>
    {/if}
    <p class="text-[4vw] md:text-2xl text-gray-800 font-semibold text-fade">
        {slide.text}
    </p>

    <button
        class="mt-[2vh] bg-teal-300 text-gray-900 px-[6vw] py-[2vh] rounded-[3vw] text-[5vw] md:text-2xl font-bold shadow-md hover:bg-teal-400 hover:scale-105 transition-all duration-300 flex items-center justify-center"
        on:click={() => showConfirm = true}
    >
        Continue 🌟
    </button>

    {#if showConfirm}
        <div class="confirm-overlay">
            <div class="confirm-modal">
                {#if hasPerfectScore}
                    <h3 class="confirm-title">Wow! Perfect Score! 🎉</h3>
                    <p class="confirm-text">You got all answers right! You're a star! 🌟</p>
                    <div class="confirm-actions">
                        <button class="confirm-btn proceed" on:click={continueToQuiz}>Go Next ➡️</button>
                    </div>
                {:else}
                    <h3 class="confirm-title">Try again or go next? 😊</h3>
                    <p class="confirm-text">
                        {#if attemptsRemaining > 0}
                            This is try {attemptsUsed} of {attemptsLimit}! You have {attemptsRemaining} {attemptsRemaining === 1 ? 'try' : 'tries'} left! 🌟
                        {:else}
                            You used all {attemptsLimit} tries! Time to go next! 🚀
                        {/if}
                    </p>
                    <div class="confirm-actions">
                        {#if attemptsRemaining > 0}
                            <button class="confirm-btn retake" on:click={retakeLevel}>Try Again ({attemptsRemaining} left) ⟲</button>
                            <button class="confirm-btn proceed" on:click={continueToQuiz}>Go Next ➡️</button>
                            <button class="confirm-btn cancel" on:click={() => showConfirm = false}>Stay Here ✕</button>
                        {:else}
                            <button class="confirm-btn proceed" on:click={continueToQuiz}>Go Next ➡️</button>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
    {/if}
</div>

<style>
    .slide { animation: fadeIn 1000ms ease-in forwards; will-change: opacity; }
    .image-container { width: 65%; height: auto; max-width: 800px; max-height: 400px; margin-bottom: 2vh; display: flex; justify-content: center; align-items: center; }
    .image-container img { width: 100%; height: 100%; object-fit: contain; }
    .text-fade { white-space: pre-wrap; overflow-wrap: break-word; max-width: 80%; animation: textFadeIn 1000ms ease-in forwards; will-change: opacity; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; transform: translateZ(0); }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes textFadeIn { from { opacity: 0; } to { opacity: 1; } }
    :global(.slide) { animation: fadeOut 1000ms ease-out forwards; }
    @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }

    /* Confirmation modal styles */
    .confirm-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .confirm-modal { background: #fff; border-radius: 1rem; padding: 1.5rem; width: min(90vw, 480px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); text-align: center; }
    .confirm-title { font-size: clamp(1.5rem, 4vw, 1.75rem); font-weight: 800; color: #111827; margin-bottom: 0.75rem; }
    .confirm-text { font-size: clamp(1.1rem, 3vw, 1.25rem); color: #374151; margin-bottom: 1rem; font-weight: 600; }
    .confirm-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    .confirm-btn { padding: 0.75rem 1rem; border-radius: 0.75rem; border: none; font-weight: 700; font-size: clamp(1rem, 2.5vw, 1.125rem); cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.2s ease; }
    .confirm-btn:hover { transform: scale(1.03); }
    .confirm-btn:active { transform: scale(0.98); }
    .confirm-btn.retake { background: #fef3c7; color: #92400e; box-shadow: 0 2px 6px rgba(146,64,14,0.2); }
    .confirm-btn.proceed { background: #d1fae5; color: #065f46; box-shadow: 0 2px 6px rgba(6,95,70,0.2); }
    .confirm-btn.cancel { grid-column: span 2; background: #e5e7eb; color: #111827; }
</style>