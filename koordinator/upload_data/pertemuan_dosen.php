<?php
/**
 * INPUT PERTEMUAN DOSEN - SiPagu v2.0
 * Admin memasukkan data setiap pertemuan dosen.
 * Sistem otomatis mengelompokkan berdasarkan cut-off tanggal 25.
 *
 * Lokasi: admin/upload_data/pertemuan_dosen.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/honor_helper.php';

$page_title = "Input Pertemuan Dosen";

$error_message   = '';
$success_message = '';

// ============================================================
// AMBIL DATA JADWAL
// ============================================================
$jadwal_list = [];
$q = mysqli_query($koneksi, "
    SELECT j.id_jdwl, j.semester, j.kode_matkul, j.nama_matkul, j.jml_mhs,
           u.nama_user, u.npp_user, COALESCE(u.honor_persks,50000) as honor_persks
    FROM t_jadwal j
    LEFT JOIN t_user u ON j.id_user = u.id_user
    ORDER BY j.semester DESC, j.kode_matkul ASC
");
while ($row = mysqli_fetch_assoc($q)) {
    $jadwal_list[] = $row;
}

// ============================================================
// PROSES FORM: TAMBAH PERTEMUAN
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_pertemuan'])) {
    $id_jadwal  = (int)($_POST['id_jadwal'] ?? 0);
    $tanggal    = trim($_POST['tanggal'] ?? '');
    $sks        = max(1, (int)($_POST['sks'] ?? 1));
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (!$id_jadwal) {
        $error_message = 'Jadwal wajib dipilih!';
    } elseif (!$tanggal || !strtotime($tanggal)) {
        $error_message = 'Tanggal pertemuan tidak valid!';
    } else {
        // Cek jadwal ada
        $qj = mysqli_query($koneksi, "SELECT id_jdwl FROM t_jadwal WHERE id_jdwl='$id_jadwal'");
        if (!mysqli_num_rows($qj)) {
            $error_message = 'Jadwal tidak ditemukan!';
        } else {
            // Cek duplikat: jadwal + tanggal yang sama
            $tgl_esc = mysqli_real_escape_string($koneksi, $tanggal);
            $qDup = mysqli_query($koneksi, "
                SELECT id_pertemuan FROM t_pertemuan_dosen
                WHERE id_jadwal='$id_jadwal' AND tanggal='$tgl_esc'
            ");
            if (mysqli_num_rows($qDup) > 0) {
                $error_message = "Pertemuan untuk jadwal ini pada tanggal $tanggal sudah ada!";
            } else {
                $ket_esc = mysqli_real_escape_string($koneksi, $keterangan);
                $ok = mysqli_query($koneksi, "
                    INSERT INTO t_pertemuan_dosen (id_jadwal, tanggal, sks, keterangan)
                    VALUES ('$id_jadwal','$tgl_esc','$sks','$ket_esc')
                ");
                if ($ok) {
                    // Tentukan periode cut-off berdasarkan tanggal
                    $periode = getCutoffPeriode($tanggal);
                    $success_message = "✅ Pertemuan berhasil disimpan! "
                        . "Masuk periode: <strong>" . ucfirst($periode['bulan']) . " " . $periode['tahun'] . "</strong> "
                        . "(" . $periode['periode_awal'] . " s/d " . $periode['periode_akhir'] . ")";
                } else {
                    $error_message = 'Gagal menyimpan: ' . mysqli_error($koneksi);
                }
            }
        }
    }
}

// ============================================================
// PROSES: HAPUS PERTEMUAN
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pertemuan'])) {
    $id_pertemuan = (int)($_POST['id_pertemuan'] ?? 0);
    if ($id_pertemuan > 0) {
        mysqli_query($koneksi, "DELETE FROM t_pertemuan_dosen WHERE id_pertemuan='$id_pertemuan'");
        $success_message = '🗑️ Pertemuan berhasil dihapus.';
    }
}

// ============================================================
// FILTER & DATA PERTEMUAN
// ============================================================
$filter_jadwal = isset($_GET['filter_jadwal']) ? (int)$_GET['filter_jadwal'] : 0;
$filter_bulan  = trim($_GET['filter_bulan'] ?? '');
$filter_tahun  = (int)($_GET['filter_tahun'] ?? date('Y'));

// Periode aktif hari ini
$periode_aktif = getPeriodeAktif();

// Build WHERE untuk filter
$where_parts = ['1=1'];
if ($filter_jadwal) $where_parts[] = "p.id_jadwal='$filter_jadwal'";
if ($filter_bulan) {
    $prd = getPeriodeForBulan($filter_bulan, $filter_tahun);
    $pA = mysqli_real_escape_string($koneksi, $prd['periode_awal']);
    $pE = mysqli_real_escape_string($koneksi, $prd['periode_akhir']);
    $where_parts[] = "p.tanggal BETWEEN '$pA' AND '$pE'";
}
$where_sql = implode(' AND ', $where_parts);

$q_pertemuan = mysqli_query($koneksi, "
    SELECT p.*, j.kode_matkul, j.nama_matkul, j.semester,
           u.nama_user, COALESCE(u.honor_persks,50000) as honor_persks
    FROM t_pertemuan_dosen p
    JOIN t_jadwal j ON p.id_jadwal = j.id_jdwl
    JOIN t_user u ON j.id_user = u.id_user
    WHERE $where_sql
    ORDER BY p.tanggal DESC, p.id_pertemuan DESC
    LIMIT 100
");

$pertemuan_list = [];
while ($row = mysqli_fetch_assoc($q_pertemuan)) {
    $row['periode'] = getCutoffPeriode($row['tanggal']);
    $pertemuan_list[] = $row;
}

// Statistik periode aktif
$pA_esc = mysqli_real_escape_string($koneksi, $periode_aktif['periode_awal']);
$pE_esc = mysqli_real_escape_string($koneksi, $periode_aktif['periode_akhir']);
$q_stat = mysqli_query($koneksi, "
    SELECT COUNT(*) as total_tm, COALESCE(SUM(sks),0) as total_sks,
           COUNT(DISTINCT id_jadwal) as jadwal_aktif
    FROM t_pertemuan_dosen
    WHERE tanggal BETWEEN '$pA_esc' AND '$pE_esc'
");
$stat = mysqli_fetch_assoc($q_stat);

$bulan_list = ['januari','februari','maret','april','mei','juni',
               'juli','agustus','september','oktober','november','desember'];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_koordinator.php';
?>

<div class="main-content">
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-chalkboard-teacher mr-2"></i>Input Pertemuan Dosen</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>koordinator/index.php">Dashboard</a></div>
            <div class="breadcrumb-item">Input Pertemuan</div>
        </div>
    </div>

    <div class="section-body">

        <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible show fade">
            <div class="alert-body">
                <button class="close" data-dismiss="alert"><span>×</span></button>
                <i class="fas fa-exclamation-circle mr-2"></i><?= $error_message ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible show fade">
            <div class="alert-body">
                <button class="close" data-dismiss="alert"><span>×</span></button>
                <i class="fas fa-check-circle mr-2"></i><?= $success_message ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- INFO PERIODE AKTIF -->
        <div class="alert alert-info">
            <i class="fas fa-calendar-check mr-2"></i>
            <strong>Periode Aktif:</strong>
            Cut-off berlaku tanggal <strong>25</strong> setiap bulan.
            Periode saat ini: <strong><?= $periode_aktif['periode_awal'] ?></strong> s/d
            <strong><?= $periode_aktif['periode_akhir'] ?></strong>
            → label bulan: <strong><?= ucfirst($periode_aktif['bulan']) . ' ' . $periode_aktif['tahun'] ?></strong>
        </div>

        <!-- STATISTIK -->
        <div class="row">
            <div class="col-6 col-sm-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-primary"><i class="fas fa-clipboard-list"></i></div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>TM Periode Ini</h4></div>
                        <div class="card-body"><?= $stat['total_tm'] ?? 0 ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success"><i class="fas fa-book"></i></div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>SKS Periode Ini</h4></div>
                        <div class="card-body"><?= $stat['total_sks'] ?? 0 ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-warning"><i class="fas fa-chalkboard"></i></div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Jadwal Aktif</h4></div>
                        <div class="card-body"><?= $stat['jadwal_aktif'] ?? 0 ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-info"><i class="fas fa-calendar-alt"></i></div>
                    <div class="card-wrap">
                        <div class="card-header"><h4>Bulan</h4></div>
                        <div class="card-body"><?= ucfirst($periode_aktif['bulan']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- FORM INPUT PERTEMUAN -->
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-plus mr-2"></i>Tambah Pertemuan</h4></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label>Jadwal (Mata Kuliah) <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="id_jadwal" id="selJadwal" required>
                                    <option value="">-- Pilih Jadwal --</option>
                                    <?php foreach ($jadwal_list as $j): ?>
                                    <option value="<?= $j['id_jdwl'] ?>"
                                            data-honor="<?= $j['honor_persks'] ?>"
                                            data-semester="<?= htmlspecialchars($j['semester']) ?>">
                                        [<?= $j['id_jdwl'] ?>] <?= htmlspecialchars($j['kode_matkul']) ?> -
                                        <?= htmlspecialchars($j['nama_matkul']) ?>
                                        (<?= htmlspecialchars($j['nama_user'] ?? '-') ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Tanggal Pertemuan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal" id="inpTanggal"
                                       value="<?= date('Y-m-d') ?>" required onchange="updatePeriodeInfo()">
                                <small class="text-muted">Sistem otomatis menentukan periode berdasarkan tanggal ini</small>
                            </div>

                            <!-- Info periode otomatis -->
                            <div id="periodeInfo" class="alert alert-light border py-2 px-3 mb-3" style="font-size:0.85rem;">
                                <i class="fas fa-info-circle text-info mr-1"></i>
                                <span id="periodeText">Pilih tanggal untuk melihat periode</span>
                            </div>

                            <div class="form-group">
                                <label>SKS Pertemuan Ini <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="sks" value="1" min="1" max="6" required>
                                <small class="text-muted">Bobot SKS untuk pertemuan ini</small>
                            </div>

                            <div class="form-group">
                                <label>Keterangan</label>
                                <input type="text" class="form-control" name="keterangan"
                                       placeholder="Opsional, misal: Pengganti pertemuan libur">
                            </div>

                            <button type="submit" name="submit_pertemuan" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-2"></i>Simpan Pertemuan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- KETERANGAN CUT-OFF -->
                <div class="card">
                    <div class="card-header"><h4><i class="fas fa-info-circle mr-2 text-info"></i>Aturan Cut-off</h4></div>
                    <div class="card-body">
                        <p class="small mb-2"><strong>Tanggal 25</strong> setiap bulan adalah batas (cut-off) periode.</p>
                        <table class="table table-sm table-bordered small">
                            <thead class="thead-light"><tr><th>Tanggal Input</th><th>Masuk Periode</th></tr></thead>
                            <tbody>
                                <tr><td>26 Feb – 25 Mar</td><td><strong>Maret</strong></td></tr>
                                <tr><td>26 Mar – 25 Apr</td><td><strong>April</strong></td></tr>
                                <tr><td>26 Apr – 25 Mei</td><td><strong>Mei</strong></td></tr>
                            </tbody>
                        </table>
                        <div class="alert alert-warning py-2 mb-0 small">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Jika pertemuan dalam 1 periode <strong>kurang dari 3</strong>, data akan
                            <strong>digabung</strong> ke periode berikutnya (tidak dibuat transaksi honor).
                        </div>
                    </div>
                </div>
            </div>

            <!-- DAFTAR PERTEMUAN -->
            <div class="col-12 col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-list mr-2"></i>Daftar Pertemuan</h4>
                        <div class="card-header-action">
                            <a href="<?= BASE_URL ?>koordinator/upload_data/hitung_honor.php" class="btn btn-success btn-sm">
                                <i class="fas fa-calculator mr-1"></i> Hitung Honor
                            </a>
                        </div>
                    </div>
                    <!-- Filter -->
                    <div class="card-body pb-0">
                        <form method="GET" class="form-inline mb-3">
                            <div class="form-group mr-2 mb-2">
                                <label class="mr-1 small">Jadwal:</label>
                                <select class="form-control form-control-sm" name="filter_jadwal">
                                    <option value="">Semua Jadwal</option>
                                    <?php foreach ($jadwal_list as $j): ?>
                                    <option value="<?= $j['id_jdwl'] ?>" <?= ($filter_jadwal == $j['id_jdwl']) ? 'selected' : '' ?>>
                                        [<?= $j['id_jdwl'] ?>] <?= htmlspecialchars($j['kode_matkul']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group mr-2 mb-2">
                                <label class="mr-1 small">Bulan:</label>
                                <select class="form-control form-control-sm" name="filter_bulan">
                                    <option value="">Semua</option>
                                    <?php foreach ($bulan_list as $bl): ?>
                                    <option value="<?= $bl ?>" <?= ($filter_bulan === $bl) ? 'selected' : '' ?>>
                                        <?= ucfirst($bl) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group mr-2 mb-2">
                                <label class="mr-1 small">Tahun:</label>
                                <input type="number" class="form-control form-control-sm" name="filter_tahun"
                                       value="<?= $filter_tahun ?>" min="2020" max="2030" style="width:80px;">
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary mb-2">
                                <i class="fas fa-filter mr-1"></i>Filter
                            </button>
                            <a href="pertemuan_dosen.php" class="btn btn-sm btn-secondary mb-2 ml-1">Reset</a>
                        </form>
                    </div>

                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm" id="tblPertemuan">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tanggal</th>
                                        <th>Periode</th>
                                        <th>Jadwal</th>
                                        <th>Dosen</th>
                                        <th class="text-center">SKS</th>
                                        <th>Ket.</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($pertemuan_list)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-2x d-block mb-2"></i>Belum ada data pertemuan
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($pertemuan_list as $no => $p): ?>
                                <tr>
                                    <td><?= $no + 1 ?></td>
                                    <td><?= htmlspecialchars($p['tanggal']) ?></td>
                                    <td>
                                        <span class="badge badge-light border">
                                            <?= ucfirst($p['periode']['bulan']) . ' ' . $p['periode']['tahun'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($p['kode_matkul']) ?></strong>
                                        <small class="d-block text-muted"><?= htmlspecialchars($p['nama_matkul']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($p['nama_user'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?= $p['sks'] ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($p['keterangan'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id_pertemuan" value="<?= $p['id_pertemuan'] ?>">
                                            <button type="submit" name="hapus_pertemuan"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Yakin hapus pertemuan ini?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($pertemuan_list) >= 100): ?>
                        <small class="text-muted">Menampilkan 100 data terakhir. Gunakan filter untuk mempersempit.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

<script>
$(document).ready(function(){
    $('#selJadwal').select2({ width:'100%', placeholder:'Cari jadwal...', allowClear:true });
    updatePeriodeInfo();
});

function updatePeriodeInfo() {
    var tgl = document.getElementById('inpTanggal').value;
    if (!tgl) return;
    var d = new Date(tgl);
    var day   = d.getDate();
    var month = d.getMonth() + 1; // 1-12
    var year  = d.getFullYear();

    var bulanNama = ['','Januari','Februari','Maret','April','Mei','Juni',
                     'Juli','Agustus','September','Oktober','November','Desember'];

    var labelBulan, labelTahun, prevMonth, prevYear;
    if (day <= 25) {
        labelBulan = month;
        labelTahun = year;
    } else {
        labelBulan = month + 1;
        labelTahun = year;
        if (labelBulan > 12) { labelBulan = 1; labelTahun++; }
    }

    prevMonth = labelBulan - 1;
    prevYear  = labelTahun;
    if (prevMonth < 1) { prevMonth = 12; prevYear--; }

    var periodeAwal  = prevYear  + '-' + String(prevMonth).padStart(2,'0') + '-26';
    var periodeAkhir = labelTahun + '-' + String(labelBulan).padStart(2,'0') + '-25';

    document.getElementById('periodeText').innerHTML =
        '📅 Masuk periode: <strong>' + bulanNama[labelBulan] + ' ' + labelTahun + '</strong>' +
        ' &nbsp;(' + periodeAwal + ' s/d ' + periodeAkhir + ')';
}
</script>

<?php
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>
