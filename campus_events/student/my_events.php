<?php
session_start();
require_once "../config/db.php";
require_once "../includes/auth.php";
require_once "../includes/role_check.php";

checkRole([3]); // Öğrenci kontrolü

$userId = $_SESSION['user_id'];
$currentTime = time();

// Kayıtlı etkinlikleri bitiş tarihine göre çekiyoruz
$stmt = $pdo->prepare("
    SELECT e.event_id, e.event_title, e.end_datetime, l.location_name
    FROM events e
    INNER JOIN event_participations ep ON e.event_id = ep.event_id
    INNER JOIN locations l ON e.location_id = l.location_id
    WHERE ep.user_id = ?
    ORDER BY e.end_datetime DESC
");
$stmt->execute([$userId]);
$myEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// LOGLAMA
$logDesc = ($_SESSION['user_name'] ?? 'Öğrenci') . " katıldığı etkinlikleri listeledi.";
$pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'view_list', ?)")
    ->execute([$userId, $logDesc]);

$page_title = "Kayıtlı Etkinliklerim | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<!-- SAYFA BAŞLIK KARTI -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h1 class="card__title">📌 Kayıtlı Etkinliklerim</h1>
    <p class="card__subtitle">
      Katıldığın etkinlikleri burada görebilir, etkinlik bittiyse geri bildirim verebilirsin.
    </p>
  </div>
</div>

<!-- LİSTE -->
<div class="card card--soft">
  <div class="card__body">

    <?php if (empty($myEvents)): ?>
      <div class="alert alert-success">
        Henüz kayıtlı olduğun bir etkinlik bulunmuyor.
      </div>
      <div style="margin-top:14px;">
      </div>

    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Etkinlik</th>
              <th>Bitiş Tarihi</th>
              <th>Konum</th>
              <th>İşlem</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($myEvents as $e): ?>
              <?php $eventEndTime = strtotime($e['end_datetime']); ?>
              <tr>
                <td><b><?= htmlspecialchars($e['event_title']) ?></b></td>
                <td><?= date("d.m.Y H:i", strtotime($e['end_datetime'])) ?></td>
                <td><?= htmlspecialchars($e['location_name']) ?></td>
                <td>
                  <?php if ($eventEndTime < $currentTime): ?>
                    <a
                      href="feedback.php?event_id=<?= (int)$e['event_id'] ?>"
                      class="btn"
                      style="border:1px solid rgba(124,58,237,.35); background: rgba(167,139,250,.12); font-weight:900;"
                    >
                      📝 Geri Bildirim Ver
                    </a>
                  <?php else: ?>
                    <span style="color: var(--muted); font-style: italic;">🕒 Henüz Bitmedi</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:16px;">
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include "../partials/footer.php"; ?>
