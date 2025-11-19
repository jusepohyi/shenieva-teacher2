-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2025 at 10:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shenieva_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_table`
--

CREATE TABLE `attendance_table` (
  `pk_attendanceID` int(11) NOT NULL,
  `fk_studentID` int(11) NOT NULL,
  `studentName` varchar(100) NOT NULL,
  `attendanceDateTime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_table`
--

INSERT INTO `attendance_table` (`pk_attendanceID`, `fk_studentID`, `studentName`, `attendanceDateTime`) VALUES
(1, 1, '', '2025-03-28 01:50:26'),
(2, 1, 'nal', '2025-03-28 01:53:09'),
(3, 1, 'nal', '2025-03-28 02:02:34'),
(4, 1, 'Nal', '2025-03-28 02:27:10'),
(5, 1, 'nal', '2025-03-28 03:27:58'),
(6, 1, 'naru', '2025-03-28 04:54:29'),
(7, 1, 'nal', '2025-03-28 05:36:16'),
(8, 1, 'Nal', '2025-04-01 08:43:00'),
(9, 1, 'Nal', '2025-04-01 08:45:00'),
(10, 1, 'Nal', '2025-04-01 08:56:00'),
(11, 1, 'Nal', '2025-04-01 09:12:00'),
(12, 1, 'Nal', '2025-04-04 11:21:00'),
(13, 1, 'Nal', '2025-04-04 01:55:00'),
(14, 1, 'Nal', '2025-04-04 02:19:00'),
(15, 1, 'Nal', '2025-04-04 03:15:00'),
(16, 2, 'Nal', '2025-04-04 03:16:00'),
(17, 2, 'Nal', '2025-04-04 03:17:00'),
(18, 1, 'Nal', '2025-04-04 03:18:00'),
(19, 1, 'Nal', '2025-04-04 03:35:00'),
(20, 1, 'nal', '2025-04-04 03:48:00'),
(21, 1, 'nal', '2025-04-05 08:33:00'),
(22, 1, 'Nal', '2025-04-05 09:01:00'),
(23, 1, 'Nal', '2025-04-05 09:03:00'),
(24, 1, 'Nal', '2025-04-05 09:15:00'),
(25, 1, 'NAL', '2025-04-05 09:24:00'),
(26, 1, 'NAL', '2025-04-05 09:55:00'),
(27, 1, 'nAL', '2025-04-05 09:58:00'),
(28, 1, 'naL', '2025-04-05 10:04:00'),
(29, 1, 'nal', '2025-04-05 10:21:00'),
(30, 1, 'nal', '2025-04-05 10:54:00'),
(31, 1, 'nal', '2025-04-05 10:59:00'),
(32, 1, 'nal', '2025-04-05 11:54:00'),
(33, 1, 'sada', '2025-04-05 11:58:00'),
(34, 1, 'sada', '2025-04-05 01:03:00'),
(35, 1, 'sada', '2025-04-08 10:52:00'),
(36, 1, 'A', '2025-04-08 10:57:00'),
(37, 1, 'Nal ', '2025-04-08 02:03:00'),
(38, 1, 'Nal', '2025-04-08 03:18:00'),
(39, 1, 'Naru', '2025-04-15 03:54:00'),
(40, 1, 'Narus', '2025-04-15 03:55:00'),
(41, 1, 'Naru sempai', '2025-04-15 04:03:00'),
(42, 1, 'Naru sempai', '2025-04-29 12:53:00'),
(43, 1, 'Naru sempai', '2025-04-29 01:32:00'),
(44, 1, 'Nal', '2025-04-29 01:37:00'),
(45, 1, 'Nal', '2025-04-29 01:51:00'),
(46, 1, 'Nal', '2025-05-03 11:12:00'),
(47, 1, 'Nal', '2025-05-06 11:34:00'),
(48, 1, 'nAL', '2025-05-06 02:19:00'),
(49, 1, 'NAL', '2025-05-06 03:33:00'),
(50, 1, 'nal', '2025-05-06 04:26:00'),
(51, 1, 'nal', '2025-05-07 09:57:00'),
(52, 1, 'nal', '2025-05-07 10:57:00'),
(53, 1, 'NAL', '2025-05-07 11:27:00'),
(54, 1, 'NAL', '2025-05-09 09:44:00'),
(55, 1, 'NAL', '2025-05-13 01:38:00'),
(56, 1, 'Nal', '2025-05-17 10:16:00'),
(57, 1, 'Nal', '2025-05-17 02:59:00'),
(58, 1, 'Nal', '2025-05-22 11:03:00'),
(59, 1, 'Nal', '2025-05-22 11:16:00'),
(60, 1, 'Nal', '2025-06-17 08:31:00'),
(61, 1, 'Nal', '2025-06-17 09:53:00'),
(62, 1, 'Nal', '2025-06-21 01:43:00'),
(63, 1, 'Nal', '2025-06-24 01:20:00'),
(64, 1, 'Nal', '2025-06-24 03:49:00'),
(65, 1, 'Nal', '2025-06-27 01:36:00');

-- --------------------------------------------------------

--
-- Table structure for table `choices_store2`
--

CREATE TABLE `choices_store2` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `choice_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `choices_store2`
--

