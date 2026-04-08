<?php
/**
 * STAFF DASHBOARD - SiPagu
 * Modern Minimalist — konsisten dengan admin & koordinator
 */
if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/function_helper.php';
require_once __DIR__ . '/../includes/honor_helper.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role_user'] != 'staff') {
    header("Location: " . BASE_URL . "index.php"); exit();
}

$id_user = (int)$_SESSION['id_user'];

$q_user = mysqli_query($koneksi, "SELECT * FROM t_user WHERE id_user = '$id_user'");
$user   = mysqli_fetch_assoc($q_user);

$periode_aktif = getPeriodeAktif();

// Total honor mengajar
$r = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COALESCE(SUM(thd.total_honor),0) as total
     FROM t_transaksi_honor_dosen thd
     JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
     WHERE j.id_user = '$id_user' AND thd.status != 'digabung'"
));
$total_mengajar = (int)($r['total'] ?? 0);

// Total honor PA/TA
$r = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COALESCE(SUM(p.honor_std),0) as total
     FROM t_transaksi_pa_ta tpt
     JOIN t_panitia p ON tpt.id_panitia = p.id_pnt
     WHERE tpt.id_user = '$id_user'"
));
$total_pata = (int)($r['total'] ?? 0);

// Total honor ujian
$r = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COALESCE(SUM(p.honor_std),0) as total
     FROM t_transaksi_ujian tu
     JOIN t_panitia p ON tu.id_panitia = p.id_pnt
     WHERE tu.id_user = '$id_user'"
));
$total_ujian = (int)($r['total'] ?? 0);

$total_honor  = $total_mengajar + $total_pata + $total_ujian;
$perhitungan  = hitungHonorStaff($total_honor);

// TM periode aktif
$pA = mysqli_real_escape_string($koneksi, $periode_aktif['periode_awal']);
$pE = mysqli_real_escape_string($koneksi, $periode_aktif['periode_akhir']);
$r  = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) as jml FROM t_pertemuan_dosen pd
     JOIN t_jadwal j ON pd.id_jadwal = j.id_jdwl
     WHERE j.id_user = '$id_user' AND pd.tanggal BETWEEN '$pA' AND '$pE'"
));
$jml_ptm_aktif = (int)($r['jml'] ?? 0);

// Status transaksi
$r = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) as jml FROM t_transaksi_honor_dosen thd
     JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
     WHERE j.id_user = '$id_user' AND thd.status = 'pending'"
));
$jml_pending = (int)($r['jml'] ?? 0);

$r = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) as jml FROM t_transaksi_honor_dosen thd
     JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
     WHERE j.id_user = '$id_user' AND thd.status = 'dibayar'"
));
$jml_dibayar = (int)($r['jml'] ?? 0);

// Chart data: 6 periode terakhir
$q_stat = mysqli_query($koneksi,
    "SELECT thd.bulan, COALESCE(SUM(thd.total_honor),0) as total
     FROM t_transaksi_honor_dosen thd
     JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
     WHERE j.id_user = '$id_user' AND thd.status != 'digabung'
     GROUP BY thd.bulan, thd.periode_awal
     ORDER BY thd.periode_awal DESC LIMIT 6"
);
$chart_labels = [];
$chart_values = [];
while ($r = mysqli_fetch_assoc($q_stat)) {
    $chart_labels[] = ucfirst($r['bulan']);
    $chart_values[] = (int)$r['total'];
}
$chart_labels = array_reverse($chart_labels);
$chart_values = array_reverse($chart_values);

// Transaksi terbaru
$q_recent = mysqli_query($koneksi,
    "SELECT thd.id_thd, thd.bulan, thd.periode_awal, thd.periode_akhir,
            thd.jml_tm, thd.sks_tempuh, thd.total_honor, thd.status,
            j.kode_matkul, j.nama_matkul
     FROM t_transaksi_honor_dosen thd
     JOIN t_jadwal j ON thd.id_jadwal = j.id_jdwl
     WHERE j.id_user = '$id_user'
     ORDER BY thd.id_thd DESC LIMIT 5"
);
$recent_list = [];
while ($r = mysqli_fetch_assoc($q_recent)) $recent_list[] = $r;

