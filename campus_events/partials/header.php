<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$page_title = $page_title ?? "Campus Events";

// Bulunduğumuz klasör
$dir = basename(dirname($_SERVER['PHP_SELF']));
$known = ['student','admin','organizer'];

// assets'a giden doğru prefix
$ASSET_BASE = in_array($dir, $known, true) ? "../" : "";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?></title>

  <!-- ANA CSS -->
  <link rel="stylesheet" href="<?= $ASSET_BASE ?>assets/css/style.css?v=1">

  <!-- varsa ek csslerin -->
  <!-- <link rel="stylesheet" href="<?= $ASSET_BASE ?>assets/css/extra.css?v=1"> -->
</head>
<body>

<header class="topbar">
  <div class="topbar__inner">
    <div class="brand">Campus Events</div>
    <div class="topbar__right">
      <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>
    </div>
  </div>
</header>
