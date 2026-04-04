<?php
session_start();
require_once '../connect/koneksi.php';
require_once '../connect/email_config.php';

// Check if user is logged in and has hrd role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hrd') {
    header('Location: ../login.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

function esc($conn, $s)
{
    return mysqli_real_escape_string($conn, $s);
}
function logActivity($conn, $actor_user_id, $action)
{
    $actor_user_id = (int)$actor_user_id;
    $action = mysqli_real_escape_string($conn, $action);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '::1';
    mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, action) VALUES ($actor_user_id, '$action')");
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['application_id'])) {
    $application_id = (int)$_POST['application_id'];

    if ($_POST['action'] === 'move_to_interview') {
        $interview_date = !empty($_POST['interview_date']) ? esc($conn, $_POST['interview_date']) : NULL;
        $q = "UPDATE applications SET status='tes & wawancara', updated_at=NOW()" .
            ($interview_date ? ", interview_date='$interview_date'" : "") .
            " WHERE application_id=$application_id";
        if (mysqli_query($conn, $q)) {
            $success = 'Dipindah ke Tes & Wawancara';
            logActivity($conn, $user_id, "HRD: set interview application #$application_id");
        } else {
            $error = 'Gagal memperbarui';
        }
    } elseif ($_POST['action'] === 'accept_hire') {
        $reason = esc($conn, $_POST['reason']);
        $start_date = esc($conn, $_POST['start_date']);
        $new_status = 'diterima bekerja';
        $q = "UPDATE applications SET status='$new_status', employment_status='aktif', updated_at=NOW(), reason='$reason', start_date='$start_date' WHERE application_id=$application_id";
        if (mysqli_query($conn, $q)) {
            $info = mysqli_query($conn, "SELECT a.*, u.email, u.full_name, l.title FROM applications a JOIN users u ON a.user_id=u.user_id JOIN lowongan l ON a.job_id=l.job_id WHERE a.application_id=$application_id");
            if ($info) {
                $row = mysqli_fetch_assoc($info);
                $to = $row['email'];
                $subject = 'Selamat Bergabung - ' . $row['title'];
                $msg = "Halo " . $row['full_name'] . ",\n\n" .
                    "Selamat! Anda DITERIMA BEKERJA pada posisi " . $row['title'] . ".\n" .
                    "Tanggal Mulai: " . $start_date . "\n" .
                    "Catatan: " . $reason . "\n\nSampai jumpa di hari pertama.\nHRD";
                sendEmail($to, $subject, $msg);
                $notif_title = esc($conn, "Update Status - " . $row['title']);
                $notif_msg   = "Status terbaru: $new_status. Tanggal mulai: $start_date. Catatan: $reason";
                mysqli_query(
                    $conn,
                    "INSERT INTO pelamar_notifications (user_id, application_id, title, message, status, is_read)
                     VALUES (" . (int)$row['user_id'] . ", $application_id, '$notif_title', '$notif_msg', '$new_status', 0)"
                );
                logActivity($conn, $user_id, "HRD: terima bekerja application #$application_id (" . $row['title'] . ")");
            }
            $success = 'Kandidat diterima bekerja';
        } else {
            $error = 'Gagal memperbarui';
        }
    } elseif ($_POST['action'] === 'reject_after_interview') {
        $reason = esc($conn, $_POST['reason']);
        $new_status = 'ditolak';
        $q = "UPDATE applications SET status='$new_status', updated_at=NOW(), reason='$reason' WHERE application_id=$application_id";
        if (mysqli_query($conn, $q)) {
            $info = mysqli_query($conn, "SELECT a.*, u.email, u.full_name, l.title FROM applications a JOIN users u ON a.user_id=u.user_id JOIN lowongan l ON a.job_id=l.job_id WHERE a.application_id=$application_id");
            if ($info) {
                $row = mysqli_fetch_assoc($info);
                $to = $row['email'];
                $subject = 'Hasil Wawancara - ' . $row['title'];
                $msg = "Halo " . $row['full_name'] . ",\n\n" .
                    "Terima kasih telah mengikuti proses. Mohon maaf Anda BELUM DITERIMA untuk posisi " . $row['title'] . ".\n" .
                    "Alasan: " . $reason . "\n\nSemoga sukses di kesempatan berikutnya.\nHRD";
                sendEmail($to, $subject, $msg);
                $notif_title = esc($conn, "Update Status - " . $row['title']);
                $notif_msg   = "Status terbaru: $new_status. Alasan: $reason";
                mysqli_query(
                    $conn,
                    "INSERT INTO pelamar_notifications (user_id, application_id, title, message, status, is_read)
                     VALUES (" . (int)$row['user_id'] . ", $application_id, '$notif_title', '$notif_msg', '$new_status', 0)"
                );
                logActivity($conn, $user_id, "HRD: tolak setelah interview application #$application_id (" . $row['title'] . ")");
            }
            $success = 'Kandidat ditolak';
        } else {
            $error = 'Gagal memperbarui';
        }
    }
}

