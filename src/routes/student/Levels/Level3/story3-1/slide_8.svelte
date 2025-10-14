<script lang="ts">
    import { studentData } from '$lib/store/student_data';

    // Unique IDs for this story's open-ended questions
    const q1 = 'story3-1_q1';
    const q2 = 'story3-1_q2';
    const q3 = 'story3-1_q3';

    // Check if Level 3 is already completed (student is at level 3 or higher)
    $: isLevelCompleted = ($studentData?.studentLevel || 0) >= 3;

    // Local bindings for inputs; pre-fill from store if available
    let a1: string = $studentData?.answeredQuestions?.[q1] ?? '';
    let a2: string = $studentData?.answeredQuestions?.[q2] ?? '';
    let a3: string = $studentData?.answeredQuestions?.[q3] ?? '';

    function saveAnswer(key: string, value: string) {
        // Don't save answers if level is already completed
        if (isLevelCompleted) return;
        
        studentData.update((data: any) => {
            if (!data) return data;
            const answered = { ...(data.answeredQuestions || {}), [key]: value };
            return { ...data, answeredQuestions: answered };
        });
    }

    function onInput1(e: Event) { a1 = (e.target as HTMLTextAreaElement).value; saveAnswer(q1, a1); }
    function onInput2(e: Event) { a2 = (e.target as HTMLTextAreaElement).value; saveAnswer(q2, a2); }
    function onInput3(e: Event) { a3 = (e.target as HTMLTextAreaElement).value; saveAnswer(q3, a3); }
</script>

<div class="slide-root">
  <div class="quiz-card">
    <div class="quiz-header">
      <span class="badge">🌟</span>
      <h2 class="title">Quiz Time</h2>
      <span class="badge">📝</span>
    </div>

    <p class="subtitle">Tell us what you think! Write your answers below.</p>

    <div class="qa">
      <label class="question" for="q1">
        1. Based on Tonya’s actions, how does she feel about her wiggly tooth?
      </label>
      <textarea
        id="q1"
        class="answer"
        bind:value={a1}
        on:input={onInput1}
        placeholder="Type your answer here..."
      ></textarea>
    </div>

    <div class="qa">
      <label class="question" for="q2">
        2. Why do you think Tonya’s tooth tilted and wiggled when she touched it?
      </label>
      <textarea
        id="q2"
        class="answer"
        bind:value={a2}
        on:input={onInput2}
        placeholder="Type your answer here..."
      ></textarea>
    </div>

    <div class="qa">
      <label class="question" for="q3">
        3. What do you think will happen to Tonya’s tooth after she bites the guava?
      </label>
      <textarea
        id="q3"
        class="answer"
        bind:value={a3}
        on:input={onInput3}
        placeholder="Type your answer here..."
      ></textarea>
    </div>

    <div class="footer">
      <p class="hint">💾 Your answers save automatically.</p>
    </div>
  </div>
</div>

<style>
  .slide-root {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(12px, 3vw, 24px);
    box-sizing: border-box;
    background: linear-gradient(180deg, #F0F9FF 0%, #FFFFFF 100%);
  }

  .quiz-card {
    width: min(900px, 100%);
    max-height: 100%;
    overflow: auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    padding: clamp(12px, 3vw, 24px);
    border: 3px solid #FDE68A;
  }

  .quiz-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 8px;
  }
  .badge { font-size: clamp(1.25rem, 3vw, 2rem); }
  .title {
    margin: 0;
    text-align: center;
    font-size: clamp(1.5rem, 4vw, 2rem);
    font-weight: 800;
    color: #0f172a;
    letter-spacing: 0.5px;
  }
  .subtitle {
    text-align: center;
    color: #475569;
    margin: 0 0 12px 0;
    font-size: clamp(0.95rem, 2.2vw, 1.05rem);
  }

  .qa { display: flex; flex-direction: column; gap: 8px; margin: 12px 0; }
  .question {
    text-align: left;
    font-size: clamp(1rem, 2.4vw, 1.15rem);
    color: #1f2937;
    font-weight: 700;
  }
  .answer {
    min-height: 110px;
    resize: vertical;
    padding: 12px 14px;
    border: 2px solid #E5E7EB;
    border-radius: 12px;
    font-size: clamp(1rem, 2.4vw, 1.1rem);
    line-height: 1.5;
    outline: none;
    background: #F8FAFC;
    transition: box-shadow 0.15s ease, border-color 0.15s ease, background 0.15s ease;
  }
  .answer::placeholder { color: #94a3b8; }
  .answer:focus { border-color: #60a5fa; box-shadow: 0 0 0 3px rgba(96,165,250,0.25); background: #fff; }

  .footer { display: flex; justify-content: center; margin-top: 8px; }
  .hint { text-align: center; font-size: 0.9rem; color: #64748b; margin: 0; }
</style>
