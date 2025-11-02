<!-- src/routes/admin/modals/view_student.svelte -->
<script lang="ts">
  import { createEventDispatcher, onMount } from "svelte";
  import { writable } from "svelte/store";
  import EditStudentModal from "./edit_student.svelte"; // Import the new edit modal
  import { apiUrl } from '$lib/api_base';

  export let selectedPerson; // Student data passed from parent

  const dispatch = createEventDispatcher();

  interface AttendanceRecord {
    studentName: string;
    attendanceDateTime: string;
  }

  let attendanceData = writable<AttendanceRecord[]>([]);
  let showEditModal = false;
  let localPerson = { ...selectedPerson }; // Local copy to update after edit

  // Quiz results per level (fetched from admin APIs then filtered client-side)
  let level1Results: any[] = [];
  let level2Results: any[] = [];
  let level3Results: any[] = [];
  let loadingLevel1 = false;
  let loadingLevel2 = false;
  let loadingLevel3 = false;

  const tabs = [
    { id: "personal", label: "Personal Data" },
    { id: "attendance", label: "Attendance" },
    { id: "quiz1", label: "Quiz 1" },
    { id: "quiz2", label: "Quiz 2" },
    { id: "quiz3", label: "Quiz 3" },
  ];

  let activeTab = "personal";

  function handleClose() {
    dispatch("close");
  }

  function handleEdit() {
    showEditModal = true;
  }

  function handleEditSave(event: CustomEvent) {
    const updatedStudent = event.detail;
    // Update localPerson to refresh the UI
    localPerson = { ...localPerson, ...updatedStudent };
    // Pass the edit event up to the parent to update studentData
    dispatch("edit", updatedStudent);
    showEditModal = false;
  }

  function setActiveTab(tabId: string) {
    activeTab = tabId;
  }

  // Handle backdrop click or keyboard activation
  function handleBackdropClose(event: MouseEvent | KeyboardEvent) {
    if (event.type === "click" && event.target !== event.currentTarget) {
      return;
    }
    if (
      event.type === "keydown" &&
      !(
        event instanceof KeyboardEvent &&
        (event.key === "Enter" || event.key === " ")
      )
    ) {
      return;
    }
    dispatch("close");
  }

  onMount(() => {
    window.addEventListener("keydown", handleKeydown);
    return () => {
      window.removeEventListener("keydown", handleKeydown);
    };
  });

  function handleKeydown(event: KeyboardEvent) {
    if (event.key === "Escape") {
      dispatch("close");
    }
  }

  // Reactive statement for level progress
  $: levelProgress = Math.min(
    ((parseInt(localPerson.studentLevel) || 0) / 3) * 100,
    100,
  );

  async function fetchAttendanceData(studentID: string) {
    try {
      const response = await fetch(apiUrl(`fetch_attendance.php?studentID=${studentID}`));
      if (!response.ok)
        throw new Error(`HTTP error! Status: ${response.status}`);
      const data = await response.json();
      if (data.error) {
        console.warn("🚨 Attendance fetch warning:", data.error);
        attendanceData.set([]); // Ensure it's still an array for the UI logic
      } else {
        attendanceData.set(data); // Successfully set fetched data
      }
    } catch (error) {
      console.error("❌ Error fetching attendance data:", error);
      attendanceData.set([]);
    }
  }

  // When selectedPerson changes, refresh local copy and fetch attendance + quizzes
  $: if (selectedPerson) {
    // use an async IIFE to allow fetching additional student details if needed
    (async () => {
      let enriched = null;
      // if the parent passed a partial person without pk_studentID, try to find the full record
      if (!selectedPerson.pk_studentID) {
        try {
          const res = await fetch(apiUrl('fetch_students.php'));
          if (res.ok) {
            const students = await res.json();
            if (Array.isArray(students)) {
              enriched = students.find((s: any) => String(s.idNo) === String(selectedPerson.idNo) || String(s.studentName) === String(selectedPerson.studentName)) || null;
            }
          }
        } catch (e) {
          console.warn('Could not enrich selectedPerson with full student record', e);
        }
      }

      localPerson = { ...selectedPerson, ...(enriched || {}) };

      // Only fetch attendance/quizzes if we have a pk_studentID
      const sid = localPerson.pk_studentID ?? localPerson.fk_studentID ?? null;
      if (sid) {
        fetchAttendanceData(String(sid));
        // fetch quiz results for this student (client-side filter)
        fetchLevel1Results(String(sid));
        fetchLevel2Results(String(sid));
        fetchLevel3Results(String(sid));
      } else {
        // clear arrays if we cannot fetch by id
        level1Results = [];
        level2Results = [];
        level3Results = [];
      }
    })();
  }

  // Fetch level1 results and filter to the current student
  async function fetchLevel1Results(studentID: string) {
    loadingLevel1 = true;
    level1Results = [];
    try {
  const res = await fetch(apiUrl('get_level1_quiz_results.php'));
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      if (data && Array.isArray(data.data)) {
        level1Results = data.data.filter((r: any) => String(r.studentID) === String(studentID));
      }
    } catch (err) {
      console.error('Error fetching level1 results', err);
      level1Results = [];
    }
    loadingLevel1 = false;
  }

  async function fetchLevel2Results(studentID: string) {
    loadingLevel2 = true;
    level2Results = [];
    try {
  const res = await fetch(apiUrl('get_level2_quiz_results.php'));
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      if (data && Array.isArray(data.data)) {
        level2Results = data.data.filter((r: any) => String(r.studentID) === String(studentID));
      }
    } catch (err) {
      console.error('Error fetching level2 results', err);
      level2Results = [];
    }
    loadingLevel2 = false;
  }

  async function fetchLevel3Results(studentID: string) {
    loadingLevel3 = true;
    level3Results = [];
    try {
  const res = await fetch(apiUrl('get_level3_quiz_results.php'));
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      if (data && Array.isArray(data.data)) {
        level3Results = data.data.filter((r: any) => String(r.studentID) === String(studentID));
      }
    } catch (err) {
      console.error('Error fetching level3 results', err);
      level3Results = [];
    }
    loadingLevel3 = false;
  }

  // Friendly date formatting
  function fmtDate(dateString: string | null | undefined) {
    if (!dateString) return '-';
    try {
      let ds = String(dateString).trim();
      // If input includes time separated by space, take both parts
      let datePart = ds.split(' ')[0];
      // Normalize numeric timestamps
      let dt: Date;
      if (/^\d+$/.test(datePart)) {
        const n = Number(datePart);
        dt = new Date(n > 1e12 ? n : n * 1000);
      } else {
        dt = new Date(ds);
      }
      if (Number.isNaN(dt.getTime())) return String(dateString);

      // Format as 'Mon DD, YYYY' and include time with AM/PM when present
      const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      const month = months[dt.getMonth()];
      const day = dt.getDate();
      const year = dt.getFullYear();
      const hours = dt.getHours();
      const minutes = String(dt.getMinutes()).padStart(2, '0');
      // time in 12-hour with AM/PM
      const ampm = hours >= 12 ? 'PM' : 'AM';
      let h12 = hours % 12;
      if (h12 === 0) h12 = 12;
      // If original string contained a time portion, include time
      const hasTime = /\d{1,2}:\d{2}/.test(ds);
      return hasTime ? `${month} ${day}, ${year} ${h12}:${minutes} ${ampm}` : `${month} ${day}, ${year}`;
    } catch (e) {
      return String(dateString);
    }
  }

  // Helpers to determine correctness
  function isLevelItemCorrect(r: any) {
    // prefer explicit numeric score/point if present
    const scoreVal = Number(r.score ?? r.point ?? 0);
    if (!Number.isNaN(scoreVal) && scoreVal > 0) return true;

    // fall back to comparing answers (A/B/C/D or text)
    if (r.selectedAnswer != null && r.correctAnswer != null) {
      const a = String(r.selectedAnswer).trim().toUpperCase();
      const b = String(r.correctAnswer).trim().toUpperCase();
      return a === b;
    }

    return false;
  }

  function level3Points(r: any) {
    const p = r.score ?? r.point ?? 0;
    return Number.isFinite(Number(p)) ? Number(p) : 0;
  }

  // reactive summaries for level3 tab
  let totalQuestions = 0;
  let totalPoints = 0;
  let totalCorrect = 0;

  $: totalQuestions = level3Results.length;
  $: totalPoints = level3Results.reduce((s, r) => s + level3Points(r), 0);
  $: totalCorrect = level3Results.reduce((c, r) => c + (isLevelItemCorrect(r) ? 1 : 0), 0);

  let currentPage = 1;
  let pageSize = 10;

  // Computed data for pagination
  $: paginatedAttendance = $attendanceData.slice(
    (currentPage - 1) * pageSize,
    currentPage * pageSize,
  );

  // Pagination Controls
  function nextPage() {
    if (currentPage * pageSize < $attendanceData.length) {
      currentPage++;
    }
  }

  function prevPage() {
    if (currentPage > 1) {
      currentPage--;
    }
  }
