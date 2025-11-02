<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';
  import { goto } from '$app/navigation';
  import QuizResultModal2 from '../../components/modals/quiz1/QuizResultModal3.svelte';
  import CongratsModal from '../../components/modals/quiz1/CongratsModal.svelte';
  import { apiUrl } from '$lib/api_base';

  // Quiz state
  let quizData = [];
  let error: string | null = null;
  const quizStore = writable({
    answers: []
  });
  let showResultModal = false;
  let showCongratsModal = false;
  let quizResults: Array<{
    question: string;
    answer: string;
    isCorrect: boolean;
    points: number;
  }> = [];
  let earnedPoints = 0;
  let attemptCount = 0;
  let maxAttempts = 3;

  // Assume student_id is available (e.g., from session or context)
  let student_id = 1; // REPLACE WITH ACTUAL STUDENT ID RETRIEVAL LOGIC

  onMount(async () => {
    console.log('Quiz 3 component mounted');
    try {
      // Fetch quiz data
      const quizResponse = await fetch(apiUrl('student-story3/get_story3_quizzes.php'), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      if (!quizResponse.ok) {
        throw new Error(`Failed to fetch quiz data: ${quizResponse.statusText}`);
      }

      const data = await quizResponse.json();
      
      if (data.error) {
        throw new Error(data.error);
      }

      // Store original quiz data, ensuring points is a number
      quizData = data.map(q => ({ ...q, points: Number(q.points) }));
      
      // Initialize answers with empty strings
      quizStore.set({
        answers: data.map(q => ({ questionId: q.id, answer: '', points: Number(q.points) }))
      });

      // Fetch attempt count for the student
      const attemptResponse = await fetch(apiUrl(`student-story3/get_student_attempts.php?student_id=${student_id}`), {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      if (!attemptResponse.ok) {
        throw new Error(`Failed to fetch attempt count: ${attemptResponse.statusText}`);
      }

      const attemptData = await attemptResponse.json();
      if (attemptData.error) {
        throw new Error(attemptData.error);
      }

      attemptCount = attemptData.attempts || 0;

    } catch (err) {
      error = `Initialization error: ${err.message}`;
      console.error('Mount error:', err);
    }

    return () => {
      console.log('Quiz 3 component destroyed');
    };
  });

  // Derived state
  $: allAnswered = $quizStore.answers.every(answer => answer.answer.trim() !== '');

  // Update answer in store when user types
  function handleAnswerInput(event: Event, questionId: number) {
    const target = event.target as HTMLInputElement;
    quizStore.update(store => {
      const updatedAnswers = store.answers.map(ans =>
        ans.questionId === questionId ? { ...ans, answer: target.value } : ans
      );
      return { ...store, answers: updatedAnswers };
    });
  }

  async function saveQuizResults(isFinal: boolean) {
    try {
      if (!student_id) {
        throw new Error('Invalid student ID');
      }
      if (!quizResults.length) {
        throw new Error('No quiz results to save');
      }
      console.log('Saving quiz results:', { student_id, attempt: attemptCount + 1, is_final: isFinal });
      const response = await fetch(apiUrl('student-story3/save_quiz_results.php'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          student_id,
          attempt: attemptCount + 1,
          results: quizResults,
          is_final: isFinal ? 1 : 0
        })
      });

      const data = await response.json();
      if (!response.ok) {
        throw new Error(`HTTP error ${response.status}: ${data.error || response.statusText}`);
      }
      if (data.error) {
        throw new Error(data.error);
      }
      console.log('Quiz results saved successfully:', data);
    } catch (err) {
      console.error('saveQuizResults error:', err);
      throw new Error(`Failed to save quiz results: ${err.message}`);
    }
  }

  async function saveRibbons(ribbons: number) {
    try {
      if (!student_id) {
        throw new Error('Invalid student ID');
      }
      if (ribbons < 0) {
        throw new Error('Invalid ribbon count');
      }
      console.log(`Saving ${ribbons} ribbons for student_id: ${student_id}`);
      const response = await fetch(apiUrl('student-story3/save_student_ribbons.php'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          student_id,
          ribbons
        })
      });

      const data = await response.json();
      if (!response.ok) {
        throw new Error(`HTTP error ${response.status}: ${data.error || response.statusText}`);
      }
      if (data.error) {
        throw new Error(data.error);
      }
      console.log('Ribbons saved successfully:', data);
    } catch (err) {
      console.error('saveRibbons error:', err);
      throw new Error(`Failed to save ribbons: ${err.message}`);
    }
  }

  function handleSubmit() {
    try {
      quizResults = $quizStore.answers.map(answer => {
        const questionData = quizData.find(q => q.id === answer.questionId);
        return {
          question: questionData?.question || 'Unknown question',
          answer: answer.answer,
          // For fill-in-the-blank, isCorrect is initially false (to be graded later)
          isCorrect: false,
          points: Number(questionData?.points || 0)
        };
      });
      earnedPoints = 0; // Points to be determined after grading
      console.log('Quiz submitted. Results:', quizResults);
      showResultModal = true;
    } catch (err) {
      console.error('Error in handleSubmit:', err);
      error = `Error processing quiz: ${err.message}`;
    }
  }

  async function handleRetake() {
    try {
      if (attemptCount < maxAttempts) {
        await saveQuizResults(false);
        attemptCount += 1;
        // Reset answers
        quizStore.set({
          answers: quizData.map(q => ({ questionId: q.id, answer: '', points: Number(q.points) }))
        });
        showResultModal = false;
        console.log('Retake initiated. Attempt:', attemptCount + 1);
      }
    } catch (err) {
      console.error('Error during retake:', err);
      error = `Error resetting quiz: ${err.message}`;
    }
  }

  async function handleFinalSubmit() {
    try {
      console.log('Final submit started. Student ID:', student_id);
      if (!student_id) {
        throw new Error('No student ID provided');
      }
      await saveQuizResults(true);
      await saveRibbons(earnedPoints);
      showResultModal = false;
      showCongratsModal = true;
      console.log('Final submit completed. Showing CongratsModal');
    } catch (err) {
      console.error('Error in handleFinalSubmit:', err.message, err.stack);
      error = `Error finalizing submission: ${err.message}. Please try again.`;
    }
  }

  function handleProceed() {
    try {
      console.log('Proceeding to next page');
      // close modal then navigate
      showCongratsModal = false;
      goto('/student/game/trash_3');
    } catch (err) {
      console.error('Error navigating:', err);
      error = `Error navigating to next page: ${err.message}`;
    }
  }
