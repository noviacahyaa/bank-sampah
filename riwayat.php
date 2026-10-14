<?php

session_start();
include 'koneksi.php';

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

/* Riwayat setoran */
$sql_setor = "SELECT
                t.tanggal,
                s.jenis_sampah,
                t.berat_kg,
                t.total_rp
              FROM transaksi t
              JOIN sampah s ON t.id_sampah = s.id_sampah
              WHERE t.id_user = '$id_user'
              ORDER BY t.tanggal DESC";

$result_setor = mysqli_query($koneksi, $sql_setor);
$rows_setor = mysqli_fetch_all($result_setor, MYSQLI_ASSOC);

/* Riwayat penarikan */
$sql_tarik = "SELECT
                nominal,
                tanggal,
                status
              FROM tarik_saldo
              WHERE id_user = '$id_user'
              ORDER BY tanggal DESC";

$result_tarik = mysqli_query($koneksi, $sql_tarik);
$rows_tarik = mysqli_fetch_all($result_tarik, MYSQLI_ASSOC);

/* Ringkasan (murni tampilan, dihitung dari data yang sudah diambil) */
$jumlah_setoran   = count($rows_setor);
$total_berat_all  = array_sum(array_column($rows_setor, 'berat_kg'));
$total_rp_setoran = array_sum(array_column($rows_setor, 'total_rp'));
$total_rp_tarik   = array_sum(array_column($rows_tarik, 'nominal'));

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat - EcoSave</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        * {
            box-sizing: border-box;
            font-family: "Poppins", Arial, sans-serif;
        }

        body {
            margin: 0;
            background: linear-gradient(135deg, #f5f8fa, #ecf2f6);
            min-height: 100vh;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        .back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #421c9b;
            text-decoration: none;
            font-weight: 600;
        }

        h1 {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-weight: 600;
            color: #3a5579;
            margin-top: 14px;
            margin-bottom: 4px;
        }

        .subtitle {
            color: #7b8ba1;
            font-size: 13.5px;
            margin-bottom: 22px;
        }

        /* RINGKASAN */

        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }

        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 16px 18px;
            box-shadow: 0 8px 20px rgba(57,87,126, 0.08);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .summary-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            color: white;
        }

        .summary-card:nth-child(1) .summary-icon { background: linear-gradient(135deg, #6434d3, #421c9b); }
        .summary-card:nth-child(2) .summary-icon { background: linear-gradient(135deg, #ffb877, #d97a3e); }
        .summary-card:nth-child(3) .summary-icon { background: linear-gradient(135deg, #7fd8ba, #482a8e); }
        .summary-card:nth-child(4) .summary-icon { background: linear-gradient(135deg, #ffcf6b, #c99420); }

        .summary-label {
            font-size: 10.5px;
            color: #7b8ba1;
            margin-bottom: 2px;
        }

        .summary-value {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 15px;
            font-weight: 600;
            color: #2b3f5a;
        }

        /* TAB */

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 18px;
        }

        .tab-btn {
            border: none;
            background: white;
            color: #7b8ba1;
            padding: 11px 20px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(57,87,126, 0.06);
            transition: 0.2s;
        }

        .tab-btn.active {
            color: white;
            background: linear-gradient(135deg, #6434d3, #421c9b);
            box-shadow: 0 8px 18px rgba(66,28,155, 0.3);
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(57,87,126, 0.08);
        }

        .card h2 {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-weight: 600;
            font-size: 17px;
            color: #3f608a;
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background: #e1eaf0;
            color: #355278;
            padding: 12px;
            text-align: left;
            font-size: 13px;
        }

        th:first-child { border-radius: 10px 0 0 10px; }
        th:last-child  { border-radius: 0 10px 10px 0; }

        td {
            padding: 12px;
            border-bottom: 1px solid #e6edf2;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #fafbfc;
        }

        .total {
            color: #421c9b;
            font-weight: 700;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .pending {
            background: #fff0c9;
            color: #9a7200;
        }

        .selesai {
            background: #e4ecf2;
            color: #402188;
        }

        .empty {
            text-align: center;
            color: #a4adb8;
            padding: 30px !important;
        }

        .empty i {
            display: block;
            font-size: 26px;
            margin-bottom: 8px;
            color: #e1eaf1;
        }

        @media (max-width: 850px) {
            .summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 550px) {
            .tabs {
                flex-wrap: wrap;
            }

            .tab-btn {
                flex: 1;
                justify-content: center;
            }
        }

    </style>


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/tema.css">
    <script>try{if(localStorage.getItem('ecosave_tema')==='gelap')document.documentElement.setAttribute('data-tema','gelap')}catch(e){}</script>
</head>

<body>

<div class="container">

    <a href="dashboard.php" class="back">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
    </a>

    <h1><i class="fa-solid fa-clipboard-list"></i> Riwayat Transaksi</h1>
    <p class="subtitle">Rekap lengkap setoran sampah dan pengajuan tarik saldo kamu.</p>

    <!-- RINGKASAN -->
    <div class="summary">

        <div class="summary-card">
            <div class="summary-icon"><i class="fa-solid fa-recycle"></i></div>
            <div>
                <div class="summary-label">Total Setoran</div>
                <div class="summary-value"><?= $jumlah_setoran ?>x</div>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon"><i class="fa-solid fa-weight-hanging"></i></div>
            <div>
                <div class="summary-label">Total Berat</div>
                <div class="summary-value"><?= number_format($total_berat_all, 1, ',', '.') ?> kg</div>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon"><i class="fa-solid fa-sack-dollar"></i></div>
            <div>
                <div class="summary-label">Hasil Setoran</div>
                <div class="summary-value">Rp <?= number_format($total_rp_setoran, 0, ',', '.') ?></div>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-icon"><i class="fa-solid fa-wallet"></i></div>
            <div>
                <div class="summary-label">Total Ditarik</div>
                <div class="summary-value">Rp <?= number_format($total_rp_tarik, 0, ',', '.') ?></div>
            </div>
        </div>

    </div>

    <!-- TAB -->
    <div class="tabs">
        <button class="tab-btn active" id="tabSetorBtn" onclick="tampilkanTab('setor')">
            <i class="fa-solid fa-recycle"></i> Setoran Sampah
        </button>
        <button class="tab-btn" id="tabTarikBtn" onclick="tampilkanTab('tarik')">
            <i class="fa-solid fa-wallet"></i> Penarikan Saldo
        </button>
    </div>

    <!-- RIWAYAT SETORAN -->
    <div class="card" id="panelSetor">

        <h2><i class="fa-solid fa-recycle"></i> Riwayat Setoran Sampah</h2>

        <table>

            <thead>

                <tr>
                    <th>Tanggal</th>
                    <th>Jenis Sampah</th>
                    <th>Berat</th>
                    <th>Total</th>
                </tr>

            </thead>

            <tbody>

            <?php if (count($rows_setor) > 0): ?>

                <?php foreach ($rows_setor as $row): ?>

                    <tr>

                        <td>
                            <?php
                            echo date(
                                'd-m-Y',
                                strtotime($row['tanggal'])
                            );
                            ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['jenis_sampah']); ?>
                        </td>

                        <td>
                            <?php echo $row['berat_kg']; ?> kg
                        </td>

                        <td class="total">
                            Rp <?php
                            echo number_format(
                                $row['total_rp'],
                                0,
                                ',',
                                '.'
                            );
                            ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="4" class="empty">
                        <i class="fa-solid fa-inbox"></i>
                        Belum ada riwayat setoran.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <!-- RIWAYAT PENARIKAN -->
    <div class="card" id="panelTarik" style="display:none;">

        <h2><i class="fa-solid fa-wallet"></i> Riwayat Penarikan Saldo</h2>

        <table>

            <thead>

                <tr>
                    <th>Tanggal</th>
                    <th>Nominal</th>
                    <th>Status</th>
                </tr>

            </thead>

            <tbody>

            <?php if (count($rows_tarik) > 0): ?>

                <?php foreach ($rows_tarik as $row): ?>

                    <tr>

                        <td>
                            <?php
                            echo date(
                                'd-m-Y',
                                strtotime($row['tanggal'])
                            );
                            ?>
                        </td>

                        <td class="total">
                            Rp <?php
                            echo number_format(
                                $row['nominal'],
                                0,
                                ',',
                                '.'
                            );
                            ?>
                        </td>

                        <td>

                            <span class="status <?php echo $row['status']; ?>">

                                <?php
                                if ($row['status'] == 'pending') {
                                    echo 'Menunggu';
                                } elseif ($row['status'] == 'selesai') {
                                    echo 'Selesai';
                                } else {
                                    echo '-';
                                }
                                ?>

                            </span>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="3" class="empty">
                        <i class="fa-solid fa-inbox"></i>
                        Belum ada riwayat penarikan.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<script>
    function tampilkanTab(nama) {
        const panelSetor = document.getElementById('panelSetor');
        const panelTarik = document.getElementById('panelTarik');
        const btnSetor = document.getElementById('tabSetorBtn');
        const btnTarik = document.getElementById('tabTarikBtn');

        if (nama === 'setor') {
            panelSetor.style.display = 'block';
            panelTarik.style.display = 'none';
            btnSetor.classList.add('active');
            btnTarik.classList.remove('active');
        } else {
            panelSetor.style.display = 'none';
            panelTarik.style.display = 'block';
            btnTarik.classList.add('active');
            btnSetor.classList.remove('active');
        }
    }
</script>

    <script src="assets/tema.js"></script>
</body>
</html>
