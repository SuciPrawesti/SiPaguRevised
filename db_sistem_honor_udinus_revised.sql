-- ============================================================
-- DATABASE: db_sistem_honor_udinus v2.0 (Full Data)
-- Setiap tabel minimal 10 data, tidak ada null wajib
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS `db_sistem_honor_udinus`;
CREATE DATABASE `db_sistem_honor_udinus` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_sistem_honor_udinus`;

-- ============================================================
-- t_user (13 data)
-- ============================================================
CREATE TABLE `t_user` (
  `id_user`       int(11) NOT NULL AUTO_INCREMENT,
  `npp_user`      varchar(20)  NOT NULL,
  `nik_user`      char(16)     NOT NULL,
  `npwp_user`     varchar(20)  NOT NULL,
  `norek_user`    varchar(30)  NOT NULL,
  `nama_user`     varchar(100) NOT NULL,
  `nohp_user`     varchar(20)  NOT NULL,
  `pw_user`       varchar(255) NOT NULL,
  `role_user`     enum('koordinator','admin','staff') NOT NULL,
  `honor_persks`  int(11) DEFAULT 50000,
  `remember_token` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_user` (`id_user`,`npp_user`,`nik_user`,`npwp_user`,`norek_user`,`nama_user`,`nohp_user`,`pw_user`,`role_user`,`honor_persks`) VALUES
(1,  '0686.11.1995.071','3374010101950001','12.345.678.9-012.000','1410001234567','Dr. Andi Prasetyo, M.Kom',   '081234560001','$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry','admin',       0),
(2,  '0721.12.1998.034','3374010202980002','23.456.789.0-123.000','1410002345678','Siti Rahmawati, M.T',        '081234560002','$2y$10$/BkQAaJ1bw9lkxcDOzDd9eSa6AClBvvyR35Gs0gLNEuilpuazcSZO','staff',       75000),
(3,  '0815.10.2001.112','3374010303010003','34.567.890.1-234.000','1410003456789','Budi Santoso, S.Kom',        '081234560003','$2y$10$ptdJFN18H5.rbrgNfL8d.uD5bZPLgimtnceE3J/H6O3lHAdP3thr6','staff',       60000),
(99, '0686.11.1995.000','1111111111111111','12.335.678.9-012.134','1410009900001','Azkiya, S.Kom',              '081234560099','$2y$10$8Fz.xsh5Jtv.ApBGdTm7YeeX1hF401W9mZXH49Ir6kymdelIxzxuC','koordinator', 0),
(101,'1101.01.1985.011','3271012101850001','45.678.901.2-345.000','1410004567890','Prof. Dr. Hendra Wijaya',    '081234560101','$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry','staff',       100000),
(102,'1102.02.1988.022','3578022202880002','56.789.012.3-456.000','1410005678901','Dr. Dewi Kusumawati, M.Sc', '081234560102','$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry','staff',       90000),
(103,'1103.03.1990.033','3374033003900003','67.890.123.4-567.000','1410006789012','Ahmad Fauzi, M.Pd',         '081234560103','$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry','staff',       70000),
(104,'1104.04.1992.044','3171044404920004','78.901.234.5-678.000','1410007890123','Linda Permata, M.Ak',       '081234560104','$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry','staff',       80000),
(105,'1105.05.1987.055','3374055505870005','89.012.345.6-789.000','1410008901234','Dr. Rizky Pratama, M.T',   '081234560105','$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry','staff',       95000),
(106,'1106.06.1983.066','3578066606830006','90.123.456.7-890.000','1410009012345','Sari Indah, M.Kom',        '081234560106','$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry','staff',       65000),
(107,'1107.07.1991.077','3373077707910007','01.234.567.8-901.000','1410000123456','Bambang Setiawan, M.Cs',   '081234560107','$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry','staff',       72000),
(108,'1108.08.1986.088','3374088808860008','12.345.670.9-012.000','1410001234560','Nur Aini, M.Hum',           '081234560108','$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry','staff',       55000),
(109,'1109.09.1989.099','3374099909890009','23.456.781.0-123.000','1410002345601','Fajar Hidayat, M.T',        '081234560109','$2y$10$RDLbekBlwDetSCFcQPruf.nJr2Z6rLDxw3kJf/cVg.ywOynlYm.ry','staff',       68000);

