<?php
session_start();
require "../config/db.php";
require "../includes/role_check.php";

checkRole([3]); // Student
$userId = $_SESSION['user_id'];
$eventId = $_GET['event_id'] ?? null;

if (!$eventId) {
    die("Geçersiz etkinlik ID'si.");
}

// Kontrol: öğrenci zaten kayıtlı mı?
$stmt = $pdo->prepare("SELECT COUNT(*) FROM event_participations WHERE user_id = ? AND event_id = ?");
$stmt->execute([$userId, $eventId]);
if ($stmt->fetchColumn() > 0) {
    die("Zaten bu etkinliğe kayıtlısınız.");
}

try {
    // SP zaten mükerrer kayıt kontrolü ve bildirim ekleme işlemlerini yapıyor
    $stmt = $pdo->prepare("CALL RegisterToEvent(?, ?)");
    $stmt->execute([$eventId, $userId]);

    // Kayıt hareketini logla
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'event_registration', ?)");
    $logStmt->execute([$userId, $_SESSION['user_name'] . " ID: $eventId etkinliğine SP ile kayıt oldu."]);

    header("Location: dashboard.php?status=success");
    exit;
} catch (PDOException $e) {
    die("Kayıt hatası: " . $e->getMessage());
}
?>