# Quiz Database Update - Score to Point Migration

## Summary of Changes

### 1. Database Column Renamed: `score` → `point`

All three quiz tables have been updated to use `point` instead of `score`:
- **level1_quiz**: Changed `score` column to `point`
- **level2_quiz**: Changed `score` column to `point`
- **level3_quiz**: Changed `score` column to `point`

**Reasoning**: Each question gets 0 or 1 point based on correctness. This is clearer terminology than "score" which could be confused with overall quiz score.

---

## 2. PHP Backend Updates

### Files Modified:

#### A. `src/lib/api/submit_level1_quiz.php`
**Changes:**
- ✅ Changed INSERT column from `score` to `point`
- ✅ Calculate point per question: `1` if correct, `0` if wrong
- ✅ Use case-insensitive comparison: `strtolower(trim($selected)) === strtolower(trim($correct))`

```php
// Calculate point per question: 1 if correct, 0 if wrong
$point = (strtolower(trim($selectedEsc)) === strtolower(trim($correct))) ? 1 : 0;
$stmt->bind_param("issssssssii", $student_id, $storyTitle, $qText, $choiceA, $choiceB, $choiceC, $choiceD, $correct, $selectedEsc, $point, $aNum);
```

#### B. `src/lib/api/submit_level2_quiz.php`
**Changes:**
- ✅ Changed INSERT column from `score` to `point`
- ✅ Calculate point per sub-question: `1` if correct, `0` if wrong
- ✅ Fixed PHP error: Changed `String()` to `(string)` cast

```php
// Calculate point for this specific sub-question: 1 if correct, 0 if wrong
$point = ((string)($selectedMap[$subKey] ?? '') === (string)$correctVal) ? 1 : 0;
$stmt->bind_param("issssii", $student_id, $storyTitle, $questionText, $correctAnswer, $selectedAnswer, $point, $aNum);
```

#### C. `src/lib/api/submit_level3_quiz.php`
**Changes:**
- ✅ Changed INSERT column from `score` to `point`
- ✅ Point set to `0` (pending manual teacher review)
- ✅ Teachers can update to `1` (correct) after reviewing essay answers

```php
// Point is 0 (pending manual review by teacher)
// Teachers can update this to 1 (correct) or 0 (incorrect) after reviewing
$point = 0;
$stmt->bind_param("issiii", $student_id, $storyTitle, $questionText, $studentAnswerEsc, $point, $aNum);
```

---

## 3. Frontend Updates

### Files Modified:

#### A. `src/routes/student/Levels/Level1/slide_last.svelte`
**Changes:**
1. ✅ Removed check for `ribbons <= 0` - now saves quiz even with 0 score
2. ✅ Ribbons only updated if `ribbons > 0`
3. ✅ Quiz ALWAYS saved when claiming/continuing (is_final = 1)
4. ✅ Updated success messages:
   - With ribbons: "Ribbons and quiz saved! 🎉"
   - Without ribbons: "Quiz saved! 📝"

```typescript
// When claiming ribbons/continuing, this is ALWAYS a final submission
const isFinal = 1; // Always final when claiming
```

#### B. `src/routes/student/Levels/Level2/slide_last.svelte`
**Changes:**
1. ✅ Removed check for `ribbons <= 0` - now saves quiz even with 0 score
2. ✅ Ribbons only updated if `ribbons > 0`
3. ✅ Quiz ALWAYS saved when claiming/continuing (is_final = 1)
4. ✅ Mark story as claimed even with 0 ribbons
5. ✅ Updated success messages:
   - With ribbons: "Ribbons and quiz saved! 🎉"
   - Without ribbons: "Quiz saved! 📝"

```typescript
// When claiming ribbons/continuing, this is ALWAYS a final submission
// Save to database regardless of score (perfect or not perfect)
const isFinal = 1; // Always final when claiming
```

#### C. `src/routes/student/Levels/Level3/slide_last.svelte`
**Changes:**
1. ✅ Quiz ALWAYS saved when continuing (is_final = 1)
2. ✅ No ribbons system in Level 3, just quiz submission

```typescript
// When student continues/submits, this is ALWAYS a final submission
// Save to database regardless of retake availability
const isFinal = 1; // Always final when submitting in Level 3
```

---

## 4. Key Behavior Changes

### Before:
❌ Quiz with 0 score was NOT saved to database
❌ is_final logic was complex: `!canRetake() || score >= passing_score`
❌ Students with bad scores wouldn't have their attempts recorded

### After:
✅ Quiz with ANY score (including 0) IS saved to database
✅ is_final is always 1 when student claims/continues
✅ ALL final submissions are recorded, perfect or not
✅ Ribbons are only awarded if score > 0
✅ Stories are marked as claimed even with 0 ribbons

---

## 5. Database Point Values

### Level 1 (Multiple Choice):
- **1 point** = Correct answer
- **0 points** = Wrong answer
- Comparison: Case-insensitive

