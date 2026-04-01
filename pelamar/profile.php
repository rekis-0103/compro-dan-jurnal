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

function logActivity($conn, $actor_user_id, $action)
{
    $actor_user_id = (int)$actor_user_id;
    $action = mysqli_real_escape_string($conn, $action);
    mysqli_query($conn, "INSERT INTO log_aktivitas (user_id, action) VALUES ($actor_user_id, '$action')");
}

// Handle CV view logging via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_cv_view'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

// ── Cek lamaran aktif ──
$activeAppQ = mysqli_query($conn, "
    SELECT a.application_id, l.title, a.status, a.applied_at
    FROM applications a
    JOIN lowongan l ON a.job_id = l.job_id
    WHERE a.user_id = $user_id
      AND a.status IN ('menunggu', 'pending', 'seleksi administrasi', 'lolos administrasi', 'tes & wawancara')
    ORDER BY a.applied_at DESC
    LIMIT 1
");
$isLocked       = $activeAppQ && mysqli_num_rows($activeAppQ) > 0;
$activeApp      = $isLocked ? mysqli_fetch_assoc($activeAppQ) : null;

// Fetch current profile with education info
$profile_q = mysqli_query($conn, "
    SELECT u.*, 
           jnj.nama_jenjang, jnj.kode_jenjang, jnj.punya_jurusan,
           jr.nama_jurusan
    FROM users u
    LEFT JOIN jenjang_pendidikan jnj ON u.id_jenjang_pendidikan = jnj.id_jenjang
    LEFT JOIN jurusan_pendidikan jr  ON u.id_jurusan_pendidikan  = jr.id_jurusan
    WHERE u.user_id=$user_id AND u.hapus=0
    LIMIT 1
");
$profile = $profile_q ? mysqli_fetch_assoc($profile_q) : null;

// Get all education levels for dropdown
$levels_result = mysqli_query($conn, "SELECT * FROM jenjang_pendidikan WHERE status=1 ORDER BY id_jenjang");

// ── Handle POST (hanya diproses jika profil tidak terkunci) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['log_cv_view'])) {

    // Blokir di level server jika terkunci
    if ($isLocked) {
        $error = 'Profil tidak dapat diubah selama ada lamaran yang sedang diproses.';
        logActivity($conn, $user_id, 'Gagal update profil (profil terkunci — ada lamaran aktif)');
    } else {
        $new_username    = mysqli_real_escape_string($conn, $_POST['username']);
        $new_full_name   = mysqli_real_escape_string($conn, $_POST['full_name']);
        $new_email       = mysqli_real_escape_string($conn, $_POST['email']);
        $new_no_telepon  = mysqli_real_escape_string($conn, $_POST['no_telepon']);
        $new_id_jenjang  = !empty($_POST['id_jenjang_pendidikan']) ? (int)$_POST['id_jenjang_pendidikan'] : null;
        $new_id_jurusan  = !empty($_POST['id_jurusan_pendidikan']) ? (int)$_POST['id_jurusan_pendidikan'] : null;
        $new_password    = isset($_POST['password']) ? trim($_POST['password']) : '';

        $changes = [];

        // Cek username unik
        $chk_username = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$new_username' AND user_id<>$user_id AND hapus=0 LIMIT 1");
        if ($chk_username && mysqli_num_rows($chk_username) > 0) {
            $error = 'Username sudah digunakan.';
            logActivity($conn, $user_id, 'Gagal update profil (username sudah digunakan)');
        } else {
            // Cek email unik
            $chk_email = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$new_email' AND user_id<>$user_id AND hapus=0 LIMIT 1");
            if ($chk_email && mysqli_num_rows($chk_email) > 0) {
                $error = 'Email sudah digunakan.';
                logActivity($conn, $user_id, 'Gagal update profil (email sudah digunakan)');
            } else {
                // Track perubahan
                if ($profile['username']   != $new_username)   $changes[] = "Username: '{$profile['username']}' → '$new_username'";
                if ($profile['full_name']  != $new_full_name)  $changes[] = "Nama: '{$profile['full_name']}' → '$new_full_name'";
                if ($profile['email']      != $new_email)      $changes[] = "Email: '{$profile['email']}' → '$new_email'";
                if ($profile['no_telepon'] != $new_no_telepon) {
                    $old_telp = $profile['no_telepon'] ?: 'belum diisi';
                    $changes[] = "No. Telepon: '$old_telp' → '$new_no_telepon'";
                }
                if ($profile['id_jenjang_pendidikan'] != $new_id_jenjang) {
                    $old_jenjang    = $profile['nama_jenjang'] ?: 'belum diisi';
                    $nj             = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_jenjang FROM jenjang_pendidikan WHERE id_jenjang=$new_id_jenjang"));
                    $new_jenjang_nm = $nj ? $nj['nama_jenjang'] : 'tidak dipilih';
                    $changes[] = "Jenjang Pendidikan: '$old_jenjang' → '$new_jenjang_nm'";
                }
                if ($profile['id_jurusan_pendidikan'] != $new_id_jurusan) {
                    $old_jurusan = $profile['nama_jurusan'] ?: 'belum diisi';
                    if ($new_id_jurusan) {
                        $nj2         = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_jurusan FROM jurusan_pendidikan WHERE id_jurusan=$new_id_jurusan"));
                        $new_jur_nm  = $nj2 ? $nj2['nama_jurusan'] : 'tidak dipilih';
                    } else {
                        $new_jur_nm  = 'tidak ada jurusan';
                    }
                    $changes[] = "Jurusan: '$old_jurusan' → '$new_jur_nm'";
                }

                // Upload CV
                $cv_update = '';
                if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['cv'];
                    if (!in_array($file['type'], ['application/pdf'])) {
                        $error = 'File harus berformat PDF.';
                        logActivity($conn, $user_id, 'Gagal upload CV (format tidak valid)');
                    } elseif ($file['size'] > 5 * 1024 * 1024) {
                        $error = 'Ukuran file maksimal 5MB.';
                        logActivity($conn, $user_id, 'Gagal upload CV (ukuran terlalu besar)');
                    } else {
                        $ext         = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $new_fn      = 'cv_' . $user_id . '_' . time() . '.' . $ext;
                        $upload_path = 'cv/' . $new_fn;
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            if (!empty($profile['cv_filename']) && file_exists('cv/' . $profile['cv_filename'])) {
                                unlink('cv/' . $profile['cv_filename']);
                                $changes[] = "CV: '{$profile['cv_filename']}' → '$new_fn'";
                            } else {
                                $changes[] = "CV: Upload CV baru '$new_fn'";
                            }
                            $cv_update = ", cv_filename='$new_fn'";
                        } else {
                            $error = 'Gagal mengupload CV.';
                            logActivity($conn, $user_id, 'Gagal upload CV (error memindahkan file)');
                        }
                    }
                }

                if ($new_password !== '') $changes[] = "Password diubah";

                if (!isset($error)) {
                    $set_pass   = $new_password !== '' ? ", password='" . md5($new_password) . "'" : '';
                    $edu_update = $new_id_jenjang !== null
                        ? ", id_jenjang_pendidikan=$new_id_jenjang, id_jurusan_pendidikan=" . ($new_id_jurusan ?? 'NULL')
                        : ", id_jenjang_pendidikan=NULL, id_jurusan_pendidikan=NULL";

                    $upd = mysqli_query($conn, "
                        UPDATE users SET
                            username='$new_username',
                            full_name='$new_full_name',
                            email='$new_email',
                            no_telepon='$new_no_telepon'
                            $edu_update
                            $cv_update
                            $set_pass
                        WHERE user_id=$user_id
                    ");

                    if ($upd) {
                        $success = 'Profil berhasil diperbarui';
                        $_SESSION['username']  = $new_username;
                        $_SESSION['full_name'] = $new_full_name;

                        $change_log = !empty($changes) ? implode(', ', $changes) : 'tidak ada perubahan';
                        logActivity($conn, $user_id, "Update profil: $change_log");

                        // Refresh profile
                        $profile_q = mysqli_query($conn, "
                            SELECT u.*, jnj.nama_jenjang, jnj.kode_jenjang, jnj.punya_jurusan, jr.nama_jurusan
                            FROM users u
                            LEFT JOIN jenjang_pendidikan jnj ON u.id_jenjang_pendidikan = jnj.id_jenjang
                            LEFT JOIN jurusan_pendidikan jr  ON u.id_jurusan_pendidikan  = jr.id_jurusan
                            WHERE u.user_id=$user_id AND u.hapus=0 LIMIT 1
                        ");
                        $profile = $profile_q ? mysqli_fetch_assoc($profile_q) : null;
                    } else {
                        $error = 'Gagal memperbarui profil';
                        logActivity($conn, $user_id, 'Gagal update profil (database error)');
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - PT Waindo Specterra</title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/profile.css">
    <style>
        /* ── Banner profil terkunci ── */
        .lock-banner {
            display: flex;
            align-items: flex-start;
            gap: 18px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 2px solid #fb923c;
            border-radius: 16px;
            padding: 22px 24px;
            margin-bottom: 28px;
            box-shadow: 0 4px 16px rgba(251,146,60,.18);
            animation: fadeInDown .4s ease;
        }
        @keyframes fadeInDown {
            from { opacity:0; transform:translateY(-12px); }
            to   { opacity:1; transform:translateY(0); }
        }
        .lock-banner-icon {
            flex-shrink: 0;
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(249,115,22,.35);
        }
        .lock-banner-icon i { font-size: 1.6rem; color: white; }
        .lock-banner-body h4 {
            margin: 0 0 6px;
            color: #9a3412;
            font-size: 1.05rem;
            font-weight: 700;
            display: flex; align-items: center; gap: 8px;
        }
        .lock-banner-body p {
            margin: 0 0 6px;
            color: #c2410c;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .lock-banner-body p:last-child { margin-bottom: 0; }
        .lock-banner-body strong { color: #7c2d12; }
        .lock-banner-link {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 10px;
            padding: 7px 16px;
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white; border-radius: 8px;
            font-size: 0.85rem; font-weight: 600;
            text-decoration: none;
            transition: all .2s ease;
            box-shadow: 0 3px 10px rgba(249,115,22,.3);
        }
        .lock-banner-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(249,115,22,.4);
        }

        /* ── Form terkunci ── */
        .form-locked {
            position: relative;
        }
        .form-locked .form-grid input,
        .form-locked .form-grid select,
        .form-locked .form-grid textarea,
        .form-locked .form-grid input[type="file"],
        .form-locked .form-grid input[type="password"] {
            background: #f1f5f9 !important;
            color: #94a3b8 !important;
            border-color: #e2e8f0 !important;
            cursor: not-allowed !important;
            pointer-events: none;
            user-select: none;
        }
        .form-locked .form-actions { display: none !important; }

        /* Label kunci di header card */
        .lock-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            border: 1px solid #fcd34d;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: .3px;
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
                <h3>Profil</h3>
                <p>Selamat datang, <?php echo htmlspecialchars($full_name); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profil</a></li>
                <li><a href="lowongan.php"><i class="fas fa-briefcase"></i> Lihat Lowongan</a></li>
                <li><a href="applications.php"><i class="fas fa-file-alt"></i> Lamaran Saya</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Beranda</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>Profil</h1>
                <p><?php echo $isLocked ? 'Data profil Anda (mode baca)' : 'Perbarui informasi akun Anda'; ?></p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($isLocked): ?>
            <!-- ── Banner terkunci ── -->
            <div class="lock-banner">
                <div class="lock-banner-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="lock-banner-body">
                    <h4><i class="fas fa-shield-alt"></i> Profil Terkunci Sementara</h4>
                    <p>
                        Anda memiliki lamaran yang sedang diproses untuk posisi
                        <strong>"<?php echo htmlspecialchars($activeApp['title']); ?>"</strong>
                        dengan status <strong><?php echo htmlspecialchars($activeApp['status']); ?></strong>.
                    </p>
                    <p>
                        Untuk menjaga konsistensi data lamaran, profil tidak dapat diubah selama proses seleksi berlangsung.
                        Profil akan bisa diedit kembali setelah lamaran <strong>ditolak</strong> atau Anda <strong>membatalkan lamaran</strong>.
                    </p>
                    <a href="applications.php" class="lock-banner-link">
                        <i class="fas fa-file-alt"></i> Lihat Status Lamaran
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- CV Card -->
            <?php if (!empty($profile['cv_filename']) && file_exists('cv/' . $profile['cv_filename'])): ?>
            <div class="card cv-card">
                <div class="card-header">
                    <h3><i class="fas fa-file-pdf"></i> CV Terdaftar</h3>
                </div>
                <div class="card-body">
                    <div class="cv-info">
                        <div class="cv-icon">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <div class="cv-details">
                            <h4><?php echo htmlspecialchars($profile['cv_filename']); ?></h4>
                            <p class="cv-meta">
                                <i class="fas fa-calendar"></i>
                                Diupload: <?php echo date('d M Y', filemtime('cv/' . $profile['cv_filename'])); ?>
                                <span class="separator">•</span>
                                <i class="fas fa-hdd"></i>
                                Ukuran: <?php echo number_format(filesize('cv/' . $profile['cv_filename']) / 1024, 2); ?> KB
                            </p>
                        </div>
                        <div class="cv-actions">
                            <a href="cv/<?php echo htmlspecialchars($profile['cv_filename']); ?>"
                               class="btn btn-view"
                               target="_blank"
                               onclick="logCVView()">
                                <i class="fas fa-eye"></i> Lihat CV
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Form Profil -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-<?php echo $isLocked ? 'lock' : 'user-edit'; ?>"></i>
                        <?php echo $isLocked ? 'Data Profil' : 'Edit Profil'; ?>
                    </h3>
                    <?php if ($isLocked): ?>
                        <span class="lock-badge"><i class="fas fa-lock"></i> Terkunci</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data"
                          class="<?php echo $isLocked ? 'form-locked' : ''; ?>"
                          <?php echo $isLocked ? 'onsubmit="return false;"' : ''; ?>>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Username <span class="required">*</span></label>
                                <input type="text" name="username"
                                       value="<?php echo htmlspecialchars($profile['username'] ?? $username); ?>"
                                       required <?php echo $isLocked ? 'readonly' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label>Nama Lengkap <span class="required">*</span></label>
                                <input type="text" name="full_name"
                                       value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>"
                                       required <?php echo $isLocked ? 'readonly' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label>Email <span class="required">*</span></label>
                                <input type="email" name="email"
                                       value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>"
                                       required <?php echo $isLocked ? 'readonly' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label>No. Telepon <span class="required">*</span></label>
                                <input type="text" name="no_telepon"
                                       value="<?php echo htmlspecialchars($profile['no_telepon'] ?? ''); ?>"
                                       required placeholder="08xxxxxxxxxx"
                                       <?php echo $isLocked ? 'readonly' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label>Jenjang Pendidikan <span class="required">*</span></label>
                                <select name="id_jenjang_pendidikan" id="jenjang_select"
                                        required
                                        <?php echo $isLocked ? 'disabled' : 'onchange="loadJurusan(this.value)"'; ?>>
                                    <option value="">-- Pilih Jenjang --</option>
                                    <?php
                                    mysqli_data_seek($levels_result, 0);
                                    while ($level = mysqli_fetch_assoc($levels_result)):
                                    ?>
                                    <option value="<?php echo $level['id_jenjang']; ?>"
                                            data-punya-jurusan="<?php echo $level['punya_jurusan']; ?>"
                                            <?php echo ($profile['id_jenjang_pendidikan'] == $level['id_jenjang']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($level['nama_jenjang']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group" id="jurusan_group" style="display:none;">
                                <label>Jurusan Pendidikan <span class="required">*</span></label>
                                <select name="id_jurusan_pendidikan" id="jurusan_select"
                                        <?php echo $isLocked ? 'disabled' : ''; ?>>
                                    <option value="">-- Pilih Jurusan --</option>
                                </select>
                            </div>

                            <?php if (!$isLocked): ?>
                            <div class="form-group full-width">
                                <label><i class="fas fa-file-pdf"></i> Upload CV Baru (PDF, Max 5MB)</label>
                                <input type="file" name="cv" accept=".pdf">
                                <small class="form-text">
                                    <i class="fas fa-info-circle"></i>
                                    <?php echo !empty($profile['cv_filename']) ? 'Upload file baru akan mengganti CV yang sudah ada' : 'Silakan upload CV Anda dalam format PDF'; ?>
                                </small>
                            </div>
                            <div class="form-group full-width">
                                <label><i class="fas fa-lock"></i> Password Baru</label>
                                <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah">
                                <small class="form-text">
                                    <i class="fas fa-info-circle"></i> Minimal 6 karakter, gunakan kombinasi huruf dan angka
                                </small>
                            </div>
                            <?php else: ?>
                            <!-- Saat terkunci, tampilkan CV & password sebagai info saja -->
                            <div class="form-group full-width">
                                <label><i class="fas fa-file-pdf"></i> CV</label>
                                <input type="text" value="<?php echo !empty($profile['cv_filename']) ? 'CV sudah terupload' : 'Belum ada CV'; ?>" readonly>
                            </div>
                            <div class="form-group full-width">
                                <label><i class="fas fa-lock"></i> Password</label>
                                <input type="password" value="••••••••" readonly>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Tombol hanya tampil saat tidak terkunci -->
                        <?php if (!$isLocked): ?>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                        <?php endif; ?>
                    </form>
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
                if (!sidebar.contains(event.target) && !mobileToggle.contains(event.target))
                    sidebar.classList.remove('active');
            }
        });

        function logCVView() {
            fetch('profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'log_cv_view=1'
            });
        }

        async function loadJurusan(idJenjang) {
            const jurusanGroup  = document.getElementById('jurusan_group');
            const jurusanSelect = document.getElementById('jurusan_select');
            const selectedOption = document.querySelector('#jenjang_select option:checked');
            const punyaJurusan  = selectedOption ? selectedOption.dataset.punyaJurusan : '0';

            if (punyaJurusan === '1' && idJenjang) {
                jurusanGroup.style.display = 'block';
                jurusanSelect.required = true;
                try {
                    const fd = new FormData();
                    fd.append('get_jurusan', '1');
                    fd.append('id_jenjang', idJenjang);
                    const res  = await fetch('get_jurusan.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    jurusanSelect.innerHTML = '<option value="">-- Pilih Jurusan --</option>';
                    data.forEach(j => {
                        const o   = document.createElement('option');
                        o.value   = j.id_jurusan;
                        o.textContent = j.nama_jurusan;
                        <?php if (!empty($profile['id_jurusan_pendidikan'])): ?>
                        if (j.id_jurusan == <?php echo $profile['id_jurusan_pendidikan']; ?>) o.selected = true;
                        <?php endif; ?>
                        jurusanSelect.appendChild(o);
                    });
                } catch(e) { console.error('Error loading jurusan:', e); }
            } else {
                jurusanGroup.style.display = 'none';
                jurusanSelect.required = false;
                jurusanSelect.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const jenjangSelect = document.getElementById('jenjang_select');
            if (jenjangSelect && jenjangSelect.value) loadJurusan(jenjangSelect.value);
        });
    </script>
</body>
</html>