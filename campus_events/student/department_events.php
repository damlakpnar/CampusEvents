<?php
session_start();
require_once "../config/db.php";
require_once "../includes/role_check.php";

checkRole([3]); // Öğrenci

$userId = $_SESSION['user_id'];

// Kullanıcının bölüm ID'sini al
$stmtUser = $pdo->prepare("SELECT department_id FROM users WHERE user_id = ?");
$stmtUser->execute([$userId]);
$deptId = $stmtUser->fetchColumn();

try {
    // SP: GetDepartmentEvents
    $stmt = $pdo->prepare("CALL GetDepartmentEvents(?)");
    $stmt->execute([$deptId]);
    $deptEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    die("Bölüm etkinlikleri yüklenirken hata oluştu.");
}

$page_title = "Bölüm Etkinlikleri | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<!-- SAYFA BAŞLIĞI -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h1 class="card__title">🎓 Bölümümün Etkinlikleri</h1>
    <p class="card__subtitle">
      Kendi bölümünüze özel veya ana bölüm tarafından düzenlenen etkinlikler.
    </p>
  </div>
</div>

<!-- LİSTE -->
<div class="card card--soft">
  <div class="card__body">

    <?php if (empty($deptEvents)): ?>
      <div class="alert alert-success">
        Bölümünüze ait özel bir etkinlik bulunmamaktadır.
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Etkinlik</th>
              <th>Kategori</th>
              <th>Konum</th>
              <th>Tarih</th>
              <th>Ana Bölüm</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($deptEvents as $e): ?>
              <tr>
                <td><b><?= htmlspecialchars($e['event_title']) ?></b></td>
                <td><span class="badge"><?= htmlspecialchars($e['category_name']) ?></span></td>
                <td><?= htmlspecialchars($e['location_name']) ?></td>
                <td><?= date("d.m.Y H:i", strtotime($e['start_datetime'])) ?></td>
                <td>
                  <?= $e['is_main_department']
                        ? '<span class="badge">Evet</span>'
                        : '<span style="color:var(--muted);">Hayır</span>' ?>
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
