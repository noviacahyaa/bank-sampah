<?php

date_default_timezone_set('Asia/Jakarta');

session_start();
include "koneksi.php";

/* Halaman ini khusus role pengepul */
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pengepul') {
    header("Location: index.php");
    exit;
}

$id_user = $_SESSION['id_user'];

/* Ambil data pengepul */
$query_user = mysqli_query(
    $koneksi,
    "SELECT nama, saldo FROM users WHERE id_user = '$id_user'"
);
$user = mysqli_fetch_assoc($query_user);

if (!$user) {
    die("Data pengguna tidak ditemukan.");
}

$nama = $user['nama'];
$saldo_pengepul = (int) $user['saldo'];

/* Stok sampah yang tersedia untuk dibeli */
$query_stok = mysqli_query(
    $koneksi,
    "SELECT id_sampah, jenis_sampah, harga_per_kg, stok_kg
     FROM sampah
     ORDER BY jenis_sampah ASC"
);

/* Ringkasan: total pembelian pengepul ini */
$query_ringkasan = mysqli_query(
    $koneksi,
    "SELECT
        COUNT(*) AS total_transaksi,
        COALESCE(SUM(berat_kg), 0) AS total_berat,
        COALESCE(SUM(total_rp), 0) AS total_bayar
     FROM transaksi
     WHERE id_user = '$id_user' AND tipe = 'jual_pengepul'"
);
$ringkasan = mysqli_fetch_assoc($query_ringkasan);
$total_transaksi = (int) ($ringkasan['total_transaksi'] ?? 0);
$total_berat_beli = (float) ($ringkasan['total_berat'] ?? 0);
$total_bayar = (int) ($ringkasan['total_bayar'] ?? 0);

