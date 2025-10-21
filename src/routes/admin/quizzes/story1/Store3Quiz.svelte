<script lang="ts">
    import {
      CaretDownSolid,
      TrashBinSolid,
      EditSolid,
    } from "flowbite-svelte-icons";
    import sanitizeForDisplay from '$lib/utils/sanitize';
  
    interface Quiz {
      id: number;
      question: string;
      choices: string[];
      answer: string;
      points: number;
      expanded: boolean;
    }
  
    let quizzes: Quiz[] = [];
  
    async function fetchQuizzes() {
      try {
        const response = await fetch(
          "http://localhost/shenieva-teacher/src/lib/api/store3/fetch_quizzes.php",
          {
            method: "GET",
            headers: { "Content-Type": "application/json" },
          },
        );
        const data = await response.json();
        if (Array.isArray(data)) {
          quizzes = data.map((quiz) => ({
            id: quiz.id,
            question: quiz.question,
            choices: quiz.choices,
            answer: quiz.answer,
            points: quiz.points,
            expanded: false,
          }));
        } else {
          console.error("Invalid data format from 3:", data);
        }
      } catch (error) {
        console.error("Failed to fetch Store 3 quizzes:", error);
      }
    }
  
    fetchQuizzes();
  
    let newQuestion = "";
    let newChoices: string[] = [];
    let newChoice = "";
    let newAnswer = "";
    let newPoints: number | null = null;
    let showModal = false;
    let isEditing = false;
    let editingQuizId: number | null = null;
  
    function addChoice() {
      if (newChoice.trim() && !newChoices.includes(newChoice)) {
        newChoices = [...newChoices, newChoice];
        newChoice = "";
      }
    }
  
    function removeChoice(index: number) {
      newChoices = newChoices.filter((_, i) => i !== index);
      if (newChoices.length === 0) newAnswer = ""; // Reset answer if no choices remain
    }
  
    async function addQuiz() {
      if (
        newQuestion.trim() &&
        newChoices.length > 0 &&
        newAnswer.trim() &&
        newPoints !== null &&
        newPoints > 0 &&
        newChoices.includes(newAnswer)
      ) {
        try {
          const response = await fetch(
            "http://localhost/shenieva-teacher/src/lib/api/store3/add_quiz1.php",
            {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                question: newQuestion,
                choices: newChoices,
                answer: newAnswer,
                points: newPoints,
              }),
            },
          );
          const result = await response.json();
          if (result.success) {
            fetchQuizzes();
            closeModal();
          } else {
            console.error("Failed to add Store 3 quiz:", result.error);
          }
        } catch (error) {
          console.error("Error adding Store 3 quiz:", error);
        }
      } else {
        console.error("All fields are required, points must be positive, and answer must be one of the choices.");
      }
    }
  
    async function deleteQuiz(id: number) {
      if (confirm("Are you sure you want to delete this Store 3 quiz?")) {
        try {
          const response = await fetch(
            "http://localhost/shenieva-teacher/src/lib/api/store3/delete_quiz.php",
            {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ id }),
            },
          );
          const result = await response.json();
          if (result.success) {
            quizzes = quizzes.filter((quiz) => quiz.id !== id);
          } else {
            console.error("Failed to delete Store 3 quiz:", result.error);
          }
        } catch (error) {
          console.error("Error deleting Store 3 quiz:", error);
        }
      }
    }
  
    function toggleExpand(id: number) {
      quizzes = quizzes.map((quiz) =>
        quiz.id === id ? { ...quiz, expanded: !quiz.expanded } : quiz,
      );
    }
  
    function editQuiz(quizId: number) {
      const quiz = quizzes.find((q) => q.id === quizId);
      if (quiz) {
        isEditing = true;
        editingQuizId = quizId;
        newQuestion = quiz.question;
        newChoices = [...quiz.choices]; // Copy choices array
        newAnswer = quiz.answer;
        newPoints = quiz.points;
        showModal = true;
      }
    }
  
    async function updateQuiz() {
      if (
        editingQuizId !== null &&
        newQuestion.trim() &&
        newChoices.length > 0 &&
        newAnswer.trim() &&
        newPoints !== null &&
        newPoints > 0 &&
        newChoices.includes(newAnswer)
      ) {
        try {
          const response = await fetch(
            "http://localhost/shenieva-teacher/src/lib/api/store3/edit_quiz.php",
            {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                id: editingQuizId,
                question: newQuestion,
                choices: newChoices,
                answer: newAnswer,
                points: newPoints,
              }),
            },
          );
          const result = await response.json();
          if (result.success) {
            fetchQuizzes();
            closeModal();
          } else {
            console.error("Failed to update Store 3 quiz:", result.error);
          }
        } catch (error) {
          console.error("Error updating Store 3 quiz:", error);
        }
      } else {
        console.error("All fields are required, points must be positive, and answer must be one of the choices.");
      }
    }
  
    function closeModal() {
      showModal = false;
      isEditing = false;
      editingQuizId = null;
      newQuestion = "";
      newChoices = [];
      newChoice = "";
      newAnswer = "";
      newPoints = null;
    }
  
    function totalPoints() {
      return quizzes.reduce((sum, quiz) => sum + quiz.points, 0);
    }
  </script>
  
  <div class="p-6 bg-white dark:bg-gray-900 shadow-lg rounded-lg">
    <div class="flex justify-between items-center mb-6">
      <button
        on:click={() => (showModal = true)}
        class="bg-lime-500 text-white px-5 py-2 rounded-lg hover:bg-lime-600 shadow"
      >
        + Add Quiz
      </button>
    </div>
  
    <div class="overflow-x-auto mt-4">
      <table class="w-full bg-white text-sm rounded-lg shadow-md border border-gray-200">
        <thead class="bg-orange-500 text-white rounded-t-lg">
          <tr>
            <th class="p-4 text-left">Question</th>
            <th class="p-4 text-center">Points</th>
            <th class="p-4 text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          {#each quizzes as quiz (quiz.id)}
            <tr
              class="border-b border-gray-300 hover:bg-lime-100 transition cursor-pointer"
              on:click={() => toggleExpand(quiz.id)}
            >
              <td class="p-4 text-gray-800">
                <span class="text-lg float-left"><CaretDownSolid /></span>
                {sanitizeForDisplay(quiz.question) ?? quiz.question}
              </td>
              <td class="p-4 text-center text-gray-800">{quiz.points}</td>
              <td class="p-4 text-center text-gray-700">
                <button
                  on:click|stopPropagation={() => editQuiz(quiz.id)}
                  class="text-green-500 hover:text-green-700 mr-3"
                >
                  <EditSolid />
                </button>
                <button
                  on:click|stopPropagation={() => deleteQuiz(quiz.id)}
                  class="text-red-500 hover:text-red-700"
                >
                  <TrashBinSolid />
                </button>
              </td>
            </tr>
            {#if quiz.expanded}
              <tr class="bg-lime-50">
                <td colspan="3" class="p-4 rounded-lg">
                  <p class="text-gray-700">
                    <strong>Choices:</strong>
                    {#each quiz.choices as choice}
                      <br />   ‣ {sanitizeForDisplay(choice) ?? choice}
                    {/each}
                  </p>
                  <p class="text-green-600 font-semibold mt-2">
                    <strong>Answer: </strong>
                    {sanitizeForDisplay(quiz.answer) ?? quiz.answer}
                  </p>
                  <p class="text-blue-600 font-semibold mt-2">
                    <strong>Points: </strong>
                    {quiz.points}
                  </p>
                </td>
              </tr>
            {/if}
          {/each}
        </tbody>
      </table>
    </div>
  
    <div class="mt-4 text-right text-lg font-semibold text-gray-700">
      Total Points: {totalPoints()}
    </div>
  
    {#if showModal}
      <div class="fixed inset-0 flex items-center justify-center bg-black/50">
        <div class="bg-white p-6 rounded-2xl shadow-2xl w-[700px] relative border-t-4 border-orange-500">
          <button
            on:click={closeModal}
            class="absolute top-3 right-3 text-gray-500 hover:text-red-500 text-xl"
          >
            ✖
          </button>
  
          <h2 class="text-2xl font-bold text-orange-600 text-center mb-4">
            {isEditing ? "Edit Store 3 Quiz" : "Add Store 3 Quiz"}
          </h2>
  
          <div class="relative w-full mb-4">
            <input
              bind:value={newQuestion}
              id="question"
              placeholder=" "
              class="peer w-full p-3 border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none"
            />
            <label
              for="question"
              class="absolute left-3 bg-white px-2 text-sm text-orange-600 transition-all
                   {newQuestion ? 'top-[-10px] px-2' : 'top-3 text-gray-400 text-lg peer-placeholder-shown:top-3 peer-placeholder-shown:text-lg'}
                   peer-focus:top-[-10px] peer-focus:text-orange-600 peer-focus:text-sm peer-focus:px-2"
            >
              Enter Question
            </label>
          </div>
  
          <div class="mb-4">
            <div class="flex space-x-2 mb-3">
              <div class="relative flex-1">
                <input
                  bind:value={newChoice}
                  id="choice"
                  placeholder=" "
                  class="peer w-full p-3 border border-lime-300 rounded-lg focus:ring-2 focus:ring-lime-400 focus:outline-none"
                />
                <label
                  for="choice"
                  class="absolute left-3 bg-white px-2 text-sm text-lime-600 transition-all
                       {newChoice ? 'top-[-10px] px-2' : 'top-3 text-gray-400 text-lg peer-placeholder-shown:top-3 peer-placeholder-shown:text-lg'}
                       peer-focus:top-[-10px] peer-focus:text-lime-600 peer-focus:text-sm peer-focus:px-2"
                >
                  Add Choice
                </label>
              </div>
              <button
                on:click={addChoice}
                class="bg-lime-500 text-white px-5 py-2 rounded-lg hover:bg-lime-600 transition"
              >
                +
              </button>
            </div>
  
            {#each newChoices as choice, index}
              <div class="flex justify-between items-center bg-gray-100 p-2 rounded mb-2">
                <span>{choice}</span>
                <button
                  on:click={() => removeChoice(index)}
                  class="text-red-500 hover:text-red-700"
                >
                  ✖
                </button>
              </div>
            {/each}
  
            <div class="relative w-full mb-4">
              <select
                bind:value={newAnswer}
                id="answer"
                class="peer w-full p-3 border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none"
              >
                <option value="" disabled>Select Answer</option>
                {#each newChoices as choice}
                  <option value={choice}>{choice}</option>
                {/each}
              </select>
              <label
                for="answer"
                class="absolute left-3 bg-white px-2 text-sm text-orange-600 transition-all
                     {newAnswer ? 'top-[-10px] px-2' : 'top-3 text-gray-400 text-lg peer-placeholder-shown:top-3 peer-placeholder-shown:text-lg'}
                     peer-focus:top-[-10px] peer-focus:text-orange-600 peer-focus:text-sm peer-focus:px-2"
              >
                Select Correct Answer
              </label>
            </div>
  
            <div class="relative w-full mb-4">
              <input
                type="number"
                bind:value={newPoints}
                id="points"
                min="5"
                max="100"
                step="5"
                placeholder=" "
                class="peer w-full p-3 border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-400 focus:outline-none"
              />
              <label
                for="points"
                class="absolute left-3 bg-white px-2 text-sm text-orange-600 transition-all
                     {newPoints !== null ? 'top-[-10px] px-2' : 'top-3 text-gray-400 text-lg peer-placeholder-shown:top-3 peer-placeholder-shown:text-lg'}
                     peer-focus:top-[-10px] peer-focus:text-orange-600 peer-focus:text-sm peer-focus:px-2"
              >
                Enter Points
              </label>
            </div>
          </div>
  
          <button
            on:click={isEditing ? updateQuiz : addQuiz}
            class="bg-orange-500 text-white px-5 py-2 rounded-lg hover:bg-orange-600 transition w-full"
          >
            {isEditing ? "Update Quiz" : "Add Store 3 Quiz"}
          </button>
        </div>
      </div>
    {/if}
  </div>