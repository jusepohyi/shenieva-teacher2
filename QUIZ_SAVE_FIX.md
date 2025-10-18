# Quiz Save Issue Fix

## Problem
When claiming ribbons in Level 2 (and potentially other levels), the error occurred:
```
Ribbons saved but failed to save quiz. Please try again.
```

The ribbons were being saved successfully, but the quiz data was failing to save to the database.

## Root Causes Identified

### 1. JSON Parsing Error
**Issue**: PHP was outputting HTML error messages (warnings/notices) before JSON responses, causing invalid JSON like:
```
"<br /> <b>Warning:...</b> ... {"success": true}"
```

**Solution**: Added error suppression to all PHP API files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
```

### 2. Empty Answers Array
**Issue**: The frontend was sending ALL student answers (from all stories) to the PHP script, but not filtering them by story. The PHP script would then try to process answers from other stories that don't have matching correct answers, resulting in no rows being inserted.

**Solution**: Filter answers to only include the current story's questions before sending to API:

**Level 2** (keys like `story2-1_slide6`):
```typescript
const filteredAnswers = Object.fromEntries(
    Object.entries(lastAttempt.answers || {}).filter(([key]) => key.startsWith(storyKey))
);
```

**Level 1** (keys like `story1_slide3`):
```typescript
const filteredAnswers = Object.fromEntries(
    Object.entries(lastAttempt.answers || {}).filter(([key]) => key.startsWith(storyKey.replace('-', '_')))
);
```

**Level 3** (keys like `story3-1_slide5`):
```typescript
const filteredAnswers = Object.fromEntries(
    Object.entries(attemptRecord.answers || {}).filter(([key]) => key.startsWith(storyKey))
);
```

## Files Modified

### PHP Files (Error Suppression)
1. **src/lib/api/conn.php**
   - Added error suppression
   - Changed `die()` to proper JSON error response

2. **src/lib/api/submit_level1_quiz.php**
   - Added error suppression at file start

3. **src/lib/api/submit_level2_quiz.php**
   - Added error suppression at file start
   - Added error logging for debugging
   - Added validation for empty answers
   - Added row count tracking

4. **src/lib/api/submit_level3_quiz.php**
   - Added error suppression at file start

5. **src/lib/api/update_student_ribbons.php**
   - Added error suppression at file start

### Frontend Files (Answer Filtering)
1. **src/routes/student/Levels/Level1/slide_last.svelte**
   - Filter answers to only include current story before sending to API

2. **src/routes/student/Levels/Level2/slide_last.svelte**
   - Filter answers to only include current story before sending to API
   - Added detailed console logging

3. **src/routes/student/Levels/Level3/slide_last.svelte**
   - Filter answers to only include current story before sending to API

## Testing Checklist

### Level 1
- [ ] Complete any Level 1 story quiz
- [ ] Click "Claim Ribbons & Continue"
- [ ] Verify message: "Ribbons and quiz saved! 🎉"
- [ ] Check database: `level1_quiz` table should have new rows

### Level 2
- [ ] Complete any Level 2 story quiz
- [ ] Click "Claim Ribbons & Continue"
- [ ] Verify message: "Ribbons and quiz saved! 🎉"
- [ ] Check database: `level2_quiz` table should have new rows
- [ ] No error: "Ribbons saved but failed to save quiz"

### Level 3
- [ ] Complete any Level 3 story quiz
- [ ] Click "Claim Ribbons & Continue"
- [ ] Verify message: "Ribbons and quiz saved! 🎉"
- [ ] Check database: `level3_quiz` table should have new rows

## Debugging

### Check PHP Error Logs
Location: `C:\xampp\apache\logs\error.log`

Look for entries like:
```
Level 2 Quiz Submission - Student ID: X, Story: story2-1
Answers count: 1
Correct Answers count: 1
Processing question: story2-1_slide6...
Total rows inserted: 5
```

### Browser Console
Check for:
```javascript
Payload.attempt.answers: { "story2-1_slide6": "{...}" }
Payload.correctAnswers: { "story2-1_slide6": "{...}" }
Save quiz result: { success: true, rows_inserted: 5 }
```

### Database Check
```sql
-- Check if quiz was saved
SELECT * FROM level2_quiz WHERE studentID = YOUR_STUDENT_ID ORDER BY created_at DESC LIMIT 10;

-- Check if ribbons were updated
SELECT pk_studentID, studentName, studentRibbon FROM students_table WHERE pk_studentID = YOUR_STUDENT_ID;
```

## Expected Behavior
1. ✅ Student completes quiz and clicks "Claim Ribbons & Continue"
2. ✅ Ribbons are added to `students_table.studentRibbon`
3. ✅ Quiz answers are saved to appropriate `levelX_quiz` table
4. ✅ Success message: "Ribbons and quiz saved! 🎉"
5. ✅ Student is redirected to trash collection game
6. ✅ No JSON parsing errors
7. ✅ No "failed to save quiz" errors

## Notes
- The fix ensures only the current story's answers are sent to the API
- Error logging is now enabled in PHP for debugging (logs to error.log, not displayed to user)
- Empty answers are now properly detected and rejected with clear error message
- All API endpoints now return valid JSON only (no HTML error messages)
