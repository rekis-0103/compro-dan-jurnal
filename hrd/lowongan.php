<?php
session_start();
require_once '../connect/koneksi.php';

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

// Helpers
function esc($conn, $str)
{
    return mysqli_real_escape_string($conn, $str);
}
function logActivity($conn, $actor_user_id, $action)
{
    $actor_user_id = (int)$actor_user_id;
    $action = mysqli_real_escape_string($conn, $action);
    mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, action) VALUES ($actor_user_id, '$action')");
}

// Function to handle image upload
function uploadPopupImage($file, $orientation)
{
    $uploadDir = '../uploads/popups/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes))
        return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.'];
    if ($file['size'] > 5 * 1024 * 1024)
        return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 5MB.'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'popup_' . $orientation . '_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename))
        return ['success' => true, 'filename' => $filename];
    return ['success' => false, 'message' => 'Gagal mengupload file.'];
}

// ============================================================
// AUTO-CLOSE: cek batas waktu & kuota setiap halaman dimuat
// ============================================================
function autoCloseLowongan($conn)
{
    $now = date('Y-m-d H:i:s');

    // 1. Tutup karena batas waktu habis
    $expired = mysqli_query($conn,
        "SELECT job_id, title FROM lowongan
         WHERE hapus=0 AND status='open'
           AND deadline IS NOT NULL AND deadline < '$now'");
    while ($row = mysqli_fetch_assoc($expired)) {
        $jid = (int)$row['job_id'];
        mysqli_query($conn,
            "UPDATE lowongan SET status='closed',
             close_reason='Ditutup otomatis: batas waktu pendaftaran telah berakhir',
             updated_at=NOW()
             WHERE job_id=$jid");
    }

    // 2. Tutup karena kuota terpenuhi
    $quota_check = mysqli_query($conn,
        "SELECT l.job_id, l.title, l.quota,
                COUNT(a.application_id) AS total_apply
         FROM lowongan l
         LEFT JOIN applications a ON l.job_id = a.job_id
         WHERE l.hapus=0 AND l.status='open'
           AND l.quota IS NOT NULL AND l.quota > 0
         GROUP BY l.job_id
         HAVING total_apply >= l.quota");
    while ($row = mysqli_fetch_assoc($quota_check)) {
        $jid = (int)$row['job_id'];
        mysqli_query($conn,
            "UPDATE lowongan SET status='closed',
             close_reason='Ditutup otomatis: kuota pendaftar telah terpenuhi (" . (int)$row['quota'] . " orang)',
             updated_at=NOW()
             WHERE job_id=$jid");
    }
}

autoCloseLowongan($conn);

// ============================================================
// Handle POST actions
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $title        = esc($conn, $_POST['title']);
        $description  = esc($conn, $_POST['description']);
        $requirements = esc($conn, $_POST['requirements']);
        $location     = esc($conn, $_POST['location']);
        $salary_range = esc($conn, $_POST['salary_range']);
        $status       = esc($conn, $_POST['status']);
        $req_jenjang  = isset($_POST['req_jenjang']) && $_POST['req_jenjang'] !== '' ? (int)$_POST['req_jenjang'] : NULL;
        $req_jurusan  = isset($_POST['req_jurusan']) && $_POST['req_jurusan'] !== '' ? (int)$_POST['req_jurusan'] : NULL;

        // Batas waktu & kuota (opsional)
        $deadline = !empty($_POST['deadline']) ? "'" . esc($conn, $_POST['deadline']) . "'" : 'NULL';
        $quota    = !empty($_POST['quota']) && (int)$_POST['quota'] > 0 ? (int)$_POST['quota'] : 'NULL';

        $q = "INSERT INTO lowongan
                (title, description, requirements, location, salary_range, status, posted_by,
                 req_jenjang_pendidikan, req_jurusan_pendidikan, deadline, quota)
              VALUES
                ('$title','$description','$requirements','$location','$salary_range','$status',$user_id,
                 " . ($req_jenjang ? $req_jenjang : 'NULL') . ",
                 " . ($req_jurusan ? $req_jurusan : 'NULL') . ",
                 $deadline, $quota)";

        if (mysqli_query($conn, $q)) {
            $success = 'Lowongan berhasil ditambahkan';
            $new_id = mysqli_insert_id($conn);
            logActivity($conn, $user_id, "HRD: tambah lowongan #$new_id - $title");
        } else {
            $error = 'Gagal menambahkan lowongan: ' . mysqli_error($conn);
        }

    } elseif ($action === 'edit') {
        $job_id       = (int)$_POST['job_id'];
        $title        = esc($conn, $_POST['title']);
        $description  = esc($conn, $_POST['description']);
        $requirements = esc($conn, $_POST['requirements']);
        $location     = esc($conn, $_POST['location']);
        $salary_range = esc($conn, $_POST['salary_range']);
        $status       = esc($conn, $_POST['status']);
        $req_jenjang  = isset($_POST['req_jenjang']) && $_POST['req_jenjang'] !== '' ? (int)$_POST['req_jenjang'] : NULL;
        $req_jurusan  = isset($_POST['req_jurusan']) && $_POST['req_jurusan'] !== '' ? (int)$_POST['req_jurusan'] : NULL;

        $deadline = !empty($_POST['deadline']) ? "'" . esc($conn, $_POST['deadline']) . "'" : 'NULL';
        $quota    = !empty($_POST['quota']) && (int)$_POST['quota'] > 0 ? (int)$_POST['quota'] : 'NULL';

        // Reset close_reason jika HRD membuka kembali
        $close_reason_sql = $status === 'open' ? "close_reason=NULL," : "";

        $q = "UPDATE lowongan SET
                title='$title', description='$description', requirements='$requirements',
                location='$location', salary_range='$salary_range', status='$status',
                req_jenjang_pendidikan=" . ($req_jenjang ? $req_jenjang : 'NULL') . ",
                req_jurusan_pendidikan=" . ($req_jurusan ? $req_jurusan : 'NULL') . ",
                deadline=$deadline, quota=$quota,
                $close_reason_sql
                updated_at=NOW()
              WHERE job_id=$job_id";

        if (mysqli_query($conn, $q)) {
            $success = 'Lowongan berhasil diubah';
            logActivity($conn, $user_id, "HRD: edit lowongan #$job_id - $title");
        } else {
            $error = 'Gagal mengubah lowongan: ' . mysqli_error($conn);
        }

    } elseif ($action === 'toggle') {
        $job_id     = (int)$_POST['job_id'];
        $new_status = esc($conn, $_POST['new_status']);
        // Jika dibuka ulang manual, hapus close_reason
        $clear = $new_status === 'open' ? ", close_reason=NULL" : "";
        $q = "UPDATE lowongan SET status='$new_status' $clear, updated_at=NOW() WHERE job_id=$job_id";
        if (mysqli_query($conn, $q)) {
            $success = 'Status lowongan diperbarui';
            logActivity($conn, $user_id, "HRD: ubah status lowongan #$job_id -> $new_status");
        } else {
            $error = 'Gagal memperbarui status';
        }

    } elseif ($action === 'delete') {
        $job_id = (int)$_POST['job_id'];
        $q = "UPDATE lowongan SET hapus=1, updated_at=NOW() WHERE job_id=$job_id";
        if (mysqli_query($conn, $q)) {
            $success = 'Lowongan dihapus';
            logActivity($conn, $user_id, "HRD: hapus lowongan #$job_id");
        } else {
            $error = 'Gagal menghapus lowongan';
        }

    } elseif ($action === 'add_popup') {
        $popup_title = esc($conn, $_POST['popup_title']);
        $orientation = esc($conn, $_POST['orientation']);
        if (isset($_FILES['popup_image']) && $_FILES['popup_image']['error'] === 0) {
            $uploadResult = uploadPopupImage($_FILES['popup_image'], $orientation);
            if ($uploadResult['success']) {
                $filename = $uploadResult['filename'];
                $q = "INSERT INTO popup_images (title, image_filename, orientation, created_by) VALUES ('$popup_title', '$filename', '$orientation', $user_id)";
                if (mysqli_query($conn, $q)) {
                    $success = 'Popup gambar berhasil ditambahkan';
                    logActivity($conn, $user_id, "HRD: tambah popup gambar - $popup_title");
                } else {
                    $error = 'Gagal menyimpan data popup ke database';
                    unlink('../uploads/popups/' . $filename);
                }
            } else {
                $error = $uploadResult['message'];
            }
        } else {
            $error = 'Harap pilih gambar untuk diupload';
        }

    } elseif ($action === 'edit_popup') {
        $popup_id    = (int)$_POST['popup_id'];
        $popup_title = esc($conn, $_POST['popup_title']);
        $orientation = esc($conn, $_POST['orientation']);
        $currentData = mysqli_query($conn, "SELECT * FROM popup_images WHERE popup_id = $popup_id");
        $current     = mysqli_fetch_assoc($currentData);
        $filename    = $current['image_filename'];
        if (isset($_FILES['popup_image']) && $_FILES['popup_image']['error'] === 0) {
            $uploadResult = uploadPopupImage($_FILES['popup_image'], $orientation);
            if ($uploadResult['success']) {
                if (file_exists('../uploads/popups/' . $current['image_filename']))
                    unlink('../uploads/popups/' . $current['image_filename']);
                $filename = $uploadResult['filename'];
            } else {
                $error = $uploadResult['message'];
            }
        }
        if (!isset($error)) {
            $q = "UPDATE popup_images SET title='$popup_title', image_filename='$filename', orientation='$orientation', updated_at=NOW() WHERE popup_id=$popup_id";
            if (mysqli_query($conn, $q)) {
                $success = 'Popup gambar berhasil diubah';
                logActivity($conn, $user_id, "HRD: edit popup gambar #$popup_id");
            } else {
                $error = 'Gagal mengubah popup gambar';
            }
        }

    } elseif ($action === 'toggle_popup') {
        $popup_id  = (int)$_POST['popup_id'];
        $is_active = (int)$_POST['is_active'];
        $q = "UPDATE popup_images SET is_active=" . ($is_active ? '1' : '0') . ", updated_at=NOW() WHERE popup_id=$popup_id";
        if (mysqli_query($conn, $q)) {
            $success = 'Status popup diperbarui';
            logActivity($conn, $user_id, "HRD: toggle popup #$popup_id -> " . ($is_active ? 'aktif' : 'nonaktif'));
        } else {
            $error = 'Gagal memperbarui status popup';
        }

    } elseif ($action === 'delete_popup') {
        $popup_id = (int)$_POST['popup_id'];
        $result   = mysqli_query($conn, "SELECT image_filename FROM popup_images WHERE popup_id = $popup_id");
        $popup    = mysqli_fetch_assoc($result);
        if ($popup) {
            if (file_exists('../uploads/popups/' . $popup['image_filename']))
                unlink('../uploads/popups/' . $popup['image_filename']);
            $q = "DELETE FROM popup_images WHERE popup_id=$popup_id";
            if (mysqli_query($conn, $q)) {
                $success = 'Popup gambar dihapus';
                logActivity($conn, $user_id, "HRD: hapus popup gambar #$popup_id");
            } else {
                $error = 'Gagal menghapus popup gambar';
            }
        } else {
            $error = 'Popup tidak ditemukan';
        }
    }
}

// ============================================================
// Fetch data
// ============================================================
$list = mysqli_query($conn,
    "SELECT l.*, u.full_name AS poster,
            jenjang.nama_jenjang, jurusan.nama_jurusan,
            (SELECT COUNT(*) FROM applications a WHERE a.job_id = l.job_id) AS total_apply
     FROM lowongan l
     LEFT JOIN users u       ON l.posted_by = u.user_id
     LEFT JOIN jenjang_pendidikan jenjang ON l.req_jenjang_pendidikan = jenjang.id_jenjang
     LEFT JOIN jurusan_pendidikan jurusan ON l.req_jurusan_pendidikan = jurusan.id_jurusan
     WHERE l.hapus=0 AND l.posted_by=$user_id
     ORDER BY l.posted_at DESC");

$popups = mysqli_query($conn,
    "SELECT p.*, u.full_name AS creator
     FROM popup_images p
     LEFT JOIN users u ON p.created_by = u.user_id
     ORDER BY p.is_active DESC, p.created_at DESC");

$jenjang_list = mysqli_query($conn, "SELECT * FROM jenjang_pendidikan WHERE status=1 ORDER BY nama_jenjang");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lowongan - PT Waindo Specterra</title>
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/lowongan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ── Info chips batas waktu & kuota ── */
        .meta-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
        .chip {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; font-weight: 600; padding: 3px 9px;
            border-radius: 999px; white-space: nowrap;
        }
        .chip-deadline { background: #fef3c7; color: #92400e; }
        .chip-deadline.urgent { background: #fee2e2; color: #991b1b; }
        .chip-quota   { background: #dbeafe; color: #1e40af; }
        .chip-quota.full { background: #d1fae5; color: #065f46; }

        /* ── Alasan penutupan ── */
        .close-reason {
            font-size: 11px; color: #6b7280; font-style: italic;
            margin-top: 4px; display: flex; align-items: flex-start; gap: 4px;
        }
        .close-reason i { color: #f59e0b; margin-top: 1px; flex-shrink: 0; }

        /* ── Progress bar kuota ── */
        .quota-bar-wrap {
            width: 100%; background: #e5e7eb;
            border-radius: 999px; height: 6px; margin-top: 4px;
            overflow: hidden;
        }
        .quota-bar { height: 100%; border-radius: 999px; transition: width .3s; }
        .quota-bar.low    { background: #3b82f6; }
        .quota-bar.medium { background: #f59e0b; }
        .quota-bar.high   { background: #ef4444; }

        /* ── Form hint ── */
        .form-hint { font-size: 11px; color: #9ca3af; margin-top: 4px; }

        /* ── Badge auto-closed ── */
        .badge-auto { background: #fde68a; color: #92400e; }
    </style>
</head>
<body>
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Kelola Lowongan</h3>
                <p>Selamat datang, <?php echo htmlspecialchars($full_name); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="lowongan.php" class="active"><i class="fas fa-briefcase"></i> Kelola Lowongan</a></li>
                <li><a href="applications.php"><i class="fas fa-file-alt"></i> Kelola Lamaran</a></li>
                <li><a href="candidates.php"><i class="fas fa-users"></i> Kandidat</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Beranda</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>Kelola Lowongan</h1>
                <p>Tambah, ubah, dan kelola lowongan pekerjaan</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <!-- ══════════════════════════════════════════
                 POPUP IMAGE MANAGEMENT
            ══════════════════════════════════════════ -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-image"></i> Kelola Popup Gambar</h3>
                </div>
                <form method="POST" enctype="multipart/form-data" class="card-body">
                    <input type="hidden" name="action" value="add_popup">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Judul Popup</label>
                            <input type="text" name="popup_title" required placeholder="Masukkan judul popup">
                        </div>
                        <div class="form-group">
                            <label>Orientasi</label>
                            <select name="orientation" required>
                                <option value="vertical">Vertikal (Portrait)</option>
                                <option value="horizontal">Horizontal (Landscape)</option>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label>Upload Gambar</label>
                            <div class="file-input-wrapper">
                                <input type="file" name="popup_image" id="popup_image" accept="image/*" required>
                                <label for="popup_image" class="file-input-display">
                                    <i class="fas fa-cloud-upload-alt"></i><br>Klik untuk memilih gambar
                                </label>
                            </div>
                            <div class="file-info">Format: JPG, PNG, GIF | Maksimal: 5MB</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Tambah Popup</button>
                </form>

                <div class="card-body">
                    <h4><i class="fas fa-list"></i> Daftar Popup Gambar</h4>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Preview</th><th>Judul</th><th>Orientasi</th>
                                    <th>Status</th><th>Dibuat</th><th>Oleh</th><th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($popups && mysqli_num_rows($popups) > 0): ?>
                                    <?php while ($popup = mysqli_fetch_assoc($popups)): ?>
                                        <tr>
                                            <td><img src="../uploads/popups/<?php echo htmlspecialchars($popup['image_filename']); ?>" alt="Preview" class="image-preview" onerror="this.src='../assets/images/no-image.png'"></td>
                                            <td><?php echo htmlspecialchars($popup['title']); ?></td>
                                            <td><span class="badge badge-<?php echo $popup['orientation']==='vertical' ? 'info' : 'warning'; ?>"><?php echo $popup['orientation']==='vertical' ? 'Vertikal' : 'Horizontal'; ?></span></td>
                                            <td><span class="badge badge-<?php echo $popup['is_active'] ? 'success' : 'secondary'; ?>"><?php echo $popup['is_active'] ? 'Aktif' : 'Nonaktif'; ?></span></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($popup['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($popup['creator'] ?: '-'); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning" onclick="openEditPopup(<?php echo (int)$popup['popup_id']; ?>, <?php echo htmlspecialchars(json_encode($popup)); ?>)"><i class="fas fa-edit"></i></button>
                                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus popup ini?')">
                                                    <input type="hidden" name="action" value="delete_popup">
                                                    <input type="hidden" name="popup_id" value="<?php echo (int)$popup['popup_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                                <form method="POST" style="display:inline">
                                                    <input type="hidden" name="action" value="toggle_popup">
                                                    <input type="hidden" name="popup_id" value="<?php echo (int)$popup['popup_id']; ?>">
                                                    <input type="hidden" name="is_active" value="<?php echo $popup['is_active'] ? '0' : '1'; ?>">
                                                    <button type="submit" class="btn btn-sm btn-<?php echo $popup['is_active'] ? 'secondary' : 'success'; ?>"><?php echo $popup['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center">Belum Ada Popup Gambar</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 TAMBAH LOWONGAN
            ══════════════════════════════════════════ -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus"></i> Tambah Lowongan</h3>
                </div>
                <form method="POST" class="card-body" id="addJobForm">
                    <input type="hidden" name="action" value="add">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Judul</label>
                            <input type="text" name="title" required placeholder="Contoh: Staff IT">
                        </div>
                        <div class="form-group">
                            <label>Lokasi</label>
                            <input type="text" name="location" placeholder="Contoh: Jakarta">
                        </div>
                        <div class="form-group">
                            <label>Range Gaji</label>
                            <input type="text" name="salary_range" placeholder="Contoh: Rp 5.000.000 – Rp 7.000.000">
                        </div>
                        <div class="form-group">
                            <label>Status Awal</label>
                            <select name="status" required>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <!-- ── BATAS WAKTU ── -->
                        <div class="form-group">
                            <label><i class="fas fa-calendar-times"></i> Batas Waktu Pendaftaran</label>
                            <input type="datetime-local" name="deadline" id="add_deadline">
                            <span class="form-hint">Kosongkan jika tidak ada batas waktu. Lowongan akan ditutup otomatis setelah tanggal ini.</span>
                        </div>

                        <!-- ── KUOTA ── -->
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Kuota Pendaftar</label>
                            <input type="number" name="quota" id="add_quota" min="1" placeholder="Contoh: 10">
                            <span class="form-hint">Kosongkan jika tidak ada batas kuota. Lowongan akan ditutup otomatis saat kuota terpenuhi.</span>
                        </div>

                        <!-- Education Requirements -->
                        <div class="form-group">
                            <label><i class="fas fa-graduation-cap"></i> Syarat Pendidikan (Jenjang)</label>
                            <select name="req_jenjang" id="add_req_jenjang" onchange="loadJurusanOptions('add')">
                                <option value="">-- Tidak Ada Syarat --</option>
                                <?php
                                mysqli_data_seek($jenjang_list, 0);
                                while ($jenjang = mysqli_fetch_assoc($jenjang_list)): ?>
                                    <option value="<?php echo $jenjang['id_jenjang']; ?>"
                                            data-punya-jurusan="<?php echo $jenjang['punya_jurusan']; ?>">
                                        <?php echo htmlspecialchars($jenjang['nama_jenjang']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group" id="add_jurusan_group" style="display:none;">
                            <label><i class="fas fa-book"></i> Syarat Jurusan</label>
                            <select name="req_jurusan" id="add_req_jurusan">
                                <option value="">-- Pilih Jurusan --</option>
                            </select>
                        </div>

                        <div class="form-group full">
                            <label>Deskripsi</label>
                            <textarea name="description" rows="3" required></textarea>
                        </div>
                        <div class="form-group full">
                            <label>Persyaratan</label>
                            <textarea name="requirements" rows="3"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </form>
            </div>

            <!-- ══════════════════════════════════════════
                 DAFTAR LOWONGAN
            ══════════════════════════════════════════ -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Lowongan</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Judul / Info</th>
                                <th>Lokasi</th>
                                <th>Gaji</th>
                                <th>Syarat Pendidikan</th>
                                <th>Status</th>
                                <th>Diposting</th>
                                <th>Oleh</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list && mysqli_num_rows($list) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($list)):
                                    $now        = new DateTime();
                                    $hasDeadline = !empty($row['deadline']);
                                    $hasQuota    = !empty($row['quota']) && $row['quota'] > 0;
                                    $totalApply  = (int)$row['total_apply'];
                                    $quota       = (int)$row['quota'];
                                    $pct         = ($hasQuota && $quota > 0) ? min(100, round($totalApply / $quota * 100)) : 0;
                                    $barClass    = $pct >= 90 ? 'high' : ($pct >= 60 ? 'medium' : 'low');

                                    // Sisa hari deadline
                                    $deadlineObj  = $hasDeadline ? new DateTime($row['deadline']) : null;
                                    $daysLeft     = $deadlineObj ? (int)$now->diff($deadlineObj)->days * ($deadlineObj > $now ? 1 : -1) : null;
                                    $deadlineUrgent = $daysLeft !== null && $daysLeft <= 3;
                                ?>
                                <tr>
                                    <!-- Judul + chip info -->
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                        <div class="meta-chips">
                                            <?php if ($hasDeadline): ?>
                                                <span class="chip chip-deadline <?php echo $deadlineUrgent ? 'urgent' : ''; ?>">
                                                    <i class="fas fa-clock"></i>
                                                    <?php
                                                    if ($daysLeft === null) {
                                                        echo 'Batas: ' . date('d/m/Y', strtotime($row['deadline']));
                                                    } elseif ($daysLeft < 0) {
                                                        echo 'Berakhir ' . abs($daysLeft) . ' hari lalu';
                                                    } elseif ($daysLeft === 0) {
                                                        echo 'Berakhir hari ini!';
                                                    } else {
                                                        echo 'Sisa ' . $daysLeft . ' hari';
                                                    }
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($hasQuota): ?>
                                                <span class="chip chip-quota <?php echo $totalApply >= $quota ? 'full' : ''; ?>">
                                                    <i class="fas fa-users"></i>
                                                    <?php echo $totalApply; ?>/<?php echo $quota; ?> pendaftar
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($hasQuota): ?>
                                            <div class="quota-bar-wrap" title="<?php echo $pct; ?>% terisi">
                                                <div class="quota-bar <?php echo $barClass; ?>" style="width:<?php echo $pct; ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($row['close_reason'])): ?>
                                            <div class="close-reason">
                                                <i class="fas fa-info-circle"></i>
                                                <?php echo htmlspecialchars($row['close_reason']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                                    <td><?php echo htmlspecialchars($row['salary_range']); ?></td>
                                    <td>
                                        <?php if ($row['nama_jenjang']): ?>
                                            <span class="badge badge-info">
                                                <?php echo htmlspecialchars($row['nama_jenjang']);
                                                if ($row['nama_jurusan']) echo ' - ' . htmlspecialchars($row['nama_jurusan']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Tidak ada syarat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $isAutoClosed = !empty($row['close_reason']);
                                        $badgeClass = $row['status'] === 'open' ? 'success' : ($isAutoClosed ? 'auto' : 'secondary');
                                        ?>
                                        <span class="badge badge-<?php echo $badgeClass; ?>">
                                            <?php if ($row['status'] === 'open'): ?>
                                                <i class="fas fa-door-open"></i> Open
                                            <?php elseif ($isAutoClosed): ?>
                                                <i class="fas fa-robot"></i> Auto-Closed
                                            <?php else: ?>
                                                <i class="fas fa-door-closed"></i> Closed
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['posted_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['poster'] ?: '-'); ?></td>
                                    <td>
                                        <!-- Edit -->
                                        <button class="btn btn-sm btn-warning"
                                            onclick="openEdit(<?php echo (int)$row['job_id']; ?>, <?php echo htmlspecialchars(json_encode($row)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- Hapus -->
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus lowongan ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="job_id" value="<?php echo (int)$row['job_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                        <!-- Toggle Open/Close -->
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="job_id" value="<?php echo (int)$row['job_id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $row['status']==='open' ? 'closed' : 'open'; ?>">
                                            <button type="submit" class="btn btn-sm btn-secondary">
                                                <?php echo $row['status']==='open' ? '<i class="fas fa-lock"></i> Tutup' : '<i class="fas fa-lock-open"></i> Buka'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center">Anda Belum Membuat Lowongan</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         EDIT LOWONGAN MODAL
    ══════════════════════════════════════════ -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Edit Lowongan</h3>
            <form method="POST" id="editJobForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="job_id" id="edit_job_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Judul</label>
                        <input type="text" name="title" id="edit_title" required>
                    </div>
                    <div class="form-group">
                        <label>Lokasi</label>
                        <input type="text" name="location" id="edit_location">
                    </div>
                    <div class="form-group">
                        <label>Range Gaji</label>
                        <input type="text" name="salary_range" id="edit_salary_range">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status" required>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <!-- ── BATAS WAKTU ── -->
                    <div class="form-group">
                        <label><i class="fas fa-calendar-times"></i> Batas Waktu Pendaftaran</label>
                        <input type="datetime-local" name="deadline" id="edit_deadline">
                        <span class="form-hint">Kosongkan untuk menghapus batas waktu.</span>
                    </div>

                    <!-- ── KUOTA ── -->
                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Kuota Pendaftar</label>
                        <input type="number" name="quota" id="edit_quota" min="1" placeholder="Kosongkan = tidak terbatas">
                        <span class="form-hint">Kosongkan untuk menghapus batas kuota.</span>
                    </div>

                    <!-- Education Requirements -->
                    <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i> Syarat Pendidikan (Jenjang)</label>
                        <select name="req_jenjang" id="edit_req_jenjang" onchange="loadJurusanOptions('edit')">
                            <option value="">-- Tidak Ada Syarat --</option>
                            <?php
                            mysqli_data_seek($jenjang_list, 0);
                            while ($jenjang = mysqli_fetch_assoc($jenjang_list)): ?>
                                <option value="<?php echo $jenjang['id_jenjang']; ?>"
                                        data-punya-jurusan="<?php echo $jenjang['punya_jurusan']; ?>">
                                    <?php echo htmlspecialchars($jenjang['nama_jenjang']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group" id="edit_jurusan_group" style="display:none;">
                        <label><i class="fas fa-book"></i> Syarat Jurusan</label>
                        <select name="req_jurusan" id="edit_req_jurusan">
                            <option value="">-- Pilih Jurusan --</option>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label>Deskripsi</label>
                        <textarea name="description" id="edit_description" rows="3" required></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Persyaratan</label>
                        <textarea name="requirements" id="edit_requirements" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         EDIT POPUP MODAL
    ══════════════════════════════════════════ -->
    <div id="editPopupModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePopupModal()">&times;</span>
            <h3>Edit Popup Gambar</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_popup">
                <input type="hidden" name="popup_id" id="edit_popup_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Judul Popup</label>
                        <input type="text" name="popup_title" id="edit_popup_title" required>
                    </div>
                    <div class="form-group">
                        <label>Orientasi</label>
                        <select name="orientation" id="edit_popup_orientation" required>
                            <option value="vertical">Vertikal (Portrait)</option>
                            <option value="horizontal">Horizontal (Landscape)</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Ganti Gambar (kosongkan jika tidak ingin mengganti)</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="popup_image" id="edit_popup_image" accept="image/*">
                            <label for="edit_popup_image" class="file-input-display">
                                <i class="fas fa-cloud-upload-alt"></i><br>Klik untuk memilih gambar baru (opsional)
                            </label>
                        </div>
                        <div class="file-info">Format: JPG, PNG, GIF | Maksimal: 5MB</div>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closePopupModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/navbar.js"></script>
    <script>
        // ── Sidebar ──
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

        // ── Jurusan AJAX ──
        function loadJurusanOptions(formType) {
            const jenjangSelect  = document.getElementById(formType + '_req_jenjang');
            const jurusanGroup   = document.getElementById(formType + '_jurusan_group');
            const jurusanSelect  = document.getElementById(formType + '_req_jurusan');
            const selectedOption = jenjangSelect.options[jenjangSelect.selectedIndex];
            const jenjangId      = jenjangSelect.value;
            const punyaJurusan   = selectedOption.getAttribute('data-punya-jurusan');

            if (jenjangId && punyaJurusan == '1') {
                jurusanGroup.style.display = 'block';
                fetch('get_jurusan.php?jenjang_id=' + jenjangId)
                    .then(r => r.json())
                    .then(data => {
                        jurusanSelect.innerHTML = '<option value="">-- Pilih Jurusan --</option>';
                        data.forEach(j => {
                            const o = document.createElement('option');
                            o.value = j.id_jurusan;
                            o.textContent = j.nama_jurusan;
                            jurusanSelect.appendChild(o);
                        });
                    })
                    .catch(err => console.error('Error:', err));
            } else {
                jurusanGroup.style.display = 'none';
                jurusanSelect.innerHTML = '<option value="">-- Pilih Jurusan --</option>';
            }
        }

        // ── Edit Lowongan Modal ──
        function openEdit(jobId, data) {
            document.getElementById('edit_job_id').value      = jobId;
            document.getElementById('edit_title').value       = data.title       || '';
            document.getElementById('edit_location').value    = data.location    || '';
            document.getElementById('edit_salary_range').value = data.salary_range || '';
            document.getElementById('edit_status').value      = data.status      || 'open';
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_requirements').value = data.requirements || '';

            // Batas waktu — konversi dari MySQL datetime ke format datetime-local
            const dlVal = data.deadline ? data.deadline.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('edit_deadline').value = dlVal;

            // Kuota
            document.getElementById('edit_quota').value = data.quota || '';

            // Pendidikan
            const editJenjangSelect = document.getElementById('edit_req_jenjang');
            editJenjangSelect.value = data.req_jenjang_pendidikan || '';
            if (data.req_jenjang_pendidikan) {
                loadJurusanOptions('edit');
                setTimeout(() => {
                    document.getElementById('edit_req_jurusan').value = data.req_jurusan_pendidikan || '';
                }, 500);
            }

            document.getElementById('editModal').style.display = 'block';
        }
        function closeModal()      { document.getElementById('editModal').style.display = 'none'; }

        // ── Edit Popup Modal ──
        function openEditPopup(popupId, data) {
            document.getElementById('edit_popup_id').value          = popupId;
            document.getElementById('edit_popup_title').value       = data.title       || '';
            document.getElementById('edit_popup_orientation').value = data.orientation || 'vertical';
            document.getElementById('editPopupModal').style.display = 'block';
        }
        function closePopupModal() { document.getElementById('editPopupModal').style.display = 'none'; }

        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) { closeModal(); closePopupModal(); }
        };

        // ── File input label update ──
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[type="file"]').forEach(function(input) {
                input.addEventListener('change', function() {
                    const label = document.querySelector('label[for="' + this.id + '"]');
                    if (label && this.files && this.files[0]) {
                        const fn = this.files[0].name;
                        const fs = (this.files[0].size / 1024 / 1024).toFixed(2);
                        label.innerHTML = '<i class="fas fa-file-image"></i><br>' + fn + '<br><small>(' + fs + ' MB)</small>';
                    }
                });
            });
        });
    </script>
</body>
</html>