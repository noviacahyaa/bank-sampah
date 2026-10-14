-- =========================================
-- ALTER ADMIN
-- Jalankan file ini SATU KALI kalau database "banksampah" kamu
-- sudah lebih dulu dibuat dari versi bank_sampah.sql yang lama
-- (sebelum fitur dashboard admin ditambahkan).
--
-- Kalau kamu baru pertama kali import bank_sampah.sql (versi terbaru),
-- file ini TIDAK PERLU dijalankan karena kolomnya sudah otomatis benar.
-- =========================================

USE banksampah;

ALTER TABLE tarik_saldo
    MODIFY status ENUM('pending','selesai','ditolak') DEFAULT 'pending';

-- Pastikan minimal ada satu akun admin untuk mencoba dashboard admin
-- (lewati baris ini kalau akun admin sudah ada).
INSERT INTO users (nama, username, password, role, saldo)
SELECT 'Admin Bank Sampah', 'admin', MD5('admin123'), 'admin', 0
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');
