<script lang="ts">
  import Store3Monitor from './Store3Monitor.svelte';

  let activeTab = 'all';
  let storyTitle = "All stories";

  const stories = [
    { key: 'story3-1', title: "Tonya's Tooth", label: "🦷 Tonya's Tooth" },
    { key: 'story3-2', title: "Lola Gloria's Flowerpot", label: "🌼 Lola Gloria's Flowerpot" },
    { key: 'story3-3', title: "Liloy and Lingling the Dog", label: "🐶 Liloy & Lingling the Dog" }
  ];

  import sanitizeForDisplay from '$lib/utils/sanitize';

  function setActiveTab(tab: string, title: string) {
    activeTab = tab;
    storyTitle = sanitizeForDisplay(title) ?? title;
  }
</script>

<div class="max-w-6xl mx-auto p-4">
  <div class="mb-4">
    <label class="text-sm font-medium text-gray-700 mr-2">Filter by story</label>
    <select
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
      class="px-3 py-2 border rounded-md"
    >
      <option value="all">📚 All stories</option>
      {#each stories as s}
        <option value={s.key}>{s.label}</option>
      {/each}
    </select>
  </div>

  <Store3Monitor {storyTitle} storyKey={activeTab} />
</div>
