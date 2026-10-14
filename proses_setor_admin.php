<?php

session_start();
include "koneksi.php";

/* Halaman ini khusus admin */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: setor_admin.php");
    exit;
}

$id_user   = isset($_POST['id_user']) ? (int) $_POST['id_user'] : 0;
$id_sampah = isset($_POST['id_sampah']) ? (int) $_POST['id_sampah'] : 0;
$berat_kg  = isset($_POST['berat_kg']) ? (float) $_POST['berat_kg'] : 0;

if ($id_user <= 0 || $id_sampah <= 0 || $berat_kg <= 0) {
    header("Location: setor_admin.php?error=Nasabah, jenis sampah, dan berat wajib diisi dengan benar");
    exit;
}

/* Pastikan nasabah valid dan berperan siswa/guru */
$query_user = mysqli_query(
    $koneksi,
    "SELECT id_user, role, saldo FROM users WHERE id_user = '$id_user'"
);
$user = mysqli_fetch_assoc($query_user);

if (!$user || !in_array($user['role'], ['siswa', 'guru'], true)) {
    header("Location: setor_admin.php?error=Nasabah tidak ditemukan atau bukan siswa/guru");
    exit;
}

/* Ambil data sampah (harga) */
$query_sampah = mysqli_query(
    $koneksi,
    "SELECT jenis_sampah, harga_per_kg FROM sampah WHERE id_sampah = '$id_sampah'"
);
$sampah = mysqli_fetch_assoc($query_sampah);

if (!$sampah) {
    header("Location: setor_admin.php?error=Jenis sampah tidak ditemukan");
    exit;
}

$total_rp = (int) round($berat_kg * $sampah['harga_per_kg']);
$tanggal  = date("Y-m-d H:i:s");

/* Simpan transaksi setoran */
$sql_insert = "INSERT INTO transaksi
        (id_user, id_sampah, tipe, berat_kg, total_rp, tanggal)
        VALUES
        ('$id_user', '$id_sampah', 'setor', '$berat_kg', '$total_rp', '$tanggal')";

if (!mysqli_query($koneksi, $sql_insert)) {
    header("Location: setor_admin.php?error=Gagal menyimpan transaksi setoran");
    exit;
}

/* Tambah saldo nasabah */
mysqli_query(
    $koneksi,
    "UPDATE users SET saldo = saldo + '$total_rp' WHERE id_user = '$id_user'"
);

/* Tambah stok sampah di bank sampah */
mysqli_query(
    $koneksi,
    "UPDATE sampah SET stok_kg = stok_kg + '$berat_kg' WHERE id_sampah = '$id_sampah'"
);

header("Location: setor_admin.php?sukses=1");
exit;
