<!-- src/routes/student/stats/+page.svelte -->
<script lang="ts">
  import { slide, scale } from 'svelte/transition';
  import { studentData } from '$lib/store/student_data';
  import { onMount } from 'svelte';
  import LevelModal from '../components/modals/stats/LevelModal.svelte';
  import ProgressModal from '../components/modals/stats/ProgressModal.svelte';
  import QuizzesModal from '../components/modals/stats/QuizzesModal.svelte';
  import TrashModal from '../components/modals/stats/TrashModal.svelte';
  import RibbonsModal from '../components/modals/stats/RibbonsModal.svelte';
  import ItemsModal from '../components/modals/stats/ItemsModal.svelte';

  // Reactive quiz scores (placeholder)
  let quizScores: { [key: number]: string } = { 1: 'Not Taken', 2: 'Not Taken', 3: 'Not Taken' };

  // Items and Ribbons fetched from the database
  type Item = { itemID: number; itemName: string; itemDescription: string; itemLocation: string };
  type Ribbon = { ribbonID: number; ribbonName: string; ribbonDescription: string; ribbonLocation: string };

  let items: Item[] = [];
  let ribbons: Ribbon[] = [];
  let isLoading = true;

  // Modal state
  let isModalOpen = false;
  let modalType: 'level' | 'progress' | 'quizzes' | 'trash' | 'ribbons' | 'items' | null = null;

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

  // Fetch ribbons
  async function fetchRibbons() {
    if (!$studentData?.pk_studentID) return;
    try {
      const response = await fetch(
        `http://localhost/shenieva-teacher/src/lib/api/get_student_ribbons.php?studentID=${$studentData.pk_studentID}`
      );
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      const data = await response.json();
      ribbons = data;
    } catch (error) {
      console.error('Error fetching ribbons:', error);
      ribbons = [];
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

  // Get level display text
  function getLevelText(): string {
    if (!$studentData) return 'Level 0 - Not Started';
    return `Level ${$studentData.studentLevel || 0}`;
  }

  // Open modal
  function openModal(type: typeof modalType) {
    modalType = type;
    isModalOpen = true;
  }

  // Close modal
  function closeModal() {
    isModalOpen = false;
    modalType = null;
  }

  onMount(async () => {
    if ($studentData) {
      await Promise.all([fetchItems(), fetchRibbons(), fetchQuizScores()]);
    }
    isLoading = false;
  });
</script>

<div
  in:slide={{ duration: 400, y: 20 }}
  class="w-full max-w-4xl mx-auto p-4 text-center bg-gradient-to-br from-blue-50 via-pink-50 to-yellow-50 rounded-3xl shadow-lg flex flex-col items-center"
>
  <!-- Header -->
  <h1
    class="text-4xl md:text-5xl font-extrabold text-purple-600 mb-[2vh] animate-bounce-slow"
    in:scale={{ duration: 600, start: 0.9 }}
    style="font-family: 'Comic Sans MS', 'Chalkboard', cursive;"
  >
    Your Super Stats! 🌟
  </h1>

  <!-- Stats Grid -->
  <div class="grid grid-cols-2 md:grid-cols-3 gap-4 w-full">
    <!-- Level Card -->
    <div
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-pink-500 active:bg-pink-50"
      in:scale={{ duration: 400, delay: 100 }}
      on:click={() => openModal('level')}
    >
      <h2 class="text-xl font-bold text-pink-600 mb-1 flex items-center justify-center">
        <span class="mr-2">🏆</span> Level
      </h2>
      <p class="text-sm font-semibold text-gray-700">{getLevelText()}</p>
    </div>

    <!-- Progress Card -->
    <div
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-lime-500 active:bg-lime-50"
      in:scale={{ duration: 400, delay: 200 }}
      on:click={() => openModal('progress')}
    >
      <h2 class="text-xl font-bold text-lime-600 mb-1 flex items-center justify-center">
        <span class="mr-2">🚀</span> Progress
      </h2>
      {#if $studentData && $studentData.studentProgress !== null}
        <div class="w-full bg-gray-200 rounded-full h-4 mb-1 overflow-hidden">
          <div
            class="bg-lime-400 h-full rounded-full transition-all duration-500"
            style="width: {$studentData.studentProgress}%;"
          ></div>
        </div>
        <p class="text-sm font-semibold text-gray-700">{$studentData.studentProgress}%</p>
      {:else}
        <p class="text-sm font-semibold text-gray-700">0%</p>
      {/if}
    </div>

    <!-- Quiz Scores Card -->
    <div
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-blue-500 active:bg-blue-50"
      in:scale={{ duration: 400, delay: 300 }}
      on:click={() => openModal('quizzes')}
    >
      <h2 class="text-xl font-bold text-blue-600 mb-1 flex items-center justify-center">
        <span class="mr-2">🧠</span> Quizzes
      </h2>
      <div class="text-sm font-semibold text-gray-700">
        <p>Quiz 1: {quizScores[1]}</p>
        <p>Quiz 2: {quizScores[2]}</p>
        <p>Quiz 3: {quizScores[3]}</p>
      </div>
    </div>

       <!-- Ribbons Card -->
       <div
       class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-orange-500 active:bg-orange-50"
       in:scale={{ duration: 400, delay: 500 }}
       on:click={() => openModal('ribbons')}
     >
       <h2 class="text-xl font-bold text-orange-600 mb-1 flex items-center justify-center">
         <span class="mr-2">🎖️</span> Ribbons
       </h2>
       <p class="text-sm font-semibold text-gray-700">
         {$studentData?.studentRibbon || 0} Earned
       </p>
       {#if ribbons.length > 0}
         <div class="mt-1 flex flex-wrap justify-center gap-2">
           {#each ribbons as ribbon}
             <div class="flex flex-col items-center">
               <img src={ribbon.ribbonLocation} alt={ribbon.ribbonName} class="w-12 h-12 object-contain" />
               <span class="text-xs text-gray-600 mt-1">{ribbon.ribbonName}</span>
             </div>
           {/each}
         </div>
       {:else}
         <p class="text-xs text-gray-600 mt-1">No ribbons yet!</p>
       {/if}
     </div>
 
    <!-- Trash Card -->
    <div
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-teal-500 active:bg-teal-50"
      in:scale={{ duration: 400, delay: 400 }}
      on:click={() => openModal('trash')}
    >
      <h2 class="text-xl font-bold text-teal-600 mb-1 flex items-center justify-center">
        <span class="mr-2">🗑️</span> Trash Collected
      </h2>
      <p class="text-sm font-semibold text-gray-700">
        {$studentData?.studentColtrash || 0} Pieces
      </p>
    </div>

 
    <!-- Items Card -->
    <div
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-cyan-500 active:bg-cyan-50"
      in:scale={{ duration: 400, delay: 600 }}
      on:click={() => openModal('items')}
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
              <img src={item.itemLocation} alt={item.itemName} class="w-12 h-12 object-contain" />
              <span class="text-xs text-gray-600 mt-1">{item.itemName}</span>
            </div>
          {/each}
        </div>
      {:else}
        <p class="text-xs text-gray-600 mt-1">No items yet!</p>
      {/if}
    </div>
  </div>

  <!-- Modals -->
  {#if isModalOpen}
    {#if modalType === 'level'}
      <LevelModal level={getLevelText()} on:close={closeModal} />
    {:else if modalType === 'progress'}
      <ProgressModal progress={$studentData?.studentProgress || 0} on:close={closeModal} />
    {:else if modalType === 'quizzes'}
      <QuizzesModal scores={quizScores} on:close={closeModal} />
    {:else if modalType === 'trash'}
      <TrashModal trash={$studentData?.studentColtrash || 0} on:close={closeModal} />
    {:else if modalType === 'ribbons'}
      <RibbonsModal count={$studentData?.studentRibbon || 0} list={ribbons} on:close={closeModal} />
    {:else if modalType === 'items'}
      <ItemsModal list={items} on:close={closeModal} />
    {/if}
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