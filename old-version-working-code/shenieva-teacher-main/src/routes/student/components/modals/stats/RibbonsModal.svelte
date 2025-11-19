<!-- src/routes/student/components/modals/stats/RibbonsModal.svelte -->
<script lang="ts">
    import { fade, scale } from 'svelte/transition';
    import { createEventDispatcher } from 'svelte';
  
    export let count: number;
    export let list: { ribbonID: number; ribbonName: string; ribbonDescription: string; ribbonLocation: string }[];
  
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
      <h2 class="text-2xl font-bold text-orange-600 mb-4">🎖️ Ribbons</h2>
      <p class="text-lg text-gray-700">{count} Earned</p>
      {#if list.length > 0}
        <div class="mt-4 flex flex-wrap justify-center gap-4">
          {#each list as ribbon}
            <div class="flex flex-col items-center">
              <img src={ribbon.ribbonLocation} alt={ribbon.ribbonName} class="w-16 h-16 object-contain" />
              <p class="text-sm text-gray-700 mt-2">{ribbon.ribbonName}</p>
              <p class="text-xs text-gray-500">{ribbon.ribbonDescription}</p>
            </div>
          {/each}
        </div>
      {:else}
        <p class="text-sm text-gray-500 mt-2">No ribbons yet! Earn some by being awesome!</p>
      {/if}
      <button
        on:click={close}
        class="mt-6 px-4 py-2 bg-purple-600 text-white rounded-full font-bold hover:bg-purple-700 transition-all duration-300"
      >
        Close
      </button>
    </div>
  </div>