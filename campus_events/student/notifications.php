<?php
session_start();
require_once "../config/db.php";
require_once "../includes/role_check.php";

checkRole([3]); // Öğrenci
$userId = $_SESSION['user_id'];

/* Tekil bildirim okundu */
if (isset($_GET['read_id'])) {
    $notifId = (int)$_GET['read_id'];
    $stmt = $pdo->prepare("CALL MarkNotificationAsRead(?)");
    $stmt->execute([$notifId]);
    header("Location: notifications.php");
    exit;
}

try {
    // Bildirimleri getir
    $stmt = $pdo->prepare("CALL GetUserNotifications(?)");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    // Okunmamışları okundu yap (opsiyonel)
    $updateStmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = ? AND is_read = 0
    ");
    $updateStmt->execute([$userId]);

} catch (PDOException $e) {
    die("Bildirimler yüklenirken bir hata oluştu.");
}

$page_title = "Bildirimlerim | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<!-- SAYFA BAŞLIĞI -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h1 class="card__title">🔔 Bildirimlerim</h1>
    <p class="card__subtitle">
      Sistem tarafından gönderilen bilgilendirme mesajları.
    </p>
  </div>
</div>

<!-- BİLDİRİM LİSTESİ -->
<div class="card card--soft">
  <div class="card__body">

    <?php if (empty($notifications)): ?>
      <div class="alert alert-success">
        Henüz bir bildiriminiz bulunmuyor.
      </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($notifications as $n): ?>
          <div
            style="
              padding:14px 16px;
              border-radius:14px;
              border:1px solid rgba(124,58,237,.18);
              background: <?= $n['is_read'] ? '#fff' : 'rgba(167,139,250,.14)' ?>;
            "
          >
            <div style="font-weight:900; color:var(--primary);">
              📢 Sistem Mesajı
            </div>

            <div style="margin:6px 0;">
              <?= htmlspecialchars($n['message']) ?>
            </div>

            <div style="font-size:13px; color:var(--muted);">
              <?= date("d.m.Y H:i", strtotime($n['created_at'])) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include "../partials/footer.php"; ?>
