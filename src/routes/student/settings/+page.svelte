<script>
  import { slide } from 'svelte/transition';
  import { goto } from '$app/navigation';
  import { studentData } from '$lib/store/student_data';

  // auto-subscribe to the store
  $: ribbons = Number($studentData?.studentRibbon ?? 0);

  function goToReadville() {
    goto('/student/village');
  }
</script>

<div in:slide={{ duration: 350 }} class="min-h-screen flex flex-col items-center justify-start pt-8 bg-gray-50 pb-12">
  <h1 class="text-3xl font-bold text-lime-700 mb-2">Your Ribbons</h1>
  <p class="text-sm text-gray-600 mb-6 text-center max-w-xl">Collect ribbons by completing stories in Readville. Ribbons unlock small rewards in the village.</p>

  <div class="w-full max-w-3xl px-4">
    <div class="relative bg-white rounded-xl shadow-md p-6 overflow-hidden">
    
      <div class="relative z-10 flex flex-col md:flex-row items-center gap-6">
        <div class="flex-shrink-0 w-full md:w-48 text-center">
          <div class="w-36 h-36 mx-auto rounded-lg flex items-center justify-center bg-lime-50 shadow-inner overflow-hidden">
            <!-- Use the ribbon GIF asset instead of emoji -->
            <img src="/converted/assets/ribbon-gif.webp" alt="Ribbon" class="w-full h-full object-contain" />
          </div>
        </div>

        <div class="flex-1">
          {#if ribbons <= 0}
            <h2 class="text-2xl font-semibold text-gray-800">No ribbons yet</h2>
            <p class="text-gray-600 mt-1">Start by playing stories in Readville Village. Finish a story to earn your first ribbon.</p>
            <div class="mt-4">
              <button class="bg-lime-500 text-white px-4 py-2 rounded-md hover:bg-lime-600 transition-colors" on:click={goToReadville}>Go to Readville</button>
            </div>
          {:else}
            <h2 class="text-2xl font-semibold text-gray-800">Great job!</h2>
            <p class="text-gray-700 mt-1">You've earned <strong>{ribbons}</strong> ribbon{ribbons !== 1 ? 's' : ''} — keep completing stories to collect more.</p>
            <div class="mt-4">
              <button class="bg-lime-500 text-white px-4 py-2 rounded-md hover:bg-lime-600 transition-colors" on:click={goToReadville}>Play more stories</button>
            </div>
          {/if}
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  /* small, screen-optimized styles matching Home layout */
  @media (max-width: 640px) {
    img[alt="Ribbon"] { display: none; }
  }
</style>