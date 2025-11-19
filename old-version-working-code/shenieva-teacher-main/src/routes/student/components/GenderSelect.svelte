<!-- src/routes/student/components/GenderSelect.svelte -->
<script lang="ts">
    import { fly } from 'svelte/transition';
    import { createEventDispatcher } from 'svelte';
    import { onMount } from "svelte";

    let showContent = false;

onMount(() => {
  setTimeout(() => {
    showContent = true;
  }, 15000); // Delay for 10 seconds
});
  
    const dispatch = createEventDispatcher();
  
    function selectGender(gender: 'boy' | 'girl') {
      dispatch('select', gender);
    }
  </script>
  
  <div class="bg-yellow-100 p-8 rounded-3xl shadow-lg w-full max-w-md border-4 border-amber-500" in:fly={{ y: -50, opacity: 0 }} out:fly={{ y: -50, opacity: 0 }}>
    <h1 class="text-3xl font-bold text-amber-600 mb-6 text-center" style="font-family: 'Comic Sans MS', 'Chalkboard', cursive;">
      Are You a Boy or a Girl? 😊
    </h1>
    <div class="flex justify-center gap-6">
      <button
        on:click={() => selectGender('boy')}
        class="bg-blue-500 text-white p-6 rounded-2xl shadow-md hover:bg-blue-600 flex flex-col items-center w-32"
        style="font-family: 'Comic Sans MS', 'Chalkboard', cursive;"
      >
        <span class="text-4xl mb-2">👦</span>
        <span class="text-xl font-bold">Boy</span>
      </button>
      <button
        on:click={() => selectGender('girl')}
        class="bg-pink-500 text-white p-6 rounded-2xl shadow-md hover:bg-pink-600 flex flex-col items-center w-32"
        style="font-family: 'Comic Sans MS', 'Chalkboard', cursive;"
      >
        <span class="text-4xl mb-2">👧</span>
        <span class="text-xl font-bold">Girl</span>
      </button>
    </div>
  </div>