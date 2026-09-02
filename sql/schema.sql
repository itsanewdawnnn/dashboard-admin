-- =====================================================================
-- Tabel untuk kredensial login
-- =====================================================================

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role`           VARCHAR(50)  NOT NULL DEFAULT 'member',
  `username`       VARCHAR(50)  NOT NULL,
  `password`       VARCHAR(255) NOT NULL COMMENT 'Plain text (tanpa hash)',
  `full_name`      VARCHAR(100) NOT NULL,
  `last_login_at`  DATETIME     DEFAULT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`role`, `username`, `password`, `full_name`)
VALUES (
  'root',
  'root',
  'toor',
  'Muhammad Kahfi'
);

-- =====================================================================
-- Tabel untuk fitur sidebar "Menu 1 > Item 1"
-- =====================================================================

DROP TABLE IF EXISTS `menu1_item1`;

CREATE TABLE `menu1_item1` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`   VARCHAR(100) NOT NULL,
  `active_date` DATE         NOT NULL,
  `password`    VARCHAR(255) NOT NULL COMMENT 'Plain text (tanpa hash), sama seperti tabel users',
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `menu1_item1` (`full_name`, `active_date`, `password`) VALUES
('Graiden',   '2026-01-05', 'graiden123'),
('Dale',      '2026-01-12', 'dale123'),
('Nathaniel', '2026-01-18', 'nathaniel123'),
('Darius',    '2026-02-02', 'darius123'),
('Oleg',      '2026-02-09', 'oleg123'),
('Kermit',    '2026-02-15', 'kermit123'),
('Jermaine',  '2026-03-01', 'jermaine123'),
('Ferdinand', '2026-03-08', 'ferdinand123'),
('Kuame',     '2026-03-14', 'kuame123'),
('Deacon',    '2026-04-01', 'deacon123'),
('Channing',  '2026-04-10', 'channing123'),
('Aladdin',   '2026-04-20', 'aladdin123');

-- =====================================================================
-- Tabel untuk fitur sidebar "Menu 2 > Item 1"
-- =====================================================================

DROP TABLE IF EXISTS `menu2_item1_photos`;
DROP TABLE IF EXISTS `menu2_item1_signatures`;
DROP TABLE IF EXISTS `menu2_item1`;

CREATE TABLE `menu2_item1` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `work_date`        DATE NOT NULL COMMENT 'Tanggal pekerjaan',
  `job_description`  TEXT NOT NULL COMMENT 'Deskripsi pekerjaan',
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `menu2_item1_photos` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`     INT UNSIGNED NOT NULL,
  `file_name`   VARCHAR(255) NOT NULL COMMENT 'Nama file di folder uploads/menu2-item1/',
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menu2_item1_photos_item_id` (`item_id`),
  CONSTRAINT `fk_menu2_item1_photos_item` FOREIGN KEY (`item_id`) REFERENCES `menu2_item1` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `menu2_item1_signatures` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`     INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `file_name`   VARCHAR(255) NOT NULL COMMENT 'Nama file di folder uploads/signatures/',
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_menu2_item1_signatures_item_user` (`item_id`, `user_id`),
  KEY `idx_menu2_item1_signatures_item_id` (`item_id`),
  CONSTRAINT `fk_menu2_item1_signatures_item` FOREIGN KEY (`item_id`) REFERENCES `menu2_item1` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_menu2_item1_signatures_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `menu2_item1` (`work_date`, `job_description`) VALUES
('2026-08-24', 'Perbaikan jaringan listrik gedung A'),
('2026-08-25', 'Pengecekan rutin server ruang IT'),
('2026-08-26', 'Pemasangan CCTV area parkir'),
('2026-08-27', 'Perbaikan AC ruang meeting lantai 2'),
('2026-08-28', 'Pengecatan ulang pagar depan kantor');

-- =====================================================================
-- Tabel untuk fitur sidebar "menu3-item1"
-- =====================================================================

