<?php

session_start();
include "koneksi.php";

/* Halaman ini khusus role pengepul */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pengepul') {
    header("Location: index.php");
    exit;
}

$id_user = $_SESSION['id_user'];

$query_user = mysqli_query(
    $koneksi,
    "SELECT saldo FROM users WHERE id_user = '$id_user'"
);
$saldo_pengepul = (int) (mysqli_fetch_assoc($query_user)['saldo'] ?? 0);

$query_riwayat = mysqli_query(
    $koneksi,
    "SELECT t.tanggal, s.jenis_sampah, t.berat_kg, t.total_rp
     FROM transaksi t
     JOIN sampah s ON t.id_sampah = s.id_sampah
     WHERE t.id_user = '$id_user' AND t.tipe = 'jual_pengepul'
     ORDER BY t.tanggal DESC"
);

$query_topup = mysqli_query(
    $koneksi,
    "SELECT nominal, tanggal FROM topup_pengepul
     WHERE id_user = '$id_user'
     ORDER BY tanggal DESC"
);

function rupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Pembelian - EcoSave</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f5f8, #e5edf2);
            color: #30353c;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            padding: 30px 20px;
            background: linear-gradient(180deg, #517ab0, #492d8a);
            color: white;
            box-shadow: 4px 0 15px rgba(0,0,0, 0.08);
        }

        .logo {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 22px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 8px;
        }

        .logo span {
            display: block;
            font-size: 12px;
            font-weight: 400;
            opacity: 0.85;
            margin-top: 4px;
        }

        .menu-title {
            font-size: 13px;
            opacity: 0.8;
            margin: 32px 0 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .menu {
            list-style: none;
        }

        .menu li {
            margin: 8px 0;
        }

        .menu a {
            display: block;
            padding: 13px 15px;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            transition: 0.3s;
        }

        .menu a i {
            width: 20px;
            margin-right: 8px;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(255,255,255, 0.2);
            transform: translateX(5px);
        }

        .main {
            margin-left: 250px;
            min-height: 100vh;
            padding: 35px;
        }

        .header h1 {
            font-family: "Fredoka", "Poppins", sans-serif;
            color: #361f6b;
            font-size: 26px;
            margin-bottom: 5px;
        }

        .header p {
            color: #6c747f;
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 20px;
            color: #492d8a;
            text-decoration: none;
            font-weight: 600;
        }

        .box {
            background: white;
            border-radius: 20px;
            padding: 26px;
            box-shadow: 0 8px 22px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 14px 10px;
            font-size: 14px;
            border-bottom: 1px solid #eef1f4;
        }

        th {
            color: #7f858e;
            font-size: 12.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .total-cell {
            color: #361f6b;
            font-weight: 700;
        }

        .empty-state {
            text-align: center;
            color: #aab0b7;
            padding: 30px 0;
        }

        @media (max-width: 768px) {
            .sidebar { position: relative; width: 100%; height: auto; padding: 20px; }
            .main { margin-left: 0; padding: 20px; }
        }

    </style>


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/tema.css">
    <script>try{if(localStorage.getItem('ecosave_tema')==='gelap')document.documentElement.setAttribute('data-tema','gelap')}catch(e){}</script>
</head>

<body>

    <aside class="sidebar">

        <div class="logo">
            ♻️ EcoSave
            <span>Portal Pengepul</span>
        </div>

        <div class="menu-title">Menu Utama</div>

        <ul class="menu">
            <li>
                <a href="dashboard_pengepul.php">
                    <i class="fa-solid fa-house"></i> Beranda
                </a>
            </li>
            <li>
                <a href="riwayat_pengepul.php" class="active">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pembelian
                </a>
            </li>
            <li>
                <a href="setor_sampah.php">
                    <i class="fa-solid fa-recycle"></i> Setor Sampah
                </a>
            </li>

            <li>
                <a href="logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i> Keluar
                </a>
            </li>
        </ul>

    </aside>

    <main class="main">

        <div class="header">
            <h1>Riwayat Pembelian</h1>
            <p>Seluruh transaksi pembelian sampah yang telah kamu catat &middot; Saldo saat ini <b><?= rupiah($saldo_pengepul) ?></b></p>
        </div>

        <a href="dashboard_pengepul.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>

        <div class="box" style="margin-bottom:22px;">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis Sampah</th>
                        <th>Berat</th>
                        <th>Total Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query_riwayat) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($query_riwayat)): ?>
                            <tr>
                                <td><?= date('d-m-Y H:i', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($row['jenis_sampah']) ?></td>
                                <td><?= number_format($row['berat_kg'], 1, ',', '.') ?> kg</td>
                                <td class="total-cell"><?= rupiah($row['total_rp']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i><br>
                                    Belum ada riwayat pembelian.
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="header">
            <h1 style="font-size:20px;">Riwayat Top Up Saldo</h1>
            <p>Saldo yang diberikan admin untuk kamu pakai membeli sampah</p>
        </div>

        <div class="box">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Nominal Top Up</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query_topup) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($query_topup)): ?>
                            <tr>
                                <td><?= date('d-m-Y H:i', strtotime($row['tanggal'])) ?></td>
                                <td class="total-cell"><?= rupiah($row['nominal']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i><br>
                                    Belum ada top up tercatat.
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <script src="assets/tema.js"></script>
</body>

</html>
