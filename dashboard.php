<?php

date_default_timezone_set('Asia/Jakarta');

session_start();
include "koneksi.php";

/* Cek login nasabah */
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

/* Ambil data user (nama, saldo) */
$query_user = mysqli_query(
    $koneksi,
    "SELECT nama, saldo FROM users WHERE id_user = '$id_user'"
);
$user = mysqli_fetch_assoc($query_user);

if (!$user) {
    die("Data pengguna tidak ditemukan.");
}

$nama  = $user['nama'];
$saldo = (int) $user['saldo'];

/* Statistik: total setoran & total berat sampah */
$query_stat = mysqli_query(
    $koneksi,
    "SELECT
        COUNT(*) AS total_setoran,
        COALESCE(SUM(berat_kg), 0) AS total_berat
     FROM transaksi
     WHERE id_user = '$id_user'"
);
$stat = mysqli_fetch_assoc($query_stat);
$total_setoran = (int) ($stat['total_setoran'] ?? 0);
$total_berat   = (float) ($stat['total_berat'] ?? 0);

/* Estimasi dampak lingkungan (angka ilustratif) */
$co2_terhindar = $total_berat * 1.5;   // kg CO2 setara
$pohon_setara  = $total_berat / 20;    // ilustrasi 1 pohon ~ 20kg sampah terkelola

/* Pengajuan tarik saldo yang masih pending */
$query_pending = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS jumlah, COALESCE(SUM(nominal), 0) AS total
     FROM tarik_saldo
     WHERE id_user = '$id_user' AND status = 'pending'"
);
$pending = mysqli_fetch_assoc($query_pending);
$pending_jumlah = (int) ($pending['jumlah'] ?? 0);
$pending_total  = (int) ($pending['total'] ?? 0);

/* Grafik mini: total berat setoran per bulan, 6 bulan terakhir */
$query_grafik = mysqli_query(
    $koneksi,
    "SELECT
        DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
        COALESCE(SUM(berat_kg), 0) AS berat
     FROM transaksi
     WHERE id_user = '$id_user'
       AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY bulan
     ORDER BY bulan ASC"
);

$grafik_data = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i month"));
    $grafik_data[$key] = 0;
}
while ($row = mysqli_fetch_assoc($query_grafik)) {
    if (isset($grafik_data[$row['bulan']])) {
        $grafik_data[$row['bulan']] = (float) $row['berat'];
    }
}
$grafik_max = max(array_merge(array_values($grafik_data), [1]));

/* Riwayat setoran terakhir (5 data terbaru) */
$query_riwayat = mysqli_query(
    $koneksi,
    "SELECT
        t.tanggal,
        s.jenis_sampah,
        t.berat_kg,
        t.total_rp
     FROM transaksi t
     JOIN sampah s ON t.id_sampah = s.id_sampah
     WHERE t.id_user = '$id_user'
     ORDER BY t.tanggal DESC
     LIMIT 5"
);

/* Sapaan berdasarkan jam */
$jam = (int) date('H');
if ($jam >= 4 && $jam < 11) {
    $sapaan = "Selamat pagi";
    $sapaan_icon = "☀️";
} elseif ($jam >= 11 && $jam < 15) {
    $sapaan = "Selamat siang";
    $sapaan_icon = "🌤️";
} elseif ($jam >= 15 && $jam < 18) {
    $sapaan = "Selamat sore";
    $sapaan_icon = "🌇";
} else {
    $sapaan = "Selamat malam";
    $sapaan_icon = "🌙";
}

function rupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

/* Tingkatan pencapaian berdasarkan total berat sampah (murni tampilan) */
$tingkatan = [
    ['batas' => 0,  'label' => 'Pemula Hijau',      'icon' => 'fa-seedling'],
    ['batas' => 5,  'label' => 'Peduli Lingkungan', 'icon' => 'fa-leaf'],
    ['batas' => 20, 'label' => 'Pahlawan Sampah',   'icon' => 'fa-tree'],
    ['batas' => 50, 'label' => 'Juara EcoSave',     'icon' => 'fa-trophy'],
];

$tingkat_sekarang = $tingkatan[0];
$tingkat_berikut  = null;

foreach ($tingkatan as $i => $t) {
    if ($total_berat >= $t['batas']) {
        $tingkat_sekarang = $t;
        $tingkat_berikut  = $tingkatan[$i + 1] ?? null;
    }
}

