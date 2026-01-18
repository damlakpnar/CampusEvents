<?php
// aktif sayfa helper
$current = basename($_SERVER['PHP_SELF']);
function is_active(string $file, string $current): string {
  return $file === $current ? 'active' : '';
}

// Şu an hangi klasördeyiz? student / organizer / admin / root
$dir = basename(dirname($_SERVER['PHP_SELF']));
$known = ['student', 'organizer', 'admin'];
$area = in_array($dir, $known, true) ? $dir : 'root';

// Link base: aynı klasördeyken '' ; root'tan giderken 'student/' gibi
$studentBase    = ($area === 'student') ? '' : 'student/';
$organizerBase  = ($area === 'organizer') ? '' : 'organizer/';
$adminBase      = ($area === 'admin') ? '' : 'admin/';

// logout root'ta; alt klasördeyken ../ ile çıkmak lazım
$logoutHref = ($area === 'root') ? 'logout.php' : '../logout.php';

// Profil sayfası root'ta: alt klasördeyken ../profile.php, root'taysan profile.php
$profileHref = ($area === 'root') ? 'profile.php' : '../profile.php';

// Role tespiti (1=admin, 2=organizer, 3=student)
$roleId = $_SESSION['role_id'] ?? $_SESSION['role'] ?? null;

// Menü öğeleri
$menus = [
  'student' => [
    ['file' => 'dashboard.php',         'href' => $studentBase.'dashboard.php',         'label' => '🏠 Panel'],
    ['file' => 'notifications.php',     'href' => $studentBase.'notifications.php',     'label' => '🔔 Bildirimlerim'],
    ['file' => 'department_events.php', 'href' => $studentBase.'department_events.php', 'label' => '🎓 Bölüm Etkinlikleri'],
    ['file' => 'my_events.php',         'href' => $studentBase.'my_events.php',         'label' => '📌 Kayıtlı Etkinliklerim'],
    ['file' => 'profile.php',           'href' => $profileHref,                         'label' => '👤 Profilim / Şifre'],
  ],

  'organizer' => [
    ['file' => 'dashboard.php',     'href' => $organizerBase.'dashboard.php',     'label' => '🏠 Panel'],
    ['file' => 'create_event.php',  'href' => $organizerBase.'create_event.php',  'label' => '➕ Etkinlik Oluştur'],
    ['file' => 'profile.php',       'href' => $profileHref,                       'label' => '👤 Profilim / Şifre'],
  ],

  'admin' => [
    ['file' => 'dashboard.php',  'href' => $adminBase.'dashboard.php',  'label' => '🏠 Panel'],
    ['file' => 'users.php',      'href' => $adminBase.'users.php',      'label' => '👤 Kullanıcılar'],
    ['file' => 'statistics.php', 'href' => $adminBase.'statistics.php', 'label' => '📊 İstatistik'],
    ['file' => 'events.php',     'href' => $adminBase.'events.php',     'label' => '📅 Etkinlikler'],
    ['file' => 'logs.php',       'href' => $adminBase.'logs.php',       'label' => '🧾 Loglar'],
    ['file' => 'profile.php',    'href' => $profileHref,                'label' => '👤 Profilim / Şifre'],
  ],
];

// Hangi menü gösterilecek?
$menuKey = ($area !== 'root') ? $area : (
  ($roleId == 1) ? 'admin' : (($roleId == 2) ? 'organizer' : 'student')
);

$items = $menus[$menuKey] ?? $menus['student'];
?>

<main class="layout">
  <div class="layout__inner">

    <aside class="sidebar">
      <div class="sidebar__inner">

        <div class="sidebar__card">
          <div class="sidebar__title">Menü</div>

          <nav class="menu">
            <?php foreach ($items as $it): ?>
              <a class="<?= is_active($it['file'], $current) ?>"
                 href="<?= htmlspecialchars($it['href']) ?>">
                <?= htmlspecialchars($it['label']) ?>
              </a>
            <?php endforeach; ?>

            <a class="logout" href="<?= htmlspecialchars($logoutHref) ?>">🚪 Çıkış</a>
          </nav>

        </div>
      </div>
    </aside>

    <!-- CONTENT START -->
    <section class="content">
