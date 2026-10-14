<?php

session_start();
include "koneksi.php";

/* Halaman ini khusus admin */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: topup_pengepul.php");
    exit;
}

$id_user = isset($_POST['id_user']) ? (int) $_POST['id_user'] : 0;
$nominal = isset($_POST['nominal']) ? (int) $_POST['nominal'] : 0;

if ($id_user <= 0 || $nominal <= 0) {
    header("Location: topup_pengepul.php?error=Pengepul dan nominal top up wajib diisi dengan benar");
    exit;
}

/* Pastikan akun tujuan valid dan berperan pengepul */
$query_user = mysqli_query(
    $koneksi,
    "SELECT id_user, role FROM users WHERE id_user = '$id_user'"
);
$user = mysqli_fetch_assoc($query_user);

if (!$user || $user['role'] !== 'pengepul') {
    header("Location: topup_pengepul.php?error=Akun tidak ditemukan atau bukan pengepul");
    exit;
}

$tanggal = date("Y-m-d H:i:s");

/* Catat riwayat top up */
$sql_insert = "INSERT INTO topup_pengepul
        (id_user, nominal, tanggal)
        VALUES
        ('$id_user', '$nominal', '$tanggal')";

if (!mysqli_query($koneksi, $sql_insert)) {
    header("Location: topup_pengepul.php?error=Gagal menyimpan riwayat top up");
    exit;
}

/* Tambah saldo pengepul */
mysqli_query(
    $koneksi,
    "UPDATE users SET saldo = saldo + '$nominal' WHERE id_user = '$id_user'"
);

header("Location: topup_pengepul.php?sukses=1");
exit;
