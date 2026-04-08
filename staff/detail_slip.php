<?php
/**
 * DETAIL SLIP HONOR STAFF - SiPagu
 * Modern Minimalist Design
 */
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/function_helper.php';
require_once __DIR__ . '/../includes/honor_helper.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: " . BASE_URL . "index.php"); exit();
}

$id_user = (int)$_SESSION['id_user'];
$jenis   = $_GET['jenis'] ?? '';
$id      = (int)($_GET['id'] ?? 0);

if (!$jenis || !$id) { header("Location: riwayat_honor.php"); exit(); }

$q_user = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$id_user'");
$user   = mysqli_fetch_assoc($q_user);

$detail  = null;
$rincian = [];
$nominal = 0;

if ($jenis === 'Honor Mengajar') {
    $q = mysqli_query($koneksi,
        "SELECT thd.*, j.kode_matkul, j.nama_matkul, j.jml_mhs,
                COALESCE(u.honor_persks,50000) as honor_persks
         FROM t_transaksi_honor_dosen thd
         JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
         JOIN t_user u ON j.id_user = u.id_user
         WHERE thd.id_thd = '$id' AND j.id_user = '$id_user'"
    );
    $detail = mysqli_fetch_assoc($q);
    if ($detail) {
        $nominal = (int)$detail['total_honor'];
        $rincian = [
            ['label' => 'Mata Kuliah',     'value' => $detail['nama_matkul'],   'icon' => 'fas fa-book-open', 'color' => '#003d7a'],
            ['label' => 'Kode MK',         'value' => $detail['kode_matkul'],   'icon' => 'fas fa-hashtag',   'color' => '#718096'],
            ['label' => 'SKS',             'value' => $detail['sks_tempuh'],    'icon' => 'fas fa-star',      'color' => '#f59e0b'],
            ['label' => 'Jumlah TM',       'value' => $detail['jml_tm'] . ' kali', 'icon' => 'fas fa-calendar-check', 'color' => '#10b981'],
            ['label' => 'Honor per SKS',   'value' => formatRupiah($detail['honor_persks']), 'icon' => 'fas fa-money-bill', 'color' => '#3b82f6'],
            ['label' => 'Total Mahasiswa', 'value' => $detail['jml_mhs'] . ' orang', 'icon' => 'fas fa-users', 'color' => '#8b5cf6'],
            ['label' => 'Periode',         'value' => ($detail['periode_awal']??'-').' s/d '.($detail['periode_akhir']??'-'), 'icon' => 'fas fa-calendar-alt', 'color' => '#14b8a6'],
            ['label' => 'Status',          'value' => ucfirst($detail['status']), 'icon' => 'fas fa-info-circle', 'color' => '#718096'],
        ];
    }
} elseif ($jenis === 'Honor PA/TA') {
    $q = mysqli_query($koneksi,
        "SELECT tpt.*, p.jbtn_pnt, p.honor_std
         FROM t_transaksi_pa_ta tpt
         JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
         WHERE tpt.id_tpt = '$id' AND tpt.id_user = '$id_user'"
    );
    $detail = mysqli_fetch_assoc($q);
    if ($detail) {
        $nominal = (int)$detail['honor_std'];
        $rincian = [
            ['label' => 'Jabatan',        'value' => $detail['jbtn_pnt'],                   'icon' => 'fas fa-id-badge',    'color' => '#003d7a'],
            ['label' => 'Prodi',          'value' => $detail['prodi'],                       'icon' => 'fas fa-university',  'color' => '#3b82f6'],
            ['label' => 'Mhs Prodi',      'value' => $detail['jml_mhs_prodi'].' orang',      'icon' => 'fas fa-users',       'color' => '#8b5cf6'],
            ['label' => 'Mhs Bimbingan',  'value' => $detail['jml_mhs_bimbingan'].' orang',  'icon' => 'fas fa-user-graduate','color' => '#10b981'],
            ['label' => 'PGJI 1',         'value' => $detail['jml_pgji_1'].' orang',         'icon' => 'fas fa-user',        'color' => '#f59e0b'],
            ['label' => 'PGJI 2',         'value' => ($detail['jml_pgji_2']??0).' orang',    'icon' => 'fas fa-user',        'color' => '#f59e0b'],
            ['label' => 'Ketua PGJI',     'value' => $detail['ketua_pgji'],                  'icon' => 'fas fa-user-tie',    'color' => '#ef4444'],
            ['label' => 'Periode Wisuda', 'value' => ucfirst($detail['periode_wisuda']),      'icon' => 'fas fa-graduation-cap','color' => '#14b8a6'],
        ];
    }
} elseif ($jenis === 'Honor Ujian') {
    $q = mysqli_query($koneksi,
        "SELECT tu.*, p.jbtn_pnt, p.honor_std
         FROM t_transaksi_ujian tu
         JOIN t_panitia p ON tu.id_panitia = p.id_pnt
         WHERE tu.id_tu = '$id' AND tu.id_user = '$id_user'"
    );
    $detail = mysqli_fetch_assoc($q);
    if ($detail) {
        $nominal = (int)$detail['honor_std'];
        $rincian = [
            ['label' => 'Jabatan',           'value' => $detail['jbtn_pnt'],         'icon' => 'fas fa-id-badge',   'color' => '#003d7a'],
            ['label' => 'Semester',          'value' => $detail['semester'],          'icon' => 'fas fa-calendar',   'color' => '#3b82f6'],
            ['label' => 'Jml Mhs Prodi',     'value' => $detail['jml_mhs_prodi'],    'icon' => 'fas fa-users',      'color' => '#8b5cf6'],
            ['label' => 'Jml Mhs',           'value' => $detail['jml_mhs'],          'icon' => 'fas fa-user',       'color' => '#10b981'],
            ['label' => 'Koreksi',           'value' => $detail['jml_koreksi'],      'icon' => 'fas fa-pen',        'color' => '#f59e0b'],
            ['label' => 'Matkul',            'value' => $detail['jml_matkul'],       'icon' => 'fas fa-book',       'color' => '#14b8a6'],
            ['label' => 'Pengawas Pagi',     'value' => $detail['jml_pgws_pagi'],    'icon' => 'fas fa-sun',        'color' => '#ef4444'],
            ['label' => 'Pengawas Sore',     'value' => $detail['jml_pgws_sore'],    'icon' => 'fas fa-moon',       'color' => '#6366f1'],
            ['label' => 'Koordinator Pagi',  'value' => $detail['jml_koor_pagi'],    'icon' => 'fas fa-user-cog',   'color' => '#ef4444'],
            ['label' => 'Koordinator Sore',  'value' => $detail['jml_koor_sore'],    'icon' => 'fas fa-user-cog',   'color' => '#6366f1'],
        ];
    }
}

