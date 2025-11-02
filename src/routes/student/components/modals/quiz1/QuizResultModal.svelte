<script lang="ts">
    import { quiz1Taking, resetQuiz1 } from '$lib/store/quiz1_taking';
    import { apiUrl } from '$lib/api_base';
    import { goto } from '$app/navigation';

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

    export let showModal: boolean;
    export let score: number;
    export let totalPoints: number;
    export let quizTake: number;
    export let maxTakes: number;
    export let randomizedQuizData: Quiz[];
    export let selectedChoices: (Choice | null)[];
    export let studentId: number | null;
    export let onClose: () => void;
    export let onRetake: () => void;

    $: canRetake = quizTake < maxTakes;

    async function saveQuiz(isFinal: boolean): Promise<void> {
        if (!studentId) {
            console.error('No student ID available');
            return;
        }

        const answers = randomizedQuizData.map((quiz, index) => {
            // Map choices to include ABCD labels
            const choicesWithLabels = quiz.choices.map((choice, choiceIndex) => ({
                id: choice.id,
                text: choice.text,
                is_correct: choice.is_correct,
                label: String.fromCharCode(65 + choiceIndex) // A, B, C, D...
            }));

            return {
                item_number: index + 1,
                question: quiz.question,
                choices: choicesWithLabels,
                is_correct: selectedChoices[index]?.is_correct || false,
                points: quiz.points,
                selected_choice_label: selectedChoices[index]
                    ? String.fromCharCode(65 + quiz.choices.findIndex(c => c.id === selectedChoices[index]?.id))
                    : null
            };
        });

        try {
            const response = await fetch(apiUrl('store1/save_story1_quiz.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_id: studentId,
                    store: 1,
                    attempt: quizTake,
                    answers: answers,
                    is_final: isFinal
                })
            });

            const result = await response.json();
            if (!result.success) {
                console.error('Failed to save quiz:', result.error);
            } else {
                console.log('Quiz saved successfully with is_final:', isFinal);
            }
        } catch (err) {
            console.error('Error saving quiz:', err);
        }
    }

    function handleProceed(): void {
        saveQuiz(true); // Final attempt
        onClose(); // Close modal, return to "Quiz Completed" screen
    }

    function handleRetake(): void {
        if (canRetake) {
            saveQuiz(false); // Not final
            resetQuiz1(); // Reset the quiz state
            onClose(); // Close the modal
            goto('/student/dashboard'); // Navigate to dashboard
        }
    }
</script>

{#if showModal}
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-20">
        <div class="bg-white p-6 rounded-2xl shadow-lg border-4 border-purple-200 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <h2 class="text-3xl text-purple-600 font-extrabold mb-4">Your Answer Sheet</h2>
            <p class="text-xl text-gray-700 mb-4">Score: <span class="text-green-500 font-bold">{score}</span> / {totalPoints}</p>
            {#each randomizedQuizData as quiz, index}
                <div class="mb-4">
                    <p class="text-lg font-semibold text-blue-600">{index + 1}. {quiz.question}</p>
                    <p class="text-md mt-1">
                        Your Answer: <span class="font-semibold">{selectedChoices[index]?.text || 'None'}</span>
                        <span class="ml-2">
                            {#if selectedChoices[index]?.is_correct}
                                <span class="text-green-500">✔️ Correct</span>
                            {:else}
                                <span class="text-red-500">❌ Wrong</span>
                            {/if}
                        </span>
                    </p>
                </div>
            {/each}
            {#if canRetake}
                <p class="text-lg text-gray-700 mb-4">Want to try the level again? You have {maxTakes - quizTake} attempt(s) left!</p>
                <div class="flex justify-end gap-4">
                    <button
                        on:click={handleProceed}
                        class="py-2 px-6 bg-gray-500 text-white text-lg font-bold rounded-full hover:bg-gray-600 transition-all duration-300"
                    >
                        Proceed to Next Level
                    </button>
                    <button
                        on:click={handleRetake}
                        class="py-2 px-6 bg-purple-500 text-white text-lg font-bold rounded-full hover:bg-purple-600 transition-all duration-300"
                    >
                        Retake Level
                    </button>
                </div>
            {:else}
                <p class="text-lg text-red-500 mb-4">No more attempts left!</p>
                <div class="flex justify-end">
                    <button
                        on:click={handleProceed}
                        class="py-2 px-6 bg-gray-500 text-white text-lg font-bold rounded-full hover:bg-gray-600 transition-all duration-300"
                    >
                        Proceed to Next Level
                    </button>
                </div>
            {/if}
        </div>
    </div>
{/if}

<style>
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