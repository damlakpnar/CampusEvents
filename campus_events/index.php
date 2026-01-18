<?php
require_once "includes/session.php";

// Giriş yaptıysa role göre yönlendir (index görünmesin)
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? null;

    if ($role == 1) { header("Location: admin/dashboard.php"); exit; }
    if ($role == 2) { header("Location: organizer/dashboard.php"); exit; }
    if ($role == 3) { header("Location: student/dashboard.php"); exit; }

    header("Location: login.php");
    exit;
}
?>

<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Campus Events</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-bg">
  <main class="login-page" style="justify-content:flex-start; padding-top:60px;">

    <!-- HERO -->
    <section class="card card--soft" style="max-width:1100px; margin:0 auto 22px; width:100%;">
      <div class="card__body">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
          <div>
            <h1 class="card__title" style="font-size:44px; margin-bottom:6px;">Campus Events ✨</h1>
            <p class="card__subtitle" style="font-size:16px; max-width:720px;">
              Kampüsteki etkinlikleri tek yerden keşfet, hızlıca kayıt ol ve etkinlik sonrası geri bildirim paylaş.
              Rolüne göre (Admin/Organizer/Student) farklı panellere yönlendirilirsin.
            </p>

            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:14px;">
              <span class="badge">Hızlı Kayıt</span>
              <span class="badge">Bildirimler</span>
              <span class="badge">Geri Bildirim</span>
              <span class="badge">Rol Bazlı Paneller</span>
            </div>
          </div>

        
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- GRID: ÖZELLİKLER + NASIL ÇALIŞIR -->
    <section style="max-width:1100px; margin:0 auto; width:100%;">
      <div class="landing-grid">

        <!-- Özellikler -->
        <div class="card card--soft">
          <div class="card__body">
            <h2 class="card__title" style="font-size:26px;">🚀 Neler Yapabilirsin?</h2>
            <p class="card__subtitle">Sistem içindeki temel işlevler.</p>

            <div style="display:grid; gap:12px; margin-top:14px;">
              <div style="padding:12px 14px; border:1px solid rgba(124,58,237,.12); border-radius:14px; background:rgba(167,139,250,.10);">
                <b>🔎 Etkinlik Keşfet</b>
                <div style="color:var(--muted); margin-top:4px;">Yaklaşan etkinlikleri listele, filtrele ve detaylarını gör.</div>
              </div>

              <div style="padding:12px 14px; border:1px solid rgba(124,58,237,.12); border-radius:14px; background:rgba(167,139,250,.10);">
                <b>✅ Kayıt & Yönetim</b>
                <div style="color:var(--muted); margin-top:4px;">Kontenjan durumuna göre etkinliğe kayıt ol / kaydını iptal et.</div>
              </div>

              <div style="padding:12px 14px; border:1px solid rgba(124,58,237,.12); border-radius:14px; background:rgba(167,139,250,.10);">
                <b>📝 Geri Bildirim</b>
                <div style="color:var(--muted); margin-top:4px;">Etkinlik sonrası puan verip yorum paylaş.</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Roller -->
        <div class="card card--soft">
          <div class="card__body">
            <h2 class="card__title" style="font-size:26px;">🧩 Roller</h2>
            <p class="card__subtitle">Her rolün farklı yetkileri vardır.</p>

            <div style="display:grid; gap:12px; margin-top:14px;">
              <div style="padding:12px 14px; border-radius:14px; border:1px solid rgba(124,58,237,.12);">
                <b>Admin</b>
                <div style="color:var(--muted); margin-top:4px;">Kullanıcılar, etkinlikler, istatistikler ve log yönetimi.</div>
              </div>

              <div style="padding:12px 14px; border-radius:14px; border:1px solid rgba(124,58,237,.12);">
                <b>Organizer</b>
                <div style="color:var(--muted); margin-top:4px;">Etkinlik oluşturma/düzenleme, katılımcı listeleri, durum güncelleme.</div>
              </div>

              <div style="padding:12px 14px; border-radius:14px; border:1px solid rgba(124,58,237,.12);">
                <b>Student</b>
                <div style="color:var(--muted); margin-top:4px;">Etkinliklere kayıt, bildirimler, bölüm etkinlikleri ve geri bildirim.</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Nasıl çalışır -->
        <div class="card card--soft">
          <div class="card__body">
            <h2 class="card__title" style="font-size:26px;">🪄 Nasıl Çalışır?</h2>
            <p class="card__subtitle">3 adımda kullanım.</p>

            <ol style="margin:14px 0 0; padding-left:18px; color:var(--text); line-height:1.8;">
              <li><b>Giriş Yap:</b> E-posta ve şifrenle sisteme gir.</li>
              <li><b>Paneli Kullan:</b> Rolüne göre yönetim paneline yönlendirilirsin.</li>
              <li><b>İşlemler:</b> Etkinliklere kayıt ol, takip et, bildirimleri kontrol et.</li>
            </ol>

            <div class="actions-center" style="margin-top:18px;">
              <a class="btn btn-primary btn-wide" href="login.php">Şimdi Giriş Yap</a>
            </div>
          </div>
        </div>

      </div>
    </section>

    <footer class="footer">© <?= date('Y') ?> Campus Events</footer>
  </main>
</body>
</html>
