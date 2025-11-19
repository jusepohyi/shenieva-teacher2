<script lang="ts">
    import { onMount } from 'svelte';
    import sanitizeForDisplay from '$lib/utils/sanitize';
    import { apiUrl } from '$lib/api_base';

    export let storyTitle: string = "Hector's Health";
    export let storyKey: string = 'story2-1';

    interface QuizResult {
        quizID: number;
        studentID: number;
        studentName: string;
        idNo: string;
        storyTitle: string;
        question: string;
        correctAnswer: string | null;
        selectedAnswer: string | null;
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
    let sortBy: 'name' | 'score' | 'date' | 'attempt' = 'name';
    let sortOrder: 'asc' | 'desc' = 'asc';

    onMount(() => {
        fetchResults();
    });

    $: if (typeof storyKey !== 'undefined') {
        fetchResults();
        selectedStudent = null;
        searchQuery = '';
    }

    $: {
        const query = searchQuery.trim().toLowerCase();
        filteredStudents = students
            .filter(student =>
                student.studentName.toLowerCase().includes(query) ||
                student.idNo.toLowerCase().includes(query)
            );

        filteredStudents = sortStudents(filteredStudents, sortBy, sortOrder);
    }

    async function fetchResults() {
        loading = true;
        try {
            const response = await fetch(apiUrl('get_level2_quiz_results.php'));
            const data = await response.json();

            if (data.success) {
                    const filteredResults = data.data.filter((result: QuizResult) => {
                        if (storyKey === 'all') return true;
                        const val = sanitizeForDisplay(result.storyTitle) ?? result.storyTitle ?? '';
                        const normalized = (val || '').trim().toLowerCase();
                        return normalized === (storyTitle ?? '').trim().toLowerCase() || normalized === (storyKey ?? '').trim().toLowerCase();
                    });
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
                    storyTitle: sanitizeForDisplay(result.storyTitle) ?? result.storyTitle,
                    dateTaken: result.createdAt,
                    totalScore: 0,
                    maxScore: 0,
                    percentage: 0,
                    attempt: result.attempt,
                    quizResults: []
                });
            }

            const student = studentMap.get(result.studentID)!;

            if (result.attempt > student.attempt) {
                student.attempt = result.attempt;
                student.quizResults = [];
                student.dateTaken = result.createdAt;
            }

            if (result.attempt === student.attempt) {
                student.quizResults.push({
                    ...result,
                    score: Number(result.score),
                    // sanitize text fields for display (remove stored escaping)
                    question: sanitizeForDisplay(result.question) ?? result.question,
                    correctAnswer: normalizeAnswer(result.correctAnswer) ?? sanitizeForDisplay(result.correctAnswer) ?? result.correctAnswer,
                    selectedAnswer: normalizeAnswer(result.selectedAnswer) ?? sanitizeForDisplay(result.selectedAnswer) ?? result.selectedAnswer
                });
            }
        });

        students = Array.from(studentMap.values()).map(student => {
            student.totalScore = student.quizResults.reduce((sum, r) => sum + r.score, 0);
            student.maxScore = student.quizResults.length;
            student.percentage = student.maxScore > 0 ? Math.round((student.totalScore / student.maxScore) * 100) : 0;
            return student;
        });
    }

    function sortStudents(studentList: StudentSummary[], by: 'name' | 'score' | 'date' | 'attempt', order: 'asc' | 'desc') {
        const sorted = [...studentList].sort((a, b) => {
            let compareValue = 0;

            if (by === 'name') {
                compareValue = a.studentName.localeCompare(b.studentName);
            } else if (by === 'score') {
                compareValue = a.percentage - b.percentage;
            } else if (by === 'attempt') {
                compareValue = (Number(a.attempt) || 0) - (Number(b.attempt) || 0);
            } else if (by === 'date') {
                compareValue = new Date(a.dateTaken).getTime() - new Date(b.dateTaken).getTime();
            }

            return order === 'asc' ? compareValue : -compareValue;
        });

        return sorted;
    }

    function handleSort(by: 'name' | 'score' | 'date' | 'attempt') {
        if (sortBy === by) {
            sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy = by;
            sortOrder = 'asc';
        }
    }

    function getSortIndicator(column: 'name' | 'score' | 'date' | 'attempt') {
        if (sortBy === column) {
            return sortOrder === 'asc' ? '↑' : '↓';
        }
        return '↕';
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

    function normalizeAnswer(value: string | null): string | null {
        if (value === null || value === undefined) {
            return null;
        }
        const trimmed = value.trim();
        return trimmed.length > 0 ? trimmed : null;
    }
</script>

{#if !selectedStudent}
    <div class="p-4 bg-gray-50 min-h-screen">
        <div class="max-w-6xl mx-auto">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-gray-800">
                    🎓 Student Quiz Results
                </h2>
            </div>

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
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-lime-100 border-b border-gray-200">
                            <tr>
                                <th
                                    on:click={() => handleSort('name')}
                                    class="px-4 py-3 text-left text-sm font-semibold text-gray-800 cursor-pointer hover:bg-lime-200 transition"
                                >
                                    <div class="flex items-center gap-2">
                                        <span>👤 Student Name</span>
                                        <span class="text-sm font-semibold {sortBy === 'name' ? 'text-lime-600' : 'text-gray-400'}">
                                            {getSortIndicator('name')}
                                        </span>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-800">
                                    🆔 Student ID
                                </th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-800">
                                    📖 Quiz Title
                                </th>
                                <th on:click={() => handleSort('attempt')} class="px-4 py-3 text-center text-sm font-semibold text-gray-800 cursor-pointer hover:bg-lime-200 transition whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        🔁 Attempts
                                        <span class="text-sm font-semibold {sortBy === 'attempt' ? 'text-lime-600' : 'text-gray-400'}">{getSortIndicator('attempt')}</span>
                                    </div>
                                </th>
                                <th
                                    on:click={() => handleSort('date')}
                                    class="px-4 py-3 text-left text-sm font-semibold text-gray-800 cursor-pointer hover:bg-lime-200 transition"
                                >
                                    <div class="flex items-center gap-2">
                                        <span>📅 Date Taken</span>
                                        <span class="text-sm font-semibold {sortBy === 'date' ? 'text-lime-600' : 'text-gray-400'}">
                                            {getSortIndicator('date')}
                                        </span>
                                    </div>
                                </th>
                                <th
                                    on:click={() => handleSort('score')}
                                    class="px-4 py-3 text-center text-sm font-semibold text-gray-800 cursor-pointer hover:bg-lime-200 transition"
                                >
                                    <div class="flex items-center justify-center gap-2">
                                        <span>⭐ Score</span>
                                        <span class="text-sm font-semibold {sortBy === 'score' ? 'text-lime-600' : 'text-gray-400'}">
                                            {getSortIndicator('score')}
                                        </span>
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
                                    <td class="px-4 py-3 text-center">
                                        <div class="text-sm text-gray-700">{student.attempt ?? 1}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-600">{formatDate(student.dateTaken)}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class={`inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ${getScoreBadgeColor(student.percentage)}`}>
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
    <div class="p-4 bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto">
            <button
                on:click={backToList}
                class="mb-4 inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors text-gray-700 font-medium"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                ⬅️ Back to Students
            </button>

            <div class="bg-white rounded-lg shadow p-6 mb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">
                            🎓 {selectedStudent.studentName}
                        </h2>
                        <p class="text-gray-600 mt-1">🆔 Student ID: {selectedStudent.idNo}</p>
                    </div>
                    <div class="text-right bg-lime-50 rounded-lg p-4 border border-lime-200">
                        <div class={`text-3xl font-bold ${getScoreColor(selectedStudent.percentage)}`}>
                            {selectedStudent.percentage}%
                        </div>
                        <p class="text-sm text-gray-600 mt-1">
                            ⭐ {selectedStudent.totalScore} / {selectedStudent.maxScore} correct
                        </p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200 grid grid-cols-3 gap-4 text-sm">
                    <div class="bg-lime-50 p-3 rounded-lg border border-lime-200">
                        <span class="text-gray-700 font-medium">📖 Quiz Title:</span>
                        <span class="ml-2 font-semibold text-gray-900">{selectedStudent.storyTitle}</span>
                    </div>
                    <div class="bg-orange-50 p-3 rounded-lg border border-orange-200">
                        <span class="text-gray-700 font-medium">📅 Date Taken:</span>
                        <span class="ml-2 font-semibold text-gray-900">{formatDate(selectedStudent.dateTaken)}</span>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                        <span class="text-gray-700 font-medium">🔁 Attempts:</span>
                        <span class="ml-2 font-semibold text-gray-900">{selectedStudent.attempt ?? 1}</span>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                {#each selectedStudent.quizResults as result, index}
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class={`px-5 py-4 ${result.score === 1 ? 'bg-green-500' : 'bg-red-500'}`}>
                            <div class="flex items-center justify-between">
                                <h3 class="text-white font-bold text-lg">
                                    Question {index + 1}
                                </h3>
                                <span class={`px-3 py-1 rounded-full text-xs font-bold bg-white ${result.score === 1 ? 'text-green-600' : 'text-red-600'}`}>
                                    {result.score === 1 ? '✓ CORRECT' : '✗ WRONG'}
                                </span>
                            </div>
                        </div>

                        <div class="px-5 py-4 bg-gray-50">
                            <p class="text-base font-medium text-gray-800 mb-2">
                                {sanitizeForDisplay(result.question) ?? result.question}
                            </p>
                        </div>

                        <div class="px-5 py-4 space-y-3">
                            <div class={`flex items-start gap-3 p-4 rounded-lg border-2 shadow-sm ${result.score === 1 ? 'bg-green-100 border-green-500' : 'bg-red-100 border-red-500'}`}>
                                <div class={`flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-lg ${result.score === 1 ? 'bg-green-500' : 'bg-red-500'}`}>
                                    📝
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-600">Student Answer</p>
                                        <p class="text-gray-900 whitespace-pre-wrap mt-1">
                                        {sanitizeForDisplay(result.selectedAnswer) ?? result.selectedAnswer ?? 'No answer submitted'}
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class={`px-3 py-1 rounded-full text-xs font-bold text-white ${result.score === 1 ? 'bg-green-500' : 'bg-red-500'}`}>
                                        {result.score === 1 ? '✓ Student Answer (Correct)' : '✗ Student Answer'}
                                    </span>
                                </div>
                            </div>

                            {#if result.score === 0}
                                <div class="flex items-start gap-3 p-4 rounded-lg bg-lime-100 border-2 border-lime-500 shadow-sm">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-lime-500 flex items-center justify-center text-white font-bold text-lg">
                                        ✅
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-gray-600">Correct Answer</p>
                                        <p class="text-gray-900 whitespace-pre-wrap mt-1">
                                            {sanitizeForDisplay(result.correctAnswer) ?? result.correctAnswer ?? 'No correct answer provided'}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="px-3 py-1 rounded-full bg-lime-500 text-white text-xs font-bold">
                                            ✓ Correct Answer
                                        </span>
                                    </div>
                                </div>
                            {/if}
                        </div>
                    </div>
                {/each}
            </div>
        </div>
    </div>
{/if}

<style>
    * {
        transition: background-color 0.2s ease;
    }
</style>
