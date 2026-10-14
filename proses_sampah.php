<?php

session_start();
include "koneksi.php";

/* Halaman ini khusus admin */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$aksi = isset($_POST['aksi']) ? $_POST['aksi'] : (isset($_GET['aksi']) ? $_GET['aksi'] : '');

/* ================= TAMBAH ================= */
if ($aksi === 'tambah') {

    $jenis_sampah = isset($_POST['jenis_sampah']) ? trim($_POST['jenis_sampah']) : '';
    $harga        = isset($_POST['harga_per_kg']) ? (int) $_POST['harga_per_kg'] : 0;
    $stok         = isset($_POST['stok_kg']) ? (float) $_POST['stok_kg'] : 0;

    if ($jenis_sampah === '' || $harga <= 0) {
        header("Location: kelola_sampah.php?error=Nama jenis sampah dan harga wajib diisi dengan benar");
        exit;
    }

    $jenis_sampah_esc = mysqli_real_escape_string($koneksi, $jenis_sampah);

    $sql = "INSERT INTO sampah (jenis_sampah, harga_per_kg, stok_kg)
            VALUES ('$jenis_sampah_esc', '$harga', '$stok')";

    if (mysqli_query($koneksi, $sql)) {
        header("Location: kelola_sampah.php?sukses=1");
    } else {
        header("Location: kelola_sampah.php?error=Gagal menambah data sampah");
    }
    exit;
}

/* ================= EDIT ================= */
if ($aksi === 'edit') {

    $id_sampah    = isset($_POST['id_sampah']) ? (int) $_POST['id_sampah'] : 0;
    $jenis_sampah = isset($_POST['jenis_sampah']) ? trim($_POST['jenis_sampah']) : '';
    $harga        = isset($_POST['harga_per_kg']) ? (int) $_POST['harga_per_kg'] : 0;
    $stok         = isset($_POST['stok_kg']) ? (float) $_POST['stok_kg'] : 0;

    if ($id_sampah <= 0 || $jenis_sampah === '' || $harga <= 0) {
        header("Location: kelola_sampah.php?error=Data tidak lengkap untuk mengubah jenis sampah");
        exit;
    }

    $jenis_sampah_esc = mysqli_real_escape_string($koneksi, $jenis_sampah);

    $sql = "UPDATE sampah
            SET jenis_sampah = '$jenis_sampah_esc',
                harga_per_kg = '$harga',
                stok_kg = '$stok'
            WHERE id_sampah = '$id_sampah'";

    if (mysqli_query($koneksi, $sql)) {
        header("Location: kelola_sampah.php?sukses=1");
    } else {
        header("Location: kelola_sampah.php?error=Gagal mengubah data sampah");
    }
    exit;
}

/* ================= HAPUS ================= */
if ($aksi === 'hapus') {

    $id_sampah = isset($_GET['id_sampah']) ? (int) $_GET['id_sampah'] : 0;

    if ($id_sampah <= 0) {
        header("Location: kelola_sampah.php?error=Data tidak valid");
        exit;
    }

    $sql = "DELETE FROM sampah WHERE id_sampah = '$id_sampah'";

    if (mysqli_query($koneksi, $sql)) {
        header("Location: kelola_sampah.php?sukses=1");
    } else {
        header("Location: kelola_sampah.php?error=Tidak bisa menghapus, jenis sampah ini sudah memiliki riwayat transaksi");
    }
    exit;
}

header("Location: kelola_sampah.php");
exit;
