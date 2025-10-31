<script lang="ts">
  import Store2Monitor from './Store2Monitor.svelte';

  let activeTab = 'all';
  let storyTitle = "All stories";

  const stories = [
    { key: 'story2-1', title: "Hector's Health", label: "🩺 Hector's Health" },
    { key: 'story2-2', title: 'Helpful Maya', label: "🤝 Helpful Maya" },
    { key: 'story2-3', title: "Royce's Choice", label: "🧭 Royce's Choice" }
  ];

  import sanitizeForDisplay from '$lib/utils/sanitize';

  function setActiveTab(tab: string, title: string) {
    activeTab = tab;
    storyTitle = sanitizeForDisplay(title) ?? title;
  }
</script>

<div class="max-w-6xl mx-auto p-4">
  <div class="mb-4">
    <label for="story-filter-2" class="text-sm font-medium text-gray-700 mr-2">Filter by story</label>
    <select id="story-filter-2"
      bind:value={activeTab}
      on:change={(e) => {
        const key = (e.target as HTMLSelectElement).value;
        if (key === 'all') {
          setActiveTab('all','');
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

  <Store2Monitor {storyTitle} storyKey={activeTab} />
</div>
