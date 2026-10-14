<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
include "koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$nama_admin = $_SESSION['nama'];

function rupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

/* Statistik jumlah pengguna per role */
$query_role = mysqli_query(
    $koneksi,
    "SELECT role, COUNT(*) AS jumlah FROM users GROUP BY role"
);
$jumlah_role = ['siswa' => 0, 'guru' => 0, 'pengepul' => 0, 'admin' => 0];
while ($row = mysqli_fetch_assoc($query_role)) {
    $jumlah_role[$row['role']] = (int) $row['jumlah'];
}

/* Total saldo beredar (siswa + guru) */
$query_saldo = mysqli_query(
    $koneksi,
    "SELECT COALESCE(SUM(saldo), 0) AS total FROM users WHERE role IN ('siswa','guru')"
);
$total_saldo = (int) mysqli_fetch_assoc($query_saldo)['total'];

/* Jenis sampah & total stok */
$query_sampah_stat = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS jenis, COALESCE(SUM(stok_kg), 0) AS stok FROM sampah"
);
$sampah_stat = mysqli_fetch_assoc($query_sampah_stat);
$total_jenis_sampah = (int) $sampah_stat['jenis'];
$total_stok = (float) $sampah_stat['stok'];

/* Tarik saldo pending */
$query_pending = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS jumlah, COALESCE(SUM(nominal), 0) AS total FROM tarik_saldo WHERE status = 'pending'"
);
$pending = mysqli_fetch_assoc($query_pending);
$pending_jumlah = (int) $pending['jumlah'];
$pending_total  = (int) $pending['total'];

/* Total transaksi hari ini */
$query_hari_ini = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS jumlah FROM transaksi WHERE DATE(tanggal) = CURDATE()"
);
$transaksi_hari_ini = (int) mysqli_fetch_assoc($query_hari_ini)['jumlah'];

/* Statistik penjualan ke pengepul: hari ini & bulan ini */
$query_jual_hari_ini = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS jumlah, COALESCE(SUM(berat_kg), 0) AS berat, COALESCE(SUM(total_rp), 0) AS total
     FROM transaksi
     WHERE tipe = 'jual_pengepul' AND DATE(tanggal) = CURDATE()"
);
$jual_hari_ini = mysqli_fetch_assoc($query_jual_hari_ini);

$query_jual_bulan_ini = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS jumlah, COALESCE(SUM(berat_kg), 0) AS berat, COALESCE(SUM(total_rp), 0) AS total
     FROM transaksi
     WHERE tipe = 'jual_pengepul'
       AND YEAR(tanggal) = YEAR(CURDATE()) AND MONTH(tanggal) = MONTH(CURDATE())"
);
$jual_bulan_ini = mysqli_fetch_assoc($query_jual_bulan_ini);

/* Total saldo pengepul yang masih tersedia untuk belanja */
$query_saldo_pengepul = mysqli_query(
    $koneksi,
    "SELECT COALESCE(SUM(saldo), 0) AS total FROM users WHERE role = 'pengepul'"
);
$total_saldo_pengepul = (int) mysqli_fetch_assoc($query_saldo_pengepul)['total'];

/* Riwayat transaksi terbaru (gabungan setor & jual_pengepul) */
$query_riwayat = mysqli_query(
    $koneksi,
    "SELECT t.tanggal, t.tipe, t.berat_kg, t.total_rp, u.nama, s.jenis_sampah
     FROM transaksi t
     JOIN users u ON t.id_user = u.id_user
     JOIN sampah s ON t.id_sampah = s.id_sampah
     ORDER BY t.tanggal DESC
     LIMIT 6"
);

