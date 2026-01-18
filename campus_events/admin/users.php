<?php
/**
 * admin/users.php
 * ADMIN - Kullanıcı Yönetimi (CRUD)
 * - Kullanıcı ekleme
 * - Kullanıcı güncelleme
 * - Kullanıcı silme
 * - Listeleme (roles, departments, user_profiles join)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once "../includes/auth.php";
require_once "../includes/role_check.php";
require_once "../config/db.php";

// Sadece Admin erişebilir
checkRole([1]);

/* =========================
   0) Sabit Veriler (Formlarda kullanılacak)
   ========================= */
$departments = $pdo->query("SELECT * FROM departments ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   1) YENİ KULLANICI EKLEME
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

    $insertUser = $pdo->prepare("
      INSERT INTO users (full_name, email, password_hash, role_id, department_id)
      VALUES (?, ?, ?, ?, ?)
    ");
    $insertUser->execute([$fullName, $email, $hashedPass, $roleId, $deptId]);
    $newUserId = (int)$pdo->lastInsertId();

    $insertProfile = $pdo->prepare("
      INSERT INTO user_profiles (user_id, phone, birth_date)
      VALUES (?, ?, ?)
    ");
    $insertProfile->execute([$newUserId, $phone, $birthDate]);

    $logDesc = ($_SESSION['user_name'] ?? 'Admin') . " yeni bir kullanıcı oluşturdu: $fullName";
    $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'user_creation', ?)")
        ->execute([$_SESSION['user_id'], $logDesc]);

    $pdo->commit();
    header("Location: users.php?msg=added");
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    die("Ekleme Hatası: " . $e->getMessage());
  }
}

/* =========================
   2) KULLANICI GÜNCELLEME
   ========================= */
if (isset($_POST['update_user'])) {
  $userId        = (int)($_POST['user_id'] ?? 0);
  $fullName      = trim($_POST['full_name'] ?? '');
  $email         = trim($_POST['email'] ?? '');
  $roleId        = (int)($_POST['role_id'] ?? 3);
  $deptId        = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;

  $studentNumber = trim($_POST['student_number'] ?? '');
  $phone         = trim($_POST['phone'] ?? '');
  $birthDate     = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;

  try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
      UPDATE users
      SET full_name = ?, email = ?, role_id = ?, department_id = ?
      WHERE user_id = ?
    ");
    $stmt->execute([$fullName, $email, $roleId, $deptId, $userId]);

    $check = $pdo->prepare("SELECT user_id FROM user_profiles WHERE user_id = ?");
    $check->execute([$userId]);

    if ($check->fetch()) {
      $pdo->prepare("
        UPDATE user_profiles
        SET student_number = ?, phone = ?, birth_date = ?
        WHERE user_id = ?
      ")->execute([$studentNumber, $phone, $birthDate, $userId]);
    } else {
      $pdo->prepare("
        INSERT INTO user_profiles (user_id, student_number, phone, birth_date)
        VALUES (?, ?, ?, ?)
      ")->execute([$userId, $studentNumber, $phone, $birthDate]);
    }

    $logDesc = ($_SESSION['user_name'] ?? 'Admin') . ", $fullName kullanıcısının bilgilerini güncelledi.";
    $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'user_update', ?)")
        ->execute([$_SESSION['user_id'], $logDesc]);

    $pdo->commit();
    header("Location: users.php?msg=updated");
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    die("Güncelleme Hatası: " . $e->getMessage());
  }
}

/* =========================
   3) KULLANICI SİLME
   ========================= */
if (isset($_GET['delete_id'])) {
  $deleteId = (int)$_GET['delete_id'];

  if ($deleteId !== (int)($_SESSION['user_id'] ?? 0)) {
    try {
      $pdo->beginTransaction();

      $stmtName = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
      $stmtName->execute([$deleteId]);
      $uName = $stmtName->fetchColumn();

      if ($uName) {
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$deleteId]);

        $logDesc = ($_SESSION['user_name'] ?? 'Admin') . " bir kullanıcıyı sildi: $uName";
        $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'user_deletion', ?)")
            ->execute([$_SESSION['user_id'], $logDesc]);

        $pdo->commit();
        header("Location: users.php?msg=deleted");
        exit;
      }

      $pdo->rollBack();
    } catch (Exception $e) {
      $pdo->rollBack();
      die("Silme Hatası: " . $e->getMessage());
    }
  }
}

/* =========================
   4) KULLANICILARI LİSTELE
   ========================= */
$sql = "
  SELECT
    u.user_id, u.full_name, u.email, u.role_id,
    r.role_name,
    d.department_name, u.department_id,
    up.phone, up.student_number, up.birth_date
  FROM users u
  JOIN roles r ON u.role_id = r.role_id
  LEFT JOIN departments d ON u.department_id = d.department_id
  LEFT JOIN user_profiles up ON u.user_id = up.user_id
  ORDER BY u.user_id DESC
";
$users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   5) FRONTEND PARTIALS
   ========================= */
$page_title = "Kullanıcı Yönetimi | Campus Events";
include "../partials/header.php";
include "../partials/menu.php";
?>

