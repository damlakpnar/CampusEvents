<?php
/**
 * organizer/view_feedbacks.php
 * Organizer - Etkinlik Geri Bildirimleri
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once "../config/db.php";
require_once "../includes/role_check.php";
require_once "../includes/auth.php";

checkRole([2]);

$eventId = $_GET['event_id'] ?? null;
if (!$eventId) die("Hata: Etkinlik seçilmedi.");
$eventId = (int)$eventId;

/* Log */
$logDesc = ($_SESSION['user_name'] ?? 'Organizer') . " (ID: $eventId) nolu etkinliğin geri bildirimlerini inceledi.";
$pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'view_feedback', ?)")
    ->execute([$_SESSION['user_id'], $logDesc]);

/* SP: Ortalama puan */
$stats = null;
try {
  $stmtAvg = $pdo->prepare("CALL GetEventAverageRating(?)");
  $stmtAvg->execute([$eventId]);
  $stats = $stmtAvg->fetch(PDO::FETCH_ASSOC);
  $stmtAvg->closeCursor();
} catch (PDOException $e) {
  $stats = null;
}

/* Yorumları listele */
$stmt = $pdo->prepare("
  SELECT ef.rating, ef.comment, ef.created_at, u.full_name
  FROM event_feedbacks ef
  JOIN users u ON ef.user_id = u.user_id
  WHERE ef.event_id = ?
  ORDER BY ef.created_at DESC
");
$stmt->execute([$eventId]);
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Başlık: etkinlik adı */
$stmtTitle = $pdo->prepare("SELECT event_title FROM events WHERE event_id = ?");
$stmtTitle->execute([$eventId]);
$eventTitle = $stmtTitle->fetchColumn();

/* Partials */
$page_title = "Geri Bildirimler | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body" style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h1 class="card__title">💬 Geri Bildirimler</h1>
      <p class="card__subtitle">
        <b><?= htmlspecialchars($eventTitle ?: 'Etkinlik') ?></b> (ID: #<?= (int)$eventId ?>)
      </p>
    </div>
  </div>
</div>

<?php if ($stats && (int)($stats['total_reviewers'] ?? 0) > 0): ?>
  <div class="card card--soft" style="margin-bottom:18px;">
    <div class="card__body">
      <h2 class="card__title" style="font-size:20px;">⭐ Özet</h2>
      <p class="card__subtitle" style="margin:0;">
        <b><?= (int)$stats['total_reviewers'] ?></b> değerlendirme •
        Ortalama: <b><?= number_format((float)$stats['average_rating'], 1) ?> / 5.0</b>
      </p>
    </div>
  </div>
<?php endif; ?>

<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h2 class="card__title" style="font-size:22px;">🗂️ Yorum Listesi</h2>
    <p class="card__subtitle">En yeni yorumlar en üstte görünür.</p>

    <?php if (empty($feedbacks)): ?>
      <div class="alert alert-error" style="margin-top:14px;">
        Henüz geri bildirim yapılmamış.
      </div>
    <?php else: ?>
      <div class="table-wrap" style="margin-top:14px;">
        <table>
          <thead>
            <tr>
              <th style="width:220px;">Kullanıcı</th>
              <th style="width:120px;">Puan</th>
              <th>Yorum</th>
              <th style="width:190px;">Tarih</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($feedbacks as $f): ?>
              <tr>
                <td><b><?= htmlspecialchars($f['full_name'] ?? '-') ?></b></td>
                <td><span class="badge"><?= (int)($f['rating'] ?? 0) ?>/5</span></td>
                <td><?= nl2br(htmlspecialchars($f['comment'] ?? '-')) ?></td>
                <td><?= !empty($f['created_at']) ? date("d.m.Y H:i", strtotime($f['created_at'])) : '-' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include "../partials/footer.php"; ?>
