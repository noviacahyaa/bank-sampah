<?php
session_start();
include "koneksi.php";

/* Cek login */
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

/* Ambil data user */
$query = mysqli_query(
    $koneksi,
    "SELECT nama, saldo FROM users WHERE id_user = '$id_user'"
);

$user = mysqli_fetch_assoc($query);

/* Jika user tidak ditemukan */
if (!$user) {
    die("Data pengguna tidak ditemukan.");
}

$saldo = (int) $user['saldo'];
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tarik Saldo - EcoSave</title>

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
            background: linear-gradient(
                135deg,
                #f5f8fa,
                #ecf2f6
            );
            color: #333333;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;

            width: 250px;
            height: 100vh;

            padding: 30px 20px;

            background: linear-gradient(
                180deg,
                #6434d3,
                #421c9b
            );

            color: white;

            box-shadow:
                4px 0 15px rgba(0,0,0, 0.08);
        }

        .logo {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 22px;
            font-weight: 600;
            text-align: center;

            margin-bottom: 40px;
        }

        .menu-title {
            font-size: 13px;
            opacity: 0.8;

            margin-bottom: 15px;

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

        .menu a:hover,
        .menu a.active {
            background: rgba(255,255,255, 0.2);

            transform: translateX(5px);
        }


        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 250px;

            min-height: 100vh;

            padding: 35px;
        }


        /* =========================
           HEADER
        ========================= */

        .header {
            margin-bottom: 25px;
        }

        .header h1 {
            font-family: "Fredoka", "Poppins", sans-serif;
            color: #3f608a;
            font-weight: 600;

            font-size: 27px;

            margin-bottom: 5px;
        }

        .header p {
            color: #777777;
        }


        /* =========================
           BACK BUTTON
        ========================= */

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            margin-bottom: 20px;

            color: #421c9b;

            text-decoration: none;

            font-weight: 600;

            transition: 0.3s;
        }

        .back-btn:hover {
            color: #421c9b;

            transform: translateX(-3px);
        }


        /* =========================
           CARD
        ========================= */

        .card {
            max-width: 650px;

            background: white;

            padding: 30px;

            border-radius: 22px;

            box-shadow:
                0 8px 25px rgba(0,0,0, 0.07);
        }


        /* =========================
           SALDO CARD
        ========================= */

        .saldo-card {
            position: relative;

            overflow: hidden;

            background: linear-gradient(
                135deg,
                #6434d3,
                #421c9b
            );

            color: white;

            padding: 25px;

            border-radius: 18px;

            margin-bottom: 25px;

            box-shadow:
                0 8px 20px rgba(66,28,155,
                    0.25
                );
        }

        .saldo-card::after {
            content: "\f555"; /* fa-wallet */
            font-family: "Font Awesome 6 Free";
            font-weight: 900;

            position: absolute;

            right: 25px;
            bottom: 5px;

            font-size: 70px;

            opacity: 0.15;
        }

        .saldo-card p {
            font-size: 14px;

            opacity: 0.9;

            margin-bottom: 8px;
        }

        .saldo-card h2 {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 31px;

            font-weight: 600;
        }


        /* =========================
           FORM
        ========================= */

        .form-title {
            color: #3f608a;

            font-size: 20px;

            margin-bottom: 8px;
        }

        .form-description {
            color: #777777;

            font-size: 14px;

            margin-bottom: 20px;
        }

        label {
            display: block;

            color: #355278;

            font-weight: bold;

            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .currency {
            position: absolute;

            left: 15px;
            top: 50%;

            transform: translateY(-50%);

            color: #3f608a;

            font-weight: bold;
        }

        input {
            width: 100%;

            padding: 14px 15px 14px 55px;

            border: 2px solid #d6f0e7;

            border-radius: 12px;

            font-size: 16px;

            outline: none;

            transition: 0.3s;

            margin-bottom: 10px;
        }

        input:focus {
            border-color: #6434d3;

            box-shadow:
                0 0 0 3px rgba(67,31,152,
                    0.1
                );
        }

        .example {
            color: #999999;

            font-size: 13px;

            margin-bottom: 20px;
        }


        /* =========================
           INFO
        ========================= */

        .info {
            background: linear-gradient(135deg, #f8fafb, #f3f7f9);

            border: 1px solid #e5edf3;

            color: #3f608a;

            padding: 16px 18px;

            border-radius: 14px;

            margin-bottom: 20px;

            font-size: 13.5px;

            line-height: 1.6;
        }

        .info-title {
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #421c9b;
        }

        .info-list {
            list-style: none;
        }

        .info-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 8px;
        }

        .info-list li:last-child {
            margin-bottom: 0;
        }

        .info-list li i {
            color: #421c9b;
            font-size: 13px;
            margin-top: 2px;
            width: 14px;
        }


        /* =========================
           BUTTON
        ========================= */

        .submit-btn {
            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 12px;

            background: linear-gradient(
                135deg,
                #6434d3,
                #421c9b
            );

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);

            box-shadow:
                0 8px 20px rgba(66,28,155,
                    0.25
                );
        }


        /* =========================
           ALERT
        ========================= */

        .success {
            background: #ebf1f5;

            border-left: 4px solid #452394;

            color: #402188;

            padding: 14px;

            border-radius: 10px;

            margin-bottom: 20px;

            line-height: 1.5;
        }

        .error {
            background: #eff4f8;

            border-left: 4px solid #dc3545;

            color: #c82333;

            padding: 14px;

            border-radius: 10px;

            margin-bottom: 20px;

            line-height: 1.5;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 768px) {

            .sidebar {
                position: relative;

                width: 100%;

                height: auto;

                padding: 20px;
            }

            .logo {
                margin-bottom: 20px;
            }

            .menu {
                display: flex;

                flex-wrap: wrap;

                gap: 5px;
            }

            .menu li {
                margin: 0;
            }

            .menu a {
                padding: 10px 12px;

                font-size: 14px;
            }

            .main {
                margin-left: 0;

                padding: 20px;
            }

            .card {
                max-width: 100%;
            }

            .header h1 {
                font-size: 24px;
            }

        }

    </style>


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/tema.css">
    <script>try{if(localStorage.getItem('ecosave_tema')==='gelap')document.documentElement.setAttribute('data-tema','gelap')}catch(e){}</script>
