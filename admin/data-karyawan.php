<?php
session_start();
require_once '../connect/koneksi.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_employment_status'], $_POST['application_id'])) {
    $app_id = (int) $_POST['application_id'];
    $new_es = $_POST['employment_status'] === 'non_aktif' ? 'non_aktif' : 'aktif';
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE applications SET employment_status = ?, updated_at = NOW() WHERE application_id = ? AND status = 'diterima bekerja'"
    );
    if ($stmt && mysqli_stmt_bind_param($stmt, 'si', $new_es, $app_id) && mysqli_stmt_execute($stmt)) {
        $action = mysqli_real_escape_string($conn, "Admin: ubah status karyawan application #$app_id menjadi $new_es");
        mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, action) VALUES ($user_id, '$action')");
    }
    if ($stmt) {
        mysqli_stmt_close($stmt);
    }
    $redir_filter = isset($_POST['emp_filter_redirect']) ? $_POST['emp_filter_redirect'] : 'semua';
    if (!in_array($redir_filter, ['semua', 'aktif', 'non_aktif'], true)) {
        $redir_filter = 'semua';
    }
    $qs = $redir_filter !== 'semua' ? '?emp_filter=' . urlencode($redir_filter) : '';
    header('Location: data-karyawan.php' . $qs);
    exit();
}

$emp_filter = isset($_GET['emp_filter']) ? $_GET['emp_filter'] : 'semua';
if (!in_array($emp_filter, ['semua', 'aktif', 'non_aktif'], true)) {
    $emp_filter = 'semua';
}

$emp_where = '';
if ($emp_filter === 'aktif') {
    $emp_where = " AND (a.employment_status IS NULL OR a.employment_status = 'aktif')";
} elseif ($emp_filter === 'non_aktif') {
    $emp_where = " AND a.employment_status = 'non_aktif'";
}

// Query untuk mendapatkan data karyawan yang diterima bekerja
$query_karyawan = "SELECT 
    a.application_id,
    u.full_name,
    u.email,
    u.no_telepon,
    j.nama_jenjang,
    jr.nama_jurusan,
    l.title as posisi,
    a.start_date,
    a.interview_date,
    a.employment_status
FROM applications a
INNER JOIN users u ON a.user_id = u.user_id
INNER JOIN lowongan l ON a.job_id = l.job_id
LEFT JOIN jenjang_pendidikan j ON a.id_jenjang_pendidikan = j.id_jenjang
LEFT JOIN jurusan_pendidikan jr ON a.id_jurusan_pendidikan = jr.id_jurusan
WHERE a.status = 'diterima bekerja'
$emp_where
ORDER BY a.start_date DESC";

$result_karyawan = mysqli_query($conn, $query_karyawan);
$total_karyawan = mysqli_num_rows($result_karyawan);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Karyawan - PT Waindo Specterra</title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/data-karyawan.css">
</head>

<body>
    <!-- Tombol hamburger -->
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Admin Dashboard</h3>
                <p>Selamat datang, <?php echo htmlspecialchars($full_name); ?></p>
            </div>

            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Kelola User</a></li>
                <li><a href="logs.php"><i class="fas fa-history"></i> Log Aktivitas</a></li>
                <li><a href="pendidikan.php"><i class="fas fa-graduation-cap"></i> Pendidikan</a></li>
                <li><a href="data-karyawan.php" class="active"><i class="fas fa-address-card"></i> Data Karyawan</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Beranda</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main content -->
        <div class="main-content">

<div class="dashboard-header">
    <h1>Data Karyawan</h1>
    <p>Daftar karyawan yang telah diterima bekerja</p>
</div>

<div class="karyawan-container">
    <div class="karyawan-header">
        <div class="header-info">
            <h2>Total: <?php echo $total_karyawan; ?> karyawan</h2>
            <form method="get" class="emp-filter-form">
                <label for="emp_filter">Tampilkan:</label>
                <select name="emp_filter" id="emp_filter" onchange="this.form.submit()">
                    <option value="semua" <?php echo $emp_filter === 'semua' ? 'selected' : ''; ?>>Semua</option>
                    <option value="aktif" <?php echo $emp_filter === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="non_aktif" <?php echo $emp_filter === 'non_aktif' ? 'selected' : ''; ?>>Non-aktif</option>
                </select>
            </form>
        </div>
        <?php if($total_karyawan > 0): ?>
        <div class="header-actions">
            <a href="export_karyawan.php<?php echo $emp_filter !== 'semua' ? '?emp_filter=' . urlencode($emp_filter) : ''; ?>" class="btn-export" target="_blank">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="table-container">
        <table class="karyawan-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>No. Telepon</th>
                    <th>Pendidikan</th>
                    <th>Jurusan</th>
                    <th>Posisi</th>
                    <th>Tanggal Mulai Kerja</th>
                    <th>Status Karyawan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if($total_karyawan > 0):
                    $no = 1;
                    while($row = mysqli_fetch_assoc($result_karyawan)):
                        $pendidikan = $row['nama_jenjang'] ?? '-';
                        $jurusan = $row['nama_jurusan'] ?? '-';
                        $start_date = date('d/m/Y', strtotime($row['start_date']));
                        $es = $row['employment_status'] ?? null;
                        $is_active = ($es === null || $es === '' || $es === 'aktif');
                ?>
                <tr class="<?php echo $is_active ? 'row-emp-aktif' : 'row-emp-nonaktif'; ?>">
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['no_telepon'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($pendidikan); ?></td>
                    <td><?php echo htmlspecialchars($jurusan); ?></td>
                    <td><?php echo htmlspecialchars($row['posisi']); ?></td>
                    <td><?php echo $start_date; ?></td>
                    <td>
                        <?php if ($is_active): ?>
                            <span class="badge-emp badge-emp-aktif"><i class="fas fa-user-check"></i> Aktif</span>
                        <?php else: ?>
                            <span class="badge-emp badge-emp-nonaktif"><i class="fas fa-user-slash"></i> Non-aktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" class="form-emp-status">
                            <input type="hidden" name="update_employment_status" value="1">
                            <input type="hidden" name="application_id" value="<?php echo (int)$row['application_id']; ?>">
                            <input type="hidden" name="emp_filter_redirect" value="<?php echo htmlspecialchars($emp_filter); ?>">
                            <select name="employment_status" class="select-emp-status" title="Status hubungan kerja">
                                <option value="aktif" <?php echo $is_active ? 'selected' : ''; ?>>Aktif</option>
                                <option value="non_aktif" <?php echo !$is_active ? 'selected' : ''; ?>>Non-aktif</option>
                            </select>
                            <button type="submit" class="btn-emp-save"><i class="fas fa-save"></i></button>
                        </form>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="10" class="text-center">Belum ada data untuk filter ini</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
        
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.mobile-toggle');

            sidebar.classList.toggle('active');

            // Sembunyikan tombol ketika sidebar muncul
            if (sidebar.classList.contains('active')) {
                toggleBtn.style.display = "none";
            } else {
                toggleBtn.style.display = "block";
            }
        }

        // Tutup sidebar kalau klik di luar
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-toggle');

            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !mobileToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                    mobileToggle.style.display = "block"; // tampilkan kembali tombol
                }
            }
        });
    </script>
</body>

</html>