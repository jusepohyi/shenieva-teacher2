<script>
  export let quizType; // 'quiz1' or 'quiz2'
  import { fade, scale } from "svelte/transition";
  import { createEventDispatcher } from "svelte";
  // Avoid importing the image as a JS module (Vite may request it as ?import).
  // Use a string path so the browser requests it as a normal image resource.
  const modalBg = "/converted/assets/icons/modal-bg.webp";
  import { apiUrl } from '$lib/api_base';

  export let person;
  export let showModal;
  export let closeModal;
  export let markAsReviewed;

  const dispatch = createEventDispatcher();

  let showFireworks = false;
  let expandedQuestions = {};
  let reviewingIndex = null; // Add this at the top to track which question is being marked

  function triggerFireworks() {
    console.log("Fireworks triggered!");
    showFireworks = true;
    setTimeout(() => {
      showFireworks = false;
    }, 2500);
  }

  function handleOverlayClick(event) {
    if (event.target === event.currentTarget) {
      closeModal();
    }
  }

  function toggleQuestion(index) {
    expandedQuestions[index] = !expandedQuestions[index];
    expandedQuestions = { ...expandedQuestions };
  }

  // Mark individual question as reviewed
  async function markQuestionAsReviewed(index) {
    reviewingIndex = index;

    const endpoint =
      quizType === "quiz1"
        ? apiUrl('records/mark_question_reviewed.php')
        : apiUrl('records/mark_question2_reviewed.php');

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          taken_quiz_id: person.questions[index].taken_quiz_id,
          is_final: 1,
        }),
      });

      if (!response.ok) {
        console.error(
          "Failed to mark question as reviewed:",
          response.statusText
        );
        return;
      }

    person.questions[index].is_final = 1;
    person.questions = [...person.questions];

    // Check if quiz is now completely reviewed
    const allReviewed = person.questions.every(q => q.is_final === 1);
    if (allReviewed) {
      dispatch("reviewComplete");
    }

    // Still dispatch the questionReviewed event
    dispatch("questionReviewed", {
      taken_quiz_id: person.questions[index].taken_quiz_id,
      is_final: 1,
    });

    } catch (error) {
      console.error("Error marking question as reviewed:", error);
    } finally {
      reviewingIndex = null;
    }
  }

  // Compute review status
  $: reviewStatus = (() => {
    const finalCount = person?.questions?.filter(
      (q) => q.is_final === 1
    ).length;
    const totalQuestions = person?.questions?.length || 0;
    if (finalCount === totalQuestions && totalQuestions > 0) {
      return "Quiz is completely reviewed";
    } else if (finalCount > 0) {
      return "Partially reviewed";
    } else {
      return "Not reviewed";
    }
  })();

  // Update score for Quiz 1 (percentage of correct answers)
  function updateScore() {
    const correctCount = person.questions.reduce((count, q) => {
      return count + (q.chosenAnswer === q.correctAnswer ? 1 : 0);
    }, 0);
    person.score = Math.round((correctCount / person.questions.length) * 100);
  }
</script>

