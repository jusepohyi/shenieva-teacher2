-- SQL script to create level quiz tables for student quiz submissions
-- Run this in phpMyAdmin or MySQL command line to create the missing tables

-- Table for Level 1 Quiz (Multiple Choice Questions)
CREATE TABLE IF NOT EXISTS `level1_quiz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` int(11) NOT NULL,
  `storyTitle` varchar(255) NOT NULL,
  `question` text NOT NULL,
  `choiceA` text DEFAULT NULL,
  `choiceB` text DEFAULT NULL,
  `choiceC` text DEFAULT NULL,
  `choiceD` text DEFAULT NULL,
  `correctAnswer` varchar(10) DEFAULT NULL,
  `selectedAnswer` varchar(10) DEFAULT NULL,
  `score` int(11) DEFAULT 0,
  `attempt` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `studentID` (`studentID`),
  KEY `storyTitle` (`storyTitle`),
  KEY `attempt` (`attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for Level 2 Quiz (Drag and Drop Mapping Questions)
CREATE TABLE IF NOT EXISTS `level2_quiz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` int(11) NOT NULL,
  `storyTitle` varchar(255) NOT NULL,
  `question` text NOT NULL,
  `correctAnswer` text DEFAULT NULL,
  `selectedAnswer` text DEFAULT NULL,
  `score` int(11) DEFAULT 0,
  `attempt` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `studentID` (`studentID`),
  KEY `storyTitle` (`storyTitle`),
  KEY `attempt` (`attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for Level 3 Quiz (Essay/Text Questions)
CREATE TABLE IF NOT EXISTS `level3_quiz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` int(11) NOT NULL,
  `storyTitle` varchar(255) NOT NULL,
  `question` text NOT NULL,
  `correctAnswer` text DEFAULT NULL,
  `selectedAnswer` text DEFAULT NULL,
  `score` int(11) DEFAULT 0,
  `attempt` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `studentID` (`studentID`),
  KEY `storyTitle` (`storyTitle`),
  KEY `attempt` (`attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
