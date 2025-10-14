<script>
    import { studentData } from '$lib/store/student_data';

    // fitText action
    function fitText(node, { min = 6, step = 1 } = {}) {
        const style = window.getComputedStyle(node);
        let max = parseFloat(style.fontSize) || 16;
        if (!max || isNaN(max)) max = 16;

        function resize() {
            let fs = max;
            node.style.fontSize = fs + 'px';
            while ((node.scrollHeight > node.clientHeight || node.scrollWidth > node.clientWidth) && fs > min) {
                fs -= step;
                node.style.fontSize = fs + 'px';
            }
        }

        resize();
        const ro = new ResizeObserver(resize);
        ro.observe(node);
        const mo = new MutationObserver(resize);
        mo.observe(node, { childList: true, subtree: true, characterData: true });
        window.addEventListener('resize', resize);
        return { update(newOpts) { if (newOpts && newOpts.min) min = newOpts.min; if (newOpts && newOpts.step) step = newOpts.step; resize(); }, destroy() { ro.disconnect(); mo.disconnect(); window.removeEventListener('resize', resize); } }
    }

    const questions = [
        { id: 1, text: "What do you think Royce will do next after he finds out the situation of his brother?" },
        { id: 2, text: "How will Royce feel if he buys the medicine?" },
        { id: 3, text: "What do you think his mother will feel after?" },
        { id: 4, text: "After what happened, what do you think Royce will do?" }
    ];

    let answers = [
        { id: 'a1', text: "Royce will buy the medicine because his brother needs it.", assignedTo: null },
        { id: 'a2', text: "Royce will feel happy to help, even if he’s a bit sad about the toy.", assignedTo: null },
        { id: 'a3', text: "Royce’s mother will feel proud and thankful to Royce.", assignedTo: null },
        { id: 'a4', text: "Royce will start saving again for his dream remote car.", assignedTo: null }
    ];

    answers = shuffleArray(answers);

    let dragged = null;
    function handleDragStart(event, ans) { dragged = ans; if (event.dataTransfer) event.dataTransfer.setData('text/plain', ans.id); }
    function makeStartDrag(ans) { return function (ev) { handleDragStart(ev, ans); }; }
    function makeStartDragAssigned(ans) { return function (ev) { handleDragStart(ev, ans); }; }
    function handleDragOver(event) { event.preventDefault(); }

    function handleDropOnQuestion(event, q) {
        event.preventDefault();
        if (!dragged) return;
        const draggedId = dragged.id;
        const prevAssigned = dragged.assignedTo;
        const existing = answers.find(a => a.assignedTo === q.id) || null;
        answers = answers.map(a => {
            if (a.id === draggedId) return { ...a, assignedTo: q.id };
            if (existing && a.id === existing.id) return { ...a, assignedTo: prevAssigned };
            return a;
        });
        dragged = null;
        persistAnswers();
    }

    function handleDropToBox(event) { event.preventDefault(); if (!dragged) return; const draggedId = dragged.id; answers = answers.map(a => a.id === draggedId ? { ...a, assignedTo: null } : a); dragged = null; persistAnswers(); }

    function persistAnswers() {
        const mapped = {};
        answers.forEach(a => { if (a.assignedTo !== null) mapped[a.assignedTo] = a.text; });
        try {
            const updater = function (d) {
                const data = d || {};
                const answered = data.answeredQuestions || {};
                answered['story2-3_slide7'] = JSON.stringify(mapped);
                return { ...data, answeredQuestions: answered };
            };
            studentData.update(updater);
        } catch (e) { console.error('Failed to persist quiz answers', e); }
    }

    $: allAnswered = questions.every(q => answers.some(a => a.assignedTo === q.id));
    $: if (typeof window !== 'undefined') { window.dispatchEvent(new CustomEvent('slideAnswered', { detail: { allAnswered } })); }

    (function restore() {
        const subscriber = function (data) {
            try {
                const answered = (data && data.answeredQuestions) || {};
                const saved = answered['story2-3_slide7'];
                if (saved) {
                    const mapped = JSON.parse(saved);
                    const hasSavedKeys = mapped && Object.keys(mapped).length > 0;
                    if (hasSavedKeys) {
                        answers = answers.map(a => {
                            const found = Object.entries(mapped).find(([qid, txt]) => txt === a.text);
                            return found ? { ...a, assignedTo: Number(found[0]) } : a;
                        });
                    } else { answers = shuffleArray(answers); }
                } else { answers = shuffleArray(answers); }
            } catch (e) { console.error(e); }
        };
        const unsub = studentData.subscribe(subscriber);
        unsub();
    })();

    function shuffleArray(arr) { const copy = arr.slice(); for (let i = copy.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); const tmp = copy[i]; copy[i] = copy[j]; copy[j] = tmp; } return copy; }
</script>

