<script lang="ts">
    import { goto } from '$app/navigation';
    import { onMount } from 'svelte';
    import QuizResultModal from '../../components/modals/quiz1/QuizResultModal.svelte';
    import { apiUrl } from '$lib/api_base';
    import { quiz1Taking, resetQuiz1, submitQuiz1, closeModal1 } from '$lib/store/quiz1_taking';
    import { studentData } from '$lib/store/student_data';

    interface Choice {
        id: number;
        text: string;
        is_correct: boolean;
    }

    interface Quiz {
        id: number;
        question: string;
        answer: string;
        points: number;
        choices: Choice[];
    }

    interface ApiResponse {
        success: boolean;
        data: Quiz[];
        error?: string;
    }

    let quizData: Quiz[] = [];
    let randomizedQuizData: Quiz[] = [];
    let selectedChoices: (Choice | null)[] = [];
    let loading: boolean = true;
    let error: string | null = null;
    let studentId: number | null = null;
    let updatingRibbons: boolean = false;

    $: allQuestionsAnswered = selectedChoices.length === randomizedQuizData.length && selectedChoices.every(choice => choice !== null);
    $: isPerfectScore = $quiz1Taking.score === $quiz1Taking.totalPoints;
    $: ribbonsEarned = $quiz1Taking.score;
    // Calculate number of correct items
    $: correctItems = selectedChoices.reduce((count, choice, index) => {
        return count + (choice?.is_correct && randomizedQuizData[index]?.choices.some(c => c.id === choice.id && c.is_correct) ? 1 : 0);
    }, 0);
    $: totalItems = randomizedQuizData.length;

    function shuffleArray<T>(array: T[]): T[] {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    }

    onMount(async () => {
            try {
            const response = await fetch(apiUrl('get_story1_quizzes.php'));
            if (!response.ok) throw new Error('Network response was not ok');
            const result: ApiResponse = await response.json();
            if (result.success) {
                quizData = result.data;
                randomizedQuizData = quizData.map(q => ({
                    ...q,
                    choices: shuffleArray([...q.choices])
                }));
                selectedChoices = new Array(randomizedQuizData.length).fill(null);
                quiz1Taking.update(state => ({
                    ...state,
                    totalPoints: randomizedQuizData.reduce((sum, q) => sum + q.points, 0)
                }));
            } else {
                error = result.error || 'Unknown error from API';
            }
        } catch (err: unknown) {
            error = err instanceof Error ? err.message : 'Unknown error';
        } finally {
            loading = false;
        }

        studentData.subscribe(data => {
            studentId = data?.pk_studentID || null;
        });
    });

    async function updateStudentRibbons(): Promise<void> {
        if (!studentId || ribbonsEarned <= 0) return;

        updatingRibbons = true;
        try {
            const response = await fetch(apiUrl('update_student_ribbons.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_id: studentId,
                    ribbons: ribbonsEarned
                })
            });

            const result = await response.json();
            if (!result.success) {
                console.error('Failed to update ribbons:', result.error);
            }
        } catch (err) {
            console.error('Error updating ribbons:', err);
        } finally {
            updatingRibbons = false;
        }
    }

    async function navigateToGame(): Promise<void> {
        await updateStudentRibbons();
        goto('/student/game/trash_1');
    }

    function selectChoice(choice: Choice, questionIndex: number): void {
        selectedChoices[questionIndex] = choice;
        selectedChoices = [...selectedChoices];
    }

    function submitAnswers(): void {
        if (!allQuestionsAnswered || !studentId) {
            alert('Please answer all questions and ensure you are logged in.');
            return;
        }

        let score = 0;
        randomizedQuizData.forEach((quiz, index) => {
            if (selectedChoices[index]?.is_correct) {
                score += quiz.points;
            }
        });
        submitQuiz1(score, $quiz1Taking.totalPoints); // Sets quizCompleted and showModal
    }

    function resetQuiz(): void {
        if ($quiz1Taking.quizTake >= $quiz1Taking.maxTakes) return;
        resetQuiz1();
        randomizedQuizData = quizData.map(q => ({
            ...q,
            choices: shuffleArray([...q.choices])
        }));
        selectedChoices = new Array(randomizedQuizData.length).fill(null);
    }
</script>

