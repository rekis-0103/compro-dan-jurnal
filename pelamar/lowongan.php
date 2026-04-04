<?php
session_start();
require_once '../connect/koneksi.php';

// Check if user is logged in and has pelamar role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pelamar') {
    header('Location: ../login.php');
    exit();
}

// Get user data
$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role      = $_SESSION['role'];

function esc($conn, $s) { return mysqli_real_escape_string($conn, $s); }
function logActivity($conn, $actor_user_id, $action) {
    $actor_user_id = (int)$actor_user_id;
    $action = mysqli_real_escape_string($conn, $action);
    mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, action) VALUES ($actor_user_id, '$action')");
}

// Get user education info
$user_query   = mysqli_query($conn, "SELECT id_jenjang_pendidikan, id_jurusan_pendidikan FROM users WHERE user_id = $user_id");
$user_info    = mysqli_fetch_assoc($user_query);
$user_jenjang = $user_info['id_jenjang_pendidikan'];
$user_jurusan = $user_info['id_jurusan_pendidikan'];

// Lamaran masih berjalan (harus sama dengan pelamar/profile.php — termasuk 'menunggu')
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
$activeApplication    = $hasActiveApplication ? mysqli_fetch_assoc($activeApplicationQuery) : null;

// Karyawan aktif: diterima bekerja dan belum non-aktif (kolom employment_status opsional di DB lama)
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

$lowonganLocked = $hasActiveApplication || $isEmployedActive;

// Filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query  = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$whereClause = "WHERE l.hapus=0";

if ($user_jenjang) {
    $whereClause .= " AND (
        l.req_jenjang_pendidikan IS NULL 
        OR (
            l.req_jenjang_pendidikan = $user_jenjang
            AND (
                l.req_jurusan_pendidikan IS NULL 
                OR l.req_jurusan_pendidikan = " . ($user_jurusan ? $user_jurusan : "0") . "
            )
        )
    )";
} else {
    $whereClause .= " AND l.req_jenjang_pendidikan IS NULL";
}

if ($status_filter === 'open') {
    $whereClause .= " AND l.status='open'";
} elseif ($status_filter === 'closed') {
    $whereClause .= " AND l.status='closed'";
}

if (!empty($search_query)) {
    $search_escaped = mysqli_real_escape_string($conn, $search_query);
    $whereClause .= " AND l.title LIKE '%$search_escaped%'";
}

