CARA MENJALANKAN
=================
1. Copy folder "banksampah_novianaa" ke dalam htdocs (XAMPP) atau www (Laragon).
2. Buka phpMyAdmin, lalu import file banksampah_noviana.sql (otomatis buat database + tabel + data contoh).
   - Kalau sebelumnya kamu SUDAH pernah import versi lama file ini (sebelum ada dashboard admin),
     jalankan juga alter_admin.sql supaya fitur tolak pengajuan tarik saldo berfungsi.
   - Kalau sebelumnya kamu SUDAH pernah import versi lama (sebelum ada fitur Top Up Pengepul),
     jalankan juga alter_topup.sql supaya tabel topup_pengepul tersedia.
   - Kalau database sudah terlanjur diimport dengan akun lama (sari / budi), jalankan update_akun.sql
     supaya username & password guru dan pengepul berubah jadi yang baru.
3. Sesuaikan koneksi.php jika username/password MySQL kamu berbeda dari default XAMPP (root, tanpa password).
4. Buka browser: http://localhost/banksampah_novianaa/index.php

AKUN CONTOH (dari data di bank_sampah.sql)
-------------------------------------------
Siswa:
  username: novia
  password: novia123

Guru:
  username: guru
  password: guru123

Pengepul:
  username: pengepul
  password: pengepul123

Admin:
  username: admin
  password: admin123

STRUKTUR FILE
-------------
banksampah_noviana.sql       -> skema + data database
alter_admin.sql              -> patch untuk database lama (tambah status "ditolak")
alter_topup.sql              -> patch untuk database lama (tambah tabel topup_pengepul)
koneksi.php                  -> koneksi ke database
index.php                    -> halaman login
proses_login.php             -> proses cek login, redirect sesuai role

# Setor sampah (semua role: siswa, guru, pengepul, admin)
setor_sampah.php              -> form setor sampah untuk akun sendiri (tampilan menyesuaikan role)
proses_setor.php              -> proses simpan setoran: saldo akun & stok sampah bertambah

# Siswa / Guru
dashboard.php                -> dashboard siswa/guru (saldo + riwayat setoran terakhir)
tarik_saldo.php               -> form ajukan tarik saldo (siswa/guru)
proses_tarik.php              -> proses simpan pengajuan tarik saldo
riwayat.php                   -> riwayat lengkap setoran & tarik saldo (siswa/guru)

# Pengepul
dashboard_pengepul.php        -> dashboard pengepul (saldo, stok sampah + catat pembelian)
proses_beli.php                -> proses simpan transaksi pembelian pengepul (memotong saldo pengepul)
riwayat_pengepul.php          -> riwayat lengkap pembelian & top up saldo pengepul

# Admin
dashboard_admin.php           -> ringkasan: jumlah akun per role, saldo beredar, stok, transaksi & PENJUALAN hari ini/bulan ini
setor_admin.php               -> catat setoran nasabah (siswa/guru) -> menambah saldo & stok sampah
proses_setor_admin.php        -> proses simpan setoran yang dicatat admin
topup_pengepul.php            -> isi/top up saldo pengepul supaya mereka bisa membeli stok sampah
proses_topup_pengepul.php     -> proses simpan top up saldo pengepul
kelola_sampah.php             -> tambah/ubah/hapus jenis sampah, harga per kg, dan stok
proses_sampah.php             -> proses CRUD data sampah
verifikasi_tarik.php          -> setujui/tolak pengajuan tarik saldo dari siswa/guru
proses_verifikasi_tarik.php   -> proses setuju/tolak pengajuan tarik saldo
kelola_user.php               -> tambah/ubah/hapus akun siswa, guru, pengepul, admin
proses_user.php               -> proses CRUD akun pengguna
riwayat_admin.php             -> rekap semua transaksi (setoran & pembelian pengepul)

logout.php                    -> keluar / hapus session

ALUR SINGKAT
------------
1. Admin mencatat setoran sampah siswa/guru lewat "Catat Setoran" -> saldo nasabah & stok sampah otomatis bertambah.
2. Semua role (siswa, guru, pengepul, admin) bisa setor sampah sendiri lewat menu "Setor Sampah" -> saldo akun
   & stok sampah langsung bertambah. Siswa/guru juga bisa mengajukan tarik saldo lewat dashboard mereka.
3. Admin menyetujui/menolak pengajuan itu lewat "Verifikasi Tarik Saldo" -> kalau disetujui, saldo nasabah otomatis berkurang.
4. Admin meng-"Top Up Pengepul" (mencatat setoran tunai/transfer dari pengepul ke bank sampah) -> saldo pengepul bertambah.
5. Pengepul membeli stok sampah dari bank sampah lewat dashboard mereka -> stok sampah berkurang, DAN saldo
   pengepul otomatis terpotong sebesar harga pembelian (kalau saldo tidak cukup, pembelian ditolak).
6. Admin bisa memantau statistik penjualan ke pengepul (hari ini & bulan ini) di dashboard admin, plus total
   saldo pengepul yang masih tersedia untuk belanja.
7. Semua transaksi bisa dipantau admin lewat "Riwayat Transaksi".

CATATAN ALUR UANG
------------------
- Saldo siswa/guru bertambah SAAT setoran dicatat admin (bukan menunggu pengepul membeli), supaya nasabah
  langsung melihat hasil setorannya. Secara bisnis, uang itu berasal dari hasil bank sampah menjual stok
  terkumpul ke pengepul -> makanya penting admin memantau statistik penjualan ke pengepul supaya kas bank
  sampah tetap sehat.
- Saldo pengepul adalah "dompet" yang di-top up admin (mewakili uang yang sudah diterima admin dari
  pengepul secara tunai/transfer di luar sistem). Setiap kali pengepul mencatat pembelian, saldo itu
  otomatis dipotong sebagai bukti pembayaran ke bank sampah.

CATATAN
-------
- Belum diberi framework CSS pihak ketiga; semua styling ditulis manual per halaman mengikuti gaya file aslinya.
- Password di database masih pakai MD5 untuk contoh sederhana. Untuk penggunaan nyata sebaiknya pakai password_hash()/password_verify()
  dan query pakai prepared statement (mysqli_prepare) supaya lebih aman dari SQL Injection.

TEMA TAMPILAN (BARU)
--------------------
assets/tema.css  -> tema bersama semua halaman (navy + emerald, sidebar melayang, topbar, mode gelap).
assets/tema.js   -> tombol buka/tutup menu, toggle mode terang/gelap (pilihan diingat di browser), sidebar responsif.
Untuk mengubah warna, edit variabel --t-pink, --t-purple, --t-blue, --t-em, --t-navy-1 di bagian :root pada assets/tema.css.
