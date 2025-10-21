<script lang="ts">
  import { onMount } from "svelte";
  import { writable, get } from "svelte/store";
  import { studentData } from '$lib/store/store_students';
  import Modal from "../modals/view_student.svelte";
  import AddModal from "../modals/add_student.svelte";

  // Define writable stores
  let selectedGender = writable("All");
  let searchQuery = writable("");
  let attendees = writable([]);
  let sortKey = writable("studentName");
  let sortOrder = writable("asc");
  let showModal = writable(false);
  let selectedPerson = writable(null);
  let showAddModal = writable(false);

  // Gender filter options
  const genders = ["All", "Male", "Female"];

  // Table headers configuration
  const tableHeaders = [
    { key: "idNo", label: "Student ID" },
    { key: "studentName", label: "Name" },
    { key: "studentGender", label: "Gender" },
    { key: "studentLevel", label: "Level" },
    { key: "studentRibbon", label: "Ribbon" },
    { key: "studentColtrash", label: "Collected Trash" }
  ];

  // Fetch student data on component mount
  onMount(async () => {
    await refreshStudentData();
    await refreshEditStudentData();
  });

  // Function to fetch and refresh student data
  async function refreshStudentData() {
    try {
      const response = await fetch("http://localhost/shenieva-teacher/src/lib/api/fetch_students.php");
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      
      let data = await response.json();
      // Sort alphabetically by studentName (A → Z)
      data.sort((a, b) => a.studentName.localeCompare(b.studentName));
      
      studentData.set(data);
      attendees.set(data);
    } catch (error) {
      console.error("❌ Error fetching student data:", error);
      studentData.set([]);
      attendees.set([]);
    }
  }
  async function refreshEditStudentData() {
    try {
      const response = await fetch("http://localhost/shenieva-teacher/src/lib/api/fetch_students.php");
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      
      let data = await response.json();
      
      studentData.set(data);
      attendees.set(data);
    } catch (error) {
      console.error("❌ Error fetching student data:", error);
      studentData.set([]);
      attendees.set([]);
    }
  }

  // Filter data based on search query and gender
  function filterData() {
    const query = get(searchQuery).toLowerCase();
    const gender = get(selectedGender);

    attendees.set(
      get(studentData).filter(student => {
        return (
          (gender === "All" || student.studentGender === gender) &&
          student.studentName.toLowerCase().includes(query)
        );
      })
    );
  }

  // Sort table by column
  function sortBy(key: string) {
    if (get(sortKey) === key) {
      sortOrder.update(order => (order === "asc" ? "desc" : "asc"));
    } else {
      sortKey.set(key);
      sortOrder.set("asc");
    }

    attendees.update(items => {
      return [...items].sort((a, b) => {
        let valA = a[key];
        let valB = b[key];
        if (typeof valA === "string") {
          return valA.localeCompare(valB) * (get(sortOrder) === "asc" ? 1 : -1);
        }
        return (valA - valB) * (get(sortOrder) === "asc" ? 1 : -1);
      });
    });
  }

  // Open modal with selected student (including studentId)
  function openModal(person) {
    console.log("Opening modal for:", person); // Debug: Check if studentId exists
    selectedPerson.set(person); // Pass entire student object, including studentId
    showModal.set(true);
  }

  // Close modal
  async function closeModal() {
    showModal.set(false);
    selectedPerson.set(null); // Reset selected person
    await refreshEditStudentData();
  }

  // Open add student modal
  function openAddModal() {
    showAddModal.set(true);
  }

  // Close add student modal and refresh data
  async function closeAddModal() {
    showAddModal.set(false);
    await refreshStudentData(); // Refresh student list after adding
  }
</script>

<div class="text-gray-500 font-bold text-2xl pl-10">
  <h1>Students Management</h1>
</div>

<div class="p-6 max-w-6xl mx-auto bg-white rounded-xl shadow-lg">
  <div class="flex items-center gap-4 mb-6">
    <!-- Search input -->
    <input 
      type="text" 
      bind:value={$searchQuery} 
      on:input={filterData} 
      placeholder="Search by name..." 
      class="w-80 p-2 border border-gray-300 rounded-md text-gray-700 shadow-sm focus:ring-2 focus:ring-lime-500" 
    />
    
    <!-- Gender filter -->
    <select 
      bind:value={$selectedGender} 
      on:change={filterData}
      class="p-2 w-22 border border-gray-300 rounded-md text-gray-700 shadow-sm focus:ring-2 focus:ring-lime-500"
    >
      {#each genders as gender}
        <option value={gender}>{gender}</option>
      {/each}
    </select>
    
    <!-- Add button -->
    <button 
      on:click={openAddModal} 
      class="ml-auto bg-orange-500 text-white px-5 py-2 rounded-lg hover:bg-orange-400 shadow"
    >
      + AddStudent
    </button>
  </div>

  <!-- Table -->
  <div class="max-h-[75vh] overflow-y-auto w-full">
    <table class="w-full border-collapse bg-white rounded-lg text-sm">
      <thead class="sticky top-0 z-10 bg-lime-500 text-white">
        <tr>
          {#each tableHeaders as header}
            <th 
              class="p-2 cursor-pointer hover:bg-lime-400 transition" 
              on:click={() => sortBy(header.key)}
            >
              {header.label}
              <span class="ml-1 text-xs">
                {($sortKey === header.key && $sortOrder === "asc") ? "▲" : "▼"}
              </span>
            </th>
          {/each}
        </tr>
      </thead>
      <tbody class="divide-y">
        {#each $attendees as student}
          <tr 
            class="border border-black/10 border-x-0 bg-gray-50 hover:bg-orange-200 transition" 
            on:click={() => openModal(student)}
          >
            <td class="p-2 text-center font-mono text-sm">{student.idNo}</td>
            <td class="p-2">{student.studentName}</td>
            <td class="p-2 flex justify-center items-center">
              {#if student.studentGender === 'Male'}
                <svg class="w-5 h-5 text-blue-500" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M14 2h8v8h-2V5.414l-4.293 4.293a7 7 0 1 1-1.414-1.414L18.586 4H14V2ZM5 11a5 5 0 1 0 10 0 5 5 0 0 0-10 0Z"/>
                </svg>
              {:else if student.studentGender === 'Female'}
                <svg class="w-5 h-5 text-pink-500" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2a7 7 0 1 1-1 13.93V18h2v-2.07A7 7 0 0 1 12 2Zm0 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm-1 6v-2h2v2h3v2H8v-2h3Z"/>
                </svg>
              {/if}
            </td>
            <td class="p-2 text-center">{student.studentLevel}</td>
            <td class="p-2 text-center">{student.studentRibbon}</td>
            <td class="p-2 text-center">{student.studentColtrash}</td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</div>

<!-- Modals -->
{#if $showModal}
  <Modal selectedPerson={$selectedPerson} on:close={closeModal} />
{/if}

{#if $showAddModal}
  <AddModal on:close={closeAddModal} />
{/if}