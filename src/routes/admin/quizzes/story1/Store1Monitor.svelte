<script lang="ts">
    import { onMount } from 'svelte';
    
    export let storyTitle: string = "Maria's Promise";
    
    interface QuizResult {
        quizID: number;
        studentID: number;
        studentName: string;
        idNo: string;
        storyTitle: string;
        question: string;
        choiceA: string;
        choiceB: string;
        choiceC: string;
        choiceD: string;
        correctAnswer: string;
        selectedAnswer: string;
        score: number;
        attempt: number;
        createdAt: string;
    }
    
    interface StudentSummary {
        studentID: number;
        studentName: string;
        idNo: string;
        storyTitle: string;
        dateTaken: string;
        totalScore: number;
        maxScore: number;
        percentage: number;
        attempt: number;
        quizResults: QuizResult[];
    }
    
    let students: StudentSummary[] = [];
    let filteredStudents: StudentSummary[] = [];
    let loading = true;
    let selectedStudent: StudentSummary | null = null;
    let searchQuery = '';
    let sortBy: 'name' | 'score' | 'date' = 'name';
    let sortOrder: 'asc' | 'desc' = 'asc';
    
    onMount(() => {
        fetchResults();
    });
    
    // Reactive statement to refetch when storyTitle changes
    $: if (storyTitle) {
        fetchResults();
        selectedStudent = null;
        searchQuery = '';
    }
    
    // Reactive filtering and sorting
    $: {
        filteredStudents = students.filter(student => {
            const query = searchQuery.toLowerCase();
            return student.studentName.toLowerCase().includes(query) || 
                   student.idNo.toLowerCase().includes(query);
        });
        
        filteredStudents = sortStudents(filteredStudents, sortBy, sortOrder);
    }
    
    async function fetchResults() {
        loading = true;
        try {
            // Fetch all Level 1 quiz results (not filtered by story initially)
            const response = await fetch(
                `http://localhost/shenieva-teacher/src/lib/api/get_level1_quiz_results.php`
            );
            const data = await response.json();
            
            if (data.success) {
                // Filter by current storyTitle
                const filteredResults = data.data.filter((result: QuizResult) => result.storyTitle === storyTitle);
                processStudentData(filteredResults);
            }
        } catch (error) {
            console.error('Error fetching results:', error);
        } finally {
            loading = false;
        }
    }
    
    function processStudentData(results: QuizResult[]) {
        const studentMap = new Map<number, StudentSummary>();
        
        results.forEach(result => {
            if (!studentMap.has(result.studentID)) {
                studentMap.set(result.studentID, {
                    studentID: result.studentID,
                    studentName: result.studentName,
                    idNo: result.idNo,
                    storyTitle: result.storyTitle,
                    dateTaken: result.createdAt,
                    totalScore: 0,
                    maxScore: 0,
                    percentage: 0,
                    attempt: result.attempt,
                    quizResults: []
                });
            }
            
            const student = studentMap.get(result.studentID)!;
            
            // Keep only the latest attempt
            if (result.attempt > student.attempt) {
                student.attempt = result.attempt;
                student.quizResults = [];
                student.dateTaken = result.createdAt;
            }
            
            if (result.attempt === student.attempt) {
                student.quizResults.push(result);
            }
        });
        
        // Calculate scores
        students = Array.from(studentMap.values()).map(student => {
            student.totalScore = student.quizResults.reduce((sum, r) => sum + r.score, 0);
            student.maxScore = student.quizResults.length;
            student.percentage = student.maxScore > 0 ? Math.round((student.totalScore / student.maxScore) * 100) : 0;
            return student;
        });
    }
    
    function sortStudents(studentList: StudentSummary[], by: 'name' | 'score' | 'date', order: 'asc' | 'desc') {
        const sorted = [...studentList].sort((a, b) => {
            let compareValue = 0;
            
            if (by === 'name') {
                compareValue = a.studentName.localeCompare(b.studentName);
            } else if (by === 'score') {
                compareValue = a.percentage - b.percentage;
            } else if (by === 'date') {
                compareValue = new Date(a.dateTaken).getTime() - new Date(b.dateTaken).getTime();
            }
            
            return order === 'asc' ? compareValue : -compareValue;
        });
        
        return sorted;
    }
    
    function handleSort(by: 'name' | 'score' | 'date') {
        if (sortBy === by) {
            sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy = by;
            sortOrder = 'asc';
        }
    }
    
    function viewStudentDetails(student: StudentSummary) {
        selectedStudent = student;
    }
    
    function backToList() {
        selectedStudent = null;
    }
    
    function formatDate(dateString: string) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function getScoreColor(percentage: number) {
        if (percentage >= 80) return 'text-lime-600';
        if (percentage >= 60) return 'text-orange-600';
        return 'text-red-600';
    }
    
    function getScoreBadgeColor(percentage: number) {
        if (percentage >= 80) return 'bg-lime-500 text-white';
        if (percentage >= 60) return 'bg-orange-500 text-white';
        return 'bg-red-500 text-white';
    }
