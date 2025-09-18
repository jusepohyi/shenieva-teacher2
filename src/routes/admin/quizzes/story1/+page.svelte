<script lang="ts">
  import Store1Quiz from '../story1/Store1Quiz.svelte';
  import Store2Quiz from '../story1/Store2Quiz.svelte';
  import Store3Quiz from '../story1/Store3Quiz.svelte';

  let activeTab = 'store1';

  function setActiveTab(tab: string) {
    activeTab = tab;
  }
</script>

<div class="text-gray-500 font-bold text-2xl pl-10">
  <h1>Quiz Management/Story1</h1>
</div>

<div class="max-w-6xl mx-auto p-6">
  <!-- Tabs -->
  <div class="flex space-x-4 mb-6">
    <button
      on:click={() => setActiveTab('store1')}
      class:bg-orange-500={activeTab === 'store1'}
      class:text-white={activeTab === 'store1'}
      class:bg-gray-200={activeTab !== 'store1'}
      class="px-4 py-2 rounded-lg font-semibold transition"
    >
      Maria's Promise
    </button>
    <button
      on:click={() => setActiveTab('store2')}
      class:bg-orange-500={activeTab === 'store2'}
      class:text-white={activeTab === 'store2'}
      class:bg-gray-200={activeTab !== 'store2'}
      class="px-4 py-2 rounded-lg font-semibold transition"
    >
      Candice and Candies
    </button>
    <button
      on:click={() => setActiveTab('store3')}
      class:bg-orange-500={activeTab === 'store3'}
      class:text-white={activeTab === 'store3'}
      class:bg-gray-200={activeTab !== 'store3'}
      class="px-4 py-2 rounded-lg font-semibold transition"
    >
      Hannah, the Honest VendorS
    </button>
  </div>

  <!-- Tab Content -->
  {#if activeTab === 'store1'}
    <Store1Quiz />
  {:else if activeTab === 'store2'}
    <Store2Quiz />
  {:else if activeTab === 'store3'}
    <Store3Quiz />
  {/if}
</div>