ALTER TABLE `t_user` MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

-- ============================================================
-- t_jadwal (12 data)
-- ============================================================
CREATE TABLE `t_jadwal` (
  `id_jdwl`     int(11)     NOT NULL AUTO_INCREMENT,
  `semester`    varchar(5)  NOT NULL,
  `kode_matkul` varchar(7)  NOT NULL,
  `nama_matkul` varchar(30) NOT NULL,
  `id_user`     int(11)     NOT NULL,
  `jml_mhs`     int(11)     NOT NULL,
  PRIMARY KEY (`id_jdwl`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `t_jadwal_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `t_user`(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_jadwal` (`id_jdwl`,`semester`,`kode_matkul`,`nama_matkul`,`id_user`,`jml_mhs`) VALUES
(1,  '20261','SI101','Algoritma Pemrograman',     101, 35),
(2,  '20261','SI102','Struktur Data',             102, 28),
(3,  '20261','SI201','Basis Data',                103, 40),
(4,  '20261','SI202','Pemrograman Web',           104, 32),
(5,  '20261','SI245','Kecerdasan Buatan',         105, 25),
(6,  '20262','SI301','Sistem Informasi',          101, 38),
(7,  '20262','SI302','Rekayasa Perangkat Lunak',  102, 30),
(8,  '20262','SI401','Keamanan Jaringan',         105, 22),
(9,  '20262','SI402','Data Mining',               106, 27),
(10, '20262','SI403','Cloud Computing',           107, 20),
(11, '20271','SI501','Proyek Akhir I',            103, 15),
(12, '20271','SI502','Proyek Akhir II',           104, 12);

ALTER TABLE `t_jadwal` MODIFY `id_jdwl` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

-- ============================================================
-- t_panitia (12 data)
-- ============================================================
CREATE TABLE `t_panitia` (
  `id_pnt`    int(11)      NOT NULL AUTO_INCREMENT,
  `jbtn_pnt`  varchar(100) NOT NULL,
  `honor_std` int(11)      NOT NULL,
  `honor_p1`  int(11)      DEFAULT 0,
  `honor_p2`  int(11)      DEFAULT 0,
  PRIMARY KEY (`id_pnt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_panitia` (`id_pnt`,`jbtn_pnt`,`honor_std`,`honor_p1`,`honor_p2`) VALUES
(1,  'Ketua Panitia',              25000, 5000, 3000),
(2,  'Wakil Ketua Panitia',        20000, 4000, 2500),
(3,  'Sekretaris',                 18000, 3500, 2000),
(4,  'Bendahara',                  18000, 3500, 2000),
(5,  'Anggota Panitia',            12000, 2000, 1500),
(6,  'Koordinator Ujian',          22000, 4500, 3000),
(7,  'Pengawas Ujian',             15000, 3000, 2000),
(8,  'Koreksi Jawaban',            10000, 2000, 1000),
(9,  'Ketua Sidang PA/TA',         30000, 6000, 4000),
(10, 'Penguji Utama PA/TA',        25000, 5000, 3500),
(11, 'Penguji Pendamping PA/TA',   20000, 4000, 2500),
(12, 'Pembimbing Utama PA/TA',     35000, 7000, 5000);

ALTER TABLE `t_panitia` MODIFY `id_pnt` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

-- ============================================================
-- t_pertemuan_dosen (27 data)
-- ============================================================
CREATE TABLE `t_pertemuan_dosen` (
  `id_pertemuan` int(11)      NOT NULL AUTO_INCREMENT,
  `id_jadwal`    int(11)      NOT NULL,
  `tanggal`      date         NOT NULL,
  `sks`          int(11)      NOT NULL DEFAULT 1,
  `keterangan`   varchar(255) DEFAULT NULL,
  `created_at`   datetime     DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pertemuan`),
  KEY `idx_id_jadwal` (`id_jadwal`),
  KEY `idx_tanggal`   (`tanggal`),
  CONSTRAINT `fk_pertemuan_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `t_jadwal`(`id_jdwl`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_pertemuan_dosen` (`id_jadwal`,`tanggal`,`sks`,`keterangan`) VALUES
-- Jadwal 1 (Algoritma) - Maret 2026
(1,'2026-02-27',3,'Pertemuan 1 - Pengenalan Algoritma'),
(1,'2026-03-06',3,'Pertemuan 2 - Variabel dan Tipe Data'),
(1,'2026-03-13',3,'Pertemuan 3 - Struktur Kontrol'),
(1,'2026-03-20',3,'Pertemuan 4 - Fungsi dan Prosedur'),
-- Jadwal 2 (Struktur Data) - Maret 2026
(2,'2026-03-01',3,'Pertemuan 1 - Array dan Linked List'),
(2,'2026-03-08',3,'Pertemuan 2 - Stack dan Queue'),
(2,'2026-03-15',3,'Pertemuan 3 - Tree dan Graph'),
-- Jadwal 3 (Basis Data) - Maret 2026
(3,'2026-02-28',3,'Pertemuan 1 - ERD'),
(3,'2026-03-07',3,'Pertemuan 2 - Normalisasi'),
(3,'2026-03-14',3,'Pertemuan 3 - SQL Dasar'),
(3,'2026-03-21',3,'Pertemuan 4 - SQL Lanjutan'),
(3,'2026-03-25',3,'Pertemuan 5 - Stored Procedure'),
-- Jadwal 4 (Pemrograman Web) - Maret 2026
(4,'2026-03-02',3,'Pertemuan 1 - HTML & CSS'),
(4,'2026-03-09',3,'Pertemuan 2 - JavaScript'),
(4,'2026-03-16',3,'Pertemuan 3 - PHP Dasar'),
(4,'2026-03-23',3,'Pertemuan 4 - Laravel'),
-- Jadwal 5 (KI) - hanya 2 pertemuan, akan digabung
(5,'2026-03-10',3,'Pertemuan 1 - Pengenalan AI'),
(5,'2026-03-17',3,'Pertemuan 2 - Machine Learning'),
-- Jadwal 6 (SI) - Februari 2026
(6,'2026-01-27',3,'Pertemuan 1 - Konsep SI'),
(6,'2026-02-03',3,'Pertemuan 2 - Analisis Sistem'),
(6,'2026-02-10',3,'Pertemuan 3 - Desain Sistem'),
(6,'2026-02-17',3,'Pertemuan 4 - Implementasi'),
-- Jadwal 7 (RPL) - Februari 2026
(7,'2026-01-28',3,'Pertemuan 1 - SDLC'),
(7,'2026-02-04',3,'Pertemuan 2 - Requirement Analysis'),
(7,'2026-02-11',3,'Pertemuan 3 - Software Design'),
(7,'2026-02-18',3,'Pertemuan 4 - Testing'),
(7,'2026-02-24',3,'Pertemuan 5 - Deployment');

ALTER TABLE `t_pertemuan_dosen` MODIFY `id_pertemuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

-- ============================================================
-- t_transaksi_honor_dosen (12 data)
-- ============================================================
CREATE TABLE `t_transaksi_honor_dosen` (
  `id_thd`        int(11) NOT NULL AUTO_INCREMENT,
  `bulan`         enum('januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember') NOT NULL,
  `semester`      varchar(5)  DEFAULT NULL,
  `id_jadwal`     int(11)     NOT NULL,
  `jml_tm`        int(11)     NOT NULL DEFAULT 0,
  `sks_tempuh`    int(11)     NOT NULL DEFAULT 0,
  `periode_awal`  date        NOT NULL,
  `periode_akhir` date        NOT NULL,
  `status`        enum('pending','digabung','dibayar') NOT NULL DEFAULT 'pending',
  `total_honor`   bigint(20)  NOT NULL DEFAULT 0,
  `catatan`       text        DEFAULT NULL,
  `created_at`    datetime    DEFAULT current_timestamp(),
  `updated_at`    datetime    DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_thd`),
  KEY `idx_id_jadwal` (`id_jadwal`),
  KEY `idx_bulan`     (`bulan`),
  KEY `idx_status`    (`status`),
  CONSTRAINT `fk_thd_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `t_jadwal`(`id_jdwl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_transaksi_honor_dosen` (`id_thd`,`bulan`,`semester`,`id_jadwal`,`jml_tm`,`sks_tempuh`,`periode_awal`,`periode_akhir`,`status`,`total_honor`,`catatan`) VALUES
(1,  'maret',    '20261', 1,  4, 12, '2026-02-26','2026-03-25','pending',   1200000, NULL),
(2,  'maret',    '20261', 2,  3,  9, '2026-02-26','2026-03-25','pending',    810000, NULL),
(3,  'maret',    '20261', 3,  5, 15, '2026-02-26','2026-03-25','pending',   1050000, NULL),
(4,  'maret',    '20261', 4,  4, 12, '2026-02-26','2026-03-25','pending',    960000, NULL),
(5,  'maret',    '20261', 5,  2,  6, '2026-02-26','2026-03-25','digabung',        0, 'Pertemuan hanya 2 kali (minimum 3). Akan digabung ke periode berikutnya.'),
(6,  'februari', '20262', 6,  4, 12, '2026-01-26','2026-02-25','dibayar',  1200000, NULL),
(7,  'februari', '20262', 7,  5, 15, '2026-01-26','2026-02-25','dibayar',  1350000, NULL),
(8,  'januari',  '20262', 6,  3,  9, '2025-12-26','2026-01-25','dibayar',   900000, NULL),
(9,  'januari',  '20262', 7,  4, 12, '2025-12-26','2026-01-25','dibayar',  1080000, NULL),
(10, 'januari',  '20261', 1,  3,  9, '2025-12-26','2026-01-25','dibayar',   900000, NULL),
(11, 'desember', '20261', 2,  4, 12, '2025-11-26','2025-12-25','dibayar',  1080000, NULL),
(12, 'desember', '20261', 3,  5, 15, '2025-11-26','2025-12-25','dibayar',  1050000, NULL);

ALTER TABLE `t_transaksi_honor_dosen` MODIFY `id_thd` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

-- ============================================================
-- t_transaksi_pa_ta (12 data)
-- ============================================================
CREATE TABLE `t_transaksi_pa_ta` (
  `id_tpt`             int(11)     NOT NULL AUTO_INCREMENT,
  `semester`           varchar(5)  NOT NULL,
  `periode_wisuda`     enum('januari','februari','maret','april','mei','juni','juli','agustus','september','oktober','november','desember') NOT NULL,
  `id_user`            int(11)     NOT NULL,
  `id_panitia`         int(11)     NOT NULL,
  `jml_mhs_prodi`      int(11)     NOT NULL,
  `jml_mhs_bimbingan`  int(11)     NOT NULL,
  `prodi`              varchar(100) NOT NULL,
  `jml_pgji_1`         int(11)     NOT NULL,
  `jml_pgji_2`         int(11)     DEFAULT 0,
  `ketua_pgji`         varchar(30) NOT NULL,
  PRIMARY KEY (`id_tpt`),
  KEY `id_user`    (`id_user`),
  KEY `id_panitia` (`id_panitia`),
  CONSTRAINT `t_transaksi_pa_ta_ibfk_2` FOREIGN KEY (`id_user`)    REFERENCES `t_user`   (`id_user`),
  CONSTRAINT `t_transaksi_pa_ta_ibfk_3` FOREIGN KEY (`id_panitia`) REFERENCES `t_panitia`(`id_pnt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_transaksi_pa_ta` (`id_tpt`,`semester`,`periode_wisuda`,`id_user`,`id_panitia`,`jml_mhs_prodi`,`jml_mhs_bimbingan`,`prodi`,`jml_pgji_1`,`jml_pgji_2`,`ketua_pgji`) VALUES
(1,  '20261','maret',      101,  9, 40, 3, 'Sistem Informasi',         4, 2, 'Dr. Andi Prasetyo'),
(2,  '20261','maret',      102, 10, 40, 2, 'Sistem Informasi',         4, 2, 'Dr. Andi Prasetyo'),
(3,  '20261','maret',      103, 12, 40, 4, 'Sistem Informasi',         4, 2, 'Dr. Andi Prasetyo'),
(4,  '20262','agustus',    104,  9, 35, 2, 'Teknik Informatika',       3, 2, 'Prof. Hendra Wijaya'),
(5,  '20262','agustus',    105, 10, 35, 3, 'Teknik Informatika',       3, 2, 'Prof. Hendra Wijaya'),
(6,  '20261','maret',      106, 11, 30, 2, 'Sistem Komputer',          3, 1, 'Dr. Dewi Kusumawati'),
(7,  '20261','juli',       107,  9, 28, 2, 'Manajemen Informatika',    3, 2, 'Ahmad Fauzi'),
(8,  '20262','agustus',    108, 12, 25, 2, 'Desain Komunikasi Visual', 2, 1, 'Linda Permata'),
(9,  '20261','maret',        2,  9, 20, 1, 'Sistem Informasi',         2, 1, 'Dr. Andi Prasetyo'),
(10, '20262','agustus',      3, 10, 18, 1, 'Teknik Informatika',       2, 1, 'Prof. Hendra Wijaya'),
(11, '20271','maret',      101,  9, 42, 4, 'Sistem Informasi',         4, 3, 'Dr. Rizky Pratama'),
(12, '20271','juli',       102, 10, 42, 3, 'Sistem Informasi',         4, 3, 'Dr. Rizky Pratama');

ALTER TABLE `t_transaksi_pa_ta` MODIFY `id_tpt` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

-- ============================================================
-- t_transaksi_ujian (12 data)
-- ============================================================
CREATE TABLE `t_transaksi_ujian` (
  `id_tu`          int(11)     NOT NULL AUTO_INCREMENT,
  `semester`       varchar(10) NOT NULL,
  `id_panitia`     int(11)     NOT NULL,
  `id_user`        int(11)     NOT NULL,
  `jml_mhs_prodi`  int(11)     NOT NULL,
  `jml_mhs`        int(11)     NOT NULL,
  `jml_koreksi`    int(11)     NOT NULL,
  `jml_matkul`     int(11)     NOT NULL,
  `jml_pgws_pagi`  int(11)     NOT NULL,
  `jml_pgws_sore`  int(11)     NOT NULL,
  `jml_koor_pagi`  int(11)     NOT NULL,
  `jml_koor_sore`  int(11)     NOT NULL,
  PRIMARY KEY (`id_tu`),
  KEY `id_user`    (`id_user`),
  KEY `id_panitia` (`id_panitia`),
  CONSTRAINT `t_transaksi_ujian_ibfk_3` FOREIGN KEY (`id_user`)    REFERENCES `t_user`   (`id_user`),
  CONSTRAINT `t_transaksi_ujian_ibfk_4` FOREIGN KEY (`id_panitia`) REFERENCES `t_panitia`(`id_pnt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_transaksi_ujian` (`id_tu`,`semester`,`id_panitia`,`id_user`,`jml_mhs_prodi`,`jml_mhs`,`jml_koreksi`,`jml_matkul`,`jml_pgws_pagi`,`jml_pgws_sore`,`jml_koor_pagi`,`jml_koor_sore`) VALUES
(1,  '20261', 6, 101, 120, 35, 35, 8, 4, 4, 2, 2),
(2,  '20261', 7, 102, 120, 28, 28, 6, 3, 3, 1, 1),
(3,  '20261', 8, 103, 120, 40, 40, 8, 4, 4, 2, 2),
(4,  '20261', 7, 104, 120, 32, 32, 7, 3, 4, 2, 1),
(5,  '20261', 8, 105, 120, 25, 25, 5, 3, 3, 1, 1),
(6,  '20262', 6, 101, 115, 38, 38, 8, 4, 4, 2, 2),
(7,  '20262', 7, 102, 115, 30, 30, 6, 3, 3, 1, 1),
(8,  '20262', 8, 106, 115, 27, 27, 6, 3, 3, 1, 1),
(9,  '20262', 7, 107, 115, 20, 20, 5, 2, 3, 1, 1),
(10, '20271', 6, 101, 110, 15, 15, 3, 2, 2, 1, 1),
(11, '20271', 7, 103, 110, 40, 40, 8, 4, 4, 2, 2),
(12, '20271', 8, 104, 110, 32, 32, 7, 3, 4, 1, 1);

ALTER TABLE `t_transaksi_ujian` MODIFY `id_tu` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

-- ============================================================
-- t_approval_status (12 data)
-- ============================================================
CREATE TABLE `t_approval_status` (
  `id_approval`    int(11)     NOT NULL AUTO_INCREMENT,
  `table_name`     varchar(50) NOT NULL,
  `record_id`      int(11)     NOT NULL,
  `status`         enum('draft','diverifikasi','disetujui','dicairkan','ditolak') DEFAULT 'draft',
  `approval_notes` text        DEFAULT NULL,
  `approved_by`    int(11)     DEFAULT NULL,
  `approved_at`    datetime    DEFAULT NULL,
  `created_at`     datetime    DEFAULT current_timestamp(),
  `updated_at`     datetime    DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_approval`),
  UNIQUE KEY `unique_record` (`table_name`,`record_id`),
  KEY `idx_status`      (`status`),
  KEY `idx_approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `t_approval_status` (`id_approval`,`table_name`,`record_id`,`status`,`approval_notes`,`approved_by`,`approved_at`,`created_at`) VALUES
(1,  'transaksi_honor_dosen', 6,  'dicairkan',   'Dicairkan ke rekening dosen Februari 2026',    99,'2026-02-28 10:00:00','2026-02-26 08:00:00'),
(2,  'transaksi_honor_dosen', 7,  'dicairkan',   'Dicairkan ke rekening dosen Februari 2026',    99,'2026-02-28 10:00:00','2026-02-26 08:00:00'),
(3,  'transaksi_honor_dosen', 8,  'dicairkan',   'Dicairkan ke rekening dosen Januari 2026',     99,'2026-01-30 10:00:00','2026-01-28 08:00:00'),
(4,  'transaksi_honor_dosen', 9,  'dicairkan',   'Dicairkan ke rekening dosen Januari 2026',     99,'2026-01-30 10:00:00','2026-01-28 08:00:00'),
(5,  'transaksi_honor_dosen', 10, 'dicairkan',   'Dicairkan ke rekening dosen Januari 2026',     99,'2026-01-30 10:00:00','2026-01-28 08:00:00'),
(6,  'transaksi_honor_dosen', 1,  'diverifikasi','Verifikasi awal periode Maret 2026',            1, '2026-03-26 09:00:00','2026-03-26 08:00:00'),
(7,  'transaksi_honor_dosen', 2,  'diverifikasi','Verifikasi awal periode Maret 2026',            1, '2026-03-26 09:00:00','2026-03-26 08:00:00'),
(8,  'transaksi_honor_dosen', 3,  'diverifikasi','Verifikasi awal periode Maret 2026',            1, '2026-03-26 09:00:00','2026-03-26 08:00:00'),
(9,  'transaksi_ujian',       1,  'disetujui',   'Disetujui untuk dicairkan semester 20261',     99,'2026-01-20 14:00:00','2026-01-18 08:00:00'),
(10, 'transaksi_ujian',       2,  'disetujui',   'Disetujui untuk dicairkan semester 20261',     99,'2026-01-20 14:00:00','2026-01-18 08:00:00'),
(11, 'transaksi_pa_ta',       1,  'dicairkan',   'Dicairkan setelah wisuda Maret 2026',          99,'2026-03-15 10:00:00','2026-03-10 08:00:00'),
(12, 'transaksi_pa_ta',       2,  'dicairkan',   'Dicairkan setelah wisuda Maret 2026',          99,'2026-03-15 10:00:00','2026-03-10 08:00:00');

ALTER TABLE `t_approval_status` MODIFY `id_approval` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