</script>

<div
  class="fixed inset-0 bg-black/30 z-30 flex items-center justify-center"
  on:click={handleBackdropClose}
  on:keydown={handleBackdropClose}
  role="dialog"
  aria-label="Student details modal"
>
  <!-- modal panel: constrained to viewport, internal scrolling only -->
  <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
  <!-- svelte-ignore a11y_click_events_have_key_events -->
  <div
    class="bg-white rounded-lg shadow-lg w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden flex flex-col"
    on:click|stopPropagation
    aria-labelledby="modal-title"
  >
    <!-- Header -->
    <div class="flex justify-between items-center p-4 border-b border-gray-200">
      <h2 id="modal-title" class="text-xl font-semibold text-gray-900">
        Student Details
      </h2>
      <button
        type="button"
        class="text-gray-500 hover:text-gray-700 text-2xl font-medium leading-none focus:outline-none focus:ring-2 focus:ring-lime-500"
        on:click={handleClose}
        aria-label="Close modal"
      >
        ×
      </button>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="flex space-x-1 px-4">
        {#each tabs as tab}
          <button
            type="button"
            class="py-2 px-4 text-sm font-medium {activeTab === tab.id
              ? 'text-lime-600 border-b-2 border-lime-500'
              : 'text-gray-500 hover:text-gray-700'} focus:outline-none focus:ring-2 focus:ring-lime-500"
            on:click={() => setActiveTab(tab.id)}
          >
            {tab.label}
          </button>
        {/each}
      </nav>
    </div>

  <!-- Content: internal scroll region -->
  <div class="px-4 py-2 overflow-auto flex-1">
      {#if activeTab === "personal"}
        <!-- Personal Data -->
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <span class="text-gray-600 font-medium min-w-[120px]">ID:</span>
            <span class="text-gray-900">{localPerson.idNo || "N/A"}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-gray-600 font-medium min-w-[120px]">Name:</span>
            <span class="text-gray-900">{localPerson.studentName}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-gray-600 font-medium min-w-[120px]">Gender:</span>
            <span class="text-gray-900">{localPerson.studentGender}</span>
          </div>
        </div>

        <!-- Performance Overview -->
        <div class="space-y-4 mt-4">
          <h3 class="text-lg font-medium text-gray-800">
            Performance Overview
          </h3>
          <div>
            <span class="text-gray-600 font-medium block mb-1">Level:</span>
            <div class="w-full bg-gray-200 rounded-full h-4">
              <div
                class="bg-lime-500 h-4 rounded-full"
                style="width: {levelProgress}%"
                aria-label={`Level: ${localPerson.studentLevel} out of 3`}
              ></div>
            </div>
            <span class="text-gray-700 text-sm mt-1 block">
              {localPerson.studentLevel} / 3
            </span>
          </div>
          <div>
            <span class="text-gray-600 font-medium block mb-1">Ribbons:</span>
            <div class="flex items-center gap-2">
              <div
                class="bg-orange-500 text-white w-8 h-8 flex items-center justify-center rounded-full font-semibold"
              >
                {localPerson.studentRibbon}
              </div>
              <span class="text-gray-900">Ribbons Earned</span>
            </div>
          </div>
          <div>
            <span class="text-gray-600 font-medium block mb-1"
              >Collected Trash:</span
            >
            <div class="flex items-center gap-2">
              <div
                class="bg-lime-500 text-white w-8 h-8 flex items-center justify-center rounded-full font-semibold"
              >
                {localPerson.studentColtrash}
              </div>
              <span class="text-gray-900">Trash Collected</span>
            </div>
          </div>
        </div>
      {:else if activeTab === "attendance"}
        <div class="text-gray-700">
          <h3 class="text-lg font-medium text-gray-800 mb-3">Attendance Records</h3>
          {#if paginatedAttendance.length > 0}
            <div class="grid gap-3">
              {#each paginatedAttendance as record, index}
                <div class="p-3 rounded-lg border border-gray-200 bg-white flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 font-semibold">{(currentPage - 1) * pageSize + index + 1}</div>
                    <div>
                      <div class="text-sm font-semibold">{record.studentName}</div>
                      <div class="text-xs text-gray-500">{fmtDate(record.attendanceDateTime)}</div>
                    </div>
                  </div>
                  <div class="text-sm text-gray-600">{fmtDate(record.attendanceDateTime)}</div>
                </div>
              {/each}
            </div>
          {:else}
            <p class="text-gray-500">No attendance records found.</p>
          {/if}
        </div>
      {:else if activeTab === "quiz1"}
        <div class="text-gray-700">
          <h3 class="text-lg font-medium text-gray-800 mb-2">Level 1 — Quiz Results</h3>
          {#if loadingLevel1}
            <p class="text-gray-500">Loading...</p>
          {:else if level1Results.length === 0}
            <p class="text-gray-500">No level 1 results for {localPerson.studentName}.</p>
          {:else}
            <div class="space-y-3">
              <div class="text-sm text-gray-600">{level1Results.length} question(s)</div>
              <div class="divide-y rounded-lg overflow-hidden">
                {#each level1Results as r, i}
                  {@const correct = isLevelItemCorrect(r)}
                  <div class={`p-3 flex items-start gap-3 ${correct ? 'bg-green-50 border-l-4 border-green-400' : 'bg-red-50 border-l-4 border-red-400'}`}>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm ${correct ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}">{i + 1}</div>
                    <div class="flex-1">
                      <div class="font-semibold text-gray-800">{r.question}</div>
                      <div class="text-xs text-gray-600 mt-1">Selected: <span class="font-medium">{r.selectedAnswer ?? '-'}</span> — Correct: <span class="font-medium">{r.correctAnswer ?? '-'}</span></div>
                    </div>
                    <div class="text-right">
                      <div class="text-sm font-semibold">{r.score ?? r.point ?? '-'}</div>
                      <div class="text-xs text-gray-500">{fmtDate(r.createdAt)}</div>
                    </div>
                  </div>
                {/each}
              </div>
            </div>
          {/if}
        </div>
      {:else if activeTab === "quiz2"}
        <div class="text-gray-700">
          <h3 class="text-lg font-medium text-gray-800 mb-2">Level 2 — Quiz Results</h3>
          {#if loadingLevel2}
            <p class="text-gray-500">Loading...</p>
          {:else if level2Results.length === 0}
            <p class="text-gray-500">No level 2 results for {localPerson.studentName}.</p>
          {:else}
            <div class="space-y-3">
              <div class="text-sm text-gray-600">{level2Results.length} question(s)</div>
              <div class="divide-y rounded-lg overflow-hidden">
                {#each level2Results as r, i}
                  {@const correct = isLevelItemCorrect(r)}
                  <div class={`p-3 flex items-start gap-3 ${correct ? 'bg-green-50 border-l-4 border-green-400' : 'bg-red-50 border-l-4 border-red-400'}`}>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm ${correct ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}">{i + 1}</div>
                    <div class="flex-1">
                      <div class="font-semibold text-gray-800">{r.question}</div>
                      <div class="text-xs text-gray-600 mt-1">Selected: <span class="font-medium">{r.selectedAnswer ?? '-'}</span> — Correct: <span class="font-medium">{r.correctAnswer ?? '-'}</span></div>
                    </div>
                    <div class="text-right">
                      <div class="text-sm font-semibold">{r.score ?? r.point ?? '-'}</div>
                      <div class="text-xs text-gray-500">{fmtDate(r.createdAt)}</div>
                    </div>
                  </div>
                {/each}
              </div>
            </div>
          {/if}
        </div>
      {:else if activeTab === "quiz3"}
        <div class="text-gray-700">
          <h3 class="text-lg font-medium text-gray-800 mb-2">Level 3 — Quiz Results</h3>
          {#if loadingLevel3}
            <p class="text-gray-500">Loading...</p>
          {:else if level3Results.length === 0}
            <p class="text-gray-500">No level 3 results for {localPerson.studentName}.</p>
          {:else}
            <div class="space-y-3">
              <div class="flex items-center justify-between text-sm text-gray-600">
                <div>{totalQuestions} question(s) — {totalCorrect} correct</div>
                <div>Points: <span class="font-semibold">{totalPoints} pts</span></div>
              </div>

              <div class="divide-y rounded-lg overflow-hidden">
                {#each level3Results as r, i}
                  {@const correct = isLevelItemCorrect(r)}
                  <div class={`p-3 flex items-start gap-3 ${correct ? 'bg-green-50 border-l-4 border-green-400' : 'bg-red-50 border-l-4 border-red-400'}`}>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm ${correct ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}">{i + 1}</div>
                    <div class="flex-1">
                      <div class="font-semibold text-gray-800">{r.question}</div>
                      <div class="text-xs text-gray-600 mt-1">Answer: <span class="font-medium">{r.studentAnswer ?? '-'}</span></div>
                    </div>
                    <div class="text-right">
                      <div class="text-sm font-semibold">{level3Points(r)} pts</div>
                      <div class="text-xs text-gray-500">{fmtDate(r.createdAt)}</div>
                    </div>
                  </div>
                {/each}
              </div>
            </div>
          {/if}
        </div>
      {/if}
    </div>

    <!-- Footer -->
    <div
      class="p-4 border-t border-gray-200 flex justify-end items-center gap-4 flex-shrink-0 bg-white"
    >
      <!-- Edit Button -->
      <button
        type="button"
        on:click={handleEdit}
        class="px-4 py-2 bg-lime-600 text-white rounded-md hover:bg-lime-700 focus:outline-none focus:ring-2 focus:ring-lime-400"
      >
        Edit
      </button>

      <!-- Pagination Buttons - Show only for Attendance Tab -->
      {#if activeTab === "attendance" && $attendanceData.length > 10}
        <div class="flex gap-2">
          <button
            class="px-4 py-2 bg-gray-300 rounded-md hover:bg-lime-500"
            on:click={prevPage}
            disabled={currentPage === 1}
          >
            &lt;
          </button>

          <button
            class="px-4 py-2 bg-gray-300 rounded-md hover:bg-lime-500"
            on:click={nextPage}
            disabled={currentPage * pageSize >= $attendanceData.length}
          >
            &gt;
          </button>
        </div>
      {/if}

      <!-- Close Button -->
      <button
        type="button"
        on:click={handleClose}
        class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2"
      >
        Close
      </button>
    </div>
  </div>

  {#if showEditModal}
    <EditStudentModal
      selectedPerson={localPerson}
      on:close={() => (showEditModal = false)}
      on:edit={handleEditSave}
    />
  {/if}
</div>
