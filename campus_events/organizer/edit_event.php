<?php
/**
 * organizer/edit_event.php
 * Organizer - Etkinlik Düzenle
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require "../config/db.php";
require "../includes/role_check.php";
require "../includes/auth.php";

checkRole([2]);

if (!isset($_GET['event_id'])) die("Etkinlik ID belirtilmedi!");
$eventId = (int)$_GET['event_id'];
$userId  = (int)($_SESSION['user_id'] ?? 0);

/* Etkinlik bilgileri */
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) die("Etkinlik bulunamadı.");

/* Güvenlik: Bu etkinliğin organizatörü mü? */
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM event_organizers WHERE event_id = ? AND user_id = ?");
$stmtCheck->execute([$eventId, $userId]);
if ((int)$stmtCheck->fetchColumn() === 0) {
  die("Bu etkinliği düzenleme yetkiniz yok.");
}

/* Dropdown için veriler */
$categories = $pdo->query("SELECT * FROM event_categories ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
$locations  = $pdo->query("SELECT * FROM locations ORDER BY location_name")->fetchAll(PDO::FETCH_ASSOC);

$message = "";

/* Form gönderildiyse güncelle */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title       = trim($_POST['event_title'] ?? '');
  $category    = (int)($_POST['category_id'] ?? 0);
  $location    = (int)($_POST['location_id'] ?? 0);
  $start       = $_POST['start_datetime'] ?? '';
  $end         = $_POST['end_datetime'] ?? '';
  $description = trim($_POST['event_description'] ?? '');
  $capacity    = (int)($_POST['capacity'] ?? 0);

  if (!$title || !$category || !$location || !$start || !$end || $capacity <= 0) {
    $message = "Lütfen tüm gerekli alanları doldurun.";
  } else {
    $stmtUp = $pdo->prepare("
      UPDATE events
      SET event_title = ?, category_id = ?, location_id = ?, event_description = ?, start_datetime = ?, end_datetime = ?, capacity = ?
      WHERE event_id = ?
    ");
    $stmtUp->execute([$title, $category, $location, $description, $start, $end, $capacity, $eventId]);

    // Log
    $logDesc = ($_SESSION['user_name'] ?? 'Organizer') . " şu etkinliği güncelledi: " . $title . " (ID: $eventId)";
    $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'update_event', ?)");
    $logStmt->execute([$userId, $logDesc]);

    header("Location: dashboard.php?msg=updated");
    exit;
  }
}

/* Partials */
$page_title = "Etkinlik Düzenle | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body" style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <h1 class="card__title">✏️ Etkinlik Düzenle</h1>
      <p class="card__subtitle">
        <b><?= htmlspecialchars($event['event_title'] ?? '-') ?></b> (ID: #<?= (int)$eventId ?>)
      </p>
    </div>
    <div>
      <a class="btn" href="dashboard.php"
         style="padding:10px 14px; border:1px solid rgba(124,58,237,.25); background:#fff; font-weight:900;">
        ← Panele Dön
      </a>
    </div>
  </div>
</div>

<?php if (!empty($message)): ?>
  <div class="card card--soft" style="margin-bottom:18px;">
    <div class="card__body">
      <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
    </div>
  </div>
<?php endif; ?>

<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <form method="post">
      <label class="label">Başlık</label>
      <input class="input" type="text" name="event_title" value="<?= htmlspecialchars($event['event_title'] ?? '') ?>" required>

      <label class="label">Açıklama</label>
      <textarea class="input" name="event_description" rows="5" required><?= htmlspecialchars($event['event_description'] ?? '') ?></textarea>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
        <div>
          <label class="label">Kategori</label>
          <select class="input" name="category_id" required>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['category_id'] ?>" <?= ((int)$c['category_id'] === (int)$event['category_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['category_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="label">Konum</label>
          <select class="input" name="location_id" required>
            <?php foreach ($locations as $l): ?>
              <option value="<?= (int)$l['location_id'] ?>" <?= ((int)$l['location_id'] === (int)$event['location_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($l['location_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
        <div>
          <label class="label">Başlangıç</label>
          <input class="input" type="datetime-local" name="start_datetime"
                 value="<?= !empty($event['start_datetime']) ? date('Y-m-d\TH:i', strtotime($event['start_datetime'])) : '' ?>" required>
        </div>
        <div>
          <label class="label">Bitiş</label>
          <input class="input" type="datetime-local" name="end_datetime"
                 value="<?= !empty($event['end_datetime']) ? date('Y-m-d\TH:i', strtotime($event['end_datetime'])) : '' ?>" required>
        </div>
      </div>

      <label class="label">Kontenjan (Kapasite)</label>
      <input class="input" type="number" name="capacity" min="1" value="<?= (int)($event['capacity'] ?? 10) ?>" required>

      <div class="actions-center" style="gap:10px; margin-top:14px;">
        <button class="btn btn-primary btn-wide" type="submit">✅ Güncelle</button>
        <a class="btn" href="dashboard.php"
           style="border:1px solid rgba(124,58,237,.25); background:#fff; font-weight:900;">
          İptal
        </a>
      </div>
    </form>
  </div>
</div>

<?php include "../partials/footer.php"; ?>