<div class="fixed inset-0 bg-gradient-to-br from-yellow-100 via-pink-100 to-blue-100 overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="w-32 h-32 bg-yellow-300 rounded-full opacity-20 absolute -top-16 -left-16 animate-float"></div>
        <div class="w-24 h-24 bg-pink-300 rounded-full opacity-20 absolute top-20 right-10 animate-float delay-1000"></div>
        <div class="w-40 h-40 bg-blue-300 rounded-full opacity-20 absolute bottom-10 left-20 animate-float delay-2000"></div>
    </div>

    <div class="relative z-10 w-full max-w-4xl mx-auto p-6 h-screen flex flex-col">
        <header class="mb-8 text-center">
            <h1 class="text-5xl text-purple-600 font-extrabold tracking-wider drop-shadow-lg animate-bounce-slow">
                🎉 Quiz Adventure! 🎉
            </h1>
            <p class="text-xl text-gray-700 mt-2 font-semibold">
                Attempt {$quiz1Taking.quizTake} of {$quiz1Taking.maxTakes}
            </p>
        </header>

        <main class="flex-1 overflow-y-auto space-y-6 pb-6">
            {#if loading}
                <div class="text-center">
                    <p class="text-2xl text-purple-500 animate-pulse">Loading Quiz... ✨</p>
                    <div class="w-16 h-16 mx-auto mt-4 border-4 border-t-purple-500 border-gray-200 rounded-full animate-spin"></div>
                </div>
            {:else if error}
                <p class="text-xl text-red-500 text-center font-semibold animate-pulse">Error: {error}</p>
            {:else if randomizedQuizData.length === 0}
                <p class="text-xl text-gray-600 text-center font-semibold">No questions available yet!</p>
            {:else if !$quiz1Taking.quizCompleted}
                {#each randomizedQuizData as quiz, index}
                    <section class="bg-white p-6 rounded-2xl shadow-md border-4 border-purple-200">
                        <h2 class="text-2xl text-blue-600 font-bold mb-4 flex items-center gap-3">
                            <span class="w-10 h-10 flex items-center justify-center bg-yellow-400 text-white rounded-full text-xl font-extrabold">
                                {index + 1}
                            </span>
                            <span class="flex-1">{quiz.question}</span>
                            <span class="text-lg text-green-600 font-semibold">
                                {quiz.points} point{quiz.points !== 1 ? 's' : ''}
                            </span>
                        </h2>
                        <div class="space-y-4">
                            {#each quiz.choices as choice, choiceIndex}
                                <button
                                    on:click={() => selectChoice(choice, index)}
                                    class="flex items-center gap-4 p-4 rounded-xl border-4 w-full text-left transition-all duration-300 hover:scale-[1.02]"
                                    class:bg-yellow-100={selectedChoices[index] !== choice}
                                    class:bg-purple-500={selectedChoices[index] === choice}
                                    class:border-yellow-300={selectedChoices[index] !== choice}
                                    class:border-purple-700={selectedChoices[index] === choice}
                                    class:text-gray-800={selectedChoices[index] !== choice}
                                    class:text-white={selectedChoices[index] === choice}
                                >
                                    <span 
                                        class="w-8 h-8 flex items-center justify-center text-lg font-bold text-white rounded-full flex-shrink-0"
                                        class:bg-purple-400={selectedChoices[index] !== choice}
                                        class:bg-purple-700={selectedChoices[index] === choice}
                                    >
                                        {String.fromCharCode(97 + choiceIndex)}
                                    </span>
                                    <span class="flex-1 text-lg font-medium">{choice.text}</span>
                                    {#if selectedChoices[index] === choice}
                                        <span class="text-2xl animate-pulse">🌟</span>
                                    {/if}
                                </button>
                            {/each}
                        </div>
                    </section>
                {/each}
                {#if allQuestionsAnswered}
                    <div class="flex justify-center sticky bottom-0 bg-gradient-to-t from-white/80 pt-4">
                        <button
                            on:click={submitAnswers}
                            class="py-3 px-8 bg-blue-500 text-white text-xl font-bold rounded-full hover:bg-blue-600 transition-all duration-300 shadow-lg hover:scale-105"
                        >
                            Submit Answers 🚀
                        </button>
                    </div>
                {/if}
            {:else if !$quiz1Taking.showModal}
                <section class="bg-white p-8 rounded-2xl shadow-md border-4 border-purple-200 text-center relative">
                    {#if updatingRibbons}
                        <div class="absolute inset-0 bg-gray-200/50 flex items-center justify-center z-10">
                            <div class="text-center">
                                <p class="text-2xl text-purple-500 animate-pulse">Saving Ribbons... 🎀</p>
                                <div class="w-16 h-16 mx-auto mt-4 border-4 border-t-purple-500 border-gray-200 rounded-full animate-spin"></div>
                            </div>
                        </div>
                    {/if}
                    <h2 class="text-4xl text-purple-600 font-extrabold mb-6 flex justify-center items-center gap-4">
                        <span class="text-5xl animate-bounce">🏆</span>
                        Quiz Completed!
                        <span class="text-5xl animate-bounce">🏆</span>
                    </h2>
                    <p class="text-2xl text-gray-700 mb-4">
                        Score: <span class="text-green-500 font-extrabold text-3xl">{correctItems}</span> / {totalItems} items correct
                    </p>
                    <p class="text-2xl text-gray-700 mb-4">
                        Points: <span class="text-green-500 font-extrabold text-3xl">{$quiz1Taking.score}</span> / {$quiz1Taking.totalPoints}
                    </p>
                    <div class="mb-6">
                        <p class="text-2xl text-gray-700">
                            You earned 
                            <span class="text-4xl text-pink-500 font-extrabold animate-ribbon-bounce">
                                {ribbonsEarned} Ribbon{ribbonsEarned !== 1 ? 's' : ''}!
                            </span>
                        </p>
                        <div class="flex justify-center gap-4 mt-4 flex-wrap">
                            {#each Array(ribbonsEarned) as _, i}
                                <span 
                                    class="text-5xl ribbon-animation" 
                                    style="animation-delay: {i * 0.3}s;"
                                >🎀</span>
                            {/each}
                        </div>
                    </div>
                    <div class="flex justify-center">
                        <button
                            on:click={navigateToGame}
                            class="py-3 px-8 bg-blue-500 text-white text-xl font-bold rounded-full hover:bg-blue-600 transition-all duration-300 shadow-lg hover:scale-105"
                            disabled={updatingRibbons}
                        >
                            Next Adventure 🎮
                        </button>
                    </div>
                </section>
            {/if}
        </main>

        <QuizResultModal
            showModal={$quiz1Taking.showModal}
            score={$quiz1Taking.score}
            totalPoints={$quiz1Taking.totalPoints}
            quizTake={$quiz1Taking.quizTake}
            maxTakes={$quiz1Taking.maxTakes}
            {randomizedQuizData}
            {selectedChoices}
            studentId={studentId}
            onClose={closeModal1}
            onRetake={resetQuiz}
        />
    </div>
</div>

<style>
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }
    @keyframes bounce-slow {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    @keyframes ribbon-bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }
    @keyframes ribbon-graffiti {
        0% {
            transform: scale(0) rotate(0deg);
            text-shadow: none;
            opacity: 0;
        }
        50% {
            transform: scale(1.3) rotate(15deg);
            text-shadow: 
                2px 2px 0px #ff00ff,
                -2px -2px 0px #00ffff,
                4px 4px 5px rgba(0, 0, 0, 0.5);
            opacity: 1;
        }
        100% {
            transform: scale(1) rotate(0deg);
            text-shadow: 
                2px 2px 0px #ff00ff,
                -2px -2px 0px #00ffff,
                4px 4px 5px rgba(0, 0, 0, 0.5);
            opacity: 1;
        }
    }
    @keyframes ribbon-chill-beat {
        0%, 100% {
            transform: scale(1);
            text-shadow: 
                2px 2px 0px #ff00ff,
                -2px -2px 0px #00ffff,
                4px 4px 5px rgba(0, 0, 0, 0.5);
        }
        50% {
            transform: scale(1.1);
            text-shadow: 
                3px 3px 0px #ff00ff,
                -3px -3px 0px #00ffff,
                5px 5px 6px rgba(0, 0, 0, 0.6);
        }
    }

    .animate-float {
        animation: float 6s ease-in-out infinite;
    }
    .animate-bounce-slow {
        animation: bounce-slow 3s ease-in-out infinite;
    }
    .animate-ribbon-bounce {
        animation: ribbon-bounce 2s ease-in-out infinite;
    }
    .ribbon-animation {
        animation: ribbon-graffiti 0.8s ease-out forwards, ribbon-chill-beat 1.5s ease-in-out infinite;
    }

    .overflow-y-auto::-webkit-scrollbar {
        width: 8px;
    }
    .overflow-y-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>