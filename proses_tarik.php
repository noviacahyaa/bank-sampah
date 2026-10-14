<?php

session_start();
include "koneksi.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header("Location: dashboard_admin.php");
    exit;
}

/* Halaman ini khusus siswa/guru, pengepul punya dashboard sendiri */
if ($_SESSION['role'] === 'pengepul') {
    header("Location: dashboard_pengepul.php");
    exit;
}

$id_user = $_SESSION['id_user'];

if (!isset($_POST['nominal'])) {
    header("Location: tarik_saldo.php?error=Nominal tidak ditemukan");
    exit;
}

/* Hilangkan titik/koma dari nominal */
$nominal = $_POST['nominal'];
$nominal = str_replace('.', '', $nominal);
$nominal = str_replace(',', '', $nominal);

$nominal = (int) $nominal;

/* Ambil saldo */
$query = mysqli_query(
    $koneksi,
    "SELECT saldo FROM users WHERE id_user = '$id_user'"
);

$user = mysqli_fetch_assoc($query);

if (!$user) {
    header("Location: tarik_saldo.php?error=Data pengguna tidak ditemukan");
    exit;
}

$saldo = (int) $user['saldo'];

/* Cek minimal */
if ($nominal < 1000) {
    header("Location: tarik_saldo.php?error=Minimal penarikan Rp 1.000");
    exit;
}

/* Cek saldo */
if ($nominal > $saldo) {
    header("Location: tarik_saldo.php?error=Saldo tidak mencukupi");
    exit;
}

/* Simpan pengajuan */
$tanggal = date("Y-m-d H:i:s");

$sql = "INSERT INTO tarik_saldo
        (id_user, nominal, tanggal, status)
        VALUES
        ('$id_user', '$nominal', '$tanggal', 'pending')";

if (mysqli_query($koneksi, $sql)) {

    header("Location: tarik_saldo.php?sukses=1");
    exit;

} else {

    header("Location: tarik_saldo.php?error=Gagal menyimpan pengajuan");
    exit;

}
