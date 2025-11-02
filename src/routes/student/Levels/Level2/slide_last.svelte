<script lang="ts">
    import { goto } from '$app/navigation';
    import { resetLevelAnswers, studentData } from '$lib/store/student_data';
    import { addAttempt } from '$lib/data/attempts.js';
    import { audioStore } from '$lib/store/audio_store';
    import { onMount, onDestroy } from 'svelte';
    // use Svelte auto-subscription ($studentData) instead of importing `get`

    // This final-slide mirrors Level1's UI/flow but adapted for Level 2
    export let storyKey: string = '';

    const slide = {
        text: "Yay! You finished the Level 2 story! 🎉",
        image: "../../assets/school-bg.gif"
    };

    // Show modal to ask "Try Again or Go Next?" or congratulate for perfect score
    let showConfirm = false;
    let showSummary = false;
    let lastAttempt: any = null;
    let quizAlreadyExists = false;
    let checkingDatabase = false;

    // If the student has already reached level 2 or higher, we should not persist Level 2 attempts or award ribbons for this level.
    $: isLevelCompleted = ($studentData?.studentLevel || 0) >= 2;

    // Attempt limiting
    const attemptsLimit = 3;
    // Retakes are limited per LEVEL (not per story). Use a level-scoped localStorage key.
    const levelRetakeCountKey = 'retakeLevel2Count';
    const levelRetakeFlagKey = 'retakeLevel2';
    let retakeCount = 0;
    try { const v = localStorage.getItem(levelRetakeCountKey); retakeCount = v ? parseInt(v,10)||0 : 0; } catch {}

    // Refresh retakeCount on mount and listen for storage events so UI updates after retake
    // typed to avoid TS implicit-any diagnostics
    let _storageHandler: ((ev: StorageEvent) => void) | null = null;
    onMount(() => {
        try { const v = localStorage.getItem(levelRetakeCountKey); retakeCount = v ? parseInt(v,10)||0 : 0; } catch {}
        _storageHandler = (ev) => {
            try { if (ev.key === levelRetakeCountKey) { const v = ev.newValue; retakeCount = v ? parseInt(v,10)||0 : 0; } } catch (e) {}
        };
    try { window.addEventListener('storage', _storageHandler as EventListener); } catch {}
    });
    onDestroy(() => { try { if (_storageHandler) window.removeEventListener('storage', _storageHandler as EventListener); } catch {} });

    // Define correct answers for each story. For DnD quizzes we store the
    // canonical mapping as a JSON string (the quiz saver uses JSON.stringify)
    const correctAnswers: Record<string, Record<string, string>> = {
        'story2-1': {
            'story2-1_slide6': JSON.stringify({
                1: 'His mother will bring him to the doctor.',
                2: "The doctor will say that Hector's weight is above average for his age right now.",
                3: "He will tell Hector that he needs to improve his eating habits and start exercising. Avoid too many sweets like chocolates and he should start eating more vegetables and fruits.",
                4: 'He will have a healthy body.'
            })
        },
        'story2-2': {
            'story2-2_slide5': JSON.stringify({
                1: 'Maya will run and check the old woman.',
                2: 'By helping her to get up and asking someone who passes by to help the old woman too.',
                3: 'She will feel thankful to Maya for her kindness and for being helpful.',
                4: 'She will praise and thank Maya.'
            })
        },
        'story2-3': {
            'story2-3_slide7': JSON.stringify({
                1: 'Royce will buy the medicine because his brother needs it.',
                2: 'Royce will feel happy to help, even if he\'s a bit sad about the toy.',
                3: 'Royce\'s mother will feel proud and thankful to Royce.',
                4: 'Royce will start saving again for his dream remote car.'
            })
        }
    };

    // Note: avoid onMount import — use reactive $studentData where needed

    // Questions metadata for summary display (populated by individual slide components)
    // Prefer the store-provided mapping when available so server receives the real question text.
    let questionsByStory: Record<string, any> = {};
    $: if ($studentData && $studentData.questionsByStory) {
        questionsByStory = $studentData.questionsByStory;
    }

    const storyTitles: Record<string, string> = {
        'story2-1': "Hector's Health",
        'story2-2': "Helpful Maya",
        'story2-3': "Royce Choice"
    };

    // Helper: try to parse JSON mapping into object, otherwise return null
    function parseMapping(v: any) {
        if (v == null) return null;
        if (typeof v === 'object') return v;
        if (typeof v === 'string') {
            try { return JSON.parse(v); } catch (e) { return null; }
        }
        return null;
    }

    // Helper to get both user and correct mappings for a question id
    function getMaps(answers: any, qid: string) {
        const userRaw = answers?.[qid];
        const correctRaw = correctAnswers?.[storyKey]?.[qid];
        const userMap = parseMapping(userRaw);
        const correctMap = parseMapping(correctRaw);
        return { userRaw, correctRaw, userMap, correctMap };
    }

    // Compute total possible points for the current story (sum of subitems)
    function countTotalPossibleForStory(key: string) {
        const items = correctAnswers?.[key] || {};
        let total = 0;
        for (const qid in items) {
            const correctRaw = items[qid];
            const correctMap = parseMapping(correctRaw);
            if (correctMap && typeof correctMap === 'object') total += Object.keys(correctMap).length;
            else total += 1;
        }
        return total;
    }

    $: totalPossible = countTotalPossibleForStory(storyKey);

    // Check if perfect (require every expected question to be answered and correct)
    $: hasPerfectScore = (() => {
        if (!storyKey || !correctAnswers[storyKey]) return false;
        const answers = $studentData?.answeredQuestions || {};
        const expectedQuestions = Object.keys(correctAnswers[storyKey]);
        if (!expectedQuestions || expectedQuestions.length === 0) return false;

        return expectedQuestions.every(qid => {
            const { userRaw, userMap, correctMap } = getMaps(answers, qid);
            if (userRaw === undefined || userRaw === null || userRaw === '') return false;
            if (userMap && correctMap) {
                return Object.keys(correctMap).every(k => (userMap[k] !== undefined && String(userMap[k]) === String(correctMap[k])));
            }
            return String(userRaw) === String(correctMap || '');
        });
    })();

    function canRetake() { return (1 + retakeCount) < attemptsLimit; }
    function incRetakeCount() { retakeCount += 1; try { localStorage.setItem(levelRetakeCountKey, String(retakeCount)); } catch {} }

    async function checkQuizExistsInDatabase() {
        try {
            checkingDatabase = true;
            const student_id = $studentData?.id;
            if (!student_id || !storyKey) {
                quizAlreadyExists = false;
                return;
            }

            const params = new URLSearchParams({
                student_id: String(student_id),
                story_key: storyKey,
                level: '2'
            });

            // Use centralized API helper so the production build points to Hostinger
            const { apiUrl } = await import('$lib/api_base');
            const response = await fetch(apiUrl(`check_quiz_exists.php?${params.toString()}`));
            const result = await response.json();

            if (result.success) {
                quizAlreadyExists = result.exists || false;
                console.log('[Level 2] Quiz exists check:', result);
            } else {
                console.warn('[Level 2] Quiz check failed:', result.message);
                quizAlreadyExists = false;
            }
        } catch (error) {
            console.error('[Level 2] Error checking quiz existence:', error);
            quizAlreadyExists = false;
        } finally {
            checkingDatabase = false;
        }
    }

    // Derived attempt counters for UI
    let attemptsUsed = 1 + retakeCount;
    let attemptsRemaining = Math.max(0, attemptsLimit - attemptsUsed);
    $: attemptsUsed = Math.min(attemptsLimit, 1 + retakeCount);
    $: attemptsRemaining = Math.max(0, attemptsLimit - attemptsUsed);

    async function continueToQuiz() {
        try {
            // Check if quiz already exists in database first
            await checkQuizExistsInDatabase();

            const snapshot = $studentData || {};
            const answers = snapshot.answeredQuestions || {};
            // Score counts each correctly matched sub-item as 1 point.
            const score = Object.keys(correctAnswers[storyKey] || {}).reduce((acc, qid) => {
                const userRaw = answers[qid];
                const correctRaw = correctAnswers[storyKey][qid];
                if (!userRaw || !correctRaw) return acc;
                try {
                    const userMap = typeof userRaw === 'string' ? JSON.parse(userRaw) : userRaw;
                    const correctMap = typeof correctRaw === 'string' ? JSON.parse(correctRaw) : correctRaw;
                    if (userMap && correctMap && typeof userMap === 'object') {
                        const keys = Object.keys(correctMap || {});
                        const matchedCount = keys.reduce((mAcc, k) => mAcc + (String(userMap[k]) === String(correctMap[k]) ? 1 : 0), 0);
                        return acc + matchedCount;
                    }
                    // fallback: treat whole answer as single MCQ
                    return acc + (String(userRaw) === String(correctRaw) ? 1 : 0);
                } catch (e) {
                    return acc + (String(userRaw) === String(correctRaw) ? 1 : 0);
                }
            }, 0);

            const attemptRecord = {
                studentId: snapshot?.pk_studentID || null,
                storyKey,
                answers,
                score,
                retakeCount,
                timestamp: Date.now()
            };

            // If already completed this level, do not persist attempts
            if (isLevelCompleted) {
                lastAttempt = attemptRecord;
                showSummary = true;
                try { localStorage.setItem('pending_story', storyKey); } catch {}
                return;
            }

            addAttempt(attemptRecord);
            lastAttempt = attemptRecord;
            showSummary = true;
            try { localStorage.setItem('pending_story', storyKey); } catch {}
        } catch (e) { console.warn('Failed to save attempt record', e); showConfirm = false; }
    }

    let savingRibbons = false;
    let ribbonMessage = '';
    // Track whether ribbons for this story have already been claimed by this student on this device
    let alreadyClaimed = false;

    // Initialize alreadyClaimed from studentData or localStorage
    $: if (storyKey) {
        try {
            // Priority: store in studentData.claimedStories if present
            const claimedFromStore = $studentData?.claimedStories?.[storyKey];
            if (claimedFromStore) {
                alreadyClaimed = true;
            } else {
                const v = localStorage.getItem(`claimed_${storyKey}`);
                alreadyClaimed = v === 'true';
            }
        } catch (e) { alreadyClaimed = false; }
    }

    async function claimRibbonsAndContinue() {
        if (!lastAttempt) return;
        const snapshot = $studentData || {};
        const studentId = snapshot?.pk_studentID || null;
        const ribbons = lastAttempt.score || 0;
        
        if (!studentId) { 
            showSummary = false; 
            goto('/student/game/trash_2'); 
            return; 
        }

        savingRibbons = true; ribbonMessage = '';
        try {
            // Check if quiz already exists in database for this level
            if (quizAlreadyExists) {
                console.log('Quiz already exists for this level — skipping save');
                ribbonMessage = 'You have already claimed ribbons for Level 2';
                await new Promise(res => setTimeout(res, 800));
                showSummary = false; showConfirm = false; 
                try { localStorage.removeItem('pending_story'); } catch {};
                goto('/student/game/trash_2');
                return;
            }

            if (isLevelCompleted || alreadyClaimed) {
                ribbonMessage = 'Level already completed — this attempt will not be recorded.';
                await new Promise(res => setTimeout(res, 800));
                showSummary = false; showConfirm = false; try { localStorage.removeItem('pending_story'); } catch {};
                goto('/student/game/trash_1');
                return;
            }

            // Prevent double-claim on client-side
            if (alreadyClaimed) {
                console.warn('Attempted to claim ribbons but already claimed for', storyKey);
                showSummary = false; showConfirm = false; try { localStorage.removeItem('pending_story'); } catch {};
                goto('/student/game/trash_2');
                return;
            }

            // If we're running on a dev server (e.g. :5173) the PHP backend is served by Apache on port 80.
            const backendOrigin = (location.port && location.port !== '80' && location.port !== '443') ? `${location.protocol}//${location.hostname}` : location.origin;
            
            // Update ribbons only if score > 0
            if (ribbons > 0) {
                // Use centralized API helper for backend calls
                const { apiUrl: _apiUrl } = await import('$lib/api_base');
                const ribbonsUrl = _apiUrl('update_student_ribbons.php');
                console.log('Updating ribbons at', ribbonsUrl, { student_id: studentId, ribbons });
                const response = await fetch(ribbonsUrl, {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ student_id: studentId, ribbons })
                });
                if (!response.ok) {
                    const txt = await response.text().catch(() => 'no response body');
                    console.error('Ribbons API returned non-OK', response.status, txt);
                    ribbonMessage = 'Failed to save ribbons. Saving quiz anyway...';
                    // Don't return - continue to save quiz
                } else {
                    const result = await response.json();
                    if (!result.success) { 
                        ribbonMessage = 'Failed to save ribbons. Saving quiz anyway...';
                        // Don't return - continue to save quiz
                    } else {
                        // Only update local store after DB confirms success
                        studentData.update((d: any) => {
                            if (!d) return d;
                            const current = d.studentRibbon || 0;
                            console.log('Local studentRibbon before:', current, 'adding', ribbons);
                            const claimed = { ...(d.claimedStories || {}), [storyKey]: true };
                            // persist claimed flag locally too
                            try { localStorage.setItem(`claimed_${storyKey}`, 'true'); } catch (e) {}
                            alreadyClaimed = true;
                            return { ...d, studentRibbon: current + ribbons, claimedStories: claimed };
                        });
                    }
                }
            } else {
                console.log('No ribbons to award (score is 0), but saving quiz submission');
                // Mark as claimed even with 0 ribbons
                studentData.update((d: any) => {
                    if (!d) return d;
                    const claimed = { ...(d.claimedStories || {}), [storyKey]: true };
                    try { localStorage.setItem(`claimed_${storyKey}`, 'true'); } catch (e) {}
                    alreadyClaimed = true;
                    return { ...d, claimedStories: claimed };
                });
            }

            try {
                // When claiming ribbons/continuing, this is ALWAYS a final submission
                // Save to database regardless of score (perfect or not perfect)
                const isFinal = 1; // Always final when claiming
                
                // Filter answers to only include current story's questions
                const filteredAnswers = Object.fromEntries(
                    Object.entries(lastAttempt.answers || {}).filter(([key]) => key.startsWith(storyKey))
                );
                
                const payload = { 
                    student_id: studentId, 
                    storyKey, 
                    storyTitle: storyTitles[storyKey] || storyKey, 
                    attempt: { ...lastAttempt, answers: filteredAnswers }, 
                    questions: (questionsByStory as any)?.[storyKey] || {}, 
                    correctAnswers: correctAnswers[storyKey] || {}, 
                    is_final: isFinal 
                };
                const { apiUrl: _apiUrl2 } = await import('$lib/api_base');
                const saveUrl = _apiUrl2('submit_level2_quiz.php');
                console.log('Saving Level 2 quiz rows at', saveUrl);
                console.log('is_final:', isFinal, '(Always 1 when claiming ribbons)');
                console.log('Payload:', payload);
                console.log('Payload.attempt:', payload.attempt);
                console.log('Payload.attempt.answers:', payload.attempt?.answers);
                console.log('Payload.correctAnswers:', payload.correctAnswers);
                const saveResp = await fetch(saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                if (!saveResp.ok) {
                    const txt = await saveResp.text().catch(() => 'no response body');
                    console.error('Save quiz API returned non-OK', saveResp.status, txt);
                    
                    // Try to parse as JSON to get the error message
                    try {
                        const errorData = JSON.parse(txt);
                        console.error('Server error details:', errorData);
                        ribbonMessage = `Ribbons saved but failed to save quiz: ${errorData.error || 'Unknown error'}`;
                    } catch {
                        ribbonMessage = 'Ribbons saved but failed to save quiz. Please try again.';
                    }
                    
                    savingRibbons = false;
                    return;
                }
                const saveResult = await saveResp.json();
                console.log('Save quiz result:', saveResult);
                if (!saveResult.success) { 
                    console.error('Quiz save failed:', saveResult.error || saveResult.message);
                    ribbonMessage = ribbons > 0 ? 'Ribbons saved but failed to save quiz. Please try again.' : 'Failed to save quiz. Please try again.'; 
                    savingRibbons = false; 
                    return; 
                }

                ribbonMessage = ribbons > 0 ? 'Ribbons and quiz saved! 🎉' : 'Quiz saved! 📝';
                await new Promise(res => setTimeout(res, 600));
                try { localStorage.removeItem('pending_story'); } catch {}
                showSummary = false; showConfirm = false; goto('/student/game/trash_2');
            } catch (e) { 
                console.error('Error while saving quiz rows:', e); 
                const error = e as Error;
                console.error('Error stack:', error.stack);
                ribbonMessage = `Ribbons saved but network error while saving quiz: ${error.message}`; 
                savingRibbons = false; 
                return; 
            }

        } catch (e) { console.warn('Error while updating ribbons', e); ribbonMessage = 'Network error while saving ribbons.'; } finally { savingRibbons = false; }
    }

    async function continueAndClaim() {
        try { await continueToQuiz(); if (!lastAttempt) return; await claimRibbonsAndContinue(); } catch (e) { console.warn('Error in continueAndClaim', e); goto('/student/game/trash_2'); }
    }

    async function continueWithoutSaving() {
        try { localStorage.removeItem('pending_story'); } catch {}
        // If the student has already leveled up, clear answers for this story each time they close
        try {
            if (isLevelCompleted) {
                resetLevelAnswers(storyKey);
            }
        } catch (e) { console.warn('Failed to reset answers on close', e); }
        showSummary = false; showConfirm = false; goto('/student/game/trash_2');
    }

    function retakeLevel() {
        if (isLevelCompleted) { 
            console.log('Level already completed — retake will not be recorded');
            showConfirm = false; 
            try { localStorage.removeItem('pending_story'); } catch {} 
            try { localStorage.setItem('openStory2Modal','true'); } catch {} 
            
            // Clear Level 2 answers by updating the store
            try {
                studentData.update((data: any) => {
                    if (!data) return data;
                    const answeredQuestions = data.answeredQuestions || {};
                    
                    const clearedQuestions = Object.fromEntries(
                        Object.entries(answeredQuestions).filter(([key]) => !key.startsWith('story2'))
                    );
                    
                    console.log('Cleared Level 2 answers on retake (completed level). Remaining answers:', clearedQuestions);
                    return { ...data, answeredQuestions: clearedQuestions };
                });
            } catch (e) {
                console.warn('Failed to reset Level 2 answers on retake', e);
            }
            
            // Save current audio track before navigation
            audioStore.saveCurrentTrack();
            
            try { location.replace(`${location.origin}/student/play?level=2&retake=${Date.now()}`); } catch (e) { goto(`/student/play?level=2&retake=${Date.now()}`); }
            return; 
        }
        if (!canRetake()) { showConfirm = false; continueToQuiz(); return; }

        try {
            const snapshot = $studentData || {};
            const answers = snapshot.answeredQuestions || {};
            const score = Object.keys(correctAnswers[storyKey] || {}).reduce((acc, qid) => {
                const userRaw = answers[qid];
                const correctRaw = correctAnswers[storyKey][qid];
                if (!userRaw || !correctRaw) return acc;
                try {
                    const userMap = typeof userRaw === 'string' ? JSON.parse(userRaw) : userRaw;
                    const correctMap = typeof correctRaw === 'string' ? JSON.parse(correctRaw) : correctRaw;
                    const keys = Object.keys(correctMap || {});
                    const matched = keys.every(k => String(userMap[k]) === String(correctMap[k]));
                    // count per-subitem
                    const matchedCount = keys.reduce((mAcc, k) => mAcc + (String(userMap[k]) === String(correctMap[k]) ? 1 : 0), 0);
                    return acc + matchedCount;
                } catch (e) {
                    return acc + (String(userRaw) === String(correctRaw) ? 1 : 0);
                }
            }, 0);
            const attemptRecord = { studentId: snapshot?.pk_studentID || null, storyKey, answers, score, retakeCount: retakeCount + 1, timestamp: Date.now() };
            addAttempt(attemptRecord);
        } catch (e) { console.warn('Failed to save attempt record', e); }

    try {
        // Clear Level 2 answers by updating the store (which will sync to localStorage)
    studentData.update((data: any) => {
            if (!data) return data;
            const answeredQuestions = data.answeredQuestions || {};
            
            // Remove all keys that start with 'story2'
            const clearedQuestions = Object.fromEntries(
                Object.entries(answeredQuestions).filter(([key]) => !key.startsWith('story2'))
            );
            
            console.log('Cleared Level 2 answers on retake. Remaining answers:', clearedQuestions);
            return { ...data, answeredQuestions: clearedQuestions };
        });
    } catch (e) {
        console.warn('Failed to reset Level 2 answers on retake', e);
    }
    incRetakeCount(); try { localStorage.setItem(levelRetakeFlagKey, 'true'); } catch {}
        try { localStorage.removeItem('pending_story'); } catch {}
        // Ensure the play page knows to open the Level 2 story chooser/modal on load
        try { localStorage.setItem('openStory2Modal', 'true'); } catch {}
        
        // Save current audio track before navigation
        audioStore.saveCurrentTrack();
        
        showConfirm = false;
        try { location.replace(`${location.origin}/student/play?level=2&retake=${Date.now()}`); } catch (e) { goto(`/student/play?level=2&retake=${Date.now()}`); }
    }

