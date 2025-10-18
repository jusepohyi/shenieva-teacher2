-- SQL Migration Script: Rename 'score' column to 'point' in quiz tables
-- Run this in phpMyAdmin or MySQL command line

-- Use the correct database
USE shenieva_DB;

-- Level 1 Quiz Table: Rename score to point
ALTER TABLE level1_quiz 
CHANGE COLUMN score point INT(11) DEFAULT 0;

-- Level 2 Quiz Table: Rename score to point
ALTER TABLE level2_quiz 
CHANGE COLUMN score point INT(11) DEFAULT 0;

-- Level 3 Quiz Table: Rename score to point
ALTER TABLE level3_quiz 
CHANGE COLUMN score point INT(11) DEFAULT 0;

-- Verify changes
DESCRIBE level1_quiz;
DESCRIBE level2_quiz;
DESCRIBE level3_quiz;

-- Success message
SELECT 'Migration complete! All quiz tables now use "point" column instead of "score".' AS status;
