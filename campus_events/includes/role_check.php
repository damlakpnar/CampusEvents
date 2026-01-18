<?php
// includes/role_check.php

function checkRole($allowedRoles = [])
{
    if (!isset($_SESSION['user_role'])) {
        header("Location: /campus_events/login.php");
        exit;
    }

    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        die("Bu sayfaya erişim yetkiniz yok.");
    }
}
