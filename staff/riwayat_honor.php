<?php
/**
 * RIWAYAT HONOR STAFF - SiPagu
 * Modern Minimalist Design
 */
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/function_helper.php';
require_once __DIR__ . '/../includes/honor_helper.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: " . BASE_URL . "index.php"); exit();
}

$id_user       = (int)$_SESSION['id_user'];
$filter_jenis  = trim($_GET['jenis']  ?? '');
$filter_status = trim($_GET['status'] ?? '');

$where_m = "j.id_user = '$id_user'";
if ($filter_status) $where_m .= " AND thd.status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
$q_m = mysqli_query($koneksi,
    "SELECT 'Honor Mengajar' as sumber, thd.id_thd as id_transaksi,
            thd.bulan, thd.periode_awal, thd.periode_akhir, thd.status,
            j.kode_matkul, j.nama_matkul, thd.jml_tm, thd.sks_tempuh,
            COALESCE(u.honor_persks,50000) as honor_persks, thd.total_honor as nominal
     FROM t_transaksi_honor_dosen thd
     JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
     JOIN t_user u ON j.id_user = u.id_user
     WHERE $where_m ORDER BY thd.periode_awal DESC"
);

$q_p = mysqli_query($koneksi,
    "SELECT 'Honor PA/TA' as sumber, tpt.id_tpt as id_transaksi,
            tpt.periode_wisuda as bulan, '' as periode_awal, '' as periode_akhir,
            'dibayar' as status, '' as kode_matkul,
            p.jbtn_pnt as nama_matkul, tpt.jml_mhs_bimbingan as jml_tm,
            1 as sks_tempuh, p.honor_std as honor_persks, p.honor_std as nominal
     FROM t_transaksi_pa_ta tpt
     JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
     WHERE tpt.id_user = '$id_user' ORDER BY tpt.id_tpt DESC"
);

$q_u = mysqli_query($koneksi,
    "SELECT 'Honor Ujian' as sumber, tu.id_tu as id_transaksi,
            '' as bulan, '' as periode_awal, '' as periode_akhir,
            'dibayar' as status, '' as kode_matkul,
            p.jbtn_pnt as nama_matkul, 0 as jml_tm, 1 as sks_tempuh,
            p.honor_std as honor_persks, p.honor_std as nominal
     FROM t_transaksi_ujian tu
     JOIN t_panitia p ON tu.id_panitia = p.id_pnt
     WHERE tu.id_user = '$id_user' ORDER BY tu.id_tu DESC"
);

$riwayat = $total_bersih = 0;
$riwayat = [];
$allRows = [];
while ($r = mysqli_fetch_assoc($q_m)) $allRows[] = $r;
while ($r = mysqli_fetch_assoc($q_p)) $allRows[] = $r;
while ($r = mysqli_fetch_assoc($q_u)) $allRows[] = $r;

foreach ($allRows as $row) {
    if ($filter_jenis && $row['sumber'] !== $filter_jenis) continue;
    $hitung    = hitungHonorStaff((int)$row['nominal']);
    $riwayat[] = array_merge($row, $hitung);
    if ($row['status'] !== 'digabung') $total_bersih += $hitung['bersih'];
}