### Level 2 (Drag-and-Drop):
- **1 point** = Correct mapping for each sub-question
- **0 points** = Wrong mapping
- One database row per sub-question

### Level 3 (Essay):
- **0 points** = Initial submission (pending review)
- **1 point** = Correct (set by teacher after review)
- **0 points** = Incorrect (set by teacher after review)

---

## 6. Testing Checklist

### Level 1:
- [ ] Complete quiz with perfect score → Should save with all questions = 1 point
- [ ] Complete quiz with some wrong answers → Should save with mixed 0/1 points
- [ ] Complete quiz with all wrong answers (0 score) → Should save with all questions = 0 points
- [ ] Verify message: "Ribbons and quiz saved! 🎉" (if score > 0)
- [ ] Verify message: "Quiz saved! 📝" (if score = 0)
- [ ] Check database: `SELECT * FROM level1_quiz WHERE studentID = ? ORDER BY createdAt DESC;`

### Level 2:
- [ ] Complete quiz with correct mappings → Should save with point = 1 per sub-question
- [ ] Complete quiz with wrong mappings → Should save with point = 0 per sub-question
- [ ] Complete quiz with all wrong (0 score) → Should save quiz without ribbons
- [ ] Verify message: "Ribbons and quiz saved! 🎉" (if score > 0)
- [ ] Verify message: "Quiz saved! 📝" (if score = 0)
- [ ] Check database: `SELECT * FROM level2_quiz WHERE studentID = ? ORDER BY createdAt DESC;`

### Level 3:
- [ ] Complete quiz and continue → Should save all answers with point = 0
- [ ] Verify no error messages
- [ ] Check database: `SELECT * FROM level3_quiz WHERE studentID = ? ORDER BY createdAt DESC;`
- [ ] Verify all point values are 0 (pending teacher review)

---

## 7. SQL Queries for Verification

### Check recent submissions:
```sql
-- Level 1
SELECT studentID, storyTitle, question, selectedAnswer, correctAnswer, point, attempt, createdAt 
FROM level1_quiz 
WHERE studentID = YOUR_STUDENT_ID 
ORDER BY createdAt DESC 
LIMIT 20;

-- Level 2
SELECT studentID, storyTitle, question, selectedAnswer, correctAnswer, point, attempt, createdAt 
FROM level2_quiz 
WHERE studentID = YOUR_STUDENT_ID 
ORDER BY createdAt DESC 
LIMIT 20;

-- Level 3
SELECT studentID, storyTitle, question, studentAnswer, point, attempt, createdAt 
FROM level3_quiz 
WHERE studentID = YOUR_STUDENT_ID 
ORDER BY createdAt DESC 
LIMIT 20;
```

### Check points summary:
```sql
-- Total points per story per student
SELECT studentID, storyTitle, attempt, SUM(point) as total_points, COUNT(*) as questions
FROM level1_quiz
WHERE studentID = YOUR_STUDENT_ID
GROUP BY studentID, storyTitle, attempt
ORDER BY createdAt DESC;
```

---

## 8. Migration Notes

### If you have existing data with 'score' column:

You need to rename the column in your database:

```sql
-- Level 1
ALTER TABLE level1_quiz CHANGE COLUMN score point INT(11) DEFAULT 0;

-- Level 2
ALTER TABLE level2_quiz CHANGE COLUMN score point INT(11) DEFAULT 0;

-- Level 3
ALTER TABLE level3_quiz CHANGE COLUMN score point INT(11) DEFAULT 0;
```

### Or use MODIFY if CHANGE doesn't work:
```sql
ALTER TABLE level1_quiz MODIFY COLUMN score point INT(11) DEFAULT 0;
ALTER TABLE level2_quiz MODIFY COLUMN score point INT(11) DEFAULT 0;
ALTER TABLE level3_quiz MODIFY COLUMN score point INT(11) DEFAULT 0;
```

---

## 9. Benefits of These Changes

1. ✅ **Complete Data**: All quiz attempts are recorded, even with 0 score
2. ✅ **Teacher Insights**: Teachers can see all student attempts, including struggles
3. ✅ **Fair Assessment**: Students get credit for trying, even if they don't pass
4. ✅ **Clear Terminology**: "point" is more intuitive than "score" for per-question values
5. ✅ **Consistent Behavior**: All levels now handle final submissions the same way
6. ✅ **No Data Loss**: No more skipped quiz saves due to low scores

---

## 10. Important Notes

- ⚠️ **is_final flag**: Now always 1 when claiming/continuing, ensuring all final submissions are saved
- ⚠️ **Ribbons**: Only awarded if score > 0, but quiz is saved regardless
- ⚠️ **Level 3**: Point is always 0 initially, teachers must manually review and update
- ⚠️ **Database**: Make sure to rename 'score' column to 'point' in all three tables before testing

---

## Done! ✅

All changes complete. Test each level to verify quiz submissions are saved correctly with proper point values (0 or 1 per question).
