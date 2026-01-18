<?php
/**
 * admin/events.php
 * Admin - Etkinlikleri Yönet & Organizer (Event Manager) Ata
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once "../config/db.php";
require_once "../includes/role_check.php";
require_once "../includes/auth.php";

checkRole([1]);

/* =========================
   Organizer atama
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $eventId = (int)($_POST['event_id'] ?? 0);
  $organizerId = (int)($_POST['organizer_id'] ?? 0);

  if ($eventId > 0 && $organizerId > 0) {
    $stmt = $pdo->prepare("
      INSERT INTO event_organizers (event_id, user_id, role_in_event)
      VALUES (?, ?, 'Event Manager')
      ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)
    ");
    $stmt->execute([$eventId, $organizerId]);

    header("Location: events.php");
    exit;
  }
}

/* =========================
   Etkinlikleri çek
   ========================= */
$stmt = $pdo->query("
  SELECT e.event_id, e.event_title, ec.category_name
  FROM events e
  LEFT JOIN event_categories ec ON e.category_id = ec.category_id
  ORDER BY e.event_id DESC
");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Organizerları çek
   ========================= */
$organizers = $pdo->query("
  SELECT user_id, full_name
  FROM users
  WHERE role_id = 2
  ORDER BY full_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   FRONTEND PARTIALS
   ========================= */
$page_title = "Etkinlik Yönetimi | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<!-- ÜST BAŞLIK -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h1 class="card__title">📅 Etkinlik Yönetimi</h1>
    <p class="card__subtitle">Etkinlikleri görüntüleyebilir ve her etkinlik için Event Manager atayabilirsin.</p>
  </div>
</div>

<!-- ETKİNLİK TABLOSU -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h2 class="card__title" style="font-size:22px;">📌 Etkinlik Listesi</h2>

    <?php if (empty($events)): ?>
      <div class="alert alert-error" style="margin-top:14px;">
        Henüz etkinlik bulunmuyor.
      </div>
    <?php else: ?>
      <div class="table-wrap" style="margin-top:14px;">
        <table>
          <thead>
            <tr>
              <th style="width:60px;">#</th>
              <th>Etkinlik</th>
              <th style="width:170px;">Kategori</th>
              <th style="width:360px;">Event Manager Ata</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $e): ?>
              <tr>
                <td><?= (int)$e['event_id'] ?></td>
                <td><b><?= htmlspecialchars($e['event_title'] ?? '-') ?></b></td>
                <td><span class="badge"><?= htmlspecialchars($e['category_name'] ?? 'Genel') ?></span></td>
                <td>
                  <?php if (empty($organizers)): ?>
                    <span style="font-weight:800; color:var(--danger);">Organizer bulunamadı.</span>
                  <?php else: ?>
                    <form method="POST" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                      <input type="hidden" name="event_id" value="<?= (int)$e['event_id'] ?>">

                      <!-- select'e input class verdik ki stil uyumu olsun -->
                      <select name="organizer_id" class="input" style="min-width:220px; max-width:260px;">
                        <?php foreach ($organizers as $o): ?>
                          <option value="<?= (int)$o['user_id'] ?>">
                            <?= htmlspecialchars($o['full_name'] ?? ('User#'.$o['user_id'])) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>

                      <button class="btn btn-primary" type="submit" style="padding:10px 14px;">
                        ✅ Ata
                      </button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include "../partials/footer.php"; ?>
