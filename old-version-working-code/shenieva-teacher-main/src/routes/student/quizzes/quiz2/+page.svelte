<script lang="ts">
  import { onMount } from 'svelte';
  import { writable } from 'svelte/store';
  import { goto } from '$app/navigation';
  import { apiUrl } from '$lib/api_base';
  import QuizResultModal2 from '../../components/modals/quiz1/QuizResultModal2.svelte';
  import CongratsModal from '../../components/modals/quiz1/CongratsModal.svelte';

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

  // Drag state
  const draggedAnswer = writable(null);

  onMount(async () => {
    console.log('Quiz component mounted');
    try {
      // Fetch quiz data
  const quizResponse = await fetch(apiUrl('student-story2/get_story2_quizzes.php'), {
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
      
      // Create randomized answers
      const randomizedAnswers = data
        .map(q => ({ text: q.answer, assignedTo: null, originalQuestionId: q.id, points: Number(q.points) }))
        .sort(() => Math.random() - 0.5);
      
      quizStore.set({
        answers: randomizedAnswers
      });

      // Fetch attempt count for the student
  const attemptResponse = await fetch(apiUrl(`student-story2/get_student_attempts.php?student_id=${student_id}`), {
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
      console.log('Quiz component destroyed');
    };
  });

  // Derived state
  $: allAnswered = $quizStore.answers.every(answer => answer.assignedTo !== null);

  // Drag and drop handlers
  function handleDragStart(event: DragEvent, answer: any) {
    $draggedAnswer = answer;
    if (event.dataTransfer && answer) {
      event.dataTransfer.setData('text/plain', answer.text);
    }
  }

  function handleDragOver(event: DragEvent) {
    event.preventDefault();
  }

  function handleDrop(event: DragEvent, question: any) {
    event.preventDefault();
    const hasAnswer = $quizStore.answers.some(answer => answer.assignedTo === question.id);
    if ($draggedAnswer && !hasAnswer && $draggedAnswer.text) {
      quizStore.update(store => {
        const updatedAnswers = store.answers.map(ans =>
          ans.text === $draggedAnswer.text ? { ...ans, assignedTo: question.id } : ans
        );
        return { ...store, answers: updatedAnswers };
      });
    }
    $draggedAnswer = null;
  }

  function handleDropToAnswerBox(event: DragEvent) {
    event.preventDefault();
    if ($draggedAnswer && $draggedAnswer.text) {
      quizStore.update(store => {
        const updatedAnswers = store.answers.map(ans =>
          ans.text === $draggedAnswer.text ? { ...ans, assignedTo: null } : ans
        );
        return { ...store, answers: updatedAnswers };
      });
    }
    $draggedAnswer = null;
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
  const response = await fetch(apiUrl('student-story2/save_quiz_results.php'), {
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
  const response = await fetch(apiUrl('student-story2/save_student_ribbons.php'), {
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
      quizResults = $quizStore.answers.map(answer => ({
        question: quizData.find(q => q.id === answer.assignedTo)?.question || 'Not assigned',
        answer: answer.text,
        isCorrect: answer.assignedTo === answer.originalQuestionId,
        points: Number(quizData.find(q => q.id === answer.originalQuestionId)?.points || 0)
      }));
      earnedPoints = quizResults.reduce((sum, r) => sum + (r.isCorrect ? r.points : 0), 0);
      console.log('Quiz submitted. Earned points:', earnedPoints, 'Results:', quizResults);
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
        // Reset quiz
        const randomizedAnswers = quizData
          .map(q => ({ text: q.answer, assignedTo: null, originalQuestionId: q.id, points: Number(q.points) }))
          .sort(() => Math.random() - 0.5);
        
        quizStore.set({
          answers: randomizedAnswers
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
      console.log('Final submit started. Student ID:', student_id, 'Earned points:', earnedPoints);
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
      console.log('Proceeding to trash_2 page');
      showCongratsModal = false;
      goto('/student/game/trash_2');
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
        Super Fun Quiz Adventure!
      </h1>

      <!-- Instructions -->
      <div class="bg-gradient-to-r from-blue-100 to-purple-100 rounded-2xl shadow-2xl p-8 mb-10 w-full max-w-4xl text-center text-xl font-semibold text-gray-900 border-4 border-yellow-300 transform hover:scale-105 transition-transform">
        <p class="mb-3 text-2xl text-purple-700">Drag the sparkly answers to the matching questions!</p>
        <p class="text-lg">Want to change an answer? Drag it back to the answer box!</p>
      </div>

      <!-- Answer Box -->
      <div
        role="region"
        aria-label="Answer box"
        on:dragover={handleDragOver}
        on:drop={handleDropToAnswerBox}
        class="bg-gradient-to-b from-white to-gray-50 rounded-2xl shadow-xl p-8 mb-10 w-full max-w-4xl flex flex-wrap gap-6 justify-center border-4 border-dashed border-purple-400"
      >
        {#each $quizStore.answers as answer (answer.text)}
          {#if !answer.assignedTo}
            <div
              role="button"
              aria-label={`Drag answer: ${answer.text}`}
              tabindex="0"
              draggable="true"
              on:dragstart={event => handleDragStart(event, answer)}
              class="bg-gradient-to-r from-yellow-400 to-orange-400 text-purple-900 font-bold px-6 py-3 rounded-full cursor-move hover:scale-110 hover:shadow-lg transition-all transform"
            >
              {answer.text}
            </div>
          {/if}
        {/each}
      </div>

      <!-- Questions -->
      <div class="grid gap-8 w-full max-w-4xl flex-1">
        {#each quizData as question (question.id)}
          <div
            role="region"
            aria-label={`Question: ${question.question}`}
            on:dragover={handleDragOver}
            on:drop={event => handleDrop(event, question)}
            class="bg-white rounded-2xl shadow-lg p-8 text-xl font-semibold text-gray-900 flex items-center justify-between hover:bg-gradient-to-r hover:from-blue-50 hover:to-purple-50 transition-all transform hover:-translate-y-1 border-2 border-purple-200"
          >
            <span class="flex-1">{question.question}</span>
            {#each $quizStore.answers as answer}
              {#if answer.assignedTo === question.id}
                <div
                  role="button"
                  aria-label={`Assigned answer: ${answer.text}`}
                  tabindex="0"
                  draggable="true"
                  on:dragstart={event => handleDragStart(event, answer)}
                  class="bg-gradient-to-r from-yellow-400 to-orange-400 text-purple-900 font-bold px-6 py-3 rounded-full cursor-move hover:scale-110 transition-transform"
                >
                  {answer.text}
                </div>
              {/if}
            {/each}
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