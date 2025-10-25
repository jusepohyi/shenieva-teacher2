<script>
  import { slide, fade, fly } from 'svelte/transition';
  import { goto } from '$app/navigation';
  import { studentData } from '$lib/store/student_data';

  // auto-subscribe to the store
  $: ribbons = Number($studentData?.studentRibbon ?? 0);

  function goToReadville() {
    goto('/student/village');
  }

  let showStatsModal = false;
  let modalTitle = '';
  let modalContent = '';

  function openStat(name) {
    modalTitle = name;
    const student = $studentData || {};
    modalChartData = null;
    switch (name) {
      case 'Level':
        modalText = `Current level: ${student.studentLevel ?? 0}`;
        break;
      case 'Collected Trash':
        modalText = `Trash collected: ${student.studentColtrash ?? 0}`;
        break;
      case 'Profile':
        modalText = `Name: ${student.studentName ?? 'Unknown'}\nID: ${student.idNo ?? '-'}\nGender: ${student.studentGender ?? '-'} `;
        break;
      case 'Quiz 1':
      case 'Quiz 2':
      case 'Quiz 3':
        // Fetch quiz results from server and compute a simple score percentage
        fetchQuizData(name, student.pk_studentID);
        break;
      default:
        modalText = '';
    }

    showStatsModal = true;
  }

  function closeStat() {
    showStatsModal = false;
  }

  let modalText = '';
  let modalListData = null; // array of {storyTitle, attempt, totalPoints, items, createdAt}

  async function fetchQuizData(name, studentPK) {
    if (!studentPK) {
      modalText = 'Student not signed in.';
      return;
    }

    let url;
    if (name === 'Quiz 1') url = 'http://localhost/shenieva-teacher/src/lib/api/get_level1_quiz_results.php';
    if (name === 'Quiz 2') url = 'http://localhost/shenieva-teacher/src/lib/api/get_level2_quiz_results.php';
    if (name === 'Quiz 3') url = 'http://localhost/shenieva-teacher/src/lib/api/get_level3_quiz_results.php';

    try {
      const res = await fetch(url);
      const data = await res.json();
      if (!data.success) {
        modalText = 'Failed to load quiz data.';
        return;
      }

      // Filter rows for the current student
      const rows = (data.data || []).filter(r => Number(r.studentID) === Number(studentPK));
      if (rows.length === 0) {
        modalText = 'Not played yet.';
        modalListData = [];
        return;
      }

      const groups = {};
      rows.forEach(r => {
        const attempt = String(r.attempt ?? '1');
        const story = r.storyTitle ?? r.story_title ?? 'Story';
        const key = `${story}::${attempt}`;
        const p = Number(r.score ?? r.point ?? 0) || 0;
        if (!groups[key]) {
          groups[key] = { storyTitle: story, attempt: Number(attempt), totalPoints: 0, items: 0, createdAt: r.createdAt };
        }
        groups[key].totalPoints += p;
        groups[key].items += 1;
        if (r.createdAt && (!groups[key].createdAt || groups[key].createdAt < r.createdAt)) groups[key].createdAt = r.createdAt;
      });

      const attempts = Object.values(groups).sort((a, b) => b.attempt - a.attempt || (b.createdAt || '').localeCompare(a.createdAt || ''));
      modalListData = attempts;
      modalText = `${attempts.length} attempt${attempts.length !== 1 ? 's' : ''}`;
    } catch (e) {
      console.error('Quiz fetch error', e);
      modalText = 'Error fetching data.';
    }
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
            <img src="/src/assets/ribbon-gif.gif" alt="Ribbon" class="w-full h-full object-contain" />
          </div>
        </div>

        <div class="flex-1">
          {#if ribbons <= 0}
            <h2 class="text-2xl font-semibold text-gray-800">No ribbons yet</h2>
            <p class="text-gray-600 mt-1">Start by playing stories in Readville Village. Finish a story to earn your first ribbon.</p>
            <div class="mt-4">
              <button class="bg-lime-500 text-white px-4 py-2 rounded-md mr-2" on:click={goToReadville}>Go to Readville</button>
              <button class="px-4 py-2 border rounded-md" on:click={() => window.scrollTo({ top: 0, behavior: 'smooth' })}>Explore</button>
            </div>
          {:else}
            <h2 class="text-2xl font-semibold text-gray-800">Great job!</h2>
            <p class="text-gray-700 mt-1">You’ve earned <strong>{ribbons}</strong> ribbon{ribbons !== 1 ? 's' : ''} — keep completing stories to collect more.</p>
            <div class="mt-4">
              <button class="bg-lime-500 text-white px-4 py-2 rounded-md" on:click={goToReadville}>Play more stories</button>
            </div>
          {/if}
        </div>
      </div>
    </div>
  </div>

  <!-- Stats Cards area (kid-friendly) -->
  <div class="w-full max-w-3xl px-4 mt-8">
    <div class="stats-grid" role="list">
      {#each ['Level','Quiz 1','Quiz 2','Quiz 3','Collected Trash','Profile'] as stat}
        <button class="stat-card" on:click={() => openStat(stat)} aria-label={`Open ${stat}`}>
          <div class="stat-emoji">{stat === 'Level' ? '🏆' : stat.startsWith('Quiz') ? '📝' : stat === 'Collected Trash' ? '🗑️' : '👤'}</div>
          <div class="stat-title">{stat}</div>
          <div class="stat-value">
            {#if stat === 'Level'}
              {$studentData?.studentLevel ?? 0}
            {:else if stat === 'Collected Trash'}
              {$studentData?.studentColtrash ?? 0}
            {:else if stat === 'Profile'}
              {$studentData?.studentName ?? '—'}
            {:else if stat === 'Quiz 1'}
              {$studentData?.quiz1Score ?? '—'}
            {:else if stat === 'Quiz 2'}
              {$studentData?.quiz2Score ?? '—'}
            {:else if stat === 'Quiz 3'}
              {$studentData?.quiz3Score ?? '—'}
            {/if}
          </div>
        </button>
      {/each}
    </div>
  </div>

  {#if showStatsModal}
    <div class="modal-overlay" role="button" tabindex="0" aria-label="Close dialog" on:click={closeStat} on:keydown={(e) => (e.key === 'Enter' || e.key === ' ' ) && closeStat()}></div>
    <div class="stat-modal" in:fly={{ y: 20 }} out:fade={{ duration: 180 }} role="dialog" aria-modal="true">
      <button class="modal-close" on:click={closeStat} aria-label="Close">✕</button>
      <h3 class="modal-title">{modalTitle}</h3>
      {#if modalListData}
        <div class="attempts-list">
          {#each modalListData as a}
            <div class="attempt-card">
              <div class="attempt-header">
                <div class="attempt-story">{a.storyTitle}</div>
                <div class="attempt-meta">Attempt {a.attempt} • {a.items} items</div>
              </div>
              <div class="attempt-body">Points: <strong>{a.totalPoints}</strong></div>
              <div class="attempt-footer">{a.createdAt}</div>
            </div>
          {/each}
        </div>
      {:else}
        <pre class="modal-content">{modalText}</pre>
      {/if}
      <div class="modal-actions">
        <button class="modal-ok" on:click={closeStat}>OK</button>
      </div>
    </div>
  {/if}
</div>

<style>
  /* small, screen-optimized styles matching Home layout */
  @media (max-width: 640px) {
    img[alt="Ribbon"] { display: none; }
  }

  /* Stats grid and card styles */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    max-height: 60vh; /* make card area scrollable on small screens */
    overflow-y: auto;
    padding: 8px;
  }

  .stat-card {
    background: linear-gradient(180deg, #ffffff, #f8ffef);
    border-radius: 18px;
    padding: 18px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    border: 3px solid rgba(16,185,129,0.06);
    font-size: 1.15rem;
    cursor: pointer;
    min-height: 140px; /* large cards for kids */
  }

  .stat-card:active { transform: translateY(2px); }

  .stat-emoji { font-size: 40px; }
  .stat-title { font-weight: 700; color: #166534; }
  .stat-value { font-size: 1.25rem; color: #0f172a; font-weight: 800; }

  /* Modal styles */
  .modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.25);
    z-index: 60;
  }

  .stat-modal {
    position: fixed;
    left: 50%; top: 50%; transform: translate(-50%,-50%);
    width: min(560px, 92%);
    background: linear-gradient(180deg,#fff,#f7fff4);
    border-radius: 16px;
    padding: 20px;
    z-index: 70;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    text-align: center;
  }

  .modal-close { position: absolute; right: 12px; top: 10px; background: transparent; border: none; font-size: 20px; cursor: pointer; }
  .modal-title { font-size: 1.6rem; color: #14532d; margin-bottom: 12px; }
  .modal-content { text-align: left; background: #fff; padding: 12px; border-radius: 8px; white-space: pre-wrap; }
  .modal-actions { margin-top: 16px; }
  .modal-ok { background: #16a34a; color: white; padding: 8px 18px; border-radius: 10px; border: none; cursor: pointer; font-weight: 700; }

  @media (max-width: 640px) {
    .stats-grid { grid-template-columns: 1fr; max-height: 65vh; }
    .stat-card { min-height: 160px; }
  }

  /* Chart styles */
  .chart-wrapper { padding: 12px 6px; }
  .chart-label { font-weight: 700; color: #065f46; margin-bottom: 8px; }
  .chart-bar-bg { width: 100%; height: 28px; background: #e6f4ea; border-radius: 14px; overflow: hidden; }
  .chart-bar-fill { height: 100%; background: linear-gradient(90deg,#34d399,#10b981); width: 0%; border-radius: 14px; transition: width 900ms cubic-bezier(.2,.9,.2,1); }
  .chart-stats { margin-top: 8px; font-weight: 700; color: #064e3b; }

  /* Attempts list styles */
  .attempts-list { display: flex; flex-direction: column; gap: 12px; max-height: 50vh; overflow-y: auto; padding: 6px; }
  .attempt-card { background: #fff; border-radius: 10px; padding: 10px; box-shadow: 0 8px 18px rgba(0,0,0,0.08); text-align: left; }
  .attempt-header { display:flex; justify-content:space-between; align-items:center; gap:8px; }
  .attempt-story { font-weight:800; color:#065f46; }
  .attempt-meta { font-size:0.9rem; color:#0f172a; opacity:0.8 }
  .attempt-body { margin-top:8px; font-weight:700 }
  .attempt-footer { margin-top:6px; font-size:0.85rem; color:#374151 }
</style>