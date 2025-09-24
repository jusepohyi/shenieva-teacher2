<script>
    import { fade } from 'svelte/transition';
    import { studentData } from '$lib/store/student_data';

    const slide = {
        text: 'Question Time! 📝'
    };

    // Question 2 for story1-3 (Hannah's feelings)
    const QUESTION_ID = 'story1_3_q2b';
    let selectedAnswer = '';
    let isAnswered = false;

    // Restore previous answer if it exists
    $: if ($studentData?.answeredQuestions?.[QUESTION_ID]) {
        isAnswered = true;
        selectedAnswer = $studentData.answeredQuestions[QUESTION_ID];
    }

    function checkAnswer() {
        if (!isAnswered) {
            isAnswered = true;
            studentData.update((data) => {
                if (data) {
                    const newData = {
                        ...data,
                        answeredQuestions: {
                            ...(data.answeredQuestions || {}),
                            [QUESTION_ID]: selectedAnswer
                        }
                    };

                    // Correct answer is 'b'
                    if (selectedAnswer === 'b' && !data.answeredQuestions?.[QUESTION_ID]) {
                        newData.studentProgress = (data.studentProgress || 0) + 1;
                    }

                    return newData;
                }
                return data;
            });
        }
    }
</script>

<div class="flex flex-col justify-center items-center text-center slide">
    <p class="text-[4vw] md:text-2xl text-gray-800 font-semibold text-fade mb-6">
        {slide.text}
    </p>

    <div class="question-container text-fade">
        <h2 class="text-[3vw] md:text-xl font-bold mb-4">Question:</h2>
        <p class="text-[2.5vw] md:text-lg mb-6">3. How would Hannah feel after returning the money and seeing the customer’s reaction?</p>

        <div class="options-container">
            <label class="option" class:disabled={isAnswered} class:selected={selectedAnswer === 'a'}>
                <input type="radio" name="answer" value="a" bind:group={selectedAnswer} on:change={checkAnswer} disabled={isAnswered}>
                <span class="text-[2vw] md:text-base">a. sad and guilty</span>
            </label>

            <label class="option" class:disabled={isAnswered} class:selected={selectedAnswer === 'b'}>
                <input type="radio" name="answer" value="b" bind:group={selectedAnswer} on:change={checkAnswer} disabled={isAnswered}>
                <span class="text-[2vw] md:text-base">b. proud and happy</span>
            </label>

            <label class="option" class:disabled={isAnswered} class:selected={selectedAnswer === 'c'}>
                <input type="radio" name="answer" value="c" bind:group={selectedAnswer} on:change={checkAnswer} disabled={isAnswered}>
                <span class="text-[2vw] md:text-base">c. confused and embarrassed</span>
            </label>
        </div>

        {#if isAnswered}
            <div class="feedback" transition:fade>
                {#if selectedAnswer === 'b'}
                    <p class="correct">✨ Correct! You earned a point! ✨</p>
                {:else}
                    <p class="incorrect">❌ Incorrect. Try to understand why this wasn't the best choice.</p>
                {/if}
                <p class="note">You've already answered this question.</p>
            </div>
        {/if}
    </div>
</div>

<style>
    .slide {
        animation: fadeIn 1000ms ease-in forwards;
        will-change: opacity;
    }
    .text-fade {
        white-space: pre-wrap;
        overflow-wrap: break-word;
        max-width: 80%;
        animation: textFadeIn 1000ms ease-in forwards;
        will-change: opacity;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        transform: translateZ(0);
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes textFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    :global(.slide) {
        animation: fadeOut 1000ms ease-out forwards;
    }
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }

    .question-container {
        width: 80%;
        max-width: 800px;
        background-color: rgba(255, 255, 255, 0.9);
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .options-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 1rem;
    }

    .option {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem;
        border-radius: 0.5rem;
        transition: background-color 0.2s;
        cursor: pointer;
    }

    .option:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    input[type="radio"] {
        width: 1.2rem;
        height: 1.2rem;
    }

    .disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .feedback {
        margin-top: 2rem;
        padding: 1rem;
        border-radius: 0.5rem;
        text-align: center;
        font-weight: bold;
    }

    .correct {
        color: #059669;
        background: rgba(0, 255, 0, 0.1);
        border: 2px solid #4CAF50;
    }

    .incorrect {
        color: #dc2626;
        background: rgba(255, 0, 0, 0.1);
        border: 2px solid #f44336;
    }

    .selected {
        background: #e5e7eb;
    }

    .note {
        font-size: 0.9rem;
        color: #666;
        margin-top: 0.5rem;
        font-style: italic;
    }
</style>