DROP TABLE IF EXISTS `menu3_item1`;

CREATE TABLE `menu3_item1` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `check_date`    DATE         NOT NULL COMMENT 'Tanggal checklist dilakukan',
  `checker_name`  VARCHAR(100) NOT NULL COMMENT 'Diambil otomatis dari akun yang login saat data ditambahkan',
  `unit`          VARCHAR(20)  NOT NULL COMMENT 'Key teknis, mis. unit1/unit2/unit3',
  `item1_check`   TINYINT(1)   NOT NULL DEFAULT 0,
  `item1_note`    VARCHAR(255) NOT NULL DEFAULT '',
  `item2_check`   TINYINT(1)   NOT NULL DEFAULT 0,
  `item2_note`    VARCHAR(255) NOT NULL DEFAULT '',
  `item3_check`   TINYINT(1)   NOT NULL DEFAULT 0,
  `item3_note`    VARCHAR(255) NOT NULL DEFAULT '',
  `item4_check`   TINYINT(1)   NOT NULL DEFAULT 0,
  `item4_note`    VARCHAR(255) NOT NULL DEFAULT '',
  `item5_check`   TINYINT(1)   NOT NULL DEFAULT 0,
  `item5_note`    VARCHAR(255) NOT NULL DEFAULT '',
  `item6_check`   TINYINT(1)   NOT NULL DEFAULT 0,
  `item6_note`    VARCHAR(255) NOT NULL DEFAULT '',
  `item7_check`   TINYINT(1)   NOT NULL DEFAULT 0,
  `item7_note`    VARCHAR(255) NOT NULL DEFAULT '',
  `item8_check`   TINYINT(1)   NOT NULL DEFAULT 0,
  `item8_note`    VARCHAR(255) NOT NULL DEFAULT '',
  `item9_check`   TINYINT(1)   NOT NULL DEFAULT 0,
  `item9_note`    VARCHAR(255) NOT NULL DEFAULT '',
  `item10_check`  TINYINT(1)   NOT NULL DEFAULT 0,
  `item10_note`   VARCHAR(255) NOT NULL DEFAULT '',
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `menu3_item1`
  ADD COLUMN `laporan` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Catatan laporan bebas, opsional' AFTER `unit`;

ALTER TABLE `menu3_item1`
  ADD COLUMN `tindak_lanjut` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Tindak lanjut yang dilakukan, hanya diisi lewat Edit -- ini yang menentukan status "belum ditindaklanjuti", bukan Laporan' AFTER `laporan`;

-- =====================================================================
-- Tabel untuk Log Aktivitas (audit trail)
-- =====================================================================

DROP TABLE IF EXISTS `activity_log`;

CREATE TABLE `activity_log` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED DEFAULT NULL COMMENT 'NULL jika akun user sudah dihapus -- username/full_name di bawah tetap tersimpan sebagai catatan',
  `username`     VARCHAR(50)  NOT NULL COMMENT 'Snapshot username saat aksi terjadi',
  `full_name`    VARCHAR(100) NOT NULL COMMENT 'Snapshot nama lengkap saat aksi terjadi',
  `action_type`  VARCHAR(20)  NOT NULL COMMENT 'login, logout, create, update, atau delete',
  `module`       VARCHAR(50)  NOT NULL COMMENT 'Modul/tabel terkait, mis. auth, users, menu1_item1',
  `record_id`    INT UNSIGNED DEFAULT NULL COMMENT 'ID baris yang terpengaruh, NULL untuk login/logout',
  `description`  VARCHAR(255) NOT NULL,
  `old_values`   TEXT DEFAULT NULL COMMENT 'JSON kolom+nilai sebelum perubahan (khusus update/delete)',
  `new_values`   TEXT DEFAULT NULL COMMENT 'JSON kolom+nilai sesudah perubahan (khusus create/update)',
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_log_created_at` (`created_at`),
  KEY `idx_activity_log_user_id` (`user_id`),
  CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

