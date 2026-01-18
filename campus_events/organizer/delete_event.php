<?php
session_start();
require "../config/db.php";
require "../includes/role_check.php";

checkRole([2]); // Organizer

if (!isset($_GET['event_id'])) die("Etkinlik ID belirtilmedi!");
$eventId = (int)$_GET['event_id'];
$userId = $_SESSION['user_id'];

// GÜVENLİK KONTROLÜ: Kullanıcının bu etkinliğin organizatörü olup olmadığını kontrol et
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM event_organizers WHERE event_id = ? AND user_id = ?");
$stmtCheck->execute([$eventId, $userId]);
if ($stmtCheck->fetchColumn() == 0) {
    die("Bu etkinliği silme yetkiniz yok.");
}

try {
    $pdo->beginTransaction();
// 1. ✅ ÖNCE İSİM ÇEK (Logda "Şu isimli etkinlik silindi" demek için)
$stmtName = $pdo->prepare("SELECT event_title FROM events WHERE event_id = ?");
$stmtName->execute([$eventId]);
$eventTitle = $stmtName->fetchColumn();

// 2. MEVCUT SİLME İŞLEMLERİN (Olduğu gibi kalıyor)
$pdo->prepare("DELETE FROM event_participations WHERE event_id = ?")->execute([$eventId]);
$pdo->prepare("DELETE FROM event_organizers WHERE event_id = ?")->execute([$eventId]);
$pdo->prepare("DELETE FROM notifications WHERE event_id = ?")->execute([$eventId]);
$pdo->prepare("DELETE FROM events WHERE event_id = ?")->execute([$eventId]);

// 3. ✅ LOG KAYDI EKLE
$logDesc = $_SESSION['user_name'] . " şu etkinliği ve tüm verilerini sildi: " . $eventTitle;
$logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'delete_event', ?)");
$logStmt->execute([$userId, $logDesc]);

    $pdo->commit();

    header("Location: dashboard.php?msg=deleted");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Etkinlik silinirken bir hata oluştu: " . $e->getMessage());
}