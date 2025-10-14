<script lang="ts">
    import { goto } from '$app/navigation';
    import { resetLevelAnswers, studentData } from '$lib/store/student_data';
    import { addAttempt } from '$lib/data/attempts.js';
    // use Svelte auto-subscription ($studentData) instead of importing `get`

    export let storyKey: string = ''; // Prop to identify the current story (story1-1, story1-2, story1-3)

    const slide = {
        text: "Yay! You finished your adventure in Readville Village! 🎉",
        image: "../../assets/school-bg.gif"
    };

    // Show modal to ask "Try Again or Go Next?" or congratulate for perfect score
    let showConfirm = false;
    let showSummary = false;
    let lastAttempt: any = null;

    // If the student has already reached level 1 or higher,
    // we should not persist Level 1 attempts or award ribbons for this level.
    $: isLevelCompleted = ($studentData?.studentLevel || 0) >= 1;

    // Attempt limiting (3 total tries; first play counts as try 1)
    const attemptsLimit = 3;
    let retakeCount = 0; // number of retakes performed
    try {
        const v = localStorage.getItem(`retake${storyKey}Count`);
        retakeCount = v ? parseInt(v, 10) || 0 : 0;
    } catch {}

    // Define correct answers for each story
    const correctAnswers: Record<string, Record<string, string>> = {
        'story1-1': {
            'story1_q1': 'b', // Slide 5
            'story1_q2': 'b', // Slide 8
            'story1_q3': 'c'  // Slide 9
        },
        'story1-2': {
            'story1_2_q1': 'a', // Slide 4
            'story1_2_q2a': 'b', // Slide 6
            'story1_2_q2b': 'c'  // Slide 7
        },
        'story1-3': {
            'story1_3_q1': 'c', // Slide 4
            'story1_3_q2a': 'b', // Slide 6
            'story1_3_q2b': 'b'  // Slide 7
        }
    };

    // No onMount logging — use reactive $studentData where needed

    // Map of question texts and choices for summary display
    const questionsByStory = {
        'story1-1': {
            'story1_q1': {
                text: '1. What do you think Lena will do next based on her actions?',
                choices: { 
                    a: 'Refuse to let Maria take the items until she pays.', 
                    b: 'Let Maria take the items and pay later, trusting her.', 
                    c: 'Ask Maria to leave and come back when she has a change for her money.' 
                }
            },
            'story1_q2': {
                text: '2. Based on Maria\'s actions, what will happen next?',
                choices: { 
                    a: 'Maria will refuse to pay.', 
                    b: 'Maria came back to pay and thanked Lena.', 
                    c: 'Maria will be disappointed that Lena is waiting for her to pay.' 
                }
            },
            'story1_q3': {
                text: '3. How do you think Lena will feel after Maria finally pays the money she owes?',
                choices: { 
                    a: 'sad and hurt', 
                    b: 'scared and guilty', 
                    c: 'relieved and happy' 
                }
            }
        },
        'story1-2': {
            'story1_2_q1': {
                text: '1. What do you think will happen to Candice?',
                choices: { a: 'She will have a toothache.', b: 'She will enjoy eating more candies.', c: 'She will be frustrated because the candy she bought is not enough.' }
            },
            'story1_2_q2a': {
                text: '2. After what happened, what do you think Candice will do next?',
                choices: { a: 'She will buy more candies.', b: 'She will listen to her mother and apologize.', c: 'She will tell her friends that they should not brush their teeth after eating candies just like her.' }
            },
            'story1_2_q2b': {
                text: '3. What is the lesson Candice learned after what happened to her?',
                choices: { a: 'Always buy candies because it makes her happy.', b: 'It is okay to eat candies as long as her mother will not find out.', c: 'Take better care of her teeth by brushing it and avoid eating too much sweets.' }
            }
        },
        'story1-3': {
            'story1_3_q1': {
                text: '1. What do you think Hannah will do after realizing the mistake?',
                choices: { 
                    a: 'She will keep the extra money.', 
                    b: 'She will forget about it and continue selling.', 
                    c: 'She will go after the customer to return the extra money.' 
                }
            },
            'story1_3_q2a': {
                text: '2. After Hannah returns the extra money to the customer, what do you think the customer will do?',
                choices: { 
                    a: 'Ignore Hannah and walk away.', 
                    b: 'Thank Hannah and praise her honesty.', 
                    c: 'Ask Hannah why she returned the money late.' 
                }
            },
            'story1_3_q2b': {
                text: '3. How would Hannah feel after returning the money and seeing the customer\'s reaction?',
                choices: { 
                    a: 'sad and guilty', 
                    b: 'proud and happy', 
                    c: 'confused and embarrassed' 
                }
            }
        }
        // Add other stories here if needed
    };

    // Human-readable story titles
    const storyTitles: Record<string, string> = {
        'story1-1': "Maria's Promise",
        'story1-2': 'Candice and Candies',
        'story1-3': 'Hannah, the Honest Vendor'
    };

    // Check if the user has a perfect score
    // NOTE: require every expected question to be answered (non-empty) AND correct
    $: hasPerfectScore = (() => {
        if (!storyKey || !correctAnswers[storyKey]) {
            console.log('Invalid storyKey or no correct answers', { storyKey });
            return false;
        }
        const answers = $studentData?.answeredQuestions || {};
        const expectedQuestions = Object.keys(correctAnswers[storyKey]);
        if (!expectedQuestions || expectedQuestions.length === 0) return false;

        const isPerfect = expectedQuestions.every(qid => {
            const userAnswer = answers[qid];
            // If any question is missing or empty, it's not a perfect score
            if (userAnswer === undefined || userAnswer === null || userAnswer === '') {
                console.log('Question unanswered — not perfect', { questionId: qid, userAnswer });
                return false;
            }
            const isCorrect = userAnswer === correctAnswers[storyKey][qid];
            if (!isCorrect) console.log('Question incorrect', { questionId: qid, userAnswer, correctAnswer: correctAnswers[storyKey][qid] });
            return isCorrect;
        });
        console.log('hasPerfectScore', isPerfect, { storyKey, answers, expectedQuestions });
        return isPerfect;
    })();

    function canRetake() {
        return (1 + retakeCount) < attemptsLimit;
    }

    function incRetakeCount() {
        retakeCount += 1;
        try { localStorage.setItem(`retake${storyKey}Count`, String(retakeCount)); } catch {}
    }

    // Derived try counters for UI
    let attemptsUsed = 1 + retakeCount;
    let attemptsRemaining = Math.max(0, attemptsLimit - attemptsUsed);
    $: attemptsUsed = Math.min(attemptsLimit, 1 + retakeCount);
    $: attemptsRemaining = Math.max(0, attemptsLimit - attemptsUsed);

    async function continueToQuiz() {
        // Save attempt record and show summary modal before navigating
        try {
            const snapshot = $studentData || {};
            const answers = snapshot.answeredQuestions || {};
            const score = Object.keys(correctAnswers[storyKey] || {}).reduce((acc, qid) => {
                if (answers[qid] && answers[qid] === correctAnswers[storyKey][qid]) return acc + 1;
                return acc;
            }, 0);

            const attemptRecord = {
                studentId: snapshot?.pk_studentID || null,
                storyKey,
                answers,
                score,
                retakeCount: retakeCount,
                timestamp: Date.now(),
            };

            // If student already completed level 1, do not persist attempts locally
            if (isLevelCompleted) {
                console.log('Level already completed — skipping attempt persistence', { studentLevel: $studentData?.studentLevel, storyKey });
                // set lastAttempt so the UI can show the summary but do not call addAttempt
                lastAttempt = attemptRecord;
                showSummary = true;
                try { localStorage.setItem('pending_story', storyKey); } catch {}
                return;
            }

            addAttempt(attemptRecord);
            console.log('Saved attempt record before continue', attemptRecord);

            // save lastAttempt for the summary modal
            lastAttempt = attemptRecord;
            showSummary = true;
            try { localStorage.setItem('pending_story', storyKey); } catch {}
        } catch (e) { console.warn('Failed to save attempt record', e); showConfirm = false; }
    }

    // Call backend to add ribbons (studentRibbon) and update store, then navigate to game
    let savingRibbons = false;
    let ribbonMessage = '';

    async function claimRibbonsAndContinue() {
        if (!lastAttempt) return;
    const snapshot = $studentData || {};
        const studentId = snapshot?.pk_studentID || null;
        const ribbons = lastAttempt.score || 0;
        if (!studentId || ribbons <= 0) {
            // nothing to update, just continue
            showSummary = false;
            goto('/student/game/trash_1');
            return;
        }

        savingRibbons = true;
        ribbonMessage = '';
        try {
            // If level already completed, don't update ribbons or persist quiz rows
            if (isLevelCompleted) {
                console.log('Level already completed — skipping ribbons/quiz persistence');
                ribbonMessage = 'Level already completed — this attempt will not be recorded.';
                // small delay so user sees the message
                await new Promise(res => setTimeout(res, 800));
                showSummary = false;
                showConfirm = false;
                // clear pending flag if set
                try { localStorage.removeItem('pending_story'); } catch {}
                goto('/student/game/trash_1');
                return;
            }
            // Update DB via existing PHP endpoint
            const response = await fetch('http://localhost/shenieva-teacher/src/lib/api/update_student_ribbons.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ student_id: studentId, ribbons })
            });
            const result = await response.json();
            if (!result.success) {
                console.warn('Failed to update ribbons on server', result.error);
                ribbonMessage = 'Failed to save ribbons. Please try again.';
                savingRibbons = false;
                return;
            }

            // On success, update local store first
            studentData.update((d: any) => {
                if (!d) return d;
                const current = d.studentRibbon || 0;
                return { ...d, studentRibbon: current + ribbons };
            });

            // Now submit the detailed quiz rows to level1_quiz table
            try {
                const snapshot = $studentData || {};
                const payload = {
                    student_id: studentId,
                    storyKey,
                    storyTitle: storyTitles[storyKey] || storyKey,
                    attempt: lastAttempt,
                    questions: (questionsByStory as any)?.[storyKey] || {},
                    correctAnswers: correctAnswers[storyKey] || {}
                };

                const saveResp = await fetch('http://localhost/shenieva-teacher/src/lib/api/submit_level1_quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const saveResult = await saveResp.json();
                if (!saveResult.success) {
                    console.warn('Failed to save quiz rows', saveResult.error);
                    ribbonMessage = 'Ribbons saved but failed to save quiz. Please try again.';
                    savingRibbons = false;
                    return;
                }

                    ribbonMessage = 'Ribbons and quiz saved! 🎉';
                // small delay so user sees the message
                await new Promise(res => setTimeout(res, 600));
                    // clear pending flag now that everything is saved
                    try { localStorage.removeItem('pending_story'); } catch {}

                    showSummary = false;
                    showConfirm = false;
                    goto('/student/game/trash_1');
            } catch (e) {
                console.warn('Error while saving quiz rows', e);
                ribbonMessage = 'Ribbons saved but network error while saving quiz.';
                savingRibbons = false;
                return;
            }
        } catch (e) {
            console.warn('Error while updating ribbons', e);
            ribbonMessage = 'Network error while saving ribbons.';
        } finally {
            savingRibbons = false;
        }
    }

    // When level already completed, allow user to continue to the game without saving
    async function continueWithoutSaving() {
        // clear pending flag and navigate
        try { localStorage.removeItem('pending_story'); } catch {}
        showSummary = false;
        showConfirm = false;
        goto('/student/game/trash_1');
    }

    // For non-perfect flows where user chooses to continue, save attempt and claim ribbons
    async function continueAndClaim() {
        try {
            // ensure the attempt is saved and lastAttempt is set
            await continueToQuiz();
            // If continueToQuiz resulted in auto-claim (perfect), it already navigated away
            if (!lastAttempt) return;
            // Otherwise claim ribbons and continue
            await claimRibbonsAndContinue();
        } catch (e) {
            console.warn('Error in continueAndClaim', e);
            // Fallback navigation
            goto('/student/game/trash_1');
        }
    }

    // If level already completed we still want to allow the student to continue to the game
    // without attempting to save ribbons or quiz rows. (Implementation above.)

    function retakeLevel() {
        // If level is already completed, do not record a retake or persist attempts — just navigate
        if (isLevelCompleted) {
            console.log('Level already completed — retake will not be recorded');
            showConfirm = false;
            // clear pending flag if present
            try { localStorage.removeItem('pending_story'); } catch {}
            // Navigate back to level chooser (not specific story) so user can select any story
            try {
                location.replace(`${location.origin}/student/play?level=1&retake=${Date.now()}`);
            } catch (e) {
                goto(`/student/play?level=1&retake=${Date.now()}`);
            }
            return;
        }

        if (!canRetake()) {
            showConfirm = false;
            continueToQuiz();
            return;
        }

        // Save attempt record (this was the completed attempt prior to retake)
        try {
            const snapshot = $studentData || {};
            const answers = snapshot.answeredQuestions || {};
            const score = Object.keys(correctAnswers[storyKey] || {}).reduce((acc, qid) => {
                if (answers[qid] && answers[qid] === correctAnswers[storyKey][qid]) return acc + 1;
                return acc;
            }, 0);
            const attemptRecord = {
                studentId: snapshot?.pk_studentID || null,
                storyKey,
                answers,
                score,
                retakeCount: retakeCount + 1,
                timestamp: Date.now(),
            };
            addAttempt(attemptRecord);
            console.log('Saved attempt record before retake', attemptRecord);
        } catch (e) { console.warn('Failed to save attempt record', e); }

        // Reset answers for the specific story by replacing answeredQuestions with an empty object
        studentData.update((data: any) => {
            if (!data) return data;
            return { ...data, answeredQuestions: {} };
        });

        // Force persist to localStorage immediately to avoid race with navigation
        try {
            const snapshot = $studentData;
            localStorage.setItem('studentData', JSON.stringify(snapshot));
            console.log('Persisted studentData after retake (cleared all answers)', snapshot);
        } catch (e) {
            console.warn('Failed to persist studentData after retake', e);
        }
        // Increment retake count and persist
        incRetakeCount();
        // Flag retake and delegate to /student/play with level parameter (to open story chooser)
        try { localStorage.setItem(`retake${storyKey}`, 'true'); } catch {}
    try { localStorage.removeItem('pending_story'); } catch {}
        showConfirm = false;
        // Use location.replace to force a full reload so the app boots from persisted state
        try {
            location.replace(`${location.origin}/student/play?level=1&retake=${Date.now()}`);
        } catch (e) {
            // Fallback to client-side navigation
            goto(`/student/play?level=1&retake=${Date.now()}`);
        }
    }

    // Animations removed for accessibility / instant rendering
