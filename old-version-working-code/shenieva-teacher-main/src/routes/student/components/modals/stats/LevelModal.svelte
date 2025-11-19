<!-- src/routes/student/components/modals/stats/LevelModal.svelte -->
<script lang="ts">
    import { fade, scale } from 'svelte/transition';
    import { createEventDispatcher } from 'svelte';
  
    export let level: string;
  
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
      <h2 class="text-2xl font-bold text-pink-600 mb-4">🏆 Level</h2>
      <p class="text-lg text-gray-700">You’re at {level}!</p>
      <p class="text-sm text-gray-500 mt-2">Keep exploring to level up!</p>
      <button
        on:click={close}
        class="mt-6 px-4 py-2 bg-purple-600 text-white rounded-full font-bold hover:bg-purple-700 transition-all duration-300"
      >
        Close
      </button>
    </div>
  </div>