/* Pengajuan tarik saldo terbaru yang masih pending */
$query_tarik_terbaru = mysqli_query(
    $koneksi,
    "SELECT ts.id_tarik, ts.nominal, ts.tanggal, u.nama
     FROM tarik_saldo ts
     JOIN users u ON ts.id_user = u.id_user
     WHERE ts.status = 'pending'
     ORDER BY ts.tanggal ASC
     LIMIT 5"
);

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin - EcoSave</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f7f9fb, #ecf2f6);
            color: #314054;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .main > * {
            animation: fadeUp 0.45s ease both;
        }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            padding: 30px 20px;
            background: linear-gradient(180deg, #6df0c4, #47288f);
            color: white;
            box-shadow: 4px 0 15px rgba(0,0,0, 0.08);
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.10) 1.5px, transparent 1.5px);
            background-size: 18px 18px;
            pointer-events: none;
        }

        .sidebar > * {
            position: relative;
            z-index: 1;
        }

        .role-tag {
            display: inline-block;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 20px;
            margin: 0 auto 18px;
            display: block;
            text-align: center;
            width: fit-content;
        }

        .logo {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 21px;
            font-weight: 600;
            text-align: center;
            padding-bottom: 22px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.25);
        }

        .logo span {
            display: block;
            font-size: 11px;
            font-weight: 400;
            opacity: 0.85;
            margin-top: 3px;
            letter-spacing: 0.5px;
        }

        .menu-title {
            font-size: 12px;
            opacity: 0.75;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .menu {
            list-style: none;
            margin-bottom: 26px;
        }

        .menu li {
            margin: 6px 0;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 15px;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-size: 14px;
            transition: 0.25s;
        }

        .menu a i {
            width: 18px;
            text-align: center;
        }

        .menu a:hover {
            background: rgba(255,255,255, 0.16);
            transform: translateX(4px);
        }

        .menu a.active {
            background: rgba(255,255,255, 0.22);
            font-weight: 600;
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        }

        .sidebar-footer {
            font-size: 11px;
            opacity: 0.7;
            text-align: center;
            line-height: 1.6;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        /* MAIN */

        .main {
            margin-left: 250px;
            min-height: 100vh;
            padding: 32px 38px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 14px;
        }

        .topbar h1 {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 25px;
            font-weight: 600;
            color: #47288f;
        }

        .topbar p {
            color: #818fa1;
            font-size: 13px;
            margin-top: 4px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255, 0.9);
            padding: 8px 16px 8px 8px;
            border-radius: 30px;
            box-shadow: 0 7px 20px rgba(71,40,143, 0.1);
            font-size: 13px;
        }

        .profile-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6434d3, #47288f);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* ALERTS */

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 13.5px;
            line-height: 1.5;
        }

        .alert i { font-size: 17px; margin-top: 1px; }

        .alert-sukses {
            background: #ecf2f6;
            border: 1px solid #b9ecdb;
            color: #3e1e8a;
        }

        .alert-error {
            background: #f5f8fa;
            border: 1px solid #cdfbec;
            color: #48298f;
        }

        .alert-info {
            background: #fff8e6;
            border: 1px solid #ffe1a8;
            color: #9a7200;
        }

        /* STAT CARDS */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }

        .card {
            position: relative;
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 22px rgba(71,40,143, 0.08);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: 0.25s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 13px 28px rgba(71,40,143, 0.13);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }

        .icon-violet { background: #ecf2f6; color: #452395; }
        .icon-pink   { background: #ecf2f6; color: #421c9b; }
        .icon-green  { background: #e5edf2; color: #492d8a; }
        .icon-orange { background: #ffe9d9; color: #d97a3e; }
        .icon-yellow { background: #fff3d6; color: #c99420; }
        .icon-blue   { background: #e2effd; color: #2e7bc9; }

        .card-title {
            font-size: 12px;
            color: #818fa1;
            margin-bottom: 4px;
        }

        .card-value {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: #314054;
        }

        /* GRID LAYOUT */

        .grid-2 {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 18px;
            margin-bottom: 22px;
            align-items: start;
        }

        /* BOX / TABLE BOX */

        .box, .table-box {
            background: white;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 8px 22px rgba(71,40,143, 0.08);
            margin-bottom: 22px;
        }

        .box-header, .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .box-header h2, .table-header h2 {
            font-size: 17px;
            color: #47288f;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .box-header a, .table-header a {
            font-size: 13px;
            font-weight: 600;
            color: #452395;
            text-decoration: none;
        }

        .box-header a:hover, .table-header a:hover {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f4f7fa;
            color: #4873ab;
            text-align: left;
            padding: 12px 14px;
            font-size: 12.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            white-space: nowrap;
        }

        th:first-child { border-radius: 10px 0 0 10px; }
        th:last-child  { border-radius: 0 10px 10px 0; }

        td {
            padding: 13px 14px;
            border-bottom: 1px solid #edf2f6;
            font-size: 13.5px;
            vertical-align: middle;
        }

        .total-cell {
            color: #452395;
            font-weight: 700;
        }

        .empty-state {
            text-align: center;
            padding: 40px 10px;
            color: #b0bdcf;
        }

        .empty-state i {
            font-size: 34px;
            margin-bottom: 10px;
            display: block;
        }

        .table-scroll {
            overflow-x: auto;
        }

        /* PILLS / BADGES */

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
        }

        .pill-pending { background: #fff3d6; color: #9a7200; }
        .pill-selesai { background: #e5edf2; color: #402188; }
        .pill-ditolak { background: #ecf2f6; color: #48298f; }
        .pill-siswa   { background: #ecf2f6; color: #421c9b; }
        .pill-guru    { background: #e2effd; color: #2e7bc9; }
        .pill-pengepul{ background: #e5edf2; color: #492d8a; }
        .pill-admin   { background: #ecf2f6; color: #452395; }
        .pill-setor   { background: #e5edf2; color: #402188; }
        .pill-jual    { background: #e2effd; color: #2e7bc9; }

        /* BUTTONS */

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: none;
            cursor: pointer;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            transition: 0.2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6434d3, #47288f);
            color: white;
            box-shadow: 0 10px 20px rgba(71,40,143, 0.25);
        }

        .btn-primary:hover { transform: translateY(-2px); }

        .btn-success {
            background: linear-gradient(135deg, #4a3088, #492d8a);
            color: white;
        }

        .btn-danger {
            background: #f5f8fa;
            color: #48298f;
            border: 1px solid #cdfbec;
        }

        .btn-outline {
            background: white;
            color: #47288f;
            border: 1.5px solid #e0e9f0;
        }

        .btn-sm { padding: 7px 13px; font-size: 12px; }

        .action-group { display: flex; gap: 8px; flex-wrap: wrap; }

        /* FORM */

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .form-group { margin-bottom: 4px; }

        .form-group.full { grid-column: 1 / -1; }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
            color: #47288f;
        }

        input, select {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #ecf2f6;
            border-radius: 12px;
            outline: none;
            background: #fbfcfd;
            color: #314054;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: 0.25s;
        }

        input:focus, select:focus {
            border-color: #6434d3;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(140,245,210, 0.14);
        }

        .hint-box {
            background: #f4f7fa;
            border: 1px dashed #e0e9f0;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 13px;
            color: #4873ab;
            margin-top: 4px;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .filter-tabs a {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
            background: #f4f7fa;
            color: #4873ab;
        }

        .filter-tabs a.active {
            background: linear-gradient(135deg, #6434d3, #47288f);
            color: white;
        }

        /* RESPONSIVE */

        @media (max-width: 1050px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
            .grid-2 { grid-template-columns: 1fr; }
        }

        @media (max-width: 850px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 20px;
            }

            .menu {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            .menu a span { display: none; }

            .sidebar-footer { display: none; }

            .main {
                margin-left: 0;
                padding: 22px;
            }

            .form-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 550px) {
            .stats { grid-template-columns: 1fr; }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }

    </style>


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/tema.css">
    <script>try{if(localStorage.getItem('ecosave_tema')==='gelap')document.documentElement.setAttribute('data-tema','gelap')}catch(e){}</script>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">
            🛠️ EcoSave
            <span>Panel Admin</span>
        </div>

        <ul class="menu">

            <li>
                <a href="dashboard_admin.php" class="active">
                    <i class="fa-solid fa-house"></i>
                    <span>Beranda</span>
                </a>
            </li>

            <li>
                <a href="setor_admin.php" class="">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span>Catat Setoran</span>
                </a>
            </li>

            <li>
                <a href="topup_pengepul.php" class="">
                    <i class="fa-solid fa-wallet"></i>
                    <span>Top Up Pengepul</span>
                </a>
            </li>

            <li>
                <a href="verifikasi_tarik.php" class="">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                    <span>Verifikasi Tarik Saldo</span>
                </a>
            </li>

            <li>
                <a href="kelola_sampah.php" class="">
                    <i class="fa-solid fa-trash-can-arrow-up"></i>
                    <span>Kelola Sampah</span>
                </a>
            </li>

            <li>
                <a href="kelola_user.php" class="">
                    <i class="fa-solid fa-users"></i>
                    <span>Kelola Pengguna</span>
                </a>
            </li>

            <li>
                <a href="riwayat_admin.php" class="">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Riwayat Transaksi</span>
                </a>
            </li>

            <li>
                <a href="setor_sampah.php">
                    <i class="fa-solid fa-recycle"></i>
                    <span>Setor Sampah</span>
                </a>
            </li>

            <li>
                <a href="logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Keluar</span>
                </a>
            </li>

        </ul>

        <div class="sidebar-footer">
            🛠️ EcoSave<br>
            Sistem Informasi Bank Sampah
        </div>

    </aside>

    <!-- MAIN -->
    <main class="main">

        <div class="topbar">

            <div>
                <h1>👋 Halo, Admin</h1>
                <p>Ringkasan aktivitas Bank Sampah EcoSave hari ini</p>
            </div>

            <div class="profile">
                <div class="profile-icon"><?= strtoupper(substr($nama_admin, 0, 1)) ?></div>
                <div>
                    <b><?= htmlspecialchars($nama_admin) ?></b><br>
                    <span style="color:#818fa1;">Admin</span>
                </div>
            </div>

        </div>


        <?php if ($pending_jumlah > 0): ?>
        <div class="alert alert-info">
            <i class="fa-solid fa-hourglass-half"></i>
            <div>
                Ada <b><?= $pending_jumlah ?> pengajuan tarik saldo</b> senilai
                <b><?= rupiah($pending_total) ?></b> yang menunggu verifikasi.
                <a href="verifikasi_tarik.php" style="color:#9a7200; font-weight:700;">Verifikasi sekarang &rarr;</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- STAT -->
        <div class="stats">

            <div class="card">
                <div class="card-icon icon-pink"><i class="fa-solid fa-user-graduate"></i></div>
                <div>
                    <div class="card-title">Siswa</div>
                    <div class="card-value"><?= $jumlah_role['siswa'] ?> akun</div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon icon-blue"><i class="fa-solid fa-chalkboard-user"></i></div>
                <div>
                    <div class="card-title">Guru</div>
                    <div class="card-value"><?= $jumlah_role['guru'] ?> akun</div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon icon-green"><i class="fa-solid fa-truck"></i></div>
                <div>
                    <div class="card-title">Pengepul</div>
                    <div class="card-value"><?= $jumlah_role['pengepul'] ?> akun</div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon icon-violet"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="card-title">Tarik Saldo Pending</div>
                    <div class="card-value"><?= $pending_jumlah ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon icon-violet"><i class="fa-solid fa-sack-dollar"></i></div>
                <div>
                    <div class="card-title">Total Saldo Beredar</div>
                    <div class="card-value"><?= rupiah($total_saldo) ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon icon-orange"><i class="fa-solid fa-recycle"></i></div>
                <div>
                    <div class="card-title">Jenis Sampah</div>
                    <div class="card-value"><?= $total_jenis_sampah ?> jenis</div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon icon-yellow"><i class="fa-solid fa-weight-hanging"></i></div>
                <div>
                    <div class="card-title">Total Stok Sampah</div>
                    <div class="card-value"><?= number_format($total_stok, 1, ',', '.') ?> kg</div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon icon-blue"><i class="fa-solid fa-calendar-day"></i></div>
                <div>
                    <div class="card-title">Transaksi Hari Ini</div>
                    <div class="card-value"><?= $transaksi_hari_ini ?>x</div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon icon-green"><i class="fa-solid fa-sack-dollar"></i></div>
                <div>
                    <div class="card-title">Penjualan ke Pengepul Hari Ini</div>
                    <div class="card-value"><?= rupiah((int) $jual_hari_ini['total']) ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon icon-violet"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <div class="card-title">Penjualan ke Pengepul Bulan Ini</div>
                    <div class="card-value"><?= rupiah((int) $jual_bulan_ini['total']) ?></div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon icon-orange"><i class="fa-solid fa-wallet"></i></div>
                <div>
                    <div class="card-title">Saldo Pengepul Tersedia</div>
                    <div class="card-value"><?= rupiah($total_saldo_pengepul) ?></div>
                </div>
            </div>

        </div>

        <!-- STATISTIK PENJUALAN -->
        <div class="table-box">

            <div class="table-header">
                <h2><i class="fa-solid fa-chart-column"></i> Statistik Penjualan Sampah ke Pengepul</h2>
            </div>

            <div class="stats" style="grid-template-columns: repeat(2, 1fr); margin-bottom:0;">

                <div class="card">
                    <div class="card-icon icon-green"><i class="fa-solid fa-calendar-day"></i></div>
                    <div>
                        <div class="card-title">Hari Ini</div>
                        <div class="card-value"><?= rupiah((int) $jual_hari_ini['total']) ?></div>
                        <div style="font-size:12px; color:#818fa1; margin-top:2px;">
                            <?= (int) $jual_hari_ini['jumlah'] ?>x transaksi &middot; <?= number_format((float) $jual_hari_ini['berat'], 1, ',', '.') ?> kg
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-icon icon-violet"><i class="fa-solid fa-calendar-days"></i></div>
                    <div>
                        <div class="card-title">Bulan Ini</div>
                        <div class="card-value"><?= rupiah((int) $jual_bulan_ini['total']) ?></div>
                        <div style="font-size:12px; color:#818fa1; margin-top:2px;">
                            <?= (int) $jual_bulan_ini['jumlah'] ?>x transaksi &middot; <?= number_format((float) $jual_bulan_ini['berat'], 1, ',', '.') ?> kg
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <div class="grid-2">

            <!-- RIWAYAT TERBARU -->
            <div class="table-box">

                <div class="table-header">
                    <h2><i class="fa-solid fa-clipboard-list"></i> Transaksi Terbaru</h2>
                    <a href="riwayat_admin.php">Lihat Semua &rarr;</a>
                </div>

                <div class="table-scroll">
                <table>

                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nasabah</th>
                            <th>Jenis</th>
                            <th>Tipe</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (mysqli_num_rows($query_riwayat) > 0): ?>

                        <?php while ($row = mysqli_fetch_assoc($query_riwayat)): ?>
                            <tr>
                                <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['jenis_sampah']) ?></td>
                                <td>
                                    <?php if ($row['tipe'] === 'setor'): ?>
                                        <span class="pill pill-setor"><i class="fa-solid fa-arrow-down"></i> Setoran</span>
                                    <?php else: ?>
                                        <span class="pill pill-jual"><i class="fa-solid fa-arrow-up"></i> Dibeli Pengepul</span>
                                    <?php endif; ?>
                                </td>
                                <td class="total-cell"><?= rupiah($row['total_rp']) ?></td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i>
                                    Belum ada transaksi.
                                </div>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>
                </div>

            </div>

            <!-- PENGAJUAN TARIK SALDO PENDING -->
            <div class="table-box">

                <div class="table-header">
                    <h2><i class="fa-solid fa-hourglass-half"></i> Menunggu Verifikasi</h2>
                    <a href="verifikasi_tarik.php">Lihat Semua &rarr;</a>
                </div>

                <?php if (mysqli_num_rows($query_tarik_terbaru) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($query_tarik_terbaru)): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid #edf2f6;">
                            <div>
                                <b style="font-size:13.5px;"><?= htmlspecialchars($row['nama']) ?></b><br>
                                <span style="font-size:11.5px; color:#818fa1;"><?= date('d-m-Y H:i', strtotime($row['tanggal'])) ?></span>
                            </div>
                            <div style="text-align:right;">
                                <div class="total-cell" style="margin-bottom:4px;"><?= rupiah($row['nominal']) ?></div>
                                <a class="btn btn-success btn-sm" href="proses_verifikasi_tarik.php?id_tarik=<?= (int) $row['id_tarik'] ?>&aksi=setuju">
                                    <i class="fa-solid fa-check"></i> Setujui
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty-state">
                        <i class="fa-solid fa-circle-check"></i>
                        Tidak ada pengajuan yang menunggu.
                    </div>

                <?php endif; ?>

            </div>

        </div>


    </main>

    <script src="assets/tema.js"></script>
</body>

</html>
