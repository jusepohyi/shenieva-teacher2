<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let results: Array<{
    question: string;
    answer: string;
    isCorrect: boolean;
    points: number;
  }>;
  export let attemptCount: number;
  export let maxAttempts: number;

  const dispatch = createEventDispatcher();

  $: score = results.filter(r => r.isCorrect).length;
  $: totalQuestions = results.length;
  $: earnedPoints = results.reduce((sum, r) => sum + (r.isCorrect ? Number(r.points) : 0), 0);
  $: totalPossiblePoints = results.reduce((sum, r) => sum + Number(r.points), 0);
  $: isPerfectScore x= score === totalQuestions;
  $: showRetakeButton = attemptCount + 1 < maxAttempts && !isPerfectScore;

  function handleRetake() {
    if (showRetakeButton) {
      dispatch('retake');
    }
  }

  function handleSubmit() {
    dispatch('submit');
  }
</script>

<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white rounded-2xl p-8 max-w-2xl w-full max-h-[80vh] overflow-y-auto shadow-2xl">
    <h2 class="text-3xl font-bold text-center text-purple-600 mb-6">Quiz Results</h2>

    <div class="mb-6 text-center">
      <p class="text-xl font-semibold">
        Score: {score}/{totalQuestions}
      </p>
      <p class="text-xl font-semibold">
        Points: {earnedPoints}/{totalPossiblePoints}
      </p>
      <p class="text-lg text-gray-600">
        Attempt: {attemptCount + 1} of {maxAttempts}
      </p>
    </div>

    <div class="space-y-4">
      {#each results as result}
        <div
          class="p-4 rounded-lg flex justify-between items-center"
          class:bg-green-100={result.isCorrect}
          class:bg-red-100={!result.isCorrect}
        >
          <div>
            <p class="font-semibold">{result.question}</p>
            <p>Your answer: {result.answer}</p>
            <p class="text-sm text-gray-600">Points: {result.points}</p>
          </div>
          <span class="text-lg font-bold">
            {result.isCorrect ? '✓ Correct' : '✗ Incorrect'}
          </span>
        </div>
      {/each}
    </div>

    <div class="mt-8 flex justify-center gap-4">
      {#if showRetakeButton}
        <button
          on:click={handleRetake}
          class="bg-yellow-400 text-purple-900 font-bold px-6 py-3 rounded-full hover:bg-yellow-500 transition-colors"
        >
          Retake Quiz
        </button>
      {/if}
      <button
        on:click={handleSubmit}
        class="bg-green-400 text-white font-bold px-6 py-3 rounded-full hover:bg-green-500 transition-colors"
      >
        {isPerfectScore ? 'Close' : 'Final Submit'}
      </button>
    </div>
  </div>
</div>