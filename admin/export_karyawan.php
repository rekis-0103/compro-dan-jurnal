<?php
session_start();
require_once '../connect/koneksi.php';
require_once('../vendor/setasign/fpdf/fpdf.php');

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get user data
$full_name = $_SESSION['full_name'];

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

// Query for employee data
$query = "SELECT 
    a.application_id,
    u.full_name,
    u.email,
    u.no_telepon,
    j.nama_jenjang,
    jr.nama_jurusan,
    l.title as posisi,
    a.start_date,
    a.employment_status
FROM applications a
INNER JOIN users u ON a.user_id = u.user_id
INNER JOIN lowongan l ON a.job_id = l.job_id
LEFT JOIN jenjang_pendidikan j ON a.id_jenjang_pendidikan = j.id_jenjang
LEFT JOIN jurusan_pendidikan jr ON a.id_jurusan_pendidikan = jr.id_jurusan
WHERE a.status = 'diterima bekerja'
$emp_where
ORDER BY a.start_date DESC";

$result = mysqli_query($conn, $query);

// Custom PDF class
class PDF extends FPDF
{
    private $exportedBy;
    
    function setExportedBy($name) {
        $this->exportedBy = $name;
    }

    function Header()
    {
        // Logo diperbesar
        if(file_exists('../assets/waindo.png')) {
            $this->Image('../assets/waindo.png', 15, 10, 24);
        }

        // Title
        $this->SetFont('Arial', 'B', 18);
        $this->SetX(55);
        $this->Cell(0, 10, 'PT Waindo SpecTerra', 0, 1);

        // Address
        $this->SetFont('Arial', '', 10);
        $this->SetX(55);
        $this->Cell(0, 5, 'Perkantoran Pejaten Raya No.7 - 8 No. 2', 0, 1);
        $this->SetX(55);
        $this->Cell(0, 5, 'Pasar Minggu, Jakarta Selatan', 0, 1);

        // Space
        $this->Ln(4);

        // Line
        $this->SetLineWidth(0.5);
        $this->Line(15, 38, 195, 38);
        $this->SetLineWidth(0.2);

        $this->Ln(6);

        // Export info
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Data diexport oleh: ' . $this->exportedBy, 0, 1);

        // Indonesian date format
        $bulan_indo = [
            'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
            'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
            'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
            'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
        ];
        $tanggal = date('d F Y');
        $tanggal = str_replace(array_keys($bulan_indo), array_values($bulan_indo), $tanggal);

        $this->Cell(0, 6, 'Tanggal: ' . $tanggal, 0, 1);

        $this->Ln(6);

        // Title table
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'DATA KARYAWAN', 0, 1, 'C');

        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetLineWidth(0.5);
        $this->Line(15, 272, 195, 272);
        $this->SetLineWidth(0.2);

        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Create PDF
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->setExportedBy($full_name);
$pdf->AddPage();

// Table Header
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(37, 99, 235);
$pdf->SetTextColor(255, 255, 255);

$pdf->Cell(8, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(36, 8, 'Nama Lengkap', 1, 0, 'C', true);
$pdf->Cell(26, 8, 'Telepon', 1, 0, 'C', true);
$pdf->Cell(22, 8, 'Pendidikan', 1, 0, 'C', true);
$pdf->Cell(38, 8, 'Jurusan', 1, 0, 'C', true);
$pdf->Cell(28, 8, 'Posisi', 1, 0, 'C', true);
$pdf->Cell(22, 8, 'Status', 1, 1, 'C', true);

// Table data
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$no = 1;

while ($row = mysqli_fetch_assoc($result)) {
    $pendidikan = $row['nama_jenjang'] ?? '-';
    $jurusan = $row['nama_jurusan'] ?? '-';
    $es = $row['employment_status'] ?? null;
    $status_label = ($es === 'non_aktif') ? 'Non-aktif' : 'Aktif';

    $pdf->Cell(8, 8, $no++, 1, 0, 'C');
    $pdf->Cell(36, 8, $row['full_name'], 1, 0);
    $pdf->Cell(26, 8, $row['no_telepon'] ?? '-', 1, 0, 'C');
    $pdf->Cell(22, 8, $pendidikan, 1, 0, 'C');
    $pdf->Cell(38, 8, $jurusan, 1, 0);
    $pdf->Cell(28, 8, $row['posisi'], 1, 0);
    $pdf->Cell(22, 8, $status_label, 1, 1, 'C');
}

// Signature area
$pdf->Ln(20);

$pdf->SetFont('Arial', '', 11);

$bulan_indo = [
    'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
    'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
    'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
    'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
];
$tanggal_ttd = date('d F Y');
$tanggal_ttd = str_replace(array_keys($bulan_indo), array_values($bulan_indo), $tanggal_ttd);

// Hitung posisi kanan
$rightX = 195 - 60; // 195 = right margin PDF, 60 = lebar kolom tanda tangan

// Tanggal rata kanan
$pdf->SetX($rightX);
$pdf->Cell(60, 6, 'Jakarta, ' . $tanggal_ttd, 0, 1, 'R');

// Space tanda tangan
$pdf->Ln(18);

// Nama tepat di bawah tanggal (sejajar)
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetX($rightX);
$pdf->Cell(60, 6, $full_name, 0, 1, 'C');   // 'C' = center relatif terhadap 60mm area


// Output
$pdf->Output('I', 'Data_Karyawan_' . date('Y-m-d') . '.pdf');
?>
