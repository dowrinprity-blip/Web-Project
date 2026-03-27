-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 12:48 PM
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
-- Database: `student_course_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminaccounts`
--

CREATE TABLE `adminaccounts` (
  `AdminID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Role` enum('super_admin','admin') DEFAULT 'admin',
  `Email` varchar(100) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `LastLogin` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adminaccounts`
--

INSERT INTO `adminaccounts` (`AdminID`, `Username`, `PasswordHash`, `Role`, `Email`, `IsActive`, `CreatedAt`, `LastLogin`) VALUES
(1, 'admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', NULL, 1, '2026-03-26 16:06:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `interestedstudents`
--

CREATE TABLE `interestedstudents` (
  `InterestID` int(11) NOT NULL,
  `ProgrammeID` int(11) NOT NULL,
  `StudentName` varchar(100) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `RegisteredAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interestedstudents`
--

INSERT INTO `interestedstudents` (`InterestID`, `ProgrammeID`, `StudentName`, `Email`, `RegisteredAt`) VALUES
(1, 1, 'John Doe', 'john.doe@example.com', '2026-03-25 13:53:04'),
(2, 4, 'Jane Smith', 'jane.smith@example.com', '2026-03-25 13:53:04'),
(3, 6, 'Alex Brown', 'alex.brown@example.com', '2026-03-25 13:53:04'),
(4, 9, 'Priya Patel', 'priya.patel@example.com', '2026-03-25 13:53:04'),
(5, 3, 'Dikshya kafle', 'deekshyakafley639@gmail.com', '2026-03-25 17:13:07'),
(6, 5, 'Srijana Kafley', 'srijana987@gmail.com', '2026-03-26 12:52:01');

-- --------------------------------------------------------

--
-- Table structure for table `levels`
--

CREATE TABLE `levels` (
  `LevelID` int(11) NOT NULL,
  `LevelName` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `levels`
--

INSERT INTO `levels` (`LevelID`, `LevelName`) VALUES
(0, 'Postgraduate'),
(1, 'Undergraduate'),
(2, 'Postgraduate');

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `ModuleID` int(11) NOT NULL,
  `ModuleName` text NOT NULL,
  `ModuleLeaderID` int(11) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Image` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`ModuleID`, `ModuleName`, `ModuleLeaderID`, `Description`, `Image`) VALUES
