<!-- src/routes/admin/quiz3.svelte -->
<script>
  import { onMount } from "svelte";
  import { writable, get } from "svelte/store";
  import QuizModal from "../modals/quizModal.svelte";
  import SuccessModal from "../modals/successModal.svelte";

  let selectedGender = "All";
  let date = new Date().toISOString().split("T")[0];
  let attendees = writable([]);
  let sortKey = writable("name");
  let sortOrder = writable("asc");
  let showModal = writable(false);
  let showSuccessModal = writable(false);
  let selectedPerson = writable(null);

  const genders = ["All", "Male", "Female"];

  const tableHeaders = [
    { key: "name", label: "Name" },
    { key: "gender", label: "Gender" },
    { key: "datetime", label: "Date & Time" },
    { key: "score", label: "Score" },
    { key: "attempts", label: "Attempts" },
    { key: "status", label: "Status" },
  ];

  const quiz3Questions = [
    { text: "Describe the impact of climate change on polar bears." },
    { text: "Explain the significance of the Industrial Revolution." },
    { text: "Discuss the role of photosynthesis in the ecosystem." },
  ];

  const data = [
    {
      name: "Alice Gou",
      gender: "Female",
      datetime: "2025-03-04 09:00",
      score: null, // Unscored initially
      attempts: 2,
      status: "Pending",
      questions: [
        {
          ...quiz3Questions[0],
          studentResponse: "Climate change melts ice, reducing polar bear habitats and food sources.",
          points: null,
        },
        {
          ...quiz3Questions[1],
          studentResponse: "The Industrial Revolution boosted production but caused pollution.",
          points: null,
        },
        {
          ...quiz3Questions[2],
          studentResponse: "Photosynthesis provides oxygen and energy for all life.",
          points: null,
        },
      ],
    },
    {
      name: "Bob Ackerman",
      gender: "Male",
      datetime: "2025-03-04 10:30",
      score: null,
      attempts: 1,
      status: "Pending",
      questions: [
        {
          ...quiz3Questions[0],
          studentResponse: "Polar bears lose ice and starve.",
          points: null,
        },
        {
          ...quiz3Questions[1],
          studentResponse: "It made machines and factories big.",
          points: null,
        },
        {
          ...quiz3Questions[2],
          studentResponse: "Plants make food and oxygen.",
          points: null,
        },
      ],
    },
    {
      name: "Charlie Johnson",
      gender: "Male",
      datetime: "2025-03-04 11:15",
      score: null,
      attempts: 3,
      status: "Pending",
      questions: [
        {
          ...quiz3Questions[0],
          studentResponse: "Ice melts, polar bears can’t hunt seals.",
          points: null,
        },
        {
          ...quiz3Questions[1],
          studentResponse: "Industrial Revolution changed society with tech.",
          points: null,
        },
        {
          ...quiz3Questions[2],
          studentResponse: "Photosynthesis is key for plant energy.",
          points: null,
        },
      ],
    },
  ];

  function filterData() {
    attendees.set(
      data.filter((person) => {
        let personDate = person.datetime.split(" ")[0];
        return selectedGender === "All" || person.gender === selectedGender;
      })
    );
  }

  function sortBy(key) {
    sortKey.set(key);
    sortOrder.update((o) => (o === "asc" ? "desc" : "asc"));
    attendees.update((items) => {
      return [...items].sort((a, b) => {
        let valA = a[key];
        let valB = b[key];
        if (typeof valA === "string") {
          return valA.localeCompare(valB) * (get(sortOrder) === "asc" ? 1 : -1);
        } else {
          return (valA - valB) * (get(sortOrder) === "asc" ? 1 : -1);
        }
      });
    });
  }

  function openModal(person) {
    console.log("Opening modal for:", person);
    selectedPerson.set(person);
    showModal.set(true);
  }

  function closeModal() {
    console.log("Closing modal");
    showModal.set(false);
    selectedPerson.set(null);
  }

  function markAsReviewed() {
    console.log("Marking as reviewed:", $selectedPerson.name);
    $selectedPerson.status = "Reviewed";
    showSuccessModal.set(true);
  }

  function closeSuccessModal() {
    console.log("Closing success modal");
    showSuccessModal.set(false);
    closeModal();
  }

  onMount(() => {
    filterData();
  });
