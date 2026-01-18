<?php
/**
 * organizer/my_events.php
 * Organizer - Benim Etkinliklerim
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once "../includes/auth.php";
require_once "../includes/role_check.php";
require_once "../config/db.php";

checkRole([2]);

/* =========================
   Etkinlikleri çek
   ========================= */
$stmt = $pdo->prepare("
  SELECT event_name, event_date, quota
  FROM events
  WHERE organizer_id = ?
  ORDER BY event_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   LOG
   ========================= */
$logDesc = ($_SESSION['user_name'] ?? 'Organizer') . " kendi etkinlik listesini görüntüledi.";
$pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'view_list', ?)")
    ->execute([$_SESSION['user_id'], $logDesc]);

/* =========================
   PARTIALS
   ========================= */
$page_title = "Benim Etkinliklerim | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body" style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h1 class="card__title">📌 Benim Etkinliklerim</h1>
      <p class="card__subtitle">Sana ait etkinlikler listelenmektedir.</p>
    </div>
    <div>
      <a class="btn btn-primary" href="create_event.php" style="padding:10px 14px;">➕ Yeni Etkinlik</a>
    </div>
  </div>
</div>

<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <?php if (empty($events)): ?>
      <div class="alert alert-error" style="margin-top:14px;">
        Henüz etkinliğin bulunmuyor.
      </div>
    <?php else: ?>
      <div class="table-wrap" style="margin-top:14px;">
        <table>
          <thead>
            <tr>
              <th>Etkinlik</th>
              <th style="width:180px;">Tarih</th>
              <th style="width:140px;">Kontenjan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $e): ?>
              <tr>
                <td><b><?= htmlspecialchars($e['event_name'] ?? '-') ?></b></td>
                <td><?= htmlspecialchars($e['event_date'] ?? '-') ?></td>
                <td><span class="badge"><?= (int)($e['quota'] ?? 0) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include "../partials/footer.php"; ?>