// Fetch candidates with education data from relational tables
$list = mysqli_query($conn, "SELECT a.*, u.full_name, u.email, l.title,
    jenjang.nama_jenjang,
    jurusan.nama_jurusan
    FROM applications a 
    JOIN users u ON a.user_id=u.user_id 
    JOIN lowongan l ON a.job_id=l.job_id 
    LEFT JOIN jenjang_pendidikan jenjang ON a.id_jenjang_pendidikan = jenjang.id_jenjang
    LEFT JOIN jurusan_pendidikan jurusan ON a.id_jurusan_pendidikan = jurusan.id_jurusan
    WHERE a.status IN ('lolos administrasi','tes & wawancara')
    ORDER BY COALESCE(a.interview_date,a.updated_at) ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kandidat - PT Waindo Specterra</title>
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/candidates.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Kandidat</h3>
                <p>Selamat datang, <?php echo htmlspecialchars($full_name); ?></p>
            </div>

            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="lowongan.php"><i class="fas fa-briefcase"></i> Kelola Lowongan</a></li>
                <li><a href="applications.php"><i class="fas fa-file-alt"></i> Kelola Lamaran</a></li>
                <li><a href="candidates.php" class="active"><i class="fas fa-users"></i> Kandidat</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Beranda</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>Kandidat</h1>
                <p>Kelola kandidat pada tahap lanjutan</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Daftar Kandidat</h3>
                    <div class="header-info">
                        <span class="info-badge">
                            <i class="fas fa-user-check"></i>
                            Total: <?php echo ($list ? mysqli_num_rows($list) : 0); ?> Kandidat
                        </span>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Nama</th>
                                <th><i class="fas fa-briefcase"></i> Posisi</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                                <th><i class="fas fa-calendar-check"></i> Jadwal Wawancara</th>
                                <th><i class="fas fa-calendar"></i> Tgl Melamar</th>
                                <th><i class="fas fa-cog"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list && mysqli_num_rows($list) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($list)): ?>
                                    <tr>
                                        <td>
                                            <div class="candidate-info">
                                                <div class="candidate-name">
                                                    <i class="fas fa-user-circle"></i>
                                                    <?php echo htmlspecialchars($row['full_name']); ?>
                                                </div>
                                                <div class="candidate-meta">
                                                    <?php if (!empty($row['no_telepon'])): ?>
                                                        <span class="meta-item">
                                                            <i class="fas fa-phone"></i>
                                                            <?php echo htmlspecialchars($row['no_telepon']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['nama_jenjang'])): ?>
                                                        <span class="meta-item">
                                                            <i class="fas fa-graduation-cap"></i>
                                                            <?php 
                                                            echo htmlspecialchars($row['nama_jenjang']);
                                                            if (!empty($row['nama_jurusan'])) {
                                                                echo ' - ' . htmlspecialchars($row['nama_jurusan']);
                                                            }
                                                            ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="position-cell">
                                            <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge status-<?php echo str_replace(' ', '-', $row['status']); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="date-cell">
                                            <?php if (!empty($row['interview_date'])): ?>
                                                <div class="interview-date">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($row['interview_date'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Belum dijadwalkan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="date-cell"><?php echo date('d/m/Y', strtotime($row['applied_at'])); ?></td>
                                        <td>
                                            <div class="row-actions">
                                                <a href="candidate-detail.php?id=<?php echo (int)$row['application_id']; ?>" class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i> Periksa
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="no-data">
                                            <i class="fas fa-users-slash"></i>
                                            <p>Belum ada kandidat pada tahap lanjutan</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/navbar.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.mobile-toggle');

            sidebar.classList.toggle('active');

            if (sidebar.classList.contains('active')) {
                toggleBtn.style.display = "none";
            } else {
                toggleBtn.style.display = "block";
            }
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-toggle');

            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !mobileToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                    mobileToggle.style.display = "block";
                }
            }
        });
    </script>
</body>

</html>