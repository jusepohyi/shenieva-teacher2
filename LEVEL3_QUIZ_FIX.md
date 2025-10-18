# Level 3 Quiz Bug Fix - Student Answers Showing "0"

## Problem Description
Student essay answers in Level 3 quizzes were being saved as "0" in the database instead of the actual text responses.

## Database Schema (level3_quiz table)
```sql
quizID          int(11)       PRI  auto_increment
studentID       int(11)       MUL
storyTitle      varchar(255)
question        text
studentAnswer   text          ← Should store essay text answers
point           int(11)       DEFAULT 0
attempt         int(11)       DEFAULT 1
createdAt       timestamp     DEFAULT current_timestamp()
```

## Root Cause
In `src/lib/api/submit_level3_quiz.php`, the `bind_param()` method was using the wrong data type for the `studentAnswer` parameter.

**Incorrect Code (Line 122):**
```php
$stmt->bind_param("issiii", $student_id, $storyTitle, $questionText, $studentAnswerEsc, $point, $aNum);
//                 ↑↑↑↑↑↑
//                 i s s i i i
//                       ↑ 4th parameter marked as INTEGER - WRONG!
```

**Parameter Mapping (INCORRECT):**
1. `i` → `$student_id` (int) ✅ Correct
2. `s` → `$storyTitle` (string) ✅ Correct  
3. `s` → `$questionText` (string) ✅ Correct
4. `i` → `$studentAnswerEsc` (string) ❌ **WRONG!** Should be `s`
5. `i` → `$point` (int) ✅ Correct
6. `i` → `$aNum` (int) ✅ Correct

## What Happened
When PHP tried to bind a STRING (essay answer) as an INTEGER:
- Student types: "Tonya felt excited about her wiggly tooth"
- PHP converts string to int: `(int)"Tonya felt..." = 0`
- Database receives: `0`
- Result: All student answers showed as "0" in the database

## Solution
Changed the 4th parameter type from `i` (integer) to `s` (string):

**Corrected Code:**
```php
// Bind: i=integer, s=string
// Parameters: studentID (int), storyTitle (string), question (string), studentAnswer (string), point (int), attempt (int)
$stmt->bind_param("isssii", $student_id, $storyTitle, $questionText, $studentAnswerEsc, $point, $aNum);
//                 ↑↑↑↑↑↑
//                 i s s s i i
//                       ↑ Now STRING - CORRECT!
```

**Parameter Mapping (CORRECT):**
1. `i` → `$student_id` (int) ✅
2. `s` → `$storyTitle` (string) ✅
3. `s` → `$questionText` (string) ✅
4. `s` → `$studentAnswerEsc` (string) ✅ **FIXED!**
5. `i` → `$point` (int) ✅
6. `i` → `$aNum` (int) ✅

## Files Modified
- `src/lib/api/submit_level3_quiz.php` - Fixed bind_param data type

## Testing
1. Go to Level 3, any story
2. Complete the quiz with essay answers like:
   - "Tonya felt excited and curious about her wiggly tooth"
   - "The tooth wiggled because it was loose and ready to fall out"
   - "The tooth will eventually fall out naturally"
3. Submit the quiz
4. Check database: `SELECT * FROM level3_quiz WHERE studentID = X`
5. **Expected Result**: `studentAnswer` column shows the full text, not "0"

## Impact
- ✅ Student essay answers now save correctly
- ✅ Teachers can read and grade actual responses
- ✅ No data loss for future submissions
- ⚠️ Previous submissions with "0" cannot be recovered (students need to retake)

## Prevention
Added comment in the code to document the parameter types:
```php
// Bind: i=integer, s=string
// Parameters: studentID (int), storyTitle (string), question (string), studentAnswer (string), point (int), attempt (int)
```

This helps future developers understand the expected data types.

## Related
This bug only affected Level 3 because:
- Level 1 uses multiple choice (select from options)
- Level 2 uses drag-and-drop (JSON mapping)
- **Level 3 uses essay questions (free text)** ← Only this level has text answers

The other levels store answers differently and weren't affected by this issue.
