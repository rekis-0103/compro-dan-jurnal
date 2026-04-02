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

// ── Handle POST actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['application_id'])) {
    $application_id = (int)$_POST['application_id'];

    // ── Catatan internal: tambah (berbasis kandidat/user, bukan lamaran) ──
    if ($_POST['action'] === 'add_note') {
        $note_text         = trim(esc($conn, $_POST['note_text']));
        $candidate_user_id = (int)$_POST['candidate_user_id'];

        if ($note_text !== '' && $candidate_user_id > 0) {
            // Simpan dengan candidate_user_id (lintas lamaran) dan application_id sebagai konteks
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

    } elseif ($_POST['action'] === 'accept_admin') {
        $reason = esc($conn, $_POST['reason']);
        $q = "UPDATE applications SET status='lolos administrasi', updated_at=NOW(), reason='$reason' WHERE application_id=$application_id";
        if (mysqli_query($conn, $q)) {
            $info = mysqli_query($conn, "SELECT a.*, u.email, u.full_name, l.title FROM applications a JOIN users u ON a.user_id=u.user_id JOIN lowongan l ON a.job_id=l.job_id WHERE a.application_id=$application_id");
            if ($info) {
                $row = mysqli_fetch_assoc($info);
                $msg = "Halo " . $row['full_name'] . ",\n\nSelamat, Anda LOLOS seleksi administrasi untuk posisi " . $row['title'] . ".\nAlasan: $reason\n\nKami akan menghubungi Anda untuk jadwal wawancara selanjutnya.\n\nTerima kasih.\nHRD";
                sendEmail($row['email'], 'Hasil Seleksi Administrasi - ' . $row['title'], $msg);
                logActivity($conn, $user_id, "HRD: terima administrasi application #$application_id (" . $row['title'] . ")");
            }
            $success = 'Lamaran diterima pada seleksi administrasi. Silakan jadwalkan wawancara di menu Kandidat.';
        } else {
            $error = 'Gagal memperbarui status';
        }

    } elseif ($_POST['action'] === 'reject_admin') {
        $reason = esc($conn, $_POST['reason']);
        $q = "UPDATE applications SET status='ditolak administrasi', updated_at=NOW(), reason='$reason' WHERE application_id=$application_id";
        if (mysqli_query($conn, $q)) {
            $info = mysqli_query($conn, "SELECT a.*, u.email, u.full_name, l.title FROM applications a JOIN users u ON a.user_id=u.user_id JOIN lowongan l ON a.job_id=l.job_id WHERE a.application_id=$application_id");
            if ($info) {
                $row = mysqli_fetch_assoc($info);
                $msg = "Halo " . $row['full_name'] . ",\n\nMohon maaf, lamaran Anda TIDAK LOLOS seleksi administrasi untuk posisi " . $row['title'] . ".\nAlasan: $reason\n\nTerima kasih.\nHRD";
                sendEmail($row['email'], 'Hasil Seleksi Administrasi - ' . $row['title'], $msg);
                logActivity($conn, $user_id, "HRD: tolak administrasi application #$application_id (" . $row['title'] . ")");
            }
            $success = 'Lamaran ditolak';
        } else {
            $error = 'Gagal memperbarui status';
        }
    }
}

