-- =========================================
-- UPDATE AKUN GURU & PENGEPUL
-- Jalankan SATU KALI kalau database "banksampah_noviana" sudah terlanjur
-- diimport dengan akun lama (guru: sari, pengepul: budi).
--
-- Hasil:
--   guru     -> username: guru      | password: guru123
--   pengepul -> username: pengepul  | password: pengepul123
-- =========================================

USE banksampah_noviana;

UPDATE users
SET username = 'guru', password = MD5('guru123')
WHERE username = 'sari' AND role = 'guru';

UPDATE users
SET username = 'pengepul', password = MD5('pengepul123')
WHERE username = 'budi' AND role = 'pengepul';
