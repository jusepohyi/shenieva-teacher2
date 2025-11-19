<script>
  // @ts-nocheck
  import { Card, Button, Modal, Chart } from "flowbite-svelte";
  import {
    ExclamationCircleOutline,
    UsersGroupSolid,
    NewspaperSolid,
    BookOpenOutline,
    AwardOutline,
  } from "flowbite-svelte-icons";
  import { goto } from "$app/navigation";
  import SettingsModal from "../modals/settings.svelte";
  // Avoid importing the image as a JS module; use a string path instead.
  const modalBg = "/converted/assets/icons/modal-bg.webp";

  let showMenu = false;
  let showLogoutModal = false;
  let showSettingsModal = false;
  // settings modal name/email (fetched client-side so settings works without visiting profile)
  let settingsName = '';
  let settingsEmail = '';

  import sanitizeForDisplay from '$lib/utils/sanitize';
  import { apiUrl } from '$lib/api_base';

  // Live counts (will be fetched)
  let studentCount = 0;
  let quizzesTaken = 0;
  let totalMale = 0;
  let totalFemale = 0;

  // data holders for charts
  /** @type {{name:string,scores:number[]}[]} */
  let quizData = []; // series will be built from fetched quizzes
  /** @type {string[]} */
  let studentNames = [];
  // recent attendance rows for quick view
  let recentAttendance = [];
  // raw attendance rows fetched from server (kept for re-aggregation)
  let allAttendanceRows = [];
  // selected attendance view: 'today'|'weekly'|'monthly'|'year'
  let attendanceView = 'weekly';
  let attendanceViewLabel = 'This Week';
  let attendanceTotal = 0;
  // per-story unique quiz takers
  let quizzesPerStoryUnique = [0,0,0];
  // male/female counts per level: array index = level
  let maleFemalePerLevel = [ {M:0,F:0}, {M:0,F:0}, {M:0,F:0}, {M:0,F:0} ];
  // quiz modal state
  let showQuizModal = false;
  let quizModalTitle = '';
  let quizModalStudents = [];

  // Male/Female data for pie chart (default empty)
  let genderData = {
    series: [0, 0], // Males, Females
    labels: ["Male", "Female"],
  };

  // lightweight tween for animated counters
  /**
   * Svelte action to tween a number into view.
   * @param {HTMLElement} node
   * @param {{from?:number,to?:number,duration?:number}} opts
   */
  function tweenNumber(node, { from = 0, to = 0, duration = 800 } = {}) {
    const start = performance.now();
    /** @param {number} now */
    function frame(now) {
      const t = Math.min(1, (now - start) / duration);
      const val = Math.floor(from + (to - from) * (1 - Math.pow(1 - t, 3)));
      node.textContent = String(val);
      if (t < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
    return {
      /** @param {{from?:number,to?:number,duration?:number}} opts */
      update(opts) {
        // allow manual re-run if needed
        requestAnimationFrame(() => tweenNumber(node, opts));
      },
    };
  }

  $: options = {
    series: quizData.map(quiz => ({
      name: quiz.name,
      data: quiz.scores,
      color: quiz.name === "Quiz 1" ? "#4CAF50" : quiz.name === "Quiz 2" ? "#2196F3" : "#FF9800",
    })),
    chart: {
      type: "bar",
      height: 230, // Back to fixed height
      toolbar: { show: false },
    },
  };

  // Pie chart options for Male/Female count
  let levelColors = ["#9CA3AF", "#10B981", "#3B82F6", "#F59E0B"]; // Level 0,1,2,3 colors

  let genderOptions = {
      series: genderData.series,
      colors: levelColors,
    chart: {
      height: 200,
      width: "100%",
      type: "pie",
      toolbar: { show: false },
    },
    stroke: {
      colors: ["white"],
      lineCap: "",
    },
    plotOptions: {
      pie: {
        labels: {
          show: true,
        },
        size: "100%",
        dataLabels: {
          offset: -25,
        },
      },
    },
    labels: genderData.labels,
    dataLabels: {
      enabled: true,
      style: {
        fontFamily: "Inter, sans-serif",
      },
      formatter: function (val, opts) {
        return opts.w.config.series[opts.seriesIndex]; // Show raw numbers
      },
    },
    legend: {
      position: "bottom",
      fontFamily: "Inter, sans-serif",
    },
    yaxis: {
      labels: {
        formatter: function (value) {
          return value; // Raw numbers
        },
      },
    },
    xaxis: {
      labels: {
        formatter: function (value) {
          return value; // Raw numbers
        },
      },
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
    tooltip: {
      y: {
        formatter: function (value) {
          return value + " students";
        },
      },
    },
  };

  // Attendance chart options (default) — defined so aggregateAttendance can update it
  let attendanceOptions = {
    series: [{ name: 'Attendance', data: [] }],
    chart: { type: 'line', height: 240, toolbar: { show: false } },
    xaxis: { categories: [] },
    colors: ['#FF5733'],
    stroke: { curve: 'smooth' },
  };

  // Podium data for top 3 students
  let topStudents = [
    { name: "Eve", totalScore: 278, place: 1 },
    { name: "Bob", totalScore: 267, place: 2 },
    { name: "Alice", totalScore: 253, place: 3 },
  ];

  // fetch live data for dashboard

  /**
   * Fetch dashboard data client-side only.
   */
  async function fetchDashboardData() {
    try {
    // construct API urls via apiUrl helper so runtime base can be configured via VITE_API_BASE
    // students
    const studentsRes = await fetch(apiUrl('fetch_students.php'));
    const students = await studentsRes.json();
      studentCount = Array.isArray(students) ? students.length : 0;

      // gender breakdown
      // compute gender breakdown without callback parameter types to avoid implicit any diagnostics
      let males = 0;
      let females = 0;
      for (let i = 0; i < students.length; i++) {
        const s = students[i];
        if (s && s.studentGender === 'Male') males++;
        if (s && s.studentGender === 'Female') females++;
      }
      genderData.series = [males, females];

      // attendance: get all rows, store them and call aggregator for selected view
  const attendanceRes = await fetch(apiUrl('fetch_all_attendance.php'));
      const attendanceRows = await attendanceRes.json();
      allAttendanceRows = Array.isArray(attendanceRows) ? attendanceRows : [];
      // compute initial attendance view
      aggregateAttendance(attendanceView);
      // recent attendance rows (sorted desc by datetime)
      recentAttendance = attendanceRows.slice().sort((a,b) => new Date(b.attendanceDateTime) - new Date(a.attendanceDateTime)).slice(0,8);

      // quizzes: aggregate unique students per story across level1/2/3
      const stories = ['story1','story2','story3'];
      const studentSets = { story1:new Set(), story2:new Set(), story3:new Set() };
      const levelApis = [
        '/src/lib/api/get_level1_quiz_results.php',
        '/src/lib/api/get_level2_quiz_results.php',
        '/src/lib/api/get_level3_quiz_results.php'
      ];
  for (let i=0;i<levelApis.length;i++){
        try{
          // fetch via base
          // levelApis entries were paths; use only the filename with apiUrl
          const filename = levelApis[i].split('/').pop();
          const res = await fetch(apiUrl(filename));
          const jr = await res.json();
          if (jr && jr.success && Array.isArray(jr.data)){
            for (let j=0;j<jr.data.length;j++){
              const row = jr.data[j];
              if (row && row.storyTitle && row.studentID){
                const st = sanitizeForDisplay(row.storyTitle) ?? row.storyTitle;
                if (studentSets[st]) studentSets[st].add(String(row.studentID));
              }
            }
          }
        } catch(e){}
  }
      const quizzesPerStoryArr = stories.map(s => studentSets[s] ? studentSets[s].size : 0);
      quizData = [ { name: 'Quizzes', scores: quizzesPerStoryArr } ];
      studentNames = stories.map(s => s.replace('story','Story '));
      quizzesTaken = quizzesPerStoryArr.reduce((a,b)=>a+b,0);

      // students by level (0..3) and male/female per level
      const levelCounts = { 0:0, 1:0, 2:0, 3:0 };
      maleFemalePerLevel = [ {M:0,F:0}, {M:0,F:0}, {M:0,F:0}, {M:0,F:0} ];
      totalMale = 0; totalFemale = 0;
      for (let i=0;i<students.length;i++){
        const s = students[i];
        const lvl = Number(s.studentLevel) || 0;
        levelCounts[lvl] = (levelCounts[lvl]||0) + 1;
        const g = s.studentGender;
        if (g === 'Male'){ maleFemalePerLevel[lvl].M++; totalMale++; }
        else if (g === 'Female'){ maleFemalePerLevel[lvl].F++; totalFemale++; }
      }
      genderData.series = [levelCounts[0], levelCounts[1], levelCounts[2], levelCounts[3]];
      genderData.labels = ['Level 0','Level 1','Level 2','Level 3'];
      // update genderOptions to use these new labels/series
      genderOptions.series = genderData.series;
      genderOptions.labels = genderData.labels;
      // store per-story unique quiz takers for UI
      quizzesPerStoryUnique = quizzesPerStoryArr;
    } catch (e) {
      console.error('Failed to fetch dashboard data', e);
    }
  }

  // run only in browser to avoid SSR fetches
  if (typeof window !== 'undefined') {
    fetchDashboardData();
    fetchTeacherInfo();
  }

  async function fetchTeacherInfo(){
    try{
  // Use centralized apiUrl helper (no need to build backend base from window.location)
  const res = await fetch(apiUrl('fetch_teacher.php'), { credentials: 'include' });
      const j = await res.json();
      if (j && j.success && j.data){
        settingsName = j.data.name || '';
        settingsEmail = j.data.email || '';
      }
    }catch(e){
      console.warn('Failed to fetch teacher info', e);
    }
  }

  /**
   * Re-aggregate allAttendanceRows into attendanceOptions based on view
   * view - 'today'|'weekly'|'monthly'|'year'
   */
  function aggregateAttendance(view){
    attendanceView = view;
    if (view === 'today') {
      attendanceViewLabel = 'Today';
      // group by hour
      const hours = Array.from({length:24},()=>0);
      for (let i=0;i<allAttendanceRows.length;i++){
        const r = allAttendanceRows[i];
        const d = new Date(r.attendanceDateTime);
        if (isNaN(d.getTime())) continue;
        const today = new Date();
        if (d.toDateString() !== today.toDateString()) continue;
        hours[d.getHours()]++;
      }
      const labels = hours.map((_,i)=> i + ':00');
      attendanceOptions.series = [{ name: 'Attendance', data: hours, color: '#FF5733' }];
      attendanceOptions.xaxis.categories = labels;
      attendanceTotal = hours.reduce((a,b)=>a+b,0);
    } else if (view === 'weekly') {
      attendanceViewLabel = 'This Week';
      // last 7 days
      const lastN = 7;
      const countsByDate = {};
      for (let i=0;i<allAttendanceRows.length;i++){
        const r = allAttendanceRows[i];
        const d = new Date(r.attendanceDateTime);
        if (isNaN(d.getTime())) continue;
        const ymd = d.toISOString().slice(0,10);
        countsByDate[ymd] = (countsByDate[ymd]||0)+1;
      }
      const series = [];
      const labels = [];
      for (let i = lastN-1; i >= 0; i--) {
        const dt = new Date();
        dt.setDate(dt.getDate() - i);
        const ymd = dt.toISOString().slice(0,10);
        labels.push(`${dt.getMonth()+1}/${dt.getDate()}`);
        series.push(countsByDate[ymd] || 0);
      }
      attendanceOptions.series = [{ name: 'Attendance', data: series, color: '#FF5733' }];
      attendanceOptions.xaxis.categories = labels;
      attendanceTotal = series.reduce((a,b)=>a+b,0);
    } else if (view === 'monthly') {
      attendanceViewLabel = 'This Month';
      // group by day for current month
      const now = new Date();
      const year = now.getFullYear();
      const month = now.getMonth();
      const daysInMonth = new Date(year, month+1, 0).getDate();
      const counts = Array.from({length:daysInMonth},()=>0);
      for (let i=0;i<allAttendanceRows.length;i++){
        const r = allAttendanceRows[i];
        const d = new Date(r.attendanceDateTime);
        if (isNaN(d.getTime())) continue;
        if (d.getFullYear()===year && d.getMonth()===month){
          counts[d.getDate()-1]++;
        }
      }
      const labels = counts.map((_,i)=> String(i+1));
      attendanceOptions.series = [{ name: 'Attendance', data: counts, color: '#FF5733' }];
      attendanceOptions.xaxis.categories = labels;
      attendanceTotal = counts.reduce((a,b)=>a+b,0);
    } else if (view === 'year') {
      attendanceViewLabel = 'This Year';
      // group by month
      const year = new Date().getFullYear();
      const counts = Array.from({length:12},()=>0);
      for (let i=0;i<allAttendanceRows.length;i++){
        const r = allAttendanceRows[i];
        const d = new Date(r.attendanceDateTime);
        if (isNaN(d.getTime())) continue;
        if (d.getFullYear()===year){
          counts[d.getMonth()]++;
        }
      }
      const labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      attendanceOptions.series = [{ name: 'Attendance', data: counts, color: '#FF5733' }];
      attendanceOptions.xaxis.categories = labels;
      attendanceTotal = counts.reduce((a,b)=>a+b,0);
    }
  }

  function toggleMenu() {
    showMenu = !showMenu;
  }

  function navigateToProfile() {
    goto("/admin/profile");
    showMenu = false;
  }

  function logout() {
    console.log("User logged out");
    goto("../");
  }

  async function showQuizStudents(index){
    const story = ['story1','story2','story3'][index];
    quizModalTitle = studentNames[index] || story;
    quizModalStudents = [];
    // fetch level results and collect distinct student info
    const apis = ['/src/lib/api/get_level1_quiz_results.php','/src/lib/api/get_level2_quiz_results.php','/src/lib/api/get_level3_quiz_results.php'];
    const studentIds = new Set();
  // legacy base declaration removed; apiUrl(...) is used instead
    for (let i=0;i<apis.length;i++){
      try{
  const r = await fetch(apiUrl(apis[i].split('/').pop()) + '?storyTitle=' + encodeURIComponent(story));
        const j = await r.json();
        if (j && j.success && Array.isArray(j.data)){
          for (let k=0;k<j.data.length;k++){
            const row = j.data[k];
            if (row && row.studentID && !studentIds.has(String(row.studentID))){
              studentIds.add(String(row.studentID));
              quizModalStudents.push({ id: row.studentID, name: row.studentName, idNo: row.idNo });
            }
          }
        }
      }catch(e){/*ignore*/}
    }
    showQuizModal = true;
  }
</script>

<div class="h-screen flex flex-col">
  <!-- Main Content -->
  <div class="flex-1 flex flex-col">
    <!-- Fixed Top Bar -->
    <div
      class="fixed top-0 left-64 w-[calc(100%-16rem)] bg-orange-500 text-white px-6 flex justify-between items-center shadow-md z-50 h-16"
    >
      <h1 class="text-xl font-semibold">Dashboard</h1>

      <!-- Avatar & Dropdown -->
      <div class="relative p-2">
        <button
          class="w-10 h-10 rounded-full overflow-hidden border-2 border-white"
          on:click={toggleMenu}
        >
          <img
            src="/avatar.jpg"
            alt="User Avatar"
            class="w-full h-full rounded-full border-4 border-gray-300 object-cover"
          />
        </button>

        {#if showMenu}
          <div
            class="absolute right-0 mt-2 w-48 bg-white text-black shadow-lg rounded-md py-2"
          >
            <button
              on:click={navigateToProfile}
              class="block w-full text-left px-4 py-2 hover:bg-gray-200"
              >Profile</button
            >
            <button
              on:click={() => (showSettingsModal = true)}
              class="block w-full text-left px-4 py-2 hover:bg-gray-200"
              >Settings</button
            >
            <button
              class="block w-full text-left px-4 py-2 hover:bg-gray-200"
              on:click={() => (showLogoutModal = true)}>Logout</button
            >
          </div>
        {/if}
      </div>
    </div>

    <!-- Top summary row -->
    <div class="mt-16 px-4 w-[calc(100%-1rem)]">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 justify-items-center">
        <div class="w-full md:w-9/12 bg-blue-600 p-3 rounded-xl shadow-md text-white">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm opacity-90">Total Students</div>
              <h2 class="text-3xl font-bold"><span use:tweenNumber={{from:0,to:studentCount,duration:900}}>{studentCount}</span></h2>
            </div>
            <UsersGroupSolid class="w-12 h-12 opacity-90" />
          </div>
        </div>
  <div class="w-full md:w-9/12 bg-white p-3 rounded-xl shadow-md">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xs text-gray-500">Male</div>
              <div class="text-2xl font-semibold"><span use:tweenNumber={{from:0,to:totalMale,duration:900}}>{totalMale}</span></div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Female</div>
              <div class="text-2xl font-semibold"><span use:tweenNumber={{from:0,to:totalFemale,duration:900}}>{totalFemale}</span></div>
            </div>
            <NewspaperSolid class="w-10 h-10 text-gray-400" />
          </div>
        </div>
  </div>

  <!-- Main content: two-column layout -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4 items-start">
        <!-- Main column (span 2 on large screens) -->
        <div class="lg:col-span-2 space-y-4">
          <div class="bg-yellow-50 p-4 rounded-xl shadow-md">
            <div class="flex items-center justify-between border-b pb-2">
              <h3 class="text-lg font-semibold text-gray-800">Attendance Trend</h3>
              <BookOpenOutline class="w-5 h-5 text-gray-500" />
            </div>
            <div class="py-3">
              <div class="flex items-center justify-between mb-2">
                <div>
                  <div class="text-sm text-gray-600">{attendanceViewLabel}</div>
                  <div class="text-2xl font-bold"><span use:tweenNumber={{from:0,to: attendanceTotal,duration:900}}>{attendanceTotal}</span></div>
                </div>
                <div class="text-sm text-gray-500">(Aggregated view)</div>
              </div>
              <div class="flex gap-2 mb-3">
                <button class="view-btn" on:click={()=>aggregateAttendance('today')} class:active={attendanceView==='today'}>Today</button>
                <button class="view-btn" on:click={()=>aggregateAttendance('weekly')} class:active={attendanceView==='weekly'}>Weekly</button>
                <button class="view-btn" on:click={()=>aggregateAttendance('monthly')} class:active={attendanceView==='monthly'}>Monthly</button>
                <button class="view-btn" on:click={()=>aggregateAttendance('year')} class:active={attendanceView==='year'}>Year</button>
              </div>
              <Chart options={{...attendanceOptions, chart: {...attendanceOptions.chart, height: 200}}} />
            </div>
          </div>

          <!-- Quizzes Overview removed per request -->
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <div class="bg-white p-3 rounded-xl shadow-md">
            <div class="flex justify-between items-center border-b pb-2">
              <h3 class="text-base font-semibold text-gray-800">Students by Level</h3>
              <UsersGroupSolid class="w-5 h-5 text-gray-500" />
            </div>
            <div class="py-2">
              <Chart options={genderOptions} />
              <div class="mt-2">
                <table class="w-full text-sm">
                  <thead><tr><th class="text-left">Level</th><th class="text-right">Male</th><th class="text-right">Female</th></tr></thead>
                  <tbody>
                    {#each maleFemalePerLevel as mf, i}
                      <tr>
                          <td class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" style="background:{levelColors[i]}"></span>
                            <span>L{i}</span>
                          </td>
                        <td class="text-right">{mf.M}</td>
                        <td class="text-right">{mf.F}</td>
                      </tr>
                    {/each}
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="bg-white p-3 rounded-xl shadow-md">
            <div class="flex justify-between items-center border-b pb-2 mb-2">
              <h3 class="text-base font-semibold text-gray-800">Recent Attendance</h3>
              <div class="text-sm text-gray-500">Latest {recentAttendance.length}</div>
            </div>
            <div class="space-y-2 max-h-80 overflow-auto">
              {#each recentAttendance as row}
                <div class="flex items-center justify-between px-2 py-1 border rounded">
                  <div>
                    <div class="text-sm font-medium">{row.studentName} <span class="text-xs text-gray-500">({row.idNo || '—'})</span></div>
                    <div class="text-xs text-gray-500">{new Date(row.attendanceDateTime).toLocaleString()}</div>
                  </div>
                  <div class="text-sm text-gray-600">Level {row.studentLevel ?? 0}</div>
                </div>
              {/each}
              {#if recentAttendance.length === 0}
                <div class="text-center text-sm text-gray-500">No recent attendance records</div>
              {/if}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Logout Confirmation Modal -->
<Modal bind:open={showLogoutModal} size="xs" autoclose>
  <div class="text-center">
    <ExclamationCircleOutline
      class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200"
    />
    <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
      Are you sure you want to logout?
    </h3>
    <Button on:click={logout} color="red" class="me-2">Yes, I'm sure</Button>
    <Button on:click={() => (showLogoutModal = false)} color="alternative"
      >No, cancel</Button
    >
  </div>
</Modal>

<!-- Settings Modal -->
<SettingsModal bind:open={showSettingsModal} bind:name={settingsName} bind:email={settingsEmail} />

<!-- Quiz Students Modal -->
<Modal bind:open={showQuizModal} size="md">
  <div class="p-4">
    <h3 class="text-lg font-semibold mb-2">Students who took {quizModalTitle}</h3>
    <div class="max-h-64 overflow-auto">
      {#if quizModalStudents.length}
        <ul class="space-y-2">
          {#each quizModalStudents as s}
            <li class="flex justify-between items-center border rounded p-2">
              <div>
                <div class="font-medium">{s.name}</div>
                <div class="text-xs text-gray-500">{s.idNo || '—'}</div>
              </div>
              <div class="text-sm text-gray-600">ID: {s.id}</div>
            </li>
          {/each}
        </ul>
      {:else}
        <div class="text-sm text-gray-500">No students found for this quiz.</div>
      {/if}
    </div>
    <div class="mt-3 text-right">
      <Button on:click={() => (showQuizModal = false)}>Close</Button>
    </div>
  </div>
</Modal>

<style>
  /* (podium styles removed) */
  .view-btn{
    padding:0.4rem 0.6rem;
    background: white;
    border-radius:0.375rem;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    font-size:0.9rem;
    border: 1px solid transparent;
  }
  .view-btn.active, .view-btn:hover{ background:#f3f4f6; border-color:#e5e7eb; }
  /* legend removed; table rows now show compact color badges */
</style>