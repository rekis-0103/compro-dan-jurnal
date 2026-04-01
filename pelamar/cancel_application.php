<?php
session_start();
require_once '../connect/koneksi.php';

// Hanya pelamar yang login yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pelamar') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Validasi method dan parameter
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['application_id'])) {
    header('Location: applications.php?error=invalid_request');
    exit();
}

$application_id = (int) $_POST['application_id'];

// Cek lamaran milik user ini dan statusnya masih pending (menunggu/diproses awal)
// Hanya boleh dibatalkan jika status masih "menunggu" atau "pending"
$check = mysqli_query($conn, "SELECT application_id, status, cv FROM applications 
                               WHERE application_id = $application_id AND user_id = $user_id");

if (!$check || mysqli_num_rows($check) === 0) {
    header('Location: applications.php?error=not_found');
    exit();
}

$app = mysqli_fetch_assoc($check);

// Batasi pembatalan hanya untuk status yang belum diproses lanjut
$allowed_statuses = ['menunggu', 'pending'];
if (!in_array(strtolower($app['status']), $allowed_statuses)) {
    header('Location: applications.php?error=cannot_cancel');
    exit();
}

// Hapus dari database
$delete = mysqli_query($conn, "DELETE FROM applications WHERE application_id = $application_id AND user_id = $user_id");

if ($delete) {
    // Opsional: hapus file CV jika ada
    // $cv_path = '../' . $app['cv'];
    // if (!empty($app['cv']) && file_exists($cv_path)) {
    //     unlink($cv_path);
    // }
    header('Location: applications.php?success=cancelled');
} else {
    header('Location: applications.php?error=delete_failed');
}
exit();