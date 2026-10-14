-- =========================================
-- ALTER TOP UP PENGEPUL
-- Jalankan file ini SATU KALI kalau database "banksampah" kamu
-- sudah lebih dulu dibuat SEBELUM fitur "Top Up Pengepul" ditambahkan.
--
-- Kalau kamu baru pertama kali import banksampah_noviana.sql (versi terbaru),
-- file ini TIDAK PERLU dijalankan karena tabelnya sudah otomatis dibuat.
-- =========================================

USE banksampah;

CREATE TABLE IF NOT EXISTS topup_pengepul (
  id_topup INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  nominal INT NOT NULL,
  tanggal DATETIME NOT NULL,
  FOREIGN KEY (id_user) REFERENCES users(id_user)
);
