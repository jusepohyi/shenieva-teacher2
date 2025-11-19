<!-- src/routes/student/components/modals/stats/ProgressModal.svelte -->
<script lang="ts">
    import { fade, scale } from 'svelte/transition';
    import { createEventDispatcher } from 'svelte';
  
    export let progress: number;
  
    const dispatch = createEventDispatcher();
  
    function close() {
      dispatch('close');
    }
  </script>
  
  <div
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
    transition:fade={{ duration: 200 }}
    on:click|self={close}
  >
    <div
      class="bg-white p-6 rounded-2xl shadow-lg max-w-md w-full text-center"
      transition:scale={{ duration: 300, start: 0.9 }}
    >
      <h2 class="text-2xl font-bold text-lime-600 mb-4">🚀 Progress</h2>
      <div class="w-full bg-gray-200 rounded-full h-6 mb-4 overflow-hidden">
        <div
          class="bg-lime-400 h-full rounded-full transition-all duration-500"
          style="width: {progress}%;"
        ></div>
      </div>
      <p class="text-lg text-gray-700">{progress}% Complete</p>
      <p class="text-sm text-gray-500 mt-2">You’re doing awesome!</p>
      <button
        on:click={close}
        class="mt-6 px-4 py-2 bg-purple-600 text-white rounded-full font-bold hover:bg-purple-700 transition-all duration-300"
      >
        Close
      </button>
    </div>
  </div>