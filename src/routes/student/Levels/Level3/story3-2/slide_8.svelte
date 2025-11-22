<script lang="ts">
    import { studentData } from '$lib/store/student_data';

    // Unique IDs for this story's open-ended questions
    const q1 = 'story3-2_q1';
    const q2 = 'story3-2_q2';
    const q3 = 'story3-2_q3';

    // Check if Level 3 is already completed (student is at level 3 or higher)
    $: isLevelCompleted = ($studentData?.studentLevel || 0) >= 3;

    // Local bindings for inputs; pre-fill from store if available
    let a1: string = $studentData?.answeredQuestions?.[q1] ?? '';
    let a2: string = $studentData?.answeredQuestions?.[q2] ?? '';
    let a3: string = $studentData?.answeredQuestions?.[q3] ?? '';

    // Track which fields have errors
    let errors = { q1: false, q2: false, q3: false };
    let isFinalized = false;

    function handleInput() {
        console.log('story3-2 handleInput called:', { a1, a2, a3, isLevelCompleted });
        // Clear finalized state when user edits
        isFinalized = false;
        // Clear all errors
        errors = { q1: false, q2: false, q3: false };
        
        if (!isLevelCompleted) {
            studentData.update((data: any) => {
                if (!data) return data;
                const updated = {
                    ...data,
                    answeredQuestions: {
                        ...(data.answeredQuestions || {}),
                        [q1]: a1,
                        [q2]: a2,
                        [q3]: a3,
                        ['story3-2_finalized']: false
                    }
                };
                console.log('story3-2 updating store with:', updated.answeredQuestions);
                return updated;
            });
        }
    }

    function finalizeAnswers() {
        console.log('Finalizing answers:', { a1, a2, a3 });
        // Validate all fields and create new errors object
        const hasError1 = !a1.trim();
        const hasError2 = !a2.trim();
        const hasError3 = !a3.trim();
        
        errors = { q1: hasError1, q2: hasError2, q3: hasError3 };
        console.log('Errors:', errors);

        // Check if all are filled
        if (!hasError1 && !hasError2 && !hasError3) {
            isFinalized = true;
            // Save finalized state to store
            studentData.update((data: any) => {
                if (!data) return data;
                return {
                    ...data,
                    answeredQuestions: {
                        ...(data.answeredQuestions || {}),
                        [q1]: a1,
                        [q2]: a2,
                        [q3]: a3,
                        ['story3-2_finalized']: true
                    }
                };
            });
        }
    }

    function editAnswers() {
        isFinalized = false;
        errors = { q1: false, q2: false, q3: false };
        // Update store to remove finalized flag
        studentData.update((data: any) => {
            if (!data) return data;
            return {
                ...data,
                answeredQuestions: {
                    ...(data.answeredQuestions || {}),
                    ['story3-2_finalized']: false
                }
            };
        });
    }
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
        1. Based on the story, how do Rosa and Juan feel when they accidentally break Lola Gloria's flowerpot?
      </label>
      <textarea
        id="q1"
        class="answer"
        class:error={errors.q1}
        class:readonly={isFinalized}
        bind:value={a1}
        on:input={handleInput}
        readonly={isFinalized}
        placeholder="Type your answer here..."
      ></textarea>
    </div>

    <div class="qa">
      <label class="question" for="q2">
        2. What do you think Rosa and Juan will do next?
      </label>
      <textarea
        id="q2"
        class="answer"
        class:error={errors.q2}
        class:readonly={isFinalized}
        bind:value={a2}
        on:input={handleInput}
        readonly={isFinalized}
        placeholder="Type your answer here..."
      ></textarea>
    </div>

    <div class="qa">
      <label class="question" for="q3">
        3. What do you think Lola Gloria will feel after?
      </label>
      <textarea
        id="q3"
        class="answer"
        class:error={errors.q3}
        class:readonly={isFinalized}
        bind:value={a3}
        on:input={handleInput}
        readonly={isFinalized}
        placeholder="Type your answer here..."
      ></textarea>
    </div>

    <div class="finalize-section">
      {#if !isFinalized}
        <button class="finalize-btn" on:click={finalizeAnswers} type="button">
          ✓ Finalize Answers
        </button>
      {:else}
        <div class="finalized-container">
          <div class="finalized-msg">
            ✅ Answers finalized! You can now proceed.
          </div>
          <button class="edit-btn" on:click={editAnswers} type="button">
            ✏️ Edit Answers
          </button>
        </div>
      {/if}
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
    overflow-y: auto;
    overflow-x: hidden;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    padding: clamp(12px, 3vw, 24px);
    padding-bottom: clamp(20px, 4vw, 32px);
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
  .answer.error { 
    border-color: #ef4444; 
    background: #fef2f2;
    box-shadow: 0 0 0 3px rgba(239,68,68,0.15);
  }
  .answer.readonly {
    background: #f3f4f6;
    cursor: not-allowed;
    opacity: 0.7;
  }

  .finalize-section {
    margin-top: 16px;
    display: flex;
    justify-content: center;
  }

  .finalized-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
  }

  .finalize-btn {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    font-size: clamp(1rem, 2.5vw, 1.2rem);
    font-weight: 700;
    padding: 12px 32px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
    transition: all 0.2s ease;
  }

  .finalize-btn:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16,185,129,0.4);
  }

  .finalize-btn:active {
    transform: translateY(0);
  }

  .edit-btn {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    font-size: clamp(0.9rem, 2.2vw, 1.1rem);
    font-weight: 600;
    padding: 10px 24px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    box-shadow: 0 3px 10px rgba(245,158,11,0.3);
    transition: all 0.2s ease;
  }

  .edit-btn:hover {
    background: linear-gradient(135deg, #d97706, #b45309);
    transform: translateY(-2px);
    box-shadow: 0 5px 14px rgba(245,158,11,0.4);
  }

  .edit-btn:active {
    transform: translateY(0);
  }

  .finalized-msg {
    background: #d1fae5;
    color: #065f46;
    font-size: clamp(1rem, 2.5vw, 1.1rem);
    font-weight: 700;
    padding: 12px 24px;
    border-radius: 12px;
    border: 2px solid #10b981;
  }

  .footer { display: flex; justify-content: center; margin-top: 8px; }
  .hint { text-align: center; font-size: 0.9rem; color: #64748b; margin: 0; }
</style>