</script>

<div class="p-10 mb-14 mt-5 max-w-6xl mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden">
  <div class="flex items-center gap-4 mb-6 px-4">
    <select
      bind:value={selectedGender}
      on:change={filterData}
      class="p-2 w-24 border border-gray-300 rounded-lg text-gray-700 shadow-sm focus:ring-2 focus:ring-lime-500 bg-lime-200 cursor-pointer"
    >
      {#each genders as gender}
        <option value={gender}>{gender}</option>
      {/each}
    </select>
  </div>

  <div class="w-full mx-4 mb-4 rounded-3xl overflow-hidden shadow-lg bg-white">
    <table class="w-full text-sm">
      <thead class="sticky top-0 z-10 bg-lime-500 text-white">
        <tr>
          {#each tableHeaders as header, index}
            <th
              class="p-4 cursor-pointer hover:bg-lime-600 transition-all duration-200
                {index === 0 ? 'rounded-tl-3xl' : ''} 
                {index === tableHeaders.length - 1 ? 'rounded-tr-3xl' : ''}"
              on:click={() => sortBy(header.key)}
            >
              <span class="flex items-center justify-center gap-1">
                {header.label}
                <span class="text-xs"
                  >{$sortKey === header.key && $sortOrder === "asc"
                    ? "▲"
                    : "▼"}</span
                >
              </span>
            </th>
          {/each}
        </tr>
      </thead>
      <tbody>
        {#each $attendees as person, rowIndex}
          <tr
            class="hover:bg-orange-50 transition-all duration-200 cursor-pointer"
            on:click={() => openModal(person)}
          >
            <td
              class="p-4 font-medium 
                {rowIndex === $attendees.length - 1 ? 'rounded-bl-3xl' : ''}"
            >
              {person.name}
            </td>
            <td class="p-4 flex items-center justify-center">
              {#if person.gender === "Male"}
                <img
                  src="/assets/icons/male-sign.svg"
                  alt="Male Icon"
                  class="w-5 h-5"
                />
              {/if}
              {#if person.gender === "Female"}
                <img
                  src="/assets/icons/female-sign.svg"
                  alt="Female Icon"
                  class="w-5 h-5"
                />
              {/if}
            </td>
            <td class="p-4 text-center">{person.datetime}</td>
            <td class="p-4 text-center">
              <span
                class="inline-block px-3 py-1 rounded-full text-sm font-medium
                {person.score === null
                  ? 'bg-gray-100 text-gray-800'
                  : person.score >= 24
                    ? 'bg-green-100 text-green-800'
                    : person.score >= 18
                      ? 'bg-orange-100 text-orange-800'
                      : 'bg-red-100 text-red-800'}"
              >
                {person.score === null ? "Unscored" : `${person.score}/30`}
              </span>
            </td>
            <td class="p-4 text-center">{person.attempts}</td>
            <td
              class="p-4 text-center 
                {rowIndex === $attendees.length - 1 ? 'rounded-br-3xl' : ''}"
            >
              <span
                class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800"
              >
                Pending
              </span>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</div>

<QuizModal
  person={$selectedPerson}
  {showModal}
  {closeModal}
  {markAsReviewed}
/>

<SuccessModal
  showSuccessModal={$showSuccessModal}
  {closeSuccessModal}
  personName={$selectedPerson?.name || ""}
/>

<style>
  table {
    border-spacing: 0;
    width: 100%;
  }

  thead {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  tbody tr {
    background-color: #fafafa;
    transition: all 0.2s ease-in-out;
  }

  tbody tr:hover {
    background-color: #fef3e8;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  }

  button:hover {
    cursor: pointer;
  }

  th:hover {
    cursor: pointer;
  }

  tr:hover {
    cursor: pointer;
  }

  select:hover {
    cursor: pointer;
  }
</style>