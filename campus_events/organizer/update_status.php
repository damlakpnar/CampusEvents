<?php
session_start();
require_once "../config/db.php";
require_once "../includes/role_check.php";

checkRole([2]); // Sadece Organizatör

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    $newStatus = isset($_POST['status']) ? $_POST['status'] : '';

    if ($eventId > 0 && !empty($newStatus)) {
        try {
            // SP ÇAĞRISI: UpdateEventStatus(p_event_id, p_new_status)
            $stmt = $pdo->prepare("CALL UpdateEventStatus(?, ?)");
            $stmt->execute([$eventId, $newStatus]);

            // LOGLAMA
            $logDesc = $_SESSION['user_name'] . " (ID: $eventId) nolu etkinliğin durumunu '$newStatus' olarak güncelledi.";
            $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'update_event', ?)")
                ->execute([$_SESSION['user_id'], $logDesc]);

            header("Location: dashboard.php?msg=status_updated");
            exit;
        } catch (PDOException $e) {
            die("Güncelleme hatası: " . $e->getMessage());
        }
    } else {
        die("Geçersiz veri girişi.");
    }
} else {
    header("Location: dashboard.php");
    exit;
}