{#if showModal && person}
  <div
    class="fixed inset-0 bg-gray-800/50 flex items-center justify-center z-50 p-4 cursor-default"
    on:mousedown={handleOverlayClick}
    transition:fade={{ duration: 300 }}
  >
    <div
  class="rounded-3xl max-w-3xl w-full p-8 relative shadow-2xl bg-cover bg-center overflow-y-auto max-h-[90vh] custom-scrollbar"
  style="background-image: url({modalBg});"
      on:mousedown|stopPropagation
      transition:scale={{ duration: 300, start: 0.95 }}
    >
      <div class="absolute inset-0 bg-white/50 rounded-3xl"></div>
      <div class="relative z-10">
        <button
          on:click={closeModal}
          class="absolute top-4 right-4 text-gray-600 hover:text-gray-900 transition-colors focus:outline-none z-50 cursor-pointer"
          aria-label="Close modal"
        >
          <svg
            class="w-8 h-8"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>

        <div class="text-center mb-8">
          <div
            class="w-28 h-28 mx-auto rounded-full bg-gradient-to-br from-lime-300 to-lime-500 flex items-center justify-center text-5xl font-bold text-white border-4 border-white shadow-lg"
          >
            {person.name.charAt(0)}
          </div>
          <h2 class="text-3xl font-extrabold text-gray-900 mt-4 drop-shadow-sm">
            {person.name}'s Quiz Results
          </h2>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-8">
          <div
            class="bg-gray-200/80 rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow duration-300"
          >
            <p class="text-sm text-gray-600 font-medium">Gender</p>
            <p
              class="font-semibold text-lg text-gray-800 flex items-center gap-2"
            >
              {person.gender}
              {#if person.gender === "Male"}
                <img
                  src="/assets/icons/male-sign.svg"
                  alt="Male Icon"
                  class="w-5 h-5"
                />
              {/if}
              {#if person.gender === "Female"}
                <img
                  src="/assets/icons/female-sign.svg"
                  alt="Female Icon"
                  class="w-5 h-5"
                />
              {/if}
            </p>
          </div>
          <div
            class="bg-gray-200/80 rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow duration-300"
          >
            <p class="text-sm text-gray-600 font-medium">Date & Time</p>
            <p class="font-semibold text-lg text-gray-800">{person.datetime}</p>
          </div>
          <div
            class="bg-gray-200/80 rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow duration-300"
          >
            <p class="text-sm text-gray-600 font-medium">Score</p>
            <p class="font-semibold text-lg text-gray-800">
              {person.score === null ? "Unscored" : `${person.score} points`}
            </p>
          </div>
          <div
            class="bg-gray-200/80 rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow duration-300"
          >
            <p class="text-sm text-gray-600 font-medium">Attempts</p>
            <p class="font-semibold text-lg text-gray-800">{person.attempts}</p>
          </div>
          <div
            class="bg-gray-200/80 rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow duration-300 col-span-2"
          >
            <p class="text-sm text-gray-600 font-medium">Status</p>
            <p
              class="font-semibold text-lg {person.status === 'Reviewed'
                ? 'text-blue-700'
                : 'text-orange-700'}"
            >
              {person.status}
            </p>
          </div>
        </div>

        <div
          class="mb-8 flex items-center justify-center gap-3 bg-gradient-to-r from-lime-100 to-lime-200 rounded-xl py-4 shadow-md cursor-pointer relative"
          on:click={triggerFireworks}
        >
          <img
            src="/assets/icons/trophy_star.svg"
            alt="Trophy Icon"
            class="w-14 h-14 text-orange-500"
          />
          <div
            class="text-4xl font-extrabold {person.score === null
              ? 'text-gray-700'
              : person.score >= 80
                ? 'text-green-700'
                : person.score >= 60
                  ? 'text-orange-700'
                  : 'text-red-700'}"
          >
            {person.score === null ? "Unscored" : `${person.score} points`}
          </div>
          {#if showFireworks}
            <div class="pyro">
              <div class="before"></div>
              <div class="after"></div>
            </div>
          {/if}
        </div>

        <div class="mb-8">
          <h3 class="text-2xl font-bold text-gray-900 mb-6 drop-shadow-sm">
            Questions
          </h3>
          {#each person.questions as question, index}
            <div
              class="bg-gray-200/80 rounded-xl p-6 mb-6 shadow-md hover:shadow-lg transition-all duration-300"
            >
              <div
                class="flex justify-between items-center cursor-pointer"
                on:click={() => toggleQuestion(index)}
              >
                <p class="text-lg font-semibold text-gray-800">
                  {index + 1}. {question.text}
                </p>
                <svg
                  class="w-6 h-6 text-gray-600 transition-transform duration-300"
                  class:rotate-180={expandedQuestions[index]}
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </div>

              {#if expandedQuestions[index]}
                <div
                  class="mt-4 space-y-4"
                  in:fade={{ duration: 200 }}
                  out:fade={{ duration: 200 }}
                >
                  <!-- Latest Attempt -->
                  <div>
                    <p class="text-sm text-gray-600 font-medium">
                      Student's Answer (Attempt {person.attempts})
                    </p>
                    <p
                      class="font-semibold text-gray-800 flex items-center gap-2
                        {question.chosenAnswer === question.correctAnswer
                        ? 'text-green-700'
                        : 'text-orange-700'}"
                    >
                      {question.chosenAnswer || "No answer provided"}
                      {#if question.chosenAnswer}
                        {#if question.chosenAnswer === question.correctAnswer}
                          <svg
                            class="w-5 h-5 text-green-500"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M5 13l4 4L19 7"
                            />
                          </svg>
                        {:else}
                          <svg
                            class="w-5 h-5 text-orange-500"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"
                            />
                          </svg>
                        {/if}
                      {/if}
                    </p>
                  </div>

                  <!-- Review Button for is_final=0 -->
                  {#if question.is_final === 0}
                    <button
                      on:click={() => markQuestionAsReviewed(index)}
                      class="px-4 py-2 bg-lime-500 text-white rounded-full transition-all duration-300 transform hover:scale-105 flex items-center gap-2 shadow-md hover:shadow-lg cursor-pointer disabled:opacity-50"
                      disabled={reviewingIndex === index}
                    >
                      {#if reviewingIndex === index}
                        <svg
                          class="w-5 h-5 animate-spin"
                          fill="none"
                          viewBox="0 0 24 24"
                        >
                          <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                          />
                          <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v8z"
                          />
                        </svg>
                        <span>Saving...</span>
                      {:else}
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          class="w-5 h-5"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                          />
                        </svg>
                        <span class="font-medium">Review</span>
                      {/if}
                    </button>
                  {/if}

                  <!-- Choices -->
                  {#if question.choices}
                    <div>
                      <p class="text-sm text-gray-600 font-medium">Choices:</p>
                      <ul class="mt-2 space-y-2">
                        {#each Object.entries(question.choices) as [key, value]}
                          <li
                            class="p-3 rounded-lg flex items-center gap-3 transition-colors duration-200
                              {value === question.chosenAnswer
                              ? 'ring-2 ring-lime-500'
                              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}
                              {value === question.chosenAnswer &&
                            value === question.correctAnswer
                              ? 'bg-green-100 text-green-800 border-l-4 border-green-500'
                              : value === question.chosenAnswer
                                ? 'bg-orange-100 text-orange-800 border-l-4 border-orange-500'
                                : value === question.correctAnswer
                                  ? 'bg-green-100 text-green-800 border-l-4 border-green-500'
                                  : ''}"
                          >
                            <span class="font-medium">{key.toUpperCase()}.</span
                            >
                            <span>{value}</span>
                          </li>
                        {/each}
                      </ul>
                    </div>
                  {/if}
                </div>
              {/if}
            </div>
          {/each}
        </div>

        <div class="flex justify-center gap-4">
          <div
            class="px-6 py-3 rounded-full flex items-center gap-2 shadow-md
              {reviewStatus === 'Quiz is completely reviewed'
              ? 'bg-blue-100 text-blue-800'
              : reviewStatus === 'Partially reviewed'
                ? 'bg-orange-100 text-orange-800'
                : 'bg-gray-100 text-gray-800'}"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
            <span class="font-medium">{reviewStatus}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
{/if}

<style>
  .custom-scrollbar::-webkit-scrollbar {
    width: 8px;
  }

  .custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
  }

  .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #a3e635;
    border-radius: 9999px;
  }

  .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #84cc16;
  }

  .custom-scrollbar::-webkit-scrollbar-button {
    display: none;
  }

  .custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #a3e635 transparent;
  }

  .pyro {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 20;
  }

  .pyro > .before,
  .pyro > .after {
    position: absolute;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    box-shadow:
      -120px -218.66667px blue,
      248px -16.66667px #00ff84,
      190px 16.33333px #002bff,
      -113px -308.66667px #ff009d,
      -109px -287.66667px #ffb300,
      -50px -313.66667px #ff006e,
      226px -31.66667px #ff4000,
      180px -351.66667px #ff00d0,
      -12px -338.66667px #00f6ff,
      220px -388.66667px #99ff00,
      -69px -27.66667px #ff0400,
      -111px -339.66667px #6200ff,
      155px -237.66667px #00ddff,
      -152px -380.66667px #00ffd0,
      -50px -37.66667px #00ffdd,
      -95px -175.66667px #a6ff00,
      -88px 10.33333px #0d00ff,
      112px -309.66667px #005eff,
      69px -415.66667px #ff00a6,
      168px -100.66667px #ff004c,
      -244px 24.33333px #ff6600,
      97px -325.66667px #ff0066,
      -211px -182.66667px #00ffa2,
      236px -126.66667px #b700ff,
      140px -196.66667px #9000ff,
      125px -175.66667px #00bbff,
      118px -381.66667px #ff002f,
      144px -111.66667px #ffae00,
      36px -78.66667px #f600ff,
      -63px -196.66667px #c800ff,
      -218px -227.66667px #d4ff00,
      -134px -377.66667px #ea00ff,
      -36px -412.66667px #ff00d4,
      209px -106.66667px #00fff2,
      91px -278.66667px #000dff,
      -22px -191.66667px #9dff00,
      139px -392.66667px #a6ff00,
      56px -2.66667px #0099ff,
      -156px -276.66667px #ea00ff,
      -163px -233.66667px #00fffb,
      -238px -346.66667px #00ff73,
      62px -363.66667px #0088ff,
      244px -170.66667px #0062ff,
      224px -142.66667px #b300ff,
      141px -208.66667px #9000ff,
      211px -285.66667px #ff6600,
      181px -128.66667px #1e00ff,
      90px -123.66667px #c800ff,
      189px 70.33333px #00ffc8,
      -18px -383.66667px #00ff33,
      100px -6.66667px #ff008c;
    animation:
      1s bang ease-out infinite backwards,
      1s gravity ease-in infinite backwards,
      5s position linear infinite backwards;
  }

  .pyro > .after {
    animation-delay: 1.25s, 1.25s, 1.25s;
    animation-duration: 1.25s, 1.25s, 6.25s;
  }

  @keyframes bang {
    from {
      box-shadow:
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white,
        0 0 white;
    }
  }

  @keyframes gravity {
    to {
      transform: translateY(200px);
      opacity: 0;
    }
  }

  @keyframes position {
    0%,
    19.9% {
      margin-top: 10%;
      margin-left: 40%;
    }
    20%,
    39.9% {
      margin-top: 40%;
      margin-left: 30%;
    }
    40%,
    59.9% {
      margin-top: 20%;
      margin-left: 70%;
    }
    60%,
    79.9% {
      margin-top: 30%;
      margin-left: 20%;
    }
    80%,
    99.9% {
      margin-top: 30%;
      margin-left: 80%;
    }
  }

  .rotate-180 {
    transform: rotate(180deg);
  }
</style>
