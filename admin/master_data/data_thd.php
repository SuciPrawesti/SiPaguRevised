<?php
include '../../config.php';
require_once __DIR__ . '/../../includes/honor_helper.php';

$page_title = "Data Honor Dosen";

// Pagination
$limit = 10;
$page  = max(1, (int)($_GET['page'] ?? 1));

// Filter
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_bulan  = isset($_GET['bulan'])  ? $_GET['bulan']  : '';

$where = "WHERE 1=1";
if ($filter_status) $where .= " AND thd.status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
if ($filter_bulan)  $where .= " AND thd.bulan  = '" . mysqli_real_escape_string($koneksi, $filter_bulan)  . "'";

$total_data  = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) as total
    FROM t_transaksi_honor_dosen thd
    LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    LEFT JOIN t_user   u ON j.id_user     = u.id_user
    $where
"))['total'];

$total_pages = max(1, ceil($total_data / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

$query = mysqli_query($koneksi, "
    SELECT thd.*, j.kode_matkul, j.nama_matkul,
           u.nama_user AS nama_dosen, u.npp_user,
           COALESCE(u.honor_persks, 50000) AS honor_persks
    FROM t_transaksi_honor_dosen thd
    LEFT JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
    LEFT JOIN t_user   u ON j.id_user     = u.id_user
    $where
    ORDER BY thd.id_thd DESC
    LIMIT $limit OFFSET $offset
");

// ── STAT CARDS ──────────────────────────────────────────────────────────────
$stat_total   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM t_transaksi_honor_dosen"))['c'];
$stat_pending  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM t_transaksi_honor_dosen WHERE status='pending'"))['c'];
$stat_dibayar  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM t_transaksi_honor_dosen WHERE status='dibayar'"))['c'];
$stat_nominal  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(total_honor),0) as s FROM t_transaksi_honor_dosen WHERE status='dibayar'"))['s'];
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1><i class="fas fa-money-bill-wave mr-2"></i>Data Honor Dosen</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= BASE_URL ?>admin/index.php">Dashboard</a></div>
                <div class="breadcrumb-item">Master Data</div>
                <div class="breadcrumb-item">Data Honor Dosen</div>
            </div>
        </div>

        <div class="section-body">

            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="up-alert up-alert-success up-alert-dismissible">
                <div class="up-alert-icon"><i class="fas fa-check-circle"></i></div>
                <div class="up-alert-content"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
                <button class="up-alert-close" onclick="this.closest('.up-alert').remove()"><span>×</span></button>
            </div>
            <?php unset($_SESSION['success_message']); endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
            <div class="up-alert up-alert-danger up-alert-dismissible">
                <div class="up-alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div class="up-alert-content">
                    <?php
                    $error_msg = $_SESSION['error_message'];
                    if (strpos($error_msg, "\n") !== false) {
                        $errors = explode("\n", $error_msg);
                        echo '<strong>' . array_shift($errors) . '</strong>';
                        echo '<ul style="margin-top:8px;margin-bottom:0;padding-left:20px;">';
                        foreach ($errors as $err) {
                            if (trim($err)) echo '<li>' . htmlspecialchars(trim($err)) . '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo htmlspecialchars($error_msg);
                    }
                    ?>
                </div>
                <button class="up-alert-close" onclick="this.closest('.up-alert').remove()"><span>×</span></button>
            </div>
            <?php unset($_SESSION['error_message']); endif; ?>

            <!-- STAT CARDS -->
            <div class="up-stat-row">
                <div class="up-stat-card">
                    <div class="up-stat-value"><?= number_format($stat_total) ?></div>
                    <div class="up-stat-label">Total Transaksi</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value text-warning"><?= number_format($stat_pending) ?></div>
                    <div class="up-stat-label">Pending</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value text-success"><?= number_format($stat_dibayar) ?></div>
                    <div class="up-stat-label">Dibayar</div>
                </div>
                <div class="up-stat-card">
                    <div class="up-stat-value text-primary" style="font-size:1.1rem;">
                        Rp <?= number_format($stat_nominal, 0, ',', '.') ?>
                    </div>
                    <div class="up-stat-label">Total Terbayar</div>
                </div>
            </div>

            <!-- MAIN CARD -->
            <div class="up-main-card">
                <div class="up-main-card-header">
                    <div class="up-card-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h5>Daftar Transaksi Honor Dosen</h5>
                    <div class="ml-auto d-flex align-items-center flex-wrap gap-2">
                        <!-- Filter Status -->
                        <select class="up-input" id="filterStatus" onchange="applyFilter()" style="width:130px;padding:6px 10px;">
                            <option value="">Semua Status</option>
                            <option value="pending"  <?= $filter_status === 'pending'  ? 'selected' : '' ?>>Pending</option>
                            <option value="digabung" <?= $filter_status === 'digabung' ? 'selected' : '' ?>>Digabung</option>
                            <option value="dibayar"  <?= $filter_status === 'dibayar'  ? 'selected' : '' ?>>Dibayar</option>
                        </select>
                        <!-- Filter Bulan -->
                        <select class="up-input" id="filterBulan" onchange="applyFilter()" style="width:140px;padding:6px 10px;">
                            <option value="">Semua Bulan</option>
                            <?php
                            $bulan_list = ['januari','februari','maret','april','mei','juni',
                                           'juli','agustus','september','oktober','november','desember'];
                            foreach ($bulan_list as $b):
                            ?>
                            <option value="<?= $b ?>" <?= $filter_bulan === $b ? 'selected' : '' ?>><?= ucfirst($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Search -->
                        <div class="up-search-box" style="width:220px;">
                            <i class="fas fa-search"></i>
                            <input type="text" class="up-search-input" id="searchInput" placeholder="Cari dosen / matkul..." onkeyup="filterTable()">
                        </div>
                        <!-- Action Buttons -->
                        <a href="<?= BASE_URL ?>admin/upload_data/pertemuan_dosen.php" class="up-btn up-btn-secondary ml-1">
                            <i class="fas fa-plus mr-1"></i> Input Pertemuan
                        </a>
                        <a href="<?= BASE_URL ?>admin/upload_data/hitung_honor.php" class="up-btn up-btn-primary ml-1">
                            <i class="fas fa-calculator mr-1"></i> Hitung Honor
                        </a>
                    </div>
                </div>

                <div class="up-card-body">
                    <div class="up-table-responsive">
                        <table class="up-table up-table-hover" id="dataTable">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>Bulan</th>
                                    <th>Periode</th>
                                    <th>Mata Kuliah</th>
                                    <th>Dosen</th>
                                    <th class="text-center" width="60">TM</th>
                                    <th class="text-center" width="60">SKS</th>
                                    <th class="text-right">Honor</th>
                                    <th class="text-center" width="110">Status</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = $offset + 1;
                                $statusMap = [
                                    'pending'  => ['warning', 'clock',        'Pending'],
                                    'digabung' => ['info',    'code-branch',   'Digabung'],
                                    'dibayar'  => ['success', 'check-circle',  'Dibayar'],
                                ];
                                while ($row = mysqli_fetch_assoc($query)):
                                    $st = $statusMap[$row['status']] ?? ['secondary', 'question-circle', ucfirst($row['status'] ?? '-')];
                                ?>
                                <tr>
                                    <td><span class="up-badge up-badge-default"><?= $no++ ?></span></td>
                                    <td><strong><?= ucfirst(htmlspecialchars($row['bulan'] ?? '-')) ?></strong></td>
                                    <td>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($row['periode_awal']  ?? '-') ?><br>
                                            s/d <?= htmlspecialchars($row['periode_akhir'] ?? '-') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['kode_matkul'] ?? '-') ?></strong>
                                        <small class="d-block text-muted"><?= htmlspecialchars($row['nama_matkul'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="up-avatar-sm bg-primary text-white mr-2">
                                                <?= strtoupper(substr($row['nama_dosen'] ?? 'D', 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div><?= htmlspecialchars($row['nama_dosen'] ?? '-') ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($row['npp_user'] ?? '') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="up-badge up-badge-default"><?= (int)$row['jml_tm'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="up-badge up-badge-info"><?= (int)$row['sks_tempuh'] ?> SKS</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="up-badge up-badge-success">
                                            Rp <?= number_format((int)($row['total_honor'] ?? 0), 0, ',', '.') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="up-badge up-badge-<?= $st[0] ?>">
                                            <i class="fas fa-<?= $st[1] ?> mr-1"></i><?= $st[2] ?>
                                        </span>
                                        <?php if (!empty($row['catatan'])): ?>
                                        <br><small class="text-muted" style="font-size:.7rem;" title="<?= htmlspecialchars($row['catatan']) ?>">
                                            <?= mb_strimwidth($row['catatan'], 0, 20, '...') ?>
                                        </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="#" onclick="viewData(<?= $row['id_thd'] ?>)" class="up-btn-icon up-btn-icon-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>admin/CRUD/edit_data/edit_thd.php?id_thd=<?= $row['id_thd'] ?>" class="up-btn-icon up-btn-icon-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if (($row['status'] ?? '') !== 'dibayar'): ?>
                                            <a href="<?= BASE_URL ?>admin/upload_data/cetak_slip_honor.php?id_thd=<?= $row['id_thd'] ?>" class="up-btn-icon up-btn-icon-info" title="Cetak Slip" target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?= $row['id_thd'] ?>&page=<?= $page ?><?= $filter_status ? '&status='.$filter_status : '' ?><?= $filter_bulan ? '&bulan='.$filter_bulan : '' ?>"
                                               class="up-btn-icon up-btn-icon-danger" title="Hapus"
                                               onclick="return confirm('Yakin ingin menghapus data honor <?= htmlspecialchars(addslashes($row['nama_dosen'] ?? '')) ?> bulan <?= ucfirst($row['bulan'] ?? '') ?>?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>

                                <?php if ($total_data == 0): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">Belum ada data honor dosen<?= $filter_status || $filter_bulan ? ' untuk filter ini' : '' ?></p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <?php
                    $qs_extra = '';
                    if ($filter_status) $qs_extra .= '&status=' . urlencode($filter_status);
                    if ($filter_bulan)  $qs_extra .= '&bulan='  . urlencode($filter_bulan);
                    ?>
                    <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">
                            Menampilkan halaman <?= $page ?> dari <?= $total_pages ?> (Total <?= $total_data ?> data)
                        </div>
                        <ul class="up-pagination">
                            <li class="up-page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?page=<?= $page - 1 . $qs_extra ?>"><i class="fas fa-chevron-left"></i></a>
                            </li>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page   = min($total_pages, $page + 2);

                            if ($start_page > 1) {
                                echo '<li class="up-page-item"><a class="up-page-link" href="?page=1' . $qs_extra . '">1</a></li>';
                                if ($start_page > 2) echo '<li class="up-page-item disabled"><a class="up-page-link">...</a></li>';
                            }
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="up-page-item ' . ($i == $page ? 'active' : '') . '">';
                                echo '<a class="up-page-link" href="?page=' . $i . $qs_extra . '">' . $i . '</a>';
                                echo '</li>';
                            }
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) echo '<li class="up-page-item disabled"><a class="up-page-link">...</a></li>';
                                echo '<li class="up-page-item"><a class="up-page-link" href="?page=' . $total_pages . $qs_extra . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <li class="up-page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="up-page-link" href="?page=<?= $page + 1 . $qs_extra ?>"><i class="fas fa-chevron-right"></i></a>
                            </li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted small">Menampilkan <?= $total_data ?> data</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Detail Honor Dosen -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Detail Honor Dosen</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="viewContent">
                <div class="text-center py-3">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="up-btn up-btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function filterTable() {
    var input  = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();
    var table  = document.getElementById("dataTable");
    var rows   = table.getElementsByTagName("tr");

    for (var i = 1; i < rows.length; i++) {
        var tds   = rows[i].getElementsByTagName("td");
        var found = false;
        for (var j = 0; j < tds.length - 1; j++) {
            if (tds[j]) {
                var txt = tds[j].textContent || tds[j].innerText;
                if (txt.toUpperCase().indexOf(filter) > -1) { found = true; break; }
            }
        }
        rows[i].style.display = found ? "" : "none";
    }
}

function applyFilter() {
    var status = document.getElementById('filterStatus').value;
    var bulan  = document.getElementById('filterBulan').value;
    var url    = '?page=1';
    if (status) url += '&status=' + encodeURIComponent(status);
    if (bulan)  url += '&bulan='  + encodeURIComponent(bulan);
    window.location.href = url;
}

function viewData(id) {
    document.getElementById('viewContent').innerHTML =
        '<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-2">Memuat data...</p></div>';
    $('#viewModal').modal('show');
    fetch('ajax/view_thd.php?id=' + id)
        .then(function(r) { return r.text(); })
        .then(function(html) {
            document.getElementById('viewContent').innerHTML = html;
        })
        .catch(function() {
            document.getElementById('viewContent').innerHTML =
                '<p class="text-center text-danger">Gagal memuat data.</p>';
        });
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    document.querySelectorAll('.up-alert').forEach(function(alert) {
        alert.style.transition = 'opacity .3s';
        alert.style.opacity = '0';
        setTimeout(function() { alert.style.display = 'none'; }, 300);
    });
}, 5000);
</script>

<?php
include __DIR__ . '/../includes/footer.php';
include __DIR__ . '/../includes/footer_scripts.php';
?>