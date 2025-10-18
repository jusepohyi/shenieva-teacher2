# Level 2 Quiz Save Issue - Database Column Type Fix

## Problem
When claiming ribbons in Level 2, you get:
```
Ribbons saved but failed to save quiz. Please try again.
Console: 500 (Internal Server Error)
```

## Root Cause
The `level2_quiz` table has columns `correctAnswer` and `selectedAnswer` defined as `VARCHAR(255)`, but Level 2 stores long text answers that exceed 255 characters.

### Your Current Table Structure:
```
correctAnswer    varchar(255)  NO  NULL
selectedAnswer   varchar(255)  YES NULL
```

### What Level 2 Tries to Store:
Level 2 uses drag-and-drop quizzes with answers like:
```
"The doctor will say that Hector's weight is above average for his age right now."
"He will tell Hector that he needs to improve his eating habits and start exercising..."
```

These sentences are often longer than 255 characters, causing SQL insert failures.

## Solution

Change the column types from `VARCHAR(255)` to `TEXT` to allow longer content.

### Option 1: Automatic Fix (Recommended)

**Open this URL in your browser:**
```
http://localhost/shenieva-teacher/src/lib/api/fix_level2_table.php
```

This script will:
1. Show your current table structure
2. Automatically change `correctAnswer` to TEXT
3. Automatically change `selectedAnswer` to TEXT
4. Show the updated structure with changes highlighted
5. Confirm completion

### Option 2: Manual Fix via phpMyAdmin

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select database: `shenieva_DB`
3. Click on table: `level2_quiz`
4. Click "SQL" tab
5. Run these queries:

```sql
ALTER TABLE level2_quiz 
MODIFY COLUMN correctAnswer TEXT;

ALTER TABLE level2_quiz 
MODIFY COLUMN selectedAnswer TEXT;
```

### Option 3: Manual Fix via Command Line

```sql
USE shenieva_DB;

ALTER TABLE level2_quiz 
MODIFY COLUMN correctAnswer TEXT,
MODIFY COLUMN selectedAnswer TEXT;
```

## After the Fix

### Expected Table Structure:
```
quizID           int(11)       NO  PRI  NULL  auto_increment
studentID        int(11)       NO  MUL  NULL
storyTitle       varchar(255)  NO       NULL
question         text          NO       NULL
correctAnswer    text          NO       NULL  ← FIXED
selectedAnswer   text          YES      NULL  ← FIXED
score            int(11)       YES      0
attempt          int(11)       YES      1
createdAt        timestamp     NO       current_timestamp()
```

### Test It:
1. Complete any Level 2 story quiz
2. Click "Claim Ribbons & Continue"
3. You should see: **"Ribbons and quiz saved! 🎉"**
4. No more 500 errors!

### Verify in Database:
```sql
SELECT * FROM level2_quiz 
WHERE studentID = YOUR_STUDENT_ID 
ORDER BY createdAt DESC 
LIMIT 5;
```

You should see your quiz answers properly saved with full-length text.

## Why This Happened

Level 1 and Level 3 likely have TEXT columns already, but Level 2's table was created with VARCHAR(255) limits. This is a common oversight when creating tables - TEXT should be used when the content length is unpredictable.

## Prevention

For future reference, when creating quiz tables:
- Use `TEXT` for question/answer content (unpredictable length)
- Use `VARCHAR(255)` for titles, names, short codes (known max length)
- Use `INT` for IDs, scores, counters

## Files Modified (for reference)

The PHP backend was already updated to:
- Add better error logging
- Truncate data if too long (temporary workaround)
- Filter answers to only current story
- Display detailed error messages

But the real fix is updating the database column types.
