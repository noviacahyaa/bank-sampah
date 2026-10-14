<?php

date_default_timezone_set('Asia/Jakarta');
session_start();
include "koneksi.php";

/* Semua role boleh membuka halaman ini */
$role_valid = ['siswa', 'guru', 'pengepul', 'admin'];

if (!isset($_SESSION['id_user']) || !in_array($_SESSION['role'], $role_valid, true)) {
    header("Location: index.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$role    = $_SESSION['role'];

function rupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

/* Data akun */
$q_user = mysqli_query($koneksi, "SELECT nama, saldo FROM users WHERE id_user = '$id_user'");
$user = mysqli_fetch_assoc($q_user);
if (!$user) {
    die("Data pengguna tidak ditemukan.");
}
$nama  = $user['nama'];
$saldo = (int) $user['saldo'];

/* Tema & menu per role */
$tema = [
    'siswa'    => ['c1' => '#6434d3', 'c2' => '#421c9b', 'bg1' => '#f5f8fa', 'bg2' => '#ecf2f6', 'soft' => '#f8fafb', 'line' => '#e5edf3', 'text' => '#333f4e', 'muted' => '#7b8ba1', 'label' => 'Siswa'],
    'guru'     => ['c1' => '#6434d3', 'c2' => '#421c9b', 'bg1' => '#f5f8fa', 'bg2' => '#ecf2f6', 'soft' => '#f8fafb', 'line' => '#e5edf3', 'text' => '#333f4e', 'muted' => '#7b8ba1', 'label' => 'Guru'],
    'pengepul' => ['c1' => '#517ab0', 'c2' => '#492d8a', 'bg1' => '#f0f5f8', 'bg2' => '#e5edf2', 'soft' => '#f4f7fa', 'line' => '#cfeee4', 'text' => '#30353c', 'muted' => '#7f858e', 'label' => 'Pengepul'],
    'admin'    => ['c1' => '#6df0c4', 'c2' => '#47288f', 'bg1' => '#f7f9fb', 'bg2' => '#ecf2f6', 'soft' => '#f4f7fa', 'line' => '#e0e9f0', 'text' => '#314054', 'muted' => '#818fa1', 'label' => 'Admin'],
];
$t = $tema[$role];

$menu = [
    'siswa' => [
        ['dashboard.php',   'fa-house',                 'Beranda'],
        ['setor_sampah.php','fa-recycle',               'Setor Sampah'],
        ['tarik_saldo.php', 'fa-wallet',                'Tarik Saldo'],
        ['riwayat.php',     'fa-clock-rotate-left',     'Riwayat'],
        ['logout.php',      'fa-right-from-bracket',    'Keluar'],
    ],
    'pengepul' => [
        ['dashboard_pengepul.php', 'fa-house',             'Beranda'],
        ['setor_sampah.php',       'fa-recycle',           'Setor Sampah'],
        ['riwayat_pengepul.php',   'fa-clock-rotate-left', 'Riwayat Pembelian'],
        ['logout.php',             'fa-right-from-bracket','Keluar'],
    ],
    'admin' => [
        ['dashboard_admin.php',  'fa-house',                  'Beranda'],
        ['setor_sampah.php',     'fa-recycle',                'Setor Sampah'],
        ['setor_admin.php',      'fa-hand-holding-dollar',    'Catat Setoran'],
        ['topup_pengepul.php',   'fa-wallet',                 'Top Up Pengepul'],
        ['verifikasi_tarik.php', 'fa-money-bill-transfer',    'Verifikasi Tarik Saldo'],
        ['kelola_sampah.php',    'fa-trash-can-arrow-up',     'Kelola Sampah'],
        ['kelola_user.php',      'fa-users',                  'Kelola Pengguna'],
        ['riwayat_admin.php',    'fa-clock-rotate-left',      'Riwayat Transaksi'],
        ['logout.php',           'fa-right-from-bracket',     'Keluar'],
    ],
];
$menu['guru'] = $menu['siswa'];
$menu_role = $menu[$role];

$subtitle = [
    'siswa'    => 'Portal Siswa',
    'guru'     => 'Portal Guru',
    'pengepul' => 'Portal Pengepul',
    'admin'    => 'Panel Admin',
][$role];

/* Daftar jenis sampah */
$q_sampah = mysqli_query($koneksi, "SELECT id_sampah, jenis_sampah, harga_per_kg FROM sampah ORDER BY jenis_sampah ASC");
$daftar_sampah = [];
while ($row = mysqli_fetch_assoc($q_sampah)) {
    $daftar_sampah[] = $row;
}

/* Setoran terakhir milik akun ini */
$q_riwayat = mysqli_query(
    $koneksi,
    "SELECT t.tanggal, t.berat_kg, t.total_rp, s.jenis_sampah
     FROM transaksi t
     JOIN sampah s ON t.id_sampah = s.id_sampah
     WHERE t.id_user = '$id_user' AND t.tipe = 'setor'
     ORDER BY t.tanggal DESC
     LIMIT 8"
);

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Setor Sampah - EcoSave</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        :root {
            --c1: <?= $t['c1'] ?>;
            --c2: <?= $t['c2'] ?>;
            --bg1: <?= $t['bg1'] ?>;
            --bg2: <?= $t['bg2'] ?>;
            --soft: <?= $t['soft'] ?>;
            --line: <?= $t['line'] ?>;
            --text: <?= $t['text'] ?>;
            --muted: <?= $t['muted'] ?>;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg1), var(--bg2));
            color: var(--text);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .main > * { animation: fadeUp 0.45s ease both; }

        /* SIDEBAR */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            padding: 30px 20px;
            background: linear-gradient(180deg, var(--c1), var(--c2));
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

        .sidebar > * { position: relative; z-index: 1; }

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

        .menu { list-style: none; margin-bottom: 26px; }
        .menu li { margin: 6px 0; }

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

        .menu a i { width: 18px; text-align: center; }

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
            color: var(--c2);
        }

        .topbar p {
            color: var(--muted);
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
            box-shadow: 0 7px 20px rgba(0,0,0, 0.07);
            font-size: 13px;
        }

        .profile-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--c1), var(--c2));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* ALERT */

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

        .alert-sukses { background: #ecf2f6; border: 1px solid #b9ecdb; color: #3e1e8a; }
        .alert-error  { background: #f5f8fa; border: 1px solid #cdfbec; color: #48298f; }

        /* LAYOUT */

        .grid-2 {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 18px;
            align-items: start;
        }

        .box {
            background: white;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 8px 22px rgba(0,0,0, 0.06);
        }

        .box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .box-header h2 {
            font-size: 17px;
            color: var(--c2);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .saldo-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--soft);
            border: 1px dashed var(--line);
            color: var(--c2);
            font-size: 13px;
            padding: 10px 14px;
            border-radius: 12px;
            margin-bottom: 18px;
        }

        /* FORM */

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .form-group.full { grid-column: 1 / -1; }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
            color: var(--c2);
        }

        input, select {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--line);
            border-radius: 12px;
            outline: none;
            background: var(--soft);
            color: var(--text);
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: 0.25s;
        }

        input:focus, select:focus {
            border-color: var(--c1);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(0,0,0, 0.05);
        }

        .hint-box {
            background: var(--soft);
            border: 1px dashed var(--line);
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 13px;
            color: var(--c2);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: none;
            cursor: pointer;
            padding: 12px 22px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--c1), var(--c2));
            color: white;
            box-shadow: 0 10px 20px rgba(0,0,0, 0.12);
            transition: 0.2s;
        }

        .btn:hover { transform: translateY(-2px); }

        /* RIWAYAT */

        .riwayat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 11px 0;
            border-bottom: 1px solid var(--line);
        }

        .riwayat-item small { font-size: 11.5px; color: var(--muted); }
        .riwayat-total { color: var(--c2); font-weight: 700; font-size: 13.5px; }

        .empty-state {
            text-align: center;
            padding: 40px 10px;
            color: var(--muted);
            opacity: 0.8;
        }

        .empty-state i { font-size: 34px; margin-bottom: 10px; display: block; }

        /* RESPONSIVE */

        @media (max-width: 1050px) {
            .grid-2 { grid-template-columns: 1fr; }
        }

        @media (max-width: 850px) {
            .sidebar { position: relative; width: 100%; height: auto; padding: 20px; }
            .menu { display: flex; flex-wrap: wrap; gap: 6px; }
            .menu a span { display: none; }
            .sidebar-footer { display: none; }
            .main { margin-left: 0; padding: 22px; }
            .form-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 550px) {
            .topbar { flex-direction: column; align-items: flex-start; }
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
            <span><?= $subtitle ?></span>
        </div>

        <div class="menu-title">Menu Utama</div>

        <ul class="menu">
            <?php foreach ($menu_role as $m): ?>
                <li>
                    <a href="<?= $m[0] ?>" class="<?= $m[0] === 'setor_sampah.php' ? 'active' : '' ?>">
                        <i class="fa-solid <?= $m[1] ?>"></i>
                        <span><?= $m[2] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
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
                <h1>♻️ Setor Sampah</h1>
                <p>Setorkan sampahmu, saldo dan stok bank sampah langsung bertambah</p>
            </div>

            <div class="profile">
                <div class="profile-icon"><?= strtoupper(substr($nama, 0, 1)) ?></div>
                <div>
                    <b><?= htmlspecialchars($nama) ?></b><br>
                    <span style="color:var(--muted);"><?= $t['label'] ?></span>
                </div>
            </div>

        </div>

        <?php if (isset($_GET['sukses'])): ?>
        <div class="alert alert-sukses">
            <i class="fa-solid fa-circle-check"></i>
            <div>Setoran berhasil disimpan. Saldo kamu sudah bertambah.</div>
        </div>
        <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-xmark"></i>
            <div><?= htmlspecialchars($_GET['error']) ?></div>
        </div>
        <?php endif; ?>

        <div class="grid-2">

            <!-- FORM SETOR -->
            <div class="box">

                <div class="box-header">
                    <h2><i class="fa-solid fa-recycle"></i> Form Setor Sampah</h2>
                </div>

                <div class="saldo-chip">
                    <i class="fa-solid fa-wallet"></i>
                    Saldo kamu saat ini: <b><?= rupiah($saldo) ?></b>
                </div>

                <?php if (count($daftar_sampah) === 0): ?>

                    <div class="empty-state">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Belum ada jenis sampah. Hubungi admin untuk menambahkannya.
                    </div>

                <?php else: ?>

                <form action="proses_setor.php" method="POST">

                    <div class="form-grid">

                        <div class="form-group">
                            <label for="id_sampah">Jenis Sampah</label>
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
                            <label for="berat_kg">Berat (kg)</label>
                            <input type="number" step="0.1" min="0.1" max="1000" name="berat_kg" id="berat_kg" placeholder="Contoh: 2.5" required oninput="hitungTotal()">
                        </div>

                        <div class="form-group full">
                            <div class="hint-box" id="hasilHitung">
                                <i class="fa-solid fa-calculator"></i> Total setoran dihitung otomatis (berat &times; harga per kg).
                            </div>
                        </div>

                        <div class="form-group full">
                            <button type="submit" class="btn">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Setoran
                            </button>
                        </div>

                    </div>

                </form>

                <?php endif; ?>

            </div>

            <!-- SETORAN TERAKHIR -->
            <div class="box">

                <div class="box-header">
                    <h2><i class="fa-solid fa-clock-rotate-left"></i> Setoran Terakhirmu</h2>
                </div>

                <?php if (mysqli_num_rows($q_riwayat) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($q_riwayat)): ?>
                        <div class="riwayat-item">
                            <div>
                                <b style="font-size:13px;"><?= htmlspecialchars($row['jenis_sampah']) ?></b><br>
                                <small><?= number_format($row['berat_kg'], 1, ',', '.') ?> kg &middot; <?= date('d-m-Y H:i', strtotime($row['tanggal'])) ?></small>
                            </div>
                            <div class="riwayat-total"><?= rupiah($row['total_rp']) ?></div>
                        </div>
                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty-state">
                        <i class="fa-solid fa-inbox"></i>
                        Belum ada setoran. Yuk setor sampah pertamamu!
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </main>

    <script>
        function hitungTotal() {
            const sel = document.getElementById('id_sampah');
            const berat = parseFloat(document.getElementById('berat_kg').value) || 0;
            const opt = sel.options[sel.selectedIndex];
            const harga = opt ? parseFloat(opt.getAttribute('data-harga')) || 0 : 0;
            const box = document.getElementById('hasilHitung');

            if (harga > 0 && berat > 0) {
                box.innerHTML = '<i class="fa-solid fa-calculator"></i> Total: <b>Rp ' +
                    Math.round(berat * harga).toLocaleString('id-ID') + '</b> (' + berat +
                    ' kg &times; Rp ' + harga.toLocaleString('id-ID') + '/kg)';
            } else {
                box.innerHTML = '<i class="fa-solid fa-calculator"></i> Total setoran dihitung otomatis (berat &times; harga per kg).';
            }
        }
    </script>

    <script src="assets/tema.js"></script>
</body>

</html>
