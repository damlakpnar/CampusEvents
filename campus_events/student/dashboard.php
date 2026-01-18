<?php
/**
 * student/dashboard.php
 * ÖĞRENCİ ANA YÖNETİM PANELİ
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once "../config/db.php";
require_once "../includes/role_check.php";
require_once "../includes/auth.php";

checkRole([3]);

$userId = $_SESSION['user_id'];

/* =========================
   ETKİNLİK ARAMA / LİSTELEME
   ========================= */
$searchKeyword = $_GET['q'] ?? '';

try {
    if (!empty($searchKeyword)) {
        $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'search', ?)");
        $logStmt->execute([$userId, "Öğrenci '$searchKeyword' kelimesiyle arama yaptı."]);

        $stmtSearch = $pdo->prepare("CALL SearchEvents(?)");
        $stmtSearch->execute([$searchKeyword]);
        $upcomingEvents = $stmtSearch->fetchAll(PDO::FETCH_ASSOC);
        $stmtSearch->closeCursor();
    } else {
        $stmt = $pdo->query("CALL GetUpcomingEventsWithCategory()");
        $upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
    }
} catch (PDOException $e) {
    die("Arama/Listeleme hatası: " . $e->getMessage());
}

/* =========================
   KAYITLI ETKİNLİKLER
   ========================= */
try {
    $stmtMy = $pdo->prepare("CALL GetUserEventList(?)");
    $stmtMy->execute([$userId]);
    $myEvents = $stmtMy->fetchAll(PDO::FETCH_ASSOC);
    $stmtMy->closeCursor();
} catch (PDOException $e) {
    die("SP hatası (GetUserEventList): " . $e->getMessage());
}

$myEventIds = array_column($myEvents, 'event_id');

/* =========================
   FRONTEND PARTIALS
   ========================= */
$page_title = "Öğrenci Paneli | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<!-- ÜST KARŞILAMA -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h1 class="card__title">🎓 Öğrenci Paneli</h1>
    <p class="card__subtitle">
      Hoş geldin,<b><?= htmlspecialchars($_SESSION['user_name'] ?? 'Student Kullanıcı') ?></b> 👋
  
    </p>

</div>

<!-- ETKİNLİK ARAMA -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h2 class="card__title" style="font-size:24px;">🔎 Etkinlik Ara</h2>

    <form method="GET" action="">
      <label class="label" for="q">Başlık / içerik</label>
      <input
        class="input"
        id="q"
        name="q"
        type="text"
        placeholder="Başlık veya içerik yazın..."
        value="<?= htmlspecialchars($searchKeyword) ?>"
      />

      <div class="actions-center" style="gap:10px;">
        <button class="btn btn-primary btn-wide" type="submit">Sonuçları Filtrele</button>

        <?php if (!empty($searchKeyword)): ?>
          <a href="dashboard.php" class="btn" style="border:1px solid rgba(124,58,237,.25); background:#fff; font-weight:900;">
            ❌ Temizle
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- YAKLAŞAN ETKİNLİKLER -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h2 class="card__title" style="font-size:24px;">📅 Etkinlikler</h2>
    <p class="card__subtitle">Katılabileceğin güncel etkinlikler listelenmektedir.</p>

    <?php if (empty($upcomingEvents)): ?>
      <div class="alert alert-error" style="margin-top:14px;">
        Kriterlerinize uygun bir etkinlik bulunamadı.
      </div>
    <?php else: ?>
      <div class="table-wrap" style="margin-top:14px;">
        <table>
          <thead>
            <tr>
              <th>Etkinlik Başlığı</th>
              <th>Kategori</th>
              <th>Konum</th>
              <th>Başlangıç</th>
              <th>Kontenjan</th>
              <th>İşlem</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($upcomingEvents as $event):
              $eventId = (int)($event['event_id'] ?? 0);
              $isRegistered = in_array($eventId, $myEventIds, true);
            ?>
              <tr>
                <td><b><?= htmlspecialchars($event['event_title'] ?? 'İsimsiz Etkinlik') ?></b></td>
                <td><span class="badge"><?= htmlspecialchars($event['category_name'] ?? 'Genel') ?></span></td>
                <td><?= htmlspecialchars($event['location_name'] ?? 'Belirtilmedi') ?></td>
                <td><?= isset($event['start_datetime']) ? date("d.m.Y H:i", strtotime($event['start_datetime'])) : '-' ?></td>
                <td><?= (int)($event['available_seats'] ?? 0) ?> Kişi</td>
                <td>
                  <?php if ($isRegistered): ?>
                    <a
                      href="unregister_event.php?event_id=<?= $eventId ?>"
                      style="font-weight:900; color: var(--danger);"
                    >
                      ✕ Kaydı İptal Et
                    </a>
                  <?php else: ?>
                    <a
                      href="register_event.php?event_id=<?= $eventId ?>"
                      style="font-weight:900; color: var(--success);"
                    >
                      ✓ Şimdi Kayıt Ol
                    </a>
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