if (!$detail) { header("Location: riwayat_honor.php"); exit(); }

$perhitungan = hitungHonorStaff($nominal);

$jenisColor = [
    'Honor Mengajar' => ['#003d7a', 'rgba(0,61,122,0.1)'],
    'Honor PA/TA'    => ['#059669', 'rgba(5,150,105,0.1)'],
    'Honor Ujian'    => ['#d97706', 'rgba(217,119,6,0.1)'],
];
$jc = $jenisColor[$jenis] ?? ['#718096','#f1f5f9'];
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
<section class="section">

    <!-- Section Header -->
    <div class="section-header pt-4 pb-0">
        <div class="d-flex align-items-center justify-content-between w-100 flex-wrap" style="gap:12px;">
            <div>
                <h1 class="h3 mb-1" style="font-weight:700;color:#1a1a2e;">Detail Slip Honor</h1>
                <div style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:#a0aec0;margin-top:4px;">
                    <a href="<?= BASE_URL ?>staff/index.php"  style="color:#79a1c0;text-decoration:none;">Dashboard</a>
                    <i class="fas fa-chevron-right" style="font-size:.6rem;"></i>
                    <a href="riwayat_honor.php" style="color:#79a1c0;text-decoration:none;">Riwayat Honor</a>
                    <i class="fas fa-chevron-right" style="font-size:.6rem;"></i>
                    <span>Detail #<?= $id ?></span>
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <a href="riwayat_honor.php" class="btn btn-sm" style="background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-weight:600;height:36px;display:flex;align-items:center;">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
                <a href="cetak_slip.php?jenis=<?= urlencode($jenis) ?>&id=<?= $id ?>"
                   class="btn btn-danger btn-sm" target="_blank"
                   style="border-radius:8px;font-weight:600;height:36px;display:flex;align-items:center;">
                    <i class="fas fa-print mr-2"></i>Cetak
                </a>
            </div>
        </div>
    </div>

    <div class="section-body mt-4">
        <div class="row">

            <!-- Detail Kiri -->
            <div class="col-12 col-lg-8 mb-4">
                <div class="content-card content-card-primary" style="border-radius:16px;overflow:hidden;">
                    <!-- Card Header -->
                    <div style="padding:18px 20px;border-bottom:1px solid #eef2f7;display:flex;align-items:center;gap:12px;">
                        <span style="background:<?= $jc[1] ?>;color:<?= $jc[0] ?>;padding:4px 12px;border-radius:999px;font-size:.72rem;font-weight:700;">
                            <?= htmlspecialchars($jenis) ?>
                        </span>
                        <span style="font-size:.875rem;color:#718096;">Transaksi #<?= $id ?></span>
                    </div>

                    <div style="padding:24px;">
                        <!-- Info Dosen -->
                        <h6 style="font-size:.78rem;font-weight:700;color:#003d7a;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-user-circle"></i> Data Dosen
                        </h6>
                        <div style="background:#f8fafc;border:1px solid #eef2f7;border-radius:12px;padding:16px 20px;margin-bottom:24px;">
                            <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #eef2f7;">
                                <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#003d7a,#3b82f6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1rem;flex-shrink:0;">
                                    <?= strtoupper(mb_substr($user['nama_user'] ?? 'S', 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight:700;color:#1a1a2e;font-size:.95rem;"><?= htmlspecialchars($user['nama_user']) ?></div>
                                    <div style="font-size:.78rem;color:#718096;">NPP: <?= htmlspecialchars($user['npp_user']) ?></div>
                                </div>
                            </div>
                            <div class="row" style="margin:0;">
                                <div class="col-md-6" style="padding:0 8px 0 0;">
                                    <?php
                                    $dl = ['NIK' => $user['nik_user'], 'NPWP' => $user['npwp_user']];
                                    foreach ($dl as $k => $v): ?>
                                    <div style="display:flex;gap:8px;font-size:.82rem;margin-bottom:6px;">
                                        <span style="color:#a0aec0;width:50px;flex-shrink:0;"><?= $k ?></span>
                                        <span style="color:#4a5568;font-weight:500;"><?= htmlspecialchars($v ?? '-') ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-6" style="padding:0 0 0 8px;">
                                    <?php $dr = ['No. Rek' => $user['norek_user']];
                                    foreach ($dr as $k => $v): ?>
                                    <div style="display:flex;gap:8px;font-size:.82rem;margin-bottom:6px;">
                                        <span style="color:#a0aec0;width:60px;flex-shrink:0;"><?= $k ?></span>
                                        <span style="color:#4a5568;font-weight:500;"><?= htmlspecialchars($v ?? '-') ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Detail Transaksi -->
                        <h6 style="font-size:.78rem;font-weight:700;color:<?= $jc[0] ?>;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-file-invoice-dollar"></i> Detail <?= htmlspecialchars($jenis) ?>
                        </h6>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <?php foreach ($rincian as $item): ?>
                            <div style="background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:12px 14px;display:flex;align-items:flex-start;gap:10px;">
                                <div style="width:30px;height:30px;border-radius:8px;background:rgba(<?= implode(',', sscanf($item['color'], '#%02x%02x%02x')) ?>,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="<?= $item['icon'] ?>" style="font-size:.75rem;color:<?= $item['color'] ?>;"></i>
                                </div>
                                <div style="min-width:0;">
                                    <div style="font-size:.7rem;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.3px;"><?= $item['label'] ?></div>
                                    <div style="font-size:.875rem;font-weight:600;color:#1a1a2e;margin-top:2px;word-break:break-word;"><?= htmlspecialchars((string)$item['value']) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Perhitungan Kanan -->
            <div class="col-12 col-lg-4 mb-4">
                <div class="content-card content-card-success" style="border-radius:16px;overflow:hidden;position:sticky;top:80px;">
                    <div class="card-header-simple" style="padding:18px 20px;">
                        <span class="card-title"><i class="fas fa-calculator mr-2" style="color:#10b981;"></i>Perhitungan Honor</span>
                    </div>
                    <div style="padding:20px;">

                        <!-- Nominal highlight -->
                        <div style="text-align:center;padding:20px;background:linear-gradient(135deg,rgba(16,185,129,0.08),rgba(5,150,105,0.04));border:1px solid rgba(16,185,129,0.2);border-radius:12px;margin-bottom:20px;">
                            <div style="width:48px;height:48px;border-radius:14px;background:rgba(16,185,129,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                                <i class="fas fa-money-bill-wave" style="color:#059669;font-size:1.1rem;"></i>
                            </div>
                            <div style="font-size:.72rem;color:#718096;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Nominal Bruto</div>
                            <div style="font-size:1.3rem;font-weight:800;color:#1a1a2e;margin-top:4px;"><?= formatRupiah($perhitungan['nominal']) ?></div>
                        </div>

                        <!-- Rincian -->
                        <?php
                        $citems = [
                            ['Pajak PPh21 (5%)', '− '.formatRupiah($perhitungan['pajak']),    '#ef4444'],
                            ['Sisa setelah Pajak', formatRupiah($perhitungan['sisa']),         null],
                            ['Potongan (5%)',    '− '.formatRupiah($perhitungan['potongan']),   '#f59e0b'],
                        ];
                        foreach ($citems as $ci): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #f0f4f8;font-size:.85rem;">
                            <span style="color:#718096;"><?= $ci[0] ?></span>
                            <span style="font-weight:500;<?= $ci[2] ? 'color:'.$ci[2].';' : '' ?>"><?= $ci[1] ?></span>
                        </div>
                        <?php endforeach; ?>

                        <!-- Bersih total -->
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;margin-top:10px;background:linear-gradient(135deg,rgba(16,185,129,0.1),rgba(5,150,105,0.05));border:1px solid rgba(16,185,129,0.2);border-radius:10px;">
                            <span style="font-weight:700;color:#1a1a2e;font-size:.875rem;">Honor Bersih</span>
                            <span style="font-weight:800;color:#059669;font-size:1.05rem;"><?= formatRupiah($perhitungan['bersih']) ?></span>
                        </div>

                        <hr style="border-color:#eef2f7;margin:16px 0;">

                        <a href="cetak_slip.php?jenis=<?= urlencode($jenis) ?>&id=<?= $id ?>"
                           class="btn btn-danger btn-block" target="_blank"
                           style="border-radius:10px;font-weight:600;height:42px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-print mr-2"></i>Cetak Slip
                        </a>
                        <a href="riwayat_honor.php"
                           class="btn btn-block mt-2"
                           style="background:#f1f5f9;color:#475569;border:none;border-radius:10px;height:38px;font-size:.875rem;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-arrow-left mr-2"></i>Kembali ke Riwayat
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/footer_scripts.php'; ?>
