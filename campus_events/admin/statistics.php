<?php
/**
 * admin/statistics.php
 * ADMIN - Sistem İstatistikleri
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once "../config/db.php";
require_once "../includes/role_check.php";
require_once "../includes/auth.php";

// Sadece Admin erişebilir
checkRole([1]);

/* =========================
   VERİ ÇEKME (SP)
   ========================= */
try {
  $stmt = $pdo->query("CALL GetTop5MostAttendedEvents()");
  $topEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();
} catch (PDOException $e) {
  $topEvents = [];
}

/* =========================
   FRONTEND PARTIALS
   ========================= */
$page_title = "İstatistikler | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<!-- SAYFA BAŞLIĞI -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h1 class="card__title">📊 Sistem İstatistikleri</h1>
    <p class="card__subtitle">
      Etkinlik performanslarını ve katılım verilerini buradan takip edebilirsin.
    </p>
  </div>
</div>

<!-- TOP 5 ETKİNLİK -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h2 class="card__title" style="font-size:22px;">🏆 En Popüler 5 Etkinlik</h2>
    <p class="card__subtitle">En yüksek katılımcıya sahip etkinlikler listelenir.</p>

    <?php if (empty($topEvents)): ?>
      <div class="alert alert-error" style="margin-top:14px;">
        Şu an gösterilecek istatistik verisi bulunmuyor.
      </div>
    <?php else: ?>
      <div class="table-wrap" style="margin-top:14px;">
        <table>
          <thead>
            <tr>
              <th style="width:80px;">Sıra</th>
              <th>Etkinlik Başlığı</th>
              <th style="width:180px;">Kategori</th>
              <th style="width:180px;">Toplam Katılımcı</th>
            </tr>
          </thead>
          <tbody>
            <?php $rank = 1; foreach ($topEvents as $event): ?>
              <tr>
                <td><b>#<?= $rank++ ?></b></td>
                <td><?= htmlspecialchars($event['event_title'] ?? 'İsimsiz') ?></td>
                <td>
                  <span class="badge">
                    <?= htmlspecialchars($event['category_name'] ?? 'Genel') ?>
                  </span>
                </td>
                <td>
                  <span class="badge">
                    <?= (int)($event['total_participants'] ?? 0) ?> Kişi
                  </span>
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
