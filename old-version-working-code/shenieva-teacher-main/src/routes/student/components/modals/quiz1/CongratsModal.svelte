<script lang="ts">
  import { createEventDispatcher } from 'svelte';

  export let ribbons: number;

  const dispatch = createEventDispatcher();

  function handleProceed() {
    dispatch('proceed');
  }
</script>

<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
  <div class="bg-white rounded-2xl p-8 max-w-lg w-full shadow-xl text-center">
    <h2 class="text-3xl font-bold text-purple-600 mb-4">Congratulations!</h2>
    <p class="text-xl font-semibold text-gray-900 mb-6">
      {#if ribbons > 0}
        You earned {ribbons} ribbon{ribbons !== 1 ? 's' : ''}!
      {:else}
        No ribbons earned this time. Keep trying!
      {/if}
    </p>
    <button
      on:click={handleProceed}
      class="bg-gradient-to-r from-blue-400 to-purple-500 text-white font-bold px-6 py-3 rounded-full hover:scale-105 transition-transform"
    >
      Proceed
    </button>
  </div>
</div>