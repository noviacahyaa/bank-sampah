<?php

session_start();
include "koneksi.php";

/* Halaman ini khusus admin */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$role_valid = ['siswa', 'guru', 'pengepul', 'admin'];
$aksi = isset($_POST['aksi']) ? $_POST['aksi'] : (isset($_GET['aksi']) ? $_GET['aksi'] : '');

/* ================= TAMBAH ================= */
if ($aksi === 'tambah') {

    $nama     = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role     = isset($_POST['role']) ? $_POST['role'] : '';

    if ($nama === '' || $username === '' || $password === '' || !in_array($role, $role_valid, true)) {
        header("Location: kelola_user.php?error=Semua data wajib diisi dengan benar");
        exit;
    }

    $username_esc = mysqli_real_escape_string($koneksi, $username);

    /* Cek username unik */
    $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username = '$username_esc'");
    if (mysqli_num_rows($cek) > 0) {
        header("Location: kelola_user.php?error=Username sudah digunakan, pilih username lain");
        exit;
    }

    $nama_esc     = mysqli_real_escape_string($koneksi, $nama);
    $password_md5 = md5($password);

    $sql = "INSERT INTO users (nama, username, password, role, saldo)
            VALUES ('$nama_esc', '$username_esc', '$password_md5', '$role', 0)";

    if (mysqli_query($koneksi, $sql)) {
        header("Location: kelola_user.php?sukses=" . urlencode("Akun $nama berhasil ditambahkan."));
    } else {
        header("Location: kelola_user.php?error=Gagal menambah pengguna");
    }
    exit;
}

/* ================= EDIT ================= */
if ($aksi === 'edit') {

    $id_user  = isset($_POST['id_user']) ? (int) $_POST['id_user'] : 0;
    $nama     = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role     = isset($_POST['role']) ? $_POST['role'] : '';

    if ($id_user <= 0 || $nama === '' || $username === '' || !in_array($role, $role_valid, true)) {
        header("Location: kelola_user.php?error=Data tidak lengkap untuk mengubah pengguna");
        exit;
    }

    /* Cegah admin tidak sengaja mengubah role akunnya sendiri jadi bukan admin */
    if ($id_user === (int) $_SESSION['id_user']) {
        $role = 'admin';
    }

    $username_esc = mysqli_real_escape_string($koneksi, $username);

    /* Cek username unik (selain miliknya sendiri) */
    $cek = mysqli_query(
        $koneksi,
        "SELECT id_user FROM users WHERE username = '$username_esc' AND id_user != '$id_user'"
    );
    if (mysqli_num_rows($cek) > 0) {
        header("Location: kelola_user.php?error=Username sudah digunakan, pilih username lain");
        exit;
    }

    $nama_esc = mysqli_real_escape_string($koneksi, $nama);

    if ($password !== '') {
        $password_md5 = md5($password);
        $sql = "UPDATE users
                SET nama = '$nama_esc', username = '$username_esc', role = '$role', password = '$password_md5'
                WHERE id_user = '$id_user'";
    } else {
        $sql = "UPDATE users
                SET nama = '$nama_esc', username = '$username_esc', role = '$role'
                WHERE id_user = '$id_user'";
    }

    if (mysqli_query($koneksi, $sql)) {

        /* Jika admin mengubah datanya sendiri, sinkronkan session */
        if ($id_user === (int) $_SESSION['id_user']) {
            $_SESSION['nama'] = $nama;
            $_SESSION['role'] = $role;
        }

        header("Location: kelola_user.php?sukses=" . urlencode("Data $nama berhasil diperbarui."));
    } else {
        header("Location: kelola_user.php?error=Gagal mengubah data pengguna");
    }
    exit;
}

/* ================= HAPUS ================= */
if ($aksi === 'hapus') {

    $id_user = isset($_GET['id_user']) ? (int) $_GET['id_user'] : 0;

    if ($id_user <= 0) {
        header("Location: kelola_user.php?error=Data tidak valid");
        exit;
    }

    if ($id_user === (int) $_SESSION['id_user']) {
        header("Location: kelola_user.php?error=Tidak bisa menghapus akun yang sedang login");
        exit;
    }

    $sql = "DELETE FROM users WHERE id_user = '$id_user'";

    if (mysqli_query($koneksi, $sql)) {
        header("Location: kelola_user.php?sukses=1");
    } else {
        header("Location: kelola_user.php?error=Tidak bisa menghapus, pengguna ini sudah memiliki riwayat transaksi");
    }
    exit;
}

header("Location: kelola_user.php");
exit;
