<?php
/**
 * CETAK SLIP HONOR - SiPagu Staff
 * Halaman print-only (tanpa navbar/sidebar)
 * Lokasi: staff/cetak_slip.php
 */
if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/function_helper.php';
require_once __DIR__ . '/../includes/honor_helper.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
$q_user  = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$id_user'");
$user    = mysqli_fetch_assoc($q_user);

$jenis     = $_GET['jenis']  ?? '';
$id        = (int)($_GET['id']    ?? 0);
$sel_bulan = $_GET['bulan']  ?? '';
$sel_tahun = (int)($_GET['tahun'] ?? date('Y'));

$honor_mengajar = [];
$honor_pata     = [];
$total_all      = 0;
$judul_periode  = '';

// Tentukan URL kembali berdasarkan konteks
if ($jenis && $id) {
    // Dari halaman detail_slip
    $back_url = "detail_slip.php?jenis=" . urlencode($jenis) . "&id=" . $id;
} else {
    // Dari halaman slip_honor
    $back_url = "slip_honor.php";
}

if ($jenis && $id) {
    $judul_periode = htmlspecialchars($jenis) . " #$id";

    if ($jenis === 'Honor Mengajar') {
        $q = mysqli_query($koneksi,
            "SELECT thd.*, j.kode_matkul, j.nama_matkul,
                    COALESCE(u.honor_persks,50000) as honor_persks
             FROM t_transaksi_honor_dosen thd
             JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
             JOIN t_user u ON j.id_user = u.id_user
             WHERE thd.id_thd = '$id' AND j.id_user = '$id_user'"
        );
        if ($r = mysqli_fetch_assoc($q)) {
            $honor_mengajar[] = [
                'matkul' => $r['nama_matkul'], 'kode' => $r['kode_matkul'],
                'sks' => $r['sks_tempuh'], 'jml_tm' => $r['jml_tm'],
                'honor' => (int)$r['total_honor'],
            ];
            $total_all += (int)$r['total_honor'];
            $judul_periode = ucfirst($r['bulan']) . ' (ID #' . $id . ')';
        }
    }

} elseif ($sel_bulan) {
    $judul_periode = ucfirst($sel_bulan) . ' ' . $sel_tahun;
    $bl_esc = mysqli_real_escape_string($koneksi, $sel_bulan);

    $q_m = mysqli_query($koneksi,
        "SELECT thd.*, j.kode_matkul, j.nama_matkul,
                COALESCE(u.honor_persks,50000) as honor_persks
         FROM t_transaksi_honor_dosen thd
         JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
         JOIN t_user u ON j.id_user = u.id_user
         WHERE j.id_user = '$id_user' AND thd.bulan = '$bl_esc'
           AND YEAR(thd.periode_akhir) = '$sel_tahun'
           AND thd.status != 'digabung'"
    );
    while ($r = mysqli_fetch_assoc($q_m)) {
        $honor_mengajar[] = [
            'matkul' => $r['nama_matkul'], 'kode' => $r['kode_matkul'],
            'sks' => $r['sks_tempuh'], 'jml_tm' => $r['jml_tm'],
            'honor' => (int)$r['total_honor'],
        ];
        $total_all += (int)$r['total_honor'];
    }

    $q_p = mysqli_query($koneksi,
        "SELECT tpt.*, p.jbtn_pnt, p.honor_std
         FROM t_transaksi_pa_ta tpt
         JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
         WHERE tpt.id_user = '$id_user' AND tpt.periode_wisuda = '$bl_esc'"
    );
    while ($r = mysqli_fetch_assoc($q_p)) {
        $honor_pata[] = ['jabatan' => $r['jbtn_pnt'], 'honor' => (int)$r['honor_std']];
        $total_all   += (int)$r['honor_std'];
    }
}