<style>
/* İşlem butonlarını küçült */
.table-actions .btn,
.table-actions button.btn,
.table-actions a.btn{
  padding: 6px 10px !important;
  font-size: 13px !important;
  border-radius: 999px !important;
  line-height: 1.1 !important;
}
.table-actions a.btn{
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
</style>

<!-- SAYFA BAŞLIĞI -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h1 class="card__title">👥 Sistem Kullanıcıları Yönetimi</h1>
    <p class="card__subtitle">Öğrenci, Organizator ve Admin kullanıcılarını buradan yönetebilirsin.</p>

    <?php if (isset($_GET['msg'])): ?>
      <?php
        $msg = $_GET['msg'];
        $text = null;
        if ($msg === 'added')   $text = "✅ Kullanıcı eklendi.";
        if ($msg === 'updated') $text = "✅ Kullanıcı güncellendi.";
        if ($msg === 'deleted') $text = "✅ Kullanıcı silindi.";
      ?>
      <?php if ($text): ?>
        <div class="alert" style="margin-top:14px; border:1px solid rgba(16,185,129,.25);">
          <?= htmlspecialchars($text) ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- KULLANICI LİSTESİ -->
<div class="card card--soft" style="margin-bottom:18px;">
  <div class="card__body">
    <h2 class="card__title" style="font-size:22px;">📋 Kullanıcı Listesi</h2>

    <?php if (empty($users)): ?>
      <div class="alert alert-error" style="margin-top:14px;">Henüz kullanıcı yok.</div>
    <?php else: ?>
      <div class="table-wrap" style="margin-top:14px;">
        <table>
          <thead>
            <tr>
              <th style="width:70px;">ID</th>
              <th>Ad Soyad</th>
              <th>E-posta</th>
              <th style="width:130px;">Rol</th>
              <th style="width:170px;">Bölüm</th>
              <th style="width:120px;">Doğum Tarihi</th>
              <th style="width:220px;">Telefon / Öğr. No</th>
              <th style="width:190px;">İşlemler</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td>#<?= (int)$u['user_id'] ?></td>
                <td><b><?= htmlspecialchars($u['full_name'] ?? '-') ?></b></td>
                <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                <td><span class="badge"><?= htmlspecialchars(strtoupper($u['role_name'] ?? '-')) ?></span></td>
                <td><?= htmlspecialchars($u['department_name'] ?? 'Bölüm Yok') ?></td>
                <td><?= !empty($u['birth_date']) ? date("d.m.Y", strtotime($u['birth_date'])) : '-' ?></td>
                <td><?= htmlspecialchars($u['phone'] ?? '-') ?> / <?= htmlspecialchars($u['student_number'] ?? '-') ?></td>

                <td class="table-actions">
                  <button class="btn btn-primary" type="button"
                          onclick="toggleEdit(<?= (int)$u['user_id'] ?>)">
                    ✏️ Düzenle
                  </button>

                  <?php if ((int)$u['user_id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                    <a class="btn"
                       href="users.php?delete_id=<?= (int)$u['user_id'] ?>"
                       onclick="return confirm('Silmek istiyor musunuz?')"
                       style="border:1px solid rgba(239,68,68,.25); background:#fff; font-weight:900; color: var(--danger);">
                      🗑️ Sil
                    </a>
                  <?php endif; ?>
                </td>
              </tr>

              <!-- Düzenleme Paneli -->
              <tr id="edit-box-<?= (int)$u['user_id'] ?>" style="display:none;">
                <td colspan="8">
                  <div class="card card--soft" style="margin:0;">
                    <div class="card__body">
                      <h3 class="card__title" style="font-size:18px;">✏️ Kullanıcı Düzenle: <?= htmlspecialchars($u['full_name'] ?? '-') ?></h3>

                      <form method="POST">
                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                          <div>
                            <label class="label">Ad Soyad</label>
                            <input class="input" type="text" name="full_name" value="<?= htmlspecialchars($u['full_name'] ?? '') ?>" required>
                          </div>
                          <div>
                            <label class="label">Email</label>
                            <input class="input" type="email" name="email" value="<?= htmlspecialchars($u['email'] ?? '') ?>" required>
                          </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                          <div>
                            <label class="label">Öğrenci No</label>
                            <input class="input" type="text" name="student_number" value="<?= htmlspecialchars($u['student_number'] ?? '') ?>">
                          </div>
                          <div>
                            <label class="label">Telefon</label>
                            <input class="input" type="text" name="phone" value="<?= htmlspecialchars($u['phone'] ?? '') ?>">
                          </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                          <div>
                            <label class="label">Doğum Tarihi</label>
                            <input class="input" type="date" name="birth_date" value="<?= htmlspecialchars($u['birth_date'] ?? '') ?>">
                          </div>
                          <div>
                            <label class="label">Rol</label>
                            <select class="input" name="role_id">
                              <option value="1" <?= ((int)$u['role_id'] === 1) ? 'selected' : '' ?>>Admin</option>
                              <option value="2" <?= ((int)$u['role_id'] === 2) ? 'selected' : '' ?>>Organizer</option>
                              <option value="3" <?= ((int)$u['role_id'] === 3) ? 'selected' : '' ?>>Student</option>
                            </select>
                          </div>
                        </div>

                        <label class="label">Bölüm</label>
                        <select class="input" name="department_id">
                          <option value="">Bölüm Yok</option>
                          <?php foreach ($departments as $d): ?>
                            <option value="<?= (int)$d['department_id'] ?>"
                              <?= ((int)($u['department_id'] ?? 0) === (int)$d['department_id']) ? 'selected' : '' ?>>
                              <?= htmlspecialchars($d['department_name']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>

                        <div class="actions-center" style="gap:10px; margin-top:14px;">
                          <button type="submit" name="update_user" class="btn btn-primary btn-wide">✅ Bilgileri Güncelle</button>
                          <button type="button" class="btn"
                                  onclick="toggleEdit(<?= (int)$u['user_id'] ?>)"
                                  style="border:1px solid rgba(124,58,237,.25); background:#fff; font-weight:900;">
                            ❌ İptal
                          </button>
                        </div>
                      </form>

                    </div>
                  </div>
                </td>
              </tr>

            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function toggleEdit(id){
  var el = document.getElementById('edit-box-' + id);
  if(!el) return;
  el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'table-row' : 'none';
}
</script>

<?php include "../partials/footer.php"; ?>
