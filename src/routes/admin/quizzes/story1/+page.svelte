<script lang="ts">
  import Store1Monitor from '../story1/Store1Monitor.svelte';

  let activeTab = 'store1';
  let storyTitle = "Maria's Promise";

  function setActiveTab(tab: string, title: string) {
    activeTab = tab;
    storyTitle = title;
  }
</script>

<div class="max-w-6xl mx-auto p-4">
  <!-- Tabs -->
  <div class="flex space-x-2 mb-4">
    <button
      on:click={() => setActiveTab('store1', "Maria's Promise")}
      class:bg-lime-500={activeTab === 'store1'}
      class:text-white={activeTab === 'store1'}
      class:bg-gray-100={activeTab !== 'store1'}
      class:text-gray-700={activeTab !== 'store1'}
      class="px-4 py-2 rounded-lg font-semibold transition-all hover:bg-lime-400"
    >
      📖 Maria's Promise
    </button>
    <button
      on:click={() => setActiveTab('store2', 'Candice and Candies')}
      class:bg-lime-500={activeTab === 'store2'}
      class:text-white={activeTab === 'store2'}
      class:bg-gray-100={activeTab !== 'store2'}
      class:text-gray-700={activeTab !== 'store2'}
      class="px-4 py-2 rounded-lg font-semibold transition-all hover:bg-lime-400"
    >
      🍬 Candice and Candies
    </button>
    <button
      on:click={() => setActiveTab('store3', 'Hannah, the Honest Vendor')}
      class:bg-lime-500={activeTab === 'store3'}
      class:text-white={activeTab === 'store3'}
      class:bg-gray-100={activeTab !== 'store3'}
      class:text-gray-700={activeTab !== 'store3'}
      class="px-4 py-2 rounded-lg font-semibold transition-all hover:bg-lime-400"
    >
      🏪 Hannah, the Honest Vendor
    </button>
  </div>

  <!-- Tab Content -->
  <Store1Monitor {storyTitle} />
</div>