</script>

{#if !selectedStudent}
    <!-- Student List View -->
    <div class="p-4 bg-gray-50 min-h-screen">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-4">
                <h2 class="text-xl font-bold text-gray-800">
                    🎓 Student Quiz Results
                </h2>
            </div>
            
            <!-- Search Bar -->
            <div class="mb-4">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-lg">🔍</span>
                    <input
                        type="text"
                        bind:value={searchQuery}
                        placeholder="Search by student name or ID..."
                        class="w-full pl-12 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 outline-none"
                    />
                </div>
            </div>
            
            {#if loading}
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-lime-500"></div>
                    <p class="mt-3 text-gray-600">Loading students...</p>
                </div>
            {:else if filteredStudents.length === 0}
                <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                    <p>📚 No students found.</p>
                </div>
            {:else}
                <!-- Student Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-lime-100 border-b border-gray-200">
                            <tr>
                                <th 
                                    on:click={() => handleSort('name')}
                                    class="px-4 py-3 text-left text-sm font-semibold text-gray-800 cursor-pointer hover:bg-lime-200 transition"
                                >
                                    <div class="flex items-center gap-1">
                                        👤 Student Name
                                        {#if sortBy === 'name'}
                                            <span class="text-lime-600">{sortOrder === 'asc' ? '↑' : '↓'}</span>
                                        {/if}
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-800">
                                    🆔 Student ID
                                </th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-800">
                                    📖 Quiz Title
                                </th>
                                <th 
                                    on:click={() => handleSort('date')}
                                    class="px-4 py-3 text-left text-sm font-semibold text-gray-800 cursor-pointer hover:bg-lime-200 transition"
                                >
                                    <div class="flex items-center gap-1">
                                        📅 Date Taken
                                        {#if sortBy === 'date'}
                                            <span class="text-lime-600">{sortOrder === 'asc' ? '↑' : '↓'}</span>
                                        {/if}
                                    </div>
                                </th>
                                <th 
                                    on:click={() => handleSort('score')}
                                    class="px-4 py-3 text-center text-sm font-semibold text-gray-800 cursor-pointer hover:bg-lime-200 transition"
                                >
                                    <div class="flex items-center justify-center gap-1">
                                        ⭐ Score
                                        {#if sortBy === 'score'}
                                            <span class="text-lime-600">{sortOrder === 'asc' ? '↑' : '↓'}</span>
                                        {/if}
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            {#each filteredStudents as student}
                                <tr 
                                    on:click={() => viewStudentDetails(student)}
                                    class="hover:bg-lime-50 cursor-pointer transition-colors"
                                >
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-gray-900">{student.studentName}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-700">{student.idNo}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-700">{student.storyTitle}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-600">{formatDate(student.dateTaken)}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {getScoreBadgeColor(student.percentage)}">
                                            {student.totalScore}/{student.maxScore} ({student.percentage}%)
                                        </span>
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/if}
        </div>
    </div>
{:else}
    <!-- Student Quiz Detail View -->
    <div class="p-4 bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto">
            <!-- Back Button -->
            <button
                on:click={backToList}
                class="mb-4 inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors text-gray-700 font-medium"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                ⬅️ Back to Students
            </button>
            
            <!-- Student Info Card -->
            <div class="bg-white rounded-lg shadow p-6 mb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">
                            🎓 {selectedStudent.studentName}
                        </h2>
                        <p class="text-gray-600 mt-1">🆔 Student ID: {selectedStudent.idNo}</p>
                    </div>
                    <div class="text-right bg-lime-50 rounded-lg p-4 border border-lime-200">
                        <div class="text-3xl font-bold {getScoreColor(selectedStudent.percentage)}">
                            {selectedStudent.percentage}%
                        </div>
                        <p class="text-sm text-gray-600 mt-1">
                            ⭐ {selectedStudent.totalScore} / {selectedStudent.maxScore} correct
                        </p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200 grid grid-cols-2 gap-4 text-sm">
                    <div class="bg-lime-50 p-3 rounded-lg border border-lime-200">
                        <span class="text-gray-700 font-medium">📖 Quiz Title:</span>
                        <span class="ml-2 font-semibold text-gray-900">{selectedStudent.storyTitle}</span>
                    </div>
                    <div class="bg-orange-50 p-3 rounded-lg border border-orange-200">
                        <span class="text-gray-700 font-medium">📅 Date Taken:</span>
                        <span class="ml-2 font-semibold text-gray-900">{formatDate(selectedStudent.dateTaken)}</span>
                    </div>
                </div>
            </div>
            
            <!-- Quiz Questions -->
            <div class="space-y-6">
                {#each selectedStudent.quizResults as result, index}
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                        <!-- Question Header -->
                        <div class="px-6 py-4 {result.score === 1 ? 'bg-green-500' : 'bg-red-500'} flex justify-between items-center">
                            <h3 class="text-lg font-bold text-white">
                                Question {index + 1}
                            </h3>
                            <span class="px-4 py-1.5 rounded-lg text-sm font-bold bg-white {result.score === 1 ? 'text-green-700' : 'text-red-700'}">
                                {result.score === 1 ? '✓ CORRECT' : '✗ WRONG'}
                            </span>
                        </div>
                        
                        <!-- Question Text -->
                        <div class="px-6 py-5 bg-gray-50 border-b border-gray-200">
                            <p class="text-lg text-gray-900 font-semibold leading-relaxed">
                                {result.question}
                            </p>
                        </div>
                        
                        <!-- Answer Choices -->
                        <div class="p-6">
                            <div class="space-y-4">
                                {#each ['A', 'B', 'C'] as choice}
                                    {@const isCorrect = result.correctAnswer === choice}
                                    {@const isSelected = result.selectedAnswer === choice}
                                    {@const choiceText = result[`choice${choice}`]}
                                    
                                    <!-- STUDENT'S CORRECT ANSWER -->
                                    {#if isSelected && result.score === 1}
                                        <div class="relative">
                                            <div class="flex items-start gap-4 p-5 rounded-xl border-4" style="background-color: #d1fae5; border-color: #10b981;">
                                                <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center font-bold text-xl" style="background-color: #059669; color: white;">
                                                    {choice}
                                                </div>
                                                <div class="flex-1 pt-2">
                                                    <p class="text-lg text-gray-900 font-semibold">{choiceText}</p>
                                                </div>
                                                <div class="flex-shrink-0 pt-2">
                                                    <span class="px-4 py-2 rounded-lg font-bold text-sm" style="background-color: #059669; color: white;">
                                                        ✓ STUDENT'S ANSWER
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    
                                    <!-- STUDENT'S WRONG ANSWER -->
                                    {:else if isSelected && result.score === 0}
                                        <div class="relative">
                                            <div class="flex items-start gap-4 p-5 rounded-xl border-4" style="background-color: #fee2e2; border-color: #ef4444;">
                                                <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center font-bold text-xl" style="background-color: #dc2626; color: white;">
                                                    {choice}
                                                </div>
                                                <div class="flex-1 pt-2">
                                                    <p class="text-lg text-gray-900 font-semibold">{choiceText}</p>
                                                </div>
                                                <div class="flex-shrink-0 pt-2">
                                                    <span class="px-4 py-2 rounded-lg font-bold text-sm" style="background-color: #dc2626; color: white;">
                                                        ✗ STUDENT'S ANSWER
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    
                                    <!-- CORRECT ANSWER (when student was wrong) -->
                                    {:else if isCorrect && result.score === 0}
                                        <div class="relative">
                                            <div class="flex items-start gap-4 p-5 rounded-xl border-4" style="background-color: #d1fae5; border-color: #10b981;">
                                                <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center font-bold text-xl" style="background-color: #059669; color: white;">
                                                    {choice}
                                                </div>
                                                <div class="flex-1 pt-2">
                                                    <p class="text-lg text-gray-900 font-semibold">{choiceText}</p>
                                                </div>
                                                <div class="flex-shrink-0 pt-2">
                                                    <span class="px-4 py-2 rounded-lg font-bold text-sm" style="background-color: #059669; color: white;">
                                                        ✓ CORRECT ANSWER
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    
                                    <!-- OTHER CHOICES (not selected, not correct answer to show) -->
                                    {:else}
                                        <div class="relative">
                                            <div class="flex items-start gap-4 p-5 rounded-xl border-2" style="background-color: #f9fafb; border-color: #d1d5db;">
                                                <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center font-bold text-xl" style="background-color: #e5e7eb; color: #4b5563;">
                                                    {choice}
                                                </div>
                                                <div class="flex-1 pt-2">
                                                    <p class="text-lg text-gray-700">{choiceText}</p>
                                                </div>
                                            </div>
                                        </div>
                                    {/if}
                                {/each}
                            </div>
                        </div>
                    </div>
                {/each}
            </div>
        </div>
    </div>
{/if}

<style>
    /* Smooth transitions */
    * {
        transition: background-color 0.2s ease;
    }
</style>
