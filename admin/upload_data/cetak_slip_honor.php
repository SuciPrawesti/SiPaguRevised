<?php
/**
 * CETAK SLIP HONOR DOSEN - SiPagu v2.0
 * Slip honor hanya bisa dicetak/diexport secara manual (tidak otomatis).
 * 
 * Lokasi: admin/upload_data/cetak_slip_honor.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/honor_helper.php';

$id_thd = (int)($_GET['id_thd'] ?? 0);
if (!$id_thd) {
    header('Location: ' . BASE_URL . 'admin/upload_data/hitung_honor.php');
    exit;
}

// Ambil data transaksi honor lengkap
$q = mysqli_query($koneksi, "
    SELECT thd.*,
           j.kode_matkul, j.nama_matkul, j.semester, j.jml_mhs,
           u.nama_user, u.npp_user, u.nik_user, u.npwp_user, u.norek_user, u.nohp_user,
           COALESCE(u.honor_persks, 50000) as honor_persks
    FROM t_transaksi_honor_dosen thd
    LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    LEFT JOIN t_user u ON j.id_user = u.id_user
    WHERE thd.id_thd = '$id_thd'
");
$h = mysqli_fetch_assoc($q);

if (!$h) {
    header('Location: ' . BASE_URL . 'admin/upload_data/hitung_honor.php');
    exit;
}

// Ambil detail pertemuan dalam periode
$pA = mysqli_real_escape_string($koneksi, $h['periode_awal']);
$pE = mysqli_real_escape_string($koneksi, $h['periode_akhir']);
$q_ptm = mysqli_query($koneksi, "
    SELECT * FROM t_pertemuan_dosen
    WHERE id_jadwal = '{$h['id_jadwal']}'
      AND tanggal BETWEEN '$pA' AND '$pE'
    ORDER BY tanggal ASC
");
$pertemuan_detail = [];
while ($row = mysqli_fetch_assoc($q_ptm)) {
    $pertemuan_detail[] = $row;
}

// Hitung pajak (PPh21: 5% untuk honor <= 50jt)
$total_honor = (int)$h['total_honor'];
$pajak_pph21 = (int)($total_honor * 0.05);
$honor_bersih = $total_honor - $pajak_pph21;

$badge = getStatusBadge($h['status']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Honor Dosen - <?= htmlspecialchars($h['nama_user'] ?? '') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            background: #f5f5f5;
        }
        .slip-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 30px;
        }
        .slip-header {
            text-align: center;
            border-bottom: 3px double #333;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .slip-header h2 {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .slip-header h3 {
            font-size: 13px;
            font-weight: normal;
        }
        .slip-header .slip-no {
            font-size: 11px;
            color: #666;
            margin-top: 6px;
        }
        .section-title {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            color: #555;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
            margin: 14px 0 8px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 6px;
            vertical-align: top;
        }
        .info-table td:first-child {
            width: 180px;
            color: #555;
        }
        .info-table td:nth-child(2) {
            width: 10px;
            color: #555;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .detail-table th {
            background: #f0f0f0;
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
            border: 1px solid #ccc;
        }
        .detail-table td {
            padding: 5px 8px;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        .detail-table tr:nth-child(even) { background: #fafafa; }
        .honor-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .honor-table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }
        .honor-table .total-row td {
            font-weight: bold;
            background: #f9f9f9;
        }
        .honor-table .bersih-row td {
            font-weight: bold;
            font-size: 13px;
            background: #e8f5e9;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-pending  { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .status-dibayar  { background: #d4edda; color: #155724; border: 1px solid #28a745; }
        .status-digabung { background: #cce5ff; color: #004085; border: 1px solid #007bff; }
        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .sign-box {
            text-align: center;
            width: 200px;
        }
        .sign-line {
            margin: 50px 0 5px;
            border-top: 1px solid #333;
        }
        .watermark-paid {
            text-align: center;
            margin-top: 10px;
            color: #28a745;
            font-weight: bold;
            font-size: 14px;
            letter-spacing: 1px;
        }
        .footer-note {
            margin-top: 16px;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }
        @media print {
            body { background: white; }
            .slip-container { margin: 0; border: none; padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Tombol Aksi (tidak ikut print) -->
<div class="no-print" style="max-width:800px;margin:10px auto;display:flex;gap:8px;">
    <button onclick="window.print()" style="padding:8px 20px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer;font-size:13px;">
        🖨️ Cetak / Print
    </button>
    <a href="<?= BASE_URL ?>admin/upload_data/hitung_honor.php" style="padding:8px 20px;background:#6c757d;color:white;border-radius:4px;text-decoration:none;font-size:13px;">
        ← Kembali
    </a>
    <span style="padding:8px;color:#666;font-size:12px;">
        💡 Slip ini hanya tersedia saat diminta secara manual (tidak digenerate otomatis)
    </span>
</div>

<div class="slip-container">
    <!-- HEADER -->
    <div class="slip-header">
        <h2>Universitas Dian Nuswantoro (UDINUS)</h2>
        <h3>Slip Honor Dosen Mengajar</h3>
        <div class="slip-no">
            No. Slip: THD-<?= str_pad($h['id_thd'], 5, '0', STR_PAD_LEFT) ?> &nbsp;|&nbsp;
            Dicetak: <?= date('d/m/Y H:i') ?> &nbsp;|&nbsp;
            Status:
            <span class="status-badge status-<?= $h['status'] ?>">
                <?= $badge['label'] ?>
            </span>
        </div>
    </div>

    <!-- INFO DOSEN -->
    <div class="section-title">Data Dosen</div>
    <table class="info-table">
        <tr><td>Nama</td><td>:</td><td><strong><?= htmlspecialchars($h['nama_user'] ?? '-') ?></strong></td></tr>
        <tr><td>NPP</td><td>:</td><td><?= htmlspecialchars($h['npp_user'] ?? '-') ?></td></tr>
        <tr><td>NIK</td><td>:</td><td><?= htmlspecialchars($h['nik_user'] ?? '-') ?></td></tr>
        <tr><td>NPWP</td><td>:</td><td><?= htmlspecialchars($h['npwp_user'] ?? '-') ?></td></tr>
        <tr><td>No. Rekening</td><td>:</td><td><?= htmlspecialchars($h['norek_user'] ?? '-') ?></td></tr>
    </table>

    <!-- INFO MATA KULIAH -->
    <div class="section-title">Data Mengajar</div>
    <table class="info-table">
        <tr><td>Kode / Nama MK</td><td>:</td><td><?= htmlspecialchars($h['kode_matkul'].' - '.$h['nama_matkul']) ?></td></tr>
        <tr><td>Semester</td><td>:</td><td><?= htmlspecialchars($h['semester'] ?? '-') ?></td></tr>
        <tr><td>Periode Honor</td><td>:</td><td><?= htmlspecialchars($h['periode_awal']) ?> s/d <?= htmlspecialchars($h['periode_akhir']) ?></td></tr>
        <tr><td>Label Bulan</td><td>:</td><td><?= ucfirst(htmlspecialchars($h['bulan'])) ?></td></tr>
        <tr><td>Jumlah Tatap Muka</td><td>:</td><td><?= $h['jml_tm'] ?> kali</td></tr>
        <tr><td>Total SKS</td><td>:</td><td><?= $h['sks_tempuh'] ?> SKS</td></tr>
        <tr><td>Honor per SKS</td><td>:</td><td><?= formatRupiahHonor($h['honor_persks']) ?></td></tr>
    </table>

    <!-- DETAIL PERTEMUAN -->
    <?php if (!empty($pertemuan_detail)): ?>
    <div class="section-title">Detail Pertemuan (<?= count($pertemuan_detail) ?> kali)</div>
    <table class="detail-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th class="text-center">SKS</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pertemuan_detail as $i => $p): ?>
            <tr>
                <td class="text-center"><?= $i+1 ?></td>
                <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                <td class="text-center"><?= $p['sks'] ?></td>
                <td><?= htmlspecialchars($p['keterangan'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- PERHITUNGAN HONOR -->
    <div class="section-title">Perhitungan Honor</div>
    <table class="honor-table">
        <tr>
            <td>Honor Bruto (<?= $h['sks_tempuh'] ?> SKS × <?= formatRupiahHonor($h['honor_persks']) ?>)</td>
            <td class="text-right"><?= formatRupiahHonor($total_honor) ?></td>
        </tr>
        <tr>
            <td>Pajak PPh 21 (5%)</td>
            <td class="text-right" style="color:#dc3545;">- <?= formatRupiahHonor($pajak_pph21) ?></td>
        </tr>
        <tr class="bersih-row">
            <td>Honor Bersih (Diterima)</td>
            <td class="text-right" style="color:#28a745;"><?= formatRupiahHonor($honor_bersih) ?></td>
        </tr>
    </table>

    <?php if ($h['catatan']): ?>
    <div style="margin-top:10px;padding:8px;background:#fff3cd;border:1px solid #ffc107;border-radius:4px;font-size:11px;">
        <strong>Catatan:</strong> <?= htmlspecialchars($h['catatan']) ?>
    </div>
    <?php endif; ?>

    <?php if ($h['status'] === 'dibayar'): ?>
    <div class="watermark-paid">✔ SUDAH DIBAYAR</div>
    <?php endif; ?>

    <!-- TANDA TANGAN -->
    <div class="signatures">
        <div class="sign-box">
            <p>Mengetahui,</p>
            <p>Koordinator</p>
            <div class="sign-line"></div>
            <p>( ........................... )</p>
        </div>
        <div class="sign-box">
            <p>Semarang, <?= date('d/m/Y') ?></p>
            <p>Dosen Ybs.</p>
            <div class="sign-line"></div>
            <p>( <?= htmlspecialchars($h['nama_user'] ?? '') ?> )</p>
        </div>
    </div>

    <div class="footer-note">
        * Slip ini digenerate secara manual oleh admin. Sistem SiPagu UDINUS v2.0.
        * Perhitungan pajak bersifat estimasi. Silakan konfirmasi ke bagian keuangan untuk angka pasti.
    </div>
</div>

</body>
</html>
