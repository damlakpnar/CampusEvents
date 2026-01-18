<?php
// config/db.php
// Hataları ekrana bas (Sorunu görmek için)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "localhost";
$user = "root";
$db   = "campus_events"; 
$pass = "mysql"; 

try {
    // XAMPP için doğrudan bağlantı
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    
    // Hata modunu aktifleştir
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Verileri dizi olarak çekmek için varsayılan ayar
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Bağlantı başarılıysa ekrana bir şey basmasına gerek yok (Beyaz ekranı engellemek için)
} catch (PDOException $e) {
    // Eğer veritabanı ismi yanlışsa veya phpMyAdmin'de yoksa bu hatayı verir
    die("Veritabanı Bağlantı Hatası: " . $e->getMessage());
}
?>