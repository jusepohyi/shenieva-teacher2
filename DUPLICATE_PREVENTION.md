# Duplicate Ribbon and Quiz Prevention

## Overview
This document explains how the system prevents students from claiming ribbons multiple times and saving duplicate quizzes to the database.

## Business Rules

### One Ribbon Claim Per Level
- Students can claim ribbons **ONCE per level**, regardless of which story they play
- Even if a student plays all 3 stories in Level 1, they can only claim ribbons once total for that level
- This prevents ribbon farming by replaying different stories

### One Quiz Save Per Level
- Students can save their quiz to the database **ONCE per level**, regardless of which story they play
- Once a quiz is saved for any story in a level, subsequent quizzes in that level will NOT be saved to the database
- This prevents database bloat and ensures clean data

## Implementation

### Backend: `check_quiz_exists.php`

**Purpose:** Server-side validation to check if student has already submitted a quiz in a level

**Query Logic:**
```php
// Checks if ANY quiz exists for this student in this level (ignores story)
SELECT COUNT(*) as count FROM level{X}_quiz WHERE studentID = ?
```

**Parameters:**
- `student_id` - The student's ID
- `story_key` - The story identifier (passed but not used in query)
- `level` - The level number (1, 2, or 3)

**Response:**
```json
{
  "success": true,
  "exists": true/false,
  "student_id": 123,
  "level": 1,
  "table": "level1_quiz",
  "message": "Student has already submitted a quiz in Level 1"
}
```

### Frontend: All Level `slide_last.svelte` Files

#### State Variables
```javascript
let quizAlreadyExists = false;  // Database check result
let checkingDatabase = false;   // Loading state
```

#### Check Function
```javascript
async function checkQuizExistsInDatabase() {
    checkingDatabase = true;
    const student_id = $studentData?.pk_studentID;
    
    const params = new URLSearchParams({
        student_id: String(student_id),
        story_key: storyKey,
        level: '1' // or '2' or '3'
    });
    
    const response = await fetch(`/lib/api/check_quiz_exists.php?${params.toString()}`);
    const result = await response.json();
    
    quizAlreadyExists = result.exists || false;
    checkingDatabase = false;
}
```

#### Level 1 & 2: Ribbon Claim Prevention
```javascript
async function claimRibbonsAndContinue() {
    // Check if quiz already exists before saving
    if (quizAlreadyExists) {
        console.log('Quiz already exists for this level — skipping save');
        ribbonMessage = 'You have already claimed ribbons for Level X';
        goto('/student/game/trash_X');
        return;
    }
    
    // Otherwise, proceed with ribbon claim and quiz save...
}
```

#### Level 3: Quiz Save Prevention
```javascript
async function saveQuizToDatabase() {
    // Check if quiz already exists before saving
    if (quizAlreadyExists) {
        console.log('Quiz already exists for this level — skipping save');
        return;
    }
    
    // Otherwise, proceed with quiz save...
}
```

#### UI Updates
```svelte
{#if isLevelCompleted || quizAlreadyExists}
    <button on:click={continueWithoutSaving}>
        Continue to Game ➡️
    </button>
    {#if quizAlreadyExists}
        <p>You have already claimed ribbons for Level X</p>
    {/if}
{:else}
    <button on:click={claimRibbonsAndContinue}>
        Claim Ribbons & Continue
    </button>
{/if}
```

## Example Scenarios

### Scenario 1: First Story in Level
1. Student plays **Level 1, Story 1**
2. Completes quiz successfully
3. System checks database: No quiz exists for this student in Level 1
4. Shows: "Claim Ribbons & Continue" button
5. Student clicks → Ribbons updated + Quiz saved to `level1_quiz` table ✅

### Scenario 2: Second Story in Same Level
1. Student plays **Level 1, Story 2** (different story, same level)
2. Completes quiz successfully
3. System checks database: Quiz exists for this student in Level 1 (from Story 1)
4. Shows: "Continue to Game ➡️" button only (no ribbon claim option)
5. Message: "You have already claimed ribbons for Level 1"
6. Student clicks → Goes to game, NO quiz saved, NO ribbons awarded ❌

### Scenario 3: Different Level
1. Student plays **Level 2, Story 1** (different level)
2. Completes quiz successfully
3. System checks database: No quiz exists for this student in Level 2
4. Shows: "Claim Ribbons & Continue" button
5. Student clicks → Ribbons updated + Quiz saved to `level2_quiz` table ✅

## Database Impact

### Before Implementation
- Student plays Level 1, Story 1 → Saves 5 quiz rows
- Student plays Level 1, Story 2 → Saves 5 more quiz rows (10 total)
- Student plays Level 1, Story 3 → Saves 5 more quiz rows (15 total)
- **Total: 15 rows for one student in one level**

### After Implementation
- Student plays Level 1, Story 1 → Saves 5 quiz rows
- Student plays Level 1, Story 2 → Saves 0 rows (already exists)
- Student plays Level 1, Story 3 → Saves 0 rows (already exists)
- **Total: 5 rows for one student in one level** ✅

## Benefits

1. **Prevents Ribbon Farming**: Students cannot gain unlimited ribbons by replaying stories
2. **Clean Database**: Only one quiz submission per student per level (not multiple)
3. **Server-Side Validation**: Uses database as source of truth (can't be bypassed by clearing localStorage)
4. **Fair Gameplay**: Encourages progression rather than repetition
5. **Reduced Storage**: Prevents unnecessary database bloat

## Files Modified

### Backend
- `src/lib/api/check_quiz_exists.php` - New endpoint for validation

### Frontend
- `src/routes/student/Levels/Level1/slide_last.svelte` - Added check and prevention logic
- `src/routes/student/Levels/Level2/slide_last.svelte` - Added check and prevention logic
- `src/routes/student/Levels/Level3/slide_last.svelte` - Added check and prevention logic

## Testing Checklist

- [ ] Play Level 1, Story 1 → Claim ribbons → Verify quiz saved to database
- [ ] Play Level 1, Story 2 → Should see "Already claimed" message
- [ ] Verify no new quiz rows added for Story 2
- [ ] Clear localStorage → Play Level 1, Story 2 → Should STILL see "Already claimed"
- [ ] Play Level 2, Story 1 → Should be able to claim ribbons (different level)
- [ ] Repeat for Level 2 and Level 3
- [ ] Check database to ensure only 1 quiz per student per level

## Notes

- The system uses `studentID` + `level` to determine if a quiz exists
- `storyTitle` is NOT part of the uniqueness check
- This means students can play all stories in a level, but only the first completion counts
- Teachers can still see all quiz attempts in the admin panel (from the first story played)
