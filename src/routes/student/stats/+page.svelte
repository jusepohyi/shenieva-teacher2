<!-- src/routes/student/stats/+page.svelte -->
<script>
  import { slide } from 'svelte/transition';
  import { studentData } from '$lib/store/student_data';
  import { onMount, onDestroy } from 'svelte';
  import LevelModal from '../components/modals/stats/LevelModal.svelte';
  import TrashModal from '../components/modals/stats/TrashModal.svelte';
  import ItemsModal from '../components/modals/stats/ItemsModal.svelte';

  // Reactive quiz scores (placeholder)
  let quizScores = { 1: 'Not Taken', 2: 'Not Taken', 3: 'Not Taken' };

  // Items fetched from the database
  /** @type {Array<any>} */
  let items = [];
  let isLoading = true;

  // Modal state
  /** @type {boolean} */
  let isModalOpen = false;
  /** @type {string | null} */
  let modalType = null;
  /** @type {Array<any> | null} */
  let modalListData = null;
  /** @type {string} */
  let modalText = '';
  // track which attempt groups are open in modal
  /** @type {Set<number>} */
  let openAttempts = new Set();

  /**
   * Toggle an attempt open/closed
   * @param {number} index
   */
  function toggleAttempt(index) {
    if (openAttempts.has(index)) openAttempts.delete(index);
    else openAttempts.add(index);
    // trigger reactivity by creating a new Set reference
    openAttempts = new Set(openAttempts);
  }

  /**
   * @param {any} ts
   */
  function formatDate(ts) {
    try {
      if (!ts) return '';
      const d = new Date(ts);
      return d.toLocaleString();
    } catch (e) {
      return String(ts);
    }
  }

  // Fetch items
  async function fetchItems() {
    if (!$studentData?.pk_studentID) return;
    try {
      const response = await fetch(
        `http://localhost/shenieva-teacher/src/lib/api/get_student_items.php?studentID=${$studentData.pk_studentID}`
      );
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      const data = await response.json();
      items = data;
    } catch (error) {
      console.error('Error fetching items:', error);
      items = [];
    }
  }


  // Fetch quiz scores (placeholder)
  async function fetchQuizScores() {
    if (!$studentData?.pk_studentID) return;
    try {
      quizScores = {
        1: $studentData?.studentLevel >= 1 ? 'Not Taken' : 'Locked',
        2: $studentData?.studentLevel >= 2 ? 'Not Taken' : 'Locked',
        3: $studentData?.studentLevel >= 3 ? 'Not Taken' : 'Locked',
      };
    } catch (error) {
      console.error('Error fetching quiz scores:', error);
    }
  }

  // Fetch quiz rows for a given level and set modalListData
  /**
   * Fetch quiz rows for a level and populate modalListData
   * @param {number} level
   */
  async function fetchQuizForLevel(level) {
    if (!$studentData?.pk_studentID) {
      modalText = 'Student not signed in.';
      modalListData = null;
      return;
    }

    /** @type {{[k:string]:string}} */
    const urls = {
      '1': '/src/lib/api/get_level1_quiz_results.php',
      '2': '/src/lib/api/get_level2_quiz_results.php',
      '3': '/src/lib/api/get_level3_quiz_results.php'
    };

    const apiPath = urls[String(level)];
    const url = `http://localhost/shenieva-teacher${apiPath}${apiPath.includes('?') ? '&' : '?'}studentID=${encodeURIComponent($studentData.pk_studentID)}`;
    modalText = 'Loading...';
    modalListData = null;

    try {
      const res = await fetch(url);
      const data = await res.json();
      if (!data.success) {
        modalText = 'Failed to load quiz data.';
        modalListData = [];
        return;
      }

      const allRows = (data.data || []);
      const matchId = String($studentData?.pk_studentID ?? '').trim();
      const matchIdNo = String($studentData?.idNo ?? '').trim().toLowerCase();
      const matchName = String($studentData?.studentName ?? '').trim().toLowerCase();

      let rows = allRows.filter(/** @param {any} r */ (r) => String(r.studentID ?? '').trim() === matchId);
      if ((!rows || rows.length === 0) && matchIdNo) {
        rows = allRows.filter(/** @param {any} r */ (r) => String(r.idNo ?? '').trim().toLowerCase() === matchIdNo);
      }
      if ((!rows || rows.length === 0) && matchName) {
        rows = allRows.filter(/** @param {any} r */ (r) => String(r.studentName ?? '').trim().toLowerCase() === matchName);
      }

      if (!rows || rows.length === 0) {
        modalText = 'Not played yet.';
        modalListData = [];
        return;
      }

      // group by storyTitle + attempt
  /** @type {Record<string, any>} */
  const groups = {};
  rows.forEach(/** @param {any} r */ (r) => {
        const attempt = String(r.attempt ?? '1');
        const story = r.storyTitle ?? r.story_title ?? 'Story';
        const key = `${story}::${attempt}`;
        const p = Number(r.score ?? r.point ?? 0) || 0;
  if (!groups[key]) groups[key] = { storyTitle: story, attempt: Number(attempt), totalPoints: 0, items: 0, createdAt: r.createdAt, rows: [] };
        groups[key].totalPoints += p;
        groups[key].items += 1;
        groups[key].rows.push(r);
        if (r.createdAt && (!groups[key].createdAt || groups[key].createdAt < r.createdAt)) groups[key].createdAt = r.createdAt;
      });

      const attempts = Object.values(groups).sort((a, b) => (b.attempt - a.attempt) || ((b.createdAt || '').localeCompare(a.createdAt || '')));
      modalListData = attempts;
      modalText = `${attempts.length} attempt${attempts.length !== 1 ? 's' : ''}`;
    } catch (e) {
      console.error('Quiz fetch error', e);
      modalText = 'Error fetching data.';
      modalListData = [];
    }
  }

  // Get level display text
  function getLevelText() {
    if (!$studentData) return 'Level 0 - Not Started';
    return `Level ${$studentData.studentLevel || 0}`;
  }

  // Open modal
  /**
   * @param {string} type
   */
  function openModal(type) {
    modalType = type;
    isModalOpen = true;
  }

  // Close modal
  function closeModal() {
    isModalOpen = false;
    modalType = null;
  }

  /** @type {(e: KeyboardEvent) => void} */
  let _escHandler = (/** @type {KeyboardEvent} */ _e) => {};
  onMount(async () => {
    if ($studentData) {
      await Promise.all([fetchItems(), fetchQuizScores()]);
    }
    // Attach Escape handler
    _escHandler = /** @param {KeyboardEvent} e */ (e) => { if (e.key === 'Escape') closeModal(); };
    window.addEventListener('keydown', _escHandler);
    isLoading = false;
  });

  onDestroy(() => {
    if (_escHandler) window.removeEventListener('keydown', _escHandler);
  });
