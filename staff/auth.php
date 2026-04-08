<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: " . BASE_URL . "index.php?pesan=belum_login");
    exit;
}