$hitung = hitungHonorStaff($total_all);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Slip Honor — <?= $judul_periode ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #333; background: #f0f2f5; }

        /* ===== ACTION BAR (no-print) ===== */
        .action-bar {
            max-width: 820px;
            margin: 16px auto 12px;
            display: flex;
            gap: 8px;
            align-items: center;
            padding: 0 4px;
        }
        .action-bar .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            background: #1d4ed8;
            color: #fff;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        .action-bar .btn-print:hover { background: #1e40af; }
        .action-bar .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #fff;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        .action-bar .btn-back:hover { background: #f9fafb; color: #111827; }
        .action-bar .hint {
            margin-left: 4px;
            font-size: 12px;
            color: #6b7280;
        }

        /* ===== SLIP DOCUMENT ===== */
        .wrap {
            max-width: 820px;
            margin: 0 auto 24px;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 32px 36px;
        }
        .hdr {
            text-align: center;
            border-bottom: 3px double #333;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .hdr h2 { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; }
        .hdr p { font-size: 11px; color: #555; margin-top: 5px; }

        .sec-title {
            font-weight: bold;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #444;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
            margin: 16px 0 9px;
        }

        .info-tbl { width: 100%; border-collapse: collapse; }
        .info-tbl td { padding: 3px 6px; vertical-align: top; font-size: 11.5px; }
        .info-tbl td:first-child { width: 130px; color: #555; }
        .info-tbl td:nth-child(2) { width: 14px; color: #888; }

        .det-tbl { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        .det-tbl th {
            background: #f3f4f6;
            padding: 6px 9px;
            text-align: left;
            border: 1px solid #d1d5db;
            font-weight: 700;
            font-size: 10.5px;
        }
        .det-tbl td { padding: 5px 9px; border: 1px solid #e5e7eb; }
        .det-tbl tr:nth-child(even) td { background: #f9fafb; }

        .honor-tbl { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .honor-tbl td { padding: 6px 9px; border: 1px solid #e5e7eb; font-size: 11.5px; }
        .honor-tbl tr.bersih td {
            font-weight: bold;
            font-size: 12.5px;
            background: #ecfdf5;
            border-color: #a7f3d0;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .sigs {
            margin-top: 32px;
            display: flex;
            justify-content: space-between;
        }
        .sign-box { text-align: center; width: 200px; }
        .sign-line { margin: 52px 0 5px; border-top: 1px solid #374151; }

        .note {
            margin-top: 18px;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            padding-top: 10px;
        }

        @media print {
            body { background: #fff; font-size: 11.5px; }
            .wrap { margin: 0; border: none; border-radius: 0; padding: 20px 24px; }
            .action-bar { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Action Bar (hidden on print) -->
<div class="action-bar">
    <button onclick="window.print()" class="btn-print">
        🖨️ Cetak / Print
    </button>
    <a href="<?= htmlspecialchars($back_url) ?>" class="btn-back">
        ← Kembali
    </a>
    <span class="hint">💡 Gunakan Ctrl+P untuk cetak langsung</span>
</div>

<!-- Slip Document -->
<div class="wrap">
    <div class="hdr">
        <h2>Universitas Dian Nuswantoro (UDINUS)</h2>
        <p>Slip Honor Dosen — <?= $judul_periode ?></p>
        <p>Dicetak: <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="sec-title">Data Dosen</div>
    <table class="info-tbl">
        <tr><td>Nama</td>        <td>:</td><td><strong><?= htmlspecialchars($user['nama_user']) ?></strong></td></tr>
        <tr><td>NPP</td>         <td>:</td><td><?= htmlspecialchars($user['npp_user']) ?></td></tr>
        <tr><td>NIK</td>         <td>:</td><td><?= htmlspecialchars($user['nik_user']) ?></td></tr>
        <tr><td>NPWP</td>        <td>:</td><td><?= htmlspecialchars($user['npwp_user']) ?></td></tr>
        <tr><td>No. Rekening</td><td>:</td><td><?= htmlspecialchars($user['norek_user']) ?></td></tr>
    </table>

    <?php if (!empty($honor_mengajar)): ?>
    <div class="sec-title">Honor Mengajar</div>
    <table class="det-tbl">
        <thead>
            <tr>
                <th>Mata Kuliah</th>
                <th>Kode</th>
                <th class="text-center">SKS</th>
                <th class="text-center">TM</th>
                <th class="text-right">Honor</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($honor_mengajar as $m): ?>
        <tr>
            <td><?= htmlspecialchars($m['matkul']) ?></td>
            <td><?= htmlspecialchars($m['kode']) ?></td>
            <td class="text-center"><?= $m['sks'] ?></td>
            <td class="text-center"><?= $m['jml_tm'] ?>x</td>
            <td class="text-right"><strong><?= formatRupiah($m['honor']) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if (!empty($honor_pata)): ?>
    <div class="sec-title">Honor PA/TA</div>
    <table class="det-tbl">
        <thead>
            <tr><th>Jabatan</th><th class="text-right">Honor</th></tr>
        </thead>
        <tbody>
        <?php foreach ($honor_pata as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['jabatan']) ?></td>
            <td class="text-right"><?= formatRupiah($p['honor']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="sec-title">Perhitungan Honor</div>
    <table class="honor-tbl">
        <tr>
            <td>Honor Bruto</td>
            <td class="text-right"><?= formatRupiah($hitung['nominal']) ?></td>
        </tr>
        <tr>
            <td>Pajak PPh21 (5%)</td>
            <td class="text-right" style="color:#dc2626;">- <?= formatRupiah($hitung['pajak']) ?></td>
        </tr>
        <tr>
            <td>Sisa</td>
            <td class="text-right"><?= formatRupiah($hitung['sisa']) ?></td>
        </tr>
        <tr>
            <td>Potongan (5%)</td>
            <td class="text-right" style="color:#d97706;">- <?= formatRupiah($hitung['potongan']) ?></td>
        </tr>
        <tr class="bersih">
            <td>Honor Bersih (Diterima)</td>
            <td class="text-right" style="color:#059669;"><?= formatRupiah($hitung['bersih']) ?></td>
        </tr>
    </table>

    <div class="sigs">
        <div class="sign-box">
            <p>Mengetahui, Koordinator</p>
            <div class="sign-line"></div>
            <p>( ...................... )</p>
        </div>
        <div class="sign-box">
            <p>Semarang, <?= date('d/m/Y') ?></p>
            <p>Dosen Ybs.</p>
            <div class="sign-line"></div>
            <p>( <?= htmlspecialchars($user['nama_user']) ?> )</p>
        </div>
    </div>

    <div class="note">
        * Slip ini digenerate manual oleh dosen. Angka pajak bersifat estimasi. SiPagu UDINUS v2.0.
    </div>
</div>

</body>
</html>