$total_thd   = $jml_pending + $jml_dibayar;
$pct_dibayar = $total_thd > 0 ? round($jml_dibayar / $total_thd * 100) : 0;
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
<section class="section">

    <!-- ── Section Header ─────────────────────────────── -->
    <div class="section-header pt-4 pb-0">
        <div class="d-flex align-items-center justify-content-between w-100 flex-wrap" style="gap:12px;">
            <div>
                <h1 class="h3 mb-1" style="font-weight:700;color:#1a1a2e;">Dashboard Staff</h1>
                <p class="text-muted mb-0" style="font-size:.875rem;">
                    Selamat datang, <strong><?= htmlspecialchars($user['nama_user'] ?? 'Staff') ?></strong>
                </p>
            </div>
            <div class="d-flex align-items-center" style="gap:8px;">
                <span class="badge" style="background:rgba(0,61,122,0.08);color:#003d7a;padding:8px 14px;border-radius:999px;font-size:.78rem;font-weight:600;">
                    <i class="fas fa-calendar-day mr-1"></i><?= date('d F Y') ?>
                </span>
            </div>
        </div>
    </div>

    <div class="section-body mt-4">

        <!-- ══ STAT CARDS ════════════════════════════════ -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 mb-3">
                <div class="stat-card stat-card-primary" style="display:flex;align-items:center;gap:16px;">
                    <div class="stat-icon bg-primary-soft text-primary" style="width:52px;height:52px;border-radius:14px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-content" style="flex:1;min-width:0;">
                        <h3 class="stat-number" style="font-size:1.1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= formatRupiah($total_mengajar) ?></h3>
                        <p class="stat-label mb-0">Honor Mengajar</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 mb-3">
                <div class="stat-card stat-card-success" style="display:flex;align-items:center;gap:16px;">
                    <div class="stat-icon bg-success-soft text-success" style="width:52px;height:52px;border-radius:14px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content" style="flex:1;min-width:0;">
                        <h3 class="stat-number" style="font-size:1.1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= formatRupiah($total_pata) ?></h3>
                        <p class="stat-label mb-0">Honor PA/TA</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 mb-3">
                <div class="stat-card stat-card-warning" style="display:flex;align-items:center;gap:16px;">
                    <div class="stat-icon bg-warning-soft text-warning" style="width:52px;height:52px;border-radius:14px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <div class="stat-content" style="flex:1;min-width:0;">
                        <h3 class="stat-number" style="font-size:1.1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= formatRupiah($total_ujian) ?></h3>
                        <p class="stat-label mb-0">Honor Ujian</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 mb-3">
                <div class="stat-card stat-card-info" style="display:flex;align-items:center;gap:16px;border:2px solid rgba(59,130,246,0.2);">
                    <div class="stat-icon bg-info-soft text-info" style="width:52px;height:52px;border-radius:14px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="stat-content" style="flex:1;min-width:0;">
                        <h3 class="stat-number text-info" style="font-size:1.1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= formatRupiah($perhitungan['bersih']) ?></h3>
                        <p class="stat-label mb-0">Honor Bersih</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ BANNER PERIODE AKTIF ═══════════════════════ -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="content-card content-card-primary" style="border-radius:16px;overflow:hidden;">
                    <div class="card-body" style="padding:20px 24px;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:16px;">
                            <div class="d-flex align-items-center" style="gap:16px;">
                                <div style="width:48px;height:48px;border-radius:12px;background:rgba(0,61,122,0.08);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-calendar-check" style="color:#003d7a;font-size:1.1rem;"></i>
                                </div>
                                <div>
                                    <div style="font-weight:700;font-size:.95rem;color:#1a1a2e;">
                                        Periode Aktif: <?= ucfirst($periode_aktif['bulan']) . ' ' . $periode_aktif['tahun'] ?>
                                    </div>
                                    <div style="font-size:.8rem;color:#718096;margin-top:2px;">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        <?= $periode_aktif['periode_awal'] ?> s/d <?= $periode_aktif['periode_akhir'] ?>
                                        &nbsp;·&nbsp; Cut-off tanggal 25
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center" style="gap:20px;flex-wrap:wrap;">
                                <div class="text-center">
                                    <div style="font-size:1.75rem;font-weight:800;color:#003d7a;line-height:1;"><?= $jml_ptm_aktif ?></div>
                                    <div style="font-size:.72rem;color:#718096;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:2px;">TM Periode Ini</div>
                                </div>
                                <div style="width:1px;height:40px;background:#eef2f7;"></div>
                                <div class="text-center">
                                    <div style="font-size:1.75rem;font-weight:800;color:<?= $jml_pending > 0 ? '#f59e0b' : '#10b981' ?>;line-height:1;"><?= $jml_pending ?></div>
                                    <div style="font-size:.72rem;color:#718096;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:2px;">Pending</div>
                                </div>
                                <div style="width:1px;height:40px;background:#eef2f7;"></div>
                                <?php if ($jml_ptm_aktif < 3): ?>
                                <span style="background:rgba(245,158,11,0.1);color:#d97706;border:1px solid rgba(245,158,11,0.3);padding:6px 14px;border-radius:999px;font-size:.78rem;font-weight:600;">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Belum cukup (min. 3)
                                </span>
                                <?php else: ?>
                                <span style="background:rgba(16,185,129,0.1);color:#059669;border:1px solid rgba(16,185,129,0.3);padding:6px 14px;border-radius:999px;font-size:.78rem;font-weight:600;">
                                    <i class="fas fa-check-circle mr-1"></i>TM Cukup
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ MAIN GRID: Rincian + Chart + Activity ════ -->
        <div class="row">

            <!-- Rincian Perhitungan Honor -->
            <div class="col-12 col-lg-4 mb-4">
                <div class="content-card content-card-success h-100" style="border-radius:16px;">
                    <div class="card-header-simple" style="padding:18px 20px;">
                        <span class="card-title">
                            <i class="fas fa-calculator mr-2" style="color:#10b981;"></i>Rincian Honor
                        </span>
                        <a href="<?= BASE_URL ?>staff/slip_honor.php" class="btn btn-sm" style="background:rgba(16,185,129,0.1);color:#059669;border:none;border-radius:8px;font-weight:600;font-size:.78rem;padding:5px 12px;">
                            <i class="fas fa-file-invoice-dollar mr-1"></i>Slip
                        </a>
                    </div>
                    <div class="card-body" style="padding:20px;">

                        <!-- Donut progress status -->
                        <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding:14px;background:#f8fafc;border-radius:12px;">
                            <div style="position:relative;width:60px;height:60px;flex-shrink:0;">
                                <svg viewBox="0 0 36 36" style="width:60px;height:60px;transform:rotate(-90deg);">
                                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#eef2f7" stroke-width="3"/>
                                    <circle cx="18" cy="18" r="15.9" fill="none" stroke="#10b981" stroke-width="3"
                                        stroke-dasharray="<?= $pct_dibayar ?>,100" stroke-linecap="round"/>
                                </svg>
                                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:.72rem;font-weight:800;color:#059669;"><?= $pct_dibayar ?>%</div>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:.9rem;color:#1a1a2e;"><?= $jml_dibayar ?> Dibayar</div>
                                <div style="font-size:.78rem;color:#718096;margin-top:2px;"><?= $jml_pending ?> Pending · <?= $total_thd ?> Total TRX</div>
                            </div>
                        </div>

                        <!-- Rincian items -->
                        <?php
                        $items = [
                            ['Nominal Bruto',    formatRupiah($perhitungan['nominal']),  null],
                            ['Pajak PPh21 (5%)', '− '.formatRupiah($perhitungan['pajak']),  'danger'],
                            ['Sisa Pajak',       formatRupiah($perhitungan['sisa']),       null],
                            ['Potongan (5%)',    '− '.formatRupiah($perhitungan['potongan']), 'warning'],
                        ];
                        foreach ($items as $item): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #f0f4f8;font-size:.85rem;">
                            <span style="color:#718096;"><?= $item[0] ?></span>
                            <span style="font-weight:500;<?= $item[2]==='danger' ? 'color:#ef4444;' : ($item[2]==='warning' ? 'color:#f59e0b;' : '') ?>"><?= $item[1] ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;margin-top:8px;background:linear-gradient(135deg,rgba(16,185,129,0.08),rgba(5,150,105,0.05));border-radius:10px;border:1px solid rgba(16,185,129,0.2);">
                            <span style="font-weight:700;font-size:.875rem;color:#1a1a2e;">Total Bersih</span>
                            <span style="font-weight:800;font-size:1rem;color:#059669;"><?= formatRupiah($perhitungan['bersih']) ?></span>
                        </div>

                        <!-- Profil singkat -->
                        <div style="margin-top:16px;padding-top:16px;border-top:1px solid #eef2f7;display:flex;align-items:center;gap:12px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#003d7a,#3b82f6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.875rem;flex-shrink:0;">
                                <?= strtoupper(mb_substr($user['nama_user'] ?? 'S', 0, 1)) ?>
                            </div>
                            <div style="font-size:.78rem;line-height:1.7;min-width:0;">
                                <div style="font-weight:700;color:#1a1a2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($user['nama_user'] ?? '-') ?></div>
                                <div style="color:#718096;">NPP: <?= htmlspecialchars($user['npp_user'] ?? '-') ?></div>
                                <div style="color:#718096;">Rek: <?= htmlspecialchars($user['norek_user'] ?? '-') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart + Activity -->
            <div class="col-12 col-lg-8 mb-4">

                <!-- Chart Honor -->
                <?php if (!empty($chart_labels)): ?>
                <div class="content-card content-card-primary mb-4" style="border-radius:16px;">
                    <div class="card-header-simple" style="padding:18px 20px;">
                        <span class="card-title">
                            <i class="fas fa-chart-bar mr-2" style="color:#003d7a;"></i>Tren Honor 6 Bulan Terakhir
                        </span>
                    </div>
                    <div class="card-body" style="padding:16px 20px;">
                        <canvas id="honorChart" style="height:180px;"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Transactions -->
                <div class="content-card content-card-info" style="border-radius:16px;">
                    <div class="card-header-simple" style="padding:18px 20px;">
                        <span class="card-title">
                            <i class="fas fa-history mr-2" style="color:#3b82f6;"></i>Transaksi Terbaru
                        </span>
                        <a href="<?= BASE_URL ?>staff/riwayat_honor.php"
                           style="font-size:.78rem;color:#3b82f6;font-weight:600;text-decoration:none;">
                            Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div style="padding:0 8px 8px;">
                        <?php if (empty($recent_list)): ?>
                        <div style="text-align:center;padding:32px;color:#a0aec0;">
                            <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                            <span style="font-size:.875rem;">Belum ada transaksi honor</span>
                        </div>
                        <?php else: ?>
                        <?php
                        $statusConf = [
                            'pending'  => ['#f59e0b', 'fas fa-clock',        '#fffbeb', '#fef3c7'],
                            'digabung' => ['#3b82f6', 'fas fa-layer-group',  '#eff6ff', '#dbeafe'],
                            'dibayar'  => ['#10b981', 'fas fa-check-circle', '#ecfdf5', '#d1fae5'],
                        ];
                        foreach ($recent_list as $r):
                            $sc   = $statusConf[$r['status']] ?? ['#718096', 'fas fa-question', '#f1f5f9', '#e2e8f0'];
                            $sclr = $sc[0]; $sicon = $sc[1]; $sbg = $sc[2];
                        ?>
                        <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 12px;border-radius:12px;margin-bottom:4px;transition:background .15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <div style="width:36px;height:36px;border-radius:10px;background:<?= $sbg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="<?= $sicon ?>" style="color:<?= $sclr ?>;font-size:.85rem;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.875rem;font-weight:600;color:#1a1a2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($r['kode_matkul']) ?> — <?= htmlspecialchars($r['nama_matkul']) ?>
                                </div>
                                <div style="font-size:.78rem;color:#718096;margin-top:2px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <span><?= ucfirst($r['bulan']) ?></span>
                                    <span>·</span>
                                    <span><?= $r['jml_tm'] ?> TM, <?= $r['sks_tempuh'] ?> SKS</span>
                                    <span>·</span>
                                    <span style="font-weight:600;color:#1a1a2e;"><?= formatRupiah($r['total_honor']) ?></span>
                                </div>
                            </div>
                            <span style="background:<?= $sbg ?>;color:<?= $sclr ?>;padding:3px 10px;border-radius:999px;font-size:.7rem;font-weight:700;white-space:nowrap;flex-shrink:0;align-self:center;">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>
</div>

<?php if (!empty($chart_labels)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('honorChart');
    if (!ctx) return;
    var labels = <?= json_encode($chart_labels) ?>;
    var values = <?= json_encode($chart_values) ?>;
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Honor (Rp)',
                data: values,
                backgroundColor: 'rgba(0,61,122,0.15)',
                borderColor: '#003d7a',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
                hoverBackgroundColor: 'rgba(0,61,122,0.25)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a1a2e',
                    titleColor: '#a0aec0',
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(c) {
                            return ' Rp ' + c.raw.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 }, color: '#718096' }
                },
                y: {
                    grid: { color: '#f0f4f8' },
                    ticks: {
                        font: { size: 11 }, color: '#718096',
                        callback: function(v) { return 'Rp ' + (v/1000).toFixed(0) + 'k'; }
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/footer_scripts.php'; ?>