(1, 'Introduction to Programming', 1, 'Covers the fundamentals of programming using Python and Java.', NULL),
(2, 'Mathematics for Computer Science', 2, 'Teaches discrete mathematics, linear algebra, and probability theory.', NULL),
(3, 'Computer Systems & Architecture', 3, 'Explores CPU design, memory management, and assembly language.', NULL),
(4, 'Databases', 4, 'Covers SQL, relational database design, and NoSQL systems.', NULL),
(5, 'Software Engineering', 5, 'Focuses on agile development, design patterns, and project management.', NULL),
(6, 'Algorithms & Data Structures', 6, 'Examines sorting, searching, graphs, and complexity analysis.', NULL),
(7, 'Cyber Security Fundamentals', 7, 'Provides an introduction to network security, cryptography, and vulnerabilities.', NULL),
(8, 'Artificial Intelligence', 8, 'Introduces AI concepts such as neural networks, expert systems, and robotics.', NULL),
(9, 'Machine Learning', 9, 'Explores supervised and unsupervised learning, including decision trees and clustering.', NULL),
(10, 'Ethical Hacking', 10, 'Covers penetration testing, security assessments, and cybersecurity laws.', NULL),
(11, 'Computer Networks', 1, 'Teaches TCP/IP, network layers, and wireless communication.', NULL),
(12, 'Software Testing & Quality Assurance', 2, 'Focuses on automated testing, debugging, and code reliability.', NULL),
(13, 'Embedded Systems', 3, 'Examines microcontrollers, real-time OS, and IoT applications.', NULL),
(14, 'Human-Computer Interaction', 4, 'Studies UI/UX design, usability testing, and accessibility.', NULL),
(15, 'Blockchain Technologies', 5, 'Covers distributed ledgers, consensus mechanisms, and smart contracts.', NULL),
(16, 'Cloud Computing', 6, 'Introduces cloud services, virtualization, and distributed systems.', NULL),
(17, 'Digital Forensics', 7, 'Teaches forensic investigation techniques for cybercrime.', NULL),
(18, 'Final Year Project', 8, 'A major independent project where students develop a software solution.', NULL),
(19, 'Advanced Machine Learning', 11, 'Covers deep learning, reinforcement learning, and cutting-edge AI techniques.', NULL),
(20, 'Cyber Threat Intelligence', 12, 'Focuses on cybersecurity risk analysis, malware detection, and threat mitigation.', NULL),
(21, 'Big Data Analytics', 13, 'Explores data mining, distributed computing, and AI-driven insights.', NULL),
(22, 'Cloud & Edge Computing', 14, 'Examines scalable cloud platforms, serverless computing, and edge networks.', NULL),
(23, 'Blockchain & Cryptography', 15, 'Covers decentralized applications, consensus algorithms, and security measures.', NULL),
(24, 'AI Ethics & Society', 16, 'Analyzes ethical dilemmas in AI, fairness, bias, and regulatory considerations.', NULL),
(25, 'Quantum Computing', 17, 'Introduces quantum algorithms, qubits, and cryptographic applications.', NULL),
(26, 'Cybersecurity Law & Policy', 18, 'Explores digital privacy, GDPR, and international cyber law.', NULL),
(27, 'Neural Networks & Deep Learning', 19, 'Delves into convolutional networks, GANs, and AI advancements.', NULL),
(28, 'Human-AI Interaction', 20, 'Studies AI usability, NLP systems, and social robotics.', NULL),
(29, 'Autonomous Systems', 11, 'Focuses on self-driving technology, robotics, and intelligent agents.', NULL),
(30, 'Digital Forensics & Incident Response', 12, 'Teaches forensic analysis, evidence gathering, and threat mitigation.', NULL),
(31, 'Postgraduate Dissertation', 13, 'A major research project where students explore advanced topics in computing.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `programmemodules`
--

CREATE TABLE `programmemodules` (
  `ProgrammeModuleID` int(11) NOT NULL,
  `ProgrammeID` int(11) DEFAULT NULL,
  `ModuleID` int(11) DEFAULT NULL,
  `Year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programmemodules`
--

INSERT INTO `programmemodules` (`ProgrammeModuleID`, `ProgrammeID`, `ModuleID`, `Year`) VALUES
(1, 1, 1, 1),
(2, 1, 2, 1),
(3, 1, 3, 1),
(4, 1, 4, 1),
(5, 2, 1, 1),
(6, 2, 2, 1),
(7, 2, 3, 1),
(8, 2, 4, 1),
(9, 3, 1, 1),
(10, 3, 2, 1),
(11, 3, 3, 1),
(12, 3, 4, 1),
(13, 4, 1, 1),
(14, 4, 2, 1),
(15, 4, 3, 1),
(16, 4, 4, 1),
(17, 5, 1, 1),
(18, 5, 2, 1),
(19, 5, 3, 1),
(20, 5, 4, 1),
(21, 1, 5, 2),
(22, 1, 6, 2),
(23, 1, 7, 2),
(24, 1, 8, 2),
(25, 2, 5, 2),
(26, 2, 6, 2),
(27, 2, 12, 2),
(28, 2, 14, 2),
(29, 3, 5, 2),
(30, 3, 9, 2),
(31, 3, 8, 2),
(32, 3, 10, 2),
(33, 4, 7, 2),
(34, 4, 10, 2),
(35, 4, 11, 2),
(36, 4, 17, 2),
(37, 5, 5, 2),
(38, 5, 6, 2),
(39, 5, 9, 2),
(40, 5, 16, 2),
(41, 1, 11, 3),
(42, 1, 13, 3),
(43, 1, 15, 3),
(44, 1, 18, 3),
(45, 2, 13, 3),
(46, 2, 15, 3),
(47, 2, 16, 3),
(48, 2, 18, 3),
(49, 3, 13, 3),
(50, 3, 15, 3),
(51, 3, 16, 3),
(52, 3, 18, 3),
(53, 4, 15, 3),
(54, 4, 16, 3),
(55, 4, 17, 3),
(56, 4, 18, 3),
(57, 5, 9, 3),
(58, 5, 14, 3),
(59, 5, 16, 3),
(60, 5, 18, 3),
(61, 6, 19, 1),
(62, 6, 24, 1),
(63, 6, 27, 1),
(64, 6, 29, 1),
(65, 6, 31, 1),
(66, 7, 20, 1),
(67, 7, 26, 1),
(68, 7, 30, 1),
(69, 7, 23, 1),
(70, 7, 31, 1),
(71, 8, 21, 1),
(72, 8, 22, 1),
(73, 8, 27, 1),
(74, 8, 28, 1),
(75, 8, 31, 1),
(76, 9, 19, 1),
(77, 9, 24, 1),
(78, 9, 28, 1),
(79, 9, 29, 1),
(80, 9, 31, 1),
(81, 10, 23, 1),
(82, 10, 22, 1),
(83, 10, 25, 1),
(84, 10, 26, 1),
(85, 10, 31, 1);

-- --------------------------------------------------------

--
-- Table structure for table `programmes`
--

CREATE TABLE `programmes` (
  `ProgrammeID` int(11) NOT NULL,
  `ProgrammeName` text NOT NULL,
  `LevelID` int(11) DEFAULT NULL,
  `ProgrammeLeaderID` int(11) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Image` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programmes`
--

INSERT INTO `programmes` (`ProgrammeID`, `ProgrammeName`, `LevelID`, `ProgrammeLeaderID`, `Description`, `Image`) VALUES
(1, 'BSc Computer Science', 1, 1, 'A broad computer science degree covering programming, AI, cybersecurity, and software engineering.', 'uploads/programme_images/cs.jpg'),
(2, 'BSc Software Engineering', 1, 2, 'A specialized degree focusing on the development and lifecycle of software applications.', 'uploads/programme_images/se.jpg'),
(3, 'BSc Artificial Intelligence', 1, 3, 'Focuses on machine learning, deep learning, and AI applications.', 'uploads/programme_images/ai.jpg'),
(4, 'BSc Cyber Security', 1, 4, 'Explores network security, ethical hacking, and digital forensics.', 'uploads/programme_images/cyber.jpg'),
(5, 'BSc Data Science', 1, 5, 'Covers big data, machine learning, and statistical computing.', 'uploads/programme_images/ds.jpg'),
(6, 'MSc Machine Learning', 2, 11, 'A postgraduate degree focusing on deep learning, AI ethics, and neural networks.', 'uploads/programme_images/ml.jpg'),
(7, 'MSc Cyber Security', 2, 12, 'A specialized programme covering digital forensics, cyber threat intelligence, and security policy.', 'uploads/programme_images/mcs.jpg'),
(8, 'MSc Data Science', 2, 13, 'Focuses on big data analytics, cloud computing, and AI-driven insights.', 'uploads/programme_images/mds.jpg'),
(9, 'MSc Artificial Intelligence', 2, 14, 'Explores autonomous systems, AI ethics, and deep learning technologies.', 'uploads/programme_images/mai.jpg'),
(10, 'MSc Software Engineering', 2, 15, 'Emphasizes software design, blockchain applications, and cutting-edge methodologies.', 'uploads/programme_images/mse.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `StaffID` int(11) NOT NULL,
  `Name` text NOT NULL,
  `Photo` varchar(255) DEFAULT NULL,
  `Bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`StaffID`, `Name`, `Photo`, `Bio`) VALUES
(1, 'Dr. Alice Johnson', 'alice johnson.jpg', NULL),
(2, 'Dr. Brian Lee', 'brian lee.jpg', NULL),
(3, 'Dr. Carol White', 'carol white.jpg', NULL),
(4, 'Dr. David Green', 'david green.jpg', NULL),
(5, 'Dr. Emma Scott', 'emma scott.jpg', NULL),
(6, 'Dr. Frank Moore', 'frank moore.jpg', NULL),
(7, 'Dr. Grace Adams', 'grace adams.jpg', NULL),
(8, 'Dr. Henry Clark', 'henry clark.jpg', NULL),
(9, 'Dr. Irene Hall', 'irene hall.jpg', NULL),
(10, 'Dr. James Wright', 'james wright.jpg', NULL),
(11, 'Dr. Sophia Miller', 'sophia miller.jpg', NULL),
(12, 'Dr. Benjamin Carter', 'benjamin carter.jpg', NULL),
(13, 'Dr. Chloe Thompson', 'chloe thompson.jpg', NULL),
(14, 'Dr. Daniel Robinson', 'daniel robinson.jpg', NULL),
(15, 'Dr. Emily Davis', 'emily davis.jpg', NULL),
(16, 'Dr. Nathan Hughes', 'nathan hughes.jpg', NULL),
(17, 'Dr. Olivia Martin', 'olivia martin.jpg', NULL),
(18, 'Dr. Samuel Anderson', 'samual anderson.jpg', NULL),
(19, 'Dr. Victoria Hall', 'victoria hall.jpg', NULL),
(20, 'Dr. William Scott', 'william scott.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staffaccounts`
--

CREATE TABLE `staffaccounts` (
  `AccountID` int(11) NOT NULL,
  `StaffID` int(11) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Bio` text DEFAULT NULL,
  `PhotoPath` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staffaccounts`
--

INSERT INTO `staffaccounts` (`AccountID`, `StaffID`, `Username`, `PasswordHash`, `Bio`, `PhotoPath`, `CreatedAt`) VALUES
(1, 1, 'alice.johnson', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, 'alice johnson.jpg', '2026-03-25 14:39:39'),
(2, 2, 'brian.lee', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(3, 3, 'carol.white', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(4, 4, 'david.green', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(5, 5, 'emma.scott', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(6, 6, 'frank.moore', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(7, 7, 'grace.adams', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(8, 8, 'henry.clark', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(9, 9, 'irene.hall', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(10, 10, 'james.wright', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(11, 11, 'sophia.miller', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(12, 12, 'benjamin.carter', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(13, 13, 'chloe.thompson', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(14, 14, 'daniel.robinson', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(15, 15, 'emily.davis', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(16, 16, 'nathan.hughes', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(17, 17, 'olivia.martin', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(18, 18, 'samuel.anderson', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(19, 19, 'victoria.hall', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39'),
(20, 20, 'william.scott', '$2y$10$n0NgHJ/i7nogFYyrA58wW.SiYikU5cHm0t2IIxQiW6S8Gs6dT/RAi', NULL, NULL, '2026-03-25 14:39:39');

-- --------------------------------------------------------

--
-- Table structure for table `staffchangerequests`
--

CREATE TABLE `staffchangerequests` (
  `RequestID` int(11) NOT NULL,
  `StaffID` int(11) NOT NULL,
  `RequestType` enum('profile','module') NOT NULL,
  `NewName` varchar(255) DEFAULT NULL,
  `NewBio` text DEFAULT NULL,
  `NewPhotoPath` varchar(255) DEFAULT NULL,
  `ModuleID` int(11) DEFAULT NULL,
  `NewModuleName` varchar(255) DEFAULT NULL,
  `NewModuleDesc` text DEFAULT NULL,
  `Status` enum('pending','approved','rejected') DEFAULT 'pending',
  `AdminNote` text DEFAULT NULL,
  `SubmittedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ReviewedAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `studentaccounts`
--

CREATE TABLE `studentaccounts` (
  `AccountID` int(11) NOT NULL,
  `FullName` varchar(100) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `StudentType` enum('interested','enrolled') DEFAULT 'interested',
  `CourseInfo` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `EnrollmentStatus` enum('pending','enrolled','graduated','withdrawn') DEFAULT 'pending',
  `EnrollmentDate` date DEFAULT NULL,
  `StudentID` varchar(20) DEFAULT NULL,
  `Photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studentaccounts`
--

INSERT INTO `studentaccounts` (`AccountID`, `FullName`, `Email`, `Phone`, `Address`, `DateOfBirth`, `PasswordHash`, `StudentType`, `CourseInfo`, `CreatedAt`, `EnrollmentStatus`, `EnrollmentDate`, `StudentID`, `Photo`) VALUES
(1, 'John Smith', 'john.smith@student.com', '+44 7712 345678', '123 Main Street, London', '2000-05-15', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enrolled', NULL, '2026-03-26 19:51:42', 'enrolled', NULL, NULL, 'johnsmith.jpg'),
(2, 'Sarah Johnson', 'sarah.johnson@student.com', '+44 7722 456789', '45 Park Avenue, Manchester', '2001-08-22', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enrolled', NULL, '2026-03-26 19:51:42', 'enrolled', NULL, NULL, 'sarahjohnson.jpg'),
(3, 'Michael Brown', 'michael.brown@student.com', '+44 7733 567890', '78 Queen Street, Birmingham', '2002-03-10', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'interested', NULL, '2026-03-26 19:51:42', 'pending', NULL, NULL, 'michaelbrown.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `studentattendance`
--

CREATE TABLE `studentattendance` (
  `AttendanceID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `ModuleID` int(11) DEFAULT NULL,
  `AttendanceDate` date DEFAULT NULL,
  `Status` enum('present','absent','late','excused') DEFAULT 'present',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `studentenrollment`
--

CREATE TABLE `studentenrollment` (
  `EnrollmentID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `ProgrammeID` int(11) DEFAULT NULL,
  `EnrollmentDate` date DEFAULT NULL,
  `EnrollmentStatus` enum('active','graduated','withdrawn','suspended') DEFAULT 'active',
  `StartYear` int(11) DEFAULT NULL,
  `ExpectedGraduationYear` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studentenrollment`
--

INSERT INTO `studentenrollment` (`EnrollmentID`, `StudentID`, `ProgrammeID`, `EnrollmentDate`, `EnrollmentStatus`, `StartYear`, `ExpectedGraduationYear`, `CreatedAt`) VALUES
(1, 1, 1, '2026-03-26', 'active', 2024, 2027, '2026-03-26 19:52:07'),
(2, 2, 3, '2026-03-26', 'active', 2024, 2026, '2026-03-26 19:52:07');

-- --------------------------------------------------------

--
-- Table structure for table `studentgrades`
--

CREATE TABLE `studentgrades` (
  `GradeID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `ModuleID` int(11) DEFAULT NULL,
  `ProgrammeID` int(11) DEFAULT NULL,
  `Grade` varchar(2) DEFAULT NULL,
  `Credits` int(11) DEFAULT 20,
  `AcademicYear` int(11) DEFAULT NULL,
  `Semester` enum('1','2','full') DEFAULT 'full',
  `GradingDate` date DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studentgrades`
--

INSERT INTO `studentgrades` (`GradeID`, `StudentID`, `ModuleID`, `ProgrammeID`, `Grade`, `Credits`, `AcademicYear`, `Semester`, `GradingDate`, `CreatedAt`) VALUES
(1, 1, 1, NULL, 'A', 20, 2024, 'full', NULL, '2026-03-26 19:52:26'),
(2, 1, 2, NULL, 'B+', 20, 2024, 'full', NULL, '2026-03-26 19:52:26'),
(3, 1, 3, NULL, 'A-', 20, 2024, 'full', NULL, '2026-03-26 19:52:26'),
(4, 2, 2, NULL, 'A-', 20, 2024, 'full', NULL, '2026-03-26 19:52:26'),
(5, 2, 3, NULL, 'A', 20, 2024, 'full', NULL, '2026-03-26 19:52:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adminaccounts`
--
ALTER TABLE `adminaccounts`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `interestedstudents`
--
ALTER TABLE `interestedstudents`
  ADD PRIMARY KEY (`InterestID`),
  ADD KEY `ProgrammeID` (`ProgrammeID`);

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`LevelID`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`ModuleID`),
  ADD KEY `ModuleLeaderID` (`ModuleLeaderID`);

--
-- Indexes for table `programmemodules`
--
ALTER TABLE `programmemodules`
  ADD PRIMARY KEY (`ProgrammeModuleID`),
  ADD KEY `ProgrammeID` (`ProgrammeID`),
  ADD KEY `ModuleID` (`ModuleID`);

--
-- Indexes for table `programmes`
--
ALTER TABLE `programmes`
  ADD PRIMARY KEY (`ProgrammeID`),
  ADD KEY `LevelID` (`LevelID`),
  ADD KEY `ProgrammeLeaderID` (`ProgrammeLeaderID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`StaffID`);

--
-- Indexes for table `staffaccounts`
--
ALTER TABLE `staffaccounts`
  ADD PRIMARY KEY (`AccountID`),
  ADD UNIQUE KEY `StaffID` (`StaffID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `staffchangerequests`
--
ALTER TABLE `staffchangerequests`
  ADD PRIMARY KEY (`RequestID`),
  ADD KEY `StaffID` (`StaffID`),
  ADD KEY `ModuleID` (`ModuleID`);

--
-- Indexes for table `studentaccounts`
--
ALTER TABLE `studentaccounts`
  ADD PRIMARY KEY (`AccountID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `StudentID` (`StudentID`);

--
-- Indexes for table `studentattendance`
--
ALTER TABLE `studentattendance`
  ADD PRIMARY KEY (`AttendanceID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `ModuleID` (`ModuleID`);

--
-- Indexes for table `studentenrollment`
--
ALTER TABLE `studentenrollment`
  ADD PRIMARY KEY (`EnrollmentID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `ProgrammeID` (`ProgrammeID`);

--
-- Indexes for table `studentgrades`
--
ALTER TABLE `studentgrades`
  ADD PRIMARY KEY (`GradeID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `ModuleID` (`ModuleID`),
  ADD KEY `ProgrammeID` (`ProgrammeID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adminaccounts`
--
ALTER TABLE `adminaccounts`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `interestedstudents`
--
ALTER TABLE `interestedstudents`
  MODIFY `InterestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `programmemodules`
--
ALTER TABLE `programmemodules`
  MODIFY `ProgrammeModuleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `programmes`
--
ALTER TABLE `programmes`
  MODIFY `ProgrammeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `staffaccounts`
--
ALTER TABLE `staffaccounts`
  MODIFY `AccountID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `staffchangerequests`
--
ALTER TABLE `staffchangerequests`
  MODIFY `RequestID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `studentaccounts`
--
ALTER TABLE `studentaccounts`
  MODIFY `AccountID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `studentattendance`
--
ALTER TABLE `studentattendance`
  MODIFY `AttendanceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `studentenrollment`
--
ALTER TABLE `studentenrollment`
  MODIFY `EnrollmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `studentgrades`
--
ALTER TABLE `studentgrades`
  MODIFY `GradeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `interestedstudents`
--
ALTER TABLE `interestedstudents`
  ADD CONSTRAINT `interestedstudents_ibfk_1` FOREIGN KEY (`ProgrammeID`) REFERENCES `programmes` (`ProgrammeID`) ON DELETE CASCADE;

--
-- Constraints for table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`ModuleLeaderID`) REFERENCES `staff` (`StaffID`);

--
-- Constraints for table `programmemodules`
--
ALTER TABLE `programmemodules`
  ADD CONSTRAINT `programmemodules_ibfk_1` FOREIGN KEY (`ProgrammeID`) REFERENCES `programmes` (`ProgrammeID`),
  ADD CONSTRAINT `programmemodules_ibfk_2` FOREIGN KEY (`ModuleID`) REFERENCES `modules` (`ModuleID`);

--
-- Constraints for table `programmes`
--
ALTER TABLE `programmes`
  ADD CONSTRAINT `programmes_ibfk_1` FOREIGN KEY (`LevelID`) REFERENCES `levels` (`LevelID`),
  ADD CONSTRAINT `programmes_ibfk_2` FOREIGN KEY (`ProgrammeLeaderID`) REFERENCES `staff` (`StaffID`);

--
-- Constraints for table `staffaccounts`
--
ALTER TABLE `staffaccounts`
  ADD CONSTRAINT `staffaccounts_ibfk_1` FOREIGN KEY (`StaffID`) REFERENCES `staff` (`StaffID`) ON DELETE CASCADE;

--
-- Constraints for table `staffchangerequests`
--
ALTER TABLE `staffchangerequests`
  ADD CONSTRAINT `staffchangerequests_ibfk_1` FOREIGN KEY (`StaffID`) REFERENCES `staff` (`StaffID`) ON DELETE CASCADE,
  ADD CONSTRAINT `staffchangerequests_ibfk_2` FOREIGN KEY (`ModuleID`) REFERENCES `modules` (`ModuleID`) ON DELETE SET NULL;

--
-- Constraints for table `studentattendance`
--
ALTER TABLE `studentattendance`
  ADD CONSTRAINT `studentattendance_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `studentaccounts` (`AccountID`) ON DELETE CASCADE,
  ADD CONSTRAINT `studentattendance_ibfk_2` FOREIGN KEY (`ModuleID`) REFERENCES `modules` (`ModuleID`) ON DELETE CASCADE;

--
-- Constraints for table `studentenrollment`
--
ALTER TABLE `studentenrollment`
  ADD CONSTRAINT `studentenrollment_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `studentaccounts` (`AccountID`) ON DELETE CASCADE,
  ADD CONSTRAINT `studentenrollment_ibfk_2` FOREIGN KEY (`ProgrammeID`) REFERENCES `programmes` (`ProgrammeID`) ON DELETE CASCADE;

--
-- Constraints for table `studentgrades`
--
ALTER TABLE `studentgrades`
  ADD CONSTRAINT `studentgrades_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `studentaccounts` (`AccountID`) ON DELETE CASCADE,
  ADD CONSTRAINT `studentgrades_ibfk_2` FOREIGN KEY (`ModuleID`) REFERENCES `modules` (`ModuleID`) ON DELETE CASCADE,
  ADD CONSTRAINT `studentgrades_ibfk_3` FOREIGN KEY (`ProgrammeID`) REFERENCES `programmes` (`ProgrammeID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