</script>

<div
  in:slide={{ duration: 400, y: 20 }}
  class="w-full max-w-4xl mx-auto p-4 text-center bg-gradient-to-br from-blue-50 via-pink-50 to-yellow-50 rounded-3xl shadow-lg flex flex-col items-center"
>
  <!-- Header -->
  <h1
    class="text-4xl md:text-5xl font-extrabold text-purple-600 mb-[2vh] animate-bounce-slow"
    in:slide={{ duration: 600 }}
    style="font-family: 'Comic Sans MS', 'Chalkboard', cursive;"
  >
    Your Super Stats! 🌟
  </h1>

  <!-- Stats Grid -->
  <div class="grid grid-cols-2 md:grid-cols-3 gap-4 w-full">
    <!-- Level Card -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-pink-500 active:bg-pink-50"
      in:slide={{ duration: 400, delay: 100 }}
      on:click={() => openModal('level')}
      type="button"
    >
      <h2 class="text-xl font-bold text-pink-600 mb-1 flex items-center justify-center">
        <span class="mr-2">🏆</span> Level
      </h2>
      <p class="text-sm font-semibold text-gray-700">{getLevelText()}</p>
  </button>

    <!-- Quiz 1 Card (Level 1) -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-lime-500 active:bg-lime-50"
      in:slide={{ duration: 400, delay: 200 }}
      on:click={async () => { modalType = 'quiz1'; isModalOpen = true; await fetchQuizForLevel(1); }}
      type="button"
    >
      <h2 class="text-xl font-bold text-lime-600 mb-1 flex items-center justify-center">
        <span class="mr-2">�</span> Quiz 1 (Level 1)
      </h2>
      <p class="text-sm font-semibold text-gray-700">{quizScores[1]}</p>
    </button>

    <!-- Quiz 2 Card (Level 2) -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-blue-500 active:bg-blue-50"
      in:slide={{ duration: 400, delay: 300 }}
      on:click={async () => { modalType = 'quiz2'; isModalOpen = true; await fetchQuizForLevel(2); }}
      type="button"
    >
      <h2 class="text-xl font-bold text-blue-600 mb-1 flex items-center justify-center">
        <span class="mr-2">📝</span> Quiz 2 (Level 2)
      </h2>
      <p class="text-sm font-semibold text-gray-700">{quizScores[2]}</p>
    </button>

    <!-- Quiz 3 Card (Level 3) -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-blue-500 active:bg-blue-50"
      in:slide={{ duration: 400, delay: 400 }}
      on:click={async () => { modalType = 'quiz3'; isModalOpen = true; await fetchQuizForLevel(3); }}
      type="button"
    >
      <h2 class="text-xl font-bold text-blue-600 mb-1 flex items-center justify-center">
        <span class="mr-2">📝</span> Quiz 3 (Level 3)
      </h2>
      <p class="text-sm font-semibold text-gray-700">{quizScores[3]}</p>
    </button>

      
 
    <!-- Trash Card -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-teal-500 active:bg-teal-50"
      in:slide={{ duration: 400, delay: 400 }}
      on:click={() => { modalType = 'trash'; isModalOpen = true; }}
      type="button"
    >
      <h2 class="text-xl font-bold text-teal-600 mb-1 flex items-center justify-center">
        <span class="mr-2">🗑️</span> Trash Collected
      </h2>
      <p class="text-sm font-semibold text-gray-700">
        {$studentData?.studentColtrash || 0} Pieces
      </p>
  </button>

 
    <!-- Items Card -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-cyan-500 active:bg-cyan-50"
      in:slide={{ duration: 400, delay: 600 }}
      on:click={() => { modalType = 'items'; isModalOpen = true; }}
      type="button"
    >
      <h2 class="text-xl font-bold text-cyan-600 mb-1 flex items-center justify-center">
        <span class="mr-2">🎁</span> Gifts
      </h2>
      <p class="text-sm font-semibold text-gray-700">
        {items.length} Collected
      </p>
      {#if items.length > 0}
        <div class="mt-1 flex flex-wrap justify-center gap-2">
          {#each items as item}
            <div class="flex flex-col items-center">
              <img
                src={item.itemLocation ?? `/src/assets/Level_Walkthrough/gift/gifts/${encodeURIComponent(item.itemName)}.png`}
                alt={item.itemName}
                class="w-12 h-12 object-contain"
                on:error={(e) => {
                  const target = /** @type {HTMLImageElement | null} */ (e?.target ?? null);
                  if (target) {
                    target.onerror = null;
                    target.src = `/src/assets/Level_Walkthrough/gift/gifts/${encodeURIComponent(item.itemName)}.png`;
                  }
                }}
              />
              <span class="text-xs text-gray-600 mt-1">{item.itemName}</span>
            </div>
          {/each}
        </div>
      {:else}
        <p class="text-xs text-gray-600 mt-1">No items yet!</p>
      {/if}
  </button>
  </div>

  <!-- Modals -->
  {#if isModalOpen}
    <div class="fixed inset-0 z-50 flex items-center justify-center">
      <button class="absolute inset-0 bg-black/40" on:click={closeModal} aria-label="Close dialog" type="button"></button>

      {#if modalType === 'level'}
        <LevelModal level={getLevelText()} on:close={closeModal} />
      {:else if modalType === 'trash'}
        <TrashModal trash={$studentData?.studentColtrash || 0} on:close={closeModal} />
      {:else if modalType === 'items'}
        <ItemsModal list={items} on:close={closeModal} />
      {:else if modalType === 'quiz1' || modalType === 'quiz2' || modalType === 'quiz3'}
        <!-- Inline Quiz modal: show grouped attempts -->
        <div class="bg-white rounded-2xl shadow-lg max-w-3xl w-full mx-4 p-6 overflow-auto">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold">{modalText}</h3>
            <button class="text-sm text-gray-600" on:click={closeModal} type="button">Close</button>
          </div>

          {#if !modalListData || modalListData.length === 0}
            <p class="text-gray-500">No attempts found for this quiz.</p>
          {:else}
            {#each modalListData as attemptGroup, index}
              <div class="mb-4 p-3 border rounded-lg">
                <button class="w-full text-left flex items-center justify-between" on:click={() => toggleAttempt(index)} type="button">
                  <div>
                    <div class="font-medium">{attemptGroup.storyTitle}</div>
                    <div class="text-sm text-gray-500">Attempt {attemptGroup.attempt} • {attemptGroup.items} questions</div>
                  </div>
                  <div class="text-sm text-gray-600">{attemptGroup.totalPoints} pts</div>
                </button>

                {#if openAttempts.has(index)}
                  <div class="mt-3 space-y-2">
                    {#each attemptGroup.rows as row, qi}
                      <div class="p-2 bg-gray-50 rounded" class:correct={(Number(row.point ?? row.score ?? 0) > 0)} class:incorrect={(Number(row.point ?? row.score ?? 0) === 0)}>
                        <div class="text-sm font-semibold">Q{qi + 1}: {row.question ?? row.storyTitle}</div>
                        <div class="text-sm text-gray-700">Your answer: <span class="font-medium">{row.selectedAnswer ?? row.studentAnswer ?? '—'}</span></div>
                        {#if String(row.selectedAnswer ?? row.studentAnswer ?? '').trim().toLowerCase() !== String(row.correctAnswer ?? row.correct_answer ?? '').trim().toLowerCase()}
                          <div class="text-sm text-red-600">Correct answer: <span class="font-medium">{row.correctAnswer ?? row.correct_answer ?? '—'}</span></div>
                        {/if}
                        <div class="text-sm text-gray-600">Points: {row.point ?? row.score ?? 0}</div>
                      </div>
                    {/each}
                  </div>
                {/if}
              </div>
            {/each}
          {/if}
        </div>
      {/if}
    </div>
  {/if}
</div>

<style>
  .hover\:scale-105:hover {
    transform: scale(1.05);
  }
  .transition-all {
    transition: all 0.3s ease;
  }
  .active\:border-2:active {
    border-width: 2px;
  }
  @keyframes bounce-slow {
    0%, 100% {
      transform: translateY(0);
    }
    50% {
      transform: translateY(-10px);
    }
  }
  .animate-bounce-slow {
    animation: bounce-slow 2s infinite;
  }
</style>