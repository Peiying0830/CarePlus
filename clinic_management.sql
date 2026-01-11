-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 10, 2026 at 08:21 PM
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
-- Database: `clinic_management`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_checkin_appointment` (IN `p_qr_code` VARCHAR(255), IN `p_user_id` INT, OUT `p_result` VARCHAR(50), OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE v_appointment_id INT;
    DECLARE v_status VARCHAR(20);
    DECLARE v_date DATE;
    
    -- Find appointment by QR code
    SELECT appointment_id, status, appointment_date 
    INTO v_appointment_id, v_status, v_date
    FROM appointments 
    WHERE qr_code = p_qr_code
    LIMIT 1;
    
    -- Check if appointment exists
    IF v_appointment_id IS NULL THEN
        SET p_result = 'ERROR';
        SET p_message = 'Invalid QR code';
    
    -- Check if appointment is cancelled
    ELSEIF v_status = 'cancelled' THEN
        SET p_result = 'ERROR';
        SET p_message = 'Appointment has been cancelled';
    
    -- Check if appointment is completed
    ELSEIF v_status = 'completed' THEN
        SET p_result = 'WARNING';
        SET p_message = 'Appointment already completed';
    
    -- Check if appointment is today
    ELSEIF v_date != CURDATE() THEN
        SET p_result = 'ERROR';
        SET p_message = CONCAT('Appointment is scheduled for ', DATE_FORMAT(v_date, '%M %d, %Y'));
    
    -- Valid check-in
    ELSE
        UPDATE appointments 
        SET checked_in_at = NOW(),
            checked_in_by = p_user_id,
            status = 'confirmed'
        WHERE appointment_id = v_appointment_id;
        
        -- Log the check-in
        INSERT INTO qr_scan_logs (appointment_id, qr_code, scanned_by, scan_result)
        VALUES (v_appointment_id, p_qr_code, p_user_id, 'success');
        
        SET p_result = 'SUCCESS';
        SET p_message = 'Patient checked in successfully';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cleanup_old_symptom_checks` ()   BEGIN
    DECLARE v_deleted_count INT DEFAULT 0;
    
    -- Create temporary table with IDs to keep
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_keep_ids AS
    SELECT id
    FROM (
        SELECT id, user_id,
               ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC) as rn
        FROM symptom_checks
    ) ranked
    WHERE rn <= 100;
    
    -- Delete records not in the keep list
    DELETE FROM symptom_checks
    WHERE id NOT IN (SELECT id FROM temp_keep_ids);
    
    SET v_deleted_count = ROW_COUNT();
    
    -- Clean up
    DROP TEMPORARY TABLE IF EXISTS temp_keep_ids;
    
    -- Return count
    SELECT v_deleted_count as deleted_count;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_confirm_appointment` (IN `p_appointment_id` INT, IN `p_patient_id` INT, OUT `p_result` VARCHAR(50), OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE v_confirmation_deadline DATETIME;
    DECLARE v_patient_id_check INT;
    
    -- Get appointment details
    SELECT status, confirmation_deadline, patient_id
    INTO v_status, v_confirmation_deadline, v_patient_id_check
    FROM appointments
    WHERE appointment_id = p_appointment_id;
    
    -- Check if appointment exists
    IF v_patient_id_check IS NULL THEN
        SET p_result = 'ERROR';
        SET p_message = 'Appointment not found';
        
    -- Check if appointment belongs to this patient
    ELSEIF v_patient_id_check != p_patient_id THEN
        SET p_result = 'ERROR';
        SET p_message = 'Unauthorized access';
        
    -- Check if already confirmed
    ELSEIF v_status = 'confirmed' THEN
        SET p_result = 'WARNING';
        SET p_message = 'Appointment already confirmed';
        
    -- Check if cancelled
    ELSEIF v_status = 'cancelled' THEN
        SET p_result = 'ERROR';
        SET p_message = 'Cannot confirm cancelled appointment';
        
    -- Check if completed
    ELSEIF v_status = 'completed' THEN
        SET p_result = 'ERROR';
        SET p_message = 'Cannot confirm completed appointment';
        
    -- Check if confirmation deadline has passed
    ELSEIF v_confirmation_deadline < NOW() THEN
        -- Auto-cancel the appointment
        UPDATE appointments
        SET status = 'cancelled',
            notes = CONCAT(COALESCE(notes, ''), ' [Auto-cancelled: confirmation deadline expired]'),
            updated_at = NOW()
        WHERE appointment_id = p_appointment_id;
        
        SET p_result = 'ERROR';
        SET p_message = 'Confirmation deadline has expired. Appointment has been cancelled.';
        
    -- All checks passed - confirm the appointment
    ELSE
        UPDATE appointments
        SET status = 'confirmed',
            confirmed_at = NOW(),
            updated_at = NOW()
        WHERE appointment_id = p_appointment_id;
        
        SET p_result = 'SUCCESS';
        SET p_message = 'Appointment confirmed successfully! You can now view your QR code.';
    END IF;
    
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `ic_number` varchar(20) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`, `first_name`, `last_name`, `ic_number`, `phone`, `department`, `created_at`, `profile_picture`) VALUES
(1, 4, 'Pei', 'Ying', '000830080326', '0123456789', 'IT Support', '2026-01-05 15:28:45', 'uploads/profiles/admin_4_1767627346.png'),
(2, 7, 'Chan', 'Jade Qi', '051116081111', '0195705579', 'Finance', '2026-01-09 01:20:58', 'uploads/profiles/admin_7_1767934481.png');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('unpaid','pending','paid') DEFAULT 'unpaid',
  `reason` text DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `confirmation_deadline` datetime DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `confirmation_reminder_sent` tinyint(1) DEFAULT 0,
  `checked_in_at` datetime DEFAULT NULL,
  `checked_in_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `payment_status`, `reason`, `symptoms`, `qr_code`, `confirmation_deadline`, `confirmed_at`, `confirmation_reminder_sent`, `checked_in_at`, `checked_in_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-01-06', '19:30:00', 'completed', 'paid', 'Consultation', 'Fever', 'APT-0DA70293E65D', '2026-01-06 18:03:59', '2026-01-05 18:47:02', 0, NULL, NULL, '2026-01-05 10:03:59', '2026-01-06 09:34:12'),
(2, 1, 1, '2026-01-08', '09:00:00', 'confirmed', 'unpaid', 'Consultation', 'Stomach pain', 'APT-DAF142369E05', '2026-01-07 15:52:36', NULL, 0, NULL, NULL, '2026-01-06 07:52:36', '2026-01-06 08:01:03'),
(3, 1, 1, '2026-01-31', '09:00:00', 'cancelled', 'unpaid', 'consultation', 'Fever', 'APT-1DD5F274999B', '2026-01-07 17:17:51', '2026-01-06 17:19:32', 0, NULL, NULL, '2026-01-06 09:17:51', '2026-01-06 09:20:28'),
(4, 2, 2, '2026-01-12', '15:30:00', 'confirmed', 'unpaid', 'consultation', 'Fever', 'APT-BEC43DE18085', '2026-01-10 12:46:46', '2026-01-09 12:46:53', 0, NULL, NULL, '2026-01-09 04:46:46', '2026-01-09 04:50:22');

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `trg_generate_qr_code` BEFORE INSERT ON `appointments` FOR EACH ROW BEGIN
    IF NEW.qr_code IS NULL OR NEW.qr_code = '' THEN
        SET NEW.qr_code = CONCAT('APT-', UPPER(SUBSTRING(MD5(CONCAT(NEW.patient_id, NEW.doctor_id, NOW(), RAND())), 1, 12)));
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_set_confirmation_deadline` BEFORE INSERT ON `appointments` FOR EACH ROW BEGIN
    -- Set confirmation deadline to 24 hours from now
    IF NEW.confirmation_deadline IS NULL THEN
        SET NEW.confirmation_deadline = DATE_ADD(NOW(), INTERVAL 24 HOUR);
    END IF;
    
    -- Force status to 'pending' for NEW appointments (not confirmed yet)
    SET NEW.status = 'pending';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_logs`
--

