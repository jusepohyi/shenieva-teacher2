<!-- src/routes/student/stats/+page.svelte -->
<script>
  import { slide } from 'svelte/transition';
  import { studentData } from '$lib/store/student_data';
  import { onMount, onDestroy, tick } from 'svelte';
  import LevelModal from '../components/modals/stats/LevelModal.svelte';
  import TrashModal from '../components/modals/stats/TrashModal.svelte';
  // Student details modal is rendered inline below; ItemsModal removed from Stats view
  // For Stats we show a read-only viewer instead of using the quiz result modals
  // (we purposely do not import QuizResultModal* here so the Proceed/Retake actions
  // from those modals won't appear inside Stats). This view is monitoring-only.

  // Reactive quiz scores (placeholder)
  let quizScores = { 1: 'Not Taken', 2: 'Not Taken', 3: 'Not Taken' };

  // Items fetched from the database (kept for compatibility but not shown in Stats)
  /** @type {Array<any>} */
  let items = [];
  let isLoading = true;
  // Student details fetched from the database
  let studentDetails = null;
  // Display student (prefer DB-fetched details, fall back to store)
  $: displayStudent = studentDetails ?? $studentData;

  // Simplified Student Details UI for kids: no avatar image, just emoji/icon.

  // Modal state
  /** @type {boolean} */
  let isModalOpen = false;
  /** @type {string | null} */
  let modalType = null;
  /** @type {Array<any> | null} */
  let modalListData = null;
  /** @type {string | null} */
  let modalListType = 'attempts';
  // cache for attempts fetched at page open so Stats always queries DB on load
  let quizAttemptsCache = { 1: [], 2: [], 3: [] };
  /** @type {string} */
  let modalText = '';
  // Quiz result viewer state
  let showQuizViewer = false;
  let quizViewerLevel = 1;
  let quizViewerData = null; // will hold randomizedQuizData
  /** @type {Array<any> | null} */
  let quizViewerSelectedChoices = null;
  let quizViewerScore = 0;
  let quizViewerTotalPoints = 0;
  let quizViewerAttempt = 1;
  let quizViewerStoryTitle = '';
  let quizViewerPercent = 0;
  $: quizViewerPercent = quizViewerTotalPoints ? Math.round((quizViewerScore / quizViewerTotalPoints) * 100) : 0;
  const QUIZ_MAX_TAKES = 3;
  // track which attempt groups are open in modal
  /** @type {Set<number>} */
  let openAttempts = new Set();

  /**
   * Toggle an attempt open/closed
   * @param {number} index
   */
  function toggleAttempt(index) {
    if (openAttempts.has(index)) openAttempts.delete(index);
    else openAttempts.add(index);
    // trigger reactivity by creating a new Set reference
    openAttempts = new Set(openAttempts);
  }

  /**
   * @param {any} ts
   */
  function formatDate(ts) {
    try {
      if (!ts) return '';
      const d = new Date(ts);
      return d.toLocaleString();
    } catch (e) {
      return String(ts);
    }
  }

  // Fetch items
  async function fetchItems() {
    if (!$studentData?.pk_studentID) return;
    try {
      // Fetch purchased gifts for this student from the server-side gifts_table
      const url = `http://localhost/shenieva-teacher/src/lib/api/get_student_gifts.php?studentID=${encodeURIComponent($studentData.pk_studentID)}`;
      const response = await fetch(url);
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      const res = await response.json();
      if (!res.success) {
        console.error('Gifts API error:', res.error ?? res);
        items = [];
        return;
      }
      // Map DB rows to the item shape used by the UI. Create multiple filename
      // candidates to tolerate different naming conventions (spaces, case, hyphens).
      items = (res.data || []).map(r => {
        const raw = String(r.gift ?? '').trim();
        const candidates = [];
        if (raw.length > 0) {
          // Original (encoded)
          candidates.push(encodeURIComponent(raw));
          // Lowercase
          candidates.push(encodeURIComponent(raw.toLowerCase()));
          // Underscore-separated
          candidates.push(encodeURIComponent(raw.replace(/\s+/g, '_').toLowerCase()));
          // Hyphen-separated
          candidates.push(encodeURIComponent(raw.replace(/\s+/g, '-').toLowerCase()));
          // Alphanumeric only
          candidates.push(encodeURIComponent(raw.replace(/[^a-z0-9_-]/ig, '').toLowerCase()));
        }
        // Deduplicate while preserving order
        const unique = Array.from(new Set(candidates)).filter(Boolean);
        return {
          itemName: raw || 'gift',
          giftID: r.giftID ?? null,
          fileCandidates: unique,
          // Primary path to try first
          itemLocation: unique.length ? `/src/assets/Level_Walkthrough/gift/gifts/${unique[0]}.png` : `/src/assets/Level_Walkthrough/gift/gifts/${encodeURIComponent(raw)}.png`
        };
      });
    } catch (error) {
      console.error('Error fetching gifts:', error);
      items = [];
    }
  }

  // Fetch student details from server (students_table)
  async function fetchStudentDetails() {
    try {
      const id = $studentData?.pk_studentID ?? $studentData?.idNo ?? null;
      if (!id) return;
      const url = `http://localhost/shenieva-teacher/src/lib/api/get_student_details.php?studentID=${encodeURIComponent($studentData.pk_studentID)}`;
      const res = await fetch(url);
      if (!res.ok) throw new Error('Failed to fetch student details');
      const data = await res.json();
      if (data.success) {
        studentDetails = data.data || null;
      } else {
        console.warn('Student details API returned error', data.error);
        studentDetails = null;
      }
    } catch (err) {
      console.error('Error fetching student details', err);
      studentDetails = null;
    }
  }


  // Fetch quiz scores (placeholder)
  async function fetchQuizScores() {
    if (!$studentData?.pk_studentID) return;
    try {
      const lvl = Number($studentData?.studentLevel ?? 0);
      // If the student's level is >= N we consider the corresponding quiz as TAKEN.
      // Quizzes above the student's current level are shown as Locked.
      quizScores = {
        1: lvl >= 1 ? 'Taken' : 'Locked',
        2: lvl >= 2 ? 'Taken' : 'Locked',
        3: lvl >= 3 ? 'Taken' : 'Locked'
      };
    } catch (error) {
      console.error('Error fetching quiz scores:', error);
    }
  }

  // Fetch quiz rows for a given level and set modalListData
  /**
   * Fetch quiz rows for a level and populate modalListData
   * @param {number} level
   */
  async function fetchQuizForLevel(level) {
    if (!$studentData?.pk_studentID) {
      modalText = 'Student not signed in.';
      modalListData = null;
      return;
    }

    /** @type {{[k:string]:string}} */
    const urls = {
      '1': '/src/lib/api/get_level1_quiz_results.php',
      '2': '/src/lib/api/get_level2_quiz_results.php',
      '3': '/src/lib/api/get_level3_quiz_results.php'
    };

    // Always fetch per-level attempt rows (use studentID to filter on server)
    const apiPath = urls[String(level)];
    const url = `http://localhost/shenieva-teacher${apiPath}${apiPath.includes('?') ? '&' : '?'}studentID=${encodeURIComponent($studentData.pk_studentID)}`;
  modalText = 'Loading...';
  modalListData = null;

    try {
      const res = await fetch(url);
      const data = await res.json();
      if (!data.success) {
        modalText = 'Failed to load quiz data.';
        modalListData = [];
        return;
      }

  const allRows = (data.data || []);
    const matchId = String($studentData?.pk_studentID ?? '').trim();
      const matchIdNo = String($studentData?.idNo ?? '').trim().toLowerCase();
      const matchName = String($studentData?.studentName ?? '').trim().toLowerCase();

  let rows = allRows.filter(/** @param {any} r */ (r) => String(r.studentID ?? '').trim() === matchId);
      if ((!rows || rows.length === 0) && matchIdNo) {
        rows = allRows.filter(/** @param {any} r */ (r) => String(r.idNo ?? '').trim().toLowerCase() === matchIdNo);
      }
      if ((!rows || rows.length === 0) && matchName) {
        rows = allRows.filter(/** @param {any} r */ (r) => String(r.studentName ?? '').trim().toLowerCase() === matchName);
      }

      // Treat the returned rows as student attempt rows for this level.
      if (!rows || rows.length === 0) {
        modalText = 'Not played yet.';
        modalListData = [];
        return;
      }

      // group by storyTitle + attempt
      /** @type {Record<string, any>} */
      const groups = {};
      rows.forEach(/** @param {any} r */ (r) => {
        const attempt = String(r.attempt ?? '1');
        const story = r.storyTitle ?? r.story_title ?? 'Story';
        const key = `${story}::${attempt}`;
        const p = Number(r.score ?? r.point ?? 0) || 0;
        if (!groups[key]) groups[key] = { storyTitle: story, attempt: Number(attempt), totalPoints: 0, items: 0, createdAt: r.createdAt, rows: [] };
        groups[key].totalPoints += p;
        groups[key].items += 1;
        groups[key].rows.push(r);
        if (r.createdAt && (!groups[key].createdAt || groups[key].createdAt < r.createdAt)) groups[key].createdAt = r.createdAt;
      });

      const attempts = Object.values(groups).sort((a, b) => (b.attempt - a.attempt) || ((b.createdAt || '').localeCompare(a.createdAt || '')));
      modalListData = attempts;
      modalText = `${attempts.length} attempt${attempts.length !== 1 ? 's' : ''}`;
    } catch (e) {
      console.error('Quiz fetch error', e);
      modalText = 'Error fetching data.';
      modalListData = [];
    }
  }

  // Get level display text
  function getLevelText() {
    if (!$studentData) return 'Level 0 - Not Started';
    return `Level ${$studentData.studentLevel || 0}`;
  }

  // Open modal
  /**
   * @param {string} type
   */
  function openModal(type) {
    modalType = type;
    isModalOpen = true;
  }

  // Open student details but wait a tick so the originating click doesn't
  // immediately hit the overlay and close the modal.
  async function openStudentDetails() {
    modalType = 'student';
    // wait for DOM update so the click that opened the button finishes
    await tick();
    isModalOpen = true;
  }

  /**
   * Click handler for quiz cards: fetch attempts then open the most recent attempt's answer sheet if present.
   * @param {number} level
   */
  async function handleQuizClick(level) {
    // fetch quiz rows and populate modalListData
    await fetchQuizForLevel(level);
    if (modalListData && modalListData.length > 0) {
      // open the most recent attempt (modalListData is sorted desc by attempt)
      openAttemptViewer(modalListData[0]);
    } else {
      // fallback to the existing inline modal which will show 'Not played yet.'
      modalType = `quiz${level}`;
      isModalOpen = true;
    }
  }

  /**
   * Open the quiz answer-sheet viewer for a grouped attempt
   * @param {{storyTitle:string, attempt:number, rows:Array<any>, totalPoints:number}} attemptGroup
   */
  function openAttemptViewer(attemptGroup) {
    const rows = attemptGroup.rows || [];
    const randomizedQuizData = rows.map((r, idx) => {
      const choices = [];
  // Only include choices A-C for the Stats viewer (exclude D)
  if (r.choiceA !== undefined) choices.push({ id: 0, text: r.choiceA, is_correct: false });
  if (r.choiceB !== undefined) choices.push({ id: 1, text: r.choiceB, is_correct: false });
  if (r.choiceC !== undefined) choices.push({ id: 2, text: r.choiceC, is_correct: false });

      const correctLabel = (r.correctAnswer ?? r.correct_answer ?? '').toString().trim();
      // Only treat A-C letter labels as answer choices in this viewer
      if (correctLabel.length === 1 && /[A-Ca-c]/.test(correctLabel)) {
        const li = correctLabel.toUpperCase().charCodeAt(0) - 65;
        if (choices[li]) choices[li].is_correct = true;
      } else if (choices.length > 0) {
        choices.forEach(c => { if (String(c.text ?? '').trim() === String(r.correctAnswer ?? r.correct_answer ?? '').trim()) c.is_correct = true; });
      }

      return {
        id: idx + 1,
        question: r.question ?? r.storyTitle ?? r.question_text ?? '',
        answer: r.answer ?? r.correctAnswer ?? '',
        points: Number(r.point ?? r.score ?? r.points ?? 0) || 0,
        choices
      };
    });

    const selectedChoices = rows.map((r) => {
      const sel = (r.selectedAnswer ?? r.studentAnswer ?? r.selected_answer ?? '').toString().trim();
      if (!sel) return null;
      if (sel.length === 1 && /[A-Da-d]/.test(sel)) {
        const index = sel.toUpperCase().charCodeAt(0) - 65;
        const ch = randomizedQuizData.find((q) => q.choices && q.choices[index])?.choices[index] ?? null;
        return ch ? { ...ch } : null;
      }
      for (const q of randomizedQuizData) {
        const found = (q.choices || []).find(c => String(c.text ?? '').trim() === sel);
        if (found) return { ...found };
      }
      return { id: -1, text: sel, is_correct: Boolean(r.point ?? r.score ?? 0) };
    });
    // Normalize selected choices and ensure is_correct reflecting the mapping between selected and correct answer
    const normalizedSelected = selectedChoices.map((sel, qi) => {
      const row = rows[qi] || {};
      const correctLabel = String(row.correctAnswer ?? row.correct_answer ?? '').trim().toLowerCase();
      if (!sel) return null;
      // if selected maps to a choice id present in randomized data, derive correctness from that choice
      if (typeof sel.id === 'number' && sel.id >= 0) {
        const choice = (randomizedQuizData[qi]?.choices || []).find(c => c.id === sel.id);
        if (choice) return { ...sel, is_correct: Boolean(choice.is_correct) };
      }
      // Otherwise, compare textual answers (case-insensitive)
      const selText = String(sel.text ?? '').trim().toLowerCase();
      const isCorrectText = selText.length > 0 && correctLabel.length > 0 && selText === correctLabel;
      return { ...sel, is_correct: Boolean(sel.is_correct) || isCorrectText };
    });
    // Determine score as number of correct items and total as number of quiz items
    const totalItems = rows.length || randomizedQuizData.length || 0;
    const anySelected = normalizedSelected.some(s => s !== null && s !== undefined);
    const correctFromSelected = normalizedSelected.reduce((acc, s) => acc + ((s && s.is_correct) ? 1 : 0), 0);
    const correctFromPoints = rows.reduce((acc, r) => acc + ((Number(r.point ?? r.score ?? 0) || 0) > 0 ? 1 : 0), 0);
    const scoreCount = anySelected ? correctFromSelected : correctFromPoints;

    quizViewerData = randomizedQuizData;
    quizViewerSelectedChoices = normalizedSelected;
    // Show score as count of correct items (e.g. 3/3)
  quizViewerScore = Number(scoreCount || 0);
  quizViewerTotalPoints = Number(totalItems || 0);
  quizViewerStoryTitle = attemptGroup.storyTitle ?? '';
  quizViewerAttempt = Number(attemptGroup.attempt ?? 1);
    quizViewerLevel = modalType === 'quiz2' ? 2 : modalType === 'quiz3' ? 3 : 1;
    showQuizViewer = true;
  }

  // Close modal
  function closeModal() {
    isModalOpen = false;
    modalType = null;
  }

  // Ensure apostrophes display cleanly (remove any escape backslashes like \' -> ') in Stats view
  /**
   * Remove any backslash before an apostrophe so text like \' becomes '
   * @param {any} input
   */
  function unescapeApostrophe(input) {
    const s = input == null ? '' : String(input);
    // Replace backslash-apostrophe (\') with just apostrophe '
    return s.replace(/\\'/g, "'");
  }

  /** @type {(e: KeyboardEvent) => void} */
  let _escHandler = (/** @type {KeyboardEvent} */ _e) => {};
  onMount(async () => {
    if ($studentData) {
      // Refresh quiz scores and fetch authoritative student details from DB
      await Promise.all([fetchQuizScores(), fetchStudentDetails()]);
    }
    // Attach Escape handler
    _escHandler = /** @param {KeyboardEvent} e */ (e) => { if (e.key === 'Escape') closeModal(); };
    window.addEventListener('keydown', _escHandler);
    isLoading = false;
  });

  onDestroy(() => {
    if (_escHandler) window.removeEventListener('keydown', _escHandler);
  });

  // Re-fetch student details when store changes
  $: if ($studentData) {
    // keep local store in sync
    fetchStudentDetails();
  }
</script>

<div
  in:slide={{ duration: 400, y: 20 }}
  class="w-full max-w-4xl mx-auto p-4 text-center bg-gradient-to-br from-blue-50 via-pink-50 to-yellow-50 rounded-3xl shadow-lg flex flex-col items-center"
>
  <!-- Header -->
  <h1
    class="text-4xl md:text-5xl font-extrabold text-purple-600 mb-[2vh] animate-bounce-slow"
    in:slide={{ duration: 600 }}
    style="font-family: 'Comic Sans MS', 'Chalkboard', cursive;"
  >
    Your Super Stats! 🌟
  </h1>

  <!-- Stats Grid -->
  <div class="grid grid-cols-2 md:grid-cols-3 gap-4 w-full">
    <!-- Level Card -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-pink-500 active:bg-pink-50"
      in:slide={{ duration: 400, delay: 100 }}
      on:click={() => openModal('level')}
      type="button"
    >
      <h2 class="text-xl font-bold text-pink-600 mb-1 flex items-center justify-center">
        <span class="mr-2">🏆</span> Level
      </h2>
      <p class="text-sm font-semibold text-gray-700">{getLevelText()}</p>
  </button>

    <!-- Quiz 1 Card (Level 1) -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-lime-500 active:bg-lime-50"
      in:slide={{ duration: 400, delay: 200 }}
      on:click={async () => { await handleQuizClick(1); }}
      type="button"
    >
      <h2 class="text-xl font-bold text-lime-600 mb-1 flex items-center justify-center">
        <span class="mr-2">�</span> Quiz 1 (Level 1)
      </h2>
      <p class="text-sm font-semibold text-gray-700">{quizScores[1]}</p>
    </button>

    <!-- Quiz 2 Card (Level 2) -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-blue-500 active:bg-blue-50"
      in:slide={{ duration: 400, delay: 300 }}
      on:click={async () => { await handleQuizClick(2); }}
      type="button"
    >
      <h2 class="text-xl font-bold text-blue-600 mb-1 flex items-center justify-center">
        <span class="mr-2">📝</span> Quiz 2 (Level 2)
      </h2>
      <p class="text-sm font-semibold text-gray-700">{quizScores[2]}</p>
    </button>

    <!-- Quiz 3 Card (Level 3) -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-blue-500 active:bg-blue-50"
      in:slide={{ duration: 400, delay: 400 }}
      on:click={async () => { await handleQuizClick(3); }}
      type="button"
    >
      <h2 class="text-xl font-bold text-blue-600 mb-1 flex items-center justify-center">
        <span class="mr-2">📝</span> Quiz 3 (Level 3)
      </h2>
      <p class="text-sm font-semibold text-gray-700">{quizScores[3]}</p>
    </button>

      
 
    <!-- Trash Card -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-teal-500 active:bg-teal-50"
      in:slide={{ duration: 400, delay: 400 }}
      on:click={() => { modalType = 'trash'; isModalOpen = true; }}
      type="button"
    >
      <h2 class="text-xl font-bold text-teal-600 mb-1 flex items-center justify-center">
        <span class="mr-2">🗑️</span> Trash Collected
      </h2>
      <p class="text-sm font-semibold text-gray-700">
        {$studentData?.studentColtrash || 0} Pieces
      </p>
  </button>

 
    <!-- Student Details Card (replaces Gifts) -->
    <button
      class="bg-white p-3 rounded-2xl shadow-md hover:shadow-lg hover:scale-105 transition-all duration-300 cursor-pointer active:border-2 active:border-cyan-500 active:bg-cyan-50"
      in:slide={{ duration: 400, delay: 600 }}
      on:click|stopPropagation={openStudentDetails}
      type="button"
    >
        <h2 class="text-xl font-bold text-cyan-600 mb-1 flex items-center justify-center">
          <span class="mr-2">👤</span> Student Details
        </h2>
        {#if displayStudent}
          <div class="flex items-center gap-3 justify-start">
            <div class="w-12 h-12 rounded-full flex items-center justify-center bg-cyan-100 text-2xl">{(displayStudent.studentGender || '').toString().toLowerCase() === 'female' ? '👧' : '👦'}</div>
            <div class="text-left">
              <div class="text-sm font-semibold text-gray-800">{displayStudent.studentName ?? '—'}</div>
              <div class="text-xs text-gray-600">ID: {displayStudent.idNo ?? displayStudent.pk_studentID ?? '—'}</div>
              <div class="text-xs text-gray-600">Level: {displayStudent.studentLevel ?? 0} • {displayStudent.studentGender ?? '—'}</div>
            </div>
          </div>
        {:else}
          <p class="text-sm font-semibold text-gray-700">{$studentData?.studentName ?? '—'}</p>
          <p class="text-xs text-gray-600 mt-1">ID: {$studentData?.idNo ?? $studentData?.pk_studentID ?? '—'}</p>
          <p class="text-xs text-gray-600">Level: { $studentData?.studentLevel ?? '0' } • Gender: { $studentData?.studentGender ?? '—' }</p>
        {/if}
    </button>
  </div>

  <!-- Modals -->
  {#if isModalOpen}
    <div class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- Overlay sits below modal content (z-40) -->
      <button class="absolute inset-0 bg-black/40 z-40" on:click|self={closeModal} aria-label="Close dialog" type="button"></button>

      {#if modalType === 'level'}
        <div class="relative z-50">
          <LevelModal level={getLevelText()} on:close={closeModal} />
        </div>
      {:else if modalType === 'trash'}
        <div class="relative z-50">
          <TrashModal trash={$studentData?.studentColtrash || 0} on:close={closeModal} />
        </div>
      {:else if modalType === 'student'}
        <!-- Inline Student Details modal -->
        <div role="dialog" aria-modal="true" aria-labelledby="student-details-title" tabindex="-1" class="relative z-50 bg-white rounded-2xl shadow-lg max-w-3xl w-full mx-4 p-6 overflow-auto text-left">
          <div class="flex items-center justify-between mb-4">
            <h3 id="student-details-title" class="text-xl font-semibold">Student Details</h3>
            <!-- top-right small close removed to avoid duplicate close buttons; bottom Close remains -->
          </div>
          <div class="space-y-3 text-sm">
            {#if displayStudent}
                  <div class="flex items-center gap-4">
                    <div class="w-20 h-20 rounded-full flex items-center justify-center bg-cyan-100 text-4xl">{(displayStudent.studentGender || '').toString().toLowerCase() === 'female' ? '👧' : '👦'}</div>
                    <div class="text-left">
                      <div class="text-lg font-bold text-gray-800">{displayStudent.studentName ?? '—'}</div>
                      <div class="text-sm text-gray-600">ID: {displayStudent.idNo ?? displayStudent.pk_studentID ?? '—'}</div>
                      <div class="text-sm text-gray-600">Level: {displayStudent.studentLevel ?? 0} • {displayStudent.studentGender ?? '—'}</div>
                    </div>
                  </div>
              <div class="grid grid-cols-3 gap-4 mt-3">
                <div class="bg-gray-50 p-3 rounded text-center">
                  <div class="text-xs text-gray-500">Trash Collected</div>
                  <div class="text-2xl font-semibold text-teal-600">{displayStudent.studentColtrash ?? 0}</div>
                </div>
                <div class="bg-gray-50 p-3 rounded text-center">
                  <div class="text-xs text-gray-500">Ribbons</div>
                  <div class="text-2xl font-semibold text-orange-500">{displayStudent.studentRibbon ?? displayStudent.studentRibbons ?? 0}</div>
                </div>
                <div class="bg-gray-50 p-3 rounded text-center">
                  <div class="text-xs text-gray-500">Level</div>
                  <div class="text-2xl font-semibold text-purple-600">{displayStudent.studentLevel ?? 0}</div>
                </div>
              </div>
            {:else}
              <div><strong>Name:</strong> {$studentData?.studentName ?? '—'}</div>
              <div><strong>Student ID:</strong> {$studentData?.pk_studentID ?? '—'}</div>
              <div><strong>ID No.:</strong> {$studentData?.idNo ?? '—'}</div>
              <div><strong>Level:</strong> {$studentData?.studentLevel ?? '0'}</div>
              <div><strong>Gender:</strong> {$studentData?.studentGender ?? '—'}</div>
              <div><strong>Trash Collected:</strong> {$studentData?.studentColtrash ?? 0}</div>
              <div><strong>Ribbons Earned:</strong> {$studentData?.studentRibbons ?? 0}</div>
            {/if}
          </div>
          <div class="mt-4 flex justify-end">
            <button class="px-4 py-2 bg-purple-600 text-white rounded-full text-sm" on:click={closeModal} type="button">Close</button>
          </div>
        </div>
  {:else if modalType === 'quiz1' || modalType === 'quiz2' || modalType === 'quiz3'}
    <!-- Inline Quiz modal: show stored quiz definitions or grouped attempts -->
  <div class="relative z-50 bg-white rounded-2xl shadow-lg max-w-3xl w-full mx-4 p-6 overflow-auto text-left">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold">{modalText}</h3>
            <button class="text-sm text-gray-600" on:click={closeModal} type="button">Close</button>
          </div>

          {#if modalListType === 'stored'}
            {#if !modalListData || modalListData.length === 0}
              <p class="text-gray-500">No stored quiz questions found for this level.</p>
            {:else}
              <div class="space-y-3">
                {#each modalListData as q, i}
                  <div class="p-3 border rounded">
                    <div class="font-semibold">Q{i + 1}: {unescapeApostrophe(q.question ?? q.question_text ?? q.questionText)}</div>
                    {#if q.choices && q.choices.length > 0}
                      <div class="mt-2 space-y-1 text-sm">
                        {#each q.choices as c, ci}
                          <div class="flex items-center gap-2">
                            <div class="w-6 font-semibold">{String.fromCharCode(65 + ci)}</div>
                            <div class="flex-1">{c.text ?? c.choice_text}</div>
                            {#if c.is_correct}
                              <div class="text-green-600 text-sm">(Correct)</div>
                            {/if}
                          </div>
                        {/each}
                      </div>
                    {/if}
                    <div class="mt-2 text-sm text-gray-600">Points: {q.points ?? q.points}</div>
                  </div>
                {/each}
              </div>
            {/if}
          {:else}
            {#if !modalListData || modalListData.length === 0}
              <p class="text-gray-500">No attempts found for this quiz.</p>
            {:else}
              {#each modalListData as attemptGroup, index}
                <div class="mb-4 p-3 border rounded-lg">
                  <button class="w-full text-left flex items-center justify-between" on:click={() => toggleAttempt(index)} type="button">
                    <div>
                      <div class="font-medium">{unescapeApostrophe(attemptGroup.storyTitle)}</div>
                      <div class="text-sm text-gray-500">Attempt {attemptGroup.attempt} • {attemptGroup.items} questions</div>
                    </div>
                    <div class="text-sm text-gray-600">{attemptGroup.totalPoints} pts</div>
                  </button>

                    {#if openAttempts.has(index)}
                    <div class="mt-3 space-y-2">
                                  {#each attemptGroup.rows as row, qi}
                                    <div class="p-2 bg-gray-50 rounded text-left" class:correct={(Number(row.point ?? row.score ?? 0) > 0)} class:incorrect={(Number(row.point ?? row.score ?? 0) === 0)}>
                                      <div class="text-sm font-bold text-left">{unescapeApostrophe(row.question ?? row.storyTitle)}</div>
                          <div class="text-sm text-gray-700 text-left">Your answer:
                                        <span class="block">{unescapeApostrophe(row.selectedAnswer ?? row.studentAnswer ?? '—')}</span>
                                      </div>
                          {#if String(row.selectedAnswer ?? row.studentAnswer ?? '').trim().toLowerCase() !== String(row.correctAnswer ?? row.correct_answer ?? '').trim().toLowerCase()}
                            <div class="text-sm text-red-600">Correct answer: <span class="font-medium">{unescapeApostrophe(row.correctAnswer ?? row.correct_answer ?? '—')}</span></div>
                          {/if}
                          <div class="text-sm text-gray-600">Points: {row.point ?? row.score ?? 0}</div>
                        </div>
                      {/each}
                      <div class="mt-2 flex justify-end">
                        <button class="px-4 py-2 bg-purple-600 text-white rounded-full text-sm" on:click={() => openAttemptViewer(attemptGroup)} type="button">View Answer Sheet</button>
                      </div>
                    </div>
                  {/if}
                </div>
              {/each}
            {/if}
          {/if}
        </div>
      {/if}
    </div>
  {/if}
  {#if showQuizViewer}
    <!-- Read-only Answer Sheet viewer used inside Stats. No Proceed / Retake actions here. -->
    <div class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" on:click|self={() => (showQuizViewer = false)} aria-hidden="true"></div>
  <div class="bg-white rounded-2xl shadow-lg max-w-3xl w-full mx-4 md:mx-16 my-8 p-6 overflow-auto z-60 text-left" style="max-height:80vh;">
        <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-2xl font-bold">Answer Sheet</h3>
                {#if quizViewerStoryTitle}
                  <div class="text-sm text-gray-700">{unescapeApostrophe(quizViewerStoryTitle)}</div>
                {/if}
                <div class="text-sm text-gray-600">Attempt {quizViewerAttempt}</div>
                <div class="mt-2 flex items-center gap-4 text-sm text-gray-600">
                  <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-lime-500 inline-block" aria-hidden="true"></span> Correct</div>
                  <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-400 inline-block" aria-hidden="true"></span> Incorrect</div>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <div class="text-sm text-gray-700">Score <span class="font-semibold text-purple-700">{quizViewerScore}/{quizViewerTotalPoints}</span></div>
                <div class="w-40 h-3 bg-gray-200 rounded overflow-hidden" aria-hidden="true">
                  <div class="h-full bg-lime-500 transition-all" style="width: {quizViewerPercent}%;"></div>
                </div>
                <button class="px-4 py-2 bg-gray-200 rounded-md" on:click={() => (showQuizViewer = false)} type="button">Close</button>
              </div>
            </div>

        {#if !quizViewerData || quizViewerData.length === 0}
          <p class="text-gray-500">No data available for this attempt.</p>
        {:else}
          <div class="space-y-4">
            {#each quizViewerData as q, i}
              <div class="p-3 border rounded-lg relative text-left" class:correct={(quizViewerSelectedChoices?.[i]?.is_correct) === true} class:incorrect={(quizViewerSelectedChoices?.[i] && quizViewerSelectedChoices[i].is_correct === false)}>
                <div class="flex items-start gap-3">
                  <div class="flex-1 text-left font-bold">{unescapeApostrophe(q.question)}</div>
                  {#if quizViewerSelectedChoices?.[i]}
                    <span class="ml-auto px-2 py-0.5 text-xs rounded-full font-semibold text-white" class:badge-correct={quizViewerSelectedChoices[i].is_correct} class:badge-incorrect={!quizViewerSelectedChoices[i].is_correct}>
                      {quizViewerSelectedChoices[i].is_correct ? '✔' : '✖'}
                    </span>
                  {/if}
                </div>
                {#if q.choices && q.choices.length > 0}
                  <div class="mt-2">
                    {#each q.choices as c, ci}
                      <div class="flex items-center gap-2 text-sm">
                        <div class="w-6 font-semibold">{String.fromCharCode(65 + ci)}</div>
                        <div class="flex-1">{unescapeApostrophe(c.text)}</div>
                        {#if c.is_correct}
                          <div class="text-green-600">(Correct)</div>
                        {/if}
                        {#if quizViewerSelectedChoices?.[i] && quizViewerSelectedChoices[i].id === c.id}
                          <div class="text-purple-600 font-semibold ml-2">Your choice</div>
                        {/if}
                      </div>
                    {/each}
                  </div>
                  {:else}
                  <div class="mt-2 text-sm text-left">Your answer:
                    <span class="block">{unescapeApostrophe(quizViewerSelectedChoices?.[i]?.text ?? '—')}</span>
                  </div>
                {/if}
                <div class="mt-2 text-sm text-gray-600">Points: {q.points}</div>
              </div>
            {/each}
          </div>
        {/if}
      </div>
    </div>
  {/if}
</div>

<style>
  .hover\:scale-105:hover {
    transform: scale(1.05);
  }
  .transition-all {
    transition: all 0.3s ease;
  }
  .active\:border-2:active {
    border-width: 2px;
  }
  @keyframes bounce-slow {
    0%, 100% {
      transform: translateY(0);
    }
    50% {
      transform: translateY(-10px);
    }
  }
  .animate-bounce-slow {
    animation: bounce-slow 2s infinite;
  }

  /* Answer sheet correctness highlighting */
  .correct { background: #f0fdf4; border-color: #bbf7d0; }
  .incorrect { background: #fff1f2; border-color: #fecaca; }
  .badge-correct { background: #16a34a; }
  .badge-incorrect { background: #ef4444; }
</style>