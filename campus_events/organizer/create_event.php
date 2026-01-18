<?php
/**
 * organizer/create_event.php
 * Organizer - Yeni Etkinlik Oluştur
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once "../includes/auth.php";
require_once "../includes/role_check.php";
require_once "../config/db.php";

checkRole([2]);

$message = "";

/* Dropdown verileri */
$categories = $pdo->query("SELECT * FROM event_categories")->fetchAll(PDO::FETCH_ASSOC);
$locations  = $pdo->query("SELECT * FROM locations")->fetchAll(PDO::FETCH_ASSOC);
$allDepts   = $pdo->query("SELECT * FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

/* Form submit */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['event_title'] ?? '');
    $description = trim($_POST['event_description'] ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $locationId  = (int)($_POST['location_id'] ?? 0);
    $startDt     = $_POST['start_datetime'] ?? '';
    $endDt       = $_POST['end_datetime'] ?? '';
    $capacity    = (int)($_POST['capacity'] ?? 0);
    $organizerId = $_SESSION['user_id'];
    $status      = 'planned';
    $targetDepts = $_POST['target_departments'] ?? [];

    if (!$title || !$categoryId || !$locationId || !$startDt || !$endDt || $capacity <= 0) {
        $message = "Lütfen tüm alanları eksiksiz doldurun.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("CALL AddNewEvent(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $categoryId, $locationId, $title, $description,
                $startDt, $endDt, $capacity, $status
            ]);

            $newEventId = $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
            if (!$newEventId) throw new Exception("Etkinlik ID alınamadı.");

            $pdo->prepare("
                INSERT INTO event_organizers (event_id, user_id, role_in_event)
                VALUES (?, ?, 'Ana Organizatör')
            ")->execute([$newEventId, $organizerId]);

            if (!empty($targetDepts)) {
                $stmtDept = $pdo->prepare("
                    INSERT INTO department_events (department_id, event_id, is_main_department)
                    VALUES (?, ?, 1)
                ");
                foreach ($targetDepts as $deptId) {
                    $stmtDept->execute([$deptId, $newEventId]);
                }
            }

            $pdo->commit();

            $logDesc = $_SESSION['user_name']." yeni bir etkinlik oluşturdu: ".$title;
            $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description)
                           VALUES (?, 'create_event', ?)")
                ->execute([$organizerId, $logDesc]);

            header("Location: dashboard.php?msg=created");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = $e->getMessage();
        }
    }
}

/* PARTIALS */
$page_title = "Yeni Etkinlik Oluştur | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<!-- BAŞLIK KARTI (yükseklik sabit değil, boşluk yok) -->
<div class="card card--soft" style="margin-bottom:16px;">
  <div class="card__body">
    <h1 class="card__title">➕ Yeni Etkinlik Oluştur</h1>
    <p class="card__subtitle">Etkinlik bilgilerini doldur ve etkinliği yayınla.</p>
  </div>
</div>

<?php if ($message): ?>
<div class="card card--soft" style="margin-bottom:16px;">
  <div class="card__body">
    <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
  </div>
</div>
<?php endif; ?>

<!-- FORM KARTI -->
<div class="card card--soft">
  <div class="card__body">

    <form method="post">
      <label class="label">Başlık</label>
      <input class="input" type="text" name="event_title" required>

      <label class="label">Açıklama</label>
      <textarea class="input" name="event_description" rows="4" required></textarea>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div>
          <label class="label">Kategori</label>
          <select class="input" name="category_id" required>
            <option value="">Seçiniz</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="label">Konum</label>
          <select class="input" name="location_id" required>
            <option value="">Seçiniz</option>
            <?php foreach ($locations as $l): ?>
              <option value="<?= $l['location_id'] ?>"><?= htmlspecialchars($l['location_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <label class="label">Hedef Bölümler (çoklu seçim)</label>
      <select class="input" name="target_departments[]" multiple required style="height:120px;">
        <?php foreach ($allDepts as $dept): ?>
          <option value="<?= $dept['department_id'] ?>">
            <?= htmlspecialchars($dept['department_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div>
          <label class="label">Başlangıç</label>
          <input class="input" type="datetime-local" name="start_datetime" required>
        </div>
        <div>
          <label class="label">Bitiş</label>
          <input class="input" type="datetime-local" name="end_datetime" required>
        </div>
      </div>

      <label class="label">Kontenjan</label>
      <input class="input" type="number" name="capacity" min="1" required>

      <div class="actions-center" style="margin-top:16px;">
        <button class="btn btn-primary btn-wide" type="submit">
          🚀 Etkinliği Oluştur
        </button>
      </div>
    </form>

  </div>
</div>

<?php include "../partials/footer.php"; ?>