</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">
        <i class="fa-solid fa-recycle"></i> EcoSave
    </div>

    <div class="menu-title">
        Menu Utama
    </div>

    <ul class="menu">

        <li>
            <a href="dashboard.php">
                <i class="fa-solid fa-house"></i> Beranda
            </a>
        </li>

        <li>
            <a href="tarik_saldo.php" class="active">
                <i class="fa-solid fa-wallet"></i> Tarik Saldo
            </a>
        </li>

        <li>
            <a href="riwayat.php">
                <i class="fa-solid fa-clock-rotate-left"></i> Riwayat
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


<!-- =========================
     MAIN
========================= -->

<main class="main">


    <div class="header">

        <h1>Tarik Saldo</h1>

        <p>
            Ajukan penarikan saldo tabungan Anda
        </p>

    </div>


    <a href="dashboard.php" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
    </a>


    <div class="card">


        <!-- SALDO -->

        <div class="saldo-card">

            <p>
                Saldo Anda saat ini
            </p>

            <h2>
                Rp <?php
                echo number_format(
                    $saldo,
                    0,
                    ',',
                    '.'
                );
                ?>
            </h2>

        </div>


        <!-- PESAN BERHASIL -->

        <?php if (isset($_GET['sukses'])) { ?>

            <div class="success">

                <i class="fa-solid fa-circle-check"></i> <b>Pengajuan berhasil!</b>

                <br>

                Pengajuan tarik saldo telah dikirim
                dan sedang menunggu diproses admin.

            </div>

        <?php } ?>


        <!-- PESAN ERROR -->

        <?php if (isset($_GET['error'])) { ?>

            <div class="error">

                <i class="fa-solid fa-circle-xmark"></i> <b>Pengajuan gagal!</b>

                <br>

                <?php
                echo htmlspecialchars(
                    $_GET['error']
                );
                ?>

            </div>

        <?php } ?>


        <h2 class="form-title">
            Ajukan Penarikan
        </h2>

        <p class="form-description">
            Masukkan jumlah saldo yang ingin Anda tarik.
        </p>


        <!-- INFO -->

        <div class="info">

            <div class="info-title"><i class="fa-solid fa-circle-info"></i> Ketentuan Penarikan</div>

            <ul class="info-list">
                <li><i class="fa-solid fa-coins"></i> Minimal penarikan Rp 1.000</li>
                <li><i class="fa-solid fa-scale-balanced"></i> Penarikan tidak boleh melebihi saldo</li>
                <li><i class="fa-solid fa-user-shield"></i> Pengajuan akan diperiksa oleh admin</li>
            </ul>

        </div>


        <!-- FORM -->

        <form
            action="proses_tarik.php"
            method="POST"
        >

            <label for="nominal">
                Nominal Penarikan
            </label>


            <div class="input-wrapper">

                <span class="currency">
                    Rp
                </span>

                <input
                    type="text"
                    id="nominal"
                    name="nominal"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="Contoh: 10.000"
                    required
                >

            </div>


            <p class="example">
                Contoh penulisan: 1.000 atau 10.000
            </p>


            <button
                type="submit"
                class="submit-btn"
            >
                <i class="fa-solid fa-paper-plane"></i> Ajukan Penarikan
            </button>

        </form>


    </div>

</main>


<!-- =========================
     FORMAT ANGKA OTOMATIS
========================= -->

<script>

const nominalInput =
    document.getElementById("nominal");


nominalInput.addEventListener(
    "input",
    function () {

        // Ambil angka saja
        let angka =
            this.value.replace(/\D/g, "");


        // Jika kosong
        if (angka === "") {

            this.value = "";

            return;

        }


        // Ubah menjadi format 1.000
        this.value =
            new Intl.NumberFormat("id-ID")
            .format(parseInt(angka));

    }
);

</script>


    <script src="assets/tema.js"></script>
</body>

</html>