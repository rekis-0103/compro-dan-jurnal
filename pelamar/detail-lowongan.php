<?php
session_start();
require_once '../connect/koneksi.php';

// Check if user is logged in and has pelamar role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pelamar') {
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

// Get user profile data including education
$user_query = mysqli_query($conn, "SELECT u.*, 
    jenjang.nama_jenjang, 
    jurusan.nama_jurusan 
    FROM users u
    LEFT JOIN jenjang_pendidikan jenjang ON u.id_jenjang_pendidikan = jenjang.id_jenjang
    LEFT JOIN jurusan_pendidikan jurusan ON u.id_jurusan_pendidikan = jurusan.id_jurusan
    WHERE u.user_id = $user_id");
$user_profile = mysqli_fetch_assoc($user_query);

// Lamaran masih berjalan (sama dengan pelamar/profile.php — termasuk 'menunggu')
$active_status_sql = "'menunggu', 'pending', 'seleksi administrasi', 'lolos administrasi', 'tes & wawancara'";

$activeApplicationQuery = mysqli_query($conn, "
    SELECT a.*, l.title 
    FROM applications a 
    JOIN lowongan l ON a.job_id = l.job_id 
    WHERE a.user_id = $user_id 
    AND a.status IN ($active_status_sql)
    ORDER BY a.applied_at DESC 
    LIMIT 1
");
$hasActiveApplication = mysqli_num_rows($activeApplicationQuery) > 0;
$activeApplication = $hasActiveApplication ? mysqli_fetch_assoc($activeApplicationQuery) : null;

$employedQuery = mysqli_query($conn, "
    SELECT a.*, l.title 
    FROM applications a 
    JOIN lowongan l ON a.job_id = l.job_id 
    WHERE a.user_id = $user_id 
    AND a.status = 'diterima bekerja'
    AND (a.employment_status IS NULL OR a.employment_status = 'aktif')
    ORDER BY a.applied_at DESC 
    LIMIT 1
");
$isEmployedActive = $employedQuery && mysqli_num_rows($employedQuery) > 0;
$employedApplication = $isEmployedActive ? mysqli_fetch_assoc($employedQuery) : null;

// Get job ID from URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$job_id) {
    header('Location: lowongan.php');
    exit();
}

// Get job details
$query = "SELECT l.*, 
    jenjang.nama_jenjang as req_jenjang_nama, 
    jurusan.nama_jurusan as req_jurusan_nama
    FROM lowongan l
    LEFT JOIN jenjang_pendidikan jenjang ON l.req_jenjang_pendidikan = jenjang.id_jenjang
    LEFT JOIN jurusan_pendidikan jurusan ON l.req_jurusan_pendidikan = jurusan.id_jurusan
    WHERE l.job_id = $job_id AND l.hapus = 0";
$result = mysqli_query($conn, $query);
$job = mysqli_fetch_assoc($result);

if (!$job) {
    header('Location: lowongan.php');
    exit();
}

// Check if user has already applied for THIS specific job
$checkApplication = mysqli_query($conn, "SELECT * FROM applications WHERE job_id = $job_id AND user_id = $user_id");
$hasApplied = mysqli_num_rows($checkApplication) > 0;

// Check if user profile is complete
$profileComplete = !empty($user_profile['no_telepon']) && 
                   !empty($user_profile['id_jenjang_pendidikan']) && 
                   !empty($user_profile['cv_filename']);

// Check if user meets job requirements
$meetsRequirements = true;
if ($job['req_jenjang_pendidikan']) {
    if ($user_profile['id_jenjang_pendidikan'] != $job['req_jenjang_pendidikan']) {
        $meetsRequirements = false;
    } elseif ($job['req_jurusan_pendidikan']) {
        if ($user_profile['id_jurusan_pendidikan'] != $job['req_jurusan_pendidikan']) {
            $meetsRequirements = false;
        }
    }
}

// Handle apply action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply') {
    // Check if job is still open
    if ($job['status'] !== 'open') {
        $error = 'Lowongan sudah ditutup, tidak dapat mengirim lamaran.';
        logActivity($conn, $user_id, 'Gagal kirim lamaran (lowongan sudah ditutup)');
    } elseif ($hasApplied) {
        $error = 'Anda sudah mengirim lamaran untuk lowongan ini sebelumnya.';
        logActivity($conn, $user_id, 'Gagal kirim lamaran (sudah apply)');
    } elseif ($isEmployedActive) {
        $error = 'Anda sudah terdaftar sebagai karyawan. Tidak dapat mengirim lamaran baru melalui sistem ini.';
        logActivity($conn, $user_id, 'Gagal kirim lamaran (sudah karyawan aktif)');
    } elseif ($hasActiveApplication) {
        $error = 'Anda sudah memiliki lamaran yang sedang diproses. Harap tunggu hingga lamaran tersebut selesai (ditolak atau diterima) sebelum melamar pekerjaan lain.';
        logActivity($conn, $user_id, 'Gagal kirim lamaran (sudah ada lamaran aktif)');
    } elseif (!$profileComplete) {
        $error = 'Profil Anda belum lengkap. Silakan lengkapi data di halaman profil terlebih dahulu.';
        logActivity($conn, $user_id, 'Gagal kirim lamaran (profil tidak lengkap)');
    } elseif (!$meetsRequirements) {
        $error = 'Anda tidak memenuhi persyaratan pendidikan untuk lowongan ini.';
        logActivity($conn, $user_id, 'Gagal kirim lamaran (tidak memenuhi syarat pendidikan)');
    } else {
        // Get data from user profile
        $noTelepon = $user_profile['no_telepon'];
        $jenjangId = $user_profile['id_jenjang_pendidikan'];
        $jurusanId = $user_profile['id_jurusan_pendidikan'];
        $cvFilename = $user_profile['cv_filename'];
        
        // Escape inputs
        $noTeleponEsc = mysqli_real_escape_string($conn, $noTelepon);
        $cvEsc = mysqli_real_escape_string($conn, $cvFilename);
        
        $q = "INSERT INTO applications (
                job_id, 
                user_id, 
                no_telepon, 
                id_jenjang_pendidikan, 
                id_jurusan_pendidikan, 
                cv, 
                status, 
                applied_at
            ) VALUES (
                $job_id, 
                $user_id, 
                '$noTeleponEsc', 
                " . ($jenjangId ? $jenjangId : 'NULL') . ", 
                " . ($jurusanId ? $jurusanId : 'NULL') . ", 
                '$cvEsc', 
                'pending', 
                NOW()
            )";
        
        if (mysqli_query($conn, $q)) {
            $success = 'Lamaran berhasil dikirim! Anda tidak dapat melamar pekerjaan lain hingga lamaran ini diproses.';
            logActivity($conn, $user_id, "Kirim lamaran (job #$job_id)");
            $hasApplied = true;
            $hasActiveApplication = true;
            
            // Refresh to show the updated state
            header("Location: detail-lowongan.php?id=$job_id&success=1");
            exit();
        } else {
            $error = 'Gagal mengirim lamaran: ' . mysqli_error($conn);
            logActivity($conn, $user_id, 'Gagal kirim lamaran (database error)');
        }
    }
}

