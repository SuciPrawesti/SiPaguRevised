<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: ../index.php");
    exit();
}
header("Location: index.php");
exit();
