<?php
include '../../../config.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = mysqli_query($koneksi, "
        SELECT thd.*, j.kode_matkul, j.nama_matkul, j.jml_mhs,
               u.nama_user AS nama_dosen, u.npp_user,
               COALESCE(u.honor_persks,50000) as honor_persks
        FROM t_transaksi_honor_dosen thd
        LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
        LEFT JOIN t_user u ON j.id_user = u.id_user
        WHERE thd.id_thd = '$id'
    ");

    if ($data = mysqli_fetch_assoc($query)) {
        $bulan_indo = ucfirst($data['bulan']);
        $statusMap  = ['pending'=>['warning','Pending'],'digabung'=>['info','Digabung'],'dibayar'=>['success','Dibayar']];
        $st = $statusMap[$data['status']] ?? ['secondary', ucfirst($data['status'])];
        ?>
        <div class="up-detail-view">
            <table class="up-table" style="border:none;">
                <tr><th width="160">ID Transaksi</th><td>: <strong><?= $data['id_thd'] ?></strong></td></tr>
                <tr><th>Bulan</th>        <td>: <?= $bulan_indo ?></td></tr>
                <tr><th>Periode Awal</th> <td>: <?= htmlspecialchars($data['periode_awal'] ?? '-') ?></td></tr>
                <tr><th>Periode Akhir</th><td>: <?= htmlspecialchars($data['periode_akhir'] ?? '-') ?></td></tr>
                <tr><th>Dosen</th>        <td>: <?= htmlspecialchars($data['nama_dosen'] ?: '-') ?></td></tr>
                <tr><th>NPP</th>          <td>: <?= htmlspecialchars($data['npp_user'] ?: '-') ?></td></tr>
                <tr><th>Kode MK</th>      <td>: <strong><?= htmlspecialchars($data['kode_matkul'] ?: '-') ?></strong></td></tr>
                <tr><th>Nama MK</th>      <td>: <?= htmlspecialchars($data['nama_matkul'] ?: '-') ?></td></tr>
                <tr><th>Jml Mahasiswa</th><td>: <?= $data['jml_mhs'] ?? 0 ?> orang</td></tr>
                <tr><th>Tatap Muka</th>   <td>: <?= $data['jml_tm'] ?> kali</td></tr>
                <tr><th>SKS Tempuh</th>   <td>: <?= $data['sks_tempuh'] ?> SKS</td></tr>
                <tr><th>Honor per SKS</th><td>: Rp <?= number_format($data['honor_persks'],0,',','.') ?></td></tr>
                <tr><th>Total Honor</th>  <td>: <strong class="text-success">Rp <?= number_format($data['total_honor']??0,0,',','.') ?></strong></td></tr>
                <tr><th>Status</th>       <td>: <span class="badge badge-<?= $st[0] ?>"><?= $st[1] ?></span></td></tr>
                <?php if ($data['catatan']): ?>
                <tr><th>Catatan</th>      <td>: <em><?= htmlspecialchars($data['catatan']) ?></em></td></tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
    } else {
        echo '<p class="text-center text-muted py-3">Data tidak ditemukan</p>';
    }
}
?>
