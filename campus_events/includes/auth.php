<?php
// includes/auth.php

require_once __DIR__ . "/session.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /campus_events/login.php");
    exit;
}