</script>

<div class="relative min-h-screen">
  {#if error}
    <div class="text-red-600 text-center text-2xl font-bold mt-4">
      {error}
    </div>
  {/if}
  {#if quizData.length === 0 && !error}
    <div class="text-gray-600 text-center text-2xl font-bold mt-10">
      Loading quiz...
    </div>
  {:else}
    <div class="h-screen w-screen bg-gradient-to-br from-indigo-200 via-purple-200 to-pink-200 p-8 flex flex-col items-center overflow-auto relative">
      <!-- Animated background elements -->
      <div class="absolute inset-0 pointer-events-none">
        <div class="absolute w-40 h-40 bg-yellow-300 opacity-20 rounded-full top-10 left-10 animate-pulse"></div>
        <div class="absolute w-60 h-60 bg-pink-300 opacity-20 rounded-full bottom-20 right-20 animate-pulse delay-1000"></div>
        <div class="absolute w-20 h-20 bg-blue-300 opacity-20 rounded-full top-1/2 left-1/3 animate-pulse delay-500"></div>
      </div>

      <!-- Header -->
      <h1 class="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 mb-6 animate-pulse">
        Quiz 3: Type Your Answers!
      </h1>

      <!-- Instructions -->
      <div class="bg-gradient-to-r from-blue-100 to-purple-100 rounded-2xl shadow-2xl p-8 mb-10 w-full max-w-4xl text-center text-xl font-semibold text-gray-900 border-4 border-yellow-300 transform hover:scale-105 transition-transform">
        <p class="mb-3 text-2xl text-purple-700">Type your answers in the boxes below each question!</p>
        <p class="text-lg">Make sure to answer all questions before submitting.</p>
      </div>

      <!-- Questions -->
      <div class="grid gap-8 w-full max-w-4xl flex-1">
        {#each quizData as question (question.id)}
          <div
            role="region"
            aria-label={`Question: ${question.question}`}
            class="bg-white rounded-2xl shadow-lg p-8 text-xl font-semibold text-gray-900 hover:bg-gradient-to-r hover:from-blue-50 hover:to-purple-50 transition-all transform hover:-translate-y-1 border-2 border-purple-200"
          >
            <span class="block mb-4">{question.question}</span>
            <input
              type="text"
              placeholder="Type your answer here..."
              value={$quizStore.answers.find(ans => ans.questionId === question.id)?.answer || ''}
              on:input={event => handleAnswerInput(event, question.id)}
              class="w-full p-3 border-2 border-purple-300 rounded-lg focus:outline-none focus:border-purple-500"
              aria-label={`Answer input for question: ${question.question}`}
            />
          </div>
        {/each}
      </div>

      <!-- Submit Button -->
      {#if allAnswered}
        <button
          on:click={handleSubmit}
          class="mt-8 bg-gradient-to-r from-green-400 to-blue-500 text-white font-bold px-8 py-4 rounded-full hover:scale-105 transition-transform shadow-lg"
        >
          Submit Quiz
        </button>
      {/if}
    </div>

    {#if showResultModal}
      <QuizResultModal2
        results={quizResults}
        attemptCount={attemptCount}
        maxAttempts={maxAttempts}
        on:retake={handleRetake}
        on:submit={handleFinalSubmit}
      />
    {/if}

    {#if showCongratsModal}
      <CongratsModal
        ribbons={earnedPoints}
        on:proceed={handleProceed}
      />
    {/if}
  {/if}
</div>