// ── Detail view ──
$detail = null;
$notes  = [];
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    mysqli_query($conn, "
        UPDATE applications
        SET status='seleksi administrasi', updated_at=NOW()
        WHERE application_id=$id AND status='pending'
    ");

    $detail_q = mysqli_query($conn, "
        SELECT a.*, u.full_name, u.email, u.user_id AS candidate_user_id, l.title,
               jenjang.nama_jenjang, jurusan.nama_jurusan
        FROM applications a
        JOIN users u ON a.user_id = u.user_id
        JOIN lowongan l ON a.job_id = l.job_id
        LEFT JOIN jenjang_pendidikan jenjang ON a.id_jenjang_pendidikan = jenjang.id_jenjang
        LEFT JOIN jurusan_pendidikan jurusan ON a.id_jurusan_pendidikan = jurusan.id_jurusan
        WHERE a.application_id = $id AND l.posted_by = $user_id
    ");

    if ($detail_q && mysqli_num_rows($detail_q) > 0) {
        $detail = mysqli_fetch_assoc($detail_q);
        $cuid   = (int)$detail['candidate_user_id'];

        // ── Ambil semua catatan untuk kandidat ini (lintas semua lamaran) ──
        $notes_q = mysqli_query($conn, "
            SELECT n.*,
                   u.full_name AS hrd_name,
                   l.title     AS note_job_title
            FROM hrd_notes n
            JOIN users u ON n.hrd_user_id = u.user_id
            LEFT JOIN applications a ON n.application_id = a.application_id
            LEFT JOIN lowongan l     ON a.job_id = l.job_id
            WHERE n.candidate_user_id = $cuid
            ORDER BY n.created_at DESC
        ");
        if ($notes_q) while ($n = mysqli_fetch_assoc($notes_q)) $notes[] = $n;
    }
}

// ── List lamaran baru ──
$list = mysqli_query($conn, "
    SELECT a.*, u.full_name, u.email, l.title,
           jenjang.nama_jenjang, jurusan.nama_jurusan
    FROM applications a
    JOIN users u ON a.user_id=u.user_id
    JOIN lowongan l ON a.job_id=l.job_id
    LEFT JOIN jenjang_pendidikan jenjang ON a.id_jenjang_pendidikan = jenjang.id_jenjang
    LEFT JOIN jurusan_pendidikan jurusan ON a.id_jurusan_pendidikan = jurusan.id_jurusan
    WHERE a.status IN ('pending', 'seleksi administrasi')
      AND l.posted_by=$user_id
    ORDER BY a.applied_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lamaran - PT Waindo Specterra</title>
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/applications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ══ CATATAN INTERNAL HRD ══ */
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
        .notes-scope-info {
            background: #fef9c3; border: 1px solid #fde68a;
            border-radius: 8px; padding: 10px 14px;
            font-size: 12.5px; color: #78350f;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 16px;
        }
        .notes-scope-info i { color: #d97706; flex-shrink: 0; }
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
        /* Tag konteks lamaran pada catatan */
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
        }
        .btn-delete-note:hover { background: #fee2e2; border-color: #dc2626; }
        .note-body {
            color: #374151; font-size: 14px; line-height: 1.65;
            white-space: pre-wrap; word-break: break-word;
            padding-top: 4px; border-top: 1px solid #f1f5f9;
        }
        .note-item.own-note .note-body { border-top-color: #bae6fd; }
        .notes-empty { text-align: center; padding: 30px 20px; }
        .notes-empty i { font-size: 2.5rem; margin-bottom: 10px; opacity: .6; color: #a78bfa; display: block; }
        .notes-empty p { font-size: 14px; font-style: italic; margin: 0; color: #94a3b8; }
        @media (max-width:576px) { .note-meta { flex-direction: column; align-items: flex-start; } }
    </style>
</head>

<body>
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Kelola Lamaran</h3>
                <p>Selamat datang, <?php echo htmlspecialchars($full_name); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="lowongan.php"><i class="fas fa-briefcase"></i> Kelola Lowongan</a></li>
                <li><a href="applications.php" class="active"><i class="fas fa-file-alt"></i> Kelola Lamaran</a></li>
                <li><a href="candidates.php"><i class="fas fa-users"></i> Kandidat</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Beranda</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>Kelola Lamaran</h1>
                <p>Review lamaran dan tentukan hasil seleksi administrasi</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($detail)): ?>
            <!-- ══ DETAIL LAMARAN ══ -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user"></i> Detail Lamaran</h3>
                    <a href="applications.php" class="btn btn-secondary btn-sm">Kembali</a>
                </div>
                <div class="card-body">

                    <div class="detail-section">
                        <h4><i class="fas fa-user-circle"></i> Informasi Pelamar</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-user"></i> Nama Lengkap</div>
                                <div class="detail-value"><?php echo htmlspecialchars($detail['full_name']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-envelope"></i> Email</div>
                                <div class="detail-value"><?php echo htmlspecialchars($detail['email']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-phone"></i> Nomor Telepon</div>
                                <div class="detail-value">
                                    <?php echo !empty($detail['no_telepon']) ? htmlspecialchars($detail['no_telepon']) : '-'; ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-graduation-cap"></i> Pendidikan Terakhir</div>
                                <div class="detail-value">
                                    <?php
                                    if (!empty($detail['nama_jenjang'])) {
                                        echo htmlspecialchars($detail['nama_jenjang']);
                                        if (!empty($detail['nama_jurusan'])) echo ' - ' . htmlspecialchars($detail['nama_jurusan']);
                                    } else { echo '-'; }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4><i class="fas fa-briefcase"></i> Informasi Lamaran</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-suitcase"></i> Posisi</div>
                                <div class="detail-value"><?php echo htmlspecialchars($detail['title']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-info-circle"></i> Status</div>
                                <div class="detail-value">
                                    <span class="badge badge-<?php echo $detail['status']; ?>">
                                        <?php echo htmlspecialchars($detail['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-calendar"></i> Tanggal Lamar</div>
                                <div class="detail-value"><?php echo date('d F Y H:i', strtotime($detail['applied_at'])); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-file-pdf"></i> CV</div>
                                <div class="detail-value">
                                    <?php if (!empty($detail['cv'])): ?>
                                        <a href="../pelamar/cv/<?php echo htmlspecialchars($detail['cv']); ?>" target="_blank" class="btn-link">
                                            <i class="fas fa-download"></i> Lihat/Download CV
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Tidak ada CV</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($detail['reason'])): ?>
                    <div class="detail-section">
                        <h4><i class="fas fa-comment-alt"></i> Informasi Tambahan</h4>
                        <div class="detail-grid">
                            <div class="detail-item full-width">
                                <div class="detail-label"><i class="fas fa-sticky-note"></i> Alasan/Catatan</div>
                                <div class="detail-value"><?php echo htmlspecialchars($detail['reason']); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ══ CATATAN INTERNAL HRD (berbasis kandidat, lintas lamaran) ══ -->
                    <div class="detail-section">
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
                            Catatan di bawah ini mengikuti <strong><?php echo htmlspecialchars($detail['full_name']); ?></strong>
                            sebagai individu — bukan hanya lamaran ini. Jika kandidat pernah melamar posisi lain,
                            catatan HRD dari lamaran tersebut juga tampil di sini.
                        </div>

                        <!-- Form tambah catatan -->
                        <form method="POST" class="note-form">
                            <input type="hidden" name="action"             value="add_note">
                            <input type="hidden" name="application_id"     value="<?php echo (int)$detail['application_id']; ?>">
                            <input type="hidden" name="candidate_user_id"  value="<?php echo (int)$detail['candidate_user_id']; ?>">
                            <label for="note_text"><i class="fas fa-pen"></i> Tambah Catatan Baru</label>
                            <textarea name="note_text" id="note_text" class="note-textarea"
                                      placeholder="Tuliskan catatan internal di sini... (misal: dokumen kurang lengkap, perlu verifikasi data, dll.)"
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
                                    $isOwn    = (int)$note['hrd_user_id'] === $user_id;
                                    $isCurrent = (int)$note['application_id'] === (int)$detail['application_id'];
                                    $initials = strtoupper(substr($note['hrd_name'], 0, 1));
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
                                                <!-- Konteks lamaran dari mana catatan berasal -->
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
                                        <form method="POST" style="margin:0" onsubmit="return confirm('Hapus catatan ini?')">
                                            <input type="hidden" name="action"         value="delete_note">
                                            <input type="hidden" name="application_id" value="<?php echo (int)$detail['application_id']; ?>">
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

                    <!-- Aksi Terima / Tolak Administrasi -->
                    <div class="actions">
                        <form method="POST" class="action-form accept-form">
                            <h4 class="form-title"><i class="fas fa-check-circle"></i> Terima Lamaran</h4>
                            <input type="hidden" name="application_id" value="<?php echo (int)$detail['application_id']; ?>">
                            <input type="hidden" name="action" value="accept_admin">
                            <div class="form-group">
                                <label><i class="fas fa-comment"></i> Alasan Diterima <span class="required">*</span></label>
                                <textarea name="reason" rows="3" placeholder="Contoh: Memenuhi persyaratan administrasi" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-check"></i> Terima (Lolos Administrasi)
                            </button>
                        </form>

                        <form method="POST" class="action-form reject-form" onsubmit="return confirm('Yakin tolak lamaran ini?')">
                            <h4 class="form-title"><i class="fas fa-times-circle"></i> Tolak Lamaran</h4>
                            <input type="hidden" name="application_id" value="<?php echo (int)$detail['application_id']; ?>">
                            <input type="hidden" name="action" value="reject_admin">
                            <div class="form-group">
                                <label><i class="fas fa-comment"></i> Alasan Ditolak <span class="required">*</span></label>
                                <textarea name="reason" rows="3" placeholder="Contoh: Tidak memenuhi persyaratan minimal" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-times"></i> Tolak Lamaran
                            </button>
                        </form>
                    </div>

                </div>
            </div>

            <?php else: ?>
            <!-- ══ TABEL LAMARAN BARU ══ -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-inbox"></i> Lamaran Baru (Pendaftaran Diterima)</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Posisi</th>
                                <th>Telepon</th>
                                <th>Pendidikan</th>
                                <th>Tanggal Lamar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list && mysqli_num_rows($list) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($list)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo !empty($row['no_telepon']) ? htmlspecialchars($row['no_telepon']) : '-'; ?></td>
                                        <td>
                                            <?php
                                            if (!empty($row['nama_jenjang'])) {
                                                echo htmlspecialchars($row['nama_jenjang']);
                                                if (!empty($row['nama_jurusan'])) echo ' - ' . htmlspecialchars($row['nama_jurusan']);
                                            } else { echo '-'; }
                                            ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['applied_at'])); ?></td>
                                        <td>
                                            <a class="btn btn-primary btn-sm" href="applications.php?id=<?php echo (int)$row['application_id']; ?>">
                                                <i class="fas fa-eye"></i> Periksa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center">Tidak ada lamaran baru</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel Semua Pelamar -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Semua Pelamar</h3>
                    <form method="GET" class="filter-form">
                        <select name="status_filter" class="form-control">
                            <option value="" <?= empty($_GET['status_filter']) ? 'selected' : '' ?>>-- Semua Status --</option>
                            <option value="pending"               <?= ($_GET['status_filter'] ?? '') === 'pending'               ? 'selected' : '' ?>>Pending</option>
                            <option value="seleksi administrasi"  <?= ($_GET['status_filter'] ?? '') === 'seleksi administrasi'  ? 'selected' : '' ?>>Seleksi Administrasi</option>
                            <option value="lolos administrasi"    <?= ($_GET['status_filter'] ?? '') === 'lolos administrasi'    ? 'selected' : '' ?>>Lolos Administrasi</option>
                            <option value="tes & wawancara"       <?= ($_GET['status_filter'] ?? '') === 'tes & wawancara'       ? 'selected' : '' ?>>Tes & Wawancara</option>
                            <option value="diterima bekerja"      <?= ($_GET['status_filter'] ?? '') === 'diterima bekerja'      ? 'selected' : '' ?>>Diterima Bekerja</option>
                            <option value="ditolak"               <?= ($_GET['status_filter'] ?? '') === 'ditolak'               ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="export_pdf.php?status=<?= $_GET['status_filter'] ?? '' ?>" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    </form>
                </div>
                <div class="card-body table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th><th>Nama</th><th>Nama Pekerjaan</th>
                                <th>Tempat</th><th>Telepon</th><th>Pendidikan</th>
                                <th>Status</th><th>Tanggal Lamar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $filter = "";
                            if (!empty($_GET['status_filter'])) {
                                $value  = mysqli_real_escape_string($conn, $_GET['status_filter']);
                                $filter = $value === 'ditolak'
                                    ? "AND a.status LIKE '%ditolak%'"
                                    : "AND a.status = '$value'";
                            }
                            $sql = "SELECT u.full_name AS nama, l.title AS nama_pekerjaan,
                                           l.location AS tempat, a.no_telepon,
                                           jenjang.nama_jenjang, jurusan.nama_jurusan,
                                           a.status, a.applied_at AS tanggal_lamar
                                    FROM applications a
                                    JOIN users u ON u.user_id = a.user_id
                                    JOIN lowongan l ON l.job_id = a.job_id
                                    LEFT JOIN jenjang_pendidikan jenjang ON a.id_jenjang_pendidikan = jenjang.id_jenjang
                                    LEFT JOIN jurusan_pendidikan jurusan ON a.id_jurusan_pendidikan = jurusan.id_jurusan
                                    WHERE l.posted_by=$user_id $filter
                                    ORDER BY a.applied_at DESC";
                            $result = mysqli_query($conn, $sql);
                            if ($result && mysqli_num_rows($result) > 0) {
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $telepon    = !empty($row['no_telepon']) ? htmlspecialchars($row['no_telepon']) : '-';
                                    $pendidikan = '-';
                                    if (!empty($row['nama_jenjang'])) {
                                        $pendidikan = htmlspecialchars($row['nama_jenjang']);
                                        if (!empty($row['nama_jurusan'])) $pendidikan .= ' - ' . htmlspecialchars($row['nama_jurusan']);
                                    }
                                    echo "<tr>
                                            <td>{$no}</td>
                                            <td>" . htmlspecialchars($row['nama']) . "</td>
                                            <td>" . htmlspecialchars($row['nama_pekerjaan']) . "</td>
                                            <td>" . htmlspecialchars($row['tempat']) . "</td>
                                            <td>{$telepon}</td>
                                            <td>{$pendidikan}</td>
                                            <td><span class='badge badge-" . htmlspecialchars($row['status']) . "'>" . htmlspecialchars($row['status']) . "</span></td>
                                            <td>" . date('d/m/Y H:i', strtotime($row['tanggal_lamar'])) . "</td>
                                          </tr>";
                                    $no++;
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>Tidak ada data untuk filter ini</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

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
    </script>
</body>
</html>