/*Kampüs Etkinlik Yönetim Sistemib SQL DOSYASI
    230601035-Damla AKPINAR
    230601040-Ülkü Bensu İNCE
    230601050-Havin Ezgi GÜNEŞ
*/

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost
-- Üretim Zamanı: 21 Ara 2025, 17:00:44
-- Sunucu sürümü: 10.4.28-MariaDB
-- PHP Sürümü: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `campus_events`
-- ==========================================================
-- VERİTABANI OLUŞTURMA VE SEÇME
-- ==========================================================
-- Eğer campus_events adında bir veritabanı varsa siler, böylece sıfır kurulum yapar.
DROP DATABASE IF EXISTS `campus_events`;

-- Veritabanını yeniden oluşturur.
CREATE DATABASE `campus_events` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabloların bu veritabanı içine kurulmasını sağlar.
USE `campus_events`;

-- ==========================================================

DELIMITER $$
--
-- Yordamlar
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `AddNewEvent` (IN `p_category_id` INT, IN `p_location_id` INT, IN `p_event_title` VARCHAR(150), IN `p_event_description` TEXT, IN `p_start_datetime` DATETIME, IN `p_end_datetime` DATETIME, IN `p_capacity` INT, IN `p_status` VARCHAR(20))   BEGIN
    INSERT INTO events (
        category_id,
        location_id,
        event_title,
        event_description,
        start_datetime,
        end_datetime,
        capacity,
        available_seats,
        status,
        created_at
    )
    VALUES (
        p_category_id,
        p_location_id,
        p_event_title,
        p_event_description,
        p_start_datetime,
        p_end_datetime,
        p_capacity,
        p_capacity, -- available_seats başlangıçta kapasiteye eşit
        p_status,
        NOW()
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `CancelEventRegistration` (IN `p_event_id` INT, IN `p_user_id` INT)   BEGIN
    DELETE FROM event_participations
    WHERE event_id = p_event_id
      AND user_id  = p_user_id
    LIMIT 1;

    
    INSERT INTO notifications (
        user_id,
        event_id,
        message,
        is_read,
        created_at
    )
    VALUES (
        p_user_id,
        p_event_id,
        'Etkinlik kaydınız iptal edildi.',
        0,
        NOW()
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetDepartmentEvents` (IN `p_department_id` INT)   BEGIN
    SELECT
        e.event_id,
        e.event_title,
        ec.category_name,
        l.location_name,
        e.start_datetime,
        e.end_datetime,
        de.is_main_department
    FROM department_events AS de
    INNER JOIN events AS e
        ON de.event_id = e.event_id
    INNER JOIN event_categories AS ec
        ON e.category_id = ec.category_id
    INNER JOIN locations AS l
        ON e.location_id = l.location_id
    WHERE de.department_id = p_department_id
    ORDER BY e.start_datetime;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetEventAverageRating` (IN `p_event_id` INT)   BEGIN
    SELECT
        e.event_id,
        e.event_title,
        AVG(ef.rating)                       AS average_rating,
        COUNT(DISTINCT u.user_id)            AS total_reviewers
    FROM events AS e
    LEFT JOIN event_feedbacks AS ef
        ON e.event_id = ef.event_id
    LEFT JOIN users AS u
        ON ef.user_id = u.user_id
    WHERE e.event_id = p_event_id
    GROUP BY e.event_id, e.event_title;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetEventOrganizers` (IN `p_event_id` INT)   BEGIN
    SELECT
        u.user_id,
        u.full_name,
        r.role_name,
        eo.role_in_event
    FROM event_organizers AS eo
    INNER JOIN users AS u
        ON eo.user_id = u.user_id
    INNER JOIN roles AS r
        ON u.role_id = r.role_id
    WHERE eo.event_id = p_event_id
    ORDER BY u.full_name;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetEventParticipants` (IN `p_event_id` INT)   BEGIN
    SELECT
        u.user_id,
        u.full_name,
        d.department_name,
        u.email
    FROM event_participations AS ep
    INNER JOIN users AS u
        ON ep.user_id = u.user_id
    LEFT JOIN departments AS d
        ON u.department_id = d.department_id
    WHERE ep.event_id = p_event_id
    ORDER BY u.full_name;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetEventsByCategory` (IN `p_category_id` INT)   BEGIN
    SELECT
        event_id,
        event_title,
        start_datetime,
        end_datetime,
        status
    FROM events
    WHERE category_id = p_category_id
    ORDER BY start_datetime;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetTop5MostAttendedEvents` ()   BEGIN
    SELECT
        e.event_id,
        e.event_title,
        ec.category_name,
        COUNT(ep.participation_id) AS total_participants
    FROM events e
    LEFT JOIN event_participations ep
        ON e.event_id = ep.event_id
    LEFT JOIN event_categories ec
        ON e.category_id = ec.category_id
    GROUP BY
        e.event_id,
        e.event_title,
        ec.category_name
    ORDER BY total_participants DESC
    LIMIT 5;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUpcomingEventsWithCategory` ()   BEGIN
    SELECT 
        e.event_id,
        e.event_title,
        c.category_name,
        l.location_name,
        e.start_datetime,
        e.end_datetime,
        -- Kontenjanı anlık hesapla
        (e.capacity - (SELECT COUNT(*) FROM event_participations WHERE event_id = e.event_id)) AS available_seats
    FROM events e
    LEFT JOIN event_categories c ON e.category_id = c.category_id
    LEFT JOIN locations l ON e.location_id = l.location_id
    WHERE e.status != 'cancelled'           -- 1. İptal edilenleri gösterme
      AND e.status != 'completed'           -- 2. Tamamlananları gösterme
      AND e.start_datetime > NOW()          -- 3. Sadece gelecekteki etkinlikler
    GROUP BY e.event_id
    ORDER BY e.start_datetime ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserEventList` (IN `p_user_id` INT)   BEGIN
    SELECT 
        e.event_id,
        e.event_title,
        e.start_datetime,
        e.end_datetime,
        l.location_name,
        ec.category_name
    FROM event_participations AS ep
    INNER JOIN events AS e
        ON ep.event_id = e.event_id
    INNER JOIN locations AS l
        ON e.location_id = l.location_id
    INNER JOIN event_categories AS ec
        ON e.category_id = ec.category_id
    WHERE ep.user_id = p_user_id
    ORDER BY e.start_datetime;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserNotifications` (IN `p_user_id` INT)   BEGIN
    SELECT
        notification_id,
        event_id,
        message,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = p_user_id
    ORDER BY created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `MarkNotificationAsRead` (IN `p_notification_id` INT)   BEGIN
    UPDATE notifications
    SET is_read = 1
    WHERE notification_id = p_notification_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `RegisterToEvent` (IN `p_event_id` INT, IN `p_user_id` INT)   BEGIN
    -- Aynı kullanıcı aynı etkinliğe iki kez yazılmasın
    IF NOT EXISTS (
        SELECT 1 FROM event_participations
        WHERE event_id = p_event_id
          AND user_id = p_user_id
    ) THEN
        INSERT INTO event_participations (
            event_id,
            user_id,
            registration_datetime,
            attendance_status
        )
        VALUES (
            p_event_id,
            p_user_id,
            NOW(),
            'registered'
        );

        INSERT INTO notifications (
            user_id,
            event_id,
            message,
            is_read,
            created_at
        )
        VALUES (
            p_user_id,
            p_event_id,
            'Etkinlik kaydınız onaylandı.',
            0,
            NOW()
        );
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SearchEvents` (IN `search_term` VARCHAR(255))   BEGIN
    SELECT 
        e.event_id, 
        e.event_title, 
        e.start_datetime, 
        e.end_datetime, 
        ec.category_name, 
        l.location_name,
        -- Kontenjanı anlık olarak hesaplayan alt sorgu
        (e.capacity - (SELECT COUNT(*) FROM event_participations WHERE event_id = e.event_id)) AS available_seats
    FROM events e
    LEFT JOIN event_categories ec ON e.category_id = ec.category_id
    LEFT JOIN locations l ON e.location_id = l.location_id
    WHERE (
        e.event_title LIKE CONCAT('%', search_term, '%') 
        OR e.event_description LIKE CONCAT('%', search_term, '%')
        OR ec.category_name LIKE CONCAT('%', search_term, '%')
        OR l.location_name LIKE CONCAT('%', search_term, '%')
        -- YENİ: Tarih araması (Örn: 2026-03 yazınca Mart etkinlikleri gelir)
        OR DATE_FORMAT(e.start_datetime, '%Y-%m-%d') LIKE CONCAT('%', search_term, '%')
    )
    AND e.status = 'planned'             -- Sadece planlanmış olanlar
    AND e.start_datetime > NOW()         -- Sadece süresi geçmemiş (gelecekteki) olanlar
    ORDER BY e.start_datetime ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateEventStatus` (IN `p_event_id` INT, IN `p_new_status` VARCHAR(20))   BEGIN
    UPDATE events
    SET status = p_new_status
    WHERE event_id = p_event_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `faculty_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `faculty_name`) VALUES
(1, 'Bilgisayar Mühendisliği', 'Mühendislik Fakültesi'),
(2, 'Yazılım Mühendisliği', 'Mühendislik Fakültesi'),
(3, 'Elektrik-Elektronik Mühendisliği', 'Mühendislik Fakültesi'),
(4, 'Endüstri Mühendisliği', 'Mühendislik Fakültesi'),
(5, 'İşletme', 'İktisadi ve İdari Bilimler Fakültesi'),
(6, 'Psikoloji', 'Fen-Edebiyat Fakültesi'),
(7, 'Sosyoloji', 'Fen-Edebiyat Fakültesi'),
(8, 'Hukuk', 'Hukuk Fakültesi'),
(9, 'Tıp', 'Tıp Fakültesi'),
(10, 'Diş Hekimliği', 'Diş Hekimliği Fakültesi'),
(11, 'Endüstriyel Tasarım', 'Mimarlık Fakültesi');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `department_events`
--

CREATE TABLE `department_events` (
  `department_event_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `is_main_department` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `department_events`
--

INSERT INTO `department_events` (`department_event_id`, `department_id`, `event_id`, `is_main_department`) VALUES
(1, 1, 1, 1),
(2, 1, 3, 1),
(3, 2, 3, 0),
(4, 1, 7, 1),
(5, 5, 5, 1),
(6, 6, 8, 1),
(7, 9, 2, 1),
(8, 1, 2, 0),
(9, 1, 6, 1),
(10, 2, 6, 0),
(11, 11, 1, 1),
(12, 1, 1, 1),
(13, 2, 1, 1),
(14, 1, 2, 1),
(15, 5, 5, 1),
(16, 1, 16, 1),
(17, 3, 16, 1),
(18, 4, 16, 1),
(19, 11, 16, 1),
(20, 2, 16, 1),
(21, 6, 17, 1),
(22, 7, 17, 1),
(23, 1, 18, 1),
(24, 2, 18, 1),
(25, 1, 19, 1),
(26, 10, 19, 1),
(27, 3, 19, 1),
(28, 4, 19, 1),
(29, 11, 19, 1),
(30, 8, 19, 1),
(31, 5, 19, 1),
(32, 6, 19, 1),
(33, 7, 19, 1),
(34, 9, 19, 1),
(35, 2, 19, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `event_title` varchar(150) NOT NULL,
  `event_description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `capacity` int(11) NOT NULL,
  `available_seats` int(11) NOT NULL,
  `status` enum('planned','active','completed','cancelled') NOT NULL DEFAULT 'planned',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `events`
--

INSERT INTO `events` (`event_id`, `category_id`, `location_id`, `event_title`, `event_description`, `start_datetime`, `end_datetime`, `capacity`, `available_seats`, `status`, `created_at`) VALUES
(1, 1, 1, 'Web Programlama Semineri', 'PHP, SQL ve backend temel konuları', '2025-12-10 10:00:00', '2025-12-10 12:00:00', 180, 179, 'planned', '2025-12-11 14:00:40'),
(2, 2, 3, 'Yapay Zeka Konferansı', 'Üniversite genel AI konuşmacıları', '2025-12-12 09:00:00', '2025-12-12 17:00:00', 350, 349, 'planned', '2025-12-11 14:00:40'),
(3, 3, 5, 'Git ve GitHub Atölyesi', 'Versiyon kontrol uygulamalı eğitim', '2025-12-15 13:00:00', '2025-12-15 16:00:00', 35, 35, 'planned', '2025-12-11 14:00:40'),
(4, 4, 4, 'Kampüs Kupa Basketbol Turnuvası', 'Fakülteler arası turnuva', '2025-12-20 14:00:00', '2025-12-20 18:00:00', 400, 499, 'planned', '2025-12-11 14:00:40'),
(5, 5, 3, 'Kariyer Buluşmaları 2025', 'Şirketlerle birebir görüşme olacak.', '2025-12-18 10:00:00', '2025-12-18 16:00:00', 350, 349, 'planned', '2025-12-11 14:00:40'),
(6, 6, 7, 'Bilişim Kulübü Haftalık Toplantı', 'Proje değerlendirmeleri', '2025-12-08 17:00:00', '2025-12-08 18:30:00', 40, 40, 'planned', '2025-12-11 14:00:40'),
(7, 7, 9, 'Veritabanı Tasarımı Webinarı', 'SQL, normalization, ER diyagram', '2025-12-22 19:00:00', '2025-12-22 21:00:00', 1000, 998, 'planned', '2025-12-11 14:00:40'),
(8, 8, 10, 'Fidan Dikme Gönüllülük Etkinliği', 'Kampüs çevre çalışması', '2025-12-25 09:00:00', '2025-12-25 13:00:00', 800, 798, 'planned', '2025-12-11 14:00:40'),
(9, 9, 2, 'Teknokent Firma Gezisi', 'Ar-Ge firmaları ziyareti', '2025-12-28 08:00:00', '2025-12-28 18:00:00', 150, 148, 'planned', '2025-12-11 14:00:40'),
(14, 2, 3, 'Kendini Tanıma', 'İçsel Yolculuk', '2025-12-23 10:30:00', '2025-12-23 12:00:00', 1, 0, 'cancelled', '2025-12-18 21:03:39'),
(16, 5, 6, 'C++ Öğren ', 'Cpp nasıl bir dildir ve nasıl öğrenilir.', '2026-01-30 10:00:00', '2026-01-30 15:00:00', 200, 197, 'planned', '2025-12-20 02:03:11'),
(17, 1, 2, 'İçindeki Çocukla Barış', 'Küçükken yaşadığınız travmalarla yüzleşin ve onunla barışın', '2026-02-28 12:30:00', '2026-02-28 14:30:00', 60, 58, 'planned', '2025-12-20 02:07:39'),
(18, 1, 5, 'Siber Güvenlik 101', 'Etik hackerlık temelleri.', '2026-03-02 10:00:00', '2026-03-02 13:00:00', 40, 40, 'planned', '2025-12-21 17:06:08'),
(19, 2, 1, 'AI ve Gelecek', 'Yapay zeka dünyayı nasıl değiştiriyor?', '2026-03-04 13:00:00', '2026-03-04 17:00:00', 200, 200, 'planned', '2025-12-21 17:08:14'),
(20, 3, 5, 'Bahar Tenis Turnuvası', 'Fakülteler arası eleme usulü tenis maçları.', '2026-03-06 09:00:00', '2026-03-06 17:00:00', 32, 32, 'planned', '2025-12-21 17:09:51'),
(21, 5, 8, 'CV ve Mülakat Teknikleri', 'İnsan kaynakları uzmanlarıyla birebir mülakat hazırlığı.', '2026-03-09 14:00:00', '2026-03-09 16:00:00', 50, 50, 'planned', '2025-12-21 17:09:51'),
(22, 3, 6, 'Blockchain Atölyesi', 'Akıllı sözleşme yazımı ve blockchain temelleri uygulaması.', '2026-03-11 15:00:00', '2026-03-11 18:00:00', 30, 30, 'planned', '2025-12-21 17:09:51'),
(23, 8, 3, 'Sıfır Atık Kampüsü', 'Sıfır atık projesi ve geri dönüşüm farkındalık çalışması.', '2026-03-13 11:00:00', '2026-03-13 13:00:00', 150, 150, 'planned', '2025-12-21 17:09:51'),
(24, 9, 10, 'Teknokent Gezisi', 'Ar-Ge merkezlerini ve start-up ofislerini ziyaret.', '2026-03-16 08:30:00', '2026-03-16 17:00:00', 45, 45, 'planned', '2025-12-21 17:09:51'),
(25, 7, 9, 'Hukukta Dijital Dönüşüm', 'Bilişim hukuku ve dijital haklar üzerine online seminer.', '2026-03-18 19:00:00', '2026-03-18 21:00:00', 500, 500, 'planned', '2025-12-21 17:09:51'),
(26, 6, 7, 'Robotik Takımı Toplantısı', 'TEKNOFEST projeleri haftalık ilerleme değerlendirmesi.', '2026-03-20 17:00:00', '2026-03-20 18:30:00', 20, 20, 'planned', '2025-12-21 17:09:51'),
(27, 2, 2, 'Tıpta Uzmanlık Paneli', 'TUS süreci, branş seçimi ve asistanlık dönemi hakkında bilgiler.', '2026-03-23 10:00:00', '2026-03-23 13:00:00', 180, 180, 'planned', '2025-12-21 17:09:51'),
(28, 3, 5, 'Python ile Veri Analizi', 'Pandas ve Matplotlib kütüphaneleri ile veri görselleştirme.', '2026-03-24 13:00:00', '2026-03-24 16:00:00', 40, 40, 'planned', '2025-12-21 17:09:51'),
(29, 10, 1, 'Yeni Öğrenci Oryantasyonu', 'Üniversite hayatına uyum ve kampüs olanakları tanıtımı.', '2026-03-25 09:00:00', '2026-03-25 12:00:00', 300, 300, 'planned', '2025-12-21 17:09:51'),
(30, 3, 6, 'UX Design Sprint', 'Kullanıcı deneyimi (UX) tasarımı ve prototipleme atölyesi.', '2026-03-26 14:00:00', '2026-03-26 17:00:00', 30, 30, 'planned', '2025-12-21 17:09:51'),
(31, 4, 4, 'Basketbol Şenliği', 'Fakülteler arası dostluk maçları ve smaç yarışması.', '2026-03-27 16:00:00', '2026-03-27 19:00:00', 100, 100, 'planned', '2025-12-21 17:09:51'),
(32, 5, 8, 'İş Dünyasında Network', 'Mezunlar ve sektör profesyonelleri ile networking gecesi.', '2026-03-30 18:00:00', '2026-03-30 20:30:00', 80, 80, 'planned', '2025-12-21 17:09:51');

--
-- Tetikleyiciler `events`
--
DELIMITER $$
CREATE TRIGGER `trg_after_event_cancelled` AFTER UPDATE ON `events` FOR EACH ROW BEGIN
    IF NEW.status = 'cancelled' AND OLD.status <> 'cancelled' THEN
        
        -- 3.1) Katılımları 'cancelled' yap
        UPDATE event_participations
        SET attendance_status = 'cancelled'
        WHERE event_id = NEW.event_id;

        -- 3.2) Tüm katılımcılara iptal bildirimi ekle
        INSERT INTO notifications (user_id, event_id, message, is_read, created_at)
        SELECT DISTINCT ep.user_id,
               NEW.event_id,
               'Katıldığınız etkinlik iptal edildi.',
               0,
               NOW()
        FROM event_participations AS ep
        WHERE ep.event_id = NEW.event_id;

        -- 3.3) system_logs tablosuna log kaydı
        INSERT INTO system_logs (user_id, action_type, description, created_at)
        VALUES (
            NULL,
            'event_cancelled',
            CONCAT('Etkinlik iptal edildi. event_id = ', NEW.event_id),
            NOW()
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `event_categories`
--

CREATE TABLE `event_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `event_categories`
--

INSERT INTO `event_categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Seminer', 'Bilimsel sunum'),
(2, 'Konferans', 'Geniş katılımlı konuşma'),
(3, 'Atölye', 'Uygulamalı eğitim'),
(4, 'Spor', 'Sportif faaliyet'),
(5, 'Kariyer', 'İşe alım ve tanıtım'),
(6, 'Kulüp Toplantısı', 'Öğrenci kulübü organizasyonları'),
(7, 'Webinar', 'Online eğitim'),
(8, 'Sosyal Sorumluluk', 'Topluma yönelik etkinlik'),
(9, 'Teknik Gezi', 'Saha ziyareti'),
(10, 'Oryantasyon', 'Üniversite tanıtım programı');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `event_feedbacks`
--

CREATE TABLE `event_feedbacks` (
  `feedback_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `event_feedbacks`
--

INSERT INTO `event_feedbacks` (`feedback_id`, `event_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 4, 5, 'Sunum çok anlaşılırdı.', '2025-12-11 14:00:40'),
(2, 1, 5, 4, 'Eğitmen konulara hakimdi.', '2025-12-11 14:00:40'),
(3, 2, 4, 5, 'Konferans çok bilgilendiriciydi.', '2025-12-11 14:00:40'),
(4, 2, 6, 4, 'Bazı bölümler uzun sürdü.', '2025-12-11 14:00:40'),
(5, 3, 4, 5, 'Atölye çok verimliydi.', '2025-12-11 14:00:40'),
(6, 3, 5, 5, 'Uygulama kısımları çok iyiydi.', '2025-12-11 14:00:40'),
(7, 5, 4, 4, 'Firmalarla görüşmeler faydalıydı.', '2025-12-11 14:00:40'),
(8, 6, 9, 5, 'Kulüp toplantısı güzel geçti.', '2025-12-11 14:00:40'),
(9, 7, 4, 5, 'Webinar kaydı da paylaşılsa çok iyi olur.', '2025-12-11 14:00:40'),
(10, 8, 5, 5, 'Çevre etkinliği harikaydı.', '2025-12-11 14:00:40'),
(11, 1, 17, 5, 'güzel etkinlik', '2025-12-18 17:49:53'),
(14, 2, 17, 5, '', '2025-12-18 17:51:48'),
(15, 1, 17, 5, 'güzel', '2025-12-18 17:57:09'),
(16, 5, 17, 5, 'harika', '2025-12-18 18:16:57'),
(17, 6, 17, 3, 'benim için uygun bir etkinlik değildi', '2025-12-19 15:52:49'),
(18, 1, 6, 4, 'Faydalı etkinlikti.', '2025-12-20 01:51:56');

--
-- Tetikleyiciler `event_feedbacks`
--
DELIMITER $$
CREATE TRIGGER `After_Feedback_Submission` AFTER INSERT ON `event_feedbacks` FOR EACH ROW BEGIN
    -- 1. Öğrenciye Bildirim Gönder
    INSERT INTO notifications (user_id, event_id, message, created_at)
    VALUES (NEW.user_id, NEW.event_id, 'Geri bildiriminiz başarıyla iletildi. Teşekkür ederiz!', NOW());

    -- 2. Sistem Loglarına Kaydet
    INSERT INTO system_logs (user_id, action_type, description, created_at)
    VALUES (NEW.user_id, 'feedback_submitted', CONCAT('Kullanıcı (ID: ', NEW.user_id, ') ', NEW.event_id, ' nolu etkinlik için geri bildirim yaptı.'), NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `event_organizers`
--

CREATE TABLE `event_organizers` (
  `event_organizer_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_in_event` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `event_organizers`
--

INSERT INTO `event_organizers` (`event_organizer_id`, `event_id`, `user_id`, `role_in_event`) VALUES
(1, 1, 2, 'Sunum Sorumlusu'),
(2, 1, 7, 'Konuşmacı'),
(3, 2, 3, 'Organizasyon Lideri'),
(4, 2, 8, 'Konuşmacı'),
(5, 3, 2, 'Eğitmen'),
(6, 4, 3, 'Turnuva Sorumlusu'),
(7, 5, 9, 'Kulüp Koordinatörü'),
(8, 6, 9, 'Kulüp Başkanı'),
(9, 7, 8, 'Eğitmen'),
(10, 8, 3, 'Sorumlu'),
(16, 1, 25, 'Event Manager'),
(17, 2, 25, 'Event Manager'),
(18, 3, 25, 'Event Manager'),
(19, 4, 25, 'Event Manager'),
(20, 5, 25, 'Event Manager'),
(21, 6, 25, 'Event Manager'),
(27, 1, 25, 'Event Manager'),
(28, 2, 3, 'Event Manager'),
(29, 2, 2, 'Event Manager'),
(30, 14, 25, 'Ana Organizatör'),
(32, 16, 3, 'Ana Organizatör'),
(33, 17, 3, 'Ana Organizatör'),
(34, 18, 25, 'Ana Organizatör'),
(35, 19, 25, 'Ana Organizatör');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `event_participations`
--

CREATE TABLE `event_participations` (
  `participation_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `attendance_status` enum('registered','attended','cancelled','no_show') NOT NULL DEFAULT 'registered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `event_participations`
--

INSERT INTO `event_participations` (`participation_id`, `event_id`, `user_id`, `registration_datetime`, `attendance_status`) VALUES
(1, 1, 4, '2025-12-11 14:00:40', 'registered'),
(2, 1, 5, '2025-12-11 14:00:40', 'registered'),
(3, 1, 6, '2025-12-11 14:00:40', 'registered'),
(4, 2, 4, '2025-12-11 14:00:40', 'registered'),
(5, 2, 5, '2025-12-11 14:00:40', 'registered'),
(6, 3, 4, '2025-12-11 14:00:40', 'registered'),
(7, 3, 5, '2025-12-11 14:00:40', 'registered'),
(8, 5, 4, '2025-12-11 14:00:40', 'registered'),
(9, 6, 9, '2025-12-11 14:00:40', 'registered'),
(10, 7, 4, '2025-12-11 14:00:40', 'registered'),
(44, 1, 17, '2025-12-19 16:00:04', 'registered'),
(45, 2, 17, '2025-12-20 01:16:40', 'registered'),
(46, 5, 17, '2025-12-20 01:28:20', 'registered'),
(47, 14, 17, '2025-12-20 01:29:15', 'cancelled'),
(49, 4, 17, '2025-12-20 01:35:18', 'registered'),
(50, 16, 4, '2025-12-20 02:04:40', 'registered'),
(51, 17, 17, '2025-12-21 16:50:04', 'registered'),
(52, 16, 17, '2025-12-21 16:50:26', 'registered'),
(53, 9, 17, '2025-12-21 16:50:31', 'registered'),
(54, 8, 17, '2025-12-21 16:50:32', 'registered'),
(61, 7, 17, '2025-12-21 17:10:29', 'registered');

--
-- Tetikleyiciler `event_participations`
--
DELIMITER $$
CREATE TRIGGER `After_Event_Registration` AFTER INSERT ON `event_participations` FOR EACH ROW BEGIN
    -- 1. Öğrenciye Bildirim Gönder
    INSERT INTO notifications (user_id, event_id, message, created_at)
    VALUES (NEW.user_id, NEW.event_id, 'Etkinliğe kaydınız başarıyla alındı.', NOW());

    -- 2. Sistem Loglarına Kaydet
    INSERT INTO system_logs (user_id, action_type, description, created_at)
    VALUES (NEW.user_id, 'event_registration', CONCAT('Kullanıcı (ID: ', NEW.user_id, ') ', NEW.event_id, ' nolu etkinliğe kayıt oldu.'), NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `After_Event_Unregistration` AFTER DELETE ON `event_participations` FOR EACH ROW BEGIN
    -- 1. Öğrenciye Bildirim Gönder
    INSERT INTO notifications (user_id, event_id, message, created_at)
    VALUES (OLD.user_id, OLD.event_id, 'Etkinlik kaydınız iptal edildi.', NOW());

    -- 2. Sistem Loglarına Kaydet
    INSERT INTO system_logs (user_id, action_type, description, created_at)
    VALUES (OLD.user_id, 'event_unregistration', CONCAT('Kullanıcı (ID: ', OLD.user_id, ') ', OLD.event_id, ' nolu etkinlik kaydını sildi.'), NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_event_participation_delete` AFTER DELETE ON `event_participations` FOR EACH ROW BEGIN
    UPDATE events
    SET available_seats = available_seats + 1
    WHERE event_id = OLD.event_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_event_participation_insert` AFTER INSERT ON `event_participations` FOR EACH ROW BEGIN
    UPDATE events
    SET available_seats = CASE
        WHEN available_seats > 0 THEN available_seats - 1
        ELSE 0
    END
    WHERE event_id = NEW.event_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `locations`
--

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `building_name` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `room_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `locations`
--

INSERT INTO `locations` (`location_id`, `location_name`, `building_name`, `capacity`, `room_number`) VALUES
(1, 'Mavi Amfi', 'Mühendislik Binası', 180, 'MA-01'),
(2, 'Kırmızı Amfi', 'Mühendislik Binası', 150, 'KA-02'),
(3, 'Konferans Salonu', 'Kültür Merkezi', 350, 'KS-01'),
(4, 'Spor Salonu', 'Spor Merkezi', 500, 'SP-01'),
(5, 'Bilgisayar Lab 1', 'Mühendislik Binası', 35, 'LAB-101'),
(6, 'Bilgisayar Lab 2', 'Mühendislik Binası', 35, 'LAB-102'),
(7, 'Fen Toplantı Odası', 'Fen-Edebiyat Binası', 40, 'FEN-11'),
(8, 'İİBF Toplantı Odası', 'İİBF Binası', 40, 'IIBF-05'),
(9, 'Online', 'Zoom Platformu', 1000, 'ZOOM'),
(10, 'Açık Alan', 'Kampüs Meydanı', 800, 'MEYDAN-1');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `event_id`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 1, 'Seminer kaydınız onaylandı.', 1, '2025-12-11 14:00:40'),
(2, 5, 1, 'Seminer kaydınız onaylandı.', 0, '2025-12-11 14:00:40'),
(3, 6, 1, 'Seminer kaydınız onaylandı.', 1, '2025-12-11 14:00:40'),
(4, 4, 2, 'Konferans için hatırlatma.', 1, '2025-12-11 14:00:40'),
(5, 4, 3, 'Atölye eğitimi için bilgi.', 1, '2025-12-11 14:00:40'),
(6, 9, NULL, 'Kulüp etkinliği duyurusu.', 0, '2025-12-11 14:00:40'),
(7, 5, 7, 'Webinar bağlantısı gönderildi.', 0, '2025-12-11 14:00:40'),
(8, 4, 8, 'Fidan dikme etkinliği yaklaşıyor.', 1, '2025-12-11 14:00:40'),
(9, 4, 5, 'Kariyer günü yarın başlıyor.', 1, '2025-12-11 14:00:40'),
(10, 5, 9, 'Teknokent gezisi duyurusu.', 0, '2025-12-11 14:00:40'),
(11, 17, 1, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-19 15:52:16'),
(12, 17, 3, 'Etkinliğe kaydınız başarıyla alındı.', 1, '2025-12-19 15:52:22'),
(13, 17, 6, 'Geri bildiriminiz başarıyla iletildi. Teşekkür ederiz!', 1, '2025-12-19 15:52:49'),
(14, 17, 4, 'Etkinliğe kaydınız başarıyla alındı.', 1, '2025-12-19 15:58:01'),
(15, 17, 1, 'Etkinliğe kaydınız başarıyla alındı.', 1, '2025-12-19 16:00:04'),
(16, 17, 2, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:16:20'),
(17, 17, 2, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:16:20'),
(18, 17, 6, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:16:30'),
(19, 17, 6, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:16:30'),
(20, 17, 2, 'Etkinliğe kaydınız başarıyla alındı.', 1, '2025-12-20 01:16:40'),
(21, 17, 2, 'Etkinlik kaydınız onaylandı.', 1, '2025-12-20 01:16:40'),
(22, 17, 5, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:23:29'),
(23, 17, 5, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:23:29'),
(24, 17, 5, 'Etkinliğe kaydınız başarıyla alındı.', 1, '2025-12-20 01:28:20'),
(25, 17, 5, 'Etkinlik kaydınız onaylandı.', 1, '2025-12-20 01:28:20'),
(26, 17, 3, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:28:26'),
(27, 17, 3, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:28:26'),
(28, 17, 14, 'Katıldığınız etkinlik iptal edildi.', 1, '2025-12-20 01:28:45'),
(29, 17, 14, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:29:12'),
(30, 17, 14, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:29:12'),
(31, 17, 14, 'Etkinliğe kaydınız başarıyla alındı.', 1, '2025-12-20 01:29:15'),
(32, 17, 14, 'Etkinlik kaydınız onaylandı.', 1, '2025-12-20 01:29:15'),
(33, 17, 14, 'Katıldığınız etkinlik iptal edildi.', 1, '2025-12-20 01:34:48'),
(34, 17, 8, 'Etkinliğe kaydınız başarıyla alındı.', 1, '2025-12-20 01:35:12'),
(35, 17, 8, 'Etkinlik kaydınız onaylandı.', 1, '2025-12-20 01:35:12'),
(36, 17, 8, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:35:13'),
(37, 17, 8, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:35:13'),
(38, 17, 7, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:35:14'),
(39, 17, 7, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:35:14'),
(40, 17, 4, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:35:15'),
(41, 17, 4, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:35:15'),
(42, 17, 9, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:35:16'),
(43, 17, 9, 'Etkinlik kaydınız iptal edildi.', 1, '2025-12-20 01:35:16'),
(44, 17, 4, 'Etkinliğe kaydınız başarıyla alındı.', 1, '2025-12-20 01:35:18'),
(45, 17, 4, 'Etkinlik kaydınız onaylandı.', 1, '2025-12-20 01:35:18'),
(46, 6, 1, 'Geri bildiriminiz başarıyla iletildi. Teşekkür ederiz!', 1, '2025-12-20 01:51:56'),
(47, 4, 16, 'Etkinliğe kaydınız başarıyla alındı.', 0, '2025-12-20 02:04:40'),
(48, 4, 16, 'Etkinlik kaydınız onaylandı.', 0, '2025-12-20 02:04:40'),
(49, 17, 17, 'Etkinliğe kaydınız başarıyla alındı.', 0, '2025-12-21 16:50:04'),
(50, 17, 17, 'Etkinlik kaydınız onaylandı.', 0, '2025-12-21 16:50:04'),
(51, 17, 16, 'Etkinliğe kaydınız başarıyla alındı.', 0, '2025-12-21 16:50:26'),
(52, 17, 16, 'Etkinlik kaydınız onaylandı.', 0, '2025-12-21 16:50:26'),
(53, 17, 9, 'Etkinliğe kaydınız başarıyla alındı.', 0, '2025-12-21 16:50:31'),
(54, 17, 9, 'Etkinlik kaydınız onaylandı.', 0, '2025-12-21 16:50:31'),
(55, 17, 8, 'Etkinliğe kaydınız başarıyla alındı.', 0, '2025-12-21 16:50:32'),
(56, 17, 8, 'Etkinlik kaydınız onaylandı.', 0, '2025-12-21 16:50:32'),
(57, 17, 7, 'Etkinliğe kaydınız başarıyla alındı.', 0, '2025-12-21 16:50:33'),
(58, 17, 7, 'Etkinlik kaydınız onaylandı.', 0, '2025-12-21 16:50:33'),
(69, 17, 7, 'Etkinlik kaydınız iptal edildi.', 0, '2025-12-21 17:10:28'),
(70, 17, 7, 'Etkinlik kaydınız iptal edildi.', 0, '2025-12-21 17:10:28'),
(71, 17, 7, 'Etkinliğe kaydınız başarıyla alındı.', 0, '2025-12-21 17:10:29'),
(72, 17, 7, 'Etkinlik kaydınız onaylandı.', 0, '2025-12-21 17:10:29');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'admin', 'Sistem yöneticisi'),
(2, 'event_manager', 'Etkinlik yönetim sorumlusu'),
(3, 'student', 'Lisans öğrencisi'),
(4, 'academic_staff', 'Akademik personel'),
(5, 'guest', 'Geçici kullanıcı'),
(6, 'club_president', 'Öğrenci kulübü başkanı'),
(7, 'club_member', 'Öğrenci kulübü üyesi'),
(8, 'it_support', 'Teknik destek personeli'),
(9, 'moderator', 'Etkinlik moderatörü'),
(10, 'security', 'Güvenlik görevlisi');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `user_id`, `action_type`, `description`, `created_at`) VALUES
(1, 1, 'login', 'Admin sisteme giriş yaptı.', '2025-12-11 14:00:40'),
(2, 2, 'create_event', 'Ayşe Demir yeni etkinlik oluşturdu (event_id=1).', '2025-12-11 14:00:40'),
(3, 3, 'create_event', 'Selim Aydın yeni etkinlik oluşturdu (event_id=2).', '2025-12-11 14:00:40'),
(4, 4, 'register_event', 'Zeynep Koç, event_id=1 için kayıt oldu.', '2025-12-11 14:00:40'),
(5, 5, 'register_event', 'Emre Can, event_id=1 için kayıt oldu.', '2025-12-11 14:00:40'),
(6, 6, 'register_event', 'Elif Kaya, event_id=1 için kayıt oldu.', '2025-12-11 14:00:40'),
(7, 4, 'register_event', 'Zeynep Koç, event_id=2 için kayıt oldu.', '2025-12-11 14:00:40'),
(8, 9, 'login', 'Selda Ertuğ sisteme giriş yaptı.', '2025-12-11 14:00:40'),
(9, 4, 'give_feedback', 'Zeynep Koç, event_id=1 için geri bildirim verdi.', '2025-12-11 14:00:40'),
(10, 5, 'give_feedback', 'Emre Can, event_id=1 için geri bildirim verdi.', '2025-12-11 14:00:40'),
(11, 17, 'login', 'Student Kullanıcı (Rol ID: 3) sisteme giriş yaptı.', '2025-12-18 13:37:06'),
(12, 17, 'event_registration', 'Student Kullanıcı ID: 7 olan etkinliğe kayıt oldu.', '2025-12-18 13:37:10'),
(13, 15, 'login', 'Admin Kullanıcı (Rol ID: 1) sisteme giriş yaptı.', '2025-12-18 13:37:29'),
(14, 15, 'login', 'Admin Kullanıcı (Rol ID: 1) sisteme giriş yaptı.', '2025-12-18 13:43:26'),
(15, 15, 'role_change', 'Admin Kullanıcı, Admin Kullanıcı kullanıcısının rolünü \'admin\' seviyesinden \'event_manager\' seviyesine güncelledi.', '2025-12-18 13:43:46'),
(16, 17, 'login', 'Student Kullanıcı (Rol ID: 3) sisteme giriş yaptı.', '2025-12-18 13:44:51'),
(17, 17, 'event_unregistration', 'Student Kullanıcı ID: 6 olan etkinlik kaydını iptal etti.', '2025-12-18 13:44:54'),
(18, 15, 'login', 'Admin Kullanıcı (Rol ID: 2) sisteme giriş yaptı.', '2025-12-18 13:45:04'),
(19, 15, 'login', 'Admin Kullanıcı (Rol ID: 2) sisteme giriş yaptı.', '2025-12-18 13:45:26'),
(20, 15, 'login', 'Admin Kullanıcı (Rol ID: 1) sisteme giriş yaptı.', '2025-12-18 13:50:19'),
(21, 25, 'login', 'Ahmet Yılmaz (Rol ID: 2) sisteme giriş yaptı.', '2025-12-18 13:50:56'),
(22, 15, 'login', 'Admin Kullanıcı (Rol ID: 1) sisteme giriş yaptı.', '2025-12-18 13:51:22'),
(23, 25, 'login', 'Organizer (Event Manager) (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-18 13:53:35'),
(24, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 13:53:52'),
(25, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-18 13:56:39'),
(26, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 13:56:53'),
(27, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 14:23:23'),
(28, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-18 14:23:36'),
(29, 25, 'view_list', 'Ahmet Yılmaz (ID: 4) nolu etkinliğin organizatörlerini inceledi.', '2025-12-18 14:23:38'),
(30, 25, 'view_list', 'Ahmet Yılmaz (ID: 4) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-18 14:23:42'),
(31, 25, 'view_list', 'Ahmet Yılmaz (ID: 13) nolu etkinliğin organizatörlerini inceledi.', '2025-12-18 14:23:45'),
(32, 25, 'view_list', 'Ahmet Yılmaz (ID: 13) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-18 14:23:48'),
(33, 25, 'view_list', 'Ahmet Yılmaz (ID: 5) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-18 14:23:51'),
(34, 25, 'view_list', 'Ahmet Yılmaz (ID: 2) nolu etkinliğin organizatörlerini inceledi.', '2025-12-18 14:23:54'),
(35, 25, 'delete_event', 'Ahmet Yılmaz şu etkinliği ve tüm verilerini sildi: futbol', '2025-12-18 14:24:00'),
(36, 25, 'update_event', 'Ahmet Yılmaz şu etkinliği güncelledi: seminer (ID: 12)', '2025-12-18 14:24:14'),
(37, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 14:24:24'),
(38, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-18 14:28:47'),
(39, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-18 14:44:13'),
(40, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-18 14:47:39'),
(41, 17, 'event_registration', 'Student Kullanıcı ID: 3 olan etkinliğe kayıt oldu.', '2025-12-18 14:57:39'),
(42, 17, 'give_feedback', 'Student Kullanıcı, 1 ID\'li etkinlik için 5 puanlık geri bildirim verdi.', '2025-12-18 17:49:53'),
(43, 17, 'give_feedback', 'Student Kullanıcı, 12 ID\'li etkinlik için 3 puanlık geri bildirim verdi.', '2025-12-18 17:50:07'),
(44, 17, 'give_feedback', 'Student Kullanıcı, 12 ID\'li etkinlik için 5 puan ve yorum bıraktı.', '2025-12-18 17:51:29'),
(45, 17, 'give_feedback', 'Student Kullanıcı, 2 ID\'li etkinlik için 5 puan ve yorum bıraktı.', '2025-12-18 17:51:48'),
(46, 17, 'give_feedback', 'Student Kullanıcı, 1 ID\'li etkinlik için 5 puan ve yorum bıraktı.', '2025-12-18 17:57:09'),
(47, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 17:57:26'),
(48, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-18 17:59:18'),
(49, 25, 'view_list', 'Ahmet Yılmaz (ID: 4) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-18 17:59:28'),
(50, 25, 'view_list', 'Ahmet Yılmaz (ID: 12) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-18 17:59:31'),
(51, 25, 'view_list', 'Ahmet Yılmaz (ID: 12) nolu etkinliğin organizatörlerini inceledi.', '2025-12-18 17:59:33'),
(52, 25, 'update_event', 'Ahmet Yılmaz şu etkinliği güncelledi: Kampüs Kupa Basketbol Turnuvası (ID: 4)', '2025-12-18 17:59:37'),
(53, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 18:12:30'),
(54, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 18:13:35'),
(55, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-18 18:14:12'),
(56, 25, 'view_list', 'Ahmet Yılmaz (ID: 4) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-18 18:14:19'),
(57, 25, 'view_list', 'Ahmet Yılmaz (ID: 5) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-18 18:14:22'),
(58, 25, 'update_event', 'Ahmet Yılmaz şu etkinliği güncelledi: Kariyer Buluşmaları 2025 (ID: 5)', '2025-12-18 18:14:42'),
(59, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 12) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-18 18:14:54'),
(60, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 3) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-18 18:14:59'),
(61, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 18:15:17'),
(62, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-18 18:15:52'),
(63, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-18 18:16:34'),
(64, 17, 'give_feedback', 'Student Kullanıcı, 5 ID\'li etkinlik için 5 puan ve yorum bıraktı.', '2025-12-18 18:16:57'),
(65, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 18:17:12'),
(66, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-18 18:17:55'),
(67, 25, 'view_list', 'Ahmet Yılmaz (ID: 5) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-18 18:18:03'),
(68, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 12) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-18 18:18:08'),
(69, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 6) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-18 18:18:15'),
(70, 25, 'delete_event', 'Ahmet Yılmaz şu etkinliği ve tüm verilerini sildi: seminer', '2025-12-18 18:18:28'),
(71, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 18:18:37'),
(72, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 21:00:46'),
(73, 15, 'role_change', 'Admin Kullanıcı, Student Kullanıcı kullanıcısının rolünü \'student\' seviyesinden \'admin\' seviyesine güncelledi.', '2025-12-18 21:01:00'),
(74, 15, 'role_change', 'Admin Kullanıcı, Ayşe Demir kullanıcısının rolünü \'admin\' seviyesinden \'event_manager\' seviyesine güncelledi.', '2025-12-18 21:01:14'),
(75, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-18 21:01:50'),
(76, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 5) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-18 21:01:52'),
(77, 25, 'view_list', 'Ahmet Yılmaz (ID: 1) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-18 21:02:22'),
(78, 25, 'create_event', 'Ahmet Yılmaz yeni bir etkinlik oluşturdu: Kendini Tanıma', '2025-12-18 21:03:39'),
(79, 17, 'login', 'Admin (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-18 21:03:51'),
(80, 17, 'login', 'Admin (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-18 21:04:32'),
(81, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 21:04:47'),
(82, 15, 'role_change', 'Admin Kullanıcı, Student Kullanıcı kullanıcısının rolünü \'admin\' seviyesinden \'student\' seviyesine güncelledi.', '2025-12-18 21:04:55'),
(83, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-18 21:05:06'),
(84, 17, 'event_registration', 'Student Kullanıcı ID: 14 olan etkinliğe kayıt oldu.', '2025-12-18 21:05:12'),
(85, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-18 21:05:32'),
(86, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-19 15:04:14'),
(87, 17, 'event_unregistration', 'Student Kullanıcı ID: 1 olan etkinlik kaydını iptal etti.', '2025-12-19 15:04:21'),
(88, 17, 'event_registration', 'Student Kullanıcı ID: 1 olan etkinliğe kayıt oldu.', '2025-12-19 15:04:23'),
(89, 17, 'event_unregistration', 'Student Kullanıcı ID: 14 olan etkinlik kaydını iptal etti.', '2025-12-19 15:04:35'),
(90, 17, 'event_registration', 'Student Kullanıcı ID: 14 olan etkinliğe kayıt oldu.', '2025-12-19 15:04:38'),
(91, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-19 15:06:01'),
(92, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 14) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-19 15:06:04'),
(93, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 2) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-19 15:06:07'),
(94, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-19 15:14:52'),
(95, 17, 'event_unregistration', 'Student Kullanıcı ID: 14 olan etkinlik kaydını iptal etti.', '2025-12-19 15:44:19'),
(96, 17, 'event_registration', 'Student Kullanıcı ID: 14 olan etkinliğe kayıt oldu.', '2025-12-19 15:44:21'),
(97, 17, 'event_unregistration', 'Student Kullanıcı ID: 3 olan etkinlik kaydını iptal etti.', '2025-12-19 15:48:48'),
(98, 17, 'event_registration', 'Student Kullanıcı ID: 6 olan etkinliğe kayıt oldu.', '2025-12-19 15:49:02'),
(99, 17, 'event_unregistration', 'Kullanıcı (ID: 17) 1 nolu etkinlik kaydını sildi.', '2025-12-19 15:52:16'),
(100, 17, 'event_unregistration', 'Student Kullanıcı ID: 1 olan etkinlik kaydını iptal etti.', '2025-12-19 15:52:16'),
(101, 17, 'event_registration', 'Kullanıcı (ID: 17) 3 nolu etkinliğe kayıt oldu.', '2025-12-19 15:52:22'),
(102, 17, 'event_registration', 'Student Kullanıcı ID: 3 olan etkinliğe kayıt oldu.', '2025-12-19 15:52:22'),
(103, 17, 'feedback_submitted', 'Kullanıcı (ID: 17) 6 nolu etkinlik için geri bildirim yaptı.', '2025-12-19 15:52:49'),
(104, 17, 'give_feedback', 'Student Kullanıcı, 6 ID\'li etkinlik için 3 puan ve yorum bıraktı.', '2025-12-19 15:52:49'),
(105, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-19 15:53:23'),
(106, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-19 15:57:49'),
(107, 17, 'event_registration', 'Kullanıcı (ID: 17) 4 nolu etkinliğe kayıt oldu.', '2025-12-19 15:58:01'),
(108, 17, 'event_registration', 'Student Kullanıcı ID: 4 olan etkinliğe kayıt oldu.', '2025-12-19 15:58:01'),
(109, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-19 16:00:01'),
(110, 17, 'event_registration', 'Kullanıcı (ID: 17) 1 nolu etkinliğe kayıt oldu.', '2025-12-19 16:00:04'),
(111, 17, 'event_registration', 'Student Kullanıcı ID: 1 olan etkinliğe kayıt oldu.', '2025-12-19 16:00:04'),
(112, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-19 17:27:34'),
(113, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-20 01:14:41'),
(114, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-20 01:14:59'),
(115, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 14) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-20 01:15:02'),
(116, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 3) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-20 01:15:05'),
(117, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 3) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-20 01:15:14'),
(118, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 1) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-20 01:15:19'),
(119, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 1) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-20 01:15:36'),
(120, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 1) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-20 01:15:44'),
(121, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 6) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-20 01:15:48'),
(122, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-20 01:16:12'),
(123, 17, 'event_unregistration', 'Kullanıcı (ID: 17) 2 nolu etkinlik kaydını sildi.', '2025-12-20 01:16:20'),
(124, 17, 'event_unregistration', 'Kullanıcı (ID: 17) 6 nolu etkinlik kaydını sildi.', '2025-12-20 01:16:30'),
(125, 17, 'event_registration', 'Kullanıcı (ID: 17) 2 nolu etkinliğe kayıt oldu.', '2025-12-20 01:16:40'),
(126, 17, 'event_registration', 'Student Kullanıcı ID: 2 etkinliğine SP ile kayıt oldu.', '2025-12-20 01:16:40'),
(127, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-20 01:18:03'),
(128, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-20 01:22:32'),
(129, 17, 'event_unregistration', 'Kullanıcı (ID: 17) 5 nolu etkinlik kaydını sildi.', '2025-12-20 01:23:29'),
(130, 17, 'event_registration', 'Kullanıcı (ID: 17) 5 nolu etkinliğe kayıt oldu.', '2025-12-20 01:28:20'),
(131, 17, 'event_registration', 'Student Kullanıcı ID: 5 etkinliğine SP ile kayıt oldu.', '2025-12-20 01:28:20'),
(132, 17, 'event_unregistration', 'Kullanıcı (ID: 17) 3 nolu etkinlik kaydını sildi.', '2025-12-20 01:28:26'),
(133, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-20 01:28:37'),
(134, NULL, 'event_cancelled', 'Etkinlik iptal edildi. event_id = 14', '2025-12-20 01:28:45'),
(135, 25, 'update_event', 'Ahmet Yılmaz (ID: 14) nolu etkinliğin durumunu \'cancelled\' olarak güncelledi.', '2025-12-20 01:28:45'),
(136, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 14) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-20 01:28:48'),
(137, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-20 01:28:59'),
(138, 17, 'event_unregistration', 'Kullanıcı (ID: 17) 14 nolu etkinlik kaydını sildi.', '2025-12-20 01:29:12'),
(139, 17, 'event_registration', 'Kullanıcı (ID: 17) 14 nolu etkinliğe kayıt oldu.', '2025-12-20 01:29:15'),
(140, 17, 'event_registration', 'Student Kullanıcı ID: 14 etkinliğine SP ile kayıt oldu.', '2025-12-20 01:29:15'),
(141, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-20 01:34:32'),
(142, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 14) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-20 01:34:36'),
(143, 25, 'update_event', 'Ahmet Yılmaz (ID: 14) nolu etkinliğin durumunu \'completed\' olarak güncelledi.', '2025-12-20 01:34:43'),
(144, NULL, 'event_cancelled', 'Etkinlik iptal edildi. event_id = 14', '2025-12-20 01:34:48'),
(145, 25, 'update_event', 'Ahmet Yılmaz (ID: 14) nolu etkinliğin durumunu \'cancelled\' olarak güncelledi.', '2025-12-20 01:34:48'),
(146, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-20 01:34:58'),
(147, 17, 'event_registration', 'Kullanıcı (ID: 17) 8 nolu etkinliğe kayıt oldu.', '2025-12-20 01:35:12'),
(148, 17, 'event_registration', 'Student Kullanıcı ID: 8 etkinliğine SP ile kayıt oldu.', '2025-12-20 01:35:12'),
(149, 17, 'event_unregistration', 'Kullanıcı (ID: 17) 8 nolu etkinlik kaydını sildi.', '2025-12-20 01:35:13'),
(150, 17, 'event_unregistration', 'Kullanıcı (ID: 17) 7 nolu etkinlik kaydını sildi.', '2025-12-20 01:35:14'),
(151, 17, 'event_unregistration', 'Kullanıcı (ID: 17) 4 nolu etkinlik kaydını sildi.', '2025-12-20 01:35:15'),
(152, 17, 'event_unregistration', 'Kullanıcı (ID: 17) 9 nolu etkinlik kaydını sildi.', '2025-12-20 01:35:16'),
(153, 17, 'event_registration', 'Kullanıcı (ID: 17) 4 nolu etkinliğe kayıt oldu.', '2025-12-20 01:35:18'),
(154, 17, 'event_registration', 'Student Kullanıcı ID: 4 etkinliğine SP ile kayıt oldu.', '2025-12-20 01:35:18'),
(155, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-20 01:37:07'),
(156, 28, 'login', 'Student (Berkcan Yıldız) sisteme giriş yaptı.', '2025-12-20 01:46:58'),
(157, 3, 'login', 'Organizer (Selim Aydın) sisteme giriş yaptı.', '2025-12-20 01:50:17'),
(158, 3, 'view_feedback', 'Selim Aydın (ID: 8) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-20 01:50:22'),
(159, 6, 'login', 'Student (Elif Kaya) sisteme giriş yaptı.', '2025-12-20 01:51:27'),
(160, 6, 'feedback_submitted', 'Kullanıcı (ID: 6) 1 nolu etkinlik için geri bildirim yaptı.', '2025-12-20 01:51:56'),
(161, 6, 'give_feedback', 'Elif Kaya, 1 ID\'li etkinlik için 4 puan ve yorum bıraktı.', '2025-12-20 01:51:56'),
(162, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-20 01:52:13'),
(163, 3, 'login', 'Organizer (Selim Aydın) sisteme giriş yaptı.', '2025-12-20 01:59:03'),
(164, 3, 'create_event', 'Selim Aydın yeni bir etkinlik oluşturdu ve bölümlere atadı: C++ Öğren ', '2025-12-20 02:03:11'),
(165, 4, 'login', 'Student (Zeynep Koç) sisteme giriş yaptı.', '2025-12-20 02:03:47'),
(166, 4, 'event_registration', 'Kullanıcı (ID: 4) 16 nolu etkinliğe kayıt oldu.', '2025-12-20 02:04:40'),
(167, 4, 'event_registration', 'Zeynep Koç ID: 16 etkinliğine SP ile kayıt oldu.', '2025-12-20 02:04:40'),
(168, 3, 'login', 'Organizer (Selim Aydın) sisteme giriş yaptı.', '2025-12-20 02:05:02'),
(169, 3, 'create_event', 'Selim Aydın yeni bir etkinlik oluşturdu ve bölümlere atadı: İçindeki Çocukla Barış', '2025-12-20 02:07:39'),
(170, 3, 'update_event', 'Selim Aydın şu etkinliği güncelledi: İçindeki Çocukla Barış (ID: 17)', '2025-12-20 02:14:25'),
(171, 3, 'view_feedback', 'Selim Aydın (ID: 17) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-20 02:14:33'),
(172, 3, 'view_list', 'Selim Aydın (ID: 17) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-20 02:14:36'),
(173, 3, 'view_list', 'Selim Aydın (ID: 8) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-20 02:14:41'),
(174, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-20 02:14:54'),
(175, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 01:04:59'),
(176, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-21 15:29:21'),
(177, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 15:37:50'),
(178, 15, 'user_creation', 'Admin Kullanıcı yeni bir kullanıcı oluşturdu: Havin Ezgi Gunes (havin.gunes@istun.edu.tr)', '2025-12-21 16:02:55'),
(179, 15, 'user_update', 'Admin Kullanıcı, Havin Ezgi Gunes adlı kullanıcının bilgilerini güncelledi.', '2025-12-21 16:03:14'),
(180, 15, 'user_update', 'Admin Kullanıcı, Havin Ezgi Gunes adlı kullanıcının bilgilerini güncelledi.', '2025-12-21 16:10:04'),
(181, 15, 'user_creation', 'Admin Kullanıcı yeni bir kullanıcı oluşturdu: Yasemin Gunes (yasemin.gunes@istun.edu.te)', '2025-12-21 16:11:55'),
(182, 15, 'user_update', 'Admin Kullanıcı, Berkcan Yıldız adlı kullanıcının bilgilerini güncelledi.', '2025-12-21 16:12:49'),
(183, NULL, 'login', 'Student (Havin Ezgi Gunes) sisteme giriş yaptı.', '2025-12-21 16:35:21'),
(184, NULL, 'search', 'Öğrenci \'cpp\' kelimesiyle arama yaptı.', '2025-12-21 16:35:34'),
(185, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-21 16:36:10'),
(186, 17, 'search', 'Öğrenci \'kendi\' kelimesiyle arama yaptı.', '2025-12-21 16:36:34'),
(187, 17, 'search', 'Öğrenci \'tekno\' kelimesiyle arama yaptı.', '2025-12-21 16:36:45'),
(188, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-21 16:41:59'),
(189, 25, 'view_list', 'Ahmet Yılmaz (ID: 14) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-21 16:43:11'),
(190, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 4) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-21 16:43:19'),
(191, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 1) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-21 16:43:23'),
(192, 25, 'view_list', 'Ahmet Yılmaz (ID: 1) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-21 16:43:51'),
(193, 25, 'view_list', 'Ahmet Yılmaz (ID: 1) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-21 16:44:00'),
(194, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 16:44:14'),
(195, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-21 16:49:37'),
(196, 17, 'event_registration', 'Kullanıcı (ID: 17) 17 nolu etkinliğe kayıt oldu.', '2025-12-21 16:50:04'),
(197, 17, 'event_registration', 'Student Kullanıcı ID: 17 etkinliğine SP ile kayıt oldu.', '2025-12-21 16:50:04'),
(198, 17, 'event_registration', 'Kullanıcı (ID: 17) 16 nolu etkinliğe kayıt oldu.', '2025-12-21 16:50:26'),
(199, 17, 'event_registration', 'Student Kullanıcı ID: 16 etkinliğine SP ile kayıt oldu.', '2025-12-21 16:50:26'),
(200, 17, 'event_registration', 'Kullanıcı (ID: 17) 9 nolu etkinliğe kayıt oldu.', '2025-12-21 16:50:31'),
(201, 17, 'event_registration', 'Student Kullanıcı ID: 9 etkinliğine SP ile kayıt oldu.', '2025-12-21 16:50:31'),
(202, 17, 'event_registration', 'Kullanıcı (ID: 17) 8 nolu etkinliğe kayıt oldu.', '2025-12-21 16:50:32'),
(203, 17, 'event_registration', 'Student Kullanıcı ID: 8 etkinliğine SP ile kayıt oldu.', '2025-12-21 16:50:32'),
(204, 17, 'event_registration', 'Kullanıcı (ID: 17) 7 nolu etkinliğe kayıt oldu.', '2025-12-21 16:50:33'),
(205, 17, 'event_registration', 'Student Kullanıcı ID: 7 etkinliğine SP ile kayıt oldu.', '2025-12-21 16:50:33'),
(206, NULL, 'login', 'Student (Havin Ezgi Gunes) sisteme giriş yaptı.', '2025-12-21 16:50:46'),
(207, NULL, 'event_registration', 'Kullanıcı (ID: 29) 17 nolu etkinliğe kayıt oldu.', '2025-12-21 16:50:51'),
(208, NULL, 'event_registration', 'Havin Ezgi Gunes ID: 17 etkinliğine SP ile kayıt oldu.', '2025-12-21 16:50:51'),
(209, NULL, 'event_registration', 'Kullanıcı (ID: 29) 16 nolu etkinliğe kayıt oldu.', '2025-12-21 16:50:52'),
(210, NULL, 'event_registration', 'Havin Ezgi Gunes ID: 16 etkinliğine SP ile kayıt oldu.', '2025-12-21 16:50:52'),
(211, NULL, 'event_registration', 'Kullanıcı (ID: 29) 9 nolu etkinliğe kayıt oldu.', '2025-12-21 16:50:52'),
(212, NULL, 'event_registration', 'Havin Ezgi Gunes ID: 9 etkinliğine SP ile kayıt oldu.', '2025-12-21 16:50:52'),
(213, NULL, 'event_registration', 'Kullanıcı (ID: 29) 8 nolu etkinliğe kayıt oldu.', '2025-12-21 16:50:53'),
(214, NULL, 'event_registration', 'Havin Ezgi Gunes ID: 8 etkinliğine SP ile kayıt oldu.', '2025-12-21 16:50:53'),
(215, NULL, 'event_registration', 'Kullanıcı (ID: 29) 7 nolu etkinliğe kayıt oldu.', '2025-12-21 16:50:54'),
(216, NULL, 'event_registration', 'Havin Ezgi Gunes ID: 7 etkinliğine SP ile kayıt oldu.', '2025-12-21 16:50:54'),
(217, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 16:51:02'),
(218, 15, 'user_update', 'Admin Kullanıcı, Havin Ezgi Gunes kullanıcısının bilgilerini (Öğr. No: 230601050) güncelledi.', '2025-12-21 16:56:11'),
(219, 15, 'user_update', 'Admin Kullanıcı, Admin Kullanıcı kullanıcısının bilgilerini (Öğr. No: ) güncelledi.', '2025-12-21 16:57:56'),
(220, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-21 17:03:39'),
(221, 25, 'create_event', 'Ahmet Yılmaz yeni bir etkinlik oluşturdu ve bölümlere atadı: Siber Güvenlik 101', '2025-12-21 17:06:08'),
(222, 25, 'create_event', 'Ahmet Yılmaz yeni bir etkinlik oluşturdu ve bölümlere atadı: AI ve Gelecek', '2025-12-21 17:08:14'),
(223, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-21 17:10:13'),
(224, 17, 'event_unregistration', 'Kullanıcı (ID: 17) 7 nolu etkinlik kaydını sildi.', '2025-12-21 17:10:28'),
(225, 17, 'event_registration', 'Kullanıcı (ID: 17) 7 nolu etkinliğe kayıt oldu.', '2025-12-21 17:10:29'),
(226, 17, 'event_registration', 'Student Kullanıcı ID: 7 etkinliğine SP ile kayıt oldu.', '2025-12-21 17:10:29'),
(227, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 17:10:45'),
(228, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-21 17:13:10'),
(229, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 17:13:30'),
(230, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-21 17:14:07'),
(231, 25, 'view_list', 'Ahmet Yılmaz (ID: 19) nolu etkinliğin katılımcı listesini inceledi.', '2025-12-21 17:14:14'),
(232, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 19) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-21 17:14:17'),
(233, 25, 'view_feedback', 'Ahmet Yılmaz (ID: 1) nolu etkinliğin geri bildirimlerini inceledi.', '2025-12-21 17:14:20'),
(234, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 17:21:19'),
(235, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-21 17:21:58'),
(236, 17, 'search', 'Öğrenci \'ai\' kelimesiyle arama yaptı.', '2025-12-21 17:22:05'),
(237, 17, 'search', 'Öğrenci \'üniversite\' kelimesiyle arama yaptı.', '2025-12-21 17:22:33'),
(238, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-21 17:32:06'),
(239, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 17:32:16'),
(240, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-21 17:33:21'),
(241, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-21 17:35:04'),
(242, 17, 'search', 'Öğrenci \'üniversite\' kelimesiyle arama yaptı.', '2025-12-21 17:35:40'),
(243, 17, 'search', 'Öğrenci \'kampüs\' kelimesiyle arama yaptı.', '2025-12-21 17:36:05'),
(244, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 17:37:38'),
(245, 15, 'user_creation', 'Admin Kullanıcı yeni bir kullanıcı oluşturdu: bensu ince', '2025-12-21 17:46:21'),
(246, 15, 'user_update', 'Admin Kullanıcı, bensu ince kullanıcısının bilgilerini (Öğr. No: ) güncelledi.', '2025-12-21 17:47:10'),
(247, 15, 'user_update', 'Admin Kullanıcı, bensu ince kullanıcısının bilgilerini (Öğr. No: 230601040) güncelledi.', '2025-12-21 17:47:54'),
(248, 15, 'user_deletion', 'Admin Kullanıcı bir kullanıcıyı sildi: bensu ince', '2025-12-21 18:05:54'),
(249, NULL, 'login', 'Student (Havin Ezgi Gunes) sisteme giriş yaptı.', '2025-12-21 18:10:55'),
(250, NULL, 'search', 'Öğrenci \'kampüs\' kelimesiyle arama yaptı.', '2025-12-21 18:11:18'),
(251, NULL, 'search', 'Öğrenci \'mavi amfi\' kelimesiyle arama yaptı.', '2025-12-21 18:11:33'),
(252, NULL, 'search', 'Öğrenci \'2026\' kelimesiyle arama yaptı.', '2025-12-21 18:11:41'),
(253, NULL, 'search', 'Öğrenci \'2026-03\' kelimesiyle arama yaptı.', '2025-12-21 18:11:48'),
(254, NULL, 'search', 'Öğrenci \'2026-02\' kelimesiyle arama yaptı.', '2025-12-21 18:11:54'),
(255, NULL, 'search', 'Öğrenci \'2026-01\' kelimesiyle arama yaptı.', '2025-12-21 18:11:59'),
(256, NULL, 'search', 'Öğrenci \'2026-30\' kelimesiyle arama yaptı.', '2025-12-21 18:12:11'),
(257, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 18:13:24'),
(258, 15, 'user_creation', 'Admin Kullanıcı yeni bir kullanıcı oluşturdu: bensu ince', '2025-12-21 18:14:45'),
(259, 15, 'user_update', 'Admin Kullanıcı, bensu ince kullanıcısının bilgilerini güncelledi.', '2025-12-21 18:15:18'),
(260, 15, 'user_update', 'Admin Kullanıcı, bensu ince kullanıcısının bilgilerini güncelledi.', '2025-12-21 18:15:44'),
(261, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 18:26:17'),
(262, 15, 'user_update', 'Admin Kullanıcı, Yasemin Gunes kullanıcısının bilgilerini güncelledi.', '2025-12-21 18:28:22'),
(263, 15, 'user_update', 'Admin Kullanıcı, Yasemin Gunes kullanıcısının bilgilerini güncelledi.', '2025-12-21 18:28:58'),
(264, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-21 18:29:46'),
(265, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-21 18:29:59'),
(266, 17, 'search', 'Öğrenci \'üniversite\' kelimesiyle arama yaptı.', '2025-12-21 18:30:34'),
(267, 17, 'search', 'Öğrenci \'mavi amfi\' kelimesiyle arama yaptı.', '2025-12-21 18:31:10'),
(268, 17, 'search', 'Öğrenci \'2026\' kelimesiyle arama yaptı.', '2025-12-21 18:31:24'),
(269, 17, 'search', 'Öğrenci \'2026-03\' kelimesiyle arama yaptı.', '2025-12-21 18:31:34'),
(270, 17, 'search', 'Öğrenci \'2026-02\' kelimesiyle arama yaptı.', '2025-12-21 18:31:38'),
(271, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-21 18:33:30'),
(272, 15, 'login', 'Admin (Admin Kullanıcı) sisteme giriş yaptı.', '2025-12-21 18:34:29'),
(273, 17, 'login', 'Student (Student Kullanıcı) sisteme giriş yaptı.', '2025-12-21 18:50:17'),
(274, 4, 'login', 'Student (Zeynep Koç) sisteme giriş yaptı.', '2025-12-21 18:50:34'),
(275, 25, 'login', 'Organizer (Ahmet Yılmaz) sisteme giriş yaptı.', '2025-12-21 18:54:06');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`user_id`, `role_id`, `department_id`, `full_name`, `email`, `password_hash`, `is_active`, `created_at`) VALUES
(1, 1, 1, 'Mehmet Yılmaz', 'mehmet.yilmaz@kampus.edu', '$2y$10$wRUpiJt2F72mLT2ufKEmDemLuFmyRc14f1xz2LoS2rDiKK2XZTGke', 1, '2025-12-11 14:00:40'),
(2, 2, 1, 'Ayşe Demir', 'ayse.demir@kampus.edu', '$2y$10$wRUpiJt2F72mLT2ufKEmDemLuFmyRc14f1xz2LoS2rDiKK2XZTGke', 1, '2025-12-11 14:00:40'),
(3, 2, 5, 'Selim Aydın', 'selim.aydin@kampus.edu', '$2y$10$wRUpiJt2F72mLT2ufKEmDemLuFmyRc14f1xz2LoS2rDiKK2XZTGke', 1, '2025-12-11 14:00:40'),
(4, 3, 1, 'Zeynep Koç', 'zeynep.koc@kampus.edu', '$2y$10$wRUpiJt2F72mLT2ufKEmDemLuFmyRc14f1xz2LoS2rDiKK2XZTGke', 1, '2025-12-11 14:00:40'),
(5, 3, 2, 'Emre Can', 'emre.can@kampus.edu', '$2y$10$wRUpiJt2F72mLT2ufKEmDemLuFmyRc14f1xz2LoS2rDiKK2XZTGke', 1, '2025-12-11 14:00:40'),
(6, 3, 6, 'Elif Kaya', 'elif.kaya@kampus.edu', '$2y$10$wRUpiJt2F72mLT2ufKEmDemLuFmyRc14f1xz2LoS2rDiKK2XZTGke', 1, '2025-12-11 14:00:40'),
(7, 4, 9, 'Dr. Cem Aksoy', 'cem.aksoy@kampus.edu', '$2y$10$wRUpiJt2F72mLT2ufKEmDemLuFmyRc14f1xz2LoS2rDiKK2XZTGke', 1, '2025-12-11 14:00:40'),
(8, 4, 1, 'Dr. Fatma Polat', 'fatma.polat@kampus.edu', '$2y$10$wRUpiJt2F72mLT2ufKEmDemLuFmyRc14f1xz2LoS2rDiKK2XZTGke', 1, '2025-12-11 14:00:40'),
(9, 6, 1, 'Selda Ertuğ', 'selda.ertug@kampus.edu', '$2y$10$wRUpiJt2F72mLT2ufKEmDemLuFmyRc14f1xz2LoS2rDiKK2XZTGke', 1, '2025-12-11 14:00:40'),
(10, 7, 1, 'Burak Arslan', 'burak.arslan@kampus.edu', '$2y$10$wRUpiJt2F72mLT2ufKEmDemLuFmyRc14f1xz2LoS2rDiKK2XZTGke', 1, '2025-12-11 14:00:40'),
(15, 1, 2, 'Admin Kullanıcı', 'admin@uni.edu', '$2y$10$x/iVRfvzYaeH3mNjtsOyleeQ1SuVC5301pT9AQLmh1Yu7eMWUv1n6', 1, '2025-12-10 17:03:46'),
(17, 3, NULL, 'Student Kullanıcı', 'student@uni.edu', '$2y$10$dHeytFBcXoNxidEnsy8oD.0PeGYg.TS9A11CCpkQqPbu9ip5Tep/y', 1, '2025-12-10 17:03:46'),
(25, 2, NULL, 'Ahmet Yılmaz', 'ahmet.organizer@example.com', '$2y$10$BwCWiDWe28m6pKiLvL84Ee82mEs0QBiAlkkOrQBfrmlKl3F9lVoLy', 1, '2025-12-15 00:40:53'),
(28, 3, 10, 'Berkcan Yıldız', 'berkcan@uni.edu', '$2y$10$wRUpiJt2F72mLT2ufKEmDemLuFmyRc14f1xz2LoS2rDiKK2XZTGke', 1, '2025-12-20 01:43:41'),
(30, 2, 1, 'Yasemin Gunes', 'yasemin.gunes@istun.edu.te', '$2y$10$dSfClk3f0qc.eWaSla7MduM9E01rfsTQUV.1QpjPw5C2FvyXi9HFO', 1, '2025-12-21 16:11:55');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int(11) NOT NULL,
  `student_number` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `bio` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `user_profiles`
--

INSERT INTO `user_profiles` (`user_id`, `student_number`, `phone`, `birth_date`, `bio`) VALUES
(1, NULL, '+90 531 111 1101', '1980-04-10', 'Sistem yöneticisi'),
(2, NULL, '+90 531 111 1102', '1984-02-18', 'Etkinlik yönetim sorumlusu'),
(3, NULL, '+90 531 111 1103', '1986-10-05', 'İİBF kariyer sorumlusu'),
(4, '20230001', '+90 552 222 2201', '2004-06-20', '1. sınıf bilgisayar müh. öğrencisi'),
(5, '20230002', '+90 552 222 2202', '2004-11-10', '2. sınıf yazılım müh. öğrencisi'),
(6, '20230003', '+90 552 222 2203', '2003-12-15', 'Fen-Edebiyat öğrencisi'),
(7, NULL, '+90 533 333 3301', '1975-01-02', 'Tıp Fakültesi öğretim üyesi'),
(8, NULL, '+90 533 333 3302', '1978-05-22', 'Bilgisayar Müh. öğretim üyesi'),
(9, '20200010', '+90 553 444 4401', '2001-08-12', 'Bilişim Kulübü Başkanı'),
(10, '20210033', '+90 553 444 4402', '2003-09-25', 'Bilişim Kulübü Üyesi'),
(15, '', '', NULL, NULL),
(28, NULL, '', NULL, NULL),
(30, '230601070', '+90 5333333434', NULL, NULL);

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Tablo için indeksler `department_events`
--
ALTER TABLE `department_events`
  ADD PRIMARY KEY (`department_event_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Tablo için indeksler `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Tablo için indeksler `event_categories`
--
ALTER TABLE `event_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Tablo için indeksler `event_feedbacks`
--
ALTER TABLE `event_feedbacks`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `event_organizers`
--
ALTER TABLE `event_organizers`
  ADD PRIMARY KEY (`event_organizer_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `event_participations`
--
ALTER TABLE `event_participations`
  ADD PRIMARY KEY (`participation_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Tablo için indeksler `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Tablo için indeksler `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Tablo için indeksler `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Tablo için AUTO_INCREMENT değeri `department_events`
--
ALTER TABLE `department_events`
  MODIFY `department_event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Tablo için AUTO_INCREMENT değeri `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Tablo için AUTO_INCREMENT değeri `event_categories`
--
ALTER TABLE `event_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `event_feedbacks`
--
ALTER TABLE `event_feedbacks`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Tablo için AUTO_INCREMENT değeri `event_organizers`
--
ALTER TABLE `event_organizers`
  MODIFY `event_organizer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Tablo için AUTO_INCREMENT değeri `event_participations`
--
ALTER TABLE `event_participations`
  MODIFY `participation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- Tablo için AUTO_INCREMENT değeri `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- Tablo için AUTO_INCREMENT değeri `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=276;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `department_events`
--
ALTER TABLE `department_events`
  ADD CONSTRAINT `department_events_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_events_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`category_id`),
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`);

--
-- Tablo kısıtlamaları `event_feedbacks`
--
ALTER TABLE `event_feedbacks`
  ADD CONSTRAINT `event_feedbacks_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_feedbacks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `event_organizers`
--
ALTER TABLE `event_organizers`
  ADD CONSTRAINT `event_organizers_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_organizers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `event_participations`
--
ALTER TABLE `event_participations`
  ADD CONSTRAINT `event_participations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_participations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