/* Riwayat pembelian terakhir (5 data) */
$query_riwayat = mysqli_query(
    $koneksi,
    "SELECT t.tanggal, s.jenis_sampah, t.berat_kg, t.total_rp
     FROM transaksi t
     JOIN sampah s ON t.id_sampah = s.id_sampah
     WHERE t.id_user = '$id_user' AND t.tipe = 'jual_pengepul'
     ORDER BY t.tanggal DESC
     LIMIT 5"
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

    <title>Dashboard Pengepul - EcoSave</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        /* SIDEBAR */
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

        /* MAIN */
        .main {
            margin-left: 250px;
            min-height: 100vh;
            padding: 35px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .topbar h1 {
            font-family: "Fredoka", "Poppins", sans-serif;
            color: #361f6b;
            font-size: 26px;
            margin-bottom: 4px;
        }

        .topbar p {
            color: #6c747f;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 10px 16px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #517ab0, #492d8a);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* RINGKASAN CARDS */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }

        .card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.05);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #4a2f89, #492d8a);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .card-title {
            font-size: 12.5px;
            color: #7f858e;
            margin-bottom: 3px;
        }

        .card-value {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 19px;
            font-weight: 600;
            color: #253549;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 22px;
            align-items: start;
        }

        /* STOK BOX */
        .box {
            background: white;
            border-radius: 20px;
            padding: 26px;
            box-shadow: 0 8px 22px rgba(0,0,0,0.05);
            margin-bottom: 22px;
        }

        .box h2 {
            font-family: "Fredoka", "Poppins", sans-serif;
            color: #361f6b;
            font-size: 18px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 12px 10px;
            font-size: 14px;
            border-bottom: 1px solid #eef1f4;
        }

        th {
            color: #7f858e;
            font-size: 12.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stok-rendah {
            color: #c8892f;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            color: #aab0b7;
            padding: 25px 0;
        }

        /* FORM BELI */
        label {
            display: block;
            color: #361f6b;
            font-weight: bold;
            margin-bottom: 6px;
            font-size: 14px;
        }

        select, input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #dae1eb;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            margin-bottom: 16px;
            transition: 0.3s;
            font-family: inherit;
        }

        select:focus, input:focus {
            border-color: #517ab0;
            box-shadow: 0 0 0 3px rgba(81,122,176, 0.12);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #517ab0, #492d8a);
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(73,45,138, 0.28);
        }

        .success {
            background: #ebf1f5;
            border-left: 4px solid #452394;
            color: #402188;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.5;
        }

        .error {
            background: #eff4f8;
            border-left: 4px solid #dc3545;
            color: #c82333;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.5;
        }

        .saldo-rendah {
            color: #c8892f;
        }

        @media (max-width: 1100px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 900px) {
            .grid-2 { grid-template-columns: 1fr; }
            .stats { grid-template-columns: 1fr; }
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

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">
            ♻️ EcoSave
            <span>Portal Pengepul</span>
        </div>

        <div class="menu-title">Menu Utama</div>

        <ul class="menu">
            <li>
                <a href="dashboard_pengepul.php" class="active">
                    <i class="fa-solid fa-house"></i> Beranda
                </a>
            </li>
            <li>
                <a href="riwayat_pengepul.php">
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

    <!-- MAIN -->
    <main class="main">

        <div class="topbar">
            <div>
                <h1>Halo, <?= htmlspecialchars($nama) ?> 👋</h1>
                <p>Catat pembelian sampah dari Bank Sampah EcoSave</p>
            </div>

            <div class="profile">
                <div class="profile-icon"><?= strtoupper(substr($nama, 0, 1)) ?></div>
                <div>
                    <b><?= htmlspecialchars($nama) ?></b><br>
                    <span style="color:#7f858e;">Pengepul</span>
                </div>
            </div>
        </div>

        <!-- SALDO -->
        <div class="saldo-hero">
            <p>
                Saldo Pengepul
                <?php if ($saldo_pengepul <= 0): ?>
                    <span class="chip-warn">Saldo habis, minta admin top up</span>
                <?php endif; ?>
            </p>
            <h2><?= rupiah($saldo_pengepul) ?></h2>
            <div class="actions">
                <a href="#form-beli"><i class="fa-solid fa-truck-ramp-box"></i> Catat Pembelian</a>
                <a href="riwayat_pengepul.php"><i class="fa-solid fa-clock-rotate-left"></i> Lihat Riwayat</a>
            </div>
        </div>

        <!-- RINGKASAN -->
        <div class="stats">

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                <div>
                    <div class="card-title">Total Transaksi</div>
                    <div class="card-value"><?= $total_transaksi ?>x</div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-weight-hanging"></i></div>
                <div>
                    <div class="card-title">Total Sampah Dibeli</div>
                    <div class="card-value"><?= number_format($total_berat_beli, 1, ',', '.') ?> kg</div>
                </div>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fa-solid fa-coins"></i></div>
                <div>
                    <div class="card-title">Total Dibayarkan</div>
                    <div class="card-value"><?= rupiah($total_bayar) ?></div>
                </div>
            </div>

        </div>

        <div class="grid-2">

            <div>

                <!-- STOK SAMPAH -->
                <div class="box">
                    <h2><i class="fa-solid fa-boxes-stacked"></i> Stok Sampah Tersedia</h2>

                    <table>
                        <thead>
                            <tr>
                                <th>Jenis</th>
                                <th>Harga/kg</th>
                                <th>Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($query_stok) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($query_stok)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['jenis_sampah']) ?></td>
                                        <td><?= rupiah($row['harga_per_kg']) ?></td>
                                        <td class="<?= $row['stok_kg'] < 10 ? 'stok-rendah' : '' ?>">
                                            <?= number_format($row['stok_kg'], 1, ',', '.') ?> kg
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3"><div class="empty-state">Belum ada data sampah.</div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- RIWAYAT TERBARU -->
                <div class="box">
                    <h2><i class="fa-solid fa-clipboard-list"></i> Pembelian Terakhir</h2>

                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Berat</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($query_riwayat) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($query_riwayat)): ?>
                                    <tr>
                                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($row['jenis_sampah']) ?></td>
                                        <td><?= number_format($row['berat_kg'], 1, ',', '.') ?> kg</td>
                                        <td><?= rupiah($row['total_rp']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4"><div class="empty-state">Belum ada riwayat pembelian.</div></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div style="margin-top:14px;">
                        <a href="riwayat_pengepul.php" style="color:#492d8a; font-weight:600; text-decoration:none;">
                            Lihat semua riwayat →
                        </a>
                    </div>
                </div>

            </div>

            <!-- FORM CATAT PEMBELIAN -->
            <div class="box">
                <h2><i class="fa-solid fa-truck-ramp-box"></i> Catat Pembelian</h2>

                <?php if (isset($_GET['sukses'])): ?>
                    <div class="success">
                        <i class="fa-solid fa-circle-check"></i> <b>Pembelian berhasil dicatat!</b>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="error">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>

                <p style="font-size:13px; color:#6c747f; margin-bottom:16px;">
                    <i class="fa-solid fa-circle-info"></i>
                    Pembelian akan otomatis dipotong dari saldo kamu (saat ini <b><?= rupiah($saldo_pengepul) ?></b>).
                    Kalau saldo kurang, minta admin untuk top up dulu.
                </p>

                <form action="proses_beli.php" method="POST" id="form-beli">

                    <label for="id_sampah">Jenis Sampah</label>
                    <select name="id_sampah" id="id_sampah" required>
                        <option value="" disabled selected>-- Pilih jenis sampah --</option>
                        <?php
                        mysqli_data_seek($query_stok, 0);
                        while ($row = mysqli_fetch_assoc($query_stok)):
                        ?>
                            <option value="<?= $row['id_sampah'] ?>">
                                <?= htmlspecialchars($row['jenis_sampah']) ?>
                                (<?= rupiah($row['harga_per_kg']) ?>/kg, stok <?= number_format($row['stok_kg'], 1, ',', '.') ?> kg)
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label for="berat_kg">Berat (kg)</label>
                    <input
                        type="number"
                        step="0.1"
                        min="0.1"
                        name="berat_kg"
                        id="berat_kg"
                        placeholder="Contoh: 25"
                        required
                    >

                    <button type="submit" class="submit-btn">
                        <i class="fa-solid fa-check"></i> Simpan Pembelian
                    </button>

                </form>
            </div>

        </div>

    </main>

    <script src="assets/tema.js"></script>
</body>

</html>