// Check if redirected with success parameter
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = 'Lamaran berhasil dikirim! Anda tidak dapat melamar pekerjaan lain hingga lamaran ini diproses.';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Lowongan - <?php echo htmlspecialchars($job['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/lowongan.css">
    <style>
        /* Profile Summary Section */
        .profile-summary {
            background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
            border: 2px solid #3b82f6;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .profile-summary h4 {
            color: #1e40af;
            font-size: 18px;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-item {
            background: white;
            padding: 12px 15px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .summary-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-value {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
        }

        .summary-value .btn-link {
            color: #3b82f6;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            transition: color 0.2s;
        }

        .summary-value .btn-link:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .profile-note {
            background: white;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            color: #475569;
            margin: 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .profile-note i {
            color: #3b82f6;
            font-size: 16px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .profile-note a {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: underline;
        }

        .profile-note a:hover {
            color: #2563eb;
        }

        .active-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #fbbf24;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .active-warning h4 {
            color: #92400e;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }

        .active-warning p {
            color: #78350f;
            margin: 5px 0;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Detail Lowongan</h3>
                <p>Selamat datang, <?php echo htmlspecialchars($full_name); ?></p>
            </div>

            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profil</a></li>
                <li><a href="lowongan.php" class="active"><i class="fas fa-briefcase"></i> Lihat Lowongan</a></li>
                <li><a href="applications.php"><i class="fas fa-file-alt"></i> Lamaran Saya</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Beranda</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="header-with-back">
                    <a href="lowongan.php" class="back-button">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Lowongan
                    </a>
                    <h1><?php echo htmlspecialchars($job['title']); ?></h1>
                    <p>Detail lengkap lowongan pekerjaan</p>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="job-detail-container">
                <div class="job-detail-card">
                    <div class="job-detail-header">
                        <div class="job-title-section">
                            <h2><?php echo htmlspecialchars($job['title']); ?></h2>
                            <div class="job-status-large">
                                <?php if ($job['status'] === 'open'): ?>
                                    <span class="status-badge status-open">
                                        <i class="fas fa-check-circle"></i> Lowongan Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-closed">
                                        <i class="fas fa-times-circle"></i> Lowongan Ditutup
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="job-detail-info">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-map-marker-alt"></i> Lokasi
                                </div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($job['location'] ?: 'Tidak disebutkan'); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-money-bill-wave"></i> Gaji
                                </div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($job['salary_range'] ?: 'Nego'); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-graduation-cap"></i> Syarat Pendidikan
                                </div>
                                <div class="info-value">
                                    <?php 
                                    if ($job['req_jenjang_nama']) {
                                        echo htmlspecialchars($job['req_jenjang_nama']);
                                        if ($job['req_jurusan_nama']) {
                                            echo ' - ' . htmlspecialchars($job['req_jurusan_nama']);
                                        }
                                    } else {
                                        echo 'Tidak ada syarat khusus';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-calendar-alt"></i> Tanggal Posting
                                </div>
                                <div class="info-value">
                                    <?php echo date('d F Y', strtotime($job['posted_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="job-detail-section">
                        <h3><i class="fas fa-file-alt"></i> Deskripsi Pekerjaan</h3>
                        <div class="job-description">
                            <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                        </div>
                    </div>

                    <?php if (!empty($job['requirements'])): ?>
                    <div class="job-detail-section">
                        <h3><i class="fas fa-list-check"></i> Persyaratan</h3>
                        <div class="job-requirements">
                            <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Application Section -->
                    <div class="application-section">
                        <?php if ($hasApplied): ?>
                            <div class="already-applied">
                                <div class="applied-notice">
                                    <i class="fas fa-check-circle"></i>
                                    <div class="applied-text">
                                        <h4>Lamaran Sudah Dikirim</h4>
                                        <p>Anda sudah mengirim lamaran untuk lowongan ini. Silakan cek status lamaran di menu "Lamaran Saya".</p>
                                    </div>
                                </div>
                                <div class="applied-actions">
                                    <a href="applications.php" class="btn btn-info">
                                        <i class="fas fa-file-alt"></i> Lihat Lamaran Saya
                                    </a>
                                </div>
                            </div>
                        <?php elseif ($job['status'] === 'open'): ?>
                            <?php if ($isEmployedActive && !$hasApplied): ?>
                                <div class="employed-block-notice">
                                    <h4><i class="fas fa-lock"></i> Lowongan tidak tersedia</h4>
                                    <p>Anda sudah <strong>diterima bekerja</strong> pada posisi <strong><?php echo htmlspecialchars($employedApplication['title']); ?></strong>. Data Anda telah masuk ke data karyawan; Anda tidak dapat melamar lowongan lain.</p>
                                    <a href="applications.php" class="btn btn-primary"><i class="fas fa-file-alt"></i> Lamaran Saya</a>
                                </div>
                            <?php elseif ($hasActiveApplication && $activeApplication['job_id'] != $job_id): ?>
                                <div class="active-warning">
                                    <h4>
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Anda Sudah Memiliki Lamaran Aktif
                                    </h4>
                                    <p>
                                        Anda tidak dapat melamar pekerjaan ini karena masih memiliki lamaran yang sedang diproses untuk posisi:
                                    </p>
                                    <p>
                                        <strong><?php echo htmlspecialchars($activeApplication['title']); ?></strong>
                                    </p>
                                    <p>
                                        <strong>Status:</strong> <?php echo htmlspecialchars($activeApplication['status']); ?>
                                    </p>
                                    <p style="margin-top: 15px;">
                                        Silakan tunggu hingga lamaran Anda <strong>ditolak</strong> atau <strong>diterima bekerja</strong> sebelum melamar pekerjaan lain.
                                    </p>
                                    <div style="margin-top: 15px;">
                                        <a href="applications.php" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> Lihat Lamaran Saya
                                        </a>
                                    </div>
                                </div>
                            <?php elseif (!$profileComplete): ?>
                                <div class="job-closed-section">
                                    <div class="closed-notice" style="background: #fff3cd; color: #856404; border-color: #ffeaa7;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <div class="closed-text">
                                            <h4>Profil Belum Lengkap</h4>
                                            <p>Silakan lengkapi data profil Anda (No. Telepon, Pendidikan, dan CV) terlebih dahulu sebelum melamar.</p>
                                        </div>
                                    </div>
                                    <div class="applied-actions" style="text-align: center; margin-top: 15px;">
                                        <a href="profile.php" class="btn btn-primary">
                                            <i class="fas fa-user-edit"></i> Lengkapi Profil
                                        </a>
                                    </div>
                                </div>
                            <?php elseif (!$meetsRequirements): ?>
                                <div class="job-closed-section">
                                    <div class="closed-notice">
                                        <i class="fas fa-times-circle"></i>
                                        <div class="closed-text">
                                            <h4>Tidak Memenuhi Syarat</h4>
                                            <p>Maaf, pendidikan Anda tidak sesuai dengan persyaratan lowongan ini.</p>
                                            <p><strong>Syarat:</strong> <?php echo htmlspecialchars($job['req_jenjang_nama']); ?>
                                            <?php if ($job['req_jurusan_nama']): ?>
                                                - <?php echo htmlspecialchars($job['req_jurusan_nama']); ?>
                                            <?php endif; ?>
                                            </p>
                                            <p><strong>Pendidikan Anda:</strong> <?php echo htmlspecialchars($user_profile['nama_jenjang']); ?>
                                            <?php if ($user_profile['nama_jurusan']): ?>
                                                - <?php echo htmlspecialchars($user_profile['nama_jurusan']); ?>
                                            <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="apply-section">
                                    <div class="apply-header">
                                        <h3><i class="fas fa-paper-plane"></i> Kirim Lamaran</h3>
                                    </div>
                                    
                                    <div class="apply-form-container" id="applyFormContainer">
                                        <div class="profile-summary">
                                            <h4><i class="fas fa-user-check"></i> Data Lamaran Anda</h4>
                                            <div class="summary-grid">
                                                <div class="summary-item">
                                                    <span class="summary-label">Nama:</span>
                                                    <span class="summary-value"><?php echo htmlspecialchars($user_profile['full_name']); ?></span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="summary-label">No. Telepon:</span>
                                                    <span class="summary-value"><?php echo htmlspecialchars($user_profile['no_telepon']); ?></span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="summary-label">Pendidikan:</span>
                                                    <span class="summary-value">
                                                        <?php 
                                                        echo htmlspecialchars($user_profile['nama_jenjang']);
                                                        if ($user_profile['nama_jurusan']) {
                                                            echo ' - ' . htmlspecialchars($user_profile['nama_jurusan']);
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="summary-label">CV:</span>
                                                    <span class="summary-value">
                                                        <a href="cv/<?php echo htmlspecialchars($user_profile['cv_filename']); ?>" target="_blank" class="btn-link">
                                                            <i class="fas fa-file-pdf"></i> Lihat CV
                                                        </a>
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="profile-note">
                                                <i class="fas fa-info-circle"></i>
                                                Data di atas akan digunakan untuk lamaran Anda. 
                                                Jika ingin mengubah, silakan <a href="profile.php">update profil</a> terlebih dahulu.
                                            </p>
                                            <p class="profile-note" style="background: #fef3c7; border-color: #fbbf24; color: #92400e; margin-top: 10px;">
                                                <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                                                <strong>Perhatian:</strong> Setelah mengirim lamaran ini, Anda tidak dapat melamar pekerjaan lain hingga lamaran ini ditolak atau diterima bekerja.
                                            </p>
                                        </div>
                                        
                                        <form method="POST" class="apply-form-detail" id="applicationForm" 
                                              onsubmit="return confirm('Setelah mengirim lamaran, Anda tidak dapat melamar pekerjaan lain hingga lamaran ini selesai diproses (ditolak atau diterima). Yakin ingin melanjutkan?')">
                                            <input type="hidden" name="action" value="apply">
                                            
                                            <div class="form-actions">
                                                <button type="submit" class="btn btn-primary btn-lg">
                                                    <i class="fas fa-paper-plane"></i> Kirim Lamaran
                                                </button>
                                                <a href="lowongan.php" class="btn btn-secondary">
                                                    <i class="fas fa-times"></i> Batal
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="job-closed-section">
                                <div class="closed-notice">
                                    <i class="fas fa-lock"></i>
                                    <div class="closed-text">
                                        <h4>Lowongan Ditutup</h4>
                                        <p>Lowongan ini sudah ditutup dan tidak menerima lamaran baru.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/navbar.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-toggle');
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !mobileToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
</body>

</html>