<?php
/**
 * organizer/view_participants.php
 * Organizer - Katılımcıları Gör
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require "../config/db.php";
require "../includes/role_check.php";
require "../includes/auth.php";

checkRole([2]);

if (!isset($_GET['event_id'])) {
  die("Etkinlik ID belirtilmedi!");
}

$eventId = (int)$_GET['event_id'];

/* Katılımcıları çek (SP) */
$stmt = $pdo->prepare("CALL GetEventParticipants(?)");
$stmt->execute([$eventId]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

/* Log */
try {
  $logDesc = ($_SESSION['user_name'] ?? 'Organizer') . " (ID: $eventId) nolu etkinliğin katılımcı listesini inceledi.";
  $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'view_list', ?)");
  $logStmt->execute([$_SESSION['user_id'], $logDesc]);
} catch (PDOException $e) {
  // Log hata verse bile sayfa bozulmasın
}

/* PARTIALS */
$page_title = "Katılımcılar | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body" style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h1 class="card__title">👥 Katılımcılar</h1>
      <p class="card__subtitle">Etkinlik ID: <b>#<?= (int)$eventId ?></b></p>
    </div>
  
  </div>
</div>

<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <?php if (empty($participants)): ?>
      <div class="alert alert-error" style="margin-top:14px;">
        Henüz kayıtlı katılımcı yok.
      </div>
    <?php else: ?>
      <div class="table-wrap" style="margin-top:14px;">
        <table>
          <thead>
            <tr>
              <th>Ad Soyad</th>
              <th style="width:220px;">Bölüm</th>
              <th style="width:280px;">Email</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($participants as $p): ?>
              <tr>
                <td><b><?= htmlspecialchars($p['full_name'] ?? '-') ?></b></td>
                <td><?= htmlspecialchars($p['department_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($p['email'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include "../partials/footer.php"; ?>