if ($tingkat_berikut) {
    $progress_persen = min(
        100,
        (($total_berat - $tingkat_sekarang['batas']) / ($tingkat_berikut['batas'] - $tingkat_sekarang['batas'])) * 100
    );
    $sisa_kg = $tingkat_berikut['batas'] - $total_berat;
} else {
    $progress_persen = 100;
    $sisa_kg = 0;
}

$nama_bulan_pendek = [
    '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
    '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu',
    '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des',
];

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - EcoSave</title>

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
            background: linear-gradient(135deg, #f5f8fa, #ecf2f6);
            color: #333f4e;
        }

        /* SIDEBAR */

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .main > * {
            animation: fadeUp 0.45s ease both;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            padding: 30px 20px;
            background: linear-gradient(180deg, #6434d3, #421c9b);
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

        /* BADGE PENCAPAIAN */

        .badge-card {
            background: linear-gradient(135deg, #f8fafb, #eff4f8);
            border: 1px solid #e1eaf1;
            border-radius: 18px;
            padding: 18px 20px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .badge-icon {
            width: 52px;
            height: 52px;
            border-radius: 15px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            color: white;
            background: linear-gradient(135deg, #6434d3, #421c9b);
            box-shadow: 0 8px 18px rgba(66,28,155, 0.3);
        }

        .badge-info {
            flex: 1;
            min-width: 0;
        }

        .badge-title {
            font-size: 11.5px;
            color: #7b8ba1;
            margin-bottom: 2px;
        }

        .badge-name {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 17px;
            font-weight: 600;
            color: #3a5579;
            margin-bottom: 8px;
        }

        .badge-track {
            width: 100%;
            height: 7px;
            background: #e7eef3;
            border-radius: 10px;
            overflow: hidden;
        }

        .badge-fill {
            height: 100%;
            background: linear-gradient(90deg, #6434d3, #421c9b);
            border-radius: 10px;
        }

        .badge-hint {
            font-size: 11px;
            color: #7b8ba1;
            margin-top: 6px;
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
        }

        .topbar h1 {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 25px;
            font-weight: 600;
            color: #3a5579;
        }

        .topbar p {
            color: #7a8696;
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
            box-shadow: 0 7px 20px rgba(52,82,121, 0.1);
            font-size: 13px;
        }

        .profile-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6434d3, #421c9b);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* NOTIFIKASI PENDING */

        .notif {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #fff8e6;
            border: 1px solid #ffe1a8;
            color: #9a7200;
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 13.5px;
        }

        .notif i {
            font-size: 20px;
            color: #e0a300;
        }

        .notif b { font-weight: 700; }

        /* SALDO HERO */

        .saldo-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #6434d3, #421c9b);
            color: white;
            padding: 28px 30px;
            border-radius: 20px;
            margin-bottom: 22px;
            box-shadow: 0 12px 30px rgba(66,28,155, 0.28);
        }

        .saldo-hero::after {
            content: "♻️";
            position: absolute;
            right: 20px;
            bottom: -10px;
            font-size: 100px;
            opacity: 0.15;
        }

        .saldo-hero p {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .saldo-hero h2 {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 33px;
            font-weight: 600;
            margin-bottom: 18px;
            letter-spacing: 0.2px;
        }

        .saldo-hero .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .saldo-hero .actions a {
            text-decoration: none;
            background: rgba(255,255,255, 0.2);
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: 0.25s;
        }

        .saldo-hero .actions a:hover {
            background: rgba(255,255,255, 0.32);
            transform: translateY(-2px);
        }

        /* GRID LAYOUT */

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 22px;
        }

        /* STAT CARDS */

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }

        .card {
            position: relative;
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 22px rgba(57,87,126, 0.08);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: 0.25s;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 13px 28px rgba(57,87,126, 0.13);
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

        .stats .card:nth-child(1) .card-icon {
            background: #ecf2f6;
            color: #421c9b;
        }

        .stats .card:nth-child(2) .card-icon {
            background: #ffe9d9;
            color: #d97a3e;
        }

        .stats .card:nth-child(3) .card-icon {
            background: #fff3d6;
            color: #c99420;
        }

        .card-title {
            font-size: 12px;
            color: #7a8696;
            margin-bottom: 4px;
        }

        .card-value {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: #2b3f5a;
        }

        /* DAMPAK LINGKUNGAN */

        .impact-box {
            background: linear-gradient(135deg, #ebf1f5, #f2f6f9);
            border: 1px solid #cdeee3;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 22px;
        }

        .impact-box h2 {
            font-size: 16px;
            color: #402188;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .impact-items {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .impact-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .impact-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #402188;
            flex-shrink: 0;
        }

        .impact-value {
            font-size: 17px;
            font-weight: 700;
            color: #402188;
        }

        .impact-label {
            font-size: 11.5px;
            color: #5f7899;
        }

        /* GRAFIK MINI */

        .chart-box {
            background: white;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 8px 22px rgba(57,87,126, 0.08);
        }

        .chart-box h2 {
            font-size: 15px;
            color: #3a5579;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 10px;
            height: 130px;
            padding: 0 4px;
        }

        .chart-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .chart-bar-track {
            width: 100%;
            height: 95px;
            display: flex;
            align-items: flex-end;
        }

        .chart-bar {
            width: 100%;
            background: linear-gradient(180deg, #c7fae9, #421c9b);
            border-radius: 8px 8px 4px 4px;
            min-height: 4px;
            transition: 0.3s;
        }

        .chart-label {
            font-size: 11px;
            color: #7a8696;
            font-weight: 600;
        }

        /* TABLE BOX */

        .table-box {
            background: white;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 8px 22px rgba(57,87,126, 0.08);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .table-header h2 {
            font-size: 17px;
            color: #3a5579;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header a {
            font-size: 13px;
            font-weight: 600;
            color: #421c9b;
            text-decoration: none;
        }

        .table-header a:hover {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f4f7fa;
            color: #3f608a;
            text-align: left;
            padding: 12px 14px;
            font-size: 12.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        th:first-child { border-radius: 10px 0 0 10px; }
        th:last-child  { border-radius: 0 10px 10px 0; }

        td {
            padding: 13px 14px;
            border-bottom: 1px solid #e6edf2;
            font-size: 14px;
        }

        .total-cell {
            color: #6434d3;
            font-weight: 700;
        }

        .empty-state {
            text-align: center;
            padding: 40px 10px;
            color: #aab4c1;
        }

        .empty-state i {
            font-size: 34px;
            margin-bottom: 10px;
            display: block;
        }

        /* RESPONSIVE */

        @media (max-width: 1000px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
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

            .stats {
                grid-template-columns: 1fr;
            }

            .impact-items {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 550px) {

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .saldo-hero .actions {
                flex-direction: column;
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
            ♻️ EcoSave
            <span>Bank Sampah</span>
        </div>

        <div class="menu-title">Menu Utama</div>

        <ul class="menu">

            <li>
                <a href="dashboard.php" class="active">
                    <i class="fa-solid fa-house"></i>
                    <span>Beranda</span>
                </a>
            </li>

            <li>
                <a href="tarik_saldo.php">
                    <i class="fa-solid fa-wallet"></i>
                    <span>Tarik Saldo</span>
                </a>
            </li>

            <li>
                <a href="riwayat.php">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Riwayat</span>
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
            ♻️ EcoSave<br>
            Sistem Informasi Bank Sampah
        </div>

    </aside>

    <!-- MAIN -->
    <main class="main">

        <div class="topbar">

            <div>
                <h1><?= $sapaan_icon ?> <?= $sapaan ?>, <?= htmlspecialchars($nama) ?></h1>
                <p>Selamat datang kembali di EcoSave</p>
            </div>

            <div class="profile">
                <div class="profile-icon"><?= strtoupper(substr($nama, 0, 1)) ?></div>
                <div>
                    <b><?= htmlspecialchars($nama) ?></b><br>
                    <span style="color:#7a8696;"><?= $_SESSION['role'] === 'guru' ? 'Guru' : 'Siswa' ?></span>
                </div>
            </div>

        </div>

        <!-- BADGE PENCAPAIAN -->
        <div class="badge-card">

            <div class="badge-icon">
                <i class="fa-solid <?= $tingkat_sekarang['icon'] ?>"></i>
            </div>

            <div class="badge-info">
                <div class="badge-title">Tingkat Pencapaianmu</div>
                <div class="badge-name"><?= $tingkat_sekarang['label'] ?></div>

                <div class="badge-track">
                    <div class="badge-fill" style="width: <?= $progress_persen ?>%;"></div>
                </div>

                <?php if ($tingkat_berikut): ?>
                    <div class="badge-hint">
                        <?= number_format($sisa_kg, 1, ',', '.') ?> kg lagi menuju
                        "<?= $tingkat_berikut['label'] ?>"
                    </div>
                <?php else: ?>
                    <div class="badge-hint">Tingkat tertinggi tercapai — terus pertahankan! 🎉</div>
                <?php endif; ?>
            </div>

        </div>

        <!-- NOTIFIKASI PENDING -->
        <?php if ($pending_jumlah > 0): ?>
        <div class="notif">
            <i class="fa-solid fa-hourglass-half"></i>
            <div>
                Kamu punya <b><?= $pending_jumlah ?> pengajuan tarik saldo</b>
                senilai <b><?= rupiah($pending_total) ?></b> yang masih menunggu diproses admin.
            </div>
        </div>
        <?php endif; ?>

        <!-- SALDO -->
        <div class="saldo-hero">
            <p>Saldo tabungan Anda saat ini</p>
            <h2><?= rupiah($saldo) ?></h2>

            <div class="actions">
                <a href="setor_sampah.php"><i class="fa-solid fa-recycle"></i> Setor Sampah</a>
                <a href="tarik_saldo.php"><i class="fa-solid fa-money-bill-transfer"></i> Tarik Saldo</a>
                <a href="riwayat.php"><i class="fa-solid fa-clock-rotate-left"></i> Lihat Riwayat</a>
            </div>
        </div>

        <!-- STAT -->
        <div class="stats">

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-recycle"></i></div>
                <div>
                    <div class="card-title">Total Setoran</div>
                    <div class="card-value"><?= $total_setoran ?>x</div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-weight-hanging"></i></div>
                <div>
                    <div class="card-title">Total Berat Sampah</div>
                    <div class="card-value"><?= number_format($total_berat, 1, ',', '.') ?> kg</div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="card-title">Pengajuan Pending</div>
                    <div class="card-value"><?= $pending_jumlah ?></div>
                </div>
            </div>

        </div>

        <!-- DAMPAK LINGKUNGAN -->
        <div class="impact-box">

            <h2><i class="fa-solid fa-leaf"></i> Dampak Lingkunganmu</h2>

            <div class="impact-items">

                <div class="impact-item">
                    <div class="impact-icon"><i class="fa-solid fa-cloud"></i></div>
                    <div>
                        <div class="impact-value"><?= number_format($co2_terhindar, 1, ',', '.') ?> kg</div>
                        <div class="impact-label">Estimasi emisi CO₂ terhindar</div>
                    </div>
                </div>

                <div class="impact-item">
                    <div class="impact-icon"><i class="fa-solid fa-tree"></i></div>
                    <div>
                        <div class="impact-value"><?= number_format($pohon_setara, 2, ',', '.') ?></div>
                        <div class="impact-label">Setara pohon yang terselamatkan</div>
                    </div>
                </div>

            </div>

        </div>

        <div class="grid-2">

            <!-- GRAFIK MINI -->
            <div class="chart-box">

                <h2><i class="fa-solid fa-chart-column"></i> Setoran 6 Bulan Terakhir</h2>

                <div class="chart-bars">

                    <?php foreach ($grafik_data as $bulan => $berat): ?>
                        <?php
                            $tinggi = $grafik_max > 0 ? max(6, ($berat / $grafik_max) * 95) : 6;
                            list($tahun, $bln) = explode('-', $bulan);
                        ?>
                        <div class="chart-col">
                            <div class="chart-bar-track">
                                <div class="chart-bar" style="height: <?= $tinggi ?>px;" title="<?= number_format($berat, 1, ',', '.') ?> kg"></div>
                            </div>
                            <div class="chart-label"><?= $nama_bulan_pendek[$bln] ?? $bln ?></div>
                        </div>
                    <?php endforeach; ?>

                </div>

            </div>

            <!-- RIWAYAT TERBARU -->
            <div class="table-box">

                <div class="table-header">
                    <h2><i class="fa-solid fa-clipboard-list"></i> Setoran Terakhir</h2>
                    <a href="riwayat.php">Lihat Semua →</a>
                </div>

                <table>

                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (mysqli_num_rows($query_riwayat) > 0): ?>

                        <?php while ($row = mysqli_fetch_assoc($query_riwayat)): ?>

                            <tr>
                                <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($row['jenis_sampah']) ?></td>
                                <td class="total-cell"><?= rupiah($row['total_rp']) ?></td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="3">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i>
                                    Belum ada riwayat setoran.
                                </div>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

    <script src="assets/tema.js"></script>
</body>

</html>