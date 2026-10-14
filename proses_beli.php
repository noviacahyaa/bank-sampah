<?php

session_start();
include "koneksi.php";

/* Halaman ini khusus role pengepul */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pengepul') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard_pengepul.php");
    exit;
}

$id_user = $_SESSION['id_user'];

$id_sampah = isset($_POST['id_sampah']) ? (int) $_POST['id_sampah'] : 0;
$berat_kg  = isset($_POST['berat_kg']) ? (float) $_POST['berat_kg'] : 0;

if ($id_sampah <= 0 || $berat_kg <= 0) {
    header("Location: dashboard_pengepul.php?error=Jenis sampah dan berat wajib diisi dengan benar");
    exit;
}

/* Ambil saldo pengepul saat ini */
$query_pengepul = mysqli_query(
    $koneksi,
    "SELECT saldo FROM users WHERE id_user = '$id_user'"
);
$pengepul = mysqli_fetch_assoc($query_pengepul);
$saldo_pengepul = $pengepul ? (int) $pengepul['saldo'] : 0;

/* Ambil data sampah (harga & stok) */
$query = mysqli_query(
    $koneksi,
    "SELECT jenis_sampah, harga_per_kg, stok_kg FROM sampah WHERE id_sampah = '$id_sampah'"
);
$sampah = mysqli_fetch_assoc($query);

if (!$sampah) {
    header("Location: dashboard_pengepul.php?error=Jenis sampah tidak ditemukan");
    exit;
}

$stok_tersedia = (float) $sampah['stok_kg'];

/* Cek stok cukup */
if ($berat_kg > $stok_tersedia) {
    header("Location: dashboard_pengepul.php?error=Stok tidak cukup. Stok tersedia hanya " . number_format($stok_tersedia, 1, ',', '.') . " kg");
    exit;
}

$total_rp = (int) round($berat_kg * $sampah['harga_per_kg']);

/* Cek saldo pengepul cukup untuk membayar pembelian ini */
if ($total_rp > $saldo_pengepul) {
    header("Location: dashboard_pengepul.php?error=Saldo kamu tidak cukup. Saldo saat ini Rp " . number_format($saldo_pengepul, 0, ',', '.') . ", butuh Rp " . number_format($total_rp, 0, ',', '.') . ". Silakan minta admin untuk top up saldo.");
    exit;
}

$tanggal  = date("Y-m-d H:i:s");

/* Simpan transaksi */
$sql_insert = "INSERT INTO transaksi
        (id_user, id_sampah, tipe, berat_kg, total_rp, tanggal)
        VALUES
        ('$id_user', '$id_sampah', 'jual_pengepul', '$berat_kg', '$total_rp', '$tanggal')";

if (!mysqli_query($koneksi, $sql_insert)) {
    header("Location: dashboard_pengepul.php?error=Gagal menyimpan transaksi");
    exit;
}

/* Kurangi stok sampah */
$sql_update = "UPDATE sampah
        SET stok_kg = stok_kg - '$berat_kg'
        WHERE id_sampah = '$id_sampah'";

mysqli_query($koneksi, $sql_update);

/* Kurangi saldo pengepul sebesar total pembayaran ke bank sampah */
$sql_saldo = "UPDATE users
        SET saldo = saldo - '$total_rp'
        WHERE id_user = '$id_user'";

mysqli_query($koneksi, $sql_saldo);

header("Location: dashboard_pengepul.php?sukses=1");
exit;
