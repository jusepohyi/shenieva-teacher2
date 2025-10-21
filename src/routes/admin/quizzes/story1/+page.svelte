<script lang="ts">
  import Store1Monitor from '../story1/Store1Monitor.svelte';

  let activeTab = 'all';
  let storyTitle = "All stories";

  const stories = [
    { key: 'story1-1', title: "Maria's Promise", label: "📖 Maria's Promise" },
    { key: 'story1-2', title: 'Candice and Candies', label: "🍬 Candice and Candies" },
    { key: 'story1-3', title: 'Hannah, the Honest Vendor', label: "🏪 Hannah, the Honest Vendor" }
  ];

  import sanitizeForDisplay from '$lib/utils/sanitize';

  function setActiveTab(tab: string, title: string) {
    activeTab = tab;
    storyTitle = sanitizeForDisplay(title) ?? title;
  }
</script>

<div class="max-w-6xl mx-auto p-4">
  <!-- Tabs -->
  <div class="mb-4">
    <label class="text-sm font-medium text-gray-700 mr-2">Filter by story</label>
    <select
      bind:value={activeTab}
      on:change={(e) => {
          const key = (e.target as HTMLSelectElement).value;
          if (key === 'all') {
            setActiveTab('all', '');
          } else {
            const s = stories.find(x => x.key === key);
            setActiveTab(key, s ? s.title : '');
          }
        }}
      class="px-4 py-2 border rounded-lg hover:shadow-sm focus:ring-2 focus:ring-lime-400 transition"
    >
      <option value="all">📚 All stories</option>
      {#each stories as s}
        <option value={s.key}>{s.label}</option>
      {/each}
    </select>
  </div>

  <!-- Tab Content -->
  <Store1Monitor {storyTitle} storyKey={activeTab} />
</div>