CREATE TABLE `chatbot_logs` (
  `log_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) NOT NULL,
  `user_message` text NOT NULL,
  `bot_response` text NOT NULL,
  `chatbot_scope_id` int(11) DEFAULT NULL,
  `is_restricted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_logs`
--

INSERT INTO `chatbot_logs` (`log_id`, `patient_id`, `session_id`, `user_message`, `bot_response`, `chatbot_scope_id`, `is_restricted`, `created_at`) VALUES
(1, NULL, '', 'sick', 'I\'m here to help! Please choose from the following options:\n\n1️⃣ Book an Appointment\n2️⃣ Clinic Hours & Location\n3️⃣ Find a Doctor\n4️⃣ Services We Offer\n5️⃣ Payment Methods\n6️⃣ Contact Information\n7️⃣ General Health Tips\n8️⃣ Vaccination Information\n9️⃣ Talk to a Staff Member\n\n💬 Just type the number (1-9) or ask your question directly!', NULL, 0, '2026-01-05 09:26:04'),
(2, NULL, '', 'hi', 'I\'m here to help! Please choose from the following options:\n\n1️⃣ Book an Appointment\n2️⃣ Clinic Hours & Location\n3️⃣ Find a Doctor\n4️⃣ Services We Offer\n5️⃣ Payment Methods\n6️⃣ Contact Information\n7️⃣ General Health Tips\n8️⃣ Vaccination Information\n9️⃣ Talk to a Staff Member\n\n💬 Just type the number (1-9) or ask your question directly!', NULL, 0, '2026-01-06 09:36:42'),
(3, NULL, '', '3', '👨‍⚕️ Find a Doctor:\n\n1️⃣ Visit our \'Find a Doctor\' page\n2️⃣ Filter by specialty or name\n3️⃣ View doctor profiles and ratings\n4️⃣ Book directly with your preferred doctor\n\nWhat specialty are you looking for?', NULL, 0, '2026-01-06 09:36:48'),
(4, NULL, '', '1', '📅 To book an appointment, please log in first!\n\n1️⃣ Log in to your account\n2️⃣ Go to \'Book Appointment\'\n3️⃣ Select a doctor and specialty\n4️⃣ Choose available date and time', NULL, 0, '2026-01-06 09:36:51'),
(5, NULL, '', 'choose', 'I\'m here to help! Please choose from the following options:\n\n1️⃣ Book an Appointment\n2️⃣ Clinic Hours & Location\n3️⃣ Find a Doctor\n4️⃣ Services We Offer\n5️⃣ Payment Methods\n6️⃣ Contact Information\n7️⃣ General Health Tips\n8️⃣ Vaccination Information\n9️⃣ Talk to a Staff Member\n\n💬 Just type the number (1-9) or ask your question directly!', NULL, 0, '2026-01-06 09:36:56'),
(6, NULL, '', '9', '👋 I\'ll connect you with our team!\n\nA staff member will reach out to you shortly. In the meantime:\n\n📞 Call: +60 12-345 6789\n📧 Email: support@careplus.com\n\nYour inquiry has been logged and will be addressed soon! ✅', NULL, 0, '2026-01-06 09:37:03'),
(7, NULL, 'sm009n81pjo05vnn5in1b552ko', 'HI', '👋 Hello! Welcome to CarePlus Smart Clinic Management Portal!\r\n\r\nI\'m your friendly AI assistant, ready to help you with:\r\n\r\n📅 Appointments - Book, reschedule, or check status\r\n🏥 Clinic Info - Hours, location, and services\r\n👨‍⚕️ Doctors - Find specialists and view profiles\r\n💡 Health Tips - General wellness information\r\n\r\nHow may I assist you today? 😊', 1, 0, '2026-01-08 09:07:46'),
(8, NULL, 'sm009n81pjo05vnn5in1b552ko', 'appointment', 'I\'m here to help! Please choose from the following options:\n\n1️⃣ Book an Appointment\n2️⃣ Clinic Hours & Location\n3️⃣ Find a Doctor\n4️⃣ Services We Offer\n5️⃣ Payment Methods\n6️⃣ Contact Information\n7️⃣ General Health Tips\n8️⃣ Vaccination Information\n9️⃣ Talk to a Staff Member\n\n💬 Just type the number (1-9) or ask your question directly!', NULL, 0, '2026-01-08 09:08:05'),
(9, NULL, 'sm009n81pjo05vnn5in1b552ko', '1', '📅 To book an appointment, please log in first!\n\n1️⃣ Log in to your account\n2️⃣ Go to \'Book Appointment\'\n3️⃣ Select a doctor and specialty\n4️⃣ Choose available date and time', 3, 0, '2026-01-08 09:08:08'),
(10, NULL, 'sm009n81pjo05vnn5in1b552ko', '2', '🏥 CarePlus Clinic Hours:\n\n📆 Monday - Friday: 9:00 AM - 8:00 PM\n📆 Saturday: 9:00 AM - 5:00 PM\n🚫 Sunday & Public Holidays: Closed\n\n📍 Location:\nKlinik Careclinics Ipoh\n1, Jln Sultan Nazrin Shah,\nMedan Gopeng, 31350 Ipoh, Perak', 6, 0, '2026-01-08 09:08:09'),
(11, NULL, 'sm009n81pjo05vnn5in1b552ko', 'hello', '👋 Hello! Welcome to CarePlus Smart Clinic Management Portal!\r\n\r\nI\'m your friendly AI assistant, ready to help you with:\r\n\r\n📅 Appointments - Book, reschedule, or check status\r\n🏥 Clinic Info - Hours, location, and services\r\n👨‍⚕️ Doctors - Find specialists and view profiles\r\n💡 Health Tips - General wellness information\r\n\r\nHow may I assist you today? 😊', 1, 0, '2026-01-08 09:18:00'),
(12, NULL, 'sm009n81pjo05vnn5in1b552ko', 'health', 'I\'m here to help! Please choose from the following options:\n\n1️⃣ Book an Appointment\n2️⃣ Clinic Hours & Location\n3️⃣ Find a Doctor\n4️⃣ Services We Offer\n5️⃣ Payment Methods\n6️⃣ Contact Information\n7️⃣ General Health Tips\n8️⃣ Vaccination Information\n9️⃣ Talk to a Staff Member\n\n💬 Just type the number (1-9) or ask your question directly!', NULL, 0, '2026-01-08 09:18:08'),
(13, NULL, 'sm009n81pjo05vnn5in1b552ko', '5', '💳 Payment Methods:\n\n💵 Cash\n📱 E-Wallets:\n  • Touch n Go eWallet\n  • GrabPay\n  • Boost\n  • ShopeePay\n\n💡 Payment is collected after consultation.\n📝 Receipts provided for all transactions.', 17, 0, '2026-01-08 09:18:13'),
(14, NULL, 'sm009n81pjo05vnn5in1b552ko', 'hi', '👋 Hello! Welcome to CarePlus Smart Clinic Management Portal!\r\n\r\nI\'m your friendly AI assistant, ready to help you with:\r\n\r\n📅 Appointments - Book, reschedule, or check status\r\n🏥 Clinic Info - Hours, location, and services\r\n👨‍⚕️ Doctors - Find specialists and view profiles\r\n💡 Health Tips - General wellness information\r\n\r\nHow may I assist you today? 😊', 1, 0, '2026-01-08 09:27:30'),
(15, NULL, 'sm009n81pjo05vnn5in1b552ko', 'hi]', '0', 1, 0, '2026-01-08 12:07:09'),
(16, NULL, 'sm009n81pjo05vnn5in1b552ko', 'docyor', '0', NULL, 0, '2026-01-08 12:07:15'),
(17, NULL, 'sm009n81pjo05vnn5in1b552ko', 'yes', 'I\'m here to help! Please choose from the following options:\n\n1️⃣ Book an Appointment\n2️⃣ Clinic Hours & Location\n3️⃣ Find a Doctor\n4️⃣ Services We Offer\n5️⃣ Payment Methods\n6️⃣ Contact Information\n7️⃣ General Health Tips\n8️⃣ Vaccination Information\n9️⃣ Talk to a Staff Member\n\n💬 Just type the number (1-9) or ask your question directly!', NULL, 0, '2026-01-08 12:09:50'),
(18, NULL, 'sm009n81pjo05vnn5in1b552ko', 'contact info', 'I\'m here to help! Please choose from the following options:\n\n1️⃣ Book an Appointment\n2️⃣ Clinic Hours & Location\n3️⃣ Find a Doctor\n4️⃣ Services We Offer\n5️⃣ Payment Methods\n6️⃣ Contact Information\n7️⃣ General Health Tips\n8️⃣ Vaccination Information\n9️⃣ Talk to a Staff Member\n\n💬 Just type the number (1-9) or ask your question directly!', NULL, 0, '2026-01-08 12:10:01'),
(19, NULL, 'sm009n81pjo05vnn5in1b552ko', 'hi', '0', 1, 0, '2026-01-08 12:12:44'),
(20, NULL, 'sm009n81pjo05vnn5in1b552ko', 'health', '0', NULL, 0, '2026-01-08 12:13:10'),
(21, NULL, 'sm009n81pjo05vnn5in1b552ko', '6', '0', 9, 0, '2026-01-08 12:13:16'),
(22, NULL, 'sm009n81pjo05vnn5in1b552ko', 'halo', '👋 Hello! Welcome to CarePlus Smart Clinic Management Portal!\r\n\r\nI\'m your friendly AI assistant, ready to help you with:\r\n\r\n📅 Appointments - Book, reschedule, or check status\r\n🏥 Clinic Info - Hours, location, and services\r\n👨‍⚕️ Doctors - Find specialists and view profiles\r\n💡 Health Tips - General wellness information\r\n\r\nHow may I assist you today? 😊', 1, 0, '2026-01-08 12:15:51'),
(23, NULL, 'sm009n81pjo05vnn5in1b552ko', 'helath', 'I\'m here to help! Please choose from the following options:\n\n1️⃣ Book an Appointment\n2️⃣ Clinic Hours & Location\n3️⃣ Find a Doctor\n4️⃣ Services We Offer\n5️⃣ Payment Methods\n6️⃣ Contact Information\n7️⃣ General Health Tips\n8️⃣ Vaccination Information\n9️⃣ Talk to a Staff Member\n\n💬 Just type the number (1-9) or ask your question directly!', NULL, 0, '2026-01-08 12:15:55'),
(24, NULL, 'sm009n81pjo05vnn5in1b552ko', '9', '👋 I\'ll connect you with our team!\n\nA staff member will reach out shortly. Meanwhile:\n\n📞 Call: +60 12-345 6789\n📧 Email: support@careplus.com\n\nYour inquiry has been logged! ✅\n\n⏰ Response time: Within 2-4 hours during office hours.', 2, 0, '2026-01-08 12:16:11');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_restricted_topics`
--

CREATE TABLE `chatbot_restricted_topics` (
  `restriction_id` int(11) NOT NULL,
  `topic_name` varchar(255) NOT NULL,
  `keywords` text NOT NULL COMMENT 'Keywords that trigger restriction',
  `restriction_reason` enum('medical_advice','privacy','legal','out_of_scope','emergency') NOT NULL,
  `redirect_message` text NOT NULL COMMENT 'Message to show when topic is restricted',
  `severity` enum('low','medium','high') DEFAULT 'medium',
  `log_attempt` tinyint(1) DEFAULT 1 COMMENT 'Log when users try to access this',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_restricted_topics`
--

INSERT INTO `chatbot_restricted_topics` (`restriction_id`, `topic_name`, `keywords`, `restriction_reason`, `redirect_message`, `severity`, `log_attempt`, `is_active`, `created_at`) VALUES
(1, 'Medical Diagnosis', 'diagnose,what do i have,is it cancer,do i have,disease diagnosis,medical condition,is this serious,whats wrong with me', 'medical_advice', '⚠️ MEDICAL DIAGNOSIS NEEDED\n\nI cannot provide medical diagnoses as I\'m an clinic assistant, not a doctor.\n\n👨‍⚕️ FOR PROPER EVALUATION:\n  • Book appointment with our doctors\n  • Get professional diagnosis\n  • Receive appropriate treatment\n\n📅 Would you like to:\n  • Book a consultation?\n  • Use our symptom checker?\n  • Speak with our staff?\n\n📞 Urgent concerns? Call: +60 12-345 6789', 'high', 1, 1, '2025-12-28 11:36:55'),
(2, 'Prescription Medication', 'prescribe,medication for,which medicine,drug dosage,prescription,antibiotics,painkillers,give me medicine', 'medical_advice', '⚠️ PRESCRIPTION REQUIRED\n\nI cannot prescribe medications or recommend specific drugs.\n\n👨‍⚕️ TO GET PRESCRIPTION:\n  • Book doctor consultation\n  • Discuss your symptoms\n  • Receive proper prescription\n\n🚫 NEVER:\n  • Self-medicate\n  • Use others\' medications\n  • Take expired medicines\n\n📅 Book appointment for proper medical care\n\n⚠️ Emergency? Call 999 immediately', 'high', 1, 1, '2025-12-28 11:36:55'),
(3, 'Symptom Interpretation', 'i have pain,my symptoms,what is wrong with me,symptom check,is this normal,should i worry', 'medical_advice', '💡 SYMPTOM EVALUATION\n\nWhile I can provide general health information, I cannot diagnose conditions based on symptoms.\n\n✅ WHAT I CAN DO:\n  • Suggest booking an appointment\n  • Provide general health tips\n  • Help you find specialists\n\n👨‍⚕️ WHAT YOU NEED:\nProfessional medical evaluation for accurate diagnosis\n\n📅 OPTIONS:\n  1️⃣ Book doctor appointment\n  2️⃣ Use our symptom checker tool\n  3️⃣ Call for advice: +60 12-345 6789\n\nWould you like to book an appointment?', 'medium', 1, 1, '2025-12-28 11:36:55'),
(4, 'Dosage & Treatment Advice', 'how much medicine,dosage,how to take,treatment for,cure for,best medicine', 'medical_advice', '⚠️ MEDICAL GUIDANCE REQUIRED\n\nI cannot provide dosage information or treatment recommendations.\n\n👨‍⚕️ SAFE MEDICATION USE:\n  • Always follow doctor\'s orders\n  • Read medication labels\n  • Never adjust doses yourself\n  • Report side effects to doctor\n\n📋 FOR MEDICATION QUESTIONS:\n  • Consult your doctor\n  • Speak with pharmacist\n  • Book follow-up appointment\n\n📞 Concerns? Call: +60 12-345 6789\n\nYour safety is our priority!', 'high', 1, 1, '2025-12-28 11:36:55'),
(5, 'Personal Medical Records', 'my medical records,my test results,my diagnosis,my prescription history,my results', 'privacy', '🔒 SECURE ACCESS REQUIRED\n\nFor your privacy and security, medical records must be accessed through your secure patient portal.\n\n🔐 TO ACCESS YOUR RECORDS:\n  1️⃣ Log in to your account\n  2️⃣ Go to \"My Medical Records\"\n  3️⃣ View all documents securely\n\n📋 AVAILABLE RECORDS:\n  • Test results\n  • Prescriptions\n  • Medical history\n  • Appointment notes\n\n❌ CANNOT ACCESS?\n  • Reset your password\n  • Contact: support@careplus.com\n  • Call: +60 12-345 6789\n\nYour privacy is protected by PDPA regulations.', 'high', 1, 1, '2025-12-28 11:36:55'),
(6, 'Other Patients Information', 'patient records,other patient,someone else medical,johns records,family member records', 'privacy', '🚫 PRIVACY VIOLATION\n\nI cannot provide information about other patients due to privacy regulations (PDPA).\n\n🔒 PRIVACY POLICY:\n  • Each patient\'s records are confidential\n  • Only accessible by the patient\n  • Cannot be shared without consent\n\n👨‍👩‍👧 FAMILY MEMBERS:\n  • Each must have own account\n  • Access own records separately\n  • Guardians: Special authorization needed\n\n📞 FOR AUTHORIZED ACCESS:\nContact clinic administration:\n+60 12-345 6789\n\nWe protect everyone\'s privacy!', 'high', 1, 1, '2025-12-28 11:36:55'),
(7, 'Legal Advice', 'medical negligence,sue,malpractice,legal action,lawyer,compensation claim,lawsuit', 'legal', '⚖️ LEGAL MATTERS\n\nI cannot provide legal advice regarding medical matters.\n\n📋 FOR LEGAL CONCERNS:\n  • Consult a qualified lawyer\n  • Contact clinic administrator\n  • Review your medical records\n\n📞 CLINIC ADMINISTRATION:\n+60 12-345 6789\nsupport@careplus.com\n\n🏥 PATIENT RELATIONS:\nWe take all concerns seriously and are committed to patient care excellence.\n\nWould you like contact information for patient relations?', 'medium', 1, 1, '2025-12-28 11:36:55'),
(8, 'Insurance Claims', 'insurance claim,health insurance,coverage,medical insurance claim,insurance company', 'out_of_scope', '💼 BILLING & PAYMENT INFORMATION\n\nFor billing and financial matters:\n\n📋 BILLING SUPPORT:\n  📞 Phone: +60 12-345 6789\n  📧 Email: support@careplus.com\n  🕐 Hours: Mon-Sat, 9AM–8PM\n\n📄 OUR STAFF CAN HELP WITH:\n  • Billing statements\n  • Payment inquiries\n  • Medical certificates\n  • Receipts\n\n💡 TIP: Payment is made at the reception after consultation.', 'low', 1, 1, '2025-12-28 11:36:55'),
(9, 'Experimental Treatments', 'experimental treatment,clinical trial,unapproved drug,alternative medicine,herbal cure', 'medical_advice', '⚠️ SPECIALIZED CONSULTATION NEEDED\n\nQuestions about experimental or alternative treatments require professional medical consultation.\n\n👨‍⚕️ DISCUSS WITH YOUR DOCTOR:\n  • Evidence-based treatments\n  • Clinical trial eligibility\n  • Treatment alternatives\n  • Risk vs. benefits\n\n📅 SCHEDULE CONSULTATION:\nBook appointment to discuss treatment options\n\n⚠️ IMPORTANT:\n  • Unproven treatments may be risky\n  • Always consult licensed doctors\n  • Avoid unlicensed practitioners\n\nBook an appointment for professional guidance?', 'medium', 1, 1, '2025-12-28 11:36:55'),
(10, 'Pet/Animal Health', 'my dog,my cat,pet health,animal sick,veterinarian', 'out_of_scope', '🐾 VETERINARY CARE NEEDED\r\n\r\nWe\'re a human medical clinic and cannot provide veterinary services.\r\n\r\n🏥 FOR YOUR PET:\r\n  • Contact a veterinary clinic\r\n  • Call emergency vet for urgent issues\r\n  • Search: \"veterinary clinic near me\"\r\n\r\n🐕🐈 PET EMERGENCIES:\r\nContact 24-hour emergency vet immediately\r\n\r\n💚 We care about all creatures, but pets need specialized veterinary care!\r\n\r\nIs there anything else I can help you with for human healthcare?', 'low', 0, 1, '2025-12-28 11:36:55'),
(11, 'Suicide & Self-Harm', 'suicide,kill myself,end my life,self harm,want to die,harm myself,no reason to live', 'emergency', '🆘 IMMEDIATE HELP AVAILABLE\n\nYou\'re not alone. Help is available right now.\n\n📞 CRISIS HOTLINES:\n🔴 Befrienders Malaysia\n   03-7627 2929 (24/7)\n   \n🔴 Mental Health Helpline\n   03-2935 9935 (24/7)\n\n🚨 EMERGENCY: 999\n\n💙 YOU MATTER:\n  • Your life has value\n  • People care about you\n  • Help is available\n  • Things can get better\n\n🏥 PROFESSIONAL HELP:\nWould you like me to:\n  • Provide more mental health resources?\n  • Connect you with our counseling services?\n  • Share suicide prevention information?\n\nPlease reach out for help immediately. You deserve support.', 'high', 1, 1, '2025-12-28 11:36:55'),
(12, 'Abuse & Violence', 'being abused,domestic violence,someone hurting me,sexual abuse,violent', 'emergency', '🆘 HELP & PROTECTION AVAILABLE\n\nYour safety is the priority.\n\n📞 IMMEDIATE HELP:\n🚨 Police Emergency: 999\n\n🔴 Women\'s Aid Organization (WAO)\n   03-7956 3488 (24/7)\n\n🔴 Talian Kasih\n   15999 (24/7)\n\n🏥 MEDICAL CARE:\nVisit emergency room for:\n  • Medical treatment\n  • Documentation of injuries\n  • Safety planning\n\n🔒 YOUR SAFETY MATTERS:\n  • You\'re not alone\n  • Help is available\n  • Abuse is never okay\n  • Support services exist\n\nWould you like more resources or support information?', 'high', 1, 1, '2025-12-28 11:36:55');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_scope`
--

CREATE TABLE `chatbot_scope` (
  `chatbot_scope_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL COMMENT 'Main category (e.g., General Health, Appointments, Services)',
  `topic` varchar(255) NOT NULL COMMENT 'Specific topic within category',
  `keywords` text NOT NULL COMMENT 'Comma-separated keywords to match',
  `allowed_response_type` enum('informative','directive','restricted') DEFAULT 'informative',
  `response_template` text DEFAULT NULL COMMENT 'Template for consistent responses',
  `max_detail_level` enum('basic','moderate','detailed') DEFAULT 'moderate',
  `requires_login` tinyint(1) DEFAULT 0 COMMENT 'Requires user to be logged in',
  `priority` int(11) DEFAULT 0 COMMENT 'Higher priority topics are matched first',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_scope`
--

INSERT INTO `chatbot_scope` (`chatbot_scope_id`, `category`, `topic`, `keywords`, `allowed_response_type`, `response_template`, `max_detail_level`, `requires_login`, `priority`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'General', 'Greetings', 'hello,hi,hey,good morning,good afternoon,good evening,greetings,halo,hai', 'informative', '👋 Hello! Welcome to CarePlus Smart Clinic Management Portal!\n\nI\'m your friendly CarePlus assistant, ready to help you with:\n\n📅 Appointments - Book, reschedule, or check status\n🏥 Clinic Info - Hours, location, and services\n👨‍⚕️ Doctors - Find specialists and view profiles\n💡 Health Tips - General wellness information\n\nHow may I assist you today? 😊', 'basic', 0, 10, 1, '2025-12-28 11:34:22', '2026-01-08 14:05:49'),
(2, 'General', 'Help & Capabilities', 'help,what can you do,how to use,assist,support,capabilities,menu,options', 'informative', '💬 Here\'s what I can help you with:\n--- 📋 APPOINTMENTS ---\n\n   • Book new appointments\n   • Reschedule existing bookings\n   • Cancel appointments\n   • Check appointment status\n   • View appointment history\n\n--- 🏥 CLINIC INFORMATION ---\n\n   • Operating hours\n   • Location & directions\n   • Available services\n   • Contact information\n   • Facility details\n\n--- 👨‍⚕️ FIND DOCTORS ---\n\n   • Search by specialty\n   • View doctor profiles\n   • Check availability\n   • See consultation fees\n\n--- 💡 HEALTH GUIDANCE ---\n\n   • General health tips\n   • Vaccination info\n   • Preventive care\n   • Wellness advice\n\nWhat would you like to explore? ✨', 'detailed', 0, 10, 1, '2025-12-28 11:34:22', '2025-12-29 04:33:44'),
(3, 'Appointments', 'Booking Process', 'book appointment,make appointment,schedule appointment,how to book,new appointment,set appointment', 'directive', '--- 📅 BOOK YOUR APPOINTMENT ---\n\nFollow these simple steps:\n\n1️⃣ LOG IN\n   Access your account at CarePlus\n\n2️⃣ SELECT \"BOOK APPOINTMENT\"\n   Navigate to booking page\n\n3️⃣ CHOOSE YOUR DOCTOR\n   Pick by specialty or name\n\n4️⃣ SELECT DATE & TIME\n   View available slots\n\n5️⃣ CONFIRM BOOKING\n   Review and submit\n\n\n--- ✅ WHAT HAPPENS NEXT? ---\n   • QR code for easy check-in\n   • Notification will show in your first page\n\n💡 TIP: Book early for preferred time slots!\n\nNeed help with any step? Just ask! 😊', 'detailed', 0, 9, 1, '2025-12-28 11:34:22', '2025-12-29 04:34:23'),
(4, 'Appointments', 'Cancellation & Rescheduling', 'cancel appointment,reschedule appointment,change appointment,modify appointment,postpone', 'directive', '--- 🔄 MODIFY YOUR APPOINTMENT ---\n\nTO CANCEL OR RESCHEDULE:\n\n1️⃣ Log in to your account\n2️⃣ Go to \"Book Appointments\"\n3️⃣ Select the appointment\n4️⃣ Choose your action:\n   • 🔄 Reschedule\n   • ❌ Cancel\n\n--- ⏰ CANCELLATION POLICY ---\n\nPlease notify us at least 24 hours in advance to avoid cancellation fees.\n\n--- 📞 NEED IMMEDIATE HELP? ---\n\nCall us: +60 12-345 6789\n\nWe\'re here to help! 💙', 'detailed', 1, 9, 1, '2025-12-28 11:34:22', '2025-12-29 04:34:42'),
(5, 'Appointments', 'Appointment Status', 'appointment status,when is my appointment,upcoming appointment,check appointment,my appointments,view appointments', 'directive', '--- 📋 YOUR APPOINTMENTS ---\n\nTO VIEW YOUR APPOINTMENTS:\n\n1️⃣ Log in to your account\n2️⃣ Go to \"Book Appointments\"\n\n--- 📊 YOU WILL FIND ---\n\n📅 ALL\n   All appointments history\n \n⚠️ PENDING\n   Pending scheduled appointments\n \n☑️ CONFIRM\n   Confirmed scheduled appointments\n\n✅ COMPLETED\n   Past visits and consultations\n\n❌ CANCELLED\n   Previously cancelled bookings\n\n🏻 QR CODES\n   For easy clinic check-in\n\n💡 TIP: Save your QR code for quick check-in!\n\nAnything else I can help with? 😊', 'moderate', 1, 8, 1, '2025-12-28 11:34:22', '2025-12-29 04:35:20'),
(6, 'Clinic Info', 'Operating Hours', 'opening hours,closing time,when open,business hours,operating hours,clinic hours,schedule,timings', 'informative', '--- 🕐 CLINIC OPERATING HOURS ---\n\n📅 WEEKDAYS\nMonday - Saturday\n🕐 9:00 AM - 8:00 PM\n\n🚫 SUNDAY & PUBLIC HOLIDAYS\nClosed for Sunday and public holidays\n\n--- 💻 ONLINE BOOKING ---\n\nAvailable 24/7 through our website!\n\n--- 📞 CALL US ---\n\n+60 12-345 6789\n(During operating hours)\n\nPlan your visit today! ✨', 'basic', 0, 8, 1, '2025-12-28 11:34:22', '2026-01-08 14:07:24'),
(7, 'Clinic Info', 'Location & Directions', 'where are you located,address,location,directions,how to get there,where is clinic,find clinic', 'informative', '--- 📍 FIND OUR CLINIC ---\n\n🏥 ADDRESS\nKlinik Careclinics Ipoh\n1, Jln Sultan Nazrin Shah,\nMedan Gopeng, 31350 Ipoh,\nPerak,\nMalaysia\n\n--- 🗺️ NEED DIRECTIONS? ---\n\nCall us: +60 12-345 6789\n\nWe\'ll guide you! 💙', 'detailed', 0, 8, 1, '2025-12-28 11:34:22', '2025-12-29 04:37:13'),
(8, 'Clinic Info', 'Services & Treatments', 'what services,available services,treatments,procedures,what do you offer,clinic services,medical services', 'informative', '--- 🏥 OUR MEDICAL SERVICES ---\n\nOur service include:\n\n--- 👨‍⚕️ GENERAL CONSULTATIONS ---\n   • General Health Checkups\n   • Follow-up Visits\n   • Medical Certificates\n\n--- 🔬 DIAGNOSTIC SERVICES ---\n   • Blood Tests & Lab Work\n\n--- 💉 PREVENTIVE CARE ---\n   • Adult Vaccinations\n   • Child Immunizations\n   • Travel Vaccines\n\n--- 🔧 MINOR PROCEDURES ---\n   • Wound Care & Stitches\n   • Minor Surgeries\n   • Dressings & Bandaging\n   • Injections\n\nWhich service are you interested in? 💡', 'detailed', 0, 8, 1, '2025-12-28 11:34:22', '2025-12-29 04:39:35'),
(9, 'Clinic Info', 'Contact Information', 'phone number,contact,email,how to reach,customer service,call,reach you,contact details', 'informative', '--- 📞 CONTACT CAREPLUS ---\nYou can contact us through:\n\n--- ☎️ PHONE ---\n+60 12-345 6789\n\n🕐 Available Hours:\nMon–Sat: 9:00 AM – 5:00 PM\n\n--- 📧 EMAIL ---\nsupport@careplus.com\n⏱️ Response Time: Within 24 hours\n\n--- 💬 LIVE CHAT / CHATBOT ---\n🤖 Chatbot available 24/7  \n💬 Live agent support:\nMon–Sat: 9:00 AM – 8:00 PM\n\n--- 🏥 VISIT US ---\nKlinik Careclinics Ipoh  \n1, Jln Sultan Nazrin Shah,  \nMedan Gopeng, 31350 Ipoh,  \n31350 Ipoh, Perak,  \nMalaysia\n\nWe\'re here to help anytime! 💙', 'detailed', 0, 8, 1, '2025-12-28 11:34:22', '2026-01-08 14:09:01'),
(10, 'Doctors', 'Find a Doctor', 'find doctor,available doctors,doctor list,specialists,which doctor,search doctor,look for doctor', 'directive', '--- 👨‍⚕️ FIND YOUR DOCTOR ---\n\nHOW TO SEARCH:\n\n1️⃣ Visit \"Find a Doctor\" page\n2️⃣ Use our smart filters:\n   • 🔍 Search by name\n   • ⚕️ Filter by specialty\n   • 📅 Check availability\n   • ⭐ Sort by ratings\n\n3️⃣ View detailed profiles\n4️⃣ Book directly with your choice\n\n--- ⚕️ COMMONLY REFERRED SPECIALTIES ---\n\n(Available via referral to partner hospitals and specialists)\n\n❤️ Cardiology\n🦴 Orthopedics\n👶 Pediatrics\n💆 Dermatology\n👁️ Ophthalmology\n🦷 Dental\n🧠 Neurology\n👨 General Practice\n\n--- 💡 DOCTOR PROFILES INCLUDE ---\n   • Qualifications & experience\n   • Patient ratings & reviews\n   • Available time slots\n   • Consultation fees\n\nWhat specialty do you need? 😊', 'detailed', 0, 7, 1, '2025-12-28 11:34:22', '2025-12-29 04:43:02'),
(11, 'Doctors', 'Doctor Availability', 'doctor available,when is doctor free,doctor schedule,consultation hours,available time,appointment slots', 'directive', '--- 📅 CHECK DOCTOR AVAILABILITY ---\n\nTO VIEW AVAILABLE SLOTS:\n\n1️⃣ Log in to your account\n2️⃣ Go to \"Book Appointment\"\n3️⃣ Select your preferred doctor\n4️⃣ View calendar with available times\n\n--- 💡 HELPFUL TIPS ---\n\n• Book early for popular doctors\n• Weekday mornings fill up fast\n• Evening slots available Mon-Fri\n• Saturday half-day service\n• Check back for cancellations\n\nNeed help finding an available doctor? \nJust let me know! 😊', 'moderate', 0, 7, 1, '2025-12-28 11:34:22', '2025-12-29 04:43:57'),
(12, 'Health Info', 'General Health Tips', 'health tips,healthy living,wellness,prevention,stay healthy,health advice,wellness tips', 'informative', '--- 💪 YOUR WELLNESS GUIDE ---\n\nOur wellness guide include:\n--- 🥗 NUTRITION ESSENTIALS ---\n\n• Eat 5 servings of fruits & vegetables daily\n• Choose whole grains over refined\n• Include lean proteins (fish, chicken, beans)\n• Limit sugar, salt, and processed foods\n• Drink 8 glasses of water daily\n\n--- 🏃 STAY ACTIVE ---\n\n• 150 minutes of moderate exercise weekly\n• Include strength training 2x/week\n• Take 10,000 steps daily\n• Stretch for flexibility\n• Mix cardio with strength workouts\n\n--- 😴 QUALITY SLEEP ----\n\n• Sleep 7-9 hours nightly\n• Maintain consistent sleep schedule\n• Create relaxing bedtime routine\n• Keep bedroom cool & dark\n• Avoid screens 1 hour before bed\n\n--- 🧘 MENTAL WELLNESS ---\n\n• Practice stress management\n• Try meditation or yoga\n• Stay socially connected\n• Take breaks when needed\n• Seek help when struggling\n\n--- 🩺 PREVENTIVE CARE ---\n\n• Annual health checkups\n• Stay updated on vaccinations\n• Regular dental visits\n• Eye exams every 2 years\n• Screen for health conditions\n\n⚠️ IMPORTANT: These are general tips. \n\nFor personalized advice, book a consultation! 💙', 'detailed', 0, 6, 1, '2025-12-28 11:34:22', '2025-12-29 04:48:58'),
(13, 'Health Info', 'Vaccination Information', 'vaccine,vaccination,immunization,shots,flu shot,covid vaccine,jab,injections', 'informative', '--- 💉 VACCINATION SERVICES ---\n\nOur vaccination services include:\n\n-- 👶 CHILDHOOD VACCINES ---\n\nEssential immunizations:\n• Birth - BCG, Hepatitis B\n• 2-6 months - DTP, Polio, Hib\n• 9-12 months - MMR\n• 18 months - Boosters\n• School entry - Required vaccines\n\n--- 👨‍💼 ADULT VACCINES ---\n\nStay protected with:\n• 💉 Annual Flu Shot (Recommended yearly)\n• 🦠 COVID-19 Vaccines & Boosters\n• 💪 Tetanus Booster (Every 10 years)\n• 🦠 Pneumonia Vaccine (For 65+)\n• 🦠 Shingles Vaccine (For 50+)\n\n--- ✈️ TRAVEL VACCINES ---\n\nProtect yourself abroad:\n• Hepatitis A & B\n• Typhoid\n• Yellow Fever\n• Japanese Encephalitis\n• Rabies (if needed)\n\n📋 Consult 4-6 weeks before travel!\n\n--- 🏥 HOW TO GET VACCINATED ---\n\n1️⃣ Consult with our doctor\n3️⃣ Receive vaccination\n4️⃣ Get vaccination record card\n5️⃣ Schedule follow-up if needed\n\n---💡 BRING WITH YOU ---\n• Previous vaccination records\n• NRIC \n\nReady to protect your health? \nBook your vaccination today! 🛡️', 'detailed', 0, 6, 1, '2025-12-28 11:34:22', '2025-12-29 04:48:33'),
(14, 'Health Info', 'Preventive Health Checkup', 'health checkup,screening,preventive care,annual checkup,medical exam,physical exam,full body checkup', 'informative', '--- 🩺 HEALTH SCREENING PACKAGES ---\n\nOur health screening packages include:\n\n--- ✅ BASIC HEALTH SCREENING ---\nPerfect for healthy adults\n\nIncludes:\n• Blood Pressure Check\n• BMI & Weight Assessment\n• Blood Sugar Test (Fasting)\n• Cholesterol Panel\n• Kidney Function Test\n• Liver Function Test\n\n💰 Starting from RM 150\n\n--- 🔬 COMPREHENSIVE PACKAGE ---\nComplete health assessment\n\nEverything in Basic, plus:\n• Complete Blood Count\n• Thyroid Function\n• Uric Acid Test\n• Urine Analysis\n• Doctor Consultation\n\n💰 Starting from RM 250\n\n--- ❤️ CARDIAC HEALTH PACKAGE ---\nFor heart health screening\n\nIncludes:\n• Detailed Lipid Profile\n• Cardiac Enzymes\n• Stress Test\n• Cardiologist Consultation\n\n💰 Starting from RM 450\n\n--- 👨 MEN\'S HEALTH | 👩 WOMEN\'S HEALTH ---\nGender-specific screenings:\n\n• Prostate Cancer Screening (Men)\n• Breast & Cervical Screening (Women)\n• Hormonal Tests\n• Reproductive Health Check\n\n--- 📅 RECOMMENDED FREQUENCY ---\n\n✓ Healthy Adults (18-40): Every 2 years\n✓ Adults with Risk Factors: Annually\n✓ Seniors (60+): Every 6 months\n\n--- 💡 PREPARATION TIPS ---\n\n• Fast 8-10 hours before test\n• Bring previous medical reports\n• Wear comfortable clothing\n• Stay hydrated\n\nBook your health screening now! \nEarly detection saves lives! 💙', 'detailed', 0, 6, 1, '2025-12-28 11:34:22', '2025-12-29 04:51:58'),
(15, 'Account', 'Login Issues', 'cannot login,forgot password,login problem,password reset,cant access,login error', 'directive', '--- 🔐 LOGIN ASSISTANCE ---\n\n🔑 FORGOT PASSWORD?\n\nFollow these steps to recover your account:\n\n1️⃣ Click \"Forgot Password\" on the login page  \n2️⃣ Enter your registered details:\n   • Email address  \n   • IC Number (MyKad – 12 digits)  \n   • Phone number  \n3️⃣ Tap **Continue to Security Question**  \n4️⃣ Answer your security question  \n   (Answers are not case-sensitive)\n5️⃣ Reset your password  \n6️⃣ Log in with your new credentials\n\n--- 🔎 VERIFY YOUR IDENTITY ---\nTo protect your account, all details must match what was registered.\n\n--- ❌ STILL HAVING TROUBLE? ---\n💬 Chatbot Support:\nAvailable 24/7\n\n📞 Call Us:\n+60 12-345 6789  \n🕐 Mon–Sat: 9:00 AM – 5:00 PM\n\n⏱️ RESPONSE TIME:\nDuring business hours, we typically respond within 2–4 hours.\n\n--- 💡 SECURITY TIP ---\nChoose a strong password with uppercase, lowercase, numbers, and symbols.\n\nWe\'re here to help! 💙', 'detailed', 0, 7, 1, '2025-12-28 11:34:22', '2026-01-08 14:11:50'),
(16, 'Account', 'Registration', 'create account,sign up,register,new user,join,new account,signup,become member', 'directive', '--- ✨ JOIN CAREPLUS FAMILY ---\n\n🎉 GETTING STARTED IS EASY!\n\n1️⃣ REGISTER (2 minutes)\n\n• Click \"Register\" on homepage\n• Fill in basic information:\n  - Full Name\n  - Email Address\n  - Phone Number\n  - IC Number\n  - Date of Birth\n• Create secure password\n\n2️⃣ COMPLETE PROFILE\n\n• Add emergency contact\n• Upload profile photo (optional)\n• Set communication preferences\n• Add medical history (optional)\n\n--- 🎁 MEMBER BENEFITS ---\n\n✅ Easy online booking 24/7\n✅ View medical records anytime\n✅ Access prescription history\n✅ Receive appointment reminders\n✅ Track health screening results\n✅ Get exclusive health tips\n✅ Priority support\n\n🆓 100% FREE\n💯 Secure & Private\n⚡ Takes only 2 minutes!\n\nReady to start your health journey? \nCreate your account today! 💙', 'detailed', 0, 7, 1, '2025-12-28 11:34:22', '2025-12-29 04:54:11'),
(17, 'Payment', 'Payment Methods', 'payment method,how to pay,accepted payment,payment options,pay,billing,payment types', 'informative', '--- 💳 PAYMENT OPTIONS ---\n\nWe make payment easy and convenient!\n\n--- 💵 CASH ---\n\n• Pay at clinic reception\n• Receipts provided\n• Change available\n\n--- 📱 E-WALLETS ---\n\nPopular options:\n• Touch n Go eWallet\n• GrabPay\n• Boost\n• ShopeePay\n• MAE by Maybank\n\n--- ⏰ WHEN TO PAY ---\n\n• Walk-in: Pay after consultation\n\n--- 📝 RECEIPT & INVOICE ---\n\n• Instant receipt upon payment\n• Detailed invoice for claims\n• Downloadable from account\n\nQuestions about payment? \nCall us: +60 12-345 6789 💙', 'detailed', 0, 6, 1, '2025-12-28 11:34:22', '2026-01-08 13:53:00'),
(18, 'Payment', 'Consultation Fees', 'how much,consultation fee,cost,price,charges,fees,expensive,rates,pricing', 'informative', '--- 💰 CONSULTATION FEES ---\n\nOur pricing is transparent & affordable!\n\n--- 👨‍⚕️ GENERAL PRACTICE ---\n\nWalk-in Consultation\nStarting from RM 50\n\nIncludes:\n• Doctor consultation\n• Basic examination\n• Medical advice\n• Medical certificate (if needed)\n\n--- ⚕️ SPECIALIST CONSULTATION ---\n\nFees vary by specialty:\n\n❤️ Cardiology: RM 120 - RM 200\n🦴 Orthopedics: RM 100 - RM 180\n👶 Pediatrics: RM 80 - RM 150\n💆 Dermatology: RM 90 - RM 160\n👁️ Ophthalmology: RM 100 - RM 180\n🧠 Neurology: RM 150 - RM 250\n\n--- 🔬 ADDITIONAL SERVICES ---\n\nLab Tests: Varies by test\nX-Ray: RM 80 - RM 150\nECG: RM 50 - RM 80\nUltrasound: RM 150 - RM 300\n\n--- 💉 PROCEDURES ---\n\nMinor procedures:\n• Wound dressing: RM 30+\n• Injections: RM 20+\n• Stitches removal: RM 50+\n\n--- 📋 HOW TO CHECK EXACT FEES ---\n\n1️⃣ View during booking process\n2️⃣ Check doctor profiles\n3️⃣ Call us: +60 12-345 6789\n4️⃣ Email: support@careplus.com\n\n--- 🏥 INSURANCE COVERAGE ---\n\nMost services covered by:\n• Corporate insurance panels\n• Personal medical insurance\n• Employee health benefits\n\n💡 Check with your insurer for coverage details!\n\nAll fees shown BEFORE you confirm! \nNo hidden charges! ✨', 'detailed', 0, 6, 1, '2025-12-28 11:34:22', '2025-12-29 04:56:45'),
(19, 'Emergency', 'Emergency Situations', 'emergency,urgent,severe pain,heart attack,stroke,cant breathe,chest pain,severe bleeding,help me,critical', 'directive', '--- 🚨 EMERGENCY RESPONSE ---\n\n⚠️ THIS IS A MEDICAL EMERGENCY!\n\n--- 🆘 CALL 999 IMMEDIATELY ---\n\nFor life-threatening conditions:\n\n❗ Severe chest pain/pressure\n❗ Difficulty breathing/choking\n❗ Heavy bleeding that won\'t stop\n❗ Loss of consciousness\n❗ Stroke symptoms (F.A.S.T.)\n❗ Severe allergic reaction\n❗ Seizures\n❗ Severe burns\n❗ Major trauma/accidents\n\n--- 🚑 WHILE WAITING FOR AMBULANCE ---\n\nDO:\n✓ Stay calm\n✓ Call for help\n✓ Follow 999 operator instructions\n✓ Keep person comfortable\n✓ Monitor breathing\n✓ Apply pressure if bleeding\n\nDON\'T:\n✗ Move injured person (unless unsafe)\n✗ Give food/drink\n✗ Try to drive yourself\n\n--- 🏥 NEAREST EMERGENCY ROOMS ---\n\nPantai Hospital Ipoh\nEmergency: +605-549 9911 (24-hour A&E)\nRaja Permaisuri Bainun Hospital Ipoh\nEmergency: +605-285 7000\n\n⏰ DO NOT WAIT \n⏰ DO NOT USE THIS CHAT FOR EMERGENCIES \n\nFor NON-emergencies:\n📞 CarePlus: +60 12-345 6789\n📅 Book urgent appointment online', 'basic', 0, 10, 1, '2025-12-28 11:34:22', '2026-01-08 14:14:28');

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `contact_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('new','read','closed') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_submissions`
--

INSERT INTO `contact_submissions` (`contact_id`, `name`, `email`, `phone`, `subject`, `message`, `ip_address`, `status`, `created_at`) VALUES
(1, 'LEN PEI YING', 'peiying.len@qiu.edu.my', '+60 01-750 2359', 'appointment', 'help', '::1', 'new', '2026-01-05 09:20:41'),
(2, 'Len Pei Ying', 'peiying.len@qiu.edu.my', '+60 01-750 2359', 'billing', 'hi', '::1', 'new', '2026-01-10 18:39:04'),
(3, 'Len Pei Ying', 'peiying.len@qiu.edu.my', '+60 01-750 2359', 'billing', 'hi', '::1', 'new', '2026-01-10 18:45:18');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ic_number` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) NOT NULL,
  `qualifications` text DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','on_leave') DEFAULT 'active',
  `phone` varchar(20) NOT NULL,
  `consultation_fee` decimal(10,2) DEFAULT 0.00,
  `available_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_days`)),
  `available_hours` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `user_id`, `ic_number`, `date_of_birth`, `gender`, `first_name`, `last_name`, `specialization`, `license_number`, `qualifications`, `bio`, `experience_years`, `profile_picture`, `status`, `phone`, `consultation_fee`, `available_days`, `available_hours`, `created_at`) VALUES
(1, 2, '770412086851', '1977-04-12', 'male', 'Ho', 'Teik Kok', 'General Practitioner', 'MMC0001', '• Bachelor of Medicine &amp;amp;amp;amp;amp;amp; Bachelor of Surgery (MBBS)• Fully Registered with Malaysian Medical Council (MMC)• General Practitioner with 10 years of clinical experience', 'Dr. Ho Teik Kok is an experienced General Practitioner with over 10 years of clinical practice. He holds a Bachelor of Medicine and Bachelor of Surgery (MBBS) and is fully registered with the Malaysian Medical Council (MMC). Dr. Ho provides comprehensive primary healthcare services, focusing on accurate diagnosis, preventive care, and personalized treatment plans. He is committed to delivering compassionate, patient-centered care and promoting long-term health and well-being for individuals and families.', 10, 'uploads/profiles/doctor_2_1767606074.png', 'active', '0123082894', 150.00, '[\"Monday\",\"Tuesday\",\"Wednesday\",\"Thursday\",\"Friday\",\"Saturday\",\"Sunday\"]', '09:00-20:00', '2026-01-05 09:36:21'),
(2, 5, '800820080327', '1980-08-20', 'male', 'Lee', 'Kim Seng', 'Pediatrician', 'MMC0002', 'MD (Mal), MRCP (Paeds-UK), MRCPCH (UK)', 'Dr. Lee Kim Seng is an experienced Pediatrician with 10 years of practice in pediatric medicine. He is dedicated to delivering comprehensive and compassionate healthcare for children of all ages.', 10, 'uploads/profiles/doctor_5_1767891283.png', 'active', '01165131218', 100.00, '[\"Monday\",\"Tuesday\",\"Wednesday\",\"Thursday\",\"Friday\"]', '09:00-20:00', '2026-01-08 16:33:48');

-- --------------------------------------------------------

--
-- Table structure for table `lab_test_catalog`
--

CREATE TABLE `lab_test_catalog` (
  `test_id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL COMMENT 'Blood Test, Urine Test, Imaging, etc.',
  `price` decimal(10,2) NOT NULL,
  `turnaround_time` varchar(50) DEFAULT NULL COMMENT 'Same day, 24 hours, 3 days, etc.',
  `sample_type` varchar(100) DEFAULT NULL COMMENT 'Blood, Urine, etc.',
  `fasting_required` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_test_catalog`
--

INSERT INTO `lab_test_catalog` (`test_id`, `test_name`, `category`, `price`, `turnaround_time`, `sample_type`, `fasting_required`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Full Blood Count (FBC)', 'Hematology', 35.00, 'Same day', 'Blood', 0, 'Complete blood count including WBC, RBC, platelets, hemoglobin', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(2, 'Lipid Profile', 'Blood Chemistry', 60.00, 'Same day', 'Blood', 1, 'Total cholesterol, HDL, LDL, triglycerides', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(3, 'Fasting Blood Sugar (FBS)', 'Blood Chemistry', 20.00, 'Same day', 'Blood', 1, 'Fasting glucose level test', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(4, 'HbA1c', 'Blood Chemistry', 55.00, '1 day', 'Blood', 0, '3-month average blood sugar control for diabetes', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(5, 'Liver Function Test (LFT)', 'Blood Chemistry', 70.00, 'Same day', 'Blood', 0, 'AST, ALT, ALP, bilirubin, albumin', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(6, 'Renal Function Test (RFT)', 'Blood Chemistry', 60.00, 'Same day', 'Blood', 0, 'Urea, creatinine, electrolytes', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(7, 'Thyroid Function Test (TFT)', 'Endocrinology', 120.00, '2 days', 'Blood', 0, 'TSH, T3, T4 levels', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(8, 'Uric Acid', 'Blood Chemistry', 30.00, 'Same day', 'Blood', 0, 'For gout diagnosis', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(9, 'Dengue NS1 Antigen + IgG/IgM', 'Serology', 90.00, 'Same day', 'Blood', 0, 'Dengue fever screening', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(10, 'Hepatitis B Screening', 'Serology', 80.00, '1 day', 'Blood', 0, 'HBsAg test for Hepatitis B', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(11, 'HIV Screening', 'Serology', 100.00, '2 days', 'Blood', 0, 'HIV antibody test', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(12, 'COVID-19 RT-PCR', 'Molecular', 150.00, 'Same day', 'Nasopharyngeal swab', 0, 'PCR test for COVID-19', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(13, 'COVID-19 Rapid Antigen Test', 'Rapid Test', 50.00, '15 minutes', 'Nasopharyngeal swab', 0, 'Quick COVID-19 screening', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(14, 'Urine Full Examination & Microscopy (UFEME)', 'Urinalysis', 25.00, 'Same day', 'Urine', 0, 'Complete urine analysis', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(15, 'Urine Pregnancy Test', 'Rapid Test', 20.00, '5 minutes', 'Urine', 0, 'Pregnancy detection', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(16, 'Urine Culture & Sensitivity', 'Microbiology', 80.00, '3 days', 'Urine', 0, 'For UTI diagnosis and antibiotic selection', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(17, 'Stool Full Examination', 'Microbiology', 30.00, '1 day', 'Stool', 0, 'Stool analysis for parasites and blood', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(18, 'Stool Culture', 'Microbiology', 100.00, '3 days', 'Stool', 0, 'Bacterial culture for gastroenteritis', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(19, 'X-Ray (Single View)', 'Imaging', 80.00, 'Same day', 'N/A', 0, 'Single x-ray image', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(20, 'X-Ray (Two Views)', 'Imaging', 120.00, 'Same day', 'N/A', 0, 'Two-view x-ray', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(21, 'Ultrasound (Abdomen)', 'Imaging', 180.00, 'Same day', 'N/A', 1, 'Abdominal ultrasound scan', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(22, 'ECG (Electrocardiogram)', 'Cardiac', 60.00, 'Same day', 'N/A', 0, '12-lead ECG', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23'),
(23, '2D Echo', 'Cardiac', 350.00, 'Same day', 'N/A', 0, 'Echocardiogram for heart assessment', 1, '2025-12-28 11:56:23', '2025-12-28 11:56:23');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `lab_results` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`record_id`, `patient_id`, `doctor_id`, `appointment_id`, `visit_date`, `diagnosis`, `symptoms`, `prescription`, `lab_results`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, '2026-01-05', 'Acute Viral Fever', 'Fever', '1. Paracetamol 500mg, take 1 tablet every 6–8 hours if fever persists.\n2. Adequate rest and increased fluid intake.\n3. Monitor body temperature daily', 'No laboratory tests performed', 'Patient advised to return if fever persists for more than 3 days.', '2026-01-05 11:21:53', '2026-01-05 11:21:53');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_catalog`
--

CREATE TABLE `medicine_catalog` (
  `medicine_id` int(11) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL COMMENT 'Antibiotic, Painkiller, etc.',
  `dosage_form` varchar(50) DEFAULT NULL COMMENT 'Tablet, Syrup, Injection, etc.',
  `strength` varchar(50) DEFAULT NULL COMMENT '500mg, 10ml, etc.',
  `unit_price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `requires_prescription` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicine_catalog`
--

INSERT INTO `medicine_catalog` (`medicine_id`, `medicine_name`, `generic_name`, `category`, `dosage_form`, `strength`, `unit_price`, `stock_quantity`, `reorder_level`, `requires_prescription`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Augmentin', 'Amoxicillin + Clavulanate', 'Antibiotic', 'Tablet', '625mg', 3.50, 172, 10, 1, 'Broad-spectrum antibiotic for bacterial infections', 1, '2025-12-28 11:55:37', '2025-12-28 15:03:40'),
(2, 'Azithromycin', 'Azithromycin', 'Antibiotic', 'Tablet', '500mg', 4.20, 150, 10, 1, 'Antibiotic for respiratory and skin infections', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(3, 'Amoxicillin', 'Amoxicillin', 'Antibiotic', 'Capsule', '500mg', 2.80, 300, 10, 1, 'Common antibiotic for various infections', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(4, 'Panadol', 'Paracetamol', 'Painkiller', 'Tablet', '500mg', 0.50, 460, 10, 0, 'Pain relief and fever reducer', 1, '2025-12-28 11:55:37', '2025-12-28 15:03:40'),
(5, 'Panadol Actifast', 'Paracetamol', 'Painkiller', 'Tablet', '500mg', 1.20, 300, 10, 0, 'Fast-acting pain and fever relief', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(6, 'Ponstan', 'Mefenamic Acid', 'Painkiller', 'Tablet', '500mg', 1.80, 200, 10, 1, 'For moderate to severe pain, menstrual cramps', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(7, 'Arcoxia', 'Etoricoxib', 'Painkiller', 'Tablet', '90mg', 3.50, 100, 10, 1, 'Anti-inflammatory for arthritis and pain', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(8, 'Clarinase', 'Loratadine + Pseudoephedrine', 'Antihistamine', 'Tablet', '5mg/120mg', 2.50, 200, 10, 0, 'Relieves nasal congestion and allergies', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(9, 'Piriton', 'Chlorpheniramine', 'Antihistamine', 'Tablet', '4mg', 0.80, 300, 10, 0, 'For allergic rhinitis and itching', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(10, 'Dextromethorphan Syrup', 'Dextromethorphan', 'Cough Syrup', 'Syrup', '100ml', 8.50, 100, 10, 0, 'Cough suppressant', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(11, 'Bisolvon', 'Bromhexine', 'Cough Medicine', 'Tablet', '8mg', 1.50, 150, 10, 0, 'Expectorant for productive cough', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(12, 'Nexium', 'Esomeprazole', 'Antacid', 'Tablet', '40mg', 4.80, 120, 10, 1, 'For GERD and stomach ulcers', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(13, 'Mylanta', 'Aluminium + Magnesium Hydroxide', 'Antacid', 'Liquid', '200ml', 12.00, 80, 10, 0, 'Relief from heartburn and indigestion', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(14, 'Buscopan', 'Hyoscine', 'Antispasmodic', 'Tablet', '10mg', 1.20, 150, 10, 0, 'Relieves stomach cramps and IBS', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(15, 'Imodium', 'Loperamide', 'Anti-diarrheal', 'Capsule', '2mg', 2.00, 100, 10, 0, 'Treats acute diarrhea', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(16, 'Metformin', 'Metformin', 'Antidiabetic', 'Tablet', '500mg', 0.60, 500, 10, 1, 'First-line treatment for Type 2 diabetes', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(17, 'Glucophage', 'Metformin', 'Antidiabetic', 'Tablet', '850mg', 1.20, 300, 10, 1, 'Extended-release for diabetes control', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(18, 'Norvasc', 'Amlodipine', 'Antihypertensive', 'Tablet', '5mg', 1.50, 400, 10, 1, 'Calcium channel blocker for high blood pressure', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(19, 'Losartan', 'Losartan', 'Antihypertensive', 'Tablet', '50mg', 1.80, 350, 10, 1, 'ARB for hypertension and kidney protection', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(20, 'Vitamin C 1000mg', 'Ascorbic Acid', 'Vitamin', 'Tablet', '1000mg', 0.80, 500, 10, 0, 'Immune support and antioxidant', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(21, 'Multivitamin', 'Mixed Vitamins', 'Vitamin', 'Tablet', 'Daily', 1.50, 400, 10, 0, 'Complete daily vitamin supplement', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(22, 'Calcium + Vitamin D', 'Calcium + Cholecalciferol', 'Supplement', 'Tablet', '600mg/400IU', 1.20, 300, 10, 0, 'Bone health support', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(23, 'Betadine Solution', 'Povidone Iodine', 'Antiseptic', 'Solution', '60ml', 8.50, 100, 10, 0, 'Wound disinfectant', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(24, 'Acyclovir Cream', 'Acyclovir', 'Antiviral', 'Cream', '5g', 15.00, 50, 10, 1, 'For cold sores and herpes', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(25, 'Hydrocortisone Cream', 'Hydrocortisone', 'Steroid', 'Cream', '15g', 12.00, 80, 10, 0, 'For skin inflammation and rashes', 1, '2025-12-28 11:55:37', '2025-12-28 11:55:37'),
(26, 'Paracetamol', NULL, NULL, 'Tablet', '500mg', 0.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(27, 'Paracetamol', NULL, NULL, 'Tablet', '650mg', 0.60, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(28, 'Ibuprofen', NULL, NULL, 'Tablet', '200mg', 0.80, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(29, 'Ibuprofen', NULL, NULL, 'Tablet', '400mg', 1.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(30, 'Aspirin', NULL, NULL, 'Tablet', '100mg', 0.40, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(31, 'Aspirin', NULL, NULL, 'Tablet', '300mg', 0.60, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(32, 'Amoxicillin', NULL, NULL, 'Capsule', '250mg', 2.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(33, 'Amoxicillin', NULL, NULL, 'Capsule', '500mg', 3.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(34, 'Azithromycin', NULL, NULL, 'Tablet', '250mg', 5.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(35, 'Azithromycin', NULL, NULL, 'Tablet', '500mg', 8.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(36, 'Ciprofloxacin', NULL, NULL, 'Tablet', '500mg', 4.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(37, 'Cephalexin', NULL, NULL, 'Capsule', '500mg', 3.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(38, 'Erythromycin', NULL, NULL, 'Tablet', '250mg', 2.80, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(39, 'Cetirizine', NULL, NULL, 'Tablet', '10mg', 1.20, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(40, 'Loratadine', NULL, NULL, 'Tablet', '10mg', 1.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(41, 'Chlorpheniramine', NULL, NULL, 'Tablet', '4mg', 0.80, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(42, 'Fexofenadine', NULL, NULL, 'Tablet', '120mg', 2.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(43, 'Fexofenadine', NULL, NULL, 'Tablet', '180mg', 3.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(44, 'Omeprazole', NULL, NULL, 'Capsule', '20mg', 2.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(45, 'Omeprazole', NULL, NULL, 'Capsule', '40mg', 3.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(46, 'Ranitidine', NULL, NULL, 'Tablet', '150mg', 1.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(47, 'Antacid', NULL, NULL, 'Suspension', '200ml', 5.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(48, 'Loperamide', NULL, NULL, 'Capsule', '2mg', 1.20, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(49, 'Metoclopramide', NULL, NULL, 'Tablet', '10mg', 1.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(50, 'Domperidone', NULL, NULL, 'Tablet', '10mg', 1.80, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(51, 'Salbutamol', NULL, NULL, 'Inhaler', '100mcg', 15.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(52, 'Salbutamol', NULL, NULL, 'Syrup', '2mg/5ml', 8.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(53, 'Cetirizine', NULL, NULL, 'Syrup', '5mg/5ml', 10.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(54, 'Dextromethorphan', NULL, NULL, 'Syrup', '15mg/5ml', 12.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(55, 'Guaifenesin', NULL, NULL, 'Syrup', '100mg/5ml', 9.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(56, 'Amlodipine', NULL, NULL, 'Tablet', '5mg', 2.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(57, 'Amlodipine', NULL, NULL, 'Tablet', '10mg', 3.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(58, 'Atenolol', NULL, NULL, 'Tablet', '50mg', 2.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(59, 'Atenolol', NULL, NULL, 'Tablet', '100mg', 4.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(60, 'Enalapril', NULL, NULL, 'Tablet', '5mg', 3.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(61, 'Simvastatin', NULL, NULL, 'Tablet', '20mg', 4.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(62, 'Simvastatin', NULL, NULL, 'Tablet', '40mg', 6.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(63, 'Metformin', NULL, NULL, 'Tablet', '500mg', 1.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(64, 'Metformin', NULL, NULL, 'Tablet', '850mg', 2.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(65, 'Glibenclamide', NULL, NULL, 'Tablet', '5mg', 2.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(66, 'Gliclazide', NULL, NULL, 'Tablet', '80mg', 3.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(67, 'Vitamin C', NULL, NULL, 'Tablet', '500mg', 2.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(68, 'Vitamin C', NULL, NULL, 'Tablet', '1000mg', 3.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(69, 'Vitamin D3', NULL, NULL, 'Tablet', '1000IU', 3.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(70, 'Calcium', NULL, NULL, 'Tablet', '600mg', 2.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(71, 'Multivitamin', NULL, NULL, 'Tablet', 'Standard', 5.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(72, 'Folic Acid', NULL, NULL, 'Tablet', '5mg', 1.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(73, 'Vitamin B Complex', NULL, NULL, 'Tablet', 'Standard', 3.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(74, 'Hydrocortisone', NULL, NULL, 'Cream', '1%', 8.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(75, 'Clotrimazole', NULL, NULL, 'Cream', '1%', 10.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(76, 'Betamethasone', NULL, NULL, 'Cream', '0.1%', 12.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(77, 'Mupirocin', NULL, NULL, 'Ointment', '2%', 15.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(78, 'Chloramphenicol', NULL, NULL, 'Eye Drops', '0.5%', 8.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(79, 'Sodium Chloride', NULL, NULL, 'Eye Drops', '0.9%', 5.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(80, 'Artificial Tears', NULL, NULL, 'Eye Drops', 'Standard', 10.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(81, 'Prednisolone', NULL, NULL, 'Tablet', '5mg', 2.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(82, 'Diazepam', NULL, NULL, 'Tablet', '5mg', 3.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(83, 'Tramadol', NULL, NULL, 'Capsule', '50mg', 4.50, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22'),
(84, 'Diphenhydramine', NULL, NULL, 'Tablet', '25mg', 2.00, 0, 10, 1, NULL, 1, '2025-12-28 15:48:22', '2025-12-28 15:48:22');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` enum('appointment','payment','reminder','general','system','cancellation','reschedule') NOT NULL DEFAULT 'general',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `notification_type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'appointment', 'Appointment Booked', 'Your appointment has been booked for January 6, 2026 at 7:30 PM', 0, '2026-01-05 10:03:59'),
(2, 1, 'appointment', 'Appointment Confirmed', 'Your appointment has been confirmed! Please arrive 10 minutes early.', 0, '2026-01-05 10:47:02'),
(3, 1, 'appointment', 'Appointment Completed', 'Your appointment on Jan 06, 2026 has been marked as completed. You can now view your records.', 0, '2026-01-05 12:16:10'),
(4, 1, 'appointment', 'Appointment Completed', 'Your appointment on Jan 06, 2026 has been marked as completed. You can now view your records.', 0, '2026-01-05 16:48:37'),
(5, 1, 'appointment', 'Appointment Booked', 'Your appointment has been booked for January 8, 2026 at 9:00 AM', 1, '2026-01-06 07:52:36'),
(6, 1, 'appointment', 'Appointment Checked-in', 'You have successfully checked in for your appointment on Jan 08, 2026. Please wait for your name to be called.', 0, '2026-01-06 08:01:03'),
(7, 1, 'appointment', 'Appointment Booked', 'Your appointment has been booked for January 8, 2026 at 2:00 PM', 0, '2026-01-06 09:17:51'),
(8, 1, 'appointment', 'Appointment Confirmed', 'Your appointment has been confirmed! Please arrive 10 minutes early.', 0, '2026-01-06 09:19:32'),
(9, 1, 'appointment', 'Appointment Rescheduled', 'Your appointment has been rescheduled to January 7, 2026 at 9:00 AM', 0, '2026-01-06 09:19:48'),
(10, 1, 'appointment', 'Appointment Rescheduled', 'Your appointment has been rescheduled to January 31, 2026 at 9:00 AM', 0, '2026-01-06 09:20:20'),
(11, 1, 'appointment', 'Appointment Cancelled', 'Your appointment for January 31, 2026 at 9:00 AM has been cancelled.', 1, '2026-01-06 09:20:28'),
(12, 6, 'appointment', 'Appointment Booked', 'Your appointment has been booked for January 12, 2026 at 9:00 AM', 0, '2026-01-09 04:46:46'),
(13, 6, 'appointment', 'Appointment Confirmed', 'Your appointment has been confirmed! Please arrive 10 minutes early.', 0, '2026-01-09 04:46:53'),
(14, 6, 'appointment', 'Appointment Rescheduled', 'Your appointment has been rescheduled to January 12, 2026 at 3:30 PM', 0, '2026-01-09 04:50:22');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `ic_number` varchar(20) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female') NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `user_id`, `first_name`, `last_name`, `ic_number`, `date_of_birth`, `gender`, `phone`, `address`, `emergency_contact`, `profile_picture`, `blood_type`, `allergies`, `created_at`) VALUES
(1, 1, 'Len', 'Pei Ying', '040830080326', '2004-08-30', 'female', '0175023599', 'No 51, Lebuh Bercham Timur (1/17), Taman Rima Gamelan, 31400, Ipoh, Perak', '0112345678', 'uploads/profiles/patient_1_1767606750.jpeg', 'A+', 'Milk', '2026-01-05 09:27:37'),
(2, 6, 'Chan', 'Jade Qi', '051116080000', '2005-11-16', 'female', '0125915579', 'Seri Botani', '0123456789', 'uploads/profiles/patient_6_1767933972.png', 'A+', '', '2026-01-09 01:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','online') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `receipt_number` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_details`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `patient_id`, `appointment_id`, `amount`, `payment_method`, `payment_status`, `receipt_number`, `transaction_id`, `payment_date`, `payment_details`) VALUES
(5, 1, 1, 160.10, 'cash', 'completed', 'RCP-20260106-CB6933', NULL, '2026-01-05 18:46:47', '[{\"item\":\"Consultation Fee\",\"price\":150},{\"item\":\"Paracetamol\",\"price\":4.5},{\"item\":\"Vitamin C\",\"price\":5.6}]'),
(6, 1, 1, 1.00, 'cash', 'pending', 'RCP-20260106-163DA5', NULL, '2026-01-05 19:39:35', '[{\"item\":\"Consultation Fee\",\"price\":1}]'),
(7, 1, 1, 2.00, 'cash', 'completed', 'RCP-20260106-5E053E', 'Trx-0001', '2026-01-06 02:48:20', '[{\"item\":\"Try\",\"price\":2}]');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `diagnosis` varchar(500) NOT NULL,
  `notes` text DEFAULT NULL,
  `verification_code` varchar(20) DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `status` enum('active','fulfilled','expired','cancelled') DEFAULT 'active',
  `prescription_date` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`prescription_id`, `patient_id`, `doctor_id`, `appointment_id`, `diagnosis`, `notes`, `verification_code`, `valid_until`, `status`, `prescription_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'Acute Viral Fever', '1. Increase fluid intake.\r\n2. Get adequate rest.\r\n3. Return for review if fever persists for more than 3 days or symptoms worsen.', '025E1BDC', '2026-02-04', 'active', '2026-01-05 19:25:21', '2026-01-05 11:25:21', '2026-01-05 12:13:04');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_medications`
--

CREATE TABLE `prescription_medications` (
  `medication_id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medication_name` varchar(200) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `frequency` varchar(100) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `instructions` text DEFAULT NULL,
  `quantity_prescribed` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prescription_medications`
--

INSERT INTO `prescription_medications` (`medication_id`, `prescription_id`, `medication_name`, `dosage`, `frequency`, `duration`, `instructions`, `quantity_prescribed`, `created_at`) VALUES
(1, 1, 'Panadol', '500 mg', '3 times daily', '3 days', 'Take after meals. Do not exceed 4,000 mg per day.', 9, '2026-01-05 11:25:21'),
(2, 1, 'Vitamin C', '1000 mg', 'Once daily', '7 days', 'Take after meals.', 7, '2026-01-05 11:25:21');

-- --------------------------------------------------------

--
-- Table structure for table `procedure_catalog`
--

CREATE TABLE `procedure_catalog` (
  `procedure_id` int(11) NOT NULL,
  `procedure_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL COMMENT 'Minor Surgery, Wound Care, etc.',
  `base_price` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL COMMENT 'Preparation needed, materials required',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `procedure_catalog`
--

INSERT INTO `procedure_catalog` (`procedure_id`, `procedure_name`, `category`, `base_price`, `duration_minutes`, `description`, `requirements`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Minor Wound Dressing', 'Wound Care', 30.00, 15, 'Cleaning and dressing of minor wounds', 'Sterile gauze, antiseptic', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(2, 'Suture/Stitching (Simple)', 'Minor Surgery', 150.00, 30, '1-3 stitches for minor lacerations', 'Local anesthesia, suture kit', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(3, 'Suture/Stitching (Complex)', 'Minor Surgery', 300.00, 60, 'Complex wound closure, multiple stitches', 'Local anesthesia, advanced suture techniques', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(4, 'Suture Removal', 'Wound Care', 50.00, 15, 'Removal of stitches after healing', 'Suture removal kit', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(5, 'Burn Dressing', 'Wound Care', 80.00, 30, 'Specialized dressing for burn wounds', 'Burn dressing materials, antiseptic', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(6, 'Intramuscular Injection', 'Injection', 20.00, 10, 'IM injection of medication', 'Syringe, needle, alcohol swab', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(7, 'Intravenous Injection', 'Injection', 50.00, 20, 'IV injection or drip setup', 'IV cannula, infusion set', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(8, 'Subcutaneous Injection', 'Injection', 15.00, 10, 'SubQ injection (e.g., insulin)', 'Syringe, needle', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(9, 'Ear Wax Removal', 'ENT Procedure', 80.00, 20, 'Professional ear cleaning and wax removal', 'Ear irrigation kit or curette', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(10, 'Foreign Body Removal (Simple)', 'Minor Surgery', 100.00, 20, 'Removal of splinter, glass, etc.', 'Sterile instruments, local anesthesia', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(11, 'Ingrown Toenail Treatment', 'Minor Surgery', 200.00, 45, 'Partial nail removal or conservative treatment', 'Surgical instruments, local anesthesia', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(12, 'Abscess Drainage', 'Minor Surgery', 250.00, 45, 'Incision and drainage of abscess', 'Surgical kit, drainage materials', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(13, 'ECG (Electrocardiogram)', 'Diagnostic', 80.00, 15, '12-lead ECG recording', 'ECG machine, electrodes', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(14, 'Nebulization', 'Respiratory', 30.00, 20, 'Breathing treatment for asthma/COPD', 'Nebulizer, medication', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(15, 'Blood Glucose Test (Finger Prick)', 'Diagnostic', 10.00, 5, 'Point-of-care blood sugar test', 'Glucometer, lancet', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(16, 'Blood Pressure Monitoring', 'Diagnostic', 10.00, 5, 'Blood pressure measurement', 'BP monitor', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59'),
(17, 'Vaccination Service', 'Vaccination', 25.00, 15, 'Administration of vaccine', 'Vaccine, syringe, cold storage', 1, '2025-12-28 11:55:59', '2025-12-28 11:55:59');

-- --------------------------------------------------------

--
-- Table structure for table `qr_code_history`
--

CREATE TABLE `qr_code_history` (
  `history_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `generated_at` datetime DEFAULT current_timestamp(),
  `generated_by` int(11) DEFAULT NULL,
  `action` enum('generated','regenerated','revoked') DEFAULT 'generated',
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_code_history`
--

INSERT INTO `qr_code_history` (`history_id`, `appointment_id`, `qr_code`, `generated_at`, `generated_by`, `action`, `reason`) VALUES
(1, 1, 'APT-0DA70293E65D', '2026-01-05 18:03:59', 1, 'generated', 'New appointment booking'),
(2, 2, 'APT-DAF142369E05', '2026-01-06 15:52:36', 1, 'generated', 'New appointment booking'),
(3, 3, 'APT-1DD5F274999B', '2026-01-06 17:17:51', 1, 'generated', 'New appointment booking'),
(4, 3, 'APT-1DD5F274999B', '2026-01-06 17:19:48', 1, 'regenerated', 'Appointment rescheduled'),
(5, 3, 'APT-1DD5F274999B', '2026-01-06 17:20:20', 1, 'regenerated', 'Appointment rescheduled'),
(6, 4, 'APT-BEC43DE18085', '2026-01-09 12:46:46', 6, 'generated', 'New appointment booking'),
(7, 4, 'APT-BEC43DE18085', '2026-01-09 12:50:22', 6, 'regenerated', 'Appointment rescheduled');

-- --------------------------------------------------------

--
-- Table structure for table `qr_scan_logs`
--

CREATE TABLE `qr_scan_logs` (
  `scan_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `scanned_by` int(11) DEFAULT NULL,
  `scanned_at` datetime DEFAULT current_timestamp(),
  `scan_result` enum('success','invalid','expired','duplicate') DEFAULT 'success',
  `scan_location` varchar(100) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_scan_logs`
--

INSERT INTO `qr_scan_logs` (`scan_id`, `appointment_id`, `qr_code`, `scanned_by`, `scanned_at`, `scan_result`, `scan_location`, `device_info`, `ip_address`, `notes`) VALUES
(1, 4, 'APT-BEC43DE18085', 5, '2026-01-09 12:47:53', 'invalid', 'Doctor Dashboard', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '::1', 'Failed: Appointment is scheduled for January 12, 2026');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `doctor_id`, `patient_id`, `appointment_id`, `rating`, `review_text`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 1, 5, 'Good experience', '2026-01-06 02:51:41', '2026-01-06 02:51:41');

-- --------------------------------------------------------

--
-- Table structure for table `symptom_checker_logs`
--

CREATE TABLE `symptom_checker_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `symptom_checker_logs`
--

INSERT INTO `symptom_checker_logs` (`log_id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'symptom_checker_access', 'Accessed symptom checker page', '::1', '2026-01-09 13:01:15');

-- --------------------------------------------------------

--
-- Table structure for table `symptom_checks`
--

CREATE TABLE `symptom_checks` (
  `check_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `symptoms` text NOT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `response` longtext DEFAULT NULL,
  `urgency_level` varchar(20) DEFAULT 'routine',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `symptom_checks`
--

INSERT INTO `symptom_checks` (`check_id`, `user_id`, `patient_id`, `symptoms`, `duration`, `age`, `additional_info`, `response`, `urgency_level`, `created_at`) VALUES
(1, 1, 1, 'I am experiencing: fever. cough headache sore throat fatigue nausea dizziness body ache', '2', 21, 'not feeling well', '# Symptom Analysis Results\n\n## 💊 Possible Diagnosis\n\nBased on your symptoms, you may be experiencing:\n\n### ✓ Common Cold\n\nA viral infection of the upper respiratory tract causing congestion, runny nose, and mild fever\n\n**Related to your symptoms:** Cough\n\n### ✓ Bronchitis\n\nInflammation of the bronchial tubes causing persistent cough and chest discomfort\n\n**Related to your symptoms:** Cough\n\n### ✓ Pneumonia\n\nLung infection causing fever, cough, and difficulty breathing - requires medical attention\n\n**Related to your symptoms:** Cough\n\n### ✓ Tension Headache\n\nMost common headache type causing band-like pressure around the head\n\n**Related to your symptoms:** Headache\n\n### ✓ Migraine\n\nSevere headache often with nausea, light sensitivity, and visual disturbances\n\n**Related to your symptoms:** Headache\n\n## 💖 Self-Care Recommendations\n\n* **Rest:** Get adequate sleep (7-9 hours per night) to help your body recover\n* **Hydration:** Drink plenty of fluids - water, warm tea, clear soup, or electrolyte drinks\n* **Fever Management:** Take paracetamol (acetaminophen) or ibuprofen as directed for temperature above 38.5°C (101°F)\n* **Cool Compress:** Apply cool, damp cloth to forehead and neck to reduce fever\n* **Respiratory Relief:** Use throat lozenges, honey (if over 1 year old), steam inhalation, or saline nasal spray\n* **Humidity:** Use a humidifier or breathe steam from hot shower to ease congestion\n* **Nutrition:** Maintain balanced diet even if appetite is reduced - small nutritious meals help recovery\n* **Monitor:** Keep track of temperature, symptoms, and overall condition - note any changes\n* **Avoid:** Skip smoking, alcohol, and strenuous activities until fully recovered\n\n## ⚠️ When to Seek Medical Attention\n\n**Seek medical care if you experience:**\n\n* Blood\n* Persistent\n* More than 3 weeks\n* Fever\n* Weight loss\n* Night sweats\n\n**General warning signs requiring medical evaluation:**\n\n## 🎯 Urgency Assessment\n\n**⚠️ URGENT - SEEK MEDICAL ATTENTION SOON**\n\nYou should see a doctor within 24-48 hours. Contact your healthcare provider today or visit an urgent care clinic.\n\n## 👨‍⚕️ Recommended Healthcare Provider\n\n**Recommended appointment with:**\n\n* Pulmonologist\n* Neurologist\n\n**Alternative specialists:** ENT Specialist, General Practitioner\n\n## 🚨 Important Medical Disclaimer\n\nThis assessment is for informational purposes only and does not constitute medical advice. It should not replace consultation with a qualified healthcare professional. If you have concerns about your health, please contact your doctor.\n\n', 'urgent', '2026-01-09 13:01:35');

-- --------------------------------------------------------

--
-- Table structure for table `symptom_check_scopes`
--

CREATE TABLE `symptom_check_scopes` (
  `check_id` int(11) NOT NULL,
  `scope_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `symptom_check_scopes`
--

INSERT INTO `symptom_check_scopes` (`check_id`, `scope_id`) VALUES
(1, 6),
(1, 8),
(1, 9),
(1, 28),
(1, 42),
(1, 49);

-- --------------------------------------------------------

--
-- Table structure for table `symptom_scope`
--

CREATE TABLE `symptom_scope` (
  `scope_id` int(11) NOT NULL,
  `symptom_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `possible_conditions` text DEFAULT NULL,
  `urgency_level` enum('routine','urgent','emergency') DEFAULT 'routine',
  `warning_keywords` text DEFAULT NULL COMMENT 'Comma-separated keywords that escalate urgency',
  `guidance` text DEFAULT NULL COMMENT 'Specific instructions for AI',
  `recommended_specialization` varchar(100) DEFAULT NULL COMMENT 'Doctor specialization to recommend',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `symptom_scope`
--

INSERT INTO `symptom_scope` (`scope_id`, `symptom_name`, `category`, `possible_conditions`, `urgency_level`, `warning_keywords`, `guidance`, `recommended_specialization`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Chest Pain', 'Cardiovascular', 'Angina, Heart Attack, Costochondritis, GERD, Anxiety, Pericarditis', 'urgent', 'crushing, radiating, severe, sudden, left arm, jaw, sweating, pressure, squeezing', 'Always recommend immediate ECG and cardiac evaluation. Emphasize calling emergency services if pain is severe, crushing, radiating to arm/jaw, or accompanied by shortness of breath, sweating, or nausea. Consider age and cardiac risk factors.', 'Cardiologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(2, 'Heart Palpitations', 'Cardiovascular', 'Arrhythmia, Anxiety, Thyroid Issues, Caffeine, Atrial Fibrillation, Panic Attack', 'urgent', 'rapid, irregular, racing heart, chest pain, fainting, dizziness, breathless', 'Assess frequency, duration, and triggers. Recommend urgent evaluation if accompanied by chest pain, shortness of breath, fainting, or if irregular. May need ECG monitoring. Consider thyroid function and caffeine intake.', 'Cardiologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(3, 'High Blood Pressure', 'Cardiovascular', 'Essential Hypertension, Secondary Hypertension, White Coat Syndrome', 'routine', 'very high, above 180/120, severe headache, nosebleed, vision problems, chest pain', 'Monitor blood pressure regularly at different times. Seek immediate care if readings consistently above 180/120 (hypertensive crisis) or accompanied by severe headache, vision changes, or chest pain. Lifestyle modifications important.', 'Cardiologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(4, 'Leg Swelling', 'Cardiovascular', 'Venous Insufficiency, Heart Failure, Kidney Disease, DVT, Lymphedema', 'routine', 'sudden, one leg only, painful, red, warm, breathing difficulty, rapid onset', 'Check for symmetry and pitting. URGENT if sudden onset, one-sided, painful, warm (possible DVT). Urgent if with breathing difficulty (possible pulmonary embolism). Bilateral chronic swelling may indicate heart or kidney issues.', 'Cardiologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(5, 'Shortness of Breath', 'Respiratory', 'Asthma, COPD, Pneumonia, Heart Failure, Anxiety, Pulmonary Embolism, Anemia', 'urgent', 'severe, sudden, unable to speak, blue lips, gasping, wheezing, at rest', 'Assess severity and onset carefully. EMERGENCY if severe, sudden, blue discoloration, or unable to speak in full sentences. Check oxygen saturation if possible. Consider cardiac and respiratory causes.', 'Pulmonologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(6, 'Cough', 'Respiratory', 'Common Cold, Bronchitis, Pneumonia, Asthma, GERD, Allergies, COVID-19', 'routine', 'blood, persistent, more than 3 weeks, fever, weight loss, night sweats, severe', 'Differentiate acute vs chronic (>8 weeks). Recommend medical evaluation if coughing blood (hemoptysis), severe breathing difficulty, high fever, or lasting more than 3 weeks. Chronic cough may indicate GERD or asthma.', 'Pulmonologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(7, 'Wheezing', 'Respiratory', 'Asthma, Bronchitis, Allergies, COPD, Heart Failure, Foreign Body', 'urgent', 'severe, first time, unable to breathe, blue lips, chest tightness, child', 'Assess severity and response to bronchodilators if available. EMERGENCY if severe difficulty breathing, first episode with respiratory distress, or in young child (possible foreign body). May indicate asthma exacerbation.', 'Pulmonologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(8, 'Headache', 'Neurological', 'Tension Headache, Migraine, Cluster Headache, Sinusitis, Hypertension', 'routine', 'worst ever, thunderclap, sudden severe, stiff neck, fever, confusion, vision changes', 'Most headaches are benign. EMERGENCY if \"worst headache of life\", thunderclap onset, with stiff neck/fever (meningitis), confusion, or neurological symptoms (stroke). Assess pattern, triggers, and frequency.', 'Neurologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(9, 'Dizziness', 'Neurological', 'Vertigo, Low Blood Pressure, Dehydration, Inner Ear Problems, Anxiety, Anemia', 'routine', 'severe, falls, fainting, chest pain, confusion, weakness, room spinning', 'Differentiate vertigo (spinning) from lightheadedness. Assess for fall risk. URGENT if accompanied by chest pain, severe headache, confusion, or neurological symptoms. Check blood pressure and hydration status.', 'Neurologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(10, 'Seizure', 'Neurological', 'Epilepsy, Fever, Head Injury, Stroke, Metabolic Disturbances, Infection', 'emergency', 'first time, prolonged, multiple, not waking up, injury during seizure', 'EMERGENCY - All first-time seizures require immediate evaluation. Call emergency services if seizure lasts more than 5 minutes, multiple seizures occur, or person does not regain consciousness. Protect from injury during seizure.', 'Neurologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(11, 'Confusion', 'Neurological', 'Infection, Medication Side Effect, Stroke, Dementia, Metabolic Issues, Dehydration', 'emergency', 'sudden, severe, disoriented, unresponsive, slurred speech, weakness', 'Confusion is always concerning, especially if sudden. EMERGENCY - immediate evaluation needed. Consider stroke (FAST test), infection, metabolic causes. Check blood sugar if diabetic. Protect patient from harm.', 'Neurologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(12, 'Memory Loss', 'Neurological', 'Normal Aging, Dementia, Alzheimers, Depression, Vitamin B12 Deficiency, Thyroid', 'routine', 'sudden, severe, getting lost, personality changes, unable to recognize family', 'Gradual memory decline with aging is common. Concerning if sudden onset, rapidly progressive, affecting daily function, or with personality changes. Comprehensive evaluation needed including cognitive testing.', 'Neurologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(13, 'Numbness/Tingling', 'Neurological', 'Nerve Compression, Carpal Tunnel, Diabetes, MS, Vitamin Deficiency, Pinched Nerve', 'routine', 'sudden, one-sided, face, arm weakness, difficulty speaking, spreading', 'EMERGENCY if sudden onset, one-sided, with facial drooping or speech difficulty (stroke signs). Chronic bilateral may be nerve compression or neuropathy. Assess pattern and distribution.', 'Neurologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(14, 'Abdominal Pain', 'Gastrointestinal', 'Gastroenteritis, Appendicitis, Gastritis, IBS, Constipation, Gallstones, Ulcer', 'routine', 'severe, right lower, sudden intense, vomiting blood, rigid abdomen, fever', 'Location is key: Right lower quadrant (appendicitis), right upper (gallbladder), epigastric (ulcer/gastritis). URGENT if severe, sudden, rigid abdomen, vomiting blood, or signs of peritonitis.', 'Gastroenterologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(15, 'Nausea and Vomiting', 'Gastrointestinal', 'Gastroenteritis, Food Poisoning, Migraine, Pregnancy, Medication, Vertigo', 'routine', 'vomiting blood, severe dehydration, unable to keep fluids, confusion, severe headache', 'Monitor for dehydration (dry mouth, decreased urination, dizziness). URGENT if vomiting blood, coffee-ground emesis, unable to keep fluids for 24 hours, or severe dehydration signs.', 'Gastroenterologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(16, 'Diarrhea', 'Gastrointestinal', 'Gastroenteritis, Food Poisoning, IBS, IBD, Infection, Medication Side Effect', 'routine', 'bloody, black tarry, severe dehydration, high fever, persistent more than 3 days', 'Most cases viral and self-limiting. Monitor hydration. URGENT if bloody/black stools, signs of severe dehydration, high fever, or lasting >3 days. Encourage oral rehydration solutions.', 'Gastroenterologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(17, 'Constipation', 'Gastrointestinal', 'Dietary Issues, Dehydration, IBS, Medication Side Effect, Bowel Obstruction', 'routine', 'severe pain, vomiting, no bowel movement for week, blood, distended abdomen', 'Usually dietary or lifestyle related. URGENT if severe abdominal pain, vomiting, no bowel movement for >1 week, or distended abdomen (possible obstruction). Increase fiber, fluids, exercise.', 'Gastroenterologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(18, 'Heartburn/GERD', 'Gastrointestinal', 'GERD, Hiatal Hernia, Peptic Ulcer, Gastritis, Esophagitis', 'routine', 'severe, daily, difficulty swallowing, weight loss, vomiting blood, chest pain', 'Common condition. Lifestyle modifications help (elevate head, avoid triggers, small meals). See doctor if persistent, severe, difficulty swallowing, or affecting quality of life. Rule out cardiac causes for chest pain.', 'Gastroenterologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(19, 'Blood in Stool', 'Gastrointestinal', 'Hemorrhoids, Anal Fissure, Polyps, IBD, Diverticulosis, Colorectal Cancer', 'urgent', 'large amount, black tarry, maroon, severe pain, weakness, dizziness, ongoing', 'Always requires evaluation. URGENT if large amounts, black/tarry stools (upper GI bleed), severe weakness, or ongoing bleeding. Bright red may be hemorrhoids but still needs assessment. Consider colonoscopy.', 'Gastroenterologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(20, 'Back Pain', 'Musculoskeletal', 'Muscle Strain, Herniated Disc, Sciatica, Spinal Stenosis, Kidney Stones', 'routine', 'severe after trauma, leg numbness, weakness, bowel/bladder changes, fever', 'Most back pain is mechanical and self-limiting. URGENT if neurological symptoms (leg numbness/weakness), loss of bowel/bladder control (cauda equina), fever (infection), or after significant trauma.', 'Orthopedic', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(21, 'Neck Pain', 'Musculoskeletal', 'Muscle Strain, Cervical Spondylosis, Herniated Disc, Whiplash, Meningitis', 'routine', 'severe after injury, arm numbness, weakness, fever with stiff neck, unable to move', 'Usually muscular from poor posture or strain. URGENT if after trauma (especially motor vehicle accident), arm numbness/weakness, or fever with severe stiff neck (possible meningitis).', 'Orthopedic', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(22, 'Joint Pain', 'Musculoskeletal', 'Arthritis, Gout, Injury, Bursitis, Tendinitis, Autoimmune Disease', 'routine', 'severe swelling, red, hot, fever, unable to move, rapid onset', 'Assess for infection signs. URGENT if joint is red, hot, swollen with fever (septic arthritis), or sudden severe pain with swelling (gout or fracture). Chronic pain may be osteoarthritis or rheumatoid arthritis.', 'Orthopedic', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(23, 'Knee Pain', 'Musculoskeletal', 'Arthritis, Meniscus Tear, Ligament Injury, Bursitis, Patellofemoral Syndrome', 'routine', 'severe swelling, unable to bear weight, deformity, locking, giving way', 'Common injury. Assess mechanism of injury. Urgent if severe swelling, unable to bear weight, visible deformity, or knee \"locks\". RICE protocol (Rest, Ice, Compression, Elevation) initially.', 'Orthopedic', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(24, 'Shoulder Pain', 'Musculoskeletal', 'Rotator Cuff Injury, Frozen Shoulder, Arthritis, Bursitis, Tendinitis', 'routine', 'severe after injury, unable to move, deformity, severe pain at rest', 'Often from overuse or rotator cuff issues. Seek care if severe pain after trauma, unable to move shoulder, visible deformity (dislocation), or pain not improving with rest and anti-inflammatories.', 'Orthopedic', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(25, 'Rash', 'Dermatological', 'Allergic Reaction, Eczema, Psoriasis, Viral Exanthem, Drug Reaction, Contact Dermatitis', 'routine', 'spreading rapidly, facial swelling, breathing difficulty, anaphylaxis, severe itching, fever', 'Assess for allergic reaction. EMERGENCY if facial/throat swelling, breathing difficulty, or signs of anaphylaxis. Urgent if rapidly spreading, severe, or with fever (possible infection or serious reaction).', 'Dermatologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(26, 'Skin Infection', 'Dermatological', 'Cellulitis, Abscess, Impetigo, Fungal Infection, MRSA', 'urgent', 'spreading rapidly, red streaks, fever, severe pain, pus, warmth', 'Monitor for spreading. URGENT if rapidly spreading, red streaks from wound (lymphangitis), fever, or severe. May need antibiotics. Diabetics at higher risk for complications.', 'Dermatologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(27, 'Mole Changes', 'Dermatological', 'Atypical Mole, Melanoma, Basal Cell Carcinoma, Squamous Cell Carcinoma', 'routine', 'bleeding, irregular borders, multiple colors, growing, changing, itching, ulceration', 'Any changing mole needs evaluation. Use ABCDE rule: Asymmetry, Border irregularity, Color variation, Diameter >6mm, Evolution/changing. Skin cancer is highly treatable when caught early.', 'Dermatologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(28, 'Sore Throat', 'ENT', 'Viral Pharyngitis, Strep Throat, Tonsillitis, Allergies, GERD, Mononucleosis', 'routine', 'severe, unable to swallow, drooling, breathing difficulty, high fever, white patches', 'Most viral and self-limiting. See doctor if severe pain, fever >101°F, white patches (strep throat), unable to swallow, or lasting >1 week. Strep requires antibiotics.', 'ENT Specialist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(29, 'Ear Pain', 'ENT', 'Ear Infection, Swimmers Ear, TMJ, Earwax Buildup, Eustachian Tube Dysfunction', 'routine', 'severe, drainage, hearing loss, fever, dizziness, after head injury', 'Common in children. See doctor if severe pain, drainage from ear, hearing loss, high fever, or symptoms lasting >48 hours. Do not insert objects in ear.', 'ENT Specialist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(30, 'Hearing Loss', 'ENT', 'Earwax Buildup, Ear Infection, Age-related, Noise-induced, Menieres Disease', 'routine', 'sudden, one ear only, ringing, dizziness, after head injury, rapid progression', 'Gradual hearing loss common with aging. URGENT if sudden onset (especially one ear) - may be reversible if treated quickly. May indicate acoustic neuroma or sudden sensorineural hearing loss.', 'ENT Specialist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(31, 'Nosebleed', 'ENT', 'Dry Air, Nose Picking, Trauma, High Blood Pressure, Blood Thinners, Bleeding Disorder', 'routine', 'frequent, heavy flow, wont stop after 20 minutes, after head injury, on blood thinners', 'Usually minor and self-limiting. Pinch soft part of nose for 10-15 minutes. See doctor if bleeding continues >20 minutes, frequent episodes, heavy flow, or on blood thinners.', 'ENT Specialist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(32, 'Difficulty Swallowing', 'ENT', 'GERD, Esophagitis, Stricture, Achalasia, Throat Cancer, Anxiety', 'urgent', 'complete blockage, drooling, weight loss, chest pain, food stuck, progressive', 'Always needs evaluation. URGENT if complete inability to swallow (dysphagia), sensation of food stuck, progressive symptoms, or unexplained weight loss. May need endoscopy.', 'ENT Specialist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(33, 'Urinary Problems', 'Urological', 'UTI, Kidney Stones, Prostate Issues, Bladder Infection, Overactive Bladder', 'routine', 'blood in urine, severe pain, fever, unable to urinate, back pain, burning', 'Common in women (UTI). URGENT if blood in urine (hematuria), severe flank pain, high fever, or complete inability to urinate (retention). Men with difficulty urinating may have prostate issues.', 'Urologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(34, 'Kidney Pain', 'Urological', 'Kidney Stones, Pyelonephritis, Kidney Infection, Hydronephrosis', 'urgent', 'severe flank pain, fever, vomiting, blood in urine, unable to urinate, chills', 'Severe flank pain suggests kidney stones or infection. URGENT if fever (possible pyelonephritis), vomiting, blood in urine, or inability to urinate. May need imaging and antibiotics.', 'Urologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(35, 'Pelvic Pain', 'Gynecological', 'Menstrual Cramps, Endometriosis, Ovarian Cyst, PID, Ectopic Pregnancy', 'routine', 'severe, fever, abnormal bleeding, fainting, pregnancy positive, sudden sharp', 'Cyclical pain may be normal menstrual cramps. URGENT if severe sudden pain (possible ovarian torsion or ectopic pregnancy), fever (PID), or in pregnancy. Chronic pain needs evaluation.', 'Gynecologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(36, 'Abnormal Vaginal Bleeding', 'Gynecological', 'Hormonal Imbalance, Fibroids, Polyps, PCOS, Cancer, Pregnancy Complications', 'urgent', 'heavy soaking pads hourly, postmenopausal, during pregnancy, with severe pain', 'Any postmenopausal bleeding requires urgent evaluation. Heavy bleeding (soaking pad every hour), bleeding in pregnancy, or with severe pain needs prompt assessment. May need ultrasound.', 'Gynecologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(37, 'Eye Pain', 'Ophthalmological', 'Eye Strain, Glaucoma, Infection, Foreign Body, Uveitis, Corneal Abrasion', 'urgent', 'severe, vision loss, after injury, light sensitivity, very red eye, halos', 'Assess cause and severity. URGENT if severe pain, vision changes, after injury, with very red eye, or extreme light sensitivity. May indicate serious conditions like glaucoma or uveitis.', 'Ophthalmologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(38, 'Vision Changes', 'Ophthalmological', 'Refractive Error, Cataracts, Glaucoma, Retinal Detachment, Stroke, Macular Degeneration', 'urgent', 'sudden, complete loss, flashes of light, curtain over vision, one eye, floaters', 'Gradual changes may need glasses/contacts. EMERGENCY for sudden vision loss, flashing lights, curtain/shadow over vision (retinal detachment), or one-sided loss (stroke). Time-critical conditions.', 'Ophthalmologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(39, 'Red Eye', 'Ophthalmological', 'Conjunctivitis, Allergy, Dry Eye, Glaucoma, Uveitis, Subconjunctival Hemorrhage', 'routine', 'severe pain, vision loss, light sensitivity, green/yellow discharge, after injury', 'Pink eye (conjunctivitis) is common and usually viral. URGENT if severe pain, vision changes, extreme light sensitivity, or after injury. Bacterial conjunctivitis needs antibiotic drops.', 'Ophthalmologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(40, 'Excessive Thirst', 'Endocrine', 'Diabetes, Dehydration, Medication Side Effect, Diabetes Insipidus', 'routine', 'extreme, frequent urination, weight loss, weakness, confusion, very high blood sugar', 'May indicate diabetes mellitus. Check blood sugar if available. Urgent if with confusion, extreme weakness, or very high blood sugar (DKA risk). Also assess for dehydration.', 'Endocrinologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(41, 'Unexplained Weight Loss', 'Endocrine', 'Diabetes, Hyperthyroidism, Cancer, Depression, Malabsorption, TB', 'urgent', 'significant, rapid, no diet change, more than 10% body weight, night sweats, fever', 'Unintentional weight loss of >10% body weight in 6 months requires evaluation. Consider diabetes, thyroid, cancer, infection. Assess appetite, bowel habits, and other symptoms.', 'Endocrinologist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(42, 'Fatigue', 'General', 'Anemia, Thyroid Issues, Depression, Sleep Apnea, Chronic Fatigue Syndrome, Diabetes', 'routine', 'severe, sudden, with weight loss, night sweats, fever, pale, shortness of breath', 'Very common with many causes. Concerning if severe, sudden onset, with weight loss, or affecting daily function. Check for anemia, thyroid dysfunction, depression, sleep disorders.', 'General Practitioner', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(43, 'Anxiety', 'Psychiatric', 'Generalized Anxiety Disorder, Panic Disorder, PTSD, Social Anxiety, Phobias', 'routine', 'severe, panic attacks, suicidal thoughts, affecting daily life, chest pain, cant function', 'Common and treatable condition. Seek help if severe, frequent panic attacks, suicidal thoughts, or significantly affecting quality of life. Therapy and/or medication effective.', 'Psychiatrist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(44, 'Depression', 'Psychiatric', 'Major Depression, Bipolar Disorder, Seasonal Affective Disorder, Adjustment Disorder', 'urgent', 'suicidal thoughts, severe, not eating, not functioning, self-harm, hopeless', 'URGENT if any suicidal thoughts - seek immediate help. Depression is highly treatable. Concerning if severe, affecting ability to function, not eating, or with self-harm thoughts.', 'Psychiatrist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(45, 'Sleep Problems', 'Psychiatric', 'Insomnia, Sleep Apnea, Restless Leg Syndrome, Depression, Anxiety', 'routine', 'severe, affecting function, snoring with gasping, daytime sleepiness, witnessed pauses', 'Chronic sleep problems affect overall health. See doctor if severe, affecting daily function, loud snoring with gasping (sleep apnea), or extreme daytime sleepiness despite adequate time in bed.', 'Psychiatrist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(46, 'Infant Fever', 'Pediatric', 'Viral Infection, Bacterial Infection, Teething, Vaccination Reaction, Meningitis', 'urgent', 'under 3 months, very high over 40C, lethargic, not feeding, rash, seizure, stiff', 'URGENT: ANY fever in infant under 3 months requires immediate evaluation (sepsis risk). In older infants, urgent if very high fever, lethargic, not feeding, rash, seizure, or appears very ill.', 'Pediatrician', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(47, 'Child Vomiting', 'Pediatric', 'Gastroenteritis, Food Poisoning, Infection, Appendicitis, Head Injury', 'routine', 'blood, severe dehydration, persistent, lethargic, no wet diapers, severe pain, head injury', 'Monitor hydration carefully. URGENT if vomiting blood, signs of severe dehydration (no tears, no wet diapers, sunken fontanelle), very lethargic, or after head injury.', 'Pediatrician', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(48, 'Allergic Reaction', 'Allergic', 'Food Allergy, Drug Allergy, Insect Sting, Latex Allergy, Contact Dermatitis', 'urgent', 'facial swelling, throat swelling, difficulty breathing, dizziness, rapid pulse, hives spreading', 'EMERGENCY if any throat swelling, breathing difficulty, facial swelling, dizziness, or rapid progression (anaphylaxis). Give epinephrine if available and call 999. Mild reactions can be managed with antihistamines.', 'Allergist', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47'),
(49, 'Fever', 'General', 'Viral Infection, Bacterial Infection, UTI, COVID-19, Influenza, Pneumonia', 'routine', 'very high over 40C, persistent more than 3 days, severe headache, stiff neck, rash, difficulty breathing', 'Common symptom with many causes. Most fevers are viral and self-limiting. URGENT if temperature >40°C (104°F), lasting >3 days, severe headache with stiff neck (meningitis), rash, or difficulty breathing.', 'General Practitioner', 1, '2025-12-21 15:20:47', '2025-12-21 15:20:47');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('admin','doctor','patient') NOT NULL,
  `status` enum('active','pending','suspended','inactive') DEFAULT 'active',
  `security_question` varchar(255) NOT NULL,
  `security_answer` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `user_type`, `status`, `security_question`, `security_answer`, `is_active`, `created_at`, `last_login`) VALUES
(1, 'peiying.len@qiu.edu.my', '$2y$10$SGNhKQUdhZuke6WqDyLXWuO/SofwoqeMHLkz6QQYbXV8qfQUYGhOG', 'patient', 'active', 'What city were you born in?', '$2y$10$.fKwPsJNkjmllx9VQe0T0eG4gTiFaJs1QTbuzGqUJ4Uj4wexwA0ta', 1, '2026-01-05 09:27:37', '2026-01-10 18:57:52'),
(2, 'teikkok.ho@gmail.com', '$2y$10$OGDvoBo//ghCy8pXLrvxi.Ukb9b5TnXbGdFTzdbZc47e7Nrrr1ndG', 'doctor', 'active', 'What city were you born in?', '$2y$10$5Wa05twN8QEMqxdrphvu.OBpspQA/lu8Q15DNYl0NBsrMs7nN5uOi', 1, '2026-01-05 09:36:21', '2026-01-08 18:06:03'),
(4, 'lenpeiying12345@gmail.com', '$2y$10$q0jqERZz9jnRntmmdx/pOOmRf1JOgYE22TiJrzFNs8P96w7aiBRJK', 'admin', 'active', 'What city were you born in?', '$2y$10$dn8cfl3CaYPrFW5a.4FupeWL.9A54OYXDBJcxKr3li/n34hssEBZC', 1, '2026-01-05 15:28:45', '2026-01-08 17:19:01'),
(5, 'kimseng_l@yahoo.com', '$2y$10$hszmoS.nshqknNg79ZXafu.WQPibp0fkJEprCW8uvzxjfu7UlZgC.', 'doctor', 'active', 'What city were you born in?', '$2y$10$F7h1pUtNw/1ue6Ec2HMx4ePnlTHAMpdi.QB2scvUXi4FRTvka7/NK', 1, '2026-01-08 16:33:48', '2026-01-09 04:47:35'),
(6, 'jadeqi.chan@qiu.edu.my', '$2y$10$KSJDujy7ASuSZeDAenzuKe12wypJc3EqlftphCvDob.N8f1YLc7L.', 'patient', 'active', 'What city were you born in?', '$2y$10$.tvk0IlbTsPBRXNH/6Bn1O6cvRd6XIOdyQHEn2E6APo10GLVzhSFy', 1, '2026-01-09 01:17:42', '2026-01-09 04:50:01'),
(7, 'jadeqichan@gmail.com', '$2y$10$z2CBNC/NURgABFWAA0btwezvARSLrmv/f.MtM/a6rSbiG/gNOvtfC', 'admin', 'active', 'What city were you born in?', '$2y$10$YhNKK6pkEenc88un6M5aVuXG4n2SyOfn4jgQItgfk21eTQhBMD7Ga', 1, '2026-01-09 01:20:58', '2026-01-09 04:54:05');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_today_appointments`
-- (See below for the actual view)
--
CREATE TABLE `vw_today_appointments` (
`appointment_id` int(11)
,`appointment_date` date
,`appointment_time` time
,`status` enum('pending','confirmed','completed','cancelled')
,`qr_code` varchar(255)
,`checked_in_at` datetime
,`patient_id` int(11)
,`patient_fname` varchar(100)
,`patient_lname` varchar(100)
,`doctor_id` int(11)
,`doctor_fname` varchar(100)
,`doctor_lname` varchar(100)
,`specialization` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_active_chatbot_scopes`
-- (See below for the actual view)
--
CREATE TABLE `v_active_chatbot_scopes` (
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_active_restrictions`
-- (See below for the actual view)
--
CREATE TABLE `v_active_restrictions` (
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_medicine_inventory`
-- (See below for the actual view)
--
CREATE TABLE `v_medicine_inventory` (
`medicine_id` int(11)
,`medicine_name` varchar(255)
,`generic_name` varchar(255)
,`category` varchar(100)
,`dosage_form` varchar(50)
,`strength` varchar(50)
,`unit_price` decimal(10,2)
,`stock_quantity` int(11)
,`reorder_level` int(11)
,`stock_status` varchar(12)
,`requires_prescription` tinyint(1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_symptom_check_stats`
-- (See below for the actual view)
--
CREATE TABLE `v_symptom_check_stats` (
);

-- --------------------------------------------------------

--
-- Structure for view `vw_today_appointments`
--
DROP TABLE IF EXISTS `vw_today_appointments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_today_appointments`  AS SELECT `a`.`appointment_id` AS `appointment_id`, `a`.`appointment_date` AS `appointment_date`, `a`.`appointment_time` AS `appointment_time`, `a`.`status` AS `status`, `a`.`qr_code` AS `qr_code`, `a`.`checked_in_at` AS `checked_in_at`, `p`.`patient_id` AS `patient_id`, `p`.`first_name` AS `patient_fname`, `p`.`last_name` AS `patient_lname`, `d`.`doctor_id` AS `doctor_id`, `d`.`first_name` AS `doctor_fname`, `d`.`last_name` AS `doctor_lname`, `d`.`specialization` AS `specialization` FROM ((`appointments` `a` join `patients` `p` on(`a`.`patient_id` = `p`.`patient_id`)) join `doctors` `d` on(`a`.`doctor_id` = `d`.`doctor_id`)) WHERE `a`.`appointment_date` = curdate() ORDER BY `a`.`appointment_time` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_active_chatbot_scopes`
--
DROP TABLE IF EXISTS `v_active_chatbot_scopes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_chatbot_scopes`  AS SELECT `chatbot_scope`.`id` AS `id`, `chatbot_scope`.`category` AS `category`, `chatbot_scope`.`topic` AS `topic`, `chatbot_scope`.`keywords` AS `keywords`, `chatbot_scope`.`allowed_response_type` AS `allowed_response_type`, `chatbot_scope`.`response_template` AS `response_template`, `chatbot_scope`.`max_detail_level` AS `max_detail_level`, `chatbot_scope`.`requires_login` AS `requires_login`, `chatbot_scope`.`priority` AS `priority` FROM `chatbot_scope` WHERE `chatbot_scope`.`is_active` = 1 ORDER BY `chatbot_scope`.`priority` DESC, `chatbot_scope`.`category` ASC, `chatbot_scope`.`topic` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_active_restrictions`
--
DROP TABLE IF EXISTS `v_active_restrictions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_restrictions`  AS SELECT `chatbot_restricted_topics`.`id` AS `id`, `chatbot_restricted_topics`.`topic_name` AS `topic_name`, `chatbot_restricted_topics`.`keywords` AS `keywords`, `chatbot_restricted_topics`.`restriction_reason` AS `restriction_reason`, `chatbot_restricted_topics`.`redirect_message` AS `redirect_message`, `chatbot_restricted_topics`.`severity` AS `severity` FROM `chatbot_restricted_topics` WHERE `chatbot_restricted_topics`.`is_active` = 1 ORDER BY `chatbot_restricted_topics`.`severity` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_medicine_inventory`
--
DROP TABLE IF EXISTS `v_medicine_inventory`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_medicine_inventory`  AS SELECT `m`.`medicine_id` AS `medicine_id`, `m`.`medicine_name` AS `medicine_name`, `m`.`generic_name` AS `generic_name`, `m`.`category` AS `category`, `m`.`dosage_form` AS `dosage_form`, `m`.`strength` AS `strength`, `m`.`unit_price` AS `unit_price`, `m`.`stock_quantity` AS `stock_quantity`, `m`.`reorder_level` AS `reorder_level`, CASE WHEN `m`.`stock_quantity` <= `m`.`reorder_level` THEN 'Low Stock' WHEN `m`.`stock_quantity` = 0 THEN 'Out of Stock' ELSE 'In Stock' END AS `stock_status`, `m`.`requires_prescription` AS `requires_prescription` FROM `medicine_catalog` AS `m` WHERE `m`.`is_active` = 1 ORDER BY `m`.`category` ASC, `m`.`medicine_name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_symptom_check_stats`
--
DROP TABLE IF EXISTS `v_symptom_check_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_symptom_check_stats`  AS SELECT `u`.`user_id` AS `user_id`, `u`.`email` AS `email`, `u`.`user_type` AS `user_type`, count(`sc`.`id`) AS `total_checks`, sum(case when `sc`.`urgency_level` = 'emergency' then 1 else 0 end) AS `emergency_count`, sum(case when `sc`.`urgency_level` = 'urgent' then 1 else 0 end) AS `urgent_count`, sum(case when `sc`.`urgency_level` = 'routine' then 1 else 0 end) AS `routine_count`, max(`sc`.`created_at`) AS `last_check_date` FROM (`users` `u` left join `symptom_checks` `sc` on(`u`.`user_id` = `sc`.`user_id`)) GROUP BY `u`.`user_id`, `u`.`email`, `u`.`user_type` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `ic_number` (`ic_number`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_admin_profile_picture` (`profile_picture`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_doctor` (`doctor_id`),
  ADD KEY `idx_date` (`appointment_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_qr_code` (`qr_code`),
  ADD KEY `idx_checked_in_at` (`checked_in_at`),
  ADD KEY `idx_patient_date` (`patient_id`,`appointment_date`),
  ADD KEY `idx_doctor_date` (`doctor_id`,`appointment_date`),
  ADD KEY `idx_appointment_datetime` (`appointment_date`,`appointment_time`),
  ADD KEY `idx_confirmation_deadline` (`confirmation_deadline`),
  ADD KEY `idx_confirmed_at` (`confirmed_at`);

--
-- Indexes for table `chatbot_logs`
--
ALTER TABLE `chatbot_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_matched_scope` (`chatbot_scope_id`),
  ADD KEY `idx_restricted` (`is_restricted`),
  ADD KEY `idx_chatbot_logs_created` (`created_at`),
  ADD KEY `idx_chatbot_logs_patient_session` (`patient_id`,`session_id`);

--
-- Indexes for table `chatbot_restricted_topics`
--
ALTER TABLE `chatbot_restricted_topics`
  ADD PRIMARY KEY (`restriction_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_severity` (`severity`);

--
-- Indexes for table `chatbot_scope`
--
ALTER TABLE `chatbot_scope`
  ADD PRIMARY KEY (`chatbot_scope_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_chatbot_scope_priority` (`priority`,`is_active`);

--
-- Indexes for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD PRIMARY KEY (`contact_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `ic_number` (`ic_number`),
  ADD KEY `idx_specialization` (`specialization`),
  ADD KEY `idx_ic_number` (`ic_number`),
  ADD KEY `idx_gender` (`gender`),
  ADD KEY `idx_profile_picture` (`profile_picture`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `lab_test_catalog`
--
ALTER TABLE `lab_test_catalog`
  ADD PRIMARY KEY (`test_id`),
  ADD KEY `idx_name` (`test_name`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_visit_date` (`visit_date`);

--
-- Indexes for table `medicine_catalog`
--
ALTER TABLE `medicine_catalog`
  ADD PRIMARY KEY (`medicine_id`),
  ADD KEY `idx_name` (`medicine_name`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_user_unread` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `ic_number` (`ic_number`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_ic` (`ic_number`),
  ADD KEY `idx_name` (`last_name`,`first_name`),
  ADD KEY `idx_profile_picture` (`profile_picture`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_status` (`payment_status`),
  ADD KEY `idx_receipt` (`receipt_number`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD UNIQUE KEY `verification_code` (`verification_code`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_doctor` (`doctor_id`),
  ADD KEY `idx_date` (`prescription_date`),
  ADD KEY `idx_verification` (`verification_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `prescription_medications`
--
ALTER TABLE `prescription_medications`
  ADD PRIMARY KEY (`medication_id`),
  ADD KEY `idx_prescription` (`prescription_id`);

--
-- Indexes for table `procedure_catalog`
--
ALTER TABLE `procedure_catalog`
  ADD PRIMARY KEY (`procedure_id`),
  ADD KEY `idx_name` (`procedure_name`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `qr_code_history`
--
ALTER TABLE `qr_code_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_appointment` (`appointment_id`),
  ADD KEY `idx_qr_code` (`qr_code`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  ADD PRIMARY KEY (`scan_id`),
  ADD KEY `idx_appointment` (`appointment_id`),
  ADD KEY `idx_qr_code` (`qr_code`),
  ADD KEY `idx_scanned_at` (`scanned_at`),
  ADD KEY `scanned_by` (`scanned_by`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_review` (`appointment_id`),
  ADD KEY `idx_doctor_rating` (`doctor_id`,`rating`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `symptom_checker_logs`
--
ALTER TABLE `symptom_checker_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `symptom_checks`
--
ALTER TABLE `symptom_checks`
  ADD PRIMARY KEY (`check_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_urgency` (`urgency_level`);

--
-- Indexes for table `symptom_check_scopes`
--
ALTER TABLE `symptom_check_scopes`
  ADD PRIMARY KEY (`check_id`,`scope_id`),
  ADD KEY `fk_scs_scope` (`scope_id`);

--
-- Indexes for table `symptom_scope`
--
ALTER TABLE `symptom_scope`
  ADD PRIMARY KEY (`scope_id`),
  ADD KEY `idx_symptom_name` (`symptom_name`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_active` (`is_active`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_user_type` (`user_type`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `chatbot_logs`
--
ALTER TABLE `chatbot_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `chatbot_restricted_topics`
--
ALTER TABLE `chatbot_restricted_topics`
  MODIFY `restriction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `chatbot_scope`
--
ALTER TABLE `chatbot_scope`
  MODIFY `chatbot_scope_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lab_test_catalog`
--
ALTER TABLE `lab_test_catalog`
  MODIFY `test_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `medicine_catalog`
--
ALTER TABLE `medicine_catalog`
  MODIFY `medicine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `prescription_medications`
--
ALTER TABLE `prescription_medications`
  MODIFY `medication_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `procedure_catalog`
--
ALTER TABLE `procedure_catalog`
  MODIFY `procedure_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `qr_code_history`
--
ALTER TABLE `qr_code_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  MODIFY `scan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `symptom_checker_logs`
--
ALTER TABLE `symptom_checker_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `symptom_checks`
--
ALTER TABLE `symptom_checks`
  MODIFY `check_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `symptom_scope`
--
ALTER TABLE `symptom_scope`
  MODIFY `scope_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL;

--
-- Constraints for table `prescription_medications`
--
ALTER TABLE `prescription_medications`
  ADD CONSTRAINT `prescription_medications_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`) ON DELETE CASCADE;

--
-- Constraints for table `qr_code_history`
--
ALTER TABLE `qr_code_history`
  ADD CONSTRAINT `qr_code_history_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qr_code_history_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `qr_scan_logs`
--
ALTER TABLE `qr_scan_logs`
  ADD CONSTRAINT `qr_scan_logs_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `qr_scan_logs_ibfk_2` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE;

--
-- Constraints for table `symptom_check_scopes`
--
ALTER TABLE `symptom_check_scopes`
  ADD CONSTRAINT `fk_scs_check` FOREIGN KEY (`check_id`) REFERENCES `symptom_checks` (`check_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_scs_scope` FOREIGN KEY (`scope_id`) REFERENCES `symptom_scope` (`scope_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
