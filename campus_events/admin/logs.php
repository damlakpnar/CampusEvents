<?php
/**
 * admin/logs.php
 * ADMIN - Sistem Hareket Logları
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once "../config/db.php";
require_once "../includes/role_check.php";
require_once "../includes/auth.php";

checkRole([1]);

/* =========================
   LOG LİSTESİ
   ========================= */
try {
  $stmt = $pdo->query("
    SELECT l.log_id, l.action_type, l.description, l.created_at, u.full_name
    FROM system_logs l
    LEFT JOIN users u ON l.user_id = u.user_id
    ORDER BY l.created_at DESC
  ");
  $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $logs = [];
}

/* =========================
   FRONTEND PARTIALS
   ========================= */
$page_title = "Sistem Logları | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";

/* action_type => label (göze daha hoş) */
function action_label(string $type): string {
  $map = [
    'login'              => 'Giriş',
    'event_registration' => 'Etkinlik Kaydı',
    'delete_event'       => 'Etkinlik Silme',
    'give_feedback'      => 'Geri Bildirim',
    'update_event'       => 'Etkinlik Güncelleme',
    'role_change'        => 'Rol Değişimi',
    'view_feedback'      => 'Yorum İnceleme',
    'search'             => 'Arama',
  ];
  return $map[$type] ?? $type;
}
?>

<!-- ÜST BAŞLIK -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h1 class="card__title">🧾 Sistem Hareket Logları</h1>
    <p class="card__subtitle">Sistemdeki kullanıcı hareketleri kronolojik olarak listelenir. (En yeni en üstte)</p>
  </div>
</div>

<!-- LOG TABLOSU -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">

    <?php if (empty($logs)): ?>
      <div class="alert alert-error" style="margin-top:14px;">
        Gösterilecek log bulunamadı.
      </div>
    <?php else: ?>
      <div class="table-wrap" style="margin-top:14px;">
        <table>
          <thead>
            <tr>
              <th style="width:80px;">ID</th>
              <th style="width:220px;">Kullanıcı</th>
              <th style="width:180px;">İşlem Tipi</th>
              <th>Açıklama</th>
              <th style="width:190px;">Tarih</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $l): ?>
              <?php
                $type = (string)($l['action_type'] ?? '');
                // Eski "renkli tag" yerine theme badge kullanıyoruz
              ?>
              <tr>
                <td>#<?= (int)($l['log_id'] ?? 0) ?></td>
                <td><b><?= htmlspecialchars($l['full_name'] ?? 'Sistem/Misafir') ?></b></td>
                <td>
                  <span class="badge"><?= htmlspecialchars(action_label($type)) ?></span>
                </td>
                <td><?= htmlspecialchars($l['description'] ?? '-') ?></td>
                <td>
                  <?= !empty($l['created_at']) ? date("d.m.Y H:i:s", strtotime($l['created_at'])) : '-' ?>
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
