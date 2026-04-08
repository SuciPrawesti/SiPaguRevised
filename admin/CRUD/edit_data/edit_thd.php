<?php
/**
 * EDIT TRANSAKSI HONOR DOSEN - SiPagu v2.0
 * Revisi: tambah field periode_awal, periode_akhir, status, total_honor
 */
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../includes/honor_helper.php';

if (!isset($_GET['id_thd'])) {
    header("Location: ../../upload_data/honor_dosen.php");
    exit;
}

$id_thd = (int)$_GET['id_thd'];

$stmt = $koneksi->prepare("SELECT thd.*, j.kode_matkul, j.nama_matkul, u.nama_user, COALESCE(u.honor_persks,50000) as honor_persks
    FROM t_transaksi_honor_dosen thd
    LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    LEFT JOIN t_user u ON j.id_user = u.id_user
    WHERE thd.id_thd = ?");
$stmt->bind_param("i", $id_thd);
$stmt->execute();
$thd = $stmt->get_result()->fetch_assoc();

if (!$thd) {
    header("Location: ../../upload_data/honor_dosen.php");
    exit;
}

$bulan_list = ['januari','februari','maret','april','mei','juni',
               'juli','agustus','september','oktober','november','desember'];
$status_list = ['pending','digabung','dibayar'];

$q_jadwal = $koneksi->query("SELECT id_jdwl, semester, kode_matkul, nama_matkul FROM t_jadwal ORDER BY semester DESC, nama_matkul ASC");

// Proses Update
if (isset($_POST['submit'])) {
    $bulan       = $_POST['bulan'];
    $id_jadwal   = (int)$_POST['id_jadwal'];
    $periode_awal  = $_POST['periode_awal'];
    $periode_akhir = $_POST['periode_akhir'];
    $status      = $_POST['status'];
    $catatan     = trim($_POST['catatan'] ?? '');

    // Ambil data pertemuan dalam periode baru untuk hitung ulang
    $pertemuan = getPertemuanInPeriode($koneksi, $id_jadwal, $periode_awal, $periode_akhir);
    $rekap = hitungRekapPeriode($pertemuan);

    $jml_tm    = $rekap['jumlah_tm'];
    $sks_tempuh = $rekap['total_sks'];
    // Ambil honor per sks dosen
    $qHp = $koneksi->query("SELECT COALESCE(u.honor_persks,50000) as hp FROM t_jadwal j LEFT JOIN t_user u ON j.id_user=u.id_user WHERE j.id_jdwl='$id_jadwal'");
    $hpRow = $qHp->fetch_assoc();
    $honor_persks = (int)($hpRow['hp'] ?? 50000);
    $total_honor = ($status === 'digabung') ? 0 : ($sks_tempuh * $honor_persks);

    $bulan_esc    = $koneksi->real_escape_string($bulan);
    $pA_esc       = $koneksi->real_escape_string($periode_awal);
    $pE_esc       = $koneksi->real_escape_string($periode_akhir);
    $status_esc   = $koneksi->real_escape_string($status);
    $catatan_esc  = $koneksi->real_escape_string($catatan);

    $ok = $koneksi->query("UPDATE t_transaksi_honor_dosen SET
        bulan='$bulan_esc', id_jadwal='$id_jadwal',
        periode_awal='$pA_esc', periode_akhir='$pE_esc',
        jml_tm='$jml_tm', sks_tempuh='$sks_tempuh',
        total_honor='$total_honor', status='$status_esc',
        catatan='$catatan_esc', updated_at=NOW()
        WHERE id_thd='$id_thd'");

    if ($ok) {
        $_SESSION['success_message'] = "Data Transaksi Honor Dosen berhasil diperbarui.";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui: " . $koneksi->error;
    }
    header("Location: ../../upload_data/honor_dosen.php");
    exit;
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
include __DIR__ . '/../../includes/sidebar_admin.php';
?>
<div class="main-content">
<section class="section">
    <div class="section-header">
        <h1>Edit Transaksi Honor Dosen</h1>
    </div>
    <div class="section-body">
        <div class="card">
            <div class="card-header">
                <h4>Edit Data Honor: <strong><?= htmlspecialchars($thd['nama_user']??'-') ?></strong> — <?= ucfirst($thd['bulan']) ?></h4>
            </div>
            <div class="card-body">
                <?php $badge = getStatusBadge($thd['status']); ?>
                <div class="alert alert-<?= $badge['class'] ?> py-2 small mb-3">
                    Status saat ini: <strong><?= $badge['label'] ?></strong>
                    <?php if ($thd['catatan']): ?>
                    &nbsp;|&nbsp; Catatan: <?= htmlspecialchars($thd['catatan']) ?>
                    <?php endif; ?>
                </div>

                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bulan (Label Tampilan)</label>
                                <select name="bulan" class="form-control" required>
                                    <?php foreach ($bulan_list as $bl): ?>
                                    <option value="<?= $bl ?>" <?= ($thd['bulan']===$bl)?'selected':'' ?>><?= ucfirst($bl) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jadwal / Mata Kuliah</label>
                                <select name="id_jadwal" class="form-control" required>
                                    <?php while ($j = $q_jadwal->fetch_assoc()): ?>
                                    <option value="<?= $j['id_jdwl'] ?>" <?= ($thd['id_jadwal']==$j['id_jdwl'])?'selected':'' ?>>
                                        [<?= $j['id_jdwl'] ?>] <?= htmlspecialchars($j['kode_matkul'].' - '.$j['nama_matkul']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Periode Awal (Cut-off)</label>
                                <input type="date" name="periode_awal" class="form-control"
                                       value="<?= htmlspecialchars($thd['periode_awal'] ?? '') ?>" required>
                                <small class="form-text text-muted">Awal periode, misal: tanggal 26 bulan sebelumnya</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Periode Akhir (Cut-off)</label>
                                <input type="date" name="periode_akhir" class="form-control"
                                       value="<?= htmlspecialchars($thd['periode_akhir'] ?? '') ?>" required>
                                <small class="form-text text-muted">Akhir periode, misal: tanggal 25 bulan ini</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control" required>
                                    <?php foreach ($status_list as $s): ?>
                                    <option value="<?= $s ?>" <?= ($thd['status']===$s)?'selected':'' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    pending = menunggu | digabung = dibawa ke periode berikutnya | dibayar = selesai
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Catatan Admin</label>
                                <input type="text" name="catatan" class="form-control"
                                       value="<?= htmlspecialchars($thd['catatan'] ?? '') ?>"
                                       placeholder="Opsional, misal: alasan digabung">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle mr-1"></i>
                        Jumlah TM, SKS, dan Total Honor akan dihitung ulang otomatis dari data
                        <strong>t_pertemuan_dosen</strong> berdasarkan periode yang dipilih.
                        Saat ini: TM=<strong><?= $thd['jml_tm'] ?></strong>,
                        SKS=<strong><?= $thd['sks_tempuh'] ?></strong>,
                        Honor=<strong><?= formatRupiahHonor($thd['total_honor']) ?></strong>
                    </div>

                    <div class="text-right">
                        <a href="../../upload_data/honor_dosen.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" name="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