</script>

<div class="flex flex-col justify-center items-center text-center slide">
    {#if slide.image}
        <div class="image-container">
            <img src="/assets/school-bg.gif" alt="Congrats Scene" class="block mx-auto rounded-[2vw] shadow-lg" />
        </div>
    {/if}

    <p class="text-[4vw] md:text-2xl text-gray-800 font-semibold">{slide.text}</p>

    <button class="mt-[2vh] bg-teal-300 text-gray-900 px-[6vw] py-[2vh] rounded-[3vw] text-[5vw] md:text-2xl font-bold shadow-md hover:bg-teal-400 flex items-center justify-center" on:click={() => { if (isLevelCompleted) { continueToQuiz(); } else { showConfirm = true; } }}>
        Continue 🌟
    </button>

    {#if showConfirm}
        <div class="confirm-overlay">
            <div class="confirm-modal">
                {#if hasPerfectScore}
                    <h3 class="confirm-title">Wow! Perfect Score! 🎉</h3>
                    <p class="confirm-text">You got all answers right! You're a star! 🌟</p>
                    <div class="confirm-actions">
                        <button class="confirm-btn proceed" on:click={continueToQuiz}>Go Next ➡️</button>
                    </div>
                {:else}
                    <h3 class="confirm-title">Try again or go next? 😊</h3>
                    <p class="confirm-text">
                        {#if attemptsRemaining > 0}
                            This is try {attemptsUsed} of {attemptsLimit}! You have {attemptsRemaining} {attemptsRemaining === 1 ? 'try' : 'tries'} left! 🌟
                        {:else}
                            You used all {attemptsLimit} tries! Time to go next! 🚀
                        {/if}
                    </p>
                    <div class="confirm-actions">
                        {#if isLevelCompleted}
                            <!-- Level already completed: only allow continue -->
                            <button class="confirm-btn proceed" on:click={continueToQuiz}>Go Next ➡️</button>
                        {:else}
                            {#if attemptsRemaining > 0}
                                <button class="confirm-btn retake" on:click={retakeLevel}>Try Again ({attemptsRemaining} left) ⟲</button>
                                <button class="confirm-btn proceed" on:click={continueToQuiz}>Go Next ➡️</button>
                                <button class="confirm-btn cancel" on:click={() => showConfirm = false}>Stay Here ✕</button>
                            {:else}
                                <button class="confirm-btn proceed" on:click={continueToQuiz}>Go Next ➡️</button>
                            {/if}
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
    {/if}

    {#if showSummary}
        <div class="summary-overlay">
            <div class="summary-modal">
                <div class="confetti" aria-hidden="true">
                    <span style="--i:0"></span>
                    <span style="--i:1"></span>
                    <span style="--i:2"></span>
                    <span style="--i:3"></span>
                    <span style="--i:4"></span>
                    <span style="--i:5"></span>
                    <span style="--i:6"></span>
                    <span style="--i:7"></span>
                </div>

                <header class="summary-header">
                    <div>
                        <h2 class="summary-title">🏆Great Job! 🎉</h2>
                        <p class="summary-sub">Here's how you did on the quiz</p>
                        {#if isLevelCompleted}
                            <p class="summary-note" style="color:#9ca3af;font-weight:700;margin-top:6px;">Note: You've already completed Level 2 — this attempt will not be recorded.</p>
                        {/if}
                    </div>
                </header>

                <section class="summary-body">
                    {#if lastAttempt}
                        <div class="score-row">
                            <div class="score-bubble">{lastAttempt.score}</div>
                            <div class="score-meta">
                                <div class="score-text">Points</div>
                                <div class="score-sub">{lastAttempt.score} / {totalPossible}</div>
                            </div>
                            <div class="ribbons-preview">
                                {#each Array(lastAttempt.score) as _}
                                    <span class="ribbon">🎀</span>
                                {/each}
                            </div>
                        </div>

                        <p class="attempts-text">Attempts used: <strong>{1 + lastAttempt.retakeCount}</strong></p>

                        <div class="questions-list" style="text-align:left;">
                            {#each Object.keys(correctAnswers[storyKey] || {}) as qid}
                                <article class="q-card">
                                    <div class="q-top" style="align-items:flex-start;">
                                        <div class="q-text">{questionsByStory?.[storyKey]?.[qid]?.text || ''}</div>
                                    </div>

                                    {#if parseMapping(correctAnswers[storyKey]?.[qid])}
                                        {@const maps = getMaps(lastAttempt?.answers || {}, qid)}
                                        <div class="mapping-list">
                                            {#each Object.entries(maps.correctMap || {}) as [subk, subv]}
                                                {@const userVal = (maps.userMap && maps.userMap[subk]) || ''}
                                                <div class="mapping-item" class:correct={String(userVal) === String(subv)} class:wrong={String(userVal) !== String(subv)}>
                                                    <div class="canonical-text">{subk}. {subv}</div>
                                                    <div class="your-answer">
                                                        <div class="your-label">Your answer</div>
                                                        <div class="your-value">{userVal}</div>
                                                        <span class="badge" class:correct={String(userVal) === String(subv)} class:wrong={String(userVal) !== String(subv)} aria-label={String(userVal) === String(subv) ? 'Correct' : 'Incorrect'}>
                                                            {String(userVal) === String(subv) ? 'Correct' : 'Incorrect'}
                                                        </span>
                                                    </div>
                                                </div>
                                            {/each}
                                        </div>
                                    {:else}
                                        {@const selectedKey = lastAttempt.answers?.[qid]}
                                        {@const selectedText = questionsByStory?.[storyKey]?.[qid]?.choices?.[selectedKey] || selectedKey}
                                        <div class="mcq-answer" class:correct={selectedKey === correctAnswers[storyKey]?.[qid]} class:wrong={selectedKey !== correctAnswers[storyKey]?.[qid]}>
                                            <div class="your-label">Your answer</div>
                                            <div class="your-value">{selectedText}</div>
                                            <span class="badge" class:correct={selectedKey === correctAnswers[storyKey]?.[qid]} class:wrong={selectedKey !== correctAnswers[storyKey]?.[qid]} aria-label={selectedKey === correctAnswers[storyKey]?.[qid] ? 'Correct' : 'Incorrect'}>
                                                {selectedKey === correctAnswers[storyKey]?.[qid] ? 'Correct' : 'Incorrect'}
                                            </span>
                                        </div>
                                    {/if}
                                </article>
                            {/each}
                        </div>
                    {:else}
                        <p>No attempt data available.</p>
                    {/if}
                </section>

                <footer class="summary-footer">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:0.4rem;">
                        {#if isLevelCompleted || alreadyClaimed || quizAlreadyExists}
                            <button class="btn-claim" on:click={continueWithoutSaving}>
                                Continue to Game ➡️
                            </button>
                            {#if quizAlreadyExists}
                                <p style="font-size:0.9rem;color:#666;margin-top:0.5rem;">
                                    You have already claimed ribbons for Level 2
                                </p>
                            {/if}
                        {:else}
                            <button class="btn-claim" on:click={claimRibbonsAndContinue} disabled={savingRibbons || checkingDatabase} aria-busy={savingRibbons || checkingDatabase}>
                                {#if checkingDatabase}
                                    Checking...
                                {:else if savingRibbons}
                                    Saving...
                                {:else}
                                    Claim Ribbons & Continue
                                {/if}
                            </button>
                        {/if}
                        {#if ribbonMessage}
                            <div class="ribbon-message" role="status" aria-live="polite">{ribbonMessage}</div>
                        {/if}
                    </div>
                    <button class="btn-close" on:click={continueWithoutSaving}>Close</button>
                </footer>
            </div>
        </div>

    {/if}
</div>

<style>
    /* reuse the kid-friendly styles from Level1's slide_last for consistency */
    .image-container { width: 65%; height: auto; max-width: 800px; max-height: 400px; margin-bottom: 2vh; display: flex; justify-content: center; align-items: center; }
    .image-container img { width: 100%; height: 100%; object-fit: contain; }

    /* Confirmation modal styles (copied from Level1) */
    .confirm-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .confirm-modal { background: #fff; border-radius: 1rem; padding: 1.5rem; width: min(90vw, 480px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); text-align: center; }
    .confirm-title { font-size: clamp(1.5rem, 4vw, 1.75rem); font-weight: 800; color: #111827; margin-bottom: 0.75rem; }
    .confirm-text { font-size: clamp(1.1rem, 3vw, 1.25rem); color: #374151; margin-bottom: 1rem; font-weight: 600; }
    .confirm-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    .confirm-btn { padding: 0.75rem 1rem; border-radius: 0.75rem; border: none; font-weight: 700; font-size: clamp(1rem, 2.5vw, 1.125rem); cursor: pointer; }
    .confirm-btn.retake { background: #fef3c7; color: #92400e; box-shadow: 0 2px 6px rgba(146,64,14,0.2); }
    .confirm-btn.proceed { background: #d1fae5; color: #065f46; box-shadow: 0 2px 6px rgba(6,95,70,0.2); }
    .confirm-btn.cancel { grid-column: span 2; background: #e5e7eb; color: #111827; }

    .summary-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; z-index:1200; }
    .summary-modal { width:min(95vw,960px); max-height:90vh; background: linear-gradient(180deg,#fff9f1 0%, #fff 50%); border-radius: 1rem; padding:1rem; box-shadow: 0 15px 45px rgba(0,0,0,0.25); overflow:auto; position:relative; }
    .confetti { position:absolute; inset:0; pointer-events:none; }
    .confetti span { position:absolute; width:10px; height:14px; background:var(--c,#ffcc00); opacity:0.95; transform:translateY(-20vh) rotate(0deg); animation: fall 1200ms linear infinite; }
    .confetti span:nth-child(1){ left:10%; --c:#ff7a7a; animation-delay:0s; }
    .confetti span:nth-child(2){ left:20%; --c:#ffd36b; animation-delay:0.1s; }
    .confetti span:nth-child(3){ left:30%; --c:#8bd3ff; animation-delay:0.2s; }
    .confetti span:nth-child(4){ left:40%; --c:#c3ff9a; animation-delay:0.3s; }
    .confetti span:nth-child(5){ left:50%; --c:#d6b3ff; animation-delay:0.4s; }
    .confetti span:nth-child(6){ left:60%; --c:#ff9ad6; animation-delay:0.5s; }
    .confetti span:nth-child(7){ left:70%; --c:#7ef0b7; animation-delay:0.6s; }
    .confetti span:nth-child(8){ left:80%; --c:#ffd48f; animation-delay:0.7s; }
    @keyframes fall { 0%{ transform:translateY(-30vh) rotate(0deg); } 100%{ transform:translateY(120vh) rotate(360deg); opacity:1; } }
    /* Header: compact centered text */
    .summary-header { display:flex; flex-direction:column; gap:0.25rem; align-items:center; padding:0.5rem 0; text-align:center; }
    .summary-title { font-size:1.8rem; margin:0; color:#0f172a; text-align:center; }
    .summary-sub { margin:0; color:#475569; text-align:center; }
     /* Let the modal scroll; allow the body to size naturally inside the modal
         Add bottom padding so the sticky footer does not cover the last content */
     .summary-body { padding:0.5rem 0.5rem 3.5rem; }
    .score-row { display:flex; align-items:center; gap:1rem; background:linear-gradient(90deg,#fff0de,#fff9f1); padding:0.6rem; border-radius:0.75rem; margin-bottom:0.5rem; }
    .score-bubble { background:#ffefc7; border-radius:999px; width:64px; height:64px; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:1.6rem; box-shadow:0 8px 20px rgba(0,0,0,0.12); }
    .score-meta { display:flex; flex-direction:column; }
    .score-text { color:#374151; font-weight:700; }
    .score-sub { color:#6b7280; }
    .ribbons-preview { margin-left:auto; display:flex; gap:0.4rem; }
    /* confetti removed */
    .ribbon { font-size:2rem; transform-origin:center; }
    .attempts-text { font-weight:700; color:#374151; margin-bottom:0.5rem; }
    .questions-list { display:grid; gap:0.6rem; }
    /* Questions live on a white card so answers can have a different, muted background */
    .q-card { background: #ffffff; border-radius:0.75rem; padding:0.6rem; box-shadow:0 8px 18px rgba(15,23,42,0.06); }
    .q-top { display:flex; gap:0.6rem; align-items:flex-start; }
    /* .q-number removed: not used in Level2 layout */
    .q-text { font-weight:800; color:#0f172a; }
    /* MCQ choice styles removed (not used) */

    /* Mapping / answer layout styles for kid-friendly display */
    .mapping-item { padding:0.5rem 0; border-bottom:1px dashed rgba(15,23,42,0.04); }
    /* Question vs Answer colors: questions are darker, answers are muted/different */
    .canonical-text { font-weight:800; color:#0b1220; margin-bottom:0.35rem; }
    .your-answer { display:flex; align-items:center; gap:0.6rem; }
    .your-label { font-size:0.85rem; color:#6b7280; font-weight:700; }
    .your-value { background:#f3f4f6; padding:0.35rem 0.6rem; border-radius:0.4rem; font-weight:400; color:#374151; }
    /* Use soft background tints instead of a strong colored border before the question */
    /* Keep mapping-item/container backgrounds neutral; tint only the answer pills so they contrast with the question card */
    .mapping-item.correct { background: transparent; }
    .mapping-item.wrong { background: transparent; }
    .mcq-answer.correct { background: transparent; padding:0; border-radius:0; }
    .mcq-answer.wrong { background: transparent; padding:0; border-radius:0; }
    /* Slightly tint the answer pill based on correctness, keep it subtle */
    /* Answer pill backgrounds: subtle green/red tints for correctness */
    .your-value { background:#f7fafc; }
    .mapping-item.correct .your-value, .mcq-answer.correct .your-value { background: #ecfdf5; }
    .mapping-item.wrong .your-value, .mcq-answer.wrong .your-value { background: #fff2f2; }
    .badge.correct { background:#10b981; color:white; padding:0.15rem 0.5rem; border-radius:0.6rem; }
    .badge.wrong { background:#ef4444; color:white; padding:0.15rem 0.5rem; border-radius:0.6rem; }
    /* Sticky footer so claim/close remain visible while modal scrolls */
    .summary-footer {
        position: sticky;
        bottom: 0;
        display:flex;
        gap:0.6rem;
        justify-content:center;
        padding:0.6rem 0.75rem;
        background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(255,255,255,0.98));
        backdrop-filter: blur(4px);
        border-top: 1px solid rgba(15,23,42,0.04);
        z-index: 30;
    }
    .btn-claim { background:#06b6d4; color:white; padding:0.6rem 0.9rem; border-radius:0.6rem; font-weight:800; box-shadow:0 8px 20px rgba(6,182,212,0.18); }
    .btn-close { background:#e5e7eb; color:#111827; padding:0.6rem 0.9rem; border-radius:0.6rem; font-weight:700; }
    .btn-claim[disabled], .btn-claim[aria-busy='true'] { opacity: 0.6; transform: none; cursor: default; }
    .ribbon-message { color: #065f46; font-weight: 700; font-size: 0.95rem; }
    @media (max-width:640px) { .summary-modal { padding:0.6rem; } .score-bubble { width:52px; height:52px; } .ribbon { font-size:1.6rem; } }

    /* Override any global .slide animation to prevent fading/blanking when this slide is mounted */
    :global(.slide) { animation: none !important; transition: none !important; opacity: 1 !important; }
</style>
