<?php
session_start();
require "../config/db.php";
require "../includes/role_check.php";

// Sadece öğrenci
checkRole([3]);

$userId = $_SESSION['user_id'];
$eventId = $_POST['event_id'] ?? $_GET['event_id'] ?? null;

if (!$eventId) {
    die("Hata: Geri bildirim yapılacak etkinlik bulunamadı.");
}

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment'] ?? '');

    try {
        $pdo->beginTransaction();

        // Geri bildirimi kaydet
        $stmt = $pdo->prepare("
            INSERT INTO event_feedbacks (event_id, user_id, rating, comment)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$eventId, $userId, $rating, $comment]);

        // Log
        $logDesc = ($_SESSION['user_name'] ?? 'Öğrenci') . 
                   ", $eventId ID'li etkinlik için $rating puan ve yorum bıraktı.";
        $logStmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, action_type, description)
            VALUES (?, 'give_feedback', ?)
        ");
        $logStmt->execute([$userId, $logDesc]);

        $pdo->commit();
        $message = "Geri bildiriminiz başarıyla iletildi. Teşekkür ederiz!";
        $messageType = "success";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $message = "Bir hata oluştu. Lütfen tekrar deneyin.";
        $messageType = "error";
    }
}

$page_title = "Geri Bildirim Ver | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<!-- SAYFA BAŞLIĞI -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h1 class="card__title">📝 Etkinlik Değerlendirme</h1>
    <p class="card__subtitle">
      Katıldığınız etkinlik hakkındaki görüşlerinizi bizimle paylaşın.
    </p>
  </div>
</div>

<!-- FORM -->
<div class="card card--soft" style="max-width:520px;">
  <div class="card__body">

    <?php if ($message): ?>
      <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php else: ?>
      <form method="POST">
        <input type="hidden" name="event_id" value="<?= htmlspecialchars($eventId) ?>">

        <label class="label" for="rating">Puanınız</label>
        <select name="rating" id="rating" required>
          <option value="5">5 – Mükemmel</option>
          <option value="4">4 – Çok İyi</option>
          <option value="3">3 – Orta</option>
          <option value="2">2 – Kötü</option>
          <option value="1">1 – Çok Kötü</option>
        </select>

        <label class="label" for="comment">Yorumunuz</label>
        <textarea
          class="input"
          name="comment"
          id="comment"
          rows="5"
          placeholder="Görüşlerinizi yazın..."
          required
        ></textarea>

        <div class="actions-center">
          <button type="submit" class="btn btn-primary btn-wide">
            Gönder
          </button>
        </div>
      </form>
    <?php endif; ?>

  </div>
</div>

<?php include "../partials/footer.php"; ?>
