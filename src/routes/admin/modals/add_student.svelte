<!-- src/routes/admin/modals/add_student.svelte -->
<script lang="ts">
  import { createEventDispatcher } from "svelte";
  import { studentData } from "$lib/store/store_students";
  import { apiUrl } from '$lib/api_base';

  const dispatch = createEventDispatcher();

  let idNo = "";
  let studentName = "";
  let studentPass = "";
  let isSubmitting = false;
  let errorMessage = "";

  async function addStudent() {
    isSubmitting = true;
    errorMessage = "";

    const newStudent = {
      idNo,
      studentName,
      studentPass,
    };

    try {
      const response = await fetch(apiUrl('add_student.php'), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(newStudent),
      });

      const result = await response.json();

      if (result.success) {
        // Add the new student to the store with only the provided fields
        const studentWithId = {
          pk_studentID: result.pk_studentID || Date.now(), // Fallback if not returned
          idNo: newStudent.idNo,
          studentName: newStudent.studentName,
          studentPass: newStudent.studentPass,
        };

        studentData.update((students) => [...students, studentWithId]);
        dispatch("close");
      } else {
        errorMessage = result.message || "Failed to add student.";
      }
    } catch (error) {
      errorMessage = "Failed to connect to the server.";
    }

    isSubmitting = false;
  }
</script>

<div class="fixed inset-0 flex items-center justify-center bg-black/30 z-30">
  <div class="bg-white p-6 rounded-lg shadow-lg w-96">
    <h2 class="text-xl font-bold mb-4">Add New Student</h2>

    {#if errorMessage}
      <p class="text-red-500 mb-4">{errorMessage}</p>
    {/if}

    <div class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-gray-600">Student ID</label>
        <input 
          type="text" 
          bind:value={idNo} 
          class="w-full p-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-lime-500" 
          required 
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-600">Name</label>
        <input 
          type="text" 
          bind:value={studentName} 
          class="w-full p-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-lime-500" 
          required 
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-600">Password</label>
        <input 
          type="password" 
          bind:value={studentPass} 
          class="w-full p-2 border border-gray-300 rounded-md text-gray-900 focus:outline-none focus:ring-2 focus:ring-lime-500" 
          required 
        />
      </div>
    </div>

    <div class="flex justify-end gap-3 mt-6">
      <button 
        on:click={() => dispatch("close")} 
        class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400"
      >
        Cancel
      </button>
      <button 
        on:click={addStudent} 
        class="px-4 py-2 bg-lime-500 text-white rounded-md hover:bg-lime-600 focus:outline-none focus:ring-2 focus:ring-lime-400"
        disabled={isSubmitting}
      >
        {isSubmitting ? "Adding..." : "Add"}
      </button>
    </div>
  </div>
</div>