<div class="quiz-slide">
    <h2 class="quiz-title">Quiz Time!</h2>
    <div class="quiz-grid">
        <section class="answers-column" aria-label="Answers">
            <div class="answers-header">Drag answers into the questions</div>
            <div class="answer-box" role="list" on:dragover={handleDragOver} on:drop={handleDropToBox} aria-label="Answer box">
                {#each answers.filter(a => !a.assignedTo) as a, i}
                    <div role="button" tabindex="0" use:fitText={{min:10,step:1}} draggable="true" on:dragstart={makeStartDrag(a)} class="answer-pill pill-{i}">{a.text}</div>
                {/each}
            </div>
        </section>
        <section class="questions-column" aria-label="Questions">
            {#each questions as q, idx}
                <div class="question-card" role="region" on:dragover={handleDragOver} on:drop={e => handleDropOnQuestion(e, q)} aria-label={`Question ${q.id}`}>
                    <div class="question-left">
                        <div class="q-badge">{idx + 1}</div>
                        <div class="question-text">{q.text}</div>
                    </div>
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
            <div class="bottom-spacer" aria-hidden="true"></div>
        </section>
    </div>
</div>

<style>
    :global(.quiz-slide) { padding:1rem; box-sizing:border-box; height:100%; display:flex; flex-direction:column; font-family: 'Century Gothic', 'CenturyGothic', 'AppleGothic', Arial, sans-serif; }
    .quiz-title { font-size:clamp(1.25rem, 3vw, 1.75rem); font-weight:800; color:#075985; margin-bottom:0.5rem; text-align:center; }
    .quiz-grid { display:grid; grid-template-columns: 420px 1fr; gap:1rem; align-items:start; height:100%; padding-bottom:6.5rem; }
    @media (max-width:700px) { .quiz-grid { grid-template-columns: 1fr; padding-bottom:7.5rem; } }
    .answers-column { display:flex; flex-direction:column; gap:0.5rem; padding-left:3.5rem; }
    @media (max-width:700px) { .answers-column { padding-left:1rem; } }
    .answers-header { font-weight:700; color:#0f172a; font-size:clamp(0.9rem,2.2vw,1.05rem); }
    .answer-box { display:flex; flex-direction:column; gap:0.5rem; padding:0.75rem; border-radius:0.75rem; background:linear-gradient(180deg,#fff7ed,#fffbeb); max-height:48vh; overflow:auto; }
    .answer-pill { padding:0.35rem 0.5rem; border-radius:0.5rem; font-weight:700; cursor:grab; color:white; display:block; word-break:break-word; box-shadow:0 4px 8px rgba(0,0,0,0.08); min-height:2.4rem; display:flex; align-items:center; font-size:clamp(0.7rem,1.6vw,0.95rem); }
    .pill-0 { background:#ef476f; }
    .pill-1 { background:#f266ff; color:#fff; }
    .pill-2 { background:#06d6a0; }
    .pill-3 { background:#118ab2; }
    .questions-column { display:flex; flex-direction:column; gap:0.75rem; padding-bottom:0.5rem; }
    .question-card { background:linear-gradient(180deg,#ffffff,#f1f5f9); padding:0.6rem; border-radius:0.75rem; box-shadow:0 6px 16px rgba(2,6,23,0.06); min-height:4.4rem; display:flex; flex-direction:column; gap:0.35rem; }
    .question-left { display:flex; align-items:flex-start; gap:0.6rem; }
    .q-badge { background:#fde047; color:#1f2937; font-weight:900; width:1.9rem; height:1.9rem; display:flex; align-items:center; justify-content:center; border-radius:0.45rem; font-size:clamp(0.78rem,1.6vw,0.95rem); box-shadow:0 4px 8px rgba(0,0,0,0.06); flex-shrink:0; }
    .question-text { flex:1; font-size:clamp(0.88rem,1.2vw,1.05rem); color:#0b1220; text-align:left; min-height:2.0rem; line-height:1.2; overflow:hidden; }
    .assigned-below { display:flex; gap:0.4rem; flex-wrap:nowrap; align-items:center; justify-content:flex-start; overflow:visible; }
    .assigned-pill { padding:0.12rem 0.3rem; border-radius:0.45rem; background:#bde0fe; color:#001219; font-weight:800; min-height:1.6rem; display:inline-flex; align-items:center; box-shadow:0 4px 8px rgba(0,0,0,0.06); max-width:100%; flex:0 1 auto; min-width:1.6rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:clamp(0.55rem,1.0vw,0.9rem); justify-content:flex-start; text-align:left; }
    .status { margin-top:0.75rem; }
    .bottom-spacer { height:8rem; width:100%; flex-shrink:0; }
    .status-ok { color:#166534; font-weight:800; }
    .status-wait { color:#9a3412; font-weight:700; }
    .answer-pill { max-width:100%; white-space:normal; }
    .answer-box::-webkit-scrollbar { height:8px; }
    .answer-box::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:99px; }
</style>