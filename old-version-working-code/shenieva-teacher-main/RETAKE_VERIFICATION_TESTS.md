# Retake Answer Clearing - Verification Tests

## Issue Fixed
Previous answers were persisting when students retook quizzes because of race condition between store subscription and page reload.

## Solution Implemented
Direct localStorage manipulation in `retakeLevel()` functions before `location.replace()`:
```javascript
// Get current data from localStorage
const currentData = JSON.parse(localStorage.getItem('studentData') || '{}');
const answeredQuestions = currentData.answeredQuestions || {};

// Remove only keys for current level
Object.keys(answeredQuestions).forEach(key => {
    if (key.startsWith('storyX')) {  // X = 1, 2, or 3
        delete answeredQuestions[key];
    }
});

// Save back to localStorage
currentData.answeredQuestions = answeredQuestions;
localStorage.setItem('studentData', JSON.stringify(currentData));

// Also update store
resetLevelAnswers(X);
```

## Answer Key Patterns

### Level 1 (Sari-sari Store - Honesty)
- Story 1-1: `story1_q1`, `story1_q2`, `story1_q3`, etc.
- Story 1-2: `story1_2_q1`, `story1_2_q2`, `story1_2_q3`, etc.
- Story 1-3: `story1_3_q1`, `story1_3_q2`, `story1_3_q3`, etc.
- **Clearing Pattern**: `key.startsWith('story1')` ✅ Catches all variants

### Level 2 (Wet Market - Health)
- Story 2-1: `story2-1_slide6`, `story2-1_slide7`, etc.
- Story 2-2: `story2-2_slide5`, `story2-2_slide6`, etc.
- Story 2-3: `story2-3_slide5`, `story2-3_slide6`, etc.
- **Clearing Pattern**: `key.startsWith('story2')` ✅ Catches all variants

### Level 3 (Plaza - Good Choices)
- Story 3-1: `story3-1_q1`, `story3-1_q2`, `story3-1_q3`, etc.
- Story 3-2: `story3-2_q1`, `story3-2_q2`, `story3-2_q3`, etc.
- Story 3-3: `story3-3_q1`, `story3-3_q2`, `story3-3_q3`, etc.
- **Clearing Pattern**: `key.startsWith('story3')` ✅ Catches all variants

## Verification Checklist

### ✅ Test 1: Level 1 Retake Isolation
**Purpose**: Ensure retaking Level 1 doesn't affect Level 2 or Level 3 answers

**Steps**:
1. Login as a student
2. Complete Level 1, Level 2, and Level 3 (any story from each)
3. Open browser console (F12)
4. Run: `console.log(JSON.parse(localStorage.getItem('studentData')).answeredQuestions)`
5. Note the keys present (should have story1_, story2-, story3- keys)
6. Go back to Level 1 and click "Retake Quiz"
7. BEFORE answering any new questions, run the console command again
8. **Expected**: Only `story2-` and `story3-` keys remain, all `story1` keys are gone

**Console Command for Quick Check**:
```javascript
const data = JSON.parse(localStorage.getItem('studentData'));
const keys = Object.keys(data.answeredQuestions || {});
console.log('Story1 keys:', keys.filter(k => k.startsWith('story1')));
console.log('Story2 keys:', keys.filter(k => k.startsWith('story2')));
console.log('Story3 keys:', keys.filter(k => k.startsWith('story3')));
```

### ✅ Test 2: Level 2 Retake Isolation
**Purpose**: Ensure retaking Level 2 doesn't affect Level 1 or Level 3 answers

**Steps**:
1. Ensure you have answered questions in all 3 levels
2. Go to Level 2 results page
3. Run the console command from Test 1 to see current state
4. Click "Retake Quiz"
5. Run the console command again
6. **Expected**: Only `story1` and `story3-` keys remain, all `story2-` keys are gone

### ✅ Test 3: Level 3 Retake Isolation
**Purpose**: Ensure retaking Level 3 doesn't affect Level 1 or Level 2 answers

**Steps**:
1. Ensure you have answered questions in all 3 levels
2. Go to Level 3 results page
3. Run the console command from Test 1 to see current state
4. Click "Retake Quiz"
5. Run the console command again
6. **Expected**: Only `story1` and `story2-` keys remain, all `story3-` keys are gone

### ✅ Test 4: Store Synchronization
**Purpose**: Ensure store subscription still works after direct localStorage manipulation

**Steps**:
1. Complete a quiz and retake it
2. Answer new questions in the retake
3. Check localStorage: `JSON.parse(localStorage.getItem('studentData')).answeredQuestions`
4. **Expected**: New answers are saved with correct keys
5. Refresh the page
6. **Expected**: Answers persist and display correctly in the UI

### ✅ Test 5: is_final Flag Submission
**Purpose**: Ensure quiz submission still works correctly with is_final flag

