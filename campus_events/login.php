<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/db.php";
require_once "includes/session.php"; // session_start() olmalı

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $pass  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($pass, $user['password_hash'])) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['user_role'] = $user['role_id'];
        $_SESSION['user_name'] = $user['full_name'];

        $roleName = "Kullanıcı";
        if ($user['role_id'] == 1) $roleName = "Admin";
        if ($user['role_id'] == 2) $roleName = "Organizer";
        if ($user['role_id'] == 3) $roleName = "Student";

        try {
            $logDesc = "$roleName (" . $user['full_name'] . ") sisteme giriş yaptı.";
            $logStmt = $pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, ?, ?)");
            $logStmt->execute([$user['user_id'], 'login', $logDesc]);
        } catch (PDOException $e) {
            die("Log Hatası: " . $e->getMessage());
        }

        header("Location: index.php");
        exit;
    } else {
        $error = "E-posta veya şifre hatalı.";
    }
}
?>

<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Giriş Yap | Campus Events</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-bg">

  <main class="login-page">
    <div class="login-grid">

      <!-- SOL KART -->
      <section class="card card--soft">
        <div class="card__body">
          <h1 class="card__title">Hoş geldin 👋</h1>
          <p class="card__subtitle">
            Bu sistemde rolüne göre (Admin / Organizer / Student) ilgili panel ekranına yönlendirilirsin.
            Giriş yaptıktan sonra etkinlikleri görüntüleyebilir, kayıt olabilir veya yönetim işlemleri yapabilirsin.
          </p>

          <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px;">
            <span class="badge">Admin</span>
            <span class="badge">Organizer</span>
            <span class="badge">Student</span>
          </div>
        </div>
      </section>

      <!-- SAĞ KART (FORM) -->
      <section class="card card--soft">
        <div class="card__body">
          <h2 class="card__title" style="font-size:30px;">Giriş Yap</h2>

          <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST" novalidate>
            <label class="label" for="email">E-posta</label>
            <input
              class="input"
              type="email"
              id="email"
              name="email"
              required
              placeholder="ornek@uni.edu"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              autocomplete="email"
            >

            <label class="label" for="password">Şifre</label>
            <input
              class="input"
              type="password"
              id="password"
              name="password"
              required
              placeholder="•••"
              autocomplete="current-password"
            >

            <div class="actions-center">
              <button class="btn btn-primary btn-wide" type="submit">Giriş</button>
            </div>
          </form>
        </div>
      </section>

    </div>

    <footer class="footer">© <?= date('Y') ?> Campus Events</footer>
  </main>

</body>
</html>
