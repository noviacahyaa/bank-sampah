<?php

session_start();

include 'koneksi.php';


// Pastikan data dikirim dari form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");
    exit;

}


// Ambil data dari form
$username = isset($_POST['username'])
    ? trim($_POST['username'])
    : '';

$password = isset($_POST['password'])
    ? $_POST['password']
    : '';


// Validasi kosong
if ($username === '' || $password === '') {

    header("Location: index.php?error=1");
    exit;

}


// Amankan username
$username = mysqli_real_escape_string(
    $koneksi,
    $username
);


// Password menggunakan MD5
$password_md5 = md5($password);


// Query database
$sql = "
    SELECT *
    FROM users
    WHERE username = '$username'
    AND password = '$password_md5'
    LIMIT 1
";


$result = mysqli_query($koneksi, $sql);


// Jika query gagal
if (!$result) {

    die(
        "Terjadi kesalahan database: "
        . mysqli_error($koneksi)
    );

}


// Cek user
if (mysqli_num_rows($result) === 1) {

    $data = mysqli_fetch_assoc($result);


    // Simpan session
    $_SESSION['id_user'] = $data['id_user'];

    $_SESSION['nama'] = $data['nama'];

    $_SESSION['role'] = $data['role'];


    // Login berhasil - arahkan sesuai role
    if ($data['role'] === 'pengepul') {
        header("Location: dashboard_pengepul.php");
        exit;
    }

    if ($data['role'] === 'admin') {
        header("Location: dashboard_admin.php");
        exit;
    }

    header("Location: dashboard.php");
    exit;

}


// Login gagal
header("Location: index.php?error=1");

exit;
