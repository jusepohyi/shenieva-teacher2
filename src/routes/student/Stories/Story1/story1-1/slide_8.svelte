<script>
    import { fade } from 'svelte/transition';
    import { studentData } from '$lib/store/student_data';

    const QUESTION_ID = 'story1_q2'; // Unique identifier for this question
    let selectedAnswer = '';
    let isAnswered = false;

    $: if ($studentData?.answeredQuestions?.[QUESTION_ID]) {
        isAnswered = true;
        selectedAnswer = $studentData.answeredQuestions[QUESTION_ID];
    }

    function checkAnswer() {
        if (!isAnswered) {
            isAnswered = true;
            studentData.update(data => {
                if (data) {
                    const newData = {
                        ...data,
                        answeredQuestions: {
                            ...(data.answeredQuestions || {}),
                            [QUESTION_ID]: selectedAnswer
                        }
                    };
                    
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

<div class="slide-container">
    <div class="question-content">
        <h2 class="question-title">Question Time! 📝</h2>
        <div class="question-text">
            <p>2. Based on Maria's actions, what will happen next?</p>
            
            <div class="options-list">
                <label class="option" class:disabled={isAnswered} class:selected={selectedAnswer === 'a'}>
                    <input type="radio" name="answer" value="a" bind:group={selectedAnswer} on:change={checkAnswer} disabled={isAnswered}>
                    <span>a. Maria will refuse to pay.</span>
                </label>
                
                <label class="option" class:disabled={isAnswered} class:selected={selectedAnswer === 'b'}>
                    <input type="radio" name="answer" value="b" bind:group={selectedAnswer} on:change={checkAnswer} disabled={isAnswered}>
                    <span>b. Maria came back to pay and thanked Lena.</span>
                </label>
                
                <label class="option" class:disabled={isAnswered} class:selected={selectedAnswer === 'c'}>
                    <input type="radio" name="answer" value="c" bind:group={selectedAnswer} on:change={checkAnswer} disabled={isAnswered}>
                    <span>c. Maria will be disappointed that Lena is waiting for her to pay.</span>
                </label>
            </div>
        </div>

        {#if isAnswered}
            <div class="feedback" transition:fade>
                {#if selectedAnswer === 'b'}
                    <p class="correct">✨ Correct! You earned a point! ✨</p>
                {:else}
                    <p class="incorrect">❌ Incorrect. Try to understand why this wasn't the best choice.</p>
                {/if}
            </div>
        {/if}
    </div>
</div>

<style>
    .slide-container {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 2rem;
    }

    .question-content {
        background: white;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        max-width: 800px;
        width: 90%;
    }

    .question-title {
        font-size: clamp(1.5rem, 4vw, 2rem);
        color: #1f2937;
        text-align: center;
        margin-bottom: 2rem;
        font-weight: bold;
    }

    .question-text {
        font-size: clamp(1rem, 2.5vw, 1.25rem);
        margin-bottom: 2rem;
    }

    .options-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 1rem;
    }

    .option {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.8rem;
        border-radius: 0.5rem;
        background: #f3f4f6;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .option:hover:not(.disabled) {
        background: #e5e7eb;
    }

    .selected {
        background: #e5e7eb;
    }

    .disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    input[type="radio"] {
        width: 1.2rem;
        height: 1.2rem;
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
</style>
