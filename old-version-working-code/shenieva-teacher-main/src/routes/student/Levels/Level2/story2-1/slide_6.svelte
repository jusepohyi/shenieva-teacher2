<script>
        import { studentData } from '$lib/store/student_data';
        
    // Svelte action: auto-fit text to avoid overflow inside a pill
    /**
     * @param {HTMLElement} node
     * @param {{min?:number,step?:number}} [opts]
     */
    function fitText(node, { min = 6, step = 1 } = {}) {
        const style = window.getComputedStyle(node);
        let max = parseFloat(style.fontSize) || 16;
        if (!max || isNaN(max)) max = 16;

        function resize() {
            // reset to max, then shrink until content fits
            let fs = max;
            node.style.fontSize = fs + 'px';
            // allow wrapping; ensure we compare scroll vs client
            while ((node.scrollHeight > node.clientHeight || node.scrollWidth > node.clientWidth) && fs > min) {
                fs -= step;
                node.style.fontSize = fs + 'px';
            }
        }

        // run initially
        resize();

        const ro = new ResizeObserver(resize);
        ro.observe(node);

        const mo = new MutationObserver(resize);
        mo.observe(node, { childList: true, subtree: true, characterData: true });

        window.addEventListener('resize', resize);

        return {
            /** @param {{min?:number,step?:number}} newOpts */
            update(newOpts) {
                if (newOpts && newOpts.min) min = newOpts.min;
                if (newOpts && newOpts.step) step = newOpts.step;
                resize();
            },
            destroy() {
                ro.disconnect();
                mo.disconnect();
                window.removeEventListener('resize', resize);
            }
        };
    }

    // Questions and answer choices
    const questions = [
        { id: 1, text: "Based on the scenario, what will be the response of Hector’s mother?" },
        { id: 2, text: "What will the doctor say about Hector’s health?" },
        { id: 3, text: "What do you think will be the doctor's advice to Hector?" },
        { id: 4, text: "What do you think is the result once Hector follows the doctor's advice?" }
    ];

        /** @type {{id:string,text:string,assignedTo:number|null}[]} */
        let answers = [
        { id: 'a1', text: "His mother will bring him to the doctor.", assignedTo: null },
        { id: 'a2', text: "The doctor will say that Hector's weight is above average for his age right now.", assignedTo: null },
        { id: 'a3', text: "He will tell Hector that he needs to improve his eating habits and start exercising. Avoid too many sweets like chocolates and he should start eating more vegetables and fruits.", assignedTo: null },
        { id: 'a4', text: "He will have a healthy body.", assignedTo: null }
    ];

    // Shuffle answers at startup so they don't match question sequence by default
    answers = shuffleArray(answers);

        /** @type {{id:string,text:string,assignedTo:number|null}|null} */
        let dragged = null;

        /** @param {DragEvent} event @param {{id:string,text:string,assignedTo:number|null}} ans */
        function handleDragStart(event, ans) {
            dragged = ans;
            if (event.dataTransfer) event.dataTransfer.setData('text/plain', ans.id);
        }

        /** @param {DragEvent} ev @param {{id:string,text:string,assignedTo:number|null}} ans */
        function startDrag(ev, ans) { handleDragStart(ev, ans); }

        /** @param {DragEvent} ev @param {{id:string,text:string,assignedTo:number|null}} ans */
        function startDragAssigned(ev, ans) { handleDragStart(ev, ans); }

        /**
         * Return a DragEvent handler bound to an answer
         * @param {{id:string,text:string,assignedTo:number|null}} ans
         * @returns {(ev:DragEvent)=>void}
         */
        function makeStartDrag(ans) {
            /** @param {DragEvent} ev */
            return function (ev) { handleDragStart(ev, ans); };
        }

        /**
         * Return a DragEvent handler bound to an assigned answer
         * @param {{id:string,text:string,assignedTo:number|null}} ans
         * @returns {(ev:DragEvent)=>void}
         */
        function makeStartDragAssigned(ans) {
            /** @param {DragEvent} ev */
            return function (ev) { handleDragStart(ev, ans); };
        }

        /** @param {DragEvent} event */
        function handleDragOver(event) { event.preventDefault(); }

        /** @param {DragEvent} event @param {{id:number}} q */
        function handleDropOnQuestion(event, q) {
            event.preventDefault();
            if (!dragged) return;
                // allow swapping: if a question already has an answer, swap assignments
                const draggedId = dragged.id;
                const prevAssigned = dragged.assignedTo; // number|null
                const existing = answers.find(a => a.assignedTo === q.id) || null;
                answers = answers.map(a => {
                    if (a.id === draggedId) return Object.assign(Object.assign({}, a), { assignedTo: q.id });
                    if (existing && a.id === existing.id) return Object.assign(Object.assign({}, a), { assignedTo: prevAssigned });
                    return a;
                });
            dragged = null;
            persistAnswers();
        }

        /** @param {DragEvent} event */
        function handleDropToBox(event) {
            event.preventDefault();
            if (!dragged) return;
            // unassign
                const draggedId = dragged.id;
                answers = answers.map(a => a.id === draggedId ? { ...a, assignedTo: null } : a);
            dragged = null;
            persistAnswers();
        }

        function persistAnswers() {
            /** @type {Record<number,string>} */
            const mapped = {};
            answers.forEach(a => { if (a.assignedTo !== null) mapped[a.assignedTo] = a.text; });
                    try {
                        /** @param {any} d */
                        const updater = /** @type {(d:any)=>any} */ (function (d) {
                            const data = d || {};
                            const answered = data.answeredQuestions || {};
                            answered['story2-1_slide6'] = JSON.stringify(mapped);
                            // Also persist question metadata so final submit can include human-friendly question text
                            const questionsByStory = data.questionsByStory || {};
                            const storyMap = questionsByStory['story2-1'] || {};
                            // Build per-qid metadata: subKey -> { text }
                            /** @type {Record<string, any>} */
                            const qMeta = {};
                            questions.forEach(q => { qMeta[String(q.id)] = { text: q.text }; });
                            storyMap['story2-1_slide6'] = qMeta;
                            questionsByStory['story2-1'] = storyMap;
                            return Object.assign({}, data, { answeredQuestions: answered, questionsByStory });
                        });
                        studentData.update(updater);
                    } catch (e) { console.error('Failed to persist quiz answers', e); }
        }

    // allAnswered: every question has an assigned answer
    $: allAnswered = questions.every(q => answers.some(a => a.assignedTo === q.id));

    // Notify parent modal about the answered state so it can enable/disable Next
    // Use a window CustomEvent so the TypeScript config for svelte doesn't require
    // createEventDispatcher types. Guard for SSR where `window` may be undefined.
    $: if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('slideAnswered', { detail: { allAnswered } }));
    }

        // Restore previous answers via a one-time subscription
        (function restore() {
                    /** @param {any} data */
                                const subscriber = function (data) {
                                    try {
                                        const answered = (data && data.answeredQuestions) || {};
                                        const saved = answered['story2-1_slide6'];
                                        if (saved) {
                                            const mapped = JSON.parse(saved);
                                            const hasSavedKeys = mapped && Object.keys(mapped).length > 0;
                                            if (hasSavedKeys) {
                                                answers = answers.map(a => {
                                                    const found = Object.entries(mapped).find(function ([qid, txt]) { return txt === a.text; });
                                                    return found ? Object.assign(Object.assign({}, a), { assignedTo: Number(found[0]) }) : a;
                                                });
                                            } else {
                                                // Saved key exists but empty mapping — treat as no saved answers and shuffle
                                                answers = shuffleArray(answers);
                                            }
                                        } else {
                                            // No saved answers — shuffle the answers so they don't match the question order
                                            answers = shuffleArray(answers);
                                        }
                                    } catch (e) { console.error(e); }
                                };
                                const unsub = studentData.subscribe(subscriber);
                        unsub();
        })();
    // Simple Fisher-Yates shuffle for an array of answer objects (non-destructive)
    /** @param {{id:string,text:string,assignedTo:number|null}[]} arr */
    function shuffleArray(arr) {
        const copy = arr.slice();
        for (let i = copy.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            const tmp = copy[i];
            copy[i] = copy[j];
            copy[j] = tmp;
        }
        return copy;
    }

