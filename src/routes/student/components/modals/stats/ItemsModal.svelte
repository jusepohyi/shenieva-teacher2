<!-- src/routes/student/components/modals/stats/ItemsModal.svelte -->
<script lang="ts">
    import { fade, scale } from 'svelte/transition';
    import { createEventDispatcher } from 'svelte';
  
  export let list: { itemID?: number; itemName: string; itemDescription?: string; itemLocation?: string; fileCandidates?: string[]; _tryIndex?: number }[];
  
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
      <h2 class="text-2xl font-bold text-cyan-600 mb-4">🎁 Gifts</h2>
      <p class="text-lg text-gray-700">{list.length} Collected</p>
      {#if list.length > 0}
        <div class="mt-4 flex flex-wrap justify-center gap-4">
          {#each list as item}
            <div class="flex flex-col items-center">
              <img
                src={item.itemLocation}
                alt={item.itemName}
                class="w-16 h-16 object-contain"
                on:error={(e) => {
                  const img = /** @type {HTMLImageElement | null} */ (e?.target ?? null);
                  try {
                    if (!img) return;
                    if (item._tryIndex === undefined) item._tryIndex = 0;
                    item._tryIndex++;
                    const next = item.fileCandidates && item.fileCandidates[item._tryIndex];
                    if (next) {
                      img.src = `/src/assets/Level_Walkthrough/gift/gifts/${next}.png`;
                    } else {
                      img.src = `/src/assets/Level_Walkthrough/gift/gifts/${encodeURIComponent(item.itemName)}.png`;
                    }
                  } catch (err) {
                    console.warn('Gift modal image error handler failed', err);
                  }
                }}
              />
              <p class="text-sm text-gray-700 mt-2">{item.itemName}</p>
              <p class="text-xs text-gray-500">{item.itemDescription}</p>
            </div>
          {/each}
        </div>
      {:else}
        <p class="text-sm text-gray-500 mt-2">No items yet! Keep exploring!</p>
      {/if}
      <button
        on:click={close}
        class="mt-6 px-4 py-2 bg-purple-600 text-white rounded-full font-bold hover:bg-purple-700 transition-all duration-300"
      >
        Close
      </button>
    </div>
  </div>