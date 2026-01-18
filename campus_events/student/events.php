<?php
session_start();
require "../includes/auth.php";
require "../includes/role_check.php";
require "../config/db.php";

requireRole('student');

try {
    $stmt = $pdo->query("CALL GetUpcomingEventsWithCategory()");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    die("Etkinlik listeleme hatası: " . $e->getMessage());
}

$page_title = "Etkinlikler | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h1 class="card__title">📅 Etkinlikler</h1>
    <p class="card__subtitle">Yaklaşan etkinlikleri görüntüleyip kayıt olabilirsin.</p>
  </div>
</div>

<div class="card card--soft">
  <div class="card__body">

    <?php if (empty($events)): ?>
      <div class="alert alert-error">Şu an listelenecek etkinlik bulunamadı.</div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Etkinlik</th>
              <th>Kategori</th>
              <th>Konum</th>
              <th>Başlangıç</th>
              <th>Kontenjan</th>
              <th>İşlem</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $e): ?>
              <tr>
                <td><b><?= htmlspecialchars($e['event_title'] ?? '-') ?></b></td>
                <td><span class="badge"><?= htmlspecialchars($e['category_name'] ?? 'Genel') ?></span></td>
                <td><?= htmlspecialchars($e['location_name'] ?? 'Belirtilmedi') ?></td>
                <td>
                  <?= isset($e['start_datetime']) ? date("d.m.Y H:i", strtotime($e['start_datetime'])) : '-' ?>
                </td>
                <td><?= (int)($e['available_seats'] ?? 0) ?></td>
                <td>
                  <a
                    href="register_event.php?event_id=<?= (int)$e['event_id'] ?>"
                    class="btn"
                    style="border:1px solid rgba(22,163,74,.35); background: rgba(22,163,74,.10); font-weight:900;"
                  >
                    ✓ Kayıt Ol
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