$statusConf = [
    'pending'  => ['warning', 'fas fa-clock',        'Pending',  '#f59e0b', 'rgba(245,158,11,0.1)'],
    'digabung' => ['info',    'fas fa-layer-group',  'Digabung', '#3b82f6', 'rgba(59,130,246,0.1)'],
    'dibayar'  => ['success', 'fas fa-check-circle', 'Dibayar',  '#10b981', 'rgba(16,185,129,0.1)'],
];
$jenisConf = [
    'Honor Mengajar' => ['#003d7a', 'rgba(0,61,122,0.1)'],
    'Honor PA/TA'    => ['#059669', 'rgba(5,150,105,0.1)'],
    'Honor Ujian'    => ['#d97706', 'rgba(217,119,6,0.1)'],
];
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<style>
.rh-page-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:24px 0 8px}
.rh-page-title{font-weight:700;font-size:1.35rem;color:#1a1a2e;margin:0 0 4px}
.rh-breadcrumb{display:flex;align-items:center;gap:5px;font-size:.75rem;color:#a0aec0}
.rh-breadcrumb a{color:#79a1c0;text-decoration:none}
.rh-breadcrumb a:hover{color:#003d7a}
.rh-total-badge{background:linear-gradient(135deg,rgba(16,185,129,.1),rgba(5,150,105,.05));border:1px solid rgba(16,185,129,.25);padding:10px 18px;border-radius:12px;text-align:right;flex-shrink:0}
.rh-total-badge .lbl{font-size:.68rem;color:#718096;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.rh-total-badge .val{font-size:1.05rem;font-weight:800;color:#059669;margin-top:2px}
.rh-filter-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;padding:14px 18px;margin-bottom:18px}
.rh-filter-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.rh-filter-row .form-control-sm{border-radius:8px;border-color:#e2e8f0;font-size:.82rem;height:34px}
.rh-filter-row .form-control-sm:focus{border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.1);outline:none}
.rh-count{font-size:.77rem;color:#718096;margin-left:auto}
.rh-count strong{color:#1a1a2e}
.rh-table-card{background:#fff;border:1px solid #eef2f7;border-radius:12px;overflow:hidden}
.rh-card-header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #f0f4f8}
.rh-card-title{font-size:.88rem;font-weight:700;color:#1a1a2e;display:flex;align-items:center;gap:8px}
.rh-card-title i{color:#003d7a}
.rh-tbl{width:100%;margin:0;border-collapse:collapse}
.rh-tbl thead tr{background:#f8fafc}
.rh-tbl thead th{padding:10px 14px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#94a3b8;border-bottom:1px solid #eef2f7;white-space:nowrap}
.rh-tbl tbody tr{border-bottom:1px solid #f5f7fa}
.rh-tbl tbody tr:last-child{border-bottom:none}
.rh-tbl tbody td{padding:11px 14px;font-size:.84rem;color:#4a5568;vertical-align:middle}
.badge-jenis{display:inline-block;padding:3px 9px;border-radius:999px;font-size:.67rem;font-weight:700;white-space:nowrap}
.badge-status{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:.67rem;font-weight:700;white-space:nowrap}
.btn-action{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:7px;background:rgba(59,130,246,.08);color:#3b82f6;font-size:.75rem;text-decoration:none}
.rh-empty{text-align:center;padding:52px 20px;color:#a0aec0}
.rh-empty i{font-size:2.5rem;display:block;margin-bottom:12px;opacity:.35}
.rh-empty .t{font-size:.92rem;font-weight:600;color:#94a3b8;margin-bottom:4px}
.rh-empty .s{font-size:.78rem}
@media(max-width:767px){
  .rh-tbl thead th:nth-child(5),.rh-tbl tbody td:nth-child(5),
  .rh-tbl thead th:nth-child(6),.rh-tbl tbody td:nth-child(6){display:none}
  .rh-page-header{flex-direction:column}
  .rh-total-badge{text-align:left;width:100%}
  .rh-count{margin-left:0;width:100%}
}
</style>

<div class="main-content">
<section class="section">

    <div class="section-header">
        <div class="rh-page-header w-100">
            <div>
                <h1 class="rh-page-title">Riwayat Honor</h1>
                <div class="rh-breadcrumb">
                    <a href="<?= BASE_URL ?>staff/index.php">Dashboard</a>
                    <i class="fas fa-chevron-right" style="font-size:.52rem;"></i>
                    <span>Riwayat Honor</span>
                </div>
            </div>
            <div class="rh-total-badge">
                <div class="lbl">Total Honor Bersih</div>
                <div class="val"><?= formatRupiah($total_bersih) ?></div>
            </div>
        </div>
    </div>

    <div class="section-body mt-3">

        <!-- Filter -->
        <div class="rh-filter-card">
            <form method="GET">
                <div class="rh-filter-row">
                    <div style="position:relative;">
                        <i class="fas fa-filter" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:#cbd5e0;font-size:.7rem;pointer-events:none;"></i>
                        <select name="jenis" class="form-control form-control-sm" style="padding-left:26px;min-width:150px;">
                            <option value="">Semua Jenis</option>
                            <option value="Honor Mengajar" <?= $filter_jenis==='Honor Mengajar'?'selected':'' ?>>Honor Mengajar</option>
                            <option value="Honor PA/TA"    <?= $filter_jenis==='Honor PA/TA'?'selected':'' ?>>Honor PA/TA</option>
                            <option value="Honor Ujian"    <?= $filter_jenis==='Honor Ujian'?'selected':'' ?>>Honor Ujian</option>
                        </select>
                    </div>
                    <div style="position:relative;">
                        <i class="fas fa-tag" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:#cbd5e0;font-size:.7rem;pointer-events:none;"></i>
                        <select name="status" class="form-control form-control-sm" style="padding-left:26px;min-width:130px;">
                            <option value="">Semua Status</option>
                            <option value="pending"  <?= $filter_status==='pending'?'selected':'' ?>>Pending</option>
                            <option value="digabung" <?= $filter_status==='digabung'?'selected':'' ?>>Digabung</option>
                            <option value="dibayar"  <?= $filter_status==='dibayar'?'selected':'' ?>>Dibayar</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;height:34px;padding:0 14px;font-size:.82rem;">
                        <i class="fas fa-search mr-1"></i>Filter
                    </button>
                    <?php if ($filter_jenis || $filter_status): ?>
                    <a href="riwayat_honor.php" class="btn btn-sm" style="height:34px;padding:0 12px;border-radius:8px;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;font-size:.82rem;display:inline-flex;align-items:center;">
                        <i class="fas fa-times mr-1"></i>Reset
                    </a>
                    <?php endif; ?>
                    <div class="rh-count"><strong><?= count($riwayat) ?></strong> data ditemukan</div>
                </div>
            </form>
        </div>

        <!-- Tabel -->
        <div class="rh-table-card">
            <div class="rh-card-header">
                <div class="rh-card-title">
                    <i class="fas fa-history"></i>Daftar Riwayat Honor
                </div>
                <a href="<?= BASE_URL ?>staff/slip_honor.php" class="btn btn-primary btn-sm" style="border-radius:8px;font-size:.78rem;">
                    <i class="fas fa-file-invoice-dollar mr-1"></i>Slip Honor
                </a>
            </div>
            <div class="table-responsive">
                <table class="rh-tbl" id="table-riwayat">
                    <thead>
                        <tr>
                            <th style="padding-left:18px;width:38px;">#</th>
                            <th>Jenis</th>
                            <th>Kegiatan / Mata Kuliah</th>
                            <th>Bulan / Periode</th>
                            <th class="text-center">TM</th>
                            <th class="text-right">Nominal</th>
                            <th class="text-right">Bersih</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width:55px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($riwayat)): ?>
                        <tr>
                            <td colspan="9">
                                <div class="rh-empty">
                                    <i class="fas fa-inbox"></i>
                                    <div class="t">Belum ada riwayat honor</div>
                                    <div class="s">Data akan muncul setelah ada transaksi</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($riwayat as $i => $item):
                            $st = $statusConf[$item['status']] ?? ['secondary','fas fa-question','—','#718096','rgba(113,128,150,0.1)'];
                            $jc = $jenisConf[$item['sumber']] ?? ['#718096','rgba(113,128,150,0.1)'];
                        ?>
                        <tr>
                            <td style="padding-left:18px;color:#a0aec0;font-size:.76rem;"><?= $i + 1 ?></td>
                            <td>
                                <span class="badge-jenis" style="background:<?= $jc[1] ?>;color:<?= $jc[0] ?>;">
                                    <?= htmlspecialchars($item['sumber']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($item['kode_matkul']): ?>
                                <div style="font-weight:600;color:#1a1a2e;font-size:.84rem;"><?= htmlspecialchars($item['kode_matkul']) ?></div>
                                <?php endif; ?>
                                <div style="color:#94a3b8;font-size:.78rem;"><?= htmlspecialchars($item['nama_matkul']) ?></div>
                            </td>
                            <td>
                                <?php if ($item['bulan']): ?>
                                <div style="font-weight:600;color:#1a1a2e;font-size:.84rem;"><?= ucfirst($item['bulan']) ?></div>
                                <?php endif; ?>
                                <?php if ($item['periode_awal']): ?>
                                <div style="font-size:.73rem;color:#a0aec0;"><?= $item['periode_awal'] ?> – <?= $item['periode_akhir'] ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= $item['jml_tm'] ?: '—' ?></td>
                            <td class="text-right"><?= formatRupiah($item['nominal']) ?></td>
                            <td class="text-right" style="font-weight:700;color:#059669;"><?= formatRupiah($item['bersih']) ?></td>
                            <td class="text-center">
                                <span class="badge-status" style="background:<?= $st[4] ?>;color:<?= $st[3] ?>;">
                                    <i class="<?= $st[1] ?>"></i><?= $st[2] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="detail_slip.php?jenis=<?= urlencode($item['sumber']) ?>&id=<?= $item['id_transaksi'] ?>"
                                class="btn-action" title="Lihat Detail"
                                style="color:#3b82f6 !important;">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/footer_scripts.php'; ?>
<script src="<?= ASSETS_URL ?>/js/page/modules-datatables.js"></script>
<script>
$(function () {
    if ($.fn.DataTable) {
        $('#table-riwayat').DataTable({
            pageLength: 15,
            columnDefs: [{ orderable: false, targets: [8] }],
            language: {
                search: '', searchPlaceholder: 'Cari data...',
                lengthMenu: 'Tampilkan _MENU_ data',
                zeroRecords: 'Data tidak ditemukan',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                paginate: { next: 'Berikutnya', previous: 'Sebelumnya' }
            }
        });
    }
});
</script>
