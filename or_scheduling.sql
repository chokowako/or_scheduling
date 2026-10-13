-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 13, 2026 at 02:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `or_scheduling`
--

-- --------------------------------------------------------

--
-- Table structure for table `anesthetics`
--

CREATE TABLE `anesthetics` (
  `anesthetic_id` int(11) NOT NULL,
  `anesthetic_name` varchar(100) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix_name` varchar(30) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `nick_name` varchar(100) DEFAULT NULL,
  `sex_gender` varchar(30) DEFAULT NULL,
  `service_class` varchar(100) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `last_name`, `email`, `first_name`, `middle_name`, `suffix_name`, `birth_date`, `nick_name`, `sex_gender`, `service_class`, `specialization`, `status`, `created_at`) VALUES
(1, 'A - EDDING', NULL, 'AMSURA', NULL, NULL, '1975-06-03', NULL, 'Female', 'Regular', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(2, 'A - FADREGUILAN', 'chokowako0818@gmail.com', 'ERDIE', 'CRUZ', NULL, '1970-10-31', NULL, 'Male', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(3, 'A - GALLENERO', NULL, 'NEIL ANGELO', 'CATALAN', NULL, '1978-07-13', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(4, 'A - GONZALES', NULL, 'SHERRY MAE', 'FAMADICO', NULL, '1983-07-27', NULL, 'Female', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(5, 'A - JULIAN', NULL, 'ROMEL', 'CRISTOBAL', NULL, '1971-05-17', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(6, 'A - OCAMPO', NULL, 'OSCAR', 'MARELLA', NULL, '1975-01-01', NULL, 'Male', 'Medicine', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(7, 'A - ONG-CORDOVEZ', NULL, 'MARY GERTRUDE', 'YONGCO', NULL, '2016-11-30', NULL, 'Female', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(8, 'A - PADUA', NULL, 'MARY RUTH', 'ALFONSO', NULL, '1978-11-04', NULL, 'Female', 'Surgery (Adult)', 'Orthopedic-Spine Surgery', 'Active', '2026-09-24 08:17:16'),
(9, 'A- HATAIE', NULL, 'JOHN', 'PANDUGA', NULL, '1975-09-12', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(10, 'A+ BADDIRI', NULL, 'NORODOM', 'DUGASAN', NULL, '1975-01-01', 'NOT ACTIVE', 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(11, 'A+ BUBULI', NULL, 'EMRAIDA', 'TANDIH', NULL, '1966-11-08', NULL, 'Female', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(12, 'A+ BURIGSAY', NULL, 'RICHARD', 'NAZARIO', NULL, '1975-01-01', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(13, 'A+CALUPITAN', NULL, 'ROBERTO JR.', 'VENIDA', NULL, '1961-10-16', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(14, 'AKALAL', NULL, 'FERDAUZI', 'IGASAN', NULL, '1971-04-29', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(15, 'ALAWADDIN', NULL, 'ANAM', 'INANI', NULL, '1973-09-14', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(16, 'ALUK', NULL, 'ROWINA', 'DIYA', NULL, '1970-01-02', NULL, 'Female', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(17, 'AMIL', NULL, 'WALID', 'AHMAD', NULL, '1971-02-23', NULL, 'Male', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(18, 'AMIN', NULL, 'CHARMAINE', 'ARMES', NULL, '1984-08-15', NULL, 'Female', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(19, 'AMINULLA', NULL, 'ZAIDA', 'KALIM', NULL, '1983-06-15', 'VACC COM', 'Female', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(20, 'ANACAN', NULL, 'IRENE', 'AQUINO', NULL, '1963-04-30', NULL, 'Female', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(21, 'ANACAN', NULL, 'JOHN HERNANI', 'VILLA', 'JR', '1963-09-29', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(22, 'ARADANI', NULL, 'FARSAUDI', 'ANCHETA', NULL, '1952-01-14', NULL, 'Female', 'Surgery (Adult)', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(23, 'ARADO', NULL, 'LOVELLA JOY', 'LABAYOG', NULL, '1976-07-17', NULL, 'Female', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(24, 'ARQUIZA', NULL, 'NOEL', 'TAN', NULL, '1963-03-17', 'VACC COM 2022', 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(25, 'BANTALA', NULL, 'KRISTINE', 'HASHIM', NULL, '1977-07-31', NULL, 'Female', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(26, 'BARCELONA', NULL, 'WILFRIDO JR.', 'MIRANDA', NULL, '1969-10-09', NULL, 'Male', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(27, 'BAZAN', NULL, 'JAYBEE', 'DAGALEA', NULL, '1977-02-28', 'VAC COM 2022', 'Male', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(28, 'BELLEZA', NULL, 'LINDA ANN', 'DIONISIO', NULL, '1956-01-22', NULL, 'Female', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(29, 'BUAC', NULL, 'PAVIO', 'LAGORA', NULL, '1966-06-16', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(30, 'CABANGAL', NULL, 'CHARLIE MAGNE', 'DJALIUL', NULL, '1978-06-16', NULL, 'Male', 'Medicine', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(31, 'CALUSCUSIN', NULL, 'IAN RAY', 'CANTOS', NULL, '1985-03-18', NULL, 'Male', 'Surgery (Adult)', 'Neuro Surgery', 'Active', '2026-09-24 08:17:16'),
(32, 'CALUSCUSIN', NULL, 'IGAN ROLLAN', 'CANTOS', NULL, '1988-11-24', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(33, 'CALUSCUSIN', NULL, 'BALTAZAR', 'PUNZALAN', NULL, '1951-03-29', NULL, 'Male', 'Regular', 'Anesthesiologists', 'Active', '2026-09-24 08:17:16'),
(34, 'CHIA', NULL, 'OSWALDO', 'SANTOS', NULL, '1947-08-05', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(35, 'CHIONG', NULL, 'LOWE', 'LEBOSADA', NULL, '1980-09-30', NULL, 'Male', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(36, 'CHUA', NULL, 'GARY', 'BALBUENA', NULL, '1968-11-04', NULL, 'Male', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(37, 'CUEVAS', NULL, 'JOSE RUFINO', 'TARGA', NULL, '1955-12-15', NULL, 'Male', 'Medicine', 'Neuro Surgery', 'Active', '2026-09-24 08:17:16'),
(38, 'CUEVAS', NULL, 'MA. CRISTINA', 'RAGUS', NULL, '1957-06-20', 'BOOSTER PFIZER', 'Female', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(39, 'DAPIT', NULL, 'YASMIN', 'COVARRUBIAS', NULL, '1975-04-11', NULL, 'Female', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(40, 'ELLONG', NULL, 'MOHD ZHAIN', 'ELIAS', NULL, '1977-07-06', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(41, 'ESPALDON JR', NULL, 'MANUEL', 'HO', NULL, '1983-05-26', NULL, 'Male', 'Orthopedic', 'Orthopedic-Spine Surgery', 'Active', '2026-09-24 08:17:16'),
(42, 'ESPLANA', NULL, 'CHRISTINE CARMELLE', 'MONTEMOR', NULL, '1982-02-23', NULL, 'Female', 'Surgery (Adult)', 'Thoracic Surgery', 'Active', '2026-09-24 08:17:16'),
(43, 'FELICIANO', NULL, 'ANTONIO', 'BUENAVENTURA', NULL, '1954-06-12', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(44, 'FERNANDEZ', NULL, 'JESSE', 'VILLANUEVA', NULL, '1971-08-20', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(45, 'GIMENA', NULL, 'GIOVANNI PAOLO', 'TANTILLER', NULL, '1975-06-29', NULL, 'Male', 'Medicine', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(46, 'GO', NULL, 'JAYSON', 'CHIONG', NULL, '1980-01-01', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(47, 'GO', NULL, 'KIMBERLY CAMILLE', 'TAN SANCHEZ', NULL, '1992-10-24', NULL, 'Female', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(48, 'HADJAIL-ISMAEL', NULL, 'NURHAYA', 'AMING', NULL, '1981-08-01', NULL, 'Female', 'Adult', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(49, 'HASAN', NULL, 'SAID', 'PUTONG', NULL, '2013-09-28', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(50, 'HO', 'boomzdummy@gmail.com', 'JEROME', 'ROYECA', NULL, '1979-07-18', NULL, 'Male', 'EENT', 'ENT and Head Neck Surgery', 'Active', '2026-09-24 08:17:16'),
(51, 'IGASAN', NULL, 'AHMED KHAYSER', 'BANGAHAN', NULL, '1970-05-29', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(52, 'ISRAEL-HASSIMAN', NULL, 'NURHADA', 'AMMAD', NULL, '1983-03-16', NULL, 'Female', 'EENT', 'ENT and Head Neck Surgery', 'Active', '2026-09-24 08:17:16'),
(53, 'ITEM', NULL, 'GLEN', 'ROUS', NULL, '1982-06-10', NULL, 'Female', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(54, 'JAMALUL', NULL, 'MARUNSAL', 'BURAN', NULL, '1975-08-08', NULL, 'Male', 'Surgery (Pediatrics)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(55, 'JAMIRI', NULL, 'FAUZIA', 'JAMAHARI', NULL, '1988-12-30', NULL, 'Female', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(56, 'JAMIRI', NULL, 'AL-RADJID', 'JAMAHARI', NULL, '1982-05-06', NULL, 'Male', 'Surgery (Adult)', 'Colorectal Surgery', 'Active', '2026-09-24 08:17:16'),
(57, 'KARIM', NULL, 'AMILBAHAR', 'JURIPAE', NULL, '1975-07-26', NULL, 'Male', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(58, 'KRISHA MAE', NULL, 'FLORES', 'BAJALA', NULL, '1990-05-24', NULL, 'Female', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(59, 'LAKIBUL- ALAMIA', NULL, 'EVELYN', 'MIJARES', NULL, '1978-08-25', NULL, 'Female', 'Obstetrics', 'Obstetrics and Gynaecology - High-risk pregnancy, Laparoscopic surgery', 'Active', '2026-09-24 08:17:16'),
(60, 'LEE', NULL, 'OLIVER', 'DIMAN', NULL, '1985-11-01', NULL, 'Female', 'Adult', 'Plastic surgery', 'Active', '2026-09-24 08:17:16'),
(61, 'LIGUTOM', NULL, 'GAUDENCIO III', 'TAN', NULL, '1968-11-24', NULL, 'Male', 'Surgery (Adult)', 'Neuro Surgery', 'Active', '2026-09-24 08:17:16'),
(62, 'LIM', NULL, 'ALEX', 'ANG', NULL, '1964-04-28', 'BOOSTER PFIZER', 'Male', 'Medicine', 'Orthopaedic Surgery', 'Active', '2026-09-24 08:17:16'),
(63, 'LIM', NULL, 'MARISSA', 'MARQUEZ', NULL, '1961-01-20', 'COMVACC', 'Female', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(64, 'LIM', NULL, 'WILSON JAMES', 'CHU', NULL, '1988-11-15', NULL, 'Male', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(65, 'LIMBAGA', NULL, 'MARTY', 'TANGCO', NULL, '1980-03-28', NULL, 'Male', 'Surgery (Adult)', 'Neuro Surgery', 'Active', '2026-09-24 08:17:16'),
(66, 'LLEMIT', NULL, 'KENNETH', 'ALMAGRO', NULL, '1987-09-06', NULL, 'Male', 'Orthopedic', 'Orthopaedic Surgery', 'Active', '2026-09-24 08:17:16'),
(67, 'LOONG', NULL, 'TITINA', 'FARAOWN', NULL, '1975-03-31', NULL, 'Female', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(68, 'LOPEZ', NULL, 'BENJAMIN', 'TOLOSA', 'JR.', '1958-04-09', NULL, 'Male', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(69, 'LOQUIAS', NULL, 'MARLON', 'CACERES', NULL, '1981-05-08', NULL, 'Male', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(70, 'LORENZO', NULL, 'ARMANDO', 'AMBIDA', 'JR', '1972-09-03', NULL, 'Male', 'Surgery (Adult)', 'ENT and Head Neck Surgery', 'Active', '2026-09-24 08:17:16'),
(71, 'LU', NULL, 'JAIME', 'KHO', NULL, '1958-01-11', NULL, 'Male', 'Medicine', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(72, 'LU', NULL, 'MA. GINA', 'GUERRERO', NULL, '1958-11-18', NULL, 'Female', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(73, 'LUBGUBAN', NULL, 'ELDONN', 'SACRISTAN', NULL, '1986-10-08', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(74, 'LUKMAN', NULL, 'SHERRYL', 'BUENA', NULL, '1975-08-14', NULL, 'Female', 'Adult', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(75, 'MAALI', NULL, 'YASHIER', 'PIAGO', NULL, '1974-02-17', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(76, 'MANDAI', NULL, 'WALDO', 'MALIK', NULL, '1975-09-24', NULL, 'Male', 'Medicine', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(77, 'MARTIN-SANTOS', NULL, 'FRANCES-GRACE', 'BARSATAN', NULL, '1989-06-26', NULL, 'Female', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(78, 'MENDIZABEL', NULL, 'DIVINE GRACE', 'BALOGO', NULL, '1978-03-26', NULL, 'Female', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(79, 'MONTUNO', NULL, 'ALFONSO', NULL, NULL, '1949-04-10', 'NOT ACTIVE', 'Male', 'Medicine', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(80, 'MORADOS', NULL, 'MARIA CRISTINA', 'NATIVIDAD', NULL, '1980-12-19', NULL, 'Female', 'Pediatrics', 'Pediatric Anesthesia', 'Active', '2026-09-24 08:17:16'),
(81, 'MUKARAM', NULL, 'BANISADAR', 'ADDALINO', NULL, '1981-06-23', NULL, 'Male', 'Adult', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(82, 'NATIVIDAD', NULL, 'OLIVER', 'MANUEL', NULL, '1973-12-05', NULL, 'Male', 'Medicine', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(83, 'NEBAB', NULL, 'ERWIN', 'GUZMAN', NULL, '1975-10-04', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(84, 'ONG', NULL, 'KENNETH MANN', 'WEE', NULL, '1987-01-24', NULL, 'Male', 'Orthopedic', 'Orthopaedic Surgery', 'Active', '2026-09-24 08:17:16'),
(85, 'PACHECO', NULL, 'LEAH', 'ESPALDON', NULL, '1978-11-26', 'VACC COM', 'Female', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(86, 'PEÑAFLOR', NULL, 'MONICCA MAE', 'QUE', NULL, '1980-12-30', NULL, 'Female', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(87, 'POBRE', NULL, 'ZOSIMO', 'VILOAN', NULL, '1954-12-08', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(88, 'RATAG', NULL, 'HENRY', 'LIMEN', NULL, '1967-08-25', NULL, 'Male', 'Medicine', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(89, 'RIVERA', NULL, 'RHODORA', 'LO', NULL, '1971-09-28', NULL, 'Female', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(90, 'ROJAS', NULL, 'LIZA LESLIE', 'IBAÑEZ', NULL, '1972-01-02', NULL, 'Female', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(91, 'ROMO', NULL, 'JENIVIC', 'YAO', NULL, '1981-03-13', NULL, 'Female', 'Surgery (Adult)', 'Laparoscopic Surgery', 'Active', '2026-09-24 08:17:16'),
(92, 'RUSTE', NULL, 'JULIET', 'DAMALERIO', NULL, '1955-09-05', NULL, 'Female', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(93, 'SAAVEDRA', NULL, 'JULIET', 'DALAYGON', NULL, '1957-07-13', NULL, 'Female', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(94, 'SANTOS', NULL, 'ADRIAN', 'HO', NULL, '1979-10-10', NULL, 'Male', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(95, 'SEBASTIAN', NULL, 'IAN ARTHUR', 'GALLENERO', NULL, '1971-02-07', NULL, 'Male', 'Medicine', 'Neuro Surgery', 'Active', '2026-09-24 08:17:16'),
(96, 'SESE', NULL, 'JOJI', 'MACROHON', NULL, '1973-02-04', NULL, 'Female', 'Surgery (Pediatrics)', 'Paediatric Surgery', 'Active', '2026-09-24 08:17:16'),
(97, 'SINGKEE', NULL, 'JULRATAG', 'HO', NULL, '1972-07-14', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(98, 'SUSULAN', NULL, 'BEN ASHRY', 'ABDAL', NULL, '1979-01-09', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(99, 'TAC-AN', NULL, 'VIRGILIO', 'DIAO', NULL, '1975-06-01', NULL, 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(100, 'TAHIL', 'chokowako0818@gmail.com', 'AIZA-MERIAM', 'HASIMAN', NULL, '1985-04-26', NULL, 'Female', 'Adult', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(101, 'TAN', NULL, 'ALBERTO', 'TABLATE', NULL, '1954-12-27', 'VACC COM 2022', 'Male', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(102, 'TAN', NULL, 'ZURIEL', 'KONG', NULL, '2014-03-03', 'NOT ACTIVE', 'Male', 'Surgery (Adult)', 'Plastic surgery', 'Active', '2026-09-24 08:17:16'),
(103, 'TAÑEDO', NULL, 'LANIE SALMA', 'RASUL', NULL, '1978-12-20', NULL, 'Female', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(104, 'TEO', NULL, 'CRISTAL JOYCE', 'SHIA', NULL, '1990-02-10', NULL, 'Female', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(105, 'TIBLANI', NULL, 'MIRIAM ELAINE', 'ATILANO', NULL, '1980-02-05', NULL, 'Female', 'Medicine', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(106, 'TORRES', NULL, 'POMPEYO III', 'FACULTAD', NULL, '1987-04-27', NULL, 'Male', 'Adult', 'Anaesthesiology', 'Active', '2026-09-24 08:17:16'),
(107, 'TRASPORTO', NULL, 'KENNETH MARIE', 'CALLAO', NULL, '1980-10-18', NULL, 'Female', 'Adult', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(108, 'TSANG-RAMOS', NULL, 'EMELY', 'WEE', NULL, '1980-03-09', NULL, 'Female', 'Surgery (Adult)', 'ENT and Head Neck Surgery', 'Active', '2026-09-24 08:17:16'),
(109, 'ULANGHUTAN', NULL, 'AMELYN', 'SANAANI', NULL, '1978-09-27', NULL, 'Female', 'Medicine', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(110, 'UNNOH', NULL, 'ALYASHER', 'HADJAIL', NULL, '1980-10-05', NULL, 'Male', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16'),
(111, 'UY', NULL, 'LOTTIE', 'ANDALES', NULL, '1975-01-21', NULL, 'Female', 'Pediatrics', 'Pediatrics-Cardiology', 'Active', '2026-09-24 08:17:16'),
(112, 'YEO', NULL, 'ELEONOR', 'SERRANO', NULL, '1982-04-12', NULL, 'Female', 'Adult', 'Cardiology', 'Active', '2026-09-24 08:17:16'),
(113, 'YNAWAT-ABDULMAJID', NULL, 'FARAHNAZ', 'ABUBAKAR', NULL, '1982-02-03', NULL, 'Female', 'Surgery (Adult)', 'General Surgery', 'Active', '2026-09-24 08:17:16');

-- --------------------------------------------------------

--
-- Table structure for table `operating_rooms`
--

CREATE TABLE `operating_rooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `room_description` varchar(255) DEFAULT NULL,
  `status` enum('Available','Occupied','Maintenance','Inactive') NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `operating_rooms`
--

INSERT INTO `operating_rooms` (`room_id`, `room_name`, `room_description`, `status`, `created_at`) VALUES
(1, 'theater 1', 'sample lang', 'Occupied', '2026-09-07 07:21:00'),
(2, 'theater 2', 'sample 2', 'Occupied', '2026-09-07 07:21:26'),
(3, 'theater 3', 'sample 3', 'Available', '2026-09-09 06:40:49'),
(4, 'theater 4', 'room 4', 'Available', '2026-09-11 05:18:25'),
(5, 'theater 5', 'tesrt', 'Available', '2026-09-11 05:45:50'),
(6, 'theater 6', 'fsdfsdfsf', 'Available', '2026-09-11 05:46:01'),
(7, 'theater 7', '7', 'Available', '2026-09-16 06:06:25');

-- --------------------------------------------------------

--
-- Table structure for table `or_schedules`
--

CREATE TABLE `or_schedules` (
  `schedule_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `patient_registry_no` varchar(50) DEFAULT NULL,
  `registry_date` date DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `registry_type` varchar(50) DEFAULT NULL,
  `patient_room_no` varchar(50) DEFAULT NULL,
  `bed_no` varchar(50) DEFAULT NULL,
  `surgery_date` date NOT NULL,
  `date_end` date DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_id` int(11) NOT NULL,
  `is_stat` tinyint(1) NOT NULL DEFAULT 0,
  `priority` enum('Elective','Urgent','Emergency') NOT NULL DEFAULT 'Elective',
  `surgeon_doctor_id` int(11) NOT NULL,
  `anesthesiologist_doctor_id` int(11) DEFAULT NULL,
  `anesthetic` varchar(150) DEFAULT NULL,
  `cardiologist_id` int(11) DEFAULT NULL,
  `cardiologist` varchar(150) DEFAULT NULL,
  `circulating_nurse` varchar(150) DEFAULT NULL,
  `instrument_nurse` varchar(150) DEFAULT NULL,
  `procedure_id` int(11) NOT NULL,
  `surgical_type` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `infections` text DEFAULT NULL,
  `status` enum('Scheduled','Confirmed','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `cancel_reason` text DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pre_op_diagnosis` text DEFAULT NULL,
  `post_op_diagnosis` text DEFAULT NULL,
  `drains` varchar(255) DEFAULT NULL,
  `sponge_count_verified` tinyint(1) NOT NULL DEFAULT 0,
  `specimen_lab_exam` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `or_schedules`
--

INSERT INTO `or_schedules` (`schedule_id`, `patient_id`, `patient_registry_no`, `registry_date`, `birth_date`, `registry_type`, `patient_room_no`, `bed_no`, `surgery_date`, `date_end`, `start_time`, `end_time`, `room_id`, `is_stat`, `priority`, `surgeon_doctor_id`, `anesthesiologist_doctor_id`, `anesthetic`, `cardiologist_id`, `cardiologist`, `circulating_nurse`, `instrument_nurse`, `procedure_id`, `surgical_type`, `remarks`, `infections`, `status`, `cancel_reason`, `cancelled_by`, `cancelled_at`, `completed_by`, `completed_at`, `created_by`, `created_at`, `updated_at`, `pre_op_diagnosis`, `post_op_diagnosis`, `drains`, `sponge_count_verified`, `specimen_lab_exam`) VALUES
(66, 26, 'PX-2026-0034', '2026-09-10', '1995-07-30', 'Inpatient', 'Room 203', 'Bed 1', '2026-10-12', '2026-10-12', '15:32:00', '17:32:00', 3, 1, 'Elective', 50, 46, NULL, 100, NULL, 'sad', 'asd', 1, 'Major', NULL, NULL, 'Completed', NULL, NULL, NULL, 1, '2026-10-12 15:34:16', 1, '2026-10-12 07:32:44', '2026-10-12 07:34:16', '', '', '', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `or_schedule_assistants`
--

CREATE TABLE `or_schedule_assistants` (
  `schedule_assistant_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `assistant_order` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `or_schedule_assistants`
--

INSERT INTO `or_schedule_assistants` (`schedule_assistant_id`, `schedule_id`, `doctor_id`, `assistant_order`, `created_at`) VALUES
(73, 66, 50, 1, '2026-10-12 07:32:44');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `patient_number` varchar(50) NOT NULL,
  `registry_date` date DEFAULT NULL,
  `registry_type` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `sex` enum('Male','Female') DEFAULT NULL,
  `patient_room_no` varchar(50) DEFAULT NULL,
  `bed_no` varchar(50) DEFAULT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `patient_number`, `registry_date`, `registry_type`, `first_name`, `middle_name`, `last_name`, `birth_date`, `sex`, `patient_room_no`, `bed_no`, `contact_number`, `address`, `created_at`, `status`) VALUES
(13, '0001', '2026-09-28', 'Inpatient', 'Justine', 'T.', 'Timber Lake', '2026-09-01', 'Male', '203', '1', '09950482882', 'Catana Drive, Tumaga, Zamboanga City', '2026-09-27 23:55:21', 'Completed'),
(14, '0002', '2026-09-28', 'Inpatient', 'Jona', 'T.', 'Cruz', '2026-09-02', 'Female', '203', '2', '09950482882', 'A-053 SAN ROQUE ZAMBOAGA CITY', '2026-09-27 23:56:29', 'Completed'),
(15, '0003', '2026-09-28', 'Out Patient', 'Cris', 'T.', 'Lado', '2026-09-14', 'Male', '205', '3', '09950482881', 'A-053 SAN ROQUE ZAMBOAGA CITY', '2026-09-27 23:57:05', 'Completed'),
(16, '0004', '2026-09-28', 'in Patient', 'Kurt', 'T.', 'Cruz', '2026-09-08', 'Male', '206', '4', '09950482882', 'A-053 SAN ROQUE ZAMBOAGA CITY', '2026-09-27 23:57:38', 'Completed'),
(17, '0005', '2026-09-28', 'In patient', 'Jason', 'T', 'Cruz', '2026-09-14', 'Male', '205', '5', '09950482882', 'A-053 SAN ROQUE ZAMBOAGA CITY', '2026-09-27 23:58:24', 'Completed'),
(18, '0006', '2026-09-28', 'Out Patient', 'Jd', 'T.', 'Les', '2026-09-15', 'Male', '206', '6', '09950482882', 'Catana Drive, Tumaga, Zamboanga City', '2026-09-27 23:59:08', 'Completed'),
(19, 'PX-2026-001', '2026-09-01', 'Inpatient', 'Juan', 'Santos', 'Dela Cruz', '1985-05-12', 'Male', 'Room 101', 'Bed 1', '09123456789', 'Zamboanga City', '2026-09-28 00:03:58', 'Completed'),
(20, 'PX-2026-002', '2026-09-03', 'Outpatient', 'Maria', 'Clara', 'Rizal', '1990-11-25', 'Female', 'Room 102', 'Bed 2', '09198765432', 'Tetuan, Zamboanga City', '2026-09-28 00:03:58', 'Completed'),
(21, 'PX-2026-003', '2026-09-05', 'Emergency', 'Carlos', 'Gonzales', 'Lim', '1978-02-14', 'Male', 'ER-01', 'Bed 1', '09175554433', 'Tumaga, Zamboanga City', '2026-09-28 00:03:58', 'Completed'),
(22, 'PX-2026-004', '2026-09-10', 'Inpatient', 'Ana', 'Cruz', 'Santiago', '1995-07-30', 'Female', 'Room 203', 'Bed 1', '09223334455', 'Baliwasan, Zamboanga City', '2026-09-28 00:03:58', 'Completed'),
(23, 'PX-2026-0011', '2026-09-01', 'Inpatient', 'jade', 't', 'Dela', '1985-05-12', 'Male', 'Room 101', 'Bed 1', '09123456789', 'Zamboanga City', '2026-09-28 00:05:26', 'Completed'),
(24, 'PX-2026-0012', '2026-09-03', 'Outpatient', 'Nana', 'claire', 'Rizaldo', '1990-11-25', 'Female', 'Room 102', 'Bed 2', '09198765432', 'Tetuan, Zamboanga City', '2026-09-28 00:05:26', 'Completed'),
(25, 'PX-2026-0023', '2026-09-05', 'Emergency', 'Jane', 'berdon', 'Tan', '1978-02-14', 'Male', 'ER-01', 'Bed 1', '09175554433', 'Tumaga, Zamboanga City', '2026-09-28 00:05:26', 'Completed'),
(26, 'PX-2026-0034', '2026-09-10', 'Inpatient', 'Analiza', 'Cruzar', 'vertigo', '1995-07-30', 'Female', 'Room 203', 'Bed 1', '09223334455', 'Baliwasan, Zamboanga City', '2026-09-28 00:05:26', 'Completed');

-- --------------------------------------------------------

--
-- Table structure for table `procedures`
--

CREATE TABLE `procedures` (
  `procedure_id` int(11) NOT NULL,
  `procedure_name` varchar(200) NOT NULL,
  `surgical_type` varchar(100) DEFAULT NULL,
  `procedure_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `procedures`
--

INSERT INTO `procedures` (`procedure_id`, `procedure_name`, `surgical_type`, `procedure_code`, `description`, `status`, `created_at`) VALUES
(1, 'Appendectomy', 'Major', 'APP-001', NULL, 'Active', '2026-09-10 06:18:45'),
(2, 'Cholecystectomy', 'Major', 'CHO-001', NULL, 'Active', '2026-09-10 06:18:45'),
(3, 'Cesarean Section', 'Major', 'CS-001', NULL, 'Active', '2026-09-10 06:18:45'),
(4, 'Hernia Repair', 'Minor', 'HERN-001', NULL, 'Active', '2026-09-10 06:18:45'),
(5, 'Total Knee Replacement', 'Major', 'TKR-001', NULL, 'Active', '2026-09-10 06:18:45'),
(6, 'Total Hip Replacement', 'Major', 'THR-001', NULL, 'Active', '2026-09-10 06:18:45'),
(7, 'Cardiac Catheterization', 'Minor', 'CATH-001', NULL, 'Active', '2026-09-10 06:18:45'),
(8, 'Coronary Artery Bypass Graft', 'Major', 'CABG-001', NULL, 'Active', '2026-09-10 06:18:45'),
(9, 'Total Abdominal Hysterectomy', 'Major', 'TAH-001', NULL, 'Active', '2026-09-10 06:18:45'),
(10, 'Transurethral Resection of Prostate', 'Major', 'TURP-001', NULL, 'Active', '2026-09-10 06:18:45');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'enable_email_alerts', '1', '2026-10-11 10:17:24'),
(2, 'enable_sms_alerts', '0', '2026-10-11 10:39:19'),
(3, 'alert_lead_time', '30', '2026-10-11 10:39:19'),
(4, 'enable_activity_logging', '1', '2026-10-11 10:17:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role` enum('Administrator','Scheduler','Staff') NOT NULL DEFAULT 'Staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$gCbG.CK0iSQrZvm9T8CwmuScY01to.P/ppRsGcSerzcYQ7rwnUdHO', 'System Administrator', 'Administrator', '2026-09-07 02:08:42'),
(2, 'JSA', '$2y$10$cpKtLRHShDD1R2wn6ggSLuXu1QJDRQWhVjqrEriXtvW0tjV5UQcyS', 'JONEL ANSILAN', 'Scheduler', '2026-09-17 05:16:40'),
(3, 'CRIS', '$2y$10$3kdGbrcC22Bf0OTG0y8HVuyFNn/SYSMNJqd/qpuW838aUozZkHndO', 'CRIS PAGAROGAN', 'Staff', '2026-09-17 06:05:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anesthetics`
--
ALTER TABLE `anesthetics`
  ADD PRIMARY KEY (`anesthetic_id`),
  ADD UNIQUE KEY `uq_anesthetic_name` (`anesthetic_name`),
  ADD KEY `idx_anesthetic_status` (`status`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD KEY `idx_doctor_name` (`last_name`,`first_name`),
  ADD KEY `idx_specialization` (`specialization`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `operating_rooms`
--
ALTER TABLE `operating_rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_name` (`room_name`);

--
-- Indexes for table `or_schedules`
--
ALTER TABLE `or_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_surgeon_doctor_id` (`surgeon_doctor_id`),
  ADD KEY `idx_anesthesiologist_doctor_id` (`anesthesiologist_doctor_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_procedure_id` (`procedure_id`),
  ADD KEY `idx_surgery_date` (`surgery_date`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `or_schedule_assistants`
--
ALTER TABLE `or_schedule_assistants`
  ADD PRIMARY KEY (`schedule_assistant_id`),
  ADD KEY `idx_schedule_id` (`schedule_id`),
  ADD KEY `idx_assistant_doctor_id` (`doctor_id`),
  ADD KEY `idx_assistant_order` (`assistant_order`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `uq_patient_number` (`patient_number`),
  ADD KEY `idx_patient_name` (`last_name`,`first_name`),
  ADD KEY `idx_birth_date` (`birth_date`);

--
-- Indexes for table `procedures`
--
ALTER TABLE `procedures`
  ADD PRIMARY KEY (`procedure_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anesthetics`
--
ALTER TABLE `anesthetics`
  MODIFY `anesthetic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `operating_rooms`
--
ALTER TABLE `operating_rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `or_schedules`
--
ALTER TABLE `or_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `or_schedule_assistants`
--
ALTER TABLE `or_schedule_assistants`
  MODIFY `schedule_assistant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `procedures`
--
ALTER TABLE `procedures`
  MODIFY `procedure_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `or_schedules`
--
ALTER TABLE `or_schedules`
  ADD CONSTRAINT `fk_schedule_anesthesiologist_doctor` FOREIGN KEY (`anesthesiologist_doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_procedure` FOREIGN KEY (`procedure_id`) REFERENCES `procedures` (`procedure_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_room` FOREIGN KEY (`room_id`) REFERENCES `operating_rooms` (`room_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_surgeon_doctor` FOREIGN KEY (`surgeon_doctor_id`) REFERENCES `doctors` (`doctor_id`) ON UPDATE CASCADE;

--
-- Constraints for table `or_schedule_assistants`
--
ALTER TABLE `or_schedule_assistants`
  ADD CONSTRAINT `fk_schedule_assistant_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_assistant_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `or_schedules` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
