<?php
/**
 * HITUNG HONOR DOSEN - SiPagu v2.0
 * Menghitung honor dosen berbasis pertemuan & cut-off tanggal 25.
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/honor_helper.php';

$page_title = "Hitung Honor Dosen";
$error_message = '';
$success_message = '';
$hasil_proses = null;

$periode_aktif = getPeriodeAktif();
$bulan_list = ['januari','februari','maret','april','mei','juni',
               'juli','agustus','september','oktober','november','desember'];

// Proses hitung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hitung_honor'])) {
    $sel_bulan  = trim($_POST['bulan'] ?? '');
    $sel_tahun  = (int)($_POST['tahun'] ?? date('Y'));
    $min_tm     = max(1, (int)($_POST['min_tm'] ?? 3));
    $cutoff_tgl = max(1, (int)($_POST['cutoff_tanggal'] ?? 25));

    if (!in_array($sel_bulan, $bulan_list)) {
        $error_message = 'Bulan tidak valid!';
    } else {
        $periode = getPeriodeForBulan($sel_bulan, $sel_tahun, $cutoff_tgl);
        $hasil_proses = prosesHonorSemuaJadwal($koneksi, $periode['periode_awal'], $periode['periode_akhir'], $sel_bulan, $min_tm);
        $hasil_proses['bulan'] = $sel_bulan;
        $hasil_proses['tahun'] = $sel_tahun;
        $hasil_proses['periode_awal']  = $periode['periode_awal'];
        $hasil_proses['periode_akhir'] = $periode['periode_akhir'];
        $hasil_proses['min_tm'] = $min_tm;

        if ($hasil_proses['diproses'] > 0) {
            $success_message = "Berhasil memproses <strong>" . $hasil_proses['diproses'] . "</strong> jadwal. "
                . "<strong>" . $hasil_proses['pending'] . "</strong> pending, "
                . "<strong>" . $hasil_proses['digabung'] . "</strong> digabung.";
        } else {
            $error_message = 'Tidak ada jadwal yang diproses. Pastikan data pertemuan sudah diinput.';
        }
    }
}

// Tandai dibayar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bayar_honor'])) {
    $id_thd = (int)($_POST['id_thd'] ?? 0);
    if ($id_thd > 0) {
        mysqli_query($koneksi, "UPDATE t_transaksi_honor_dosen SET status='dibayar', updated_at=NOW() WHERE id_thd='$id_thd' AND status='pending'");
        $success_message = 'Status honor berhasil diubah ke Dibayar.';
    }
}

$filter_status = trim($_GET['status'] ?? '');
$where_status  = $filter_status ? "WHERE thd.status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'" : '';

$q_honor = mysqli_query($koneksi, "
    SELECT thd.*, j.kode_matkul, j.nama_matkul, u.nama_user,
           COALESCE(u.honor_persks,50000) as honor_persks
    FROM t_transaksi_honor_dosen thd
    LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    LEFT JOIN t_user u ON j.id_user = u.id_user
    $where_status
    ORDER BY thd.id_thd DESC LIMIT 50
");
$honor_list = [];
while ($row = mysqli_fetch_assoc($q_honor)) $honor_list[] = $row;

$q_stat = mysqli_query($koneksi, "SELECT COUNT(*) as total,
    SUM(status='pending') as jml_pending,
    SUM(status='digabung') as jml_digabung,
    SUM(status='dibayar') as jml_dibayar,
    COALESCE(SUM(CASE WHEN status='pending' THEN total_honor END),0) as nominal_pending
    FROM t_transaksi_honor_dosen");
$stat = mysqli_fetch_assoc($q_stat);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_admin.php';
?>
<div class="main-content">
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-calculator mr-2"></i>Hitung Honor Dosen</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
            <div class="breadcrumb-item">Hitung Honor</div>
        </div>
    </div>
    <div class="section-body">
        <?php if ($error_message): ?><div class="alert alert-danger alert-dismissible show fade"><div class="alert-body"><button class="close" data-dismiss="alert"><span>×</span></button><?= $error_message ?></div></div><?php endif; ?>
        <?php if ($success_message): ?><div class="alert alert-success alert-dismissible show fade"><div class="alert-body"><button class="close" data-dismiss="alert"><span>×</span></button><?= $success_message ?></div></div><?php endif; ?>

        <div class="row">
            <div class="col-6 col-sm-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-warning"><i class="fas fa-clock"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Pending</h4></div><div class="card-body"><?= $stat['jml_pending']??0 ?></div></div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-info"><i class="fas fa-layer-group"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Digabung</h4></div><div class="card-body"><?= $stat['jml_digabung']??0 ?></div></div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success"><i class="fas fa-check-circle"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Dibayar</h4></div><div class="card-body"><?= $stat['jml_dibayar']??0 ?></div></div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-primary"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="card-wrap"><div class="card-header"><h4>Nominal Pending</h4></div><div class="card-body" style="font-size:0.9rem;"><?= formatRupiahHonor($stat['nominal_pending']??0) ?></div></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-cog mr-2"></i>Proses Honor Periode</h4></div>
                    <div class="card-body">
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Periode aktif: <strong><?= $periode_aktif['periode_awal'] ?> s/d <?= $periode_aktif['periode_akhir'] ?></strong>
                            (label: <strong><?= ucfirst($periode_aktif['bulan']).' '.$periode_aktif['tahun'] ?></strong>)
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label>Bulan Periode <span class="text-danger">*</span></label>
                                <select class="form-control" name="bulan" required>
                                    <option value="">-- Pilih Bulan --</option>
                                    <?php foreach ($bulan_list as $bl): ?>
                                    <option value="<?= $bl ?>"
                                        <?= ($bl === ($hasil_proses['bulan'] ?? $periode_aktif['bulan'])) ? 'selected' : '' ?>>
                                        <?= ucfirst($bl) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tahun <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="tahun"
                                       value="<?= $hasil_proses['tahun'] ?? $periode_aktif['tahun'] ?>" min="2020" max="2030" required>
                            </div>
                            <div class="form-group">
                                <label>Minimum Pertemuan
                                    <small class="text-muted">(default: 3)</small>
                                </label>
                                <input type="number" class="form-control" name="min_tm"
                                       value="<?= $hasil_proses['min_tm'] ?? 3 ?>" min="1" max="20">
                                <small class="form-text text-muted">Jika TM &lt; jumlah ini → status <strong>digabung</strong></small>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Cut-off <small class="text-muted">(default: 25)</small></label>
                                <input type="number" class="form-control" name="cutoff_tanggal" value="25" min="1" max="28">
                            </div>
                            <button type="submit" name="hitung_honor" class="btn btn-primary btn-block">
                                <i class="fas fa-calculator mr-2"></i>Hitung & Proses Honor
                            </button>
                        </form>
                        <hr>
                        <a href="<?= BASE_URL ?>admin/upload_data/pertemuan_dosen.php" class="btn btn-outline-primary btn-sm btn-block">
                            <i class="fas fa-plus mr-1"></i> Input Data Pertemuan
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-8">
                <?php if ($hasil_proses): ?>
                <div class="card border-primary mb-3">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-chart-bar mr-2"></i>Hasil Proses: <?= ucfirst($hasil_proses['bulan']).' '.$hasil_proses['tahun'] ?></h4>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">Periode: <?= $hasil_proses['periode_awal'] ?> s/d <?= $hasil_proses['periode_akhir'] ?> | Min TM: <?= $hasil_proses['min_tm'] ?></p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr><th>Kode MK</th><th>Dosen</th><th class="text-center">TM</th><th class="text-center">SKS</th><th class="text-right">Honor</th><th class="text-center">Status</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($hasil_proses['detail'] as $d): $badge = getStatusBadge($d['status']); ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($d['kode_matkul']) ?></strong><small class="d-block text-muted"><?= htmlspecialchars($d['nama_matkul']) ?></small></td>
                                    <td><?= htmlspecialchars($d['nama_user']??'-') ?></td>
                                    <td class="text-center"><?= $d['jml_tm'] ?></td>
                                    <td class="text-center"><?= $d['total_sks'] ?></td>
                                    <td class="text-right"><?= $d['status']==='pending' ? formatRupiahHonor($d['total_honor']??0) : '-' ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-<?= $badge['class'] ?>"><i class="fas fa-<?= $badge['icon'] ?> mr-1"></i><?= $badge['label'] ?></span>
                                        <?php if ($d['status']==='digabung'): ?><br><small class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($d['message']) ?></small><?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-list mr-2"></i>Daftar Transaksi Honor</h4>
                        <div class="card-header-action">
                            <?php foreach([''=>'Semua','pending'=>'Pending','digabung'=>'Digabung','dibayar'=>'Dibayar'] as $s=>$lbl): ?>
                            <a href="?status=<?= $s ?>" class="btn btn-sm <?= ($filter_status===$s)?'btn-primary':'btn-outline-secondary' ?> mr-1"><?= $lbl ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th><th>Bulan</th><th>Periode</th><th>Mata Kuliah</th><th>Dosen</th>
                                        <th class="text-center">TM</th><th class="text-center">SKS</th>
                                        <th class="text-right">Honor</th><th class="text-center">Status</th><th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($honor_list)): ?>
                                <tr><td colspan="10" class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x d-block mb-2"></i>Belum ada data</td></tr>
                                <?php else: ?>
                                <?php foreach ($honor_list as $no => $h): $badge = getStatusBadge($h['status']); ?>
                                <tr>
                                    <td><?= $no+1 ?></td>
                                    <td><?= ucfirst($h['bulan']) ?></td>
                                    <td><small class="text-muted"><?= $h['periode_awal'] ?><br><?= $h['periode_akhir'] ?></small></td>
                                    <td><strong><?= htmlspecialchars($h['kode_matkul']??'-') ?></strong><small class="d-block text-muted"><?= htmlspecialchars($h['nama_matkul']??'-') ?></small></td>
                                    <td><?= htmlspecialchars($h['nama_user']??'-') ?></td>
                                    <td class="text-center"><?= $h['jml_tm'] ?></td>
                                    <td class="text-center"><?= $h['sks_tempuh'] ?></td>
                                    <td class="text-right"><strong><?= formatRupiahHonor($h['total_honor']) ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-<?= $badge['class'] ?>">
                                            <i class="fas fa-<?= $badge['icon'] ?> mr-1"></i><?= $badge['label'] ?>
                                        </span>
                                        <?php if ($h['catatan']): ?><br><small class="text-muted" style="font-size:0.7rem;" title="<?= htmlspecialchars($h['catatan']) ?>"><i class="fas fa-info-circle"></i> <?= mb_strimwidth($h['catatan'],0,35,'...') ?></small><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($h['status']==='pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id_thd" value="<?= $h['id_thd'] ?>">
                                            <button type="submit" name="bayar_honor" class="btn btn-success btn-sm" title="Tandai Dibayar" onclick="return confirm('Tandai honor ini sebagai DIBAYAR?')"><i class="fas fa-check"></i></button>
                                        </form>
                                        <a href="<?= BASE_URL ?>admin/upload_data/cetak_slip_honor.php?id_thd=<?= $h['id_thd'] ?>" class="btn btn-info btn-sm" title="Cetak Slip" target="_blank"><i class="fas fa-print"></i></a>
                                        <?php elseif ($h['status']==='dibayar'): ?>
                                        <a href="<?= BASE_URL ?>admin/upload_data/cetak_slip_honor.php?id_thd=<?= $h['id_thd'] ?>" class="btn btn-secondary btn-sm" title="Cetak Slip" target="_blank"><i class="fas fa-print"></i></a>
                                        <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                                        <a href="../CRUD/edit_data/edit_thd.php?id_thd=<?= $h['id_thd'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</div>
<?php
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>
