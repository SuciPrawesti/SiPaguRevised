<?php
include '../../config.php';
require_once __DIR__ . '/../../includes/honor_helper.php';

$page_title = "Data Transaksi Honor Dosen";
$limit = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$total_data  = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) as total FROM t_transaksi_honor_dosen"))['total'];
$total_pages = max(1, ceil($total_data / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

$query = mysqli_query($koneksi, "
    SELECT thd.*, j.kode_matkul, j.nama_matkul,
           u.nama_user AS nama_dosen,
           COALESCE(u.honor_persks,50000) as honor_persks
    FROM t_transaksi_honor_dosen thd
    LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    LEFT JOIN t_user u ON j.id_user = u.id_user
    ORDER BY thd.id_thd DESC
    LIMIT $limit OFFSET $offset
");
?>
<table class="table table-striped table-sm">
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
    </tr>
</thead>
<tbody>
<?php
$no = $offset + 1;
$statusMap = [
    'pending'  => ['warning','Pending'],
    'digabung' => ['info','Digabung'],
    'dibayar'  => ['success','Dibayar'],
];
while ($r = mysqli_fetch_assoc($query)):
    $st = $statusMap[$r['status']] ?? ['secondary', ucfirst($r['status'])];
?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= ucfirst($r['bulan']) ?></td>
    <td>
        <small class="text-muted">
            <?= htmlspecialchars($r['periode_awal'] ?? '-') ?><br>
            <?= htmlspecialchars($r['periode_akhir'] ?? '-') ?>
        </small>
    </td>
    <td>
        <strong><?= htmlspecialchars($r['kode_matkul'] ?? '-') ?></strong>
        <small class="d-block text-muted"><?= htmlspecialchars($r['nama_matkul'] ?? '-') ?></small>
    </td>
    <td><?= htmlspecialchars($r['nama_dosen'] ?? '-') ?></td>
    <td class="text-center"><?= $r['jml_tm'] ?></td>
    <td class="text-center"><?= $r['sks_tempuh'] ?></td>
    <td class="text-right"><strong><?= 'Rp '.number_format((int)($r['total_honor']??0),0,',','.') ?></strong></td>
    <td class="text-center">
        <span class="badge badge-<?= $st[0] ?>"><?= $st[1] ?></span>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php if ($total_pages > 1): ?>
<nav>
<ul class="pagination pagination-sm justify-content-center">
    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
    <li class="page-item <?= ($p == $page)?'active':'' ?>">
        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
    </li>
    <?php endfor; ?>
</ul>
</nav>
<?php endif; ?>
