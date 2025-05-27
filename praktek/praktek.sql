-- Buat database (jika belum ada)
CREATE DATABASE IF NOT EXISTS `contoh buku` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Pilih database yang dibuat
USE `contoh buku`;

-- Buat tabel buku
CREATE TABLE IF NOT EXISTS `buku` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `judul` VARCHAR(255) NOT NULL,
  `penulis` VARCHAR(255) NOT NULL,
  `tahun_terbit` INT NOT NULL,
  `stok` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
