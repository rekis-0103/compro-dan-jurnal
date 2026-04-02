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

// Get candidate ID (application_id)
$candidate_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$candidate_id) { header('Location: candidates.php'); exit(); }

// ── Handle POST actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $application_id = (int)$_POST['application_id'];

    // ── Catatan internal: tambah (berbasis kandidat/user, bukan lamaran) ──
    if ($_POST['action'] === 'add_note') {
        $note_text         = trim(esc($conn, $_POST['note_text']));
        $candidate_user_id = (int)$_POST['candidate_user_id'];

        if ($note_text !== '' && $candidate_user_id > 0) {
            // Simpan dengan candidate_user_id (lintas lamaran) dan application_id sebagai konteks historis
            $q = "INSERT INTO hrd_notes (candidate_user_id, application_id, hrd_user_id, note)
                  VALUES ($candidate_user_id, $application_id, $user_id, '$note_text')";
            if (mysqli_query($conn, $q)) {
                $success = 'Catatan berhasil ditambahkan';
                logActivity($conn, $user_id, "HRD: tambah catatan untuk kandidat #$candidate_user_id (lamaran #$application_id)");
            } else {
                $error = 'Gagal menyimpan catatan: ' . mysqli_error($conn);
            }
        } else {
            $error = 'Catatan tidak boleh kosong';
        }

    // ── Catatan internal: hapus (hanya pemilik) ──
    } elseif ($_POST['action'] === 'delete_note') {
        $note_id = (int)$_POST['note_id'];
        $q = "DELETE FROM hrd_notes WHERE note_id=$note_id AND hrd_user_id=$user_id";
        if (mysqli_query($conn, $q) && mysqli_affected_rows($conn) > 0) {
            $success = 'Catatan dihapus';
            logActivity($conn, $user_id, "HRD: hapus catatan internal #$note_id");
        } else {
            $error = 'Gagal menghapus catatan (hanya pemilik catatan yang bisa menghapus)';
        }

    } elseif ($_POST['action'] === 'move_to_interview') {
        $interview_date = !empty($_POST['interview_date']) ? esc($conn, $_POST['interview_date']) : NULL;
        if ($interview_date) {
            if (strtotime($interview_date) < time()) {
                $error = 'Tanggal dan waktu wawancara tidak boleh mundur dari sekarang!';
            } else {
                $q = "UPDATE applications SET status='tes & wawancara', updated_at=NOW(), interview_date='$interview_date' WHERE application_id=$application_id";
                if (mysqli_query($conn, $q)) {
                    $info = mysqli_query($conn, "SELECT a.*, u.email, u.full_name, l.title FROM applications a JOIN users u ON a.user_id=u.user_id JOIN lowongan l ON a.job_id=l.job_id WHERE a.application_id=$application_id");
                    if ($info) {
                        $row = mysqli_fetch_assoc($info);
                        $msg = "Halo " . $row['full_name'] . ",\n\nSelamat! Anda dijadwalkan untuk mengikuti Tes & Wawancara untuk posisi " . $row['title'] . ".\nJadwal: " . date('d F Y, H:i', strtotime($interview_date)) . " WIB\n\nMohon hadir tepat waktu.\n\nTerima kasih.\nHRD";
                        sendEmail($row['email'], 'Jadwal Wawancara - ' . $row['title'], $msg);
                    }
                    $success = 'Jadwal wawancara berhasil diset';
                    logActivity($conn, $user_id, "HRD: set interview application #$application_id");
                } else { $error = 'Gagal memperbarui jadwal wawancara'; }
            }
        } else { $error = 'Tanggal wawancara wajib diisi'; }

    } elseif ($_POST['action'] === 'accept_hire') {
        $reason     = esc($conn, $_POST['reason']);
        $start_date = esc($conn, $_POST['start_date']);
        if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
            $error = 'Tanggal mulai bekerja tidak boleh mundur dari hari ini!';
        } else {
            $q = "UPDATE applications SET status='diterima bekerja', updated_at=NOW(), reason='$reason', start_date='$start_date' WHERE application_id=$application_id";
            if (mysqli_query($conn, $q)) {
                $info = mysqli_query($conn, "SELECT a.*, u.email, u.full_name, l.title FROM applications a JOIN users u ON a.user_id=u.user_id JOIN lowongan l ON a.job_id=l.job_id WHERE a.application_id=$application_id");
                if ($info) {
                    $row = mysqli_fetch_assoc($info);
                    $msg = "Halo " . $row['full_name'] . ",\n\nSelamat! Anda DITERIMA BEKERJA pada posisi " . $row['title'] . ".\nTanggal Mulai: " . date('d F Y', strtotime($start_date)) . "\n" . (!empty($reason) ? "Catatan: $reason\n" : "") . "\nSampai jumpa di hari pertama.\nHRD";
                    sendEmail($row['email'], 'Selamat Bergabung - ' . $row['title'], $msg);
                    logActivity($conn, $user_id, "HRD: terima bekerja application #$application_id (" . $row['title'] . ")");
                }
                $success = 'Kandidat diterima bekerja';
            } else { $error = 'Gagal memperbarui status'; }
        }

    } elseif ($_POST['action'] === 'reject_after_interview') {
        $reason = esc($conn, $_POST['reason']);
        $q = "UPDATE applications SET status='ditolak tes & wawancara', updated_at=NOW(), reason='$reason' WHERE application_id=$application_id";
        if (mysqli_query($conn, $q)) {
            $info = mysqli_query($conn, "SELECT a.*, u.email, u.full_name, l.title FROM applications a JOIN users u ON a.user_id=u.user_id JOIN lowongan l ON a.job_id=l.job_id WHERE a.application_id=$application_id");
            if ($info) {
                $row = mysqli_fetch_assoc($info);
                $msg = "Halo " . $row['full_name'] . ",\n\nTerima kasih telah mengikuti proses wawancara. Mohon maaf Anda BELUM DITERIMA untuk posisi " . $row['title'] . ".\nAlasan: $reason\n\nSemoga sukses di kesempatan berikutnya.\nHRD";
                sendEmail($row['email'], 'Hasil Wawancara - ' . $row['title'], $msg);
                logActivity($conn, $user_id, "HRD: tolak setelah interview application #$application_id (" . $row['title'] . ")");
            }
            $success = 'Kandidat ditolak';
        } else { $error = 'Gagal memperbarui status'; }

    } elseif ($_POST['action'] === 'reject_before_interview') {
        $reason = esc($conn, $_POST['reason']);
        $q = "UPDATE applications SET status='ditolak administrasi', updated_at=NOW(), reason='$reason' WHERE application_id=$application_id";
        if (mysqli_query($conn, $q)) {
            $info = mysqli_query($conn, "SELECT a.*, u.email, u.full_name, l.title FROM applications a JOIN users u ON a.user_id=u.user_id JOIN lowongan l ON a.job_id=l.job_id WHERE a.application_id=$application_id");
            if ($info) {
                $row = mysqli_fetch_assoc($info);
                $msg = "Halo " . $row['full_name'] . ",\n\nTerima kasih atas minat Anda. Mohon maaf lamaran Anda DITOLAK untuk posisi " . $row['title'] . ".\nAlasan: $reason\n\nSemoga sukses di kesempatan berikutnya.\nHRD";
                sendEmail($row['email'], 'Hasil Seleksi - ' . $row['title'], $msg);
                logActivity($conn, $user_id, "HRD: tolak kandidat application #$application_id (" . $row['title'] . ")");
            }
            $success = 'Kandidat ditolak';
        } else { $error = 'Gagal memperbarui status'; }
    }
}