**Steps**:
1. Complete Level 1 with a failing score (< 60%)
2. Open browser Network tab (F12 → Network)
3. Click "Retake Quiz" and complete it
4. Look for the POST request to `submit_level1_quiz.php`
5. Check the request payload
6. **Expected**: `is_final: 0` (because it's a retake within retry limit)
7. Check database: `SELECT * FROM level1_quiz ORDER BY date_taken DESC LIMIT 1`
8. **Expected**: No new record added (retakes not saved)
9. Complete the retake with passing score (≥ 60%)
10. **Expected**: `is_final: 1` and new record saved to database

### ✅ Test 6: Completed Level Behavior
**Purpose**: Ensure already-completed levels work correctly

**Steps**:
1. Complete Level 1 with passing score
2. Navigate away and come back to Level 1
3. Click "Play Again" or navigate to the story
4. **Expected**: Can replay the story
5. Complete the quiz again
6. **Expected**: Results show, but previous score/data remains in database
7. Check localStorage for old answers
8. **Expected**: Previous answers may persist (replay doesn't force retake)

### ✅ Test 7: Cross-Story Retakes
**Purpose**: Ensure retaking one story doesn't affect other stories in the same level

**Steps**:
1. Complete Level 1 Story 1 (story1-1)
2. Complete Level 1 Story 2 (story1-2)
3. Run console: `Object.keys(JSON.parse(localStorage.getItem('studentData')).answeredQuestions).filter(k => k.startsWith('story1'))`
4. **Expected**: See both `story1_` (from story1-1) and `story1_2_` (from story1-2) keys
5. Retake Story 1 (story1-1)
6. Run console command again
7. **Expected**: ALL `story1` keys are gone (including story1_2_)
8. **Note**: This is INTENDED behavior - retaking any story in Level 1 clears ALL Level 1 answers

### ✅ Test 8: Navigation Flow
**Purpose**: Ensure location.replace() and goto() still work correctly

**Steps**:
1. Click "Retake Quiz" on any level
2. **Expected**: Page reloads and returns to the quiz start
3. Check browser history (back button)
4. **Expected**: Can navigate back to previous pages
5. Complete a quiz without retaking
6. **Expected**: Navigates to appropriate next location (village, next story, etc.)

## Technical Details

### Files Modified
- `src/routes/student/Levels/Level1/slide_last.svelte` (lines 378-401)
- `src/routes/student/Levels/Level2/slide_last.svelte` (lines 340-360)
- `src/routes/student/Levels/Level3/slide_last.svelte` (lines 252-272)

### Why Direct localStorage Manipulation?
The previous approach used `resetLevelAnswers()` then waited with `setTimeout()` for the store subscription to persist to localStorage. However, this created a race condition:
1. `resetLevelAnswers()` updates the store
2. Store subscription triggers and writes to localStorage (ASYNC)
3. `location.replace()` immediately reloads the page (SYNC)
4. Page reloads with OLD data from localStorage (because step 2 didn't complete)

By manipulating localStorage directly BEFORE the page reload, we guarantee the cleared data is ready when the page reloads.

### Why Call Both localStorage + resetLevelAnswers()?
- **localStorage direct manipulation**: Ensures immediate clearing before page reload
- **resetLevelAnswers()**: Keeps the Svelte store in sync for any code that reads from `$studentData` before reload

### Store Subscription Still Works
After the direct localStorage manipulation and page reload:
1. Page loads and initializes store from localStorage (now with cleared answers)
2. Student answers new questions
3. Store updates via `studentData.update()`
4. Store subscription writes to localStorage
5. Everything continues working normally

## Potential Issues to Watch For

### ❌ FALSE POSITIVE: "Store out of sync"
If you check `$studentData` immediately after direct localStorage manipulation but BEFORE page reload, they may appear different. This is EXPECTED and harmless because the page reloads immediately.

### ❌ FALSE POSITIVE: "Lost answers from other levels"
If you retake Level 1 Story 1 (story1-1) and lose answers from Level 1 Story 2 (story1_2_), this is INTENDED behavior. Retaking any story in a level clears ALL answers for that level, ensuring a clean slate.

### ✅ REAL ISSUE: "Answers persist after retake"
If after clicking "Retake Quiz" and the page reloading, you still see previously selected answers in the quiz form, this indicates:
- localStorage clearing didn't execute
- Check browser console for errors in `retakeLevel()` function
- Verify `localStorage.setItem()` completed successfully

### ✅ REAL ISSUE: "Other level answers cleared"
If retaking Level 1 clears Level 2 answers, check:
- `.startsWith('story1')` pattern is correct in the code
- No typos in the level number
- Console log the keys being deleted

## Success Criteria

✅ **All tests pass** means:
1. Retaking Level 1 clears only `story1*` keys
2. Retaking Level 2 clears only `story2-*` keys
3. Retaking Level 3 clears only `story3-*` keys
4. New answers save correctly after retake
5. Quiz submission and is_final flag work unchanged
6. Navigation flows correctly
7. No console errors
8. Database saves only final submissions

## Rollback Plan

If issues arise, revert these three files to use the previous approach:
```javascript
resetLevelAnswers(X);
setTimeout(() => {
    location.replace(`/student/Levels/LevelX?story=${storyKey}&retake=1`);
}, 100);
```

However, this reintroduces the race condition and may cause answers to persist on retake.
