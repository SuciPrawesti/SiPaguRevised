<?php
/**
 * HELPER LOGIKA BISNIS HONOR DOSEN - SiPagu v2.0
 * 
 * Berisi fungsi-fungsi untuk:
 * - Perhitungan cut-off bulanan (tanggal 25 setiap bulan)
 * - Pengelompokan pertemuan per periode
 * - Aturan minimum 3 pertemuan
 * - Perhitungan honor otomatis
 */

if (!function_exists('getCutoffPeriode')) {

    /**
     * Menghitung periode cut-off berdasarkan tanggal hari ini atau tanggal tertentu.
     * Cut-off berlaku pada tanggal 25 setiap bulan.
     *
     * Contoh logika:
     *   - 26 Jan – 25 Feb → label "Februari"
     *   - 26 Feb – 25 Mar → label "Maret"
     *
     * @param string|null $tanggal  Format Y-m-d, null = hari ini
     * @param int         $cutoff_tanggal  Default 25
     * @return array ['periode_awal'=>'Y-m-d', 'periode_akhir'=>'Y-m-d', 'bulan'=>'maret', 'tahun'=>2026]
     */
    function getCutoffPeriode($tanggal = null, $cutoff_tanggal = 25)
    {
        $dt = $tanggal ? new DateTime($tanggal) : new DateTime();
        $day   = (int) $dt->format('d');
        $month = (int) $dt->format('m');
        $year  = (int) $dt->format('Y');

        // Jika tanggal <= cutoff: periode ini sudah tutup bulan lalu
        // Jika tanggal > cutoff: periode ini masih berjalan, bulan = bulan+1
        if ($day <= $cutoff_tanggal) {
            // Masih dalam periode bulan ini (label = bulan sekarang)
            $label_bulan = $month;
            $label_tahun = $year;
        } else {
            // Lewat cut-off, periode berikutnya
            $label_bulan = $month + 1;
            $label_tahun = $year;
            if ($label_bulan > 12) {
                $label_bulan = 1;
                $label_tahun++;
            }
        }

        // Awal periode = tanggal 26 bulan sebelum label
        $prev_month = $label_bulan - 1;
        $prev_year  = $label_tahun;
        if ($prev_month < 1) {
            $prev_month = 12;
            $prev_year--;
        }
        $periode_awal  = sprintf('%04d-%02d-%02d', $prev_year, $prev_month, $cutoff_tanggal + 1);
        $periode_akhir = sprintf('%04d-%02d-%02d', $label_tahun, $label_bulan, $cutoff_tanggal);

        $bulan_nama = [
            1=>'januari', 2=>'februari', 3=>'maret', 4=>'april',
            5=>'mei', 6=>'juni', 7=>'juli', 8=>'agustus',
            9=>'september', 10=>'oktober', 11=>'november', 12=>'desember'
        ];

        return [
            'periode_awal'  => $periode_awal,
            'periode_akhir' => $periode_akhir,
            'bulan'         => $bulan_nama[$label_bulan],
            'bulan_num'     => $label_bulan,
            'tahun'         => $label_tahun,
        ];
    }

    /**
     * Mendapatkan periode cut-off untuk bulan & tahun tertentu.
     * Berguna untuk filter tampilan.
     *
     * @param string $bulan_nama  mis. 'maret'
     * @param int    $tahun
     * @param int    $cutoff_tanggal  Default 25
     * @return array ['periode_awal'=>'Y-m-d', 'periode_akhir'=>'Y-m-d']
     */
    function getPeriodeForBulan($bulan_nama, $tahun, $cutoff_tanggal = 25)
    {
        $bulan_map = [
            'januari'=>1, 'februari'=>2, 'maret'=>3, 'april'=>4,
            'mei'=>5, 'juni'=>6, 'juli'=>7, 'agustus'=>8,
            'september'=>9, 'oktober'=>10, 'november'=>11, 'desember'=>12
        ];

        $label_bulan = $bulan_map[strtolower(trim($bulan_nama))] ?? 1;
        $label_tahun = (int)$tahun;

        $prev_month = $label_bulan - 1;
        $prev_year  = $label_tahun;
        if ($prev_month < 1) {
            $prev_month = 12;
            $prev_year--;
        }

        return [
            'periode_awal'  => sprintf('%04d-%02d-%02d', $prev_year, $prev_month, $cutoff_tanggal + 1),
            'periode_akhir' => sprintf('%04d-%02d-%02d', $label_tahun, $label_bulan, $cutoff_tanggal),
        ];
    }

    /**
     * Mengambil semua pertemuan milik jadwal tertentu dalam satu periode cut-off.
     *
     * @param mysqli $koneksi
     * @param int    $id_jadwal
     * @param string $periode_awal   Y-m-d
     * @param string $periode_akhir  Y-m-d
     * @return array  Array of pertemuan rows
     */
    function getPertemuanInPeriode($koneksi, $id_jadwal, $periode_awal, $periode_akhir)
    {
        $id_jadwal    = (int)$id_jadwal;
        $periode_awal  = mysqli_real_escape_string($koneksi, $periode_awal);
        $periode_akhir = mysqli_real_escape_string($koneksi, $periode_akhir);

        $result = mysqli_query($koneksi, "
            SELECT * FROM t_pertemuan_dosen
            WHERE id_jadwal = '$id_jadwal'
              AND tanggal BETWEEN '$periode_awal' AND '$periode_akhir'
            ORDER BY tanggal ASC
        ");

        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Menghitung jumlah pertemuan & total SKS dalam satu periode.
     *
     * @param array $pertemuan_rows  dari getPertemuanInPeriode()
     * @return array ['jumlah_tm'=>int, 'total_sks'=>int]
     */
    function hitungRekapPeriode(array $pertemuan_rows)
    {
        $jumlah_tm  = count($pertemuan_rows);
        $total_sks  = 0;
        foreach ($pertemuan_rows as $p) {
            $total_sks += (int)($p['sks'] ?? 1);
        }
        return [
            'jumlah_tm' => $jumlah_tm,
            'total_sks' => $total_sks,
        ];
    }

    /**
     * Memproses honor dosen untuk satu jadwal pada satu periode cut-off.
     * Menerapkan aturan:
     *  - < 3 pertemuan → status 'digabung', tidak buat transaksi baru
     *  - >= 3 pertemuan → buat/update transaksi dengan status 'pending'
     *
     * @param mysqli $koneksi
     * @param int    $id_jadwal
     * @param string $periode_awal
     * @param string $periode_akhir
     * @param string $bulan_label   mis. 'maret'
     * @param int    $honor_persks  dari t_user
     * @param int    $min_tm        Minimum pertemuan (default 3)
     * @param int    $cutoff_tanggal
     * @return array ['status'=>string, 'message'=>string, 'id_thd'=>int|null]
     */
    function prosesHonorPeriode(
        $koneksi,
        $id_jadwal,
        $periode_awal,
        $periode_akhir,
        $bulan_label,
        $honor_persks,
        $min_tm = 3,
        $cutoff_tanggal = 25
    ) {
        $id_jadwal     = (int)$id_jadwal;
        $honor_persks  = (int)$honor_persks;
        $min_tm        = (int)$min_tm;

        // Ambil pertemuan dalam periode ini
        $pertemuan = getPertemuanInPeriode($koneksi, $id_jadwal, $periode_awal, $periode_akhir);
        $rekap     = hitungRekapPeriode($pertemuan);

        $jml_tm    = $rekap['jumlah_tm'];
        $total_sks = $rekap['total_sks'];

        // Cek apakah sudah ada transaksi untuk periode ini
        $pA = mysqli_real_escape_string($koneksi, $periode_awal);
        $pE = mysqli_real_escape_string($koneksi, $periode_akhir);
        $qExist = mysqli_query($koneksi, "
            SELECT id_thd, status FROM t_transaksi_honor_dosen
            WHERE id_jadwal = '$id_jadwal'
              AND periode_awal  = '$pA'
              AND periode_akhir = '$pE'
            LIMIT 1
        ");
        $existing = mysqli_fetch_assoc($qExist);

        // Terapkan aturan minimum pertemuan
        if ($jml_tm < $min_tm) {
            // Kurang dari minimum → tandai digabung
            $status = 'digabung';
            $catatan = "Pertemuan hanya $jml_tm kali (minimum $min_tm). Akan digabung ke periode berikutnya.";

            if ($existing) {
                // Update status existing
                $id_thd = (int)$existing['id_thd'];
                $catan_esc = mysqli_real_escape_string($koneksi, $catatan);
                mysqli_query($koneksi, "
                    UPDATE t_transaksi_honor_dosen
                    SET status='digabung', jml_tm='$jml_tm', sks_tempuh='$total_sks',
                        total_honor=0, catatan='$catan_esc', updated_at=NOW()
                    WHERE id_thd='$id_thd'
                ");
            } else {
                // Insert baru dengan status digabung
                $bulan_esc = mysqli_real_escape_string($koneksi, $bulan_label);
                $catan_esc = mysqli_real_escape_string($koneksi, $catatan);
                mysqli_query($koneksi, "
                    INSERT INTO t_transaksi_honor_dosen
                        (bulan, id_jadwal, jml_tm, sks_tempuh, periode_awal, periode_akhir, status, total_honor, catatan)
                    VALUES
                        ('$bulan_esc','$id_jadwal','$jml_tm','$total_sks','$pA','$pE','digabung',0,'$catan_esc')
                ");
                $id_thd = (int)mysqli_insert_id($koneksi);
            }

            return [
                'status'  => 'digabung',
                'message' => $catatan,
                'id_thd'  => $id_thd ?? null,
                'jml_tm'  => $jml_tm,
                'total_sks' => $total_sks,
            ];
        }

        // Cukup pertemuan → hitung honor
        $total_honor = $total_sks * $honor_persks;
        $bulan_esc   = mysqli_real_escape_string($koneksi, $bulan_label);

        if ($existing) {
            $id_thd = (int)$existing['id_thd'];
            // Hanya update jika belum dibayar
            if ($existing['status'] !== 'dibayar') {
                mysqli_query($koneksi, "
                    UPDATE t_transaksi_honor_dosen
                    SET status='pending', jml_tm='$jml_tm', sks_tempuh='$total_sks',
                        total_honor='$total_honor', catatan=NULL, updated_at=NOW()
                    WHERE id_thd='$id_thd'
                ");
            }
        } else {
            mysqli_query($koneksi, "
                INSERT INTO t_transaksi_honor_dosen
                    (bulan, id_jadwal, jml_tm, sks_tempuh, periode_awal, periode_akhir, status, total_honor)
                VALUES
                    ('$bulan_esc','$id_jadwal','$jml_tm','$total_sks','$pA','$pE','pending','$total_honor')
            ");
            $id_thd = (int)mysqli_insert_id($koneksi);
        }

        return [
            'status'    => 'pending',
            'message'   => "Honor berhasil dihitung: $jml_tm TM, $total_sks SKS, Rp " . number_format($total_honor, 0, ',', '.'),
            'id_thd'    => $id_thd ?? null,
            'jml_tm'    => $jml_tm,
            'total_sks' => $total_sks,
            'total_honor' => $total_honor,
        ];
    }

    /**
     * Memproses SEMUA jadwal untuk satu periode cut-off.
     * Digunakan pada halaman "Hitung Honor" batch.
     *
     * @param mysqli $koneksi
     * @param string $periode_awal
     * @param string $periode_akhir
     * @param string $bulan_label
     * @param int    $min_tm
     * @return array Ringkasan hasil proses
     */
    function prosesHonorSemuaJadwal($koneksi, $periode_awal, $periode_akhir, $bulan_label, $min_tm = 3)
    {
        $query_jadwal = mysqli_query($koneksi, "
            SELECT j.id_jdwl, j.kode_matkul, j.nama_matkul,
                   u.nama_user, COALESCE(u.honor_persks, 50000) as honor_persks
            FROM t_jadwal j
            LEFT JOIN t_user u ON j.id_user = u.id_user
        ");

        $hasil = [
            'diproses'     => 0,
            'pending'      => 0,
            'digabung'     => 0,
            'detail'       => [],
        ];

        while ($jadwal = mysqli_fetch_assoc($query_jadwal)) {
            $r = prosesHonorPeriode(
                $koneksi,
                $jadwal['id_jdwl'],
                $periode_awal,
                $periode_akhir,
                $bulan_label,
                $jadwal['honor_persks'],
                $min_tm
            );
            $hasil['diproses']++;
            if ($r['status'] === 'pending') $hasil['pending']++;
            else $hasil['digabung']++;
            $hasil['detail'][] = array_merge($jadwal, $r);
        }

        return $hasil;
    }

    /**
     * Format rupiah helper.
     */
    function formatRupiahHonor($number)
    {
        return 'Rp ' . number_format((int)$number, 0, ',', '.');
    }

    /**
     * Mendapatkan label status dengan badge class Bootstrap.
     */
    function getStatusBadge($status)
    {
        $map = [
            'pending'  => ['label' => 'Pending',  'class' => 'warning', 'icon' => 'clock'],
            'digabung' => ['label' => 'Digabung', 'class' => 'info',    'icon' => 'layer-group'],
            'dibayar'  => ['label' => 'Dibayar',  'class' => 'success', 'icon' => 'check-circle'],
        ];
        return $map[$status] ?? ['label' => ucfirst($status), 'class' => 'secondary', 'icon' => 'question'];
    }

    /**
     * Mendapatkan periode cut-off aktif berdasarkan tanggal hari ini.
     * Shortcut untuk tampilan dashboard.
     */
    function getPeriodeAktif($cutoff_tanggal = 25)
    {
        return getCutoffPeriode(null, $cutoff_tanggal);
    }

} // end if(!function_exists)
