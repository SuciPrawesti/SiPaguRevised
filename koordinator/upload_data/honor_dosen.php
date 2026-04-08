<?php
/**
 * DATA HONOR DOSEN - SiPagu v2.0
 * List view dengan kolom periode, status, total honor.
 */
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/honor_helper.php';

$page_title = "Data Honor Dosen";

$query = mysqli_query($koneksi, "
    SELECT th.*, j.nama_matkul, j.kode_matkul, u.nama_user,
           COALESCE(u.honor_persks,50000) as honor_persks
    FROM t_transaksi_honor_dosen th
    LEFT JOIN t_jadwal j ON th.id_jadwal = j.id_jdwl
    LEFT JOIN t_user u ON j.id_user = u.id_user
    ORDER BY th.id_thd DESC
");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
include __DIR__ . '/../includes/sidebar_koordinator.php';
?>
<div class="main-content">
<section class="section">
    <div class="section-header">
        <h1>Data Honor Dosen</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>koordinator/index.php">Dashboard</a></div>
            <div class="breadcrumb-item">Master Data</div>
            <div class="breadcrumb-item">Honor Dosen</div>
        </div>
    </div>
    <div class="section-body">
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success_message']; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error']; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <div class="card">
            <div class="card-header">
                <h4>Daftar Transaksi Honor Dosen</h4>
                <div class="card-header-action">
                    <a href="<?= BASE_URL ?>koordinator/upload_data/pertemuan_dosen.php" class="btn btn-outline-primary btn-sm mr-1">
                        <i class="fas fa-plus"></i> Input Pertemuan
                    </a>
                    <a href="<?= BASE_URL ?>koordinator/upload_data/hitung_honor.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-calculator"></i> Hitung Honor
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="table-1">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Bulan</th>
                                <th>Periode</th>
                                <th>Mata Kuliah</th>
                                <th>Dosen</th>
                                <th class="text-center">TM</th>
                                <th class="text-center">SKS</th>
                                <th class="text-right">Honor</th>
                                <th class="text-center">Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($query)): ?>
                        <?php $badge = getStatusBadge($row['status'] ?? 'pending'); ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= ucfirst(htmlspecialchars($row['bulan'])) ?></td>
                            <td>
                                <small class="text-muted">
                                    <?= htmlspecialchars($row['periode_awal'] ?? '-') ?><br>
                                    <?= htmlspecialchars($row['periode_akhir'] ?? '-') ?>
                                </small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['kode_matkul'] ?? '-') ?></strong>
                                <small class="d-block text-muted"><?= htmlspecialchars($row['nama_matkul'] ?? '-') ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['nama_user'] ?? '-') ?></td>
                            <td class="text-center"><?= $row['jml_tm'] ?></td>
                            <td class="text-center"><?= $row['sks_tempuh'] ?></td>
                            <td class="text-right"><strong><?= formatRupiahHonor($row['total_honor'] ?? 0) ?></strong></td>
                            <td class="text-center">
                                <span class="badge badge-<?= $badge['class'] ?>">
                                    <i class="fas fa-<?= $badge['icon'] ?> mr-1"></i><?= $badge['label'] ?>
                                </span>
                                <?php if ($row['catatan']): ?>
                                <br><small class="text-muted" style="font-size:0.7rem;" title="<?= htmlspecialchars($row['catatan']) ?>">
                                    <?= mb_strimwidth($row['catatan'], 0, 25, '...') ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="../CRUD/edit_data/edit_thd.php?id_thd=<?= $row['id_thd'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <?php if (($row['status']??'') !== 'dibayar'): ?>
                                <a href="<?= BASE_URL ?>koordinator/upload_data/cetak_slip_honor.php?id_thd=<?= $row['id_thd'] ?>" class="btn btn-info btn-sm" target="_blank" title="Cetak Slip"><i class="fas fa-print"></i></a>
                                <?php endif; ?>
                                <form action="../CRUD/hapus_data/hapus_thd.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="id_thd" value="<?= $row['id_thd'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
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
<script src="<?= ASSETS_URL ?>js/page/modules-datatables.js"></script>
<script>
$(function(){
    $('#table-1').DataTable({
        pageLength: 15,
        language: {
            search:"Cari:", lengthMenu:"Tampilkan _MENU_ data",
            zeroRecords:"Data tidak ditemukan",
            info:"Halaman _PAGE_ dari _PAGES_",
            paginate:{next:"Berikutnya",previous:"Sebelumnya"}
        }
    });
});
</script>