INSERT INTO `choices_store2` (`id`, `quiz_id`, `choice_text`, `is_correct`) VALUES
(8, 2, 'Yes', 1),
(9, 2, 'No', 0);

-- --------------------------------------------------------

--
-- Table structure for table `choices_store3`
--

CREATE TABLE `choices_store3` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `choice_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `choices_store3`
--

INSERT INTO `choices_store3` (`id`, `quiz_id`, `choice_text`, `is_correct`) VALUES
(1, 1, 'Yep', 1),
(2, 1, 'Nope', 0);

-- --------------------------------------------------------

--
-- Table structure for table `choices_story1`
--

CREATE TABLE `choices_story1` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `choice_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `choices_story1`
--

INSERT INTO `choices_story1` (`id`, `quiz_id`, `choice_text`, `is_correct`) VALUES
(9, 3, ' Earth 🌍', 0),
(10, 3, 'Mars 🔴', 0),
(11, 3, 'Mercury ☀️', 1),
(12, 3, 'Venus 🪐', 0),
(13, 4, 'Tinker Bell 🧚‍♀️', 1),
(14, 4, 'Cinderella 👸', 0),
(15, 4, 'Elsa ❄️', 0),
(16, 4, 'Ariel 🧜‍♀️', 0),
(17, 5, 'Frogs 🐸', 0),
(18, 5, 'Butterflies 🦋', 1),
(19, 5, 'Birds 🐦', 0),
(20, 5, 'Bees 🐝', 0),
(22, 6, '2', 0),
(23, 6, '3', 0),
(24, 6, '4', 1);

-- --------------------------------------------------------

--
-- Table structure for table `quiz1_table`
--

