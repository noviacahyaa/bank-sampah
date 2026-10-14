<?php

date_default_timezone_set('Asia/Jakarta');
session_start();
include "koneksi.php";

/* Semua role yang sudah login boleh setor sampah untuk akunnya sendiri */
$role_valid = ['siswa', 'guru', 'pengepul', 'admin'];

if (!isset($_SESSION['id_user']) || !in_array($_SESSION['role'], $role_valid, true)) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: setor_sampah.php");
    exit;
}

$id_user   = (int) $_SESSION['id_user'];
$id_sampah = isset($_POST['id_sampah']) ? (int) $_POST['id_sampah'] : 0;
$berat_kg  = isset($_POST['berat_kg']) ? (float) $_POST['berat_kg'] : 0;

if ($id_sampah <= 0 || $berat_kg <= 0) {
    header("Location: setor_sampah.php?error=" . urlencode("Jenis sampah dan berat wajib diisi dengan benar"));
    exit;
}

/* Batas wajar supaya tidak salah input (misal 5000 kg) */
if ($berat_kg > 1000) {
    header("Location: setor_sampah.php?error=" . urlencode("Berat terlalu besar. Maksimal 1.000 kg per setoran"));
    exit;
}

/* Ambil harga sampah */
$stmt = mysqli_prepare($koneksi, "SELECT harga_per_kg FROM sampah WHERE id_sampah = ?");
mysqli_stmt_bind_param($stmt, "i", $id_sampah);
mysqli_stmt_execute($stmt);
$sampah = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$sampah) {
    header("Location: setor_sampah.php?error=" . urlencode("Jenis sampah tidak ditemukan"));
    exit;
}

$total_rp = (int) round($berat_kg * $sampah['harga_per_kg']);
$tanggal  = date("Y-m-d H:i:s");

/* Simpan transaksi + saldo + stok dalam satu transaksi database */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_begin_transaction($koneksi);

try {

    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO transaksi (id_user, id_sampah, tipe, berat_kg, total_rp, tanggal)
         VALUES (?, ?, 'setor', ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "iidis", $id_user, $id_sampah, $berat_kg, $total_rp, $tanggal);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($koneksi, "UPDATE users SET saldo = saldo + ? WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, "ii", $total_rp, $id_user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($koneksi, "UPDATE sampah SET stok_kg = stok_kg + ? WHERE id_sampah = ?");
    mysqli_stmt_bind_param($stmt, "di", $berat_kg, $id_sampah);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($koneksi);

} catch (Throwable $e) {

    mysqli_rollback($koneksi);
    header("Location: setor_sampah.php?error=" . urlencode("Gagal menyimpan setoran, coba lagi"));
    exit;

}

header("Location: setor_sampah.php?sukses=1");
exit;
