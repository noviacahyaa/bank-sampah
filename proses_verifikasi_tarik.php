<?php

session_start();
include "koneksi.php";

/* Halaman ini khusus admin */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$id_tarik = isset($_GET['id_tarik']) ? (int) $_GET['id_tarik'] : 0;
$aksi     = isset($_GET['aksi']) ? $_GET['aksi'] : '';

if ($id_tarik <= 0 || !in_array($aksi, ['setuju', 'tolak'], true)) {
    header("Location: verifikasi_tarik.php?error=Aksi tidak valid");
    exit;
}

/* Ambil data pengajuan, harus masih pending */
$query = mysqli_query(
    $koneksi,
    "SELECT ts.id_tarik, ts.id_user, ts.nominal, ts.status, u.nama, u.saldo
     FROM tarik_saldo ts
     JOIN users u ON ts.id_user = u.id_user
     WHERE ts.id_tarik = '$id_tarik'
     LIMIT 1"
);
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("Location: verifikasi_tarik.php?error=Pengajuan tidak ditemukan");
    exit;
}

if ($data['status'] !== 'pending') {
    header("Location: verifikasi_tarik.php?error=Pengajuan ini sudah diproses sebelumnya");
    exit;
}

/* ================= SETUJUI ================= */
if ($aksi === 'setuju') {

    $saldo_sekarang = (int) $data['saldo'];
    $nominal        = (int) $data['nominal'];

    if ($nominal > $saldo_sekarang) {
        header("Location: verifikasi_tarik.php?error=Saldo " . urlencode($data['nama']) . " tidak lagi mencukupi untuk penarikan ini");
        exit;
    }

    mysqli_query(
        $koneksi,
        "UPDATE users SET saldo = saldo - '$nominal' WHERE id_user = '" . (int) $data['id_user'] . "'"
    );

    mysqli_query(
        $koneksi,
        "UPDATE tarik_saldo SET status = 'selesai' WHERE id_tarik = '$id_tarik'"
    );

    header("Location: verifikasi_tarik.php?sukses=" . urlencode("Penarikan saldo {$data['nama']} disetujui."));
    exit;
}

/* ================= TOLAK ================= */
if ($aksi === 'tolak') {

    mysqli_query(
        $koneksi,
        "UPDATE tarik_saldo SET status = 'ditolak' WHERE id_tarik = '$id_tarik'"
    );

    header("Location: verifikasi_tarik.php?sukses=" . urlencode("Pengajuan penarikan {$data['nama']} ditolak."));
    exit;
}