</script>

<div class="quiz-slide">
    <h2 class="quiz-title">Quiz Time!</h2>

    <div class="quiz-grid">
        <!-- Answers column (top on mobile) -->
        <section class="answers-column" aria-label="Answers">
            <div class="answers-header">Drag answers into the questions</div>
                    <div class="answer-box" role="list" on:dragover={handleDragOver} on:drop={handleDropToBox} aria-label="Answer box">
                {#each answers.filter(a => !a.assignedTo) as a, i}
                    <div role="button" tabindex="0" use:fitText={{min:10,step:1}} draggable="true" on:dragstart={makeStartDrag(a)} class="answer-pill pill-{i}">{a.text}</div>
                {/each}
            </div>
        </section>

        <!-- Questions column -->
        <section class="questions-column" aria-label="Questions">
            {#each questions as q, idx}
                <div class="question-card" role="region" on:dragover={handleDragOver} on:drop={e => handleDropOnQuestion(e, q)} aria-label={`Question ${q.id}`}>
                    <div class="question-left">
                        <div class="q-badge">{idx + 1}</div>
                        <div class="question-text">{q.text}</div>
                    </div>

                    <!-- Assigned answers shown under the question text -->
                    <div class="assigned-below" aria-live="polite">
                        {#each answers.filter(a => a.assignedTo === q.id) as assigned}
                            <div role="button" tabindex="0" use:fitText={{min:8,step:1}} draggable="true" on:dragstart={makeStartDragAssigned(assigned)} class="answer-pill assigned-pill">{assigned.text}</div>
                        {/each}
                    </div>
                </div>
            {/each}

            <div class="status">
                {#if allAnswered}
                    <div class="status-ok">All questions answered — you may proceed.</div>
                {:else}
                    <div class="status-wait">Please answer all questions to proceed.</div>
                {/if}
            </div>

            <!-- spacer to keep floating nav buttons from overlapping content -->
            <div class="bottom-spacer" aria-hidden="true"></div>
        </section>
    </div>
</div>

<style>
    :global(.quiz-slide) { padding:1rem; box-sizing:border-box; height:100%; display:flex; flex-direction:column; font-family: 'Century Gothic', 'CenturyGothic', 'AppleGothic', Arial, sans-serif; }
    .quiz-title { font-size:clamp(1.25rem, 3vw, 1.75rem); font-weight:800; color:#075985; margin-bottom:0.5rem; text-align:center; }

    /* two-column responsive layout */
    /* leave extra bottom padding to avoid overlay from modal nav buttons */
    /* increase left column so questions column becomes narrower */
    .quiz-grid { display:grid; grid-template-columns: 420px 1fr; gap:1rem; align-items:start; height:100%; padding-bottom:6.5rem; }
    @media (max-width:700px) { .quiz-grid { grid-template-columns: 1fr; padding-bottom:7.5rem; } }

    .answers-column { display:flex; flex-direction:column; gap:0.5rem; padding-left:3.5rem; }
    @media (max-width:700px) { .answers-column { padding-left:1rem; } }
    .answers-header { font-weight:700; color:#0f172a; font-size:clamp(0.9rem,2.2vw,1.05rem); }

        .answer-box { display:flex; flex-direction:column; gap:0.5rem; padding:0.75rem; border-radius:0.75rem; background:linear-gradient(180deg,#fff7ed,#fffbeb); max-height:48vh; overflow:auto; }

        /* kid-friendly colored pills (distinct per answer index) - make them card-like */
    .answer-pill { padding:0.35rem 0.5rem; border-radius:0.5rem; font-weight:700; cursor:grab; color:white; display:block; word-break:break-word; box-shadow:0 4px 8px rgba(0,0,0,0.08); min-height:2.4rem; display:flex; align-items:center; font-size:clamp(0.7rem,1.6vw,0.95rem); }
        .pill-0 { background:#ef476f; }
    .pill-1 { background:#f266ff; color:#fff; }
        .pill-2 { background:#06d6a0; }
        .pill-3 { background:#118ab2; }

            .questions-column { display:flex; flex-direction:column; gap:0.75rem; padding-bottom:0.5rem; }
            /* make the question card a fixed-height container so assigned answers don't resize it */
            .question-card { background:linear-gradient(180deg,#ffffff,#f1f5f9); padding:0.6rem; border-radius:0.75rem; box-shadow:0 6px 16px rgba(2,6,23,0.06); min-height:4.4rem; display:flex; flex-direction:column; gap:0.35rem; }
            .question-left { display:flex; align-items:flex-start; gap:0.6rem; }
            .q-badge { background:#fde047; color:#1f2937; font-weight:900; width:1.9rem; height:1.9rem; display:flex; align-items:center; justify-content:center; border-radius:0.45rem; font-size:clamp(0.78rem,1.6vw,0.95rem); box-shadow:0 4px 8px rgba(0,0,0,0.06); flex-shrink:0; }
            .question-text { flex:1; font-size:clamp(0.88rem,1.2vw,1.05rem); color:#0b1220; text-align:left; min-height:2.0rem; line-height:1.2; overflow:hidden; }

    /* assigned area sits inside the fixed card and will scroll if overflowing */
    /* single-line assigned area: pills will shrink to fit instead of wrapping or causing scroll */
    .assigned-below { display:flex; gap:0.4rem; flex-wrap:nowrap; align-items:center; justify-content:flex-start; overflow:visible; }
    .assigned-pill { padding:0.12rem 0.3rem; border-radius:0.45rem; background:#bde0fe; color:#001219; font-weight:800; min-height:1.6rem; display:inline-flex; align-items:center; box-shadow:0 4px 8px rgba(0,0,0,0.06); max-width:100%; flex:0 1 auto; min-width:1.6rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:clamp(0.55rem,1.0vw,0.9rem); justify-content:flex-start; text-align:left; }

    /* ensure assigned pills shrink and wrap rather than expand the question card */

        .status { margin-top:0.75rem; }

    /* add an explicit bottom spacer so floating nav doesn't cover content on small screens */
    .bottom-spacer { height:8rem; width:100%; flex-shrink:0; }
    .status-ok { color:#166534; font-weight:800; }
    .status-wait { color:#9a3412; font-weight:700; }

    /* ensure long text wraps inside answer-box pills without overflowing; assigned pills stay single-line and ellipsize */
        .answer-pill { max-width:100%; white-space:normal; }

    /* make answer-box scroll nice */
    .answer-box::-webkit-scrollbar { height:8px; }
    .answer-box::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:99px; }
</style>
