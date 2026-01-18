<?php
session_start();
require "../config/db.php";
require "../includes/role_check.php";

checkRole([2]); // Organizer

if (!isset($_GET['event_id'])) die("Etkinlik ID belirtilmedi!");

$eventId = (int)$_GET['event_id'];

// Stored Procedure (SP) kullanmak yerine, Email bilgisini de çekmek için 
// manuel SQL sorgusu kullanıyoruz.
// ✅ DISTINCT eklendi: Organizatör, tabloda mükerrer olsa bile sadece bir kez listelenir.
$stmt = $pdo->prepare("
    SELECT DISTINCT
        u.full_name,
        u.email,         /* ✅ Email bilgisini çektik */
        r.role_name,
        eo.role_in_event
    FROM event_organizers AS eo
    INNER JOIN users AS u ON eo.user_id = u.user_id
    INNER JOIN roles AS r ON u.role_id = r.role_id
    WHERE eo.event_id = ?
    ORDER BY u.full_name
");
$stmt->execute([$eventId]);
$organizers = $stmt->fetchAll();

// ✅ LOG EKLEME
$logDesc = $_SESSION['user_name'] . " (ID: $eventId) nolu etkinliğin organizatörlerini inceledi.";
$pdo->prepare("INSERT INTO system_logs (user_id, action_type, description) VALUES (?, 'view_list', ?)")
    ->execute([$_SESSION['user_id'], $logDesc]);

// Eğer GetEventOrganizers SP'sini kullanırsanız bu satır gereklidir: $stmt->closeCursor(); 
// Manuel sorguda gerekmez.
?>

<h2>Organizatörler</h2>

<?php if (empty($organizers)): ?>
    <p>Henüz organizatör atanmamış.</p>
<?php else: ?>
<table border="1" cellpadding="8">
<tr>
    <th>Ad Soyad</th>
    <th>Email</th>         <th>Rol</th>
    <th>Etkinlikteki Rolü</th>
</tr>
<?php foreach ($organizers as $o): ?>
<tr>
    <td><?= htmlspecialchars($o['full_name']) ?></td>
    <td><?= htmlspecialchars($o['email']) ?></td> <td><?= htmlspecialchars($o['role_name']) ?></td>
    <td><?= htmlspecialchars($o['role_in_event']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<p><a href="dashboard.php">Geri Dön</a></p>