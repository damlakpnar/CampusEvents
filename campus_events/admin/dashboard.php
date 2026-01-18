<?php
/**
 * admin/dashboard.php
 * Admin Panel (Sadece yeni kullanıcı ekleme)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once "../includes/auth.php";
require_once "../includes/role_check.php";
require_once "../config/db.php";

checkRole([1]);

// Bölümler dropdown için
$departments = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Yeni kullanıcı ekleme
   ========================= */
if (isset($_POST['add_user'])) {
  $fullName  = trim($_POST['full_name'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $rawPass   = (string)($_POST['password'] ?? '');
  $roleId    = (int)($_POST['role_id'] ?? 3);
  $deptId    = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
  $phone     = trim($_POST['phone'] ?? '');
  $birthDate = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;

  $hashedPass = password_hash($rawPass, PASSWORD_DEFAULT);

  try {
    $pdo->beginTransaction();

    // users insert
    $insertUser = $pdo->prepare("
      INSERT INTO users (full_name, email, password_hash, role_id, department_id)
      VALUES (?, ?, ?, ?, ?)
    ");
    $insertUser->execute([$fullName, $email, $hashedPass, $roleId, $deptId]);
    $newUserId = (int)$pdo->lastInsertId();

    // user_profiles insert
    $insertProfile = $pdo->prepare("
      INSERT INTO user_profiles (user_id, phone, birth_date)
      VALUES (?, ?, ?)
    ");
    $insertProfile->execute([$newUserId, $phone, $birthDate]);

    // log
    $logDesc = ($_SESSION['user_name'] ?? 'Admin') . " yeni bir kullanıcı oluşturdu: $fullName";
    $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'user_creation', ?)")
        ->execute([$_SESSION['user_id'], $logDesc]);

    $pdo->commit();
    header("Location: dashboard.php?msg=added");
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    die("Ekleme Hatası: " . $e->getMessage());
  }
}

/* =========================
   Frontend partials
   ========================= */
$page_title = "Admin Panel | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<!-- ÜST BAŞLIK -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h1 class="card__title">🛡️ Admin Panel</h1>
    <p class="card__subtitle">
      Hoş geldin <b><?= htmlspecialchars($_SESSION['user_name'] ?? 'Yönetici') ?></b>. Buradan yeni kullanıcı ekleyebilirsin.
    </p>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
      <div class="alert" style="margin-top:14px; border:1px solid rgba(16,185,129,.25);">
        ✅ Kullanıcı başarıyla eklendi.
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- YENİ KULLANICI EKLE -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h2 class="card__title" style="font-size:22px;">➕ Yeni Kullanıcı Tanımla</h2>
    <p class="card__subtitle">Yeni kullanıcıyı sisteme ekle (şifre otomatik hashlenir).</p>

    <form method="POST">
      <label class="label">Ad Soyad</label>
      <input class="input" type="text" name="full_name" placeholder="Ad Soyad" required>

      <label class="label">E-posta</label>
      <input class="input" type="email" name="email" placeholder="E-posta" required>

      <label class="label">Şifre</label>
      <input class="input" type="password" name="password" placeholder="Şifre" required>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
        <div>
          <label class="label">Telefon</label>
          <input class="input" type="text" name="phone" placeholder="Telefon No">
        </div>
        <div>
          <label class="label">Doğum Tarihi</label>
          <input class="input" type="date" name="birth_date">
        </div>
      </div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
        <div>
          <label class="label">Rol</label>
          <select class="input" name="role_id" required>
            <option value="3">Öğrenci (Student)</option>
            <option value="2">Organizatör (Organizer)</option>
            <option value="1">Yönetici (Admin)</option>
          </select>
        </div>
        <div>
          <label class="label">Bölüm</label>
          <select class="input" name="department_id">
            <option value="">Bölüm Seçiniz (Opsiyonel)</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= (int)$d['department_id'] ?>">
                <?= htmlspecialchars($d['department_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="actions-center" style="gap:10px; margin-top:14px;">
        <button type="submit" name="add_user" class="btn btn-primary btn-wide">Sisteme Kaydet</button>
      </div>
    </form>
  </div>
</div>

<?php include "../partials/footer.php"; ?>
