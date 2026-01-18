<?php
/**
 * profile.php - ANA DİZİNDE (campus_events)
 * TÜM KULLANICI ROLLERİ İÇİN ORTAK PROFİL YÖNETİM SAYFASI
 */

require_once "includes/auth.php"; 
require_once "config/db.php";

$userId = $_SESSION['user_id'];
$successMsg = ""; 
$errorMsg = "";   

/* ==========================================================================
   1. ADIM: MEVCUT KULLANICI BİLGİLERİNİ ÇEKME
   birth_date ve student_number alanlarını da sorguya dahil ettik.
   ========================================================================== */
$sql = "SELECT u.*, d.department_name, up.phone, up.bio, up.student_number, up.birth_date 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.department_id 
        LEFT JOIN user_profiles up ON u.user_id = up.user_id 
        WHERE u.user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { die("Kullanıcı kaydı bulunamadı!"); }

/* ==========================================================================
   2. ADIM: PROFİL DETAYLARINI GÜNCELLEME
   İsim, E-posta, Bölüm, Öğrenci No ve Doğum Tarihi kilitlidir.
   ========================================================================== */
if (isset($_POST['update_profile'])) {
    $phone = $_POST['phone'];
    $bio   = $_POST['bio'];

    try {
        $check = $pdo->prepare("SELECT user_id FROM user_profiles WHERE user_id = ?");
        $check->execute([$userId]);
        
        if ($check->fetch()) {
            $pdo->prepare("UPDATE user_profiles SET phone = ?, bio = ? WHERE user_id = ?")
                ->execute([$phone, $bio, $userId]);
        } else {
            $pdo->prepare("INSERT INTO user_profiles (user_id, phone, bio) VALUES (?, ?, ?)")
                ->execute([$userId, $phone, $bio]);
        }

        $successMsg = "Profil bilgileriniz başarıyla güncellendi.";
        $user['phone'] = $phone;
        $user['bio'] = $bio;
    } catch (Exception $e) {
        $errorMsg = "Güncelleme hatası: " . $e->getMessage();
    }
}

/* ==========================================================================
   3. ADIM: ŞİFRE DEĞİŞTİRME
   ========================================================================== */
if (isset($_POST['change_password'])) {
    $currentPass = $_POST['current_password'];
    $newPass     = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    if (password_verify($currentPass, $user['password_hash'])) {
        if ($newPass === $confirmPass) {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([$hashed, $userId]);
            $successMsg = "Şifreniz başarıyla değiştirildi.";
        } else { $errorMsg = "Şifreler eşleşmiyor!"; }
    } else { $errorMsg = "Mevcut şifre hatalı!"; }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Profil Ayarlarım | Kampüs Etkinlik</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; padding: 20px; color: #333; }
        .container { max-width: 950px; margin: auto; display: flex; gap: 25px; flex-wrap: wrap; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); flex: 1; min-width: 350px; }
        .role-badge { display: inline-block; padding: 6px 15px; background: #007bff; color: white; border-radius: 25px; font-size: 12px; font-weight: bold; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 10px; color: #555; }
        input, textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 14px; }
        input[readonly] { background: #f9f9f9; color: #888; cursor: not-allowed; border: 1px dashed #ccc; }
        .btn { padding: 14px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; font-size: 15px; transition: 0.3s; }
        .btn-update { background: #28a745; color: white; }
        .btn-pass { background: #495057; color: white; margin-top: 10px; }
        .btn-back { background: #ffffff; color: #007bff; border: 2px solid #007bff; text-decoration: none; display: inline-block; padding: 10px 25px; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .msg { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <span class="role-badge">
            <?php 
                if($user['role_id'] == 1) echo "🛡️ SİSTEM YÖNETİCİSİ";
                elseif($user['role_id'] == 2) echo "🗓️ ORGANİZATÖR";
                else echo "🎓 ÖĞRENCİ";
            ?>
        </span>
        <h2>👤 Profil Bilgilerim</h2>
        <p style="color: #ea4335; font-size: 0.85em; font-weight: bold;">* Temel bilgileriniz (İsim, Doğum Tarihi vb.) güvenlik için kilitlenmiştir.</p>

        <?php if($successMsg): ?> <div class="msg" style="color:#155724; background:#d4edda;"><?= $successMsg ?></div> <?php endif; ?>
        <?php if($errorMsg): ?> <div class="msg" style="color:#721c24; background:#f8d7da;"><?= $errorMsg ?></div> <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Ad Soyad</label>
                <input type="text" value="<?= htmlspecialchars($user['full_name']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Doğum Tarihi</label>
                <input type="text" value="<?= $user['birth_date'] ? date("d.m.Y", strtotime($user['birth_date'])) : 'Belirtilmedi' ?>" readonly>
            </div>

            <div class="form-group">
                <label>E-posta Adresi</label>
                <input type="text" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>

            <div class="form-group">
                <label>Kayıtlı Olduğu Bölüm</label>
                <input type="text" value="<?= htmlspecialchars($user['department_name'] ?? 'Genel') ?>" readonly>
            </div>

            <?php if($user['role_id'] == 3): ?>
            <div class="form-group">
                <label>Öğrenci Numarası</label>
                <input type="text" value="<?= htmlspecialchars($user['student_number'] ?? '-') ?>" readonly>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>İletişim Telefonu</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Örn: 0555 555 55 55">
            </div>

            <div class="form-group">
                <label>Kısa Biyografi</label>
                <textarea name="bio" rows="4" placeholder="Kendinizden kısaca bahsedin..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>

            <button type="submit" name="update_profile" class="btn btn-update">💾 Değişiklikleri Kaydet</button>
        </form>
    </div>

    <div class="card" style="max-width: 320px; flex: 0.5;">
        <h3>🔐 Şifre Güncelle</h3>
        <form method="POST">
            <div class="form-group">
                <label>Mevcut Şifre</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>Yeni Şifre</label>
                <input type="password" name="new_password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Yeni Şifre (Onay)</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" name="change_password" class="btn btn-pass">🔄 Şifreyi Güncelle</button>
        </form>
        
        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">
        
        <div style="text-align: center;">
            <?php 
                $back = "index.php"; 
                if($_SESSION['user_role'] == 1) $back = "admin/dashboard.php";
                elseif($_SESSION['user_role'] == 2) $back = "organizer/dashboard.php";
                elseif($_SESSION['user_role'] == 3) $back = "student/dashboard.php";
            ?>
            <a href="<?= $back ?>" class="btn-back">⬅️ Panele Geri Dön</a>
        </div>
    </div>
</div>

</body>
</html> bu profile.php önce bunu halledelim