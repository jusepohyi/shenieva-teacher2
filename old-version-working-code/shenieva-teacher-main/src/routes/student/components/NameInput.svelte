<!-- src/routes/student/components/NameInput.svelte -->
<script lang="ts">
    import { fly } from 'svelte/transition';
    import { name } from "$lib/store/nameStore.js";
    import { createEventDispatcher } from "svelte";
  
    const dispatch = createEventDispatcher();
  
    function submitName() {
      if ($name.trim()) {
        dispatch("submit", { name: $name }); // Send object with the name value
      }
    }
  </script>
  
  <div class="bg-yellow-100 p-8 rounded-3xl shadow-lg w-full max-w-md border-4 border-orange-400" in:fly={{ y: -50, opacity: 0 }} out:fly={{ y: 50, opacity: 0 }}>
    <h1 class="text-3xl font-bold text-orange-600 mb-6 text-center" style="font-family: 'Comic Sans MS', 'Chalkboard', cursive;">
      What is Your Name? 🌟
    </h1>
    <input
      type="text"
      bind:value={$name}
      placeholder="Type your name here!"
      class="w-full p-4 rounded-xl border-2 border-orange-500 text-lg text-gray-800 focus:outline-none focus:border-orange-500"
      style="font-family: 'Comic Sans MS', 'Chalkboard', cursive;"
    />
    <button
      on:click={submitName}
      disabled={!$name.trim()}
      class="mt-6 w-full bg-orange-500 text-white p-4 rounded-xl text-xl font-bold hover:bg-orange-600 disabled:bg-gray-400"
      style="font-family: 'Comic Sans MS', 'Chalkboard', cursive;"
    >
      Next! 🚀
    </button>
  </div>
  