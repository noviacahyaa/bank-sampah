-- =========================================
-- DATABASE: banksampah
-- Sesuai ERD: users, sampah, transaksi, tarik_saldo
-- =========================================

CREATE DATABASE IF NOT EXISTS banksampah;
USE banksampah;

-- Tabel users
CREATE TABLE users (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','siswa','guru','pengepul') DEFAULT 'siswa',
  saldo INT DEFAULT 0
);

-- Tabel sampah
CREATE TABLE sampah (
  id_sampah INT AUTO_INCREMENT PRIMARY KEY,
  jenis_sampah VARCHAR(50) NOT NULL,
  harga_per_kg INT NOT NULL,
  stok_kg FLOAT DEFAULT 0
);

-- Tabel transaksi (1 sampah bisa banyak transaksi -> relasi 1..N di ERD)
CREATE TABLE transaksi (
  id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  id_sampah INT NOT NULL,
  tipe ENUM('setor','jual_pengepul','') DEFAULT 'setor',
  berat_kg FLOAT NOT NULL,
  total_rp INT NOT NULL,
  tanggal DATETIME NOT NULL,
  FOREIGN KEY (id_user) REFERENCES users(id_user),
  FOREIGN KEY (id_sampah) REFERENCES sampah(id_sampah)
);

-- Tabel tarik_saldo
CREATE TABLE tarik_saldo (
  id_tarik INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  nominal INT NOT NULL,
  status ENUM('pending','selesai','ditolak') DEFAULT 'pending',
  tanggal DATETIME NOT NULL,
  FOREIGN KEY (id_user) REFERENCES users(id_user)
);

-- Tabel topup_pengepul (riwayat top up saldo pengepul oleh admin)
CREATE TABLE topup_pengepul (
  id_topup INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  nominal INT NOT NULL,
  tanggal DATETIME NOT NULL,
  FOREIGN KEY (id_user) REFERENCES users(id_user)
);

-- =========================================
-- DATA CONTOH
-- password admin  : admin123
-- password siswa  : novia123
-- username guru    : guru     | password : guru123
-- username pengepul : pengepul | password : pengepul123
-- =========================================
INSERT INTO users (nama, username, password, role, saldo) VALUES
('Admin Bank Sampah', 'admin', MD5('admin123'), 'admin', 0),
('Noviana', 'novia', MD5('novia123'), 'siswa', 45000),
('Bu Sari', 'guru', MD5('guru123'), 'guru', 20000),
('Pak Budi', 'pengepul', MD5('pengepul123'), 'pengepul', 500000);

INSERT INTO sampah (jenis_sampah, harga_per_kg, stok_kg) VALUES
('Plastik', 4500, 100),
('Kardus', 1667, 50),
('Kertas', 2000, 30);

INSERT INTO transaksi (id_user, id_sampah, tipe, berat_kg, total_rp, tanggal) VALUES
(2, 2, 'setor', 6, 10000, '2026-08-28 09:00:00'),
(2, 1, 'setor', 2, 9000, '2026-09-01 10:30:00');

INSERT INTO topup_pengepul (id_user, nominal, tanggal) VALUES
(4, 500000, '2026-09-01 08:00:00');
