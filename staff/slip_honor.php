<?php
/**
 * SLIP HONOR STAFF - SiPagu
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
$q_user  = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$id_user'");
$user    = mysqli_fetch_assoc($q_user);

$bulan_list = ['januari','februari','maret','april','mei','juni',
               'juli','agustus','september','oktober','november','desember'];

$q_tahun = mysqli_query($koneksi,
    "SELECT DISTINCT YEAR(periode_akhir) as tahun
     FROM t_transaksi_honor_dosen thd
     JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
     WHERE j.id_user = '$id_user' AND periode_akhir IS NOT NULL
     ORDER BY tahun DESC"
);
$tahun_list = [];
while ($r = mysqli_fetch_assoc($q_tahun)) $tahun_list[] = $r['tahun'];
if (empty($tahun_list)) $tahun_list = [date('Y')];

$selected_bulan = $_POST['bulan'] ?? '';
$selected_tahun = (int)($_POST['tahun'] ?? date('Y'));
$honor_mengajar = [];
$honor_pata     = [];
$total_all      = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tampilkan']) && $selected_bulan) {
    $bl_esc = mysqli_real_escape_string($koneksi, $selected_bulan);
    $q_m = mysqli_query($koneksi,
        "SELECT thd.*, j.kode_matkul, j.nama_matkul, thd.sks_tempuh,
                COALESCE(u.honor_persks,50000) as honor_persks
         FROM t_transaksi_honor_dosen thd
         JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
         JOIN t_user u ON j.id_user = u.id_user
         WHERE j.id_user = '$id_user' AND thd.bulan = '$bl_esc'
           AND YEAR(thd.periode_akhir) = '$selected_tahun'
           AND thd.status != 'digabung'"
    );
    while ($r = mysqli_fetch_assoc($q_m)) {
        $honor_mengajar[] = [
            'matkul' => $r['nama_matkul'], 'kode'   => $r['kode_matkul'],
            'sks'    => $r['sks_tempuh'],  'jml_tm' => $r['jml_tm'],
            'honor'  => (int)$r['total_honor'], 'status' => $r['status'],
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

$perhitungan = hitungHonorStaff($total_all);
$has_result  = ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['tampilkan']));
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<style>
/* ===== SLIP HONOR CLEAN STYLES ===== */
.sh-page-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:24px 0 8px}
.sh-page-title{font-weight:700;font-size:1.35rem;color:#1a1a2e;margin:0 0 4px}
.sh-breadcrumb{display:flex;align-items:center;gap:5px;font-size:.75rem;color:#a0aec0}
.sh-breadcrumb a{color:#79a1c0;text-decoration:none}
.sh-breadcrumb a:hover{color:#003d7a}

/* Flat card - no shadow */
.sh-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;overflow:hidden;margin-bottom:20px}
.sh-card-header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #f0f4f8}
.sh-card-title{font-size:.88rem;font-weight:700;color:#1a1a2e;display:flex;align-items:center;gap:8px}
.sh-card-body{padding:20px}

/* Form */
.sh-form-row{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap}
.sh-form-group{display:flex;flex-direction:column;gap:5px}
.sh-label{font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px}
.sh-form-group .form-control{border-radius:8px;border-color:#e2e8f0;font-size:.84rem}
.sh-form-group .form-control:focus{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.1);outline:none}

/* User info panel */
.sh-user-panel{background:#f8fafc;border:1px solid #eef2f7;border-radius:10px;padding:14px 18px;margin-bottom:20px}
.sh-user-head{display:flex;align-items:center;gap:12px;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #eef2f7}
.sh-avatar{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,#003d7a,#3b82f6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.95rem;flex-shrink:0}
.sh-user-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px 16px}
.sh-info-row{display:flex;gap:8px;font-size:.8rem}
.sh-info-label{color:#a0aec0;flex-shrink:0;width:58px}
.sh-info-val{color:#4a5568;font-weight:500}

/* Section heading */
.sh-section-title{font-weight:700;font-size:.8rem;text-transform:uppercase;letter-spacing:.4px;display:flex;align-items:center;gap:7px;margin-bottom:10px}

/* Inner table */
.sh-inner-tbl-wrap{border:1px solid #eef2f7;border-radius:9px;overflow:hidden;margin-bottom:20px}
.sh-inner-tbl{width:100%;margin:0;border-collapse:collapse}
.sh-inner-tbl thead tr{background:#f8fafc}
.sh-inner-tbl thead th{padding:9px 12px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;border-bottom:1px solid #eef2f7;white-space:nowrap}
.sh-inner-tbl tbody tr{border-bottom:1px solid #f5f7fa}
.sh-inner-tbl tbody tr:last-child{border-bottom:none}
.sh-inner-tbl tbody td{padding:9px 12px;font-size:.84rem;color:#2d3748;vertical-align:middle}

/* Calc panel (right) */
.sh-calc-panel{background:#fff;border:1px solid #eef2f7;border-radius:12px;overflow:hidden;position:sticky;top:80px}
.sh-calc-header{padding:14px 18px;border-bottom:1px solid #f0f4f8;font-size:.88rem;font-weight:700;color:#1a1a2e;display:flex;align-items:center;gap:8px}
.sh-calc-body{padding:18px}
.sh-calc-highlight{background:linear-gradient(135deg,rgba(16,185,129,.08),rgba(5,150,105,.04));border:1px solid rgba(16,185,129,.2);border-radius:10px;padding:18px;text-align:center;margin-bottom:18px}
.sh-calc-highlight .lbl{font-size:.68rem;color:#718096;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.sh-calc-highlight .val{font-size:1.4rem;font-weight:800;color:#059669;margin-top:4px}
.sh-calc-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f5f7fa;font-size:.83rem}
.sh-calc-row .name{color:#718096}
.sh-calc-row .amount{font-weight:500}

/* Formula info bar */
.sh-formula{background:rgba(59,130,246,.05);border:1px solid rgba(59,130,246,.15);border-radius:9px;padding:10px 14px;font-size:.79rem;color:#3b82f6;margin-top:20px}

/* Empty state */
.sh-empty{text-align:center;padding:52px 24px;background:#fff;border:1px solid #eef2f7;border-radius:12px}
.sh-empty .icon-wrap{width:60px;height:60px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
.sh-empty h5{font-weight:700;color:#1a1a2e;margin-bottom:6px}
.sh-empty p{color:#718096;font-size:.875rem;margin:0}

.badge-status-sm{display:inline-block;padding:2px 8px;border-radius:999px;font-size:.65rem;font-weight:700}

@media(max-width:767px){
  .sh-page-header{flex-direction:column}
  .sh-form-row{flex-direction:column;align-items:stretch}
  .sh-form-group{width:100%}
  .sh-user-info-grid{grid-template-columns:1fr}
  .sh-inner-tbl thead th:nth-child(3),
  .sh-inner-tbl tbody td:nth-child(3),
  .sh-inner-tbl thead th:nth-child(4),
  .sh-inner-tbl tbody td:nth-child(4){display:none}
}
</style>

<div class="main-content">
<section class="section">

    <div class="section-header">
        <div class="sh-page-header w-100">
            <div>
                <h1 class="sh-page-title">Slip Honor</h1>
                <div class="sh-breadcrumb">
                    <a href="<?= BASE_URL ?>staff/index.php">Dashboard</a>
                    <i class="fas fa-chevron-right" style="font-size:.52rem;"></i>
                    <span>Slip Honor</span>
                </div>
            </div>
            <?php if ($has_result && $total_all > 0): ?>
            <a href="cetak_slip.php?bulan=<?= urlencode($selected_bulan) ?>&tahun=<?= $selected_tahun ?>"
               class="btn btn-danger btn-sm" target="_blank"
               style="border-radius:9px;font-weight:600;height:36px;display:inline-flex;align-items:center;padding:0 16px;">
                <i class="fas fa-print mr-2"></i>Cetak / Print
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="section-body mt-3">

        <!-- Filter Periode -->
        <div class="sh-card">
            <div class="sh-card-header">
                <div class="sh-card-title">
                    <i class="fas fa-calendar-alt" style="color:#003d7a;"></i>
                    Pilih Periode Slip
                </div>
            </div>
            <div class="sh-card-body">
                <form method="POST">
                    <div class="sh-form-row">
                        <div class="sh-form-group" style="flex:2;min-width:150px;">
                            <label class="sh-label">Bulan <span style="color:#ef4444;">*</span></label>
                            <select name="bulan" class="form-control" required>
                                <option value="">— Pilih Bulan —</option>
                                <?php foreach ($bulan_list as $bl): ?>
                                <option value="<?= $bl ?>" <?= $selected_bulan===$bl?'selected':'' ?>><?= ucfirst($bl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sh-form-group" style="flex:1;min-width:110px;">
                            <label class="sh-label">Tahun</label>
                            <select name="tahun" class="form-control">
                                <?php foreach ($tahun_list as $th): ?>
                                <option value="<?= $th ?>" <?= $selected_tahun==$th?'selected':'' ?>><?= $th ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sh-form-group" style="flex:none;">
                            <label class="sh-label" style="visibility:hidden;">.</label>
                            <button type="submit" name="tampilkan" class="btn btn-primary" style="height:38px;padding:0 18px;border-radius:8px;font-weight:600;font-size:.85rem;">
                                <i class="fas fa-search mr-2"></i>Tampilkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($has_result): ?>
        <?php if ($total_all === 0): ?>

        <!-- Empty State -->
        <div class="sh-empty">
            <div class="icon-wrap">
                <i class="fas fa-search-dollar" style="font-size:1.4rem;color:#d97706;"></i>
            </div>
            <h5>Data Tidak Ditemukan</h5>
            <p>Tidak ada data honor untuk bulan <strong><?= ucfirst($selected_bulan) ?> <?= $selected_tahun ?></strong>.</p>
        </div>

        <?php else: ?>
        <div class="row">

            <!-- Slip Utama (kiri) -->
            <div class="col-12 col-lg-8 mb-4">
                <div class="sh-card">
                    <div class="sh-card-header">
                        <div class="sh-card-title">
                            <i class="fas fa-file-invoice-dollar" style="color:#003d7a;"></i>
                            SLIP HONOR — <?= strtoupper($selected_bulan) . ' ' . $selected_tahun ?>
                        </div>
                    </div>
                    <div class="sh-card-body">

                        <!-- Info Dosen -->
                        <div class="sh-user-panel">
                            <div class="sh-user-head">
                                <div class="sh-avatar">
                                    <?= strtoupper(mb_substr($user['nama_user'] ?? 'S', 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight:700;color:#1a1a2e;font-size:.92rem;"><?= htmlspecialchars($user['nama_user']) ?></div>
                                    <div style="font-size:.76rem;color:#94a3b8;">NPP: <?= htmlspecialchars($user['npp_user']) ?> &nbsp;·&nbsp; <?= ucfirst($selected_bulan) . ' ' . $selected_tahun ?></div>
                                </div>
                            </div>
                            <div class="sh-user-info-grid">
                                <div class="sh-info-row">
                                    <span class="sh-info-label">NIK</span>
                                    <span class="sh-info-val"><?= htmlspecialchars($user['nik_user'] ?? '-') ?></span>
                                </div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">No. Rek</span>
                                    <span class="sh-info-val"><?= htmlspecialchars($user['norek_user'] ?? '-') ?></span>
                                </div>
                                <div class="sh-info-row">
                                    <span class="sh-info-label">NPWP</span>
                                    <span class="sh-info-val"><?= htmlspecialchars($user['npwp_user'] ?? '-') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Honor Mengajar -->
                        <?php if (!empty($honor_mengajar)): ?>
                        <h6 class="sh-section-title" style="color:#003d7a;">
                            <i class="fas fa-book-open"></i> Honor Mengajar
                        </h6>
                        <?php
                        $sc_conf = [
                            'pending'  => ['#f59e0b','rgba(245,158,11,0.1)'],
                            'dibayar'  => ['#10b981','rgba(16,185,129,0.1)'],
                            'digabung' => ['#3b82f6','rgba(59,130,246,0.1)'],
                        ];
                        ?>
                        <div class="sh-inner-tbl-wrap">
                            <table class="sh-inner-tbl">
                                <thead>
                                    <tr>
                                        <th>Mata Kuliah</th>
                                        <th>Kode</th>
                                        <th class="text-center">SKS</th>
                                        <th class="text-center">TM</th>
                                        <th class="text-right">Honor</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($honor_mengajar as $m):
                                    $sc = $sc_conf[$m['status']] ?? ['#718096','rgba(113,128,150,0.1)'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['matkul']) ?></td>
                                    <td style="color:#94a3b8;font-size:.8rem;"><?= htmlspecialchars($m['kode']) ?></td>
                                    <td class="text-center"><?= $m['sks'] ?></td>
                                    <td class="text-center"><?= $m['jml_tm'] ?>×</td>
                                    <td class="text-right" style="font-weight:700;color:#1a1a2e;"><?= formatRupiah($m['honor']) ?></td>
                                    <td class="text-center">
                                        <span class="badge-status-sm" style="background:<?= $sc[1] ?>;color:<?= $sc[0] ?>;">
                                            <?= ucfirst($m['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- Honor PA/TA -->
                        <?php if (!empty($honor_pata)): ?>
                        <h6 class="sh-section-title" style="color:#059669;">
                            <i class="fas fa-users"></i> Honor PA/TA
                        </h6>
                        <div class="sh-inner-tbl-wrap">
                            <table class="sh-inner-tbl">
                                <thead>
                                    <tr>
                                        <th>Jabatan</th>
                                        <th class="text-right">Honor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($honor_pata as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['jabatan']) ?></td>
                                    <td class="text-right" style="font-weight:700;color:#1a1a2e;"><?= formatRupiah($p['honor']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>

                        <!-- Formula info -->
                        <div class="sh-formula">
                            <i class="fas fa-info-circle mr-2"></i>
                            <?= formatRupiah($perhitungan['nominal']) ?>
                            <span style="color:#718096;"> − Pajak 5% (<?= formatRupiah($perhitungan['pajak']) ?>) = <?= formatRupiah($perhitungan['sisa']) ?></span>
                            <span style="color:#718096;"> → Potongan 5% (<?= formatRupiah($perhitungan['potongan']) ?>) = </span>
                            <strong style="color:#059669;"><?= formatRupiah($perhitungan['bersih']) ?></strong>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Perhitungan Kanan -->
            <div class="col-12 col-lg-4 mb-4">
                <div class="sh-calc-panel">
                    <div class="sh-calc-header">
                        <i class="fas fa-calculator" style="color:#10b981;"></i>Perhitungan
                    </div>
                    <div class="sh-calc-body">

                        <div class="sh-calc-highlight">
                            <div class="lbl">Honor Bersih Diterima</div>
                            <div class="val"><?= formatRupiah($perhitungan['bersih']) ?></div>
                        </div>

                        <?php
                        $calc_items = [
                            ['Nominal Bruto',    formatRupiah($perhitungan['nominal']),  null],
                            ['Pajak PPh21 (5%)', '− '.formatRupiah($perhitungan['pajak']), '#ef4444'],
                            ['Sisa Pajak',       formatRupiah($perhitungan['sisa']),     null],
                            ['Potongan (5%)',    '− '.formatRupiah($perhitungan['potongan']), '#f59e0b'],
                        ];
                        foreach ($calc_items as $ci): ?>
                        <div class="sh-calc-row">
                            <span class="name"><?= $ci[0] ?></span>
                            <span class="amount" <?= $ci[2] ? 'style="color:'.$ci[2].';"' : '' ?>><?= $ci[1] ?></span>
                        </div>
                        <?php endforeach; ?>

                        <hr style="border-color:#eef2f7;margin:14px 0;">
                        <a href="cetak_slip.php?bulan=<?= urlencode($selected_bulan) ?>&tahun=<?= $selected_tahun ?>"
                           class="btn btn-danger btn-block" target="_blank"
                           style="border-radius:9px;font-weight:600;height:40px;display:flex;align-items:center;justify-content:center;font-size:.85rem;">
                            <i class="fas fa-print mr-2"></i>Cetak / Print Slip
                        </a>
                        <a href="riwayat_honor.php" class="btn btn-block mt-2"
                           style="background:#f1f5f9;color:#475569;border:none;border-radius:9px;font-size:.84rem;height:38px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-history mr-2"></i>Lihat Semua Riwayat
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </div>
</section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/footer_scripts.php'; ?>
