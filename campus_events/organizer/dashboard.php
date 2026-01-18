<?php
/**
 * organizer/dashboard.php
 * ORGANIZER - Ana Panel
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require "../config/db.php";
require "../includes/role_check.php";
require "../includes/auth.php";

// Sadece Organizer (Rol ID = 2)
checkRole([2]);

$userId = (int)($_SESSION['user_id'] ?? 0);
$message = "";

/* =========================
   Bilgilendirme mesajları
   ========================= */
if (isset($_GET['msg'])) {
  if ($_GET['msg'] === 'created')            $message = "✅ Etkinliğiniz başarıyla oluşturuldu.";
  elseif ($_GET['msg'] === 'updated')        $message = "✅ Etkinlik bilgileri güncellendi.";
  elseif ($_GET['msg'] === 'deleted')        $message = "✅ Etkinlik başarıyla silindi.";
  elseif ($_GET['msg'] === 'status_updated') $message = "✅ Etkinlik durumu güncellendi.";
}

/* =========================
   Organizer'ın etkinlikleri
   ========================= */
$stmt = $pdo->prepare("
  SELECT
    e.event_id,
    e.event_title,
    ec.category_name,
    e.start_datetime,
    e.end_datetime,
    e.status
  FROM events e
  INNER JOIN event_categories ec ON e.category_id = ec.category_id
  INNER JOIN event_organizers eo ON e.event_id = eo.event_id
  WHERE eo.user_id = ?
  ORDER BY e.start_datetime DESC
");
$stmt->execute([$userId]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Frontend Partials
   ========================= */
$page_title = "Organizer Paneli | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<!-- ÜST KARŞILAMA -->
<div class="card card--soft" style="margin-bottom:16px;">
  <div class="card__body" style="display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; align-items:flex-start;">
    <div>
      <h1 class="card__title">📋 Organizer Paneli</h1>
      <p class="card__subtitle">
        Hoş geldin <b><?= htmlspecialchars($_SESSION['user_name'] ?? 'Organizer') ?></b>.
        Yönettiğin etkinlikleri buradan kontrol edebilirsin.
      </p>

      <?php if ($message): ?>
        <div class="alert" style="margin-top:12px; border:1px solid rgba(16,185,129,.25);">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ETKİNLİKLER -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h2 class="card__title" style="font-size:22px;">📅 Yönettiğim Etkinlikler</h2>
    <p class="card__subtitle">Sana atanmış etkinliklerin listesi.</p>

    <?php if (empty($events)): ?>
      <div class="alert alert-error" style="margin-top:14px;">
        Henüz organizatörü olduğun bir etkinlik bulunmuyor.
      </div>
    <?php else: ?>
      <div class="table-wrap" style="margin-top:14px;">
        <table>
          <thead>
            <tr>
              <th style="width:70px;">ID</th>
              <th>Etkinlik</th>
              <th style="width:150px;">Kategori</th>
              <th style="width:170px;">Başlangıç</th>
              <th style="width:170px;">Bitiş</th>
              <th style="width:140px;">Katılımcılar</th>
              <th style="width:340px;">Yönetim</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $event): ?>
              <tr>
                <td>#<?= (int)$event['event_id'] ?></td>
                <td><b><?= htmlspecialchars($event['event_title'] ?? '-') ?></b></td>
                <td><span class="badge"><?= htmlspecialchars($event['category_name'] ?? 'Genel') ?></span></td>
                <td><?= !empty($event['start_datetime']) ? date("d.m.Y H:i", strtotime($event['start_datetime'])) : '-' ?></td>
                <td><?= !empty($event['end_datetime']) ? date("d.m.Y H:i", strtotime($event['end_datetime'])) : '-' ?></td>

                <td>
                  <a href="view_participants.php?event_id=<?= (int)$event['event_id'] ?>"
                     style="font-weight:900; color:var(--success);">
                    👥 Görüntüle
                  </a>
                </td>

                <td style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                  <a class="btn"
                     href="edit_event.php?event_id=<?= (int)$event['event_id'] ?>"
                     style="padding:8px 10px; border:1px solid rgba(124,58,237,.25); background:#fff; font-weight:900;">
                    ✏️ Düzenle
                  </a>

                  <form action="update_status.php" method="POST" style="margin:0;">
                    <input type="hidden" name="event_id" value="<?= (int)$event['event_id'] ?>">
                    <select name="status" class="input" onchange="this.form.submit()" style="min-width:130px;">
                      <option value="planned"   <?= ($event['status'] ?? '') === 'planned' ? 'selected' : '' ?>>Planlandı</option>
                      <option value="active"    <?= ($event['status'] ?? '') === 'active' ? 'selected' : '' ?>>Aktif</option>
                      <option value="completed" <?= ($event['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Tamamlandı</option>
                      <option value="cancelled" <?= ($event['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>İptal</option>
                    </select>
                  </form>

                  <a href="delete_event.php?event_id=<?= (int)$event['event_id'] ?>"
                     onclick="return confirm('Bu etkinliği silmek istiyor musunuz?')"
                     style="font-weight:900; color:var(--danger);">
                    🗑️
                  </a>

                  <a href="view_feedbacks.php?event_id=<?= (int)$event['event_id'] ?>"
                     style="font-weight:900; color:var(--primary);">
                    💬 Yorumlar
                  </a>
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
