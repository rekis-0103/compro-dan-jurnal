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

$list = mysqli_query($conn, "SELECT a.*, l.title FROM applications a JOIN lowongan l ON a.job_id=l.job_id WHERE a.user_id=$user_id ORDER BY a.applied_at DESC");

// Status yang boleh dibatalkan
$cancellable_statuses = ['menunggu', 'pending'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lamaran Saya - PT Waindo Specterra</title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/pages.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/applications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* === TOAST NOTIFICATION === */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 14px 20px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.4s ease;
            max-width: 340px;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast.success {
            background: linear-gradient(135deg, #059669, #10b981);
        }

        .toast.error {
            background: linear-gradient(135deg, #dc2626, #ef4444);
        }

        .toast.warning {
            background: linear-gradient(135deg, #d97706, #f59e0b);
        }

        .toast i {
            font-size: 1.1rem;
        }

        /* === MODAL KONFIRMASI === */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: white;
            border-radius: 20px;
            padding: 32px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
            animation: modalIn 0.3s ease;
        }

        @keyframes modalIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #fee2e2, #fca5a5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .modal-icon i {
            color: #dc2626;
            font-size: 1.6rem;
        }

        .modal-box h4 {
            text-align: center;
            color: #1e293b;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .modal-box p {
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }

        .modal-job-title {
            text-align: center;
            font-weight: 700;
            color: #1e293b;
            font-size: 1rem;
            background: #f1f5f9;
            padding: 10px 16px;
            border-radius: 10px;
            margin: 12px 0 24px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .btn-cancel-confirm {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .btn-cancel-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35);
        }

        .btn-close-modal {
            flex: 1;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            color: #475569;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .btn-close-modal:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        /* === TOMBOL BATALKAN === */
        .btn-batalkan {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #fee2e2, #fca5a5);
            color: #dc2626;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-batalkan:hover {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(220, 38, 38, 0.3);
        }

        /* Status tidak bisa dibatalkan */
        .status-locked {
            color: #94a3b8;
            font-size: 12px;
            font-style: italic;
        }
    </style>
</head>

<body>
    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toast-msg"></span>
    </div>

    <!-- Modal Konfirmasi Batalkan -->
    <div class="modal-overlay" id="cancelModal">
        <div class="modal-box">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h4>Batalkan Lamaran?</h4>
            <p>Anda yakin ingin membatalkan lamaran untuk posisi:</p>
            <div class="modal-job-title" id="modal-job-title">-</div>
            <p style="color:#ef4444; font-size:0.85rem;">
                <i class="fas fa-info-circle"></i> Data lamaran akan dihapus permanen dan tidak dapat dikembalikan.
            </p>
            <div class="modal-actions">
                <button class="btn-close-modal" onclick="closeModal()">
                    <i class="fas fa-times"></i> Tidak
                </button>
                <form id="cancelForm" method="POST" action="cancel_application.php" style="flex:1; margin:0;">
                    <input type="hidden" name="application_id" id="modal-app-id">
                    <button type="submit" class="btn-cancel-confirm" style="width:100%;">
                        <i class="fas fa-trash-alt"></i> Ya, Batalkan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="dashboard-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Lamaran Saya</h3>
                <p>Selamat datang, <?php echo htmlspecialchars($full_name); ?></p>
            </div>

            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profil</a></li>
                <li><a href="lowongan.php"><i class="fas fa-briefcase"></i> Lihat Lowongan</a></li>
                <li><a href="applications.php" class="active"><i class="fas fa-file-alt"></i> Lamaran Saya</a></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Beranda</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="dashboard-header">
                <h1>Lamaran Saya</h1>
                <p>Detail status lamaran yang telah Anda kirim</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Lamaran</h3>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Posisi</th>
                                <th>Status</th>
                                <th>Tgl Lamar</th>
                                <th>Alasan HRD</th>
                                <th>Jadwal Interview</th>
                                <th>Tgl Mulai Bekerja</th>
                                <th>CV</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list && mysqli_num_rows($list) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($list)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['applied_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['reason'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['interview_date'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['start_date'] ?: '-'); ?></td>
                                        <td>
                                            <?php if (!empty($row['cv'])): ?>
                                                <a href="cv/<?php echo htmlspecialchars($row['cv']); ?>" target="_blank">Lihat</a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (in_array(strtolower($row['status']), $cancellable_statuses)): ?>
                                                <button class="btn-batalkan" onclick="openModal(<?php echo $row['application_id']; ?>, '<?php echo addslashes(htmlspecialchars($row['title'])); ?>')">
                                                    <i class="fas fa-times-circle"></i> Batalkan
                                                </button>
                                            <?php else: ?>
                                                <span class="status-locked" title="Tidak dapat dibatalkan karena sudah diproses">
                                                    <i class="fas fa-lock"></i> —
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Belum ada lamaran</td>
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
        // === SIDEBAR ===
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

        // === MODAL ===
        function openModal(appId, jobTitle) {
            document.getElementById('modal-app-id').value = appId;
            document.getElementById('modal-job-title').textContent = jobTitle;
            document.getElementById('cancelModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('cancelModal').classList.remove('active');
        }
        // Tutup modal jika klik luar kotak
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // === TOAST NOTIFICATION ===
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-times-circle',
                warning: 'fa-exclamation-triangle'
            };
            toast.className = `toast ${type}`;
            toast.querySelector('i').className = `fas ${icons[type] || icons.success}`;
            document.getElementById('toast-msg').textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 4000);
        }

        // Tampilkan toast berdasarkan query string
        (function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('success') === 'cancelled') {
                showToast('Lamaran berhasil dibatalkan.', 'success');
            } else if (params.get('error') === 'cannot_cancel') {
                showToast('Lamaran tidak dapat dibatalkan karena sudah diproses HRD.', 'warning');
            } else if (params.get('error') === 'not_found') {
                showToast('Lamaran tidak ditemukan.', 'error');
            } else if (params.get('error') === 'delete_failed') {
                showToast('Gagal membatalkan lamaran. Coba lagi.', 'error');
            } else if (params.get('error') === 'invalid_request') {
                showToast('Permintaan tidak valid.', 'error');
            }
            // Bersihkan query string dari URL tanpa reload
            if (params.has('success') || params.has('error')) {
                window.history.replaceState({}, '', window.location.pathname);
            }
        })();
    </script>
</body>

</html>