</script>

<div class="flex flex-col justify-center items-center text-center slide">
    {#if slide.image}
        <div class="image-container">
            <img
                src="/src/assets/school-bg.gif"
                alt="Congrats Scene"
                class="block mx-auto rounded-[2vw] shadow-lg"
            />
        </div>
    {/if}
    <p class="text-[4vw] md:text-2xl text-gray-800 font-semibold text-fade">
        {slide.text}
    </p>

    <button
        class="mt-[2vh] bg-teal-300 text-gray-900 px-[6vw] py-[2vh] rounded-[3vw] text-[5vw] md:text-2xl font-bold shadow-md hover:bg-teal-400 hover:scale-105 transition-all duration-300 flex items-center justify-center"
        on:click={() => showConfirm = true}
    >
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
                        {#if attemptsRemaining > 0}
                            <button class="confirm-btn retake" on:click={retakeLevel}>Try Again ({attemptsRemaining} left) ⟲</button>
                            <button class="confirm-btn proceed" on:click={continueToQuiz}>Go Next ➡️</button>
                            <button class="confirm-btn cancel" on:click={() => showConfirm = false}>Stay Here ✕</button>
                        {:else}
                            <button class="confirm-btn proceed" on:click={continueToQuiz}>Go Next ➡️</button>
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

                <header class="summary-header compact">
                    <div class="trophy">🏆</div>
                    <div class="header-text">🏆Great Job! 🎉</div>
                    {#if isLevelCompleted}
                        <div class="summary-note compact-note">Note: You've already completed Level 1 — this attempt will not be recorded.</div>
                    {/if}
                </header>

                <section class="summary-body">
                    {#if lastAttempt}
                        <div class="score-row">
                            <div class="score-bubble">{lastAttempt.score}</div>
                            <div class="score-meta">
                                <div class="score-text">Points</div>
                                <div class="score-sub">{lastAttempt.score} / {Object.keys(correctAnswers[storyKey] || {}).length}</div>
                            </div>
                            <div class="ribbons-preview">
                                    {#each Array(lastAttempt.score) as _}
                                        <span class="ribbon">🎀</span>
                                    {/each}
                                </div>
                        </div>

                        <p class="attempts-text">Attempts used: <strong>{1 + lastAttempt.retakeCount}</strong></p>

                        <div class="questions-list">
                            {#each Object.keys(correctAnswers[storyKey] || {}) as qid}
                                <article class="q-card">
                                    <div class="q-top">
                                        <div class="q-number">Q</div>
                                        <div class="q-text">{(questionsByStory as any)?.[storyKey]?.[qid]?.text || qid}</div>
                                    </div>
                                    <ul class="choices">
                                        {#each Object.entries((questionsByStory as any)?.[storyKey]?.[qid]?.choices || {}) as [key, text]}
                                            {#if lastAttempt.answers?.[qid] === key}
                                                <li class="choice selected" class:correct={correctAnswers[storyKey]?.[qid] === key} class:wrong={correctAnswers[storyKey]?.[qid] !== key}>
                                                    <span class="choice-key">{key.toUpperCase()}</span>
                                                    <span class="choice-text">{text}</span>
                                                    <span class="badge" aria-label={correctAnswers[storyKey]?.[qid] === key ? 'Correct' : 'Incorrect'}>
                                                        {correctAnswers[storyKey]?.[qid] === key ? 'Correct' : 'Incorrect'}
                                                    </span>
                                                </li>
                                            {:else}
                                                <li class="choice">
                                                    <span class="choice-key">{key.toUpperCase()}</span>
                                                    <span class="choice-text">{text}</span>
                                                </li>
                                            {/if}
                                        {/each}
                                    </ul>
                                </article>
                            {/each}
                        </div>
                    {:else}
                        <p>No attempt data available.</p>
                    {/if}
                </section>

                <footer class="summary-footer">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:0.4rem;">
                        {#if isLevelCompleted}
                            <button class="btn-claim" on:click={continueWithoutSaving}>
                                Continue to Game ➡️
                            </button>
                        {:else}
                            <button class="btn-claim" on:click={claimRibbonsAndContinue} disabled={savingRibbons} aria-busy={savingRibbons}>
                                {#if savingRibbons}
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
                    <button class="btn-close" on:click={() => { showSummary = false; showConfirm = false; }}>Close</button>
                </footer>
            </div>
        </div>

        
    {/if}
</div>

<style>
    .image-container { width: 65%; height: auto; max-width: 800px; max-height: 400px; margin-bottom: 2vh; display: flex; justify-content: center; align-items: center; }
    .image-container img { width: 100%; height: 100%; object-fit: contain; }

    /* Confirmation modal styles */
    .confirm-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .confirm-modal { background: #fff; border-radius: 1rem; padding: 1.5rem; width: min(90vw, 480px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); text-align: center; }
    .confirm-title { font-size: clamp(1.5rem, 4vw, 1.75rem); font-weight: 800; color: #111827; margin-bottom: 0.75rem; }
    .confirm-text { font-size: clamp(1.1rem, 3vw, 1.25rem); color: #374151; margin-bottom: 1rem; font-weight: 600; }
    .confirm-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    .confirm-btn { padding: 0.75rem 1rem; border-radius: 0.75rem; border: none; font-weight: 700; font-size: clamp(1rem, 2.5vw, 1.125rem); cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.2s ease; }
    .confirm-btn:hover { transform: scale(1.03); }
    .confirm-btn:active { transform: scale(0.98); }
    .confirm-btn.retake { background: #fef3c7; color: #92400e; box-shadow: 0 2px 6px rgba(146,64,14,0.2); }
    .confirm-btn.proceed { background: #d1fae5; color: #065f46; box-shadow: 0 2px 6px rgba(6,95,70,0.2); }
    .confirm-btn.cancel { grid-column: span 2; background: #e5e7eb; color: #111827; }

    /* Kid-friendly summary modal styles */
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

    /* compact header to reduce vertical space */
    .summary-header { display:flex; gap:0.6rem; align-items:center; padding:0.25rem 0; }
    .summary-header.compact .trophy { font-size:2rem; animation: pop 500ms ease; }
    .summary-header .header-text { font-weight:800; color:#0f172a; font-size:1.1rem; }
    /* summary-sub was removed — kept intentionally compact */
    .compact-note { font-size:0.78rem; color:#9ca3af; margin-left:0.5rem; font-weight:700; }

    /* Make the entire modal scroll instead of the inner body so header/footer scroll with content */
    .summary-body { padding:0.35rem 0.5rem 0.5rem; }
    .score-row { display:flex; align-items:center; gap:0.6rem; background:linear-gradient(90deg,#fff0de,#fff9f1); padding:0.45rem; border-radius:0.65rem; margin-bottom:0.4rem; }
    .score-bubble { background:#ffefc7; border-radius:999px; width:52px; height:52px; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:1.2rem; box-shadow:0 6px 14px rgba(0,0,0,0.12); }
    .score-meta { display:flex; flex-direction:column; }
    .score-text { color:#374151; font-weight:700; font-size:0.95rem; }
    .score-sub { color:#6b7280; font-size:0.9rem; }
    .ribbons-preview { margin-left:auto; display:flex; gap:0.4rem; }
    .ribbon { font-size:2rem; transform-origin:center; animation: ribbonPop 900ms ease-out both; }

    .attempts-text { font-weight:700; color:#374151; margin-bottom:0.5rem; }

    .questions-list { display:grid; gap:0.45rem; }
    .q-card { background: linear-gradient(180deg,#ffffff,#fffaf6); border-radius:0.6rem; padding:0.45rem; box-shadow:0 6px 12px rgba(15,23,42,0.05); }
    .q-top { display:flex; gap:0.45rem; align-items:flex-start; }
    .q-number { background:#fef3c7; border-radius:6px; padding:0.2rem 0.45rem; font-weight:800; font-size:0.85rem; }
    .q-text { font-weight:800; color:#0f172a; font-size:0.95rem; }
    .choices { margin-top:0.6rem; display:flex; flex-direction:column; gap:0.4rem; }
    .choice { display:flex; align-items:center; gap:0.5rem; padding:0.35rem; border-radius:0.5rem; background:linear-gradient(180deg,#ffffff,#fff7ed); }
    .choice .choice-key { width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center; border-radius:6px; background:#fde68a; font-weight:800; font-size:0.85rem; }
    .choice .choice-text { flex:1; }
    .choice .badge { background:#10b981; color:white; padding:0.12rem 0.36rem; border-radius:999px; font-size:0.72rem; }
    .choice.selected { border:1.6px dashed #ffd28a; background:linear-gradient(90deg,#fff7ed,#fffaf0); }
    /* removed unused .ok/.no selectors (replaced by .choice.selected.correct/.wrong) */

    /* Badge colors for correct/incorrect selected answers */
    .choice.selected.correct .badge { background: #10b981; }
    .choice.selected.wrong .badge { background: #ef4444; }

    .summary-footer { display:flex; gap:0.5rem; justify-content:center; padding-top:0.45rem; }
    .btn-claim { background:#06b6d4; color:white; padding:0.6rem 0.9rem; border-radius:0.55rem; font-weight:800; box-shadow:0 6px 14px rgba(6,182,212,0.16); font-size:0.95rem; }
    .btn-close { background:#e5e7eb; color:#111827; padding:0.6rem 0.9rem; border-radius:0.55rem; font-weight:700; font-size:0.95rem; }

    @keyframes pop { 0%{ transform:scale(0.2); opacity:0; } 70%{ transform:scale(1.08); } 100%{ transform:scale(1); } }
    @keyframes ribbonPop { 0%{ transform:scale(0); opacity:0; } 60%{ transform:scale(1.15); } 100%{ transform:scale(1); opacity:1; } }

    /* small responsive tweaks */
    @media (max-width:640px) { .summary-modal { padding:0.6rem; } .score-bubble { width:52px; height:52px; } .ribbon { font-size:1.6rem; } }

    /* Disabled claim button and ribbon message */
    .btn-claim[disabled], .btn-claim[aria-busy='true'] { opacity: 0.6; transform: none; cursor: default; }
    .ribbon-message { color: #065f46; font-weight: 700; font-size: 0.95rem; }
</style>