CREATE TABLE `quiz1_table` (
  `pk_quiz1ID` int(11) NOT NULL,
  `fk_studentID` int(11) NOT NULL,
  `fk_storyQuiz1ID` int(11) NOT NULL,
  `studentAnswer` text NOT NULL,
  `studentAttempts` int(11) NOT NULL,
  `studentPoints` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz1_table`
--

INSERT INTO `quiz1_table` (`pk_quiz1ID`, `fk_studentID`, `fk_storyQuiz1ID`, `studentAnswer`, `studentAttempts`, `studentPoints`) VALUES
(1, 1, 1, 'Yes', 2, 10),
(2, 2, 2, 'False', 1, 15),
(3, 3, 3, 'Correct', 3, 5),
(4, 4, 4, 'No', 2, 20),
(5, 5, 5, 'B', 1, 10);

-- --------------------------------------------------------

--
-- Table structure for table `quiz2_table`
--

CREATE TABLE `quiz2_table` (
  `pk_quiz2ID` int(11) NOT NULL,
  `fk_studentID` int(11) NOT NULL,
  `fk_storyQuiz2ID` int(11) NOT NULL,
  `studentAnswer` text NOT NULL,
  `studentAttempts` int(11) NOT NULL,
  `studentPoints` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz2_table`
--

INSERT INTO `quiz2_table` (`pk_quiz2ID`, `fk_studentID`, `fk_storyQuiz2ID`, `studentAnswer`, `studentAttempts`, `studentPoints`) VALUES
(1, 1, 1, 'Yes', 2, 10),
(2, 2, 2, 'No', 1, 15),
(3, 3, 3, 'Maybe', 3, 5),
(4, 4, 4, 'Always', 2, 20),
(5, 5, 5, 'Never', 1, 10);

-- --------------------------------------------------------

--
-- Table structure for table `quiz3_table`
--

CREATE TABLE `quiz3_table` (
  `pk_quiz3ID` int(11) NOT NULL,
  `fk_studentID` int(11) NOT NULL,
  `fk_storyQuiz3ID` int(11) NOT NULL,
  `studentAnswer` text NOT NULL,
  `studentAttempts` int(11) NOT NULL,
  `studentPoints` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz3_table`
--

INSERT INTO `quiz3_table` (`pk_quiz3ID`, `fk_studentID`, `fk_storyQuiz3ID`, `studentAnswer`, `studentAttempts`, `studentPoints`) VALUES
(1, 1, 1, 'Answer1', 2, 10),
(2, 2, 2, 'Answer2', 1, 15),
(3, 3, 3, 'Answer3', 3, 5),
(4, 4, 4, 'Answer4', 2, 20),
(5, 5, 5, 'Answer5', 1, 10);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes_store2`
--

CREATE TABLE `quizzes_store2` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` varchar(255) NOT NULL,
  `points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes_store2`
--

INSERT INTO `quizzes_store2` (`id`, `question`, `answer`, `points`) VALUES
(2, 'Is this store 2?', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes_store3`
--

CREATE TABLE `quizzes_store3` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` varchar(255) NOT NULL,
  `points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes_store3`
--

INSERT INTO `quizzes_store3` (`id`, `question`, `answer`, `points`) VALUES
(1, 'Is this store 3?', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes_story1`
--

CREATE TABLE `quizzes_story1` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` varchar(255) NOT NULL,
  `points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes_story1`
--

INSERT INTO `quizzes_story1` (`id`, `question`, `answer`, `points`) VALUES
(3, 'Which planet is closest to the Sun?', 'Mercury ☀️', 2),
(4, 'What is the name of the fairy in Peter Pan?', 'Tinker Bell 🧚‍♀️', 1),
(5, 'What do caterpillars turn into?', 'Butterflies 🦋', 2),
(6, 'What is 2 + 2?', '4', 1);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes_story2`
--

CREATE TABLE `quizzes_story2` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` varchar(255) NOT NULL,
  `points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes_story2`
--

INSERT INTO `quizzes_story2` (`id`, `question`, `answer`, `points`) VALUES
(1, 'Is this story 2', 'Yes', 1),
(2, 'Who am I?', 'Nal', 2),
(3, 'How old am I?', '24', 1);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes_story3`
--

CREATE TABLE `quizzes_story3` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `points` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `story1_taken_quizzes`
--

CREATE TABLE `story1_taken_quizzes` (
  `taken_quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `store` int(11) NOT NULL CHECK (`store` in (1,2,3)),
  `attempt` int(11) NOT NULL CHECK (`attempt` in (1,2,3)),
  `item_number` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `choices` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`choices`)),
  `is_correct` tinyint(1) NOT NULL,
  `points` int(11) NOT NULL,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `quiz_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `story1_taken_quizzes`
--

INSERT INTO `story1_taken_quizzes` (`taken_quiz_id`, `student_id`, `store`, `attempt`, `item_number`, `question`, `choices`, `is_correct`, `points`, `is_final`, `quiz_id`) VALUES
(1, 1, 1, 1, 1, 'Which planet is closest to the Sun?', '[{\"id\":\"12\",\"text\":\"Venus \\ud83e\\ude90\",\"is_correct\":false,\"label\":\"A\"},{\"id\":\"10\",\"text\":\"Mars \\ud83d\\udd34\",\"is_correct\":false,\"label\":\"B\"},{\"id\":\"9\",\"text\":\" Earth \\ud83c\\udf0d\",\"is_correct\":false,\"label\":\"C\"},{\"id\":\"11\",\"text\":\"Mercury \\u2600\\ufe0f\",\"is_correct\":true,\"label\":\"D\"}]', 0, 2, 1, 3),
(2, 1, 1, 1, 2, 'What is the name of the fairy in Peter Pan?', '[{\"id\":\"13\",\"text\":\"Tinker Bell \\ud83e\\uddda\\u200d\\u2640\\ufe0f\",\"is_correct\":true,\"label\":\"A\"},{\"id\":\"15\",\"text\":\"Elsa \\u2744\\ufe0f\",\"is_correct\":false,\"label\":\"B\"},{\"id\":\"16\",\"text\":\"Ariel \\ud83e\\udddc\\u200d\\u2640\\ufe0f\",\"is_correct\":false,\"label\":\"C\"},{\"id\":\"14\",\"text\":\"Cinderella \\ud83d\\udc78\",\"is_correct\":false,\"label\":\"D\"}]', 1, 1, 1, 0),
(3, 1, 1, 1, 3, 'What do caterpillars turn into?', '[{\"id\":\"20\",\"text\":\"Bees \\ud83d\\udc1d\",\"is_correct\":false,\"label\":\"A\"},{\"id\":\"18\",\"text\":\"Butterflies \\ud83e\\udd8b\",\"is_correct\":true,\"label\":\"B\"},{\"id\":\"17\",\"text\":\"Frogs \\ud83d\\udc38\",\"is_correct\":false,\"label\":\"C\"},{\"id\":\"19\",\"text\":\"Birds \\ud83d\\udc26\",\"is_correct\":false,\"label\":\"D\"}]', 0, 2, 1, 0),
(4, 1, 1, 1, 4, 'What is 2 + 2?', '[{\"id\":\"24\",\"text\":\"4\",\"is_correct\":true,\"label\":\"A\"},{\"id\":\"23\",\"text\":\"3\",\"is_correct\":false,\"label\":\"B\"},{\"id\":\"22\",\"text\":\"2\",\"is_correct\":false,\"label\":\"C\"}]', 1, 1, 1, 6),
(13, 1, 2, 1, 1, 'Is this store 2?', '[{\"id\":\"9\",\"text\":\"No\",\"is_correct\":false},{\"id\":\"8\",\"text\":\"Yes\",\"is_correct\":true}]', 0, 1, 0, 0),
(19, 1, 2, 2, 1, 'Is this store 2?', '[{\"id\":\"8\",\"text\":\"Yes\",\"is_correct\":true},{\"id\":\"9\",\"text\":\"No\",\"is_correct\":false}]', 0, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `story2_taken_quizzes`
--

CREATE TABLE `story2_taken_quizzes` (
  `taken_quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attempt` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `is_final` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `story2_taken_quizzes`
--

INSERT INTO `story2_taken_quizzes` (`taken_quiz_id`, `student_id`, `attempt`, `question`, `answer`, `is_correct`, `is_final`) VALUES
(259, 1, 1, 'How old am I?', '24', 1, 1),
(260, 1, 1, 'Is this story 2', 'Nal', 0, 1),
(261, 1, 1, 'Who am I?', 'Yes', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `students_table`
--

CREATE TABLE `students_table` (
  `pk_studentID` int(11) NOT NULL,
  `idNo` varchar(50) NOT NULL,
  `studentName` varchar(255) NOT NULL,
  `studentPass` varchar(255) NOT NULL,
  `studentGender` enum('Male','Female') NOT NULL,
  `studentLevel` int(11) NOT NULL,
  `studentRibbon` int(11) DEFAULT NULL,
  `studentColtrash` int(11) DEFAULT NULL,
  `studentProgress` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students_table`
--

INSERT INTO `students_table` (`pk_studentID`, `idNo`, `studentName`, `studentPass`, `studentGender`, `studentLevel`, `studentRibbon`, `studentColtrash`, `studentProgress`) VALUES
(1, 'S001', 'Alice Brown', 'password123', 'Male', 2, 13, 288, 80),
(2, 'S002', 'Bob Smith', 'password123', 'Male', 0, 5, 0, 90),
(3, 'S003', 'Charlie Johnson', 'password123', 'Male', 3, 2, 3, 70),
(4, 'S004', 'Daisy Williams', 'password123', 'Female', 1, 4, 2, 85),
(5, 'S005', 'Ethan Martinez', 'password123', 'Male', 2, 3, 4, 75),
(6, 'S006', 'Fiona Anderson', 'password123', 'Female', 3, 5, 1, 95),
(7, 'S007', 'George Thomas', 'password123', 'Male', 1, 2, 2, 60),
(8, 'S008', 'Hannah Taylor', 'password123', 'Female', 2, 4, 3, 88),
(9, 'S009', 'Ian Hernandez', 'password123', 'Male', 3, 3, 1, 78),
(10, 'S010', 'Jasmine Moore', 'password123', 'Female', 1, 5, 4, 92),
(11, 'S011', 'Kevin White', 'password123', 'Male', 2, 2, 3, 76),
(12, 'S012', 'Laura Garcia', 'password123', 'Female', 3, 4, 2, 89),
(13, 'S013', 'Michael Rodriguez', 'password123', 'Male', 1, 3, 4, 83),
(14, 'S014', 'Nancy Davis', 'password123', 'Female', 2, 5, 2, 94),
(15, 'S015', 'Oscar Lee', 'password123', 'Male', 3, 4, 1, 77),
(16, '1209', 'Lamborghini', '1209', 'Male', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_table`
--

CREATE TABLE `teacher_table` (
  `pk_teacherID` int(11) NOT NULL,
  `idNo` varchar(50) NOT NULL,
  `teacherName` varchar(255) NOT NULL,
  `teacherEmail` varchar(255) NOT NULL,
  `teacherPass` varchar(255) NOT NULL,
  `teacherGender` enum('Male','Female','Other') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_table`
--

INSERT INTO `teacher_table` (`pk_teacherID`, `idNo`, `teacherName`, `teacherEmail`, `teacherPass`, `teacherGender`) VALUES
(1, 'T001', 'Nal Sempai', 'nalnal@example.com', 'password123', 'Male'),
(2, 'T002', 'Jane Smith', 'janesmith@example.com', 'password123', 'Female'),
(3, 'T003', 'Michael Brown', 'michaelbrown@example.com', 'password123', 'Male'),
(4, 'T004', 'Emma Johnson', 'emmajohnson@example.com', 'password123', 'Female'),
(5, 'T005', 'Liam Williams', 'liamwilliams@example.com', 'password123', 'Male'),
(6, 'T006', 'Olivia Martinez', 'oliviamartinez@example.com', 'password123', 'Female'),
(7, 'T007', 'Noah Anderson', 'noahanderson@example.com', 'password123', 'Male'),
(8, 'T008', 'Sophia Thomas', 'sophiathomas@example.com', 'password123', 'Female'),
(9, 'T009', 'James Taylor', 'jamestaylor@example.com', 'password123', 'Male'),
(10, 'T010', 'Mia Hernandez', 'miahernandez@example.com', 'password123', 'Female'),
(11, 'T011', 'Benjamin Moore', 'benjaminmoore@example.com', 'password123', 'Male'),
(12, 'T012', 'Charlotte White', 'charlottewhite@example.com', 'password123', 'Female'),
(13, 'T013', 'Lucas Garcia', 'lucasgarcia@example.com', 'password123', 'Male'),
(14, 'T014', 'Amelia Rodriguez', 'ameliarodriguez@example.com', 'password123', 'Female'),
(15, 'T015', 'Elijah Davis', 'elijahdavis@example.com', 'password123', 'Male');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_table`
--
ALTER TABLE `attendance_table`
  ADD PRIMARY KEY (`pk_attendanceID`),
  ADD KEY `fk_studentID` (`fk_studentID`);

--
-- Indexes for table `choices_store2`
--
ALTER TABLE `choices_store2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `choices_store3`
--
ALTER TABLE `choices_store3`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `choices_story1`
--
ALTER TABLE `choices_story1`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quiz1_table`
--
ALTER TABLE `quiz1_table`
  ADD PRIMARY KEY (`pk_quiz1ID`),
  ADD KEY `fk_studentID` (`fk_studentID`),
  ADD KEY `fk_storyQuiz1ID` (`fk_storyQuiz1ID`);

--
-- Indexes for table `quiz2_table`
--
ALTER TABLE `quiz2_table`
  ADD PRIMARY KEY (`pk_quiz2ID`),
  ADD KEY `fk_studentID` (`fk_studentID`),
  ADD KEY `fk_storyQuiz2ID` (`fk_storyQuiz2ID`);

--
-- Indexes for table `quiz3_table`
--
ALTER TABLE `quiz3_table`
  ADD PRIMARY KEY (`pk_quiz3ID`),
  ADD KEY `fk_studentID` (`fk_studentID`),
  ADD KEY `fk_storyQuiz3ID` (`fk_storyQuiz3ID`);

--
-- Indexes for table `quizzes_store2`
--
ALTER TABLE `quizzes_store2`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quizzes_store3`
--
ALTER TABLE `quizzes_store3`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quizzes_story1`
--
ALTER TABLE `quizzes_story1`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quizzes_story2`
--
ALTER TABLE `quizzes_story2`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quizzes_story3`
--
ALTER TABLE `quizzes_story3`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `story1_taken_quizzes`
--
ALTER TABLE `story1_taken_quizzes`
  ADD PRIMARY KEY (`taken_quiz_id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`store`,`attempt`,`item_number`);

--
-- Indexes for table `story2_taken_quizzes`
--
ALTER TABLE `story2_taken_quizzes`
  ADD PRIMARY KEY (`taken_quiz_id`);

--
-- Indexes for table `students_table`
--
ALTER TABLE `students_table`
  ADD PRIMARY KEY (`pk_studentID`),
  ADD UNIQUE KEY `idNo` (`idNo`);

--
-- Indexes for table `teacher_table`
--
ALTER TABLE `teacher_table`
  ADD PRIMARY KEY (`pk_teacherID`),
  ADD UNIQUE KEY `idNo` (`idNo`),
  ADD UNIQUE KEY `teacherEmail` (`teacherEmail`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_table`
--
ALTER TABLE `attendance_table`
  MODIFY `pk_attendanceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `choices_store2`
--
ALTER TABLE `choices_store2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `choices_store3`
--
ALTER TABLE `choices_store3`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `choices_story1`
--
ALTER TABLE `choices_story1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `quiz1_table`
--
ALTER TABLE `quiz1_table`
  MODIFY `pk_quiz1ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quiz2_table`
--
ALTER TABLE `quiz2_table`
  MODIFY `pk_quiz2ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quiz3_table`
--
ALTER TABLE `quiz3_table`
  MODIFY `pk_quiz3ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quizzes_store2`
--
ALTER TABLE `quizzes_store2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quizzes_store3`
--
ALTER TABLE `quizzes_store3`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `quizzes_story1`
--
ALTER TABLE `quizzes_story1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `quizzes_story2`
--
ALTER TABLE `quizzes_story2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quizzes_story3`
--
ALTER TABLE `quizzes_story3`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `story1_taken_quizzes`
--
ALTER TABLE `story1_taken_quizzes`
  MODIFY `taken_quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `story2_taken_quizzes`
--
ALTER TABLE `story2_taken_quizzes`
  MODIFY `taken_quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=262;

--
-- AUTO_INCREMENT for table `students_table`
--
ALTER TABLE `students_table`
  MODIFY `pk_studentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `teacher_table`
--
ALTER TABLE `teacher_table`
  MODIFY `pk_teacherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_table`
--
ALTER TABLE `attendance_table`
  ADD CONSTRAINT `attendance_table_ibfk_1` FOREIGN KEY (`fk_studentID`) REFERENCES `students_table` (`pk_studentID`) ON DELETE CASCADE;

--
-- Constraints for table `choices_store2`
--
ALTER TABLE `choices_store2`
  ADD CONSTRAINT `choices_store2_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes_store2` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `choices_store3`
--
ALTER TABLE `choices_store3`
  ADD CONSTRAINT `choices_store3_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes_store3` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `choices_story1`
--
ALTER TABLE `choices_story1`
  ADD CONSTRAINT `choices_story1_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes_story1` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz1_table`
--
ALTER TABLE `quiz1_table`
  ADD CONSTRAINT `quiz1_table_ibfk_1` FOREIGN KEY (`fk_studentID`) REFERENCES `students_table` (`pk_studentID`) ON DELETE CASCADE;

--
-- Constraints for table `quiz2_table`
--
ALTER TABLE `quiz2_table`
  ADD CONSTRAINT `quiz2_table_ibfk_1` FOREIGN KEY (`fk_studentID`) REFERENCES `students_table` (`pk_studentID`) ON DELETE CASCADE;

--
-- Constraints for table `quiz3_table`
--
ALTER TABLE `quiz3_table`
  ADD CONSTRAINT `quiz3_table_ibfk_1` FOREIGN KEY (`fk_studentID`) REFERENCES `students_table` (`pk_studentID`) ON DELETE CASCADE;

--
-- Constraints for table `story1_taken_quizzes`
--
ALTER TABLE `story1_taken_quizzes`
  ADD CONSTRAINT `story1_taken_quizzes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students_table` (`pk_studentID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
