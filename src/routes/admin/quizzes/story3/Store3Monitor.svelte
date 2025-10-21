<script lang="ts">
    import { onMount } from 'svelte';
    import sanitizeForDisplay from '$lib/utils/sanitize';

    export let storyTitle: string = "Liloy and Lingling the Dog";
    export let storyKey: string = 'story3-1';

    interface QuizResult {
        quizID: number;
        studentID: number;
        studentName: string;
        idNo: string;
        storyTitle: string;
        question: string;
        studentAnswer: string | null;
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
        questionCount: number;
        correctCount: number;
        percentage: number;
        attempt: number;
        quizResults: QuizResult[];
    }

    interface QuizResultView extends QuizResult {
        displayScore: number;
        originalScore: number;
        isPending: boolean;
        hasOriginalScore: boolean;
    }

    interface StudentSummaryView extends StudentSummary {
        quizResults: QuizResultView[];
        gradedCount: number;
        pendingCount: number;
        ungradedCount: number;
    }

    type SortColumn = 'name' | 'score' | 'date';
    type SortOrder = 'asc' | 'desc';

    let students: StudentSummary[] = [];
    let filteredStudents: StudentSummary[] = [];
    let loading = true;
    let selectedStudent: StudentSummary | null = null;
    let searchQuery = '';
    let sortBy: SortColumn = 'name';
    let sortOrder: SortOrder = 'asc';

    let gradingStatus: Record<number, 'idle' | 'saving' | 'success' | 'error'> = {};
    let gradingMessage: Record<number, string> = {};
    let customScores: Record<number, string> = {};
    let draftScores: Record<number, number> = {};
    let pendingScores: Record<number, number> = {};
    let selectedStudentView: StudentSummaryView | null = null;

    type BulkStatus = 'idle' | 'saving' | 'success' | 'error';
    let bulkStatus: BulkStatus = 'idle';
    let bulkMessage = '';
    let pendingCount = 0;

    onMount(() => {
        fetchResults();
    });

    // react to storyKey changes (supports 'all' to show every quiz)
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

    $: {
        void draftScores;
        void pendingScores;
        selectedStudentView = selectedStudent ? deriveStudentView(selectedStudent) : null;
    }

    $: pendingCount = selectedStudentView ? selectedStudentView.pendingCount : 0;

    async function fetchResults() {
        loading = true;
        try {
            const response = await fetch(`http://localhost/shenieva-teacher/src/lib/api/get_level3_quiz_results.php`);
            const data = await response.json();

            if (data.success) {
                const filteredResults = data.data.filter((result: QuizResult) => matchesStory(result.storyTitle));
                processStudentData(filteredResults);
            }
        } catch (error) {
            console.error('Error fetching results:', error);
        } finally {
            loading = false;
        }
    }

    function matchesStory(value: string | null | undefined) {
        // when storyKey is 'all', don't filter — include every entry
        if (storyKey === 'all') return true;
        if (!value) return false;
        const normalizedValue = normalizeTitle(value);
        const normalizedTitle = normalizeTitle(storyTitle);
        const normalizedKey = normalizeTitle(storyKey);
        return normalizedValue === normalizedTitle || normalizedValue === normalizedKey;
    }

    function normalizeTitle(value: string) {
        if (!value) return '';
        // sanitize escapes and normalize
        const cleaned = sanitizeForDisplay(value) ?? value;
        return cleaned.trim().toLowerCase();
    }

    function processStudentData(results: QuizResult[]) {
        const studentMap = new Map<number, StudentSummary>();

        results.forEach(result => {
            if (!studentMap.has(result.studentID)) {
                studentMap.set(result.studentID, {
                    studentID: result.studentID,
                    studentName: result.studentName,
                    idNo: result.idNo,
                    storyTitle: sanitizeForDisplay(result.storyTitle) ?? storyTitle,
                    dateTaken: result.createdAt,
                    totalScore: 0,
                    questionCount: 0,
                    correctCount: 0,
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
                    score: Number.isFinite(result.score) ? Number(result.score) : 0,
                    // sanitize student answer and question text for display
                    question: sanitizeForDisplay(result.question) ?? result.question,
                    studentAnswer: sanitizeForDisplay(result.studentAnswer) ?? result.studentAnswer ?? ''
                });
            }
        });

        const derivedStudents = Array.from(studentMap.values()).map(student => {
            student.totalScore = student.quizResults.reduce((sum, r) => sum + (Number.isFinite(r.score) ? r.score : 0), 0);
            student.questionCount = student.quizResults.length;
            student.correctCount = student.quizResults.reduce((count, r) =>
                Number.isFinite(r.score) && r.score > 0 ? count + 1 : count,
                0
            );
            student.percentage = student.questionCount > 0 ? Math.round((student.correctCount / student.questionCount) * 100) : 0;
            return student;
        });

        students = derivedStudents;

        const nextCustomScores: Record<number, string> = {};
        const nextDraftScores: Record<number, number> = {};
        derivedStudents.forEach(student => {
            student.quizResults.forEach(result => {
                const normalizedScore = Number.isFinite(result.score)
                    ? Number(result.score)
                    : 0;
                nextCustomScores[result.quizID] = String(normalizedScore);
                nextDraftScores[result.quizID] = normalizedScore;
            });
        });

        customScores = nextCustomScores;
        draftScores = nextDraftScores;
        pendingScores = {};
        bulkStatus = 'idle';
        bulkMessage = '';
    }

    function sortStudents(studentList: StudentSummary[], by: SortColumn, order: SortOrder) {
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

    function deriveStudentView(student: StudentSummary): StudentSummaryView {
        const quizResults: QuizResultView[] = student.quizResults.map(result => {
            const hasOriginalScore = Number.isFinite(result.score);
            const originalScore = hasOriginalScore ? Number(result.score) : 0;
            const draftScore = Object.prototype.hasOwnProperty.call(draftScores, result.quizID)
                ? draftScores[result.quizID]
                : originalScore;
            const isQueued = Object.prototype.hasOwnProperty.call(pendingScores, result.quizID);

            return {
                ...result,
                displayScore: draftScore,
                originalScore,
                isPending: isQueued,
                hasOriginalScore
            };
        });

        const totalPoints = quizResults.reduce((sum, r) => sum + (Number.isFinite(r.displayScore) ? r.displayScore : 0), 0);
        const questionCount = quizResults.length;
        const correctCount = quizResults.reduce((count, r) =>
            Number.isFinite(r.displayScore) && r.displayScore > 0 ? count + 1 : count,
            0
        );
        const percentage = questionCount > 0 ? Math.round((correctCount / questionCount) * 100) : 0;
        const gradedCount = quizResults.reduce((count, r) => (r.hasOriginalScore ? count + 1 : count), 0);
        const pendingCount = quizResults.reduce((count, r) => (r.isPending ? count + 1 : count), 0);
        const ungradedCount = Math.max(questionCount - gradedCount, 0);

        return {
            ...student,
            quizResults,
            totalScore: totalPoints,
            correctCount,
            questionCount,
            percentage,
            gradedCount,
            pendingCount,
            ungradedCount
        };
    }

    function handleSort(by: SortColumn) {
        if (sortBy === by) {
            sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            sortBy = by;
            sortOrder = 'asc';
        }
    }

    function getSortIndicator(column: SortColumn) {
        if (sortBy === column) {
            return sortOrder === 'asc' ? '↑' : '↓';
        }
        return '↕';
    }

    function viewStudentDetails(student: StudentSummary) {
        selectedStudent = student;
        bulkStatus = 'idle';
        bulkMessage = '';
    }

    function backToList() {
        selectedStudent = null;
        bulkStatus = 'idle';
        bulkMessage = '';
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

    function getSuggestedFullScore(result: QuizResultView) {
        if (result.displayScore > 0) {
            return result.displayScore;
        }
        if (result.originalScore > 0) {
            return result.originalScore;
        }
        return 1;
    }

    function commitScoreInput(result: QuizResultView, rawValue: string) {
        const numeric = Number(rawValue);
        if (!Number.isNaN(numeric)) {
            stageScore(result, numeric);
        }
    }

    function handleScoreKey(event: KeyboardEvent, result: QuizResultView, index: number) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        const input = event.target as HTMLInputElement;
        commitScoreInput(result, input.value);

        const nextInput = document.querySelector<HTMLInputElement>(`[data-score-input="${index + 1}"]`);
        if (nextInput) {
            nextInput.focus();
            nextInput.select();
        }
    }

    function describeRowStatus(result: QuizResultView) {
        const status = gradingStatus[result.quizID] ?? 'idle';

        if (status === 'saving') {
            return { label: 'Saving...', color: 'text-gray-500' };
        }

        if (status === 'error') {
            return { label: gradingMessage[result.quizID] || 'Save failed', color: 'text-red-600' };
        }

        if (status === 'success') {
            return { label: gradingMessage[result.quizID] || 'Saved', color: 'text-green-600' };
        }

        if (result.isPending) {
            return { label: 'Pending save', color: 'text-orange-600' };
        }

        if (!result.hasOriginalScore) {
            return { label: 'Needs grading', color: 'text-gray-600' };
        }

        return { label: 'Saved', color: 'text-gray-500' };
    }

    function normalizeScore(value: number) {
        if (!Number.isFinite(value)) {
            return 0;
        }
        return Math.max(0, Math.round(value));
    }

    function stageScore(result: QuizResult, newScore: number) {
        const normalizedScore = normalizeScore(newScore);
        const quizID = result.quizID;
        const originalScore = Number.isFinite(result.score) ? result.score : 0;
        const hadScore = Number.isFinite(result.score);

        draftScores = { ...draftScores, [quizID]: normalizedScore };
        customScores = { ...customScores, [quizID]: String(normalizedScore) };

        if (normalizedScore !== originalScore || !hadScore) {
            pendingScores = { ...pendingScores, [quizID]: normalizedScore };
        } else {
            const { [quizID]: _, ...rest } = pendingScores;
            pendingScores = rest;
        }

        bulkStatus = 'idle';
        bulkMessage = '';
        gradingStatus = { ...gradingStatus, [quizID]: 'idle' };
        gradingMessage = { ...gradingMessage, [quizID]: '' };
    }

    async function persistScore(studentID: number, quizID: number, newScore: number) {
        const normalizedScore = normalizeScore(newScore);

        gradingStatus = { ...gradingStatus, [quizID]: 'saving' };
        gradingMessage = { ...gradingMessage, [quizID]: '' };

        try {
            const response = await fetch('http://localhost/shenieva-teacher/src/lib/api/update_level3_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ quizID, score: normalizedScore })
            });
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Failed to update score');
            }

            applyScoreToState(studentID, quizID, normalizedScore);

            gradingStatus = { ...gradingStatus, [quizID]: 'success' };
            gradingMessage = { ...gradingMessage, [quizID]: 'Saved!' };

            setTimeout(() => {
                gradingStatus = { ...gradingStatus, [quizID]: 'idle' };
                gradingMessage = { ...gradingMessage, [quizID]: '' };
            }, 2000);

            return true;
        } catch (error) {
            console.error('Error updating score:', error);
            gradingStatus = { ...gradingStatus, [quizID]: 'error' };
            gradingMessage = { ...gradingMessage, [quizID]: 'Failed to save score' };
            return false;
        }
    }

    async function saveAllPending() {
        if (!selectedStudent) {
            return;
        }

        const relevantQuizIDs = new Set(selectedStudent.quizResults.map(result => result.quizID));
        const pendingEntries = Object.entries(pendingScores).filter(([quizIDKey]) =>
            relevantQuizIDs.has(Number(quizIDKey))
        );

        if (pendingEntries.length === 0) {
            return;
        }

        bulkStatus = 'saving';
        bulkMessage = '';

        const failures: number[] = [];

        for (const [quizIDKey, score] of pendingEntries) {
            const quizID = Number(quizIDKey);
            const success = await persistScore(selectedStudent.studentID, quizID, score);

            if (!success) {
                failures.push(quizID);
            }
        }

        if (failures.length === 0) {
            bulkStatus = 'success';
            bulkMessage = 'All changes saved!';
            setTimeout(() => {
                bulkStatus = 'idle';
                bulkMessage = '';
            }, 3000);
        } else {
            bulkStatus = 'error';
            bulkMessage = `Unable to save ${failures.length} ${failures.length === 1 ? 'change' : 'changes'}.`;
        }
    }

    function applyScoreToState(studentID: number, quizID: number, newScore: number) {
        let updatedSelected: StudentSummary | null = selectedStudent;

        students = students.map(student => {
            if (student.studentID !== studentID) {
                return student;
            }

            const updatedResults = student.quizResults.map(result =>
                result.quizID === quizID
                    ? { ...result, score: newScore }
                    : result
            );

            const totalPoints = updatedResults.reduce((sum, r) => sum + (Number.isFinite(r.score) ? r.score : 0), 0);
            const questionCount = updatedResults.length;
            const correctCount = updatedResults.reduce((count, r) =>
                Number.isFinite(r.score) && r.score > 0 ? count + 1 : count,
                0
            );
            const percentage = questionCount > 0 ? Math.round((correctCount / questionCount) * 100) : 0;

            const updatedStudent: StudentSummary = {
                ...student,
                quizResults: updatedResults,
                totalScore: totalPoints,
                questionCount,
                correctCount,
                percentage
            };

            if (selectedStudent && selectedStudent.studentID === student.studentID) {
                updatedSelected = updatedStudent;
            }

            return updatedStudent;
        });

        selectedStudent = updatedSelected;
        draftScores = { ...draftScores, [quizID]: newScore };
        const { [quizID]: _, ...restPending } = pendingScores;
        pendingScores = restPending;
        customScores = { ...customScores, [quizID]: String(newScore) };
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
                                    class="px-4 py-3 text-left text-sm font-semibold text-gray-800 cursor-pointer hover:bg-lime-200 transition whitespace-nowrap"
                                >
                                    <div class="flex items-center gap-2 whitespace-nowrap">
                                        <span>👤 Student Name</span>
                                        <span class="text-sm font-semibold {sortBy === 'name' ? 'text-lime-600' : 'text-gray-400'}">
                                            {getSortIndicator('name')}
                                        </span>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-800 whitespace-nowrap min-w-[140px]">
                                    🆔 Student ID
                                </th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-800">
                                    📖 Quiz Title
                                </th>
                                <th
                                    on:click={() => handleSort('date')}
                                    class="px-4 py-3 text-left text-sm font-semibold text-gray-800 cursor-pointer hover:bg-lime-200 transition whitespace-nowrap"
                                >
                                    <div class="flex items-center gap-2 whitespace-nowrap">
                                        <span>📅 Date Taken</span>
                                        <span class="text-sm font-semibold {sortBy === 'date' ? 'text-lime-600' : 'text-gray-400'}">
                                            {getSortIndicator('date')}
                                        </span>
                                    </div>
                                </th>
                                <th
                                    on:click={() => handleSort('score')}
                                    class="px-4 py-3 text-center text-sm font-semibold text-gray-800 cursor-pointer hover:bg-lime-200 transition whitespace-nowrap"
                                >
                                    <div class="flex items-center justify-center gap-2 whitespace-nowrap">
                                        <span>⭐ Score</span>
                                        <span class="text-sm font-semibold {sortBy === 'score' ? 'text-lime-600' : 'text-gray-400'}">
                                            {getSortIndicator('score')}
                                        </span>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center text-sm font-semibold text-gray-800 whitespace-nowrap">
                                    🎯 Points
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            {#each filteredStudents as student}
                                <tr
                                    on:click={() => viewStudentDetails(student)}
                                    class="hover:bg-lime-50 cursor-pointer transition-colors"
                                >
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">{student.studentName}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap min-w-[140px]">
                                        <div class="text-sm text-gray-700">{student.idNo}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-700">{student.storyTitle}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-600">{formatDate(student.dateTaken)}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class={`inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ${getScoreBadgeColor(student.percentage)}`}>
                                            {student.correctCount}/{student.questionCount} correct ({student.percentage}%)
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-700">
                                            {student.totalScore} pts
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
    {#if selectedStudentView}
        <div class="p-4 bg-gray-50 min-h-screen">
            <div class="max-w-6xl mx-auto space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <button
                        on:click={backToList}
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors text-gray-700 font-medium"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        ⬅️ Back to Students
                    </button>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="text-sm text-gray-600">
                            {#if pendingCount > 0}
                                💾 {pendingCount} pending {pendingCount === 1 ? 'change' : 'changes'}
                            {:else if selectedStudentView.ungradedCount > 0}
                                📝 {selectedStudentView.ungradedCount} {selectedStudentView.ungradedCount === 1 ? 'question needs grading' : 'questions need grading'}
                            {:else}
                                ✅ All changes saved
                            {/if}
                        </div>
                        {#if bulkStatus === 'saving'}
                            <span class="text-sm text-gray-500">Saving...</span>
                        {:else if bulkStatus === 'success' && bulkMessage}
                            <span class="text-sm text-green-600">{bulkMessage}</span>
                        {:else if bulkStatus === 'error' && bulkMessage}
                            <span class="text-sm text-red-600">{bulkMessage}</span>
                        {/if}
                        <button
                            on:click={saveAllPending}
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled={pendingCount === 0 || bulkStatus === 'saving'}
                        >
                            💾 Save All Changes
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex flex-wrap items-start justify-between gap-5">
                        <div class="space-y-2">
                            <h2 class="text-2xl font-bold text-gray-800">🎓 {selectedStudentView.studentName}</h2>
                            <p class="text-gray-600">🆔 Student ID: {selectedStudentView.idNo}</p>
                            <p class="text-gray-600">📖 Quiz Title: {selectedStudentView.storyTitle}</p>
                            <p class="text-gray-600">📅 Date Taken: {formatDate(selectedStudentView.dateTaken)}</p>
                        </div>
                        <div class="text-right bg-lime-50 rounded-lg p-4 border border-lime-200 min-w-[200px] space-y-1">
                            <div class={`text-3xl font-bold ${getScoreColor(selectedStudentView.percentage)}`}>
                                {selectedStudentView.percentage}%
                            </div>
                            <p class="text-sm text-gray-600">
                                ⭐ Score: {selectedStudentView.correctCount}/{selectedStudentView.questionCount} correct
                            </p>
                            <p class="text-sm text-gray-600">
                                🎯 Points: {selectedStudentView.totalScore} pts
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Score</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">{selectedStudentView.correctCount}/{selectedStudentView.questionCount} correct</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Points Earned</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">{selectedStudentView.totalScore} pts</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Graded</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">{selectedStudentView.gradedCount}</div>
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pending Save</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">{pendingCount}</div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="flex items-center justify-between text-xs font-semibold text-gray-500 mb-1">
                            <span>Grading Progress</span>
                            <span>{selectedStudentView.gradedCount}/{selectedStudentView.questionCount} graded</span>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-lime-500 transition-all duration-300"
                                style={`width: ${selectedStudentView.questionCount > 0 ? Math.min(100, Math.round((selectedStudentView.gradedCount / selectedStudentView.questionCount) * 100)) : 0}%`}
                            ></div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    {#each selectedStudentView.quizResults as result, index}
                        {@const status = describeRowStatus(result)}
                        {@const isMarkedCorrect = result.displayScore > 0}
                        {@const isMarkedIncorrect = result.displayScore === 0 && (result.hasOriginalScore || result.isPending)}
                        <div class={`rounded-xl border shadow-sm transition ${result.isPending ? 'border-orange-300 bg-orange-50/70' : 'border-gray-200 bg-white'}`}>
                            <div class={`flex flex-wrap items-center justify-between gap-3 px-5 py-3 rounded-t-xl ${result.displayScore > 0 ? 'bg-green-50 border-b border-green-100' : result.isPending ? 'bg-orange-50 border-b border-orange-100' : 'bg-gray-50 border-b border-gray-200'}`}>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold text-gray-700">Question {index + 1}</span>
                                    {#if result.isPending}
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-200 text-orange-800">Pending</span>
                                    {:else if !result.hasOriginalScore}
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-300 text-gray-800">New</span>
                                    {/if}
                                </div>
                                <span class={`text-xs font-semibold ${status.color}`}>{status.label}</span>
                            </div>

                            <div class="px-5 py-4 space-y-4">
                                <div class="space-y-2">
                                    <div class="text-sm font-semibold text-gray-900 leading-snug">
                                        {sanitizeForDisplay(result.question) ?? result.question}
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Student Answer</div>
                                        <div class="mt-2 text-gray-800 whitespace-pre-wrap leading-relaxed bg-gray-50 border border-gray-200 rounded-md p-3">
                                            {sanitizeForDisplay(result.studentAnswer) ?? (result.studentAnswer && result.studentAnswer.trim().length > 0 ? result.studentAnswer : 'No answer submitted')}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full md:w-auto">
                                        <button
                                            on:click={() => stageScore(result, getSuggestedFullScore(result))}
                                            class={`flex-1 sm:flex-none px-4 py-2 rounded-lg border text-sm font-semibold transition ${isMarkedCorrect ? 'bg-green-600 border-green-600 text-white shadow-sm' : 'bg-white border-green-500 text-green-700 hover:bg-green-50'}`}
                                            disabled={bulkStatus === 'saving'}
                                        >
                                            ✓ Correct
                                        </button>
                                        <button
                                            on:click={() => stageScore(result, 0)}
                                            class={`flex-1 sm:flex-none px-4 py-2 rounded-lg border text-sm font-semibold transition ${isMarkedIncorrect ? 'bg-red-600 border-red-600 text-white shadow-sm' : 'bg-white border-red-500 text-red-700 hover:bg-red-50'}`}
                                            disabled={bulkStatus === 'saving'}
                                        >
                                            ✗ Incorrect
                                        </button>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide" for={`custom-score-${result.quizID}`}>
                                            Points
                                        </label>
                                        <input
                                            id={`custom-score-${result.quizID}`}
                                            data-score-input={index}
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="w-24 px-2 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-lime-500 focus:border-lime-500"
                                            value={customScores[result.quizID] ?? ''}
                                            on:input={(event) => {
                                                const value = (event.target as HTMLInputElement).value;
                                                customScores = { ...customScores, [result.quizID]: value };
                                            }}
                                            on:blur={(event) => commitScoreInput(result, (event.target as HTMLInputElement).value)}
                                            on:keydown={(event) => handleScoreKey(event, result, index)}
                                            disabled={bulkStatus === 'saving'}
                                        />
                                        <span class="text-xs text-gray-500">pts</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            </div>
        </div>
    {/if}
{/if}

<style>
    * {
        transition: background-color 0.2s ease;
    }
</style>