// Query dengan subquery total pendaftar
$list = mysqli_query($conn, "
    SELECT l.*,
           jenjang.nama_jenjang,
           jurusan.nama_jurusan,
           (SELECT COUNT(*) FROM applications a WHERE a.job_id = l.job_id) AS total_apply
    FROM lowongan l
    LEFT JOIN jenjang_pendidikan jenjang ON l.req_jenjang_pendidikan = jenjang.id_jenjang
    LEFT JOIN jurusan_pendidikan jurusan ON l.req_jurusan_pendidikan = jurusan.id_jurusan
    $whereClause
    ORDER BY l.status ASC, l.posted_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lowongan - PT Waindo Specterra</title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/lowongan.css">
    <style>
        /* ── Active application notice ── */
        .active-application-notice {
            background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .active-application-notice h4 {
            color: #856404; margin: 0 0 10px 0;
            display: flex; align-items: center; gap: 10px; font-size: 18px;
        }
        .active-application-notice h4 i { font-size: 24px; }
        .active-application-notice p { color: #664d03; margin: 5px 0; line-height: 1.6; }
        .active-application-notice strong { color: #523d01; }
        .application-status-badge {
            display: inline-block; padding: 6px 14px; border-radius: 20px;
            font-size: 13px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.5px; margin-top: 10px;
        }
        .status-pending              { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
        .status-seleksi-administrasi { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
        .status-lolos-administrasi   { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
        .status-tes-wawancara        { background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; }
        .view-application-btn { margin-top: 15px; }
        /* overlay pesan: dipindah ke lowongan.css (.locked-pipeline / .locked-employed) */

        /* ── Info chips ── */
        .job-info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0 6px;
        }
        .info-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 600;
            line-height: 1.3;
        }
        /* Deadline */
        .chip-deadline          { background:#fef9ec; color:#92400e; border:1px solid #fde68a; }
        .chip-deadline.urgent   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5;
                                  animation: pulse-chip 1.5s ease-in-out infinite; }
        .chip-deadline.expired  { background:#f1f5f9; color:#94a3b8; border:1px solid #e2e8f0; }
        /* Kuota */
        .chip-quota             { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; }
        .chip-quota.full        { background:#ecfdf5; color:#065f46; border:1px solid #6ee7b7; }
        /* Alasan tutup */
        .chip-close-reason      { background:#fef3c7; color:#92400e; border:1px solid #fde68a;
                                  border-radius:8px; font-weight:500; font-size:12px; }

        /* Progress bar kuota */
        .quota-bar-outer {
            width: 100%; background: #e5e7eb; border-radius: 999px;
            height: 5px; overflow: hidden; margin-bottom: 10px;
        }
        .quota-bar-inner { height:100%; border-radius:999px; transition:width .4s; }
        .quota-bar-inner.low    { background:#3b82f6; }
        .quota-bar-inner.medium { background:#f59e0b; }
        .quota-bar-inner.high   { background:#ef4444; }

        @keyframes pulse-chip {
            0%,100% { box-shadow:0 0 0 0 rgba(239,68,68,.35); }
            50%      { box-shadow:0 0 0 5px rgba(239,68,68,0); }
        }

        @media (max-width:480px) {
            .job-info-row { gap:6px; }
            .info-chip { font-size:11.5px; padding:4px 10px; }
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
                <h3>Lowongan</h3>
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
                <h1>Lowongan</h1>
                <p>Daftar lowongan yang sesuai dengan pendidikan Anda</p>
            </div>

            <?php if (!$user_jenjang): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Perhatian!</strong> Anda belum melengkapi data pendidikan.
                    Silakan <a href="profile.php" style="text-decoration:underline;font-weight:bold;">lengkapi profil Anda</a>
                    untuk melihat lowongan yang sesuai dengan kualifikasi Anda.
                </div>
            <?php endif; ?>

            <?php if ($hasActiveApplication): ?>
                <div class="active-application-notice">
                    <h4><i class="fas fa-info-circle"></i> Anda Memiliki Lamaran yang Sedang Diproses</h4>
                    <p><strong>Posisi:</strong> <?php echo htmlspecialchars($activeApplication['title']); ?></p>
                    <p>
                        <strong>Status Saat Ini:</strong>
                        <span class="application-status-badge status-<?php echo str_replace(' ', '-', $activeApplication['status']); ?>">
                            <?php echo htmlspecialchars($activeApplication['status']); ?>
                        </span>
                    </p>
                    <p><strong>Tanggal Melamar:</strong> <?php echo date('d F Y H:i', strtotime($activeApplication['applied_at'])); ?></p>
                    <p style="margin-top:15px;padding-top:15px;border-top:1px solid #ffc107;">
                        <i class="fas fa-lock"></i>
                        <strong>Perhatian:</strong> Anda tidak dapat melamar pekerjaan lain sampai lamaran ini <strong>ditolak</strong> atau <strong>diterima bekerja</strong>.
                    </p>
                    <div class="view-application-btn">
                        <a href="applications.php" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Lihat Detail Lamaran
                        </a>
                    </div>
                </div>
            <?php elseif ($isEmployedActive): ?>
                <div class="employed-notice">
                    <h4><i class="fas fa-building"></i> Anda Terdaftar sebagai Karyawan</h4>
                    <p><strong>Posisi:</strong> <?php echo htmlspecialchars($employedApplication['title']); ?></p>
                    <?php if (!empty($employedApplication['start_date'])): ?>
                        <p><strong>Tanggal mulai kerja:</strong> <?php echo date('d F Y', strtotime($employedApplication['start_date'])); ?></p>
                    <?php endif; ?>
                    <p style="margin-top:15px;padding-top:15px;border-top:1px solid #86efac;">
                        <i class="fas fa-lock"></i>
                        <strong>Halaman lowongan terkunci:</strong> Data Anda sudah masuk sebagai karyawan. Anda tidak dapat melamar lowongan lain melalui sistem ini.
                    </p>
                    <div class="view-application-btn">
                        <a href="applications.php" class="btn btn-primary">
                            <i class="fas fa-file-alt"></i> Lihat Riwayat Lamaran
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-briefcase"></i> Daftar Lowongan</h3>
                </div>
                <div class="card-body">
                    <!-- Filter -->
                    <div class="filter-section">
                        <form method="GET" action="" id="filterForm" class="filter-form">
                            <div class="filter-group search-group">
                                <label for="search-input"><i class="fas fa-search"></i> Cari Pekerjaan:</label>
                                <div class="search-input-wrapper">
                                    <input type="text" id="search-input" name="search"
                                           placeholder="Masukkan nama pekerjaan..."
                                           value="<?php echo htmlspecialchars($search_query); ?>"
                                           class="search-input"
                                           <?php echo $lowonganLocked ? 'disabled' : ''; ?>>
                                    <?php if (!empty($search_query)): ?>
                                        <button type="button" class="clear-search" onclick="clearSearch()" title="Hapus pencarian">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="filter-group">
                                <label for="status-filter"><i class="fas fa-filter"></i> Filter Status:</label>
                                <select id="status-filter" name="status"
                                        onchange="document.getElementById('filterForm').submit()"
                                        <?php echo $lowonganLocked ? 'disabled' : ''; ?>>
                                    <option value="all"    <?php echo $status_filter==='all'    ? 'selected' : ''; ?>>Semua Status</option>
                                    <option value="open"   <?php echo $status_filter==='open'   ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="closed" <?php echo $status_filter==='closed' ? 'selected' : ''; ?>>Ditutup</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-search" <?php echo $lowonganLocked ? 'disabled' : ''; ?>>
                                <i class="fas fa-search"></i> Cari
                            </button>
                        </form>

                        <?php if (!empty($search_query)): ?>
                            <div class="search-info">
                                <i class="fas fa-info-circle"></i>
                                Menampilkan hasil pencarian untuk: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong>
                                <?php $total_results = $list ? mysqli_num_rows($list) : 0;
                                      echo " ($total_results hasil ditemukan)"; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Daftar lowongan -->
                    <?php if ($list && mysqli_num_rows($list) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($list)):

                            /* ── Deadline ── */
                            $hasDeadline    = !empty($row['deadline']);
                            $now            = new DateTime();
                            $deadlineObj    = $hasDeadline ? new DateTime($row['deadline']) : null;
                            $isExpired      = $hasDeadline && $deadlineObj < $now;
                            $daysLeft       = null;
                            $deadlineUrgent = false;
                            if ($hasDeadline && !$isExpired) {
                                $daysLeft       = (int)$now->diff($deadlineObj)->days;
                                $deadlineUrgent = $daysLeft <= 3;
                            }

                            /* ── Kuota ── */
                            $hasQuota   = !empty($row['quota']) && (int)$row['quota'] > 0;
                            $quota      = $hasQuota ? (int)$row['quota'] : 0;
                            $totalApply = (int)$row['total_apply'];
                            $pct        = ($hasQuota && $quota > 0) ? min(100, round($totalApply / $quota * 100)) : 0;
                            $barClass   = $pct >= 90 ? 'high' : ($pct >= 60 ? 'medium' : 'low');
                            $quotaFull  = $hasQuota && $totalApply >= $quota;

                            $showInfoRow = $hasDeadline || $hasQuota || !empty($row['close_reason']);
                        ?>
                        <?php
                        $lockClass = '';
                        if ($lowonganLocked) {
                            $lockClass = 'disabled ' . ($hasActiveApplication ? 'locked-pipeline' : 'locked-employed');
                        }
                        ?>
                        <div class="job-item <?php echo $row['status']==='closed' ? 'job-closed' : ''; ?> <?php echo $lockClass; ?>">

                            <!-- Header: judul + status badge -->
                            <div class="job-header">
                                <div class="job-title"><?php echo htmlspecialchars($row['title']); ?></div>
                                <div class="job-status">
                                    <?php if ($row['status']==='open'): ?>
                                        <span class="status-badge status-open"><i class="fas fa-check-circle"></i> Aktif</span>
                                    <?php else: ?>
                                        <span class="status-badge status-closed"><i class="fas fa-times-circle"></i> Ditutup</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Info chips: deadline & kuota -->
                            <?php if ($showInfoRow): ?>
                            <div class="job-info-row">

                                <?php if ($hasDeadline): ?>
                                    <?php if ($isExpired): ?>
                                        <span class="info-chip chip-deadline expired">
                                            <i class="fas fa-calendar-times"></i> Pendaftaran telah berakhir
                                        </span>
                                    <?php elseif ($daysLeft === 0): ?>
                                        <span class="info-chip chip-deadline urgent">
                                            <i class="fas fa-hourglass-end"></i> Berakhir hari ini!
                                        </span>
                                    <?php elseif ($deadlineUrgent): ?>
                                        <span class="info-chip chip-deadline urgent">
                                            <i class="fas fa-clock"></i>
                                            Sisa <?php echo $daysLeft; ?> hari &mdash; <?php echo date('d M Y', strtotime($row['deadline'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="info-chip chip-deadline">
                                            <i class="fas fa-calendar-alt"></i>
                                            Batas daftar: <?php echo date('d M Y', strtotime($row['deadline'])); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($hasQuota): ?>
                                    <span class="info-chip chip-quota <?php echo $quotaFull ? 'full' : ''; ?>">
                                        <i class="fas fa-users"></i>
                                        <?php if ($quotaFull): ?>
                                            Kuota penuh &mdash; <?php echo $totalApply; ?>/<?php echo $quota; ?> pendaftar
                                        <?php else: ?>
                                            Kuota: <?php echo $totalApply; ?>/<?php echo $quota; ?> pendaftar
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($row['close_reason'])): ?>
                                    <span class="info-chip chip-close-reason">
                                        <i class="fas fa-info-circle"></i>
                                        <?php echo htmlspecialchars($row['close_reason']); ?>
                                    </span>
                                <?php endif; ?>

                            </div>

                            <!-- Progress bar kuota -->
                            <?php if ($hasQuota): ?>
                                <div class="quota-bar-outer" title="Terisi <?php echo $pct; ?>%">
                                    <div class="quota-bar-inner <?php echo $barClass; ?>" style="width:<?php echo $pct; ?>%"></div>
                                </div>
                            <?php endif; ?>
                            <?php endif; // showInfoRow ?>

                            <!-- Meta: lokasi, gaji, tanggal, syarat -->
                            <div class="job-meta">
                                <span><i class="fas fa-map-marker-alt" style="color:#3b82f6"></i> Lokasi: <?php echo htmlspecialchars($row['location'] ?: '-'); ?></span>
                                <span><i class="fas fa-money-bill-wave" style="color:#10b981"></i> Gaji: <?php echo htmlspecialchars($row['salary_range'] ?: '-'); ?></span>
                                <span><i class="fas fa-calendar-plus" style="color:#8b5cf6"></i> Diposting: <?php echo date('d M Y', strtotime($row['posted_at'])); ?></span>
                                <?php if ($row['nama_jenjang']): ?>
                                    <span>
                                        <i class="fas fa-graduation-cap" style="color:#f59e0b"></i> Pendidikan Terakhir:
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($row['nama_jenjang']);
                                                  if ($row['nama_jurusan']) echo ' – ' . htmlspecialchars($row['nama_jurusan']); ?>
                                        </span>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="job-desc-preview">
                                <?php $desc = strip_tags($row['description']);
                                      echo htmlspecialchars(strlen($desc) > 200 ? substr($desc,0,200).'...' : $desc); ?>
                            </div>

                            <div class="job-actions">
                                <a href="detail-lowongan.php?id=<?php echo $row['job_id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-jobs">
                            <i class="fas fa-briefcase"></i>
                            <p>
                                <?php if (!$user_jenjang): ?>
                                    Silakan lengkapi data pendidikan Anda terlebih dahulu untuk melihat lowongan yang tersedia.
                                <?php elseif (!empty($search_query)): ?>
                                    Tidak ada lowongan yang cocok dengan pencarian "<?php echo htmlspecialchars($search_query); ?>".
                                <?php elseif ($status_filter === 'open'): ?>
                                    Belum ada lowongan aktif yang sesuai dengan pendidikan Anda saat ini.
                                <?php elseif ($status_filter === 'closed'): ?>
                                    Belum ada lowongan yang ditutup.
                                <?php else: ?>
                                    Belum ada lowongan yang sesuai dengan pendidikan Anda saat ini.
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($search_query) || $status_filter !== 'all'): ?>
                                <a href="lowongan.php" class="btn btn-primary btn-sm" style="margin-top:15px;">
                                    <i class="fas fa-redo"></i> Tampilkan Semua Lowongan
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/navbar.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        function clearSearch() {
            document.getElementById('search-input').value = '';
            document.getElementById('filterForm').submit();
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