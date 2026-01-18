<?php
session_start();
require "../config/db.php";
require "../includes/role_check.php";

checkRole([3]); // Student

if (!isset($_GET['event_id'])) {
    die("Etkinlik ID belirtilmedi!");
}

$eventId = (int)$_GET['event_id'];
$userId = $_SESSION['user_id'];

try {
    // SP: Kaydı siler ve bildirim ekler
    $stmt = $pdo->prepare("CALL CancelEventRegistration(?, ?)");
    $stmt->execute([$eventId, $userId]);

    header("Location: dashboard.php?msg=unregistered");
    exit;
} catch (PDOException $e) {
    die("İptal hatası: " . $e->getMessage());
}
?>