// Fetch candidate details (application saat ini)
$detail = mysqli_query($conn, "
    SELECT a.*, u.full_name, u.email, u.user_id AS candidate_user_id,
           l.title, jenjang.nama_jenjang, jurusan.nama_jurusan
    FROM applications a
    JOIN users u ON a.user_id = u.user_id
    JOIN lowongan l ON a.job_id = l.job_id
    LEFT JOIN jenjang_pendidikan jenjang ON a.id_jenjang_pendidikan = jenjang.id_jenjang
    LEFT JOIN jurusan_pendidikan jurusan ON a.id_jurusan_pendidikan = jurusan.id_jurusan
    WHERE a.application_id = $candidate_id
");
if ($detail && mysqli_num_rows($detail) > 0) {
    $candidate = mysqli_fetch_assoc($detail);
} else {
    header('Location: candidates.php'); exit();
}

$cuid = (int)$candidate['candidate_user_id'];

// ── Ambil semua catatan internal untuk kandidat ini (lintas semua lamaran) ──
$notes_result = mysqli_query($conn, "
    SELECT n.*,
           u.full_name  AS hrd_name,
           u.username   AS hrd_username,
           l.title      AS note_job_title
    FROM hrd_notes n
    JOIN users u ON n.hrd_user_id = u.user_id
    LEFT JOIN applications a ON n.application_id = a.application_id
    LEFT JOIN lowongan l     ON a.job_id = l.job_id
    WHERE n.candidate_user_id = $cuid
    ORDER BY n.created_at DESC
");
$notes = [];
if ($notes_result) {
    while ($n = mysqli_fetch_assoc($notes_result)) $notes[] = $n;
}

// ── Riwayat lamaran lain dari kandidat yang sama (untuk konteks) ──
$history_result = mysqli_query($conn, "
    SELECT a.application_id, a.status, a.applied_at, l.title
    FROM applications a
    JOIN lowongan l ON a.job_id = l.job_id
    WHERE a.user_id = $cuid AND a.application_id != $candidate_id
    ORDER BY a.applied_at DESC
    LIMIT 5
");
$other_apps = [];
if ($history_result) {
    while ($h = mysqli_fetch_assoc($history_result)) $other_apps[] = $h;
}

$min_datetime = date('Y-m-d\TH:i');
$min_date     = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kandidat - <?php echo htmlspecialchars($candidate['full_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/applications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ══════════════════════════════════════
           CATATAN INTERNAL HRD
        ══════════════════════════════════════ */
        .notes-section { margin-top: 0; }

        .notes-header {
            display: flex; align-items: center;
            justify-content: space-between; flex-wrap: wrap;
            gap: 10px; margin-bottom: 18px;
        }
        .notes-header h4 {
            margin: 0; color: #1f2937; font-size: 16px; font-weight: 600;
            display: flex; align-items: center; gap: 8px;
        }
        .notes-header h4 i { color: #7c3aed; }
        .notes-count {
            background: #ede9fe; color: #5b21b6;
            padding: 3px 10px; border-radius: 999px;
            font-size: 12px; font-weight: 700;
        }

        /* Info scope */
        .notes-scope-info {
            background: #fef9c3; border: 1px solid #fde68a;
            border-radius: 8px; padding: 10px 14px;
            font-size: 12.5px; color: #78350f;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 16px;
        }
        .notes-scope-info i { color: #d97706; flex-shrink: 0; }

        /* Riwayat lamaran lain */
        .other-apps-strip {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 8px; padding: 10px 14px;
            font-size: 12.5px; color: #475569;
            margin-bottom: 16px;
        }
        .other-apps-strip strong { color: #334155; }
        .other-apps-strip ul {
            margin: 6px 0 0 0; padding-left: 18px; display: flex; flex-wrap: wrap; gap: 6px; list-style: none; padding: 0;
        }
        .other-app-chip {
            display: inline-flex; align-items: center; gap: 5px;
            background: white; border: 1px solid #cbd5e1;
            border-radius: 6px; padding: 4px 10px;
            font-size: 11.5px; color: #334155;
        }
        .other-app-chip i { color: #94a3b8; font-size: 10px; }
        .other-app-chip .chip-status {
            font-size: 10px; font-weight: 700; padding: 1px 6px;
            border-radius: 4px; background: #e2e8f0; color: #475569;
        }

        /* Form catatan */
        .note-form {
            background: #fafafa; border: 2px dashed #c4b5fd;
            border-radius: 12px; padding: 18px; margin-bottom: 20px;
            transition: border-color .2s;
        }
        .note-form:focus-within { border-color: #7c3aed; background: #fdf4ff; }
        .note-form label {
            display: flex; align-items: center; gap: 7px;
            font-weight: 600; color: #5b21b6; font-size: 13.5px; margin-bottom: 10px;
        }
        .note-form label i { font-size: 14px; }
        .note-textarea {
            width: 100%; min-height: 90px; padding: 12px 14px;
            border: 1.5px solid #ddd6fe; border-radius: 8px;
            font-size: 14px; font-family: inherit; resize: vertical;
            transition: border-color .2s, box-shadow .2s;
            box-sizing: border-box; color: #1f2937; background: white;
        }
        .note-textarea:focus {
            outline: none; border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124,58,237,.12);
        }
        .note-textarea::placeholder { color: #a78bfa; font-style: italic; }
        .note-form-actions { display: flex; justify-content: flex-end; margin-top: 10px; }
        .btn-add-note {
            background: linear-gradient(135deg, #7c3aed, #6d28d9); color: white;
            border: none; padding: 9px 20px; border-radius: 8px;
            font-size: 13.5px; font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center; gap: 7px; transition: all .2s;
        }
        .btn-add-note:hover {
            background: linear-gradient(135deg, #6d28d9, #5b21b6);
            transform: translateY(-1px); box-shadow: 0 4px 12px rgba(124,58,237,.3);
        }

        /* Daftar catatan */
        .notes-list { display: flex; flex-direction: column; gap: 14px; }
        .note-item {
            background: white; border: 1px solid #ede9fe;
            border-left: 4px solid #7c3aed; border-radius: 10px;
            padding: 16px 18px; position: relative;
            animation: noteIn .3s ease;
        }
        .note-item.own-note {
            border-left-color: #0891b2; background: #f0f9ff; border-color: #bae6fd;
        }
        @keyframes noteIn {
            from { opacity:0; transform:translateY(8px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .note-meta {
            display: flex; align-items: center;
            justify-content: space-between; flex-wrap: wrap;
            gap: 8px; margin-bottom: 10px;
        }
        .note-author { display: flex; align-items: center; gap: 8px; }
        .note-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: white; flex-shrink: 0;
        }
        .note-avatar.own   { background: linear-gradient(135deg, #0891b2, #0e7490); }
        .note-avatar.other { background: linear-gradient(135deg, #7c3aed, #5b21b6); }
        .note-author-info  { display: flex; flex-direction: column; gap: 1px; }
        .note-author-name  {
            font-weight: 700; color: #1f2937; font-size: 13.5px;
            display: flex; align-items: center; gap: 6px;
        }
        .badge-own-note {
            background: #dbeafe; color: #1e40af;
            padding: 1px 8px; border-radius: 999px;
            font-size: 10px; font-weight: 700; letter-spacing: .3px;
        }
        .note-datetime { font-size: 11.5px; color: #94a3b8; display: flex; align-items: center; gap: 4px; }

        /* Tag konteks lamaran dari mana catatan berasal */
        .note-job-context {
            display: inline-flex; align-items: center; gap: 5px;
            background: #f1f5f9; border: 1px solid #e2e8f0;
            border-radius: 6px; padding: 3px 9px;
            font-size: 11px; color: #475569; font-weight: 500;
            margin-top: 4px;
        }
        .note-job-context i { color: #94a3b8; font-size: 10px; }
        .note-job-context.current-job {
            background: #eff6ff; border-color: #bfdbfe; color: #1e40af;
        }
        .note-job-context.current-job i { color: #3b82f6; }

        .btn-delete-note {
            background: transparent; border: 1.5px solid #fca5a5; color: #dc2626;
            border-radius: 6px; padding: 4px 10px; font-size: 12px; font-weight: 600;
            cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all .2s;
            white-space: nowrap;
        }
        .btn-delete-note:hover { background: #fee2e2; border-color: #dc2626; }

        .note-body {
            color: #374151; font-size: 14px; line-height: 1.65;
            white-space: pre-wrap; word-break: break-word;
            padding-top: 4px; border-top: 1px solid #f1f5f9;
        }
        .note-item.own-note .note-body { border-top-color: #bae6fd; }

        .notes-empty { text-align: center; padding: 30px 20px; color: #a78bfa; }
        .notes-empty i { font-size: 2.5rem; margin-bottom: 10px; opacity: .6; display: block; }
        .notes-empty p { font-size: 14px; font-style: italic; margin: 0; color: #94a3b8; }

        @media (max-width:576px) {
            .note-meta { flex-direction: column; align-items: flex-start; }
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
                <h3>Detail Kandidat</h3>
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
                <div class="header-with-back">
                    <h1>Detail Kandidat</h1>
                    <p>Informasi lengkap kandidat</p>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- ── Informasi Kandidat ── -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> Informasi Kandidat</h3>
                    <a href="candidates.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i> Tutup
                    </a>
                </div>
                <div class="card-body">
                    <!-- Data Pribadi -->
                    <div class="detail-section">
                        <h4><i class="fas fa-user-circle"></i> Data Pribadi</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-user"></i> Nama Lengkap</div>
                                <div class="detail-value"><?php echo htmlspecialchars($candidate['full_name']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-envelope"></i> Email</div>
                                <div class="detail-value"><?php echo htmlspecialchars($candidate['email']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-phone"></i> Nomor Telepon</div>
                                <div class="detail-value">
                                    <?php echo !empty($candidate['no_telepon']) ? '<i class="fas fa-phone-alt"></i> ' . htmlspecialchars($candidate['no_telepon']) : '<span class="text-muted">Tidak ada</span>'; ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-graduation-cap"></i> Pendidikan Terakhir</div>
                                <div class="detail-value">
                                    <?php
                                    if (!empty($candidate['nama_jenjang'])) {
                                        echo htmlspecialchars($candidate['nama_jenjang']);
                                        if (!empty($candidate['nama_jurusan'])) echo ' - ' . htmlspecialchars($candidate['nama_jurusan']);
                                    } else {
                                        echo '<span class="text-muted">Tidak ada</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Lamaran -->
                    <div class="detail-section">
                        <h4><i class="fas fa-briefcase"></i> Informasi Lamaran</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-suitcase"></i> Posisi</div>
                                <div class="detail-value"><?php echo htmlspecialchars($candidate['title']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-info-circle"></i> Status</div>
                                <div class="detail-value">
                                    <span class="badge badge-<?php echo htmlspecialchars($candidate['status']); ?>">
                                        <?php echo htmlspecialchars($candidate['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-calendar"></i> Tanggal Melamar</div>
                                <div class="detail-value"><?php echo date('d F Y H:i', strtotime($candidate['applied_at'])); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-file-pdf"></i> CV</div>
                                <div class="detail-value">
                                    <?php if (!empty($candidate['cv'])): ?>
                                        <a href="../pelamar/cv/<?php echo htmlspecialchars($candidate['cv']); ?>" target="_blank" class="btn-link">
                                            <i class="fas fa-download"></i> Lihat/Download CV
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Tidak ada CV</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Tambahan (jadwal, alasan, dll) -->
                    <?php if (!empty($candidate['interview_date']) || !empty($candidate['reason']) || !empty($candidate['start_date'])): ?>
                    <div class="detail-section">
                        <h4><i class="fas fa-comment-alt"></i> Informasi Tambahan</h4>
                        <div class="detail-grid">
                            <?php if (!empty($candidate['interview_date'])): ?>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-calendar-check"></i> Jadwal Wawancara</div>
                                <div class="detail-value"><?php echo date('d F Y H:i', strtotime($candidate['interview_date'])); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($candidate['start_date'])): ?>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-calendar-day"></i> Tanggal Mulai Bekerja</div>
                                <div class="detail-value"><?php echo date('d F Y', strtotime($candidate['start_date'])); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($candidate['reason'])): ?>
                            <div class="detail-item full-width">
                                <div class="detail-label"><i class="fas fa-sticky-note"></i> Catatan/Alasan</div>
                                <div class="detail-value"><?php echo nl2br(htmlspecialchars($candidate['reason'])); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ══ CATATAN INTERNAL HRD (berbasis kandidat, lintas lamaran) ══ -->
                    <div class="detail-section notes-section">
                        <div class="notes-header">
                            <h4>
                                <i class="fas fa-lock"></i> Catatan Internal HRD
                                <span class="notes-count"><?php echo count($notes); ?> catatan</span>
                            </h4>
                            <small style="color:#94a3b8;font-size:12px;">
                                <i class="fas fa-eye-slash"></i> Hanya terlihat oleh tim HRD
                            </small>
                        </div>

                        <!-- Info scope: catatan mengikuti kandidat -->
                        <div class="notes-scope-info">
                            <i class="fas fa-info-circle"></i>
                            Catatan di bawah ini mengikuti <strong><?php echo htmlspecialchars($candidate['full_name']); ?></strong>
                            sebagai individu — bukan hanya lamaran ini. Seluruh catatan HRD dari lamaran sebelumnya
                            juga ditampilkan di sini untuk referensi tim.
                        </div>

                        <!-- Riwayat lamaran lain (jika ada) -->
                        <?php if (!empty($other_apps)): ?>
                        <div class="other-apps-strip">
                            <strong><i class="fas fa-history"></i> Riwayat lamaran lain oleh kandidat ini:</strong>
                            <ul>
                                <?php foreach ($other_apps as $oa): ?>
                                <li>
                                    <span class="other-app-chip">
                                        <i class="fas fa-briefcase"></i>
                                        <?php echo htmlspecialchars($oa['title']); ?>
                                        <span class="chip-status"><?php echo htmlspecialchars($oa['status']); ?></span>
                                        <span style="color:#94a3b8;font-size:10px;"><?php echo date('d/m/Y', strtotime($oa['applied_at'])); ?></span>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- Form tambah catatan -->
                        <form method="POST" class="note-form">
                            <input type="hidden" name="action"            value="add_note">
                            <input type="hidden" name="application_id"    value="<?php echo (int)$candidate['application_id']; ?>">
                            <input type="hidden" name="candidate_user_id" value="<?php echo $cuid; ?>">
                            <label for="note_text">
                                <i class="fas fa-pen"></i> Tambah Catatan Baru
                            </label>
                            <textarea name="note_text" id="note_text" class="note-textarea"
                                      placeholder="Tuliskan catatan internal di sini... (misal: kandidat memiliki pengalaman relevan, perlu diskusi lebih lanjut, dll.)"
                                      required></textarea>
                            <div class="note-form-actions">
                                <button type="submit" class="btn-add-note">
                                    <i class="fas fa-plus"></i> Simpan Catatan
                                </button>
                            </div>
                        </form>

                        <!-- Daftar catatan kandidat (lintas lamaran) -->
                        <div class="notes-list">
                            <?php if (!empty($notes)): ?>
                                <?php foreach ($notes as $note):
                                    $isOwn     = (int)$note['hrd_user_id'] === $user_id;
                                    $isCurrent = (int)$note['application_id'] === $candidate_id;
                                    $initials  = strtoupper(substr($note['hrd_name'], 0, 1));
                                ?>
                                <div class="note-item <?php echo $isOwn ? 'own-note' : ''; ?>">
                                    <div class="note-meta">
                                        <div class="note-author">
                                            <div class="note-avatar <?php echo $isOwn ? 'own' : 'other'; ?>">
                                                <?php echo htmlspecialchars($initials); ?>
                                            </div>
                                            <div class="note-author-info">
                                                <div class="note-author-name">
                                                    <?php echo htmlspecialchars($note['hrd_name']); ?>
                                                    <?php if ($isOwn): ?>
                                                        <span class="badge-own-note">Anda</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="note-datetime">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo date('d M Y, H:i', strtotime($note['created_at'])); ?>
                                                    <?php if (!empty($note['updated_at'])): ?>
                                                        &nbsp;· <i class="fas fa-pencil-alt"></i> diperbarui
                                                    <?php endif; ?>
                                                </div>
                                                <!-- Konteks: catatan ini dari lamaran mana -->
                                                <?php if (!empty($note['note_job_title'])): ?>
                                                <span class="note-job-context <?php echo $isCurrent ? 'current-job' : ''; ?>">
                                                    <i class="fas fa-<?php echo $isCurrent ? 'map-marker-alt' : 'history'; ?>"></i>
                                                    <?php echo $isCurrent ? 'Lamaran ini: ' : 'Dari lamaran: '; ?>
                                                    <strong><?php echo htmlspecialchars($note['note_job_title']); ?></strong>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($isOwn): ?>
                                        <form method="POST" style="margin:0"
                                              onsubmit="return confirm('Hapus catatan ini?')">
                                            <input type="hidden" name="action"         value="delete_note">
                                            <input type="hidden" name="application_id" value="<?php echo $candidate_id; ?>">
                                            <input type="hidden" name="note_id"        value="<?php echo (int)$note['note_id']; ?>">
                                            <button type="submit" class="btn-delete-note">
                                                <i class="fas fa-trash-alt"></i> Hapus
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <div class="note-body"><?php echo htmlspecialchars($note['note']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="notes-empty">
                                    <i class="fas fa-sticky-note"></i>
                                    <p>Belum ada catatan internal untuk kandidat ini.<br>Tambahkan catatan pertama di atas.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Form Aksi HRD -->
                    <div class="detail-section">
                        <h4><i class="fas fa-tasks"></i> Aksi HRD</h4>
                        <div class="actions">
                            <?php if ($candidate['status'] === 'lolos administrasi'): ?>
                            <form method="POST" class="action-form accept-form" onsubmit="return validateInterviewDate()">
                                <h4 class="form-title"><i class="fas fa-calendar-check"></i> Jadwalkan Wawancara</h4>
                                <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                <input type="hidden" name="action" value="move_to_interview">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar-alt"></i> Tanggal & Waktu Wawancara <span class="required">*</span></label>
                                    <input type="datetime-local" id="interview_date" name="interview_date" class="form-control" min="<?php echo $min_datetime; ?>" required>
                                    <small class="form-hint">Pilih tanggal dan waktu wawancara (tidak boleh mundur dari sekarang)</small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-calendar-check"></i> Set Jadwal Wawancara
                                </button>
                            </form>
                            <?php endif; ?>

                            <?php if ($candidate['status'] === 'tes & wawancara'): ?>
                            <form method="POST" class="action-form accept-form" onsubmit="return validateStartDate() && confirm('Yakin terima kandidat ini?')">
                                <h4 class="form-title"><i class="fas fa-user-check"></i> Terima Bekerja</h4>
                                <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                <input type="hidden" name="action" value="accept_hire">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar-alt"></i> Tanggal Mulai Bekerja <span class="required">*</span></label>
                                    <input type="date" id="start_date" name="start_date" class="form-control" min="<?php echo $min_date; ?>" required>
                                    <small class="form-hint">Tidak boleh mundur dari hari ini</small>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-comment"></i> Catatan (opsional)</label>
                                    <input type="text" name="reason" class="form-control" placeholder="Catatan untuk kandidat">
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-user-check"></i> Terima Bekerja
                                </button>
                            </form>

                            <form method="POST" class="action-form reject-form" onsubmit="return confirm('Yakin tolak kandidat ini?')">
                                <h4 class="form-title"><i class="fas fa-user-times"></i> Tolak Kandidat</h4>
                                <input type="hidden" name="application_id" value="<?php echo $candidate['application_id']; ?>">
                                <input type="hidden" name="action" value="reject_after_interview">
                                <div class="form-group">
                                    <label><i class="fas fa-comment"></i> Alasan Penolakan <span class="required">*</span></label>
                                    <textarea name="reason" class="form-control" rows="3" placeholder="Contoh: Hasil wawancara kurang memuaskan" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger btn-block">
                                    <i class="fas fa-user-times"></i> Tolak Kandidat
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>

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
            toggleBtn.style.display = sidebar.classList.contains('active') ? 'none' : 'block';
        }
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-toggle');
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                mobileToggle.style.display = 'block';
            }
        });
        function validateInterviewDate() {
            const input = document.getElementById('interview_date');
            if (!input) return true;
            if (new Date(input.value) < new Date()) {
                alert('Tanggal dan waktu wawancara tidak boleh mundur dari sekarang!');
                return false;
            }
            return true;
        }
        function validateStartDate() {
            const input = document.getElementById('start_date');
            if (!input) return true;
            const sel = new Date(input.value); sel.setHours(0,0,0,0);
            const today = new Date();          today.setHours(0,0,0,0);
            if (sel < today) {
                alert('Tanggal mulai bekerja tidak boleh mundur dari hari ini!');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>