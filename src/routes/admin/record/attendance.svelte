<script>
  import { onMount } from "svelte";
  import DatePicker from '$lib/components/DatePicker.svelte';
    import { writable, get } from "svelte/store";
  import ViewStudentModal from '../modals/view_student.svelte';
  
    let selectedGender = "All";
  // default to empty so we show all records until a date is chosen
  let date = "";
  let useDateRange = false;
  let showSingle = true;
  let dateFrom = "";
  let dateTo = "";
    let attendees = writable([]);
    let sortKey = writable("name");
    let sortOrder = writable("asc");
  
    const genders = ["All", "Male", "Female"];
  
    const tableHeaders = [
      { key: "name", label: "Name" },
      { key: "idNo", label: "Student ID" },
      { key: "gender", label: "Gender" },
      { key: "datetime", label: "Date & Time" },
      { key: "studentLevel", label: "Level" },
    ];
  
  let data = [];
  let fetchError = '';
  let rowsLoaded = 0;
  let showStudentModal = false;
  let selectedPerson = null;
    // date variables (ISO YYYY-MM-DD) used for filtering


  
    /** Filter data according to selectedGender and date/date range */
    function filterData() {
      attendees.set(
        // @ts-ignore
        data.filter(person => {
          // person.datetime is expected to be 'Y-m-d H:i:s' or similar; parseDate will handle it
          const personDate = person.datetime;
          const genderMatch = (selectedGender === "All" || person.gender === selectedGender);

          // no date filter selected -> show all
          if (!useDateRange && !date) return genderMatch;

          if (!useDateRange) {
            const p = parseDate(personDate);
            const d = parseDate(date);
            if (!p || !d) return false;
            d.setHours(0,0,0,0);
            p.setHours(0,0,0,0);
            return genderMatch && (p.getTime() === d.getTime());
          }

          // date range: if either from/to not set, treat accordingly
          if (useDateRange && dateFrom && dateTo) {
            const from = parseDate(dateFrom);
            const to = parseDate(dateTo);
            const p = parseDate(personDate);
            if (!from || !to || !p) return false;
            from.setHours(0,0,0,0);
            to.setHours(23,59,59,999);
            return genderMatch && (p >= from && p <= to);
          }

          // if range enabled but missing ends, fallback to gender match
          return genderMatch;
        })
      );
    }
  
    /**
   * @param {string} key
   */
    function sortBy(key) {
      sortKey.set(key);
      sortOrder.update(o => (o === "asc" ? "desc" : "asc"));
  
      attendees.update(items => {
        return [...items].sort((a, b) => {
          /**
           * @type {string | number}
           */
          let valA = a[key];
          /**
           * @type {number}
           */
          let valB = b[key];
  
          if (typeof valA === "string") {
            // @ts-ignore
            return valA.localeCompare(valB) * (get(sortOrder) === "asc" ? 1 : -1);
          } else {
            return (valA - valB) * (get(sortOrder) === "asc" ? 1 : -1);
          }
        });
      });
    }
  
    onMount(() => {
      // fetch real attendance rows from the server
      fetch('http://localhost/shenieva-teacher/src/lib/api/fetch_all_attendance.php')
        .then(r => r.json())
        .then(rows => {
          console.log('attendance rows received', rows.length, rows.slice(0,5));
          // map server rows to UI-friendly objects including idNo and gender
          data = rows.map(r => ({
            // keep both legacy and new keys so filtering/sorting works
            name: r.studentName,
            studentName: r.studentName,
            datetime: r.attendanceDateTime,
            progress: Number(r.progress ?? 0),
            studentLevel: r.studentLevel ?? '',
            idNo: r.idNo,
            gender: r.gender,
            studentGender: r.gender,
            pk_studentID: r.fk_studentID
          }));
          rowsLoaded = data.length;
          filterData();
        })
        .catch(err => {
          console.error('Failed to fetch attendance', err);
          fetchError = String(err);
          filterData();
        });
    });

      // keep showSingle in sync with useDateRange and clear values when toggling
      $: showSingle = !useDateRange;
      $: if (useDateRange) {
        // when switching to range, clear single date
        date = '';
      } else {
        // when switching off range, clear the range
        dateFrom = '';
        dateTo = '';
      }

    // Format a date string like '2025-03-09 14:30' to MM/DD/YYYY
    function fmtDisplayDate(dtString) {
      if (!dtString) return '';
      const d = parseDate(dtString);
      if (!d || isNaN(d.getTime())) return dtString;
      const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
    }

  // DatePicker binds directly to ISO date variables; no extra sync required

    // Format date+time to MM/DD/YYYY h:mm AM/PM for table display
    function fmtDisplayDateTime(dtString) {
      if (!dtString) return '';
      const d = parseDate(dtString);
      if (!d || isNaN(d.getTime())) return dtString;
      const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      let hh = d.getHours();
      const min = String(d.getMinutes()).padStart(2, '0');
      const ampm = hh >= 12 ? 'PM' : 'AM';
      hh = hh % 12;
      if (hh === 0) hh = 12;
      return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()} ${hh}:${min} ${ampm}`;
    }

    // Parse many date formats into a Date object (date-only or timestamp)
    function parseDate(input) {
      if (!input && input !== 0) return null;
      const s = String(input).trim();
      // numeric timestamp (seconds or ms)
      if (/^\d+$/.test(s)) {
        const n = Number(s);
        return new Date(n > 1e12 ? n : n * 1000);
      }

      // ISO or datetime with space
      // if contains '/', assume MM/DD/YYYY or similar
      if (s.includes('/')) {
        // mm/dd/yyyy or dd/mm/yyyy — assume mm/dd/yyyy for this app
        const parts = s.split(/[\/ ]/)[0].split('/');
        if (parts.length === 3) {
          const mm = Number(parts[0]);
          const dd = Number(parts[1]);
          const yyyy = Number(parts[2]);
          return new Date(yyyy, mm - 1, dd);
        }
      }

      // try to extract YYYY-MM-DD HH:MM:SS or YYYY-MM-DD from strings
      const mFull = s.match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/);
      if (mFull) {
        const yy = Number(mFull[1]);
        const mo = Number(mFull[2]) - 1;
        const dd = Number(mFull[3]);
        const hh = Number(mFull[4]);
        const mi = Number(mFull[5]);
        const ss = mFull[6] ? Number(mFull[6]) : 0;
        return new Date(yy, mo, dd, hh, mi, ss);
      }

      const m = s.match(/(\d{4})-(\d{2})-(\d{2})/);
      if (m) {
        return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
      }

      // fallback to Date constructor
      const d = new Date(s);
      return isNaN(d.getTime()) ? null : d;
    }

    // Convert ISO (YYYY-MM-DD) to MM/DD/YYYY
    function isoToMmdd(iso) {
      if (!iso) return '';
      const m = iso.match(/(\d{4})-(\d{2})-(\d{2})/);
      if (!m) return '';
      return `${m[2]}/${m[3]}/${m[1]}`;
    }

    // Convert MM/DD/YYYY to ISO (YYYY-MM-DD); returns '' on invalid
    function mmddToIso(txt) {
      if (!txt) return '';
      const parts = String(txt).trim().split('/');
      if (parts.length !== 3) return '';
      const mm = Number(parts[0]);
      const dd = Number(parts[1]);
      const yyyy = Number(parts[2]);
      if (!mm || !dd || !yyyy) return '';
      // basic validation
      if (mm < 1 || mm > 12 || dd < 1 || dd > 31) return '';
      return `${yyyy}-${String(mm).padStart(2,'0')}-${String(dd).padStart(2,'0')}`;
    }

    // derive numeric level (0..3) and progress bar width
    function deriveLevel(person) {
      const prog = Number(person.progress ?? 0);
      let level = 0;

      // treat explicit studentLevel if provided (including '0')
      if (
        person.studentLevel !== undefined &&
        person.studentLevel !== null &&
        String(person.studentLevel).trim() !== ""
      ) {
        const n = Number(person.studentLevel);
        level = Number.isFinite(n) ? n : 0;
      } else {
        // map progress buckets to levels: <40 -> 0, 40-59 ->1, 60-79 ->2, >=80 ->3
        if (prog < 40) level = 0;
        else if (prog < 60) level = 1;
        else if (prog < 80) level = 2;
        else level = 3;
      }

      // Compute width: prefer explicit progress when it's > 0, otherwise fall back
      // to a reasonable level-derived percentage so Level 1/2 show a visible bar
      // even when historical progress is missing (progress == 0).
      let width;
      if (prog > 0) {
        width = Math.max(0, Math.min(100, prog));
      } else {
        // level -> percentage mapping (approximate): 0->0%, 1->33%, 2->66%, 3->100%
        const map = [0, 33, 66, 100];
        const idx = Math.min(Math.max(0, level), 3);
        width = map[idx];
      }

      return { level, width };
    }

    async function fetchStudentDetails(pk_studentID) {
      if (!pk_studentID) return null;
      try {
        const res = await fetch('http://localhost/shenieva-teacher/src/lib/api/fetch_students.php');
        if (!res.ok) return null;
        const arr = await res.json();
        if (!Array.isArray(arr)) return null;
        return arr.find(s => String(s.pk_studentID) === String(pk_studentID)) || null;
      } catch (e) {
        console.error('Failed to fetch student details', e);
        return null;
      }
    }
  </script>
  
  <div class="p-6 max-w-6xl mx-auto bg-white rounded-xl shadow-lg">
    <div class="flex items-center gap-4 mb-6">
      <!-- Gender Filter -->
      <select bind:value={selectedGender} on:change={filterData}
        class="p-2 w-22 border border-gray-300 rounded-md text-gray-700 shadow-sm focus:ring-2 focus:ring-lime-500">
        {#each genders as gender}
          <option value={gender}>{gender}</option>
        {/each}
      </select>
  
      <!-- Date Filter: single date or range -->
      <div class="flex items-center gap-2">
        <label class="flex items-center gap-2">
          <input type="checkbox" bind:checked={useDateRange} on:change={filterData} />
          <span class="text-sm">Use date range</span>
        </label>

        {#if showSingle}
          <DatePicker bind:value={date} on:change={() => { filterData(); }} />
          <span class="ml-2 text-sm text-gray-600">{date ? fmtDisplayDate(date) : ''}</span>
        {:else}
          <DatePicker bind:value={dateFrom} on:change={() => { if(dateTo && dateTo < dateFrom) dateTo = dateFrom; filterData(); }} />
          <span class="text-sm">to</span>
          <DatePicker bind:value={dateTo} on:change={() => { if(dateFrom && dateTo < dateFrom) dateTo = dateFrom; filterData(); }} />
          <span class="ml-2 text-sm text-gray-600">{dateFrom ? fmtDisplayDate(dateFrom) : ''} {dateTo ? ' — ' + fmtDisplayDate(dateTo) : ''}</span>
        {/if}
      </div>
      <div class="ml-4 text-sm text-gray-500">
        <div>Rows: {rowsLoaded}</div>
        {#if fetchError}
          <div class="text-red-500">Error: {fetchError}</div>
        {/if}
      </div>
    </div>
  
    <!-- <div class="overflow-x-auto rounded-lg shadow-md"> -->
    <div class="max-h-[65vh] overflow-y-auto w-full">
      <table class="w-full border-collapse bg-white rounded-lg text-sm">
        <thead class="sticky top-0 z-10">
          <tr class="bg-lime-500 text-white">
            {#each tableHeaders as header}
              <th class="p-2 cursor-pointer hover:bg-lime-500 transition" on:click={() => sortBy(header.key)}>
                {header.label}
                <span class="ml-1 text-xs">{($sortKey === header.key && $sortOrder === "asc") ? "▲" : "▼"}</span>
              </th>
            {/each}
          </tr>
        </thead>
          <tbody>
          {#each $attendees as person}
            <tr class="border-b last:border-none bg-gray-50 hover:bg-orange-100 transition cursor-pointer" on:click={async () => {
                // fetch full student info if available
                console.log('Opening student modal for', person);
                try {
                  const details = await fetchStudentDetails(person.pk_studentID);
                  console.log('Fetched details', details);
                  selectedPerson = { ...person, ...(details || {}) };
                } catch(e) {
                  console.error('Error fetching student details', e);
                  selectedPerson = person;
                }
                showStudentModal = true;
              }}>
              <td class="p-2">{person.studentName}</td>
              <td class="p-2 text-center">{person.idNo}</td>
              <!-- <td class="p-2">{person.studentGender}</td> -->
              <td class="p-2 flex items-center justify-center space-x-2">
                {#if person.studentGender === 'Male'}
                  <svg class="w-5 h-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14 2h8v8h-2V5.414l-4.293 4.293a7 7 0 1 1-1.414-1.414L18.586 4H14V2ZM5 11a5 5 0 1 0 10 0 5 5 0 0 0-10 0Z"/>
                  </svg>
                {/if}
                {#if person.studentGender === 'Female'}
                  <svg class="w-5 h-5 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2a7 7 0 1 1-1 13.93V18h2v-2.07A7 7 0 0 1 12 2Zm0 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm-1 6v-2h2v2h3v2H8v-2h3Z"/>
                  </svg>
                {/if}
              </td>
                          
              <td class="p-2 text-center justify-center">{fmtDisplayDateTime(person.datetime)}</td>
              <td class="p-2 text-center">
                <div class="flex flex-col items-center gap-1">
                  <div class="font-semibold">{deriveLevel(person).level ?? 0}</div>
                  <div class="w-full bg-gray-300 rounded-xl h-4 relative flex items-center text-xs text-gray-700 z-0 max-w-[120px]">
                    <div class="absolute left-0 h-4 rounded-xl" style="width: {deriveLevel(person).width}%; background: linear-gradient(to right, #f97316, #84cc16)"></div>
                    <!-- removed percentage overlay per request -->
                  </div>
                </div>
              </td>
            </tr>
          {/each}
        </tbody>
      </table>
    </div>
  </div>
    {#if showStudentModal}
      <ViewStudentModal selectedPerson={selectedPerson} on:close={() => { showStudentModal = false; selectedPerson = null; }} />
    {/if}
  