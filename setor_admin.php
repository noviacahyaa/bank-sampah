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

/* Daftar nasabah (siswa & guru) untuk dropdown */
$query_nasabah = mysqli_query(
    $koneksi,
    "SELECT id_user, nama, username, role, saldo FROM users
     WHERE role IN ('siswa','guru')
     ORDER BY nama ASC"
);

/* Daftar jenis sampah untuk dropdown */
$query_sampah = mysqli_query(
    $koneksi,
    "SELECT id_sampah, jenis_sampah, harga_per_kg, stok_kg FROM sampah ORDER BY jenis_sampah ASC"
);
$daftar_sampah = [];
while ($row = mysqli_fetch_assoc($query_sampah)) {
    $daftar_sampah[] = $row;
}

/* Setoran terbaru yang dicatat admin */
$query_riwayat = mysqli_query(
    $koneksi,
    "SELECT t.tanggal, t.berat_kg, t.total_rp, u.nama, s.jenis_sampah
     FROM transaksi t
     JOIN users u ON t.id_user = u.id_user
     JOIN sampah s ON t.id_sampah = s.id_sampah
     WHERE t.tipe = 'setor'
     ORDER BY t.tanggal DESC
     LIMIT 8"
);

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Catat Setoran - EcoSave</title>

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
                <a href="dashboard_admin.php" class="">
                    <i class="fa-solid fa-house"></i>
                    <span>Beranda</span>
                </a>
            </li>

            <li>
                <a href="setor_admin.php" class="active">
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
                <h1>💰 Catat Setoran Nasabah</h1>
                <p>Input setoran sampah siswa/guru untuk menambah saldo & stok</p>
            </div>

            <div class="profile">
                <div class="profile-icon"><?= strtoupper(substr($nama_admin, 0, 1)) ?></div>
                <div>
                    <b><?= htmlspecialchars($nama_admin) ?></b><br>
                    <span style="color:#818fa1;">Admin</span>
                </div>
            </div>

        </div>


        <?php if (isset($_GET['sukses'])): ?>
        <div class="alert alert-sukses">
            <i class="fa-solid fa-circle-check"></i>
            <div>Setoran berhasil dicatat. Saldo nasabah &amp; stok sampah telah diperbarui.</div>
        </div>
        <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-xmark"></i>
            <div><?= htmlspecialchars($_GET['error']) ?></div>
        </div>
        <?php endif; ?>

        <div class="grid-2">

            <!-- FORM CATAT SETORAN -->
            <div class="box">

                <div class="box-header">
                    <h2><i class="fa-solid fa-hand-holding-dollar"></i> Form Setoran Nasabah</h2>
                </div>

                <?php if (mysqli_num_rows($query_nasabah) === 0 || count($daftar_sampah) === 0): ?>

                    <div class="empty-state">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <?= mysqli_num_rows($query_nasabah) === 0 ? 'Belum ada akun siswa/guru terdaftar.' : 'Belum ada data jenis sampah. Tambahkan dulu di menu Kelola Sampah.' ?>
                    </div>

                <?php else: ?>

                <form action="proses_setor_admin.php" method="POST">

                    <div class="form-grid">

                        <div class="form-group full">
                            <label>Nasabah (Siswa / Guru)</label>
                            <select name="id_user" required>
                                <option value="">-- Pilih nasabah --</option>
                                <?php mysqli_data_seek($query_nasabah, 0); ?>
                                <?php while ($n = mysqli_fetch_assoc($query_nasabah)): ?>
                                    <option value="<?= $n['id_user'] ?>">
                                        <?= htmlspecialchars($n['nama']) ?> (<?= htmlspecialchars($n['username']) ?>) &mdash; <?= $n['role'] === 'guru' ? 'Guru' : 'Siswa' ?>, saldo <?= rupiah($n['saldo']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Jenis Sampah</label>
                            <select name="id_sampah" id="id_sampah" required onchange="hitungTotal()">
                                <option value="">-- Pilih jenis --</option>
                                <?php foreach ($daftar_sampah as $s): ?>
                                    <option value="<?= $s['id_sampah'] ?>" data-harga="<?= $s['harga_per_kg'] ?>">
                                        <?= htmlspecialchars($s['jenis_sampah']) ?> (<?= rupiah($s['harga_per_kg']) ?>/kg)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Berat (kg)</label>
                            <input type="number" step="0.1" min="0.1" name="berat_kg" id="berat_kg" placeholder="Contoh: 2.5" required onkeyup="hitungTotal()" oninput="hitungTotal()">
                        </div>

                        <div class="form-group full">
                            <div class="hint-box" id="hasilHitung">
                                <i class="fa-solid fa-calculator"></i> Total setoran akan dihitung otomatis (berat &times; harga per kg).
                            </div>
                        </div>

                        <div class="form-group full">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Setoran
                            </button>
                        </div>

                    </div>

                </form>

                <?php endif; ?>

            </div>

            <!-- SETORAN TERBARU -->
            <div class="table-box">

                <div class="table-header">
                    <h2><i class="fa-solid fa-clock-rotate-left"></i> Setoran Terbaru</h2>
                </div>

                <?php if (mysqli_num_rows($query_riwayat) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($query_riwayat)): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:11px 0; border-bottom:1px solid #edf2f6;">
                            <div>
                                <b style="font-size:13px;"><?= htmlspecialchars($row['nama']) ?></b><br>
                                <span style="font-size:11.5px; color:#818fa1;">
                                    <?= htmlspecialchars($row['jenis_sampah']) ?> &middot; <?= number_format($row['berat_kg'], 1, ',', '.') ?> kg &middot; <?= date('d-m-Y', strtotime($row['tanggal'])) ?>
                                </span>
                            </div>
                            <div class="total-cell"><?= rupiah($row['total_rp']) ?></div>
                        </div>
                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        Belum ada setoran tercatat.
                    </div>

                <?php endif; ?>

            </div>

        </div>

        <script>
            function hitungTotal() {
                const sel = document.getElementById('id_sampah');
                const berat = parseFloat(document.getElementById('berat_kg').value) || 0;
                const opt = sel.options[sel.selectedIndex];
                const harga = opt ? parseFloat(opt.getAttribute('data-harga')) || 0 : 0;
                const total = berat * harga;
                const box = document.getElementById('hasilHitung');

                if (harga > 0 && berat > 0) {
                    box.innerHTML = '<i class="fa-solid fa-calculator"></i> Total: <b>Rp ' +
                        total.toLocaleString('id-ID') + '</b> (' + berat + ' kg &times; Rp ' + harga.toLocaleString('id-ID') + '/kg)';
                } else {
                    box.innerHTML = '<i class="fa-solid fa-calculator"></i> Total setoran akan dihitung otomatis (berat &times; harga per kg).';
                }
            }
        </script>


    </main>

    <script src="assets/tema.js"></script>
</body>

</html>
