<script lang="ts">
    import { goto } from '$app/navigation';
    import { resetLevelAnswers, studentData } from '$lib/store/student_data';
    import { addAttempt } from '$lib/data/attempts.js';
    // use Svelte auto-subscription ($studentData) instead of importing `get`

    // This final-slide mirrors Level1's UI/flow but adapted for Level 3
    export let storyKey: string = '';

    const slide = {
        text: "Yay! You finished the Level 3 story! 🎉",
        image: "../../assets/school-bg.gif"
    };

    // Show modal to ask "Try Again or Go Next?" 
    let showConfirm = false;
    // Note: Level 3 does not show quiz summary - goes directly to trash game

    // If the student has already reached level 3 or higher, we should not persist Level 3 attempts or award ribbons for this level.
    $: isLevelCompleted = ($studentData?.studentLevel || 0) >= 3;

    // Attempt limiting
    const attemptsLimit = 3;
    let retakeCount = 0;
    try { const v = localStorage.getItem(`retake${storyKey}Count`); retakeCount = v ? parseInt(v,10)||0 : 0; } catch {}

    // Define correct answers for each story. For DnD quizzes we store the
    // canonical mapping as a JSON string (the quiz saver uses JSON.stringify)
    const correctAnswers: Record<string, Record<string, string>> = {
        // TODO: populate with actual Level 3 quiz answers when available
        'story3-1': {},
        'story3-2': {},
        'story3-3': {}
    };

    // Note: avoid onMount import — use reactive $studentData where needed

    // Questions metadata for summary display (if/when available)
    const questionsByStory: Record<string, any> = {
        'story3-1': {},
        'story3-2': {},
        'story3-3': {}
    };

    const storyTitles: Record<string, string> = {
        'story3-1': "Tonya's Tooth",
        'story3-2': "Lola Gloria's Flowerpot",
        'story3-3': "Liloy and Liling the Dog"
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
    function incRetakeCount() { retakeCount += 1; try { localStorage.setItem(`retake${storyKey}Count`, String(retakeCount)); } catch {} }

    // Derived attempt counters for UI
    let attemptsUsed = 1 + retakeCount;
    let attemptsRemaining = Math.max(0, attemptsLimit - attemptsUsed);
    $: attemptsUsed = Math.min(attemptsLimit, 1 + retakeCount);
    $: attemptsRemaining = Math.max(0, attemptsLimit - attemptsUsed);

    // Level 3: Skip quiz summary, go directly to trash game
    async function continueToGame() {
        try {
            // Save quiz to database before continuing (if not already completed)
            if (!isLevelCompleted) {
                await saveQuizToDatabase();
            }
            
            // Clear answers for this story if level already completed
            if (isLevelCompleted) {
                resetLevelAnswers(storyKey);
            }
        } catch (e) {
            console.warn('Failed to save quiz or reset answers', e);
        }
        
        // Navigate directly to trash game 3
        goto('/student/game/trash_3');
    }

    async function saveQuizToDatabase() {
        try {
            const snapshot = $studentData || {};
            const studentId = snapshot?.pk_studentID || null;
            const answers = snapshot.answeredQuestions || {};
            
            if (!studentId) {
                console.warn('No student ID found, skipping quiz save');
                return;
            }

            // Filter answers for this story only
            const storyAnswers: Record<string, string> = {};
            const prefix = `${storyKey}_`;
            for (const key in answers) {
                if (key.startsWith(prefix)) {
                    storyAnswers[key] = answers[key];
                }
            }

            // Only save if there are answers
            if (Object.keys(storyAnswers).length === 0) {
                console.warn('No answers found for this story, skipping quiz save');
                return;
            }

            // Create attempt record
            const attemptRecord = {
                studentId,
                storyKey,
                answers: storyAnswers,
                score: 0, // Level 3 requires manual grading
                retakeCount,
                timestamp: Date.now()
            };

            // Build payload for API
            const payload = {
                student_id: studentId,
                storyKey,
                storyTitle: storyTitles[storyKey] || storyKey,
                attempt: attemptRecord,
                questions: questionsByStory[storyKey] || {}
            };

            // Determine backend origin
            const backendOrigin = (location.port && location.port !== '80' && location.port !== '443') 
                ? `${location.protocol}//${location.hostname}` 
                : location.origin;
            const saveUrl = `${backendOrigin}/shenieva-teacher/src/lib/api/submit_level3_quiz.php`;
            
            console.log('Saving Level 3 quiz to database:', saveUrl, payload);
            
            const response = await fetch(saveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const txt = await response.text().catch(() => 'no response body');
                console.error('Quiz save API returned non-OK', response.status, txt);
                return;
            }

            const result = await response.json();
            if (result.success) {
                console.log('Quiz saved successfully:', result.message || 'Pending teacher review');
                // Add to local attempts log
                addAttempt(attemptRecord);
            } else {
                console.error('Quiz save failed:', result.error);
            }
        } catch (e) {
            console.error('Error saving quiz to database:', e);
            // Don't block navigation if save fails
        }
    }

    function retakeLevel() {
        // If level is already completed, do not record a retake or persist attempts — just navigate
        if (isLevelCompleted) { 
            console.log('Level already completed — retake will not be recorded');
            showConfirm = false; 
            try { localStorage.removeItem('pending_story'); } catch {} 
            try { localStorage.setItem('openStory3Modal','true'); } catch {} 
            goto(`/student/play?level=3&retake=${Date.now()}`); 
            return; 
        }
        if (!canRetake()) { 
            showConfirm = false; 
            continueToGame(); 
            return; 
        }

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

        studentData.update((data:any) => { if (!data) return data; return { ...data, answeredQuestions: {} }; });
        try { const snapshot = $studentData; localStorage.setItem('studentData', JSON.stringify(snapshot)); } catch (e) { console.warn('Failed to persist studentData after retake', e); }
        incRetakeCount(); try { localStorage.setItem(`retake${storyKey}`, 'true'); } catch {}
        try { localStorage.removeItem('pending_story'); } catch {}
        // Ensure the play page knows to open the Level 3 story chooser/modal on load
        try { localStorage.setItem('openStory3Modal', 'true'); } catch {}
        showConfirm = false;
        try { location.replace(`${location.origin}/student/play?level=3&retake=${Date.now()}`); } catch (e) { goto(`/student/play?level=3&retake=${Date.now()}`); }
    }

</script>

<div class="flex flex-col justify-center items-center text-center slide">
    {#if slide.image}
        <div class="image-container">
            <img src="/src/assets/school-bg.gif" alt="Congrats Scene" class="block mx-auto rounded-[2vw] shadow-lg" />
        </div>
    {/if}

    <p class="text-[4vw] md:text-2xl text-gray-800 font-semibold">{slide.text}</p>

    <button class="mt-[2vh] bg-teal-300 text-gray-900 px-[6vw] py-[2vh] rounded-[3vw] text-[5vw] md:text-2xl font-bold shadow-md hover:bg-teal-400 flex items-center justify-center" on:click={() => { if (isLevelCompleted) { continueToGame(); } else { showConfirm = true; } }}>
        Continue 🌟
    </button>

    {#if showConfirm}
        <div class="confirm-overlay">
            <div class="confirm-modal">
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
                        <button class="confirm-btn proceed" on:click={continueToGame}>Go Next ➡️</button>
                    {:else}
                        {#if attemptsRemaining > 0}
                            <button class="confirm-btn retake" on:click={retakeLevel}>Try Again ({attemptsRemaining} left) ⟲</button>
                            <button class="confirm-btn proceed" on:click={continueToGame}>Go Next ➡️</button>
                            <button class="confirm-btn cancel" on:click={() => showConfirm = false}>Stay Here ✕</button>
                        {:else}
                            <button class="confirm-btn proceed" on:click={continueToGame}>Go Next ➡️</button>
                        {/if}
                    {/if}
                </div>
            </div>
        </div>
    {/if}
</div>

<style>
    .image-container { 
        width: 65%; 
        height: auto; 
        max-width: 800px; 
        max-height: 400px; 
        margin-bottom: 2vh; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
    }
    
    .image-container img { 
        width: 100%; 
        height: 100%; 
        object-fit: contain; 
    }

    /* Confirmation modal styles */
    .confirm-overlay { 
        position: fixed; 
        inset: 0; 
        background: rgba(0,0,0,0.5); 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        z-index: 1000; 
    }
    
    .confirm-modal { 
        background: #fff; 
        border-radius: 1rem; 
        padding: 1.5rem; 
        width: min(90vw, 480px); 
        box-shadow: 0 10px 25px rgba(0,0,0,0.2); 
        text-align: center; 
    }
    
    .confirm-title { 
        font-size: clamp(1.5rem, 4vw, 1.75rem); 
        font-weight: 800; 
        color: #111827; 
        margin-bottom: 0.75rem; 
    }
    
    .confirm-text { 
        font-size: clamp(1.1rem, 3vw, 1.25rem); 
        color: #374151; 
        margin-bottom: 1rem; 
        font-weight: 600; 
    }
    
    .confirm-actions { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 0.75rem; 
    }
    
    .confirm-btn { 
        padding: 0.75rem 1rem; 
        border-radius: 0.75rem; 
        border: none; 
        font-weight: 700; 
        font-size: clamp(1rem, 2.5vw, 1.125rem); 
        cursor: pointer; 
    }
    
    .confirm-btn.retake { 
        background: #fef3c7; 
        color: #92400e; 
        box-shadow: 0 2px 6px rgba(146,64,14,0.2); 
    }
    
    .confirm-btn.proceed { 
        background: #d1fae5; 
        color: #065f46; 
        box-shadow: 0 2px 6px rgba(6,95,70,0.2); 
    }
    
    .confirm-btn.cancel { 
        grid-column: span 2; 
        background: #e5e7eb; 
        color: #111827; 
    }

    /* Override any global .slide animation */
    :global(.slide) { 
        animation: none !important; 
        transition: none !important; 
        opacity: 1 !important; 
    }
</style>
