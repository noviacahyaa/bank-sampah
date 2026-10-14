<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - EcoSave</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --blush-50: #f9fbfc;
            --blush-100: #f1f5f8;
            --pink-300: #6434d3;
            --pink-400: #6434d3;
            --pink-500: #6df3c6;
            --pink-600: #421b9c;
            --rose-ink: #324968;
            --rose-muted: #7b8ba1;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", Arial, sans-serif;
        }

        html, body {
            min-height: 100vh;
        }

        body {
            position: relative;
            overflow-x: hidden;

            display: flex;
            justify-content: center;
            align-items: center;

            background: linear-gradient(160deg, #e1eaf1 0%, #eef3f7 45%, #f5f8fa 100%);
            padding: 24px;
        }

        /* BLOB DEKORASI */
        body::before,
        body::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            filter: blur(2px);
            z-index: 0;
        }

        body::before {
            width: 380px;
            height: 380px;
            top: -140px;
            left: -110px;
            background: radial-gradient(circle at 30% 30%, #c7fbea, rgba(199,251,234,0));
        }

        body::after {
            width: 460px;
            height: 460px;
            bottom: -180px;
            right: -150px;
            background: radial-gradient(circle at 60% 60%, #6434d3, rgba(180,249,226,0));
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* SHELL DUA PANEL */
        .shell {
            position: relative;
            z-index: 1;

            display: flex;
            width: 100%;
            max-width: 920px;

            background: rgba(255,255,255, 0.9);
            backdrop-filter: blur(6px);

            border-radius: 30px;
            border: 1px solid rgba(255,255,255, 0.6);
            overflow: hidden;

            box-shadow: 0 30px 60px rgba(73,44,140, 0.2);

            animation: fadeUp 0.5s ease both;
        }

        /* PANEL KIRI - BRAND */
        .brand-panel {
            position: relative;
            flex: 1;
            min-width: 0;

            padding: 46px 40px;

            color: white;

            background: linear-gradient(155deg, #6434d3 0%, #6ff3c7 55%, #421c9b 100%);

            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .brand-panel::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            right: -80px;
            top: -80px;
            border-radius: 50%;
            background: rgba(255,255,255, 0.12);
        }

        .brand-panel::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            left: -60px;
            bottom: -60px;
            border-radius: 50%;
            background: rgba(255,255,255, 0.1);
        }

        .brand-top {
            position: relative;
            z-index: 1;
        }

        .brand-badge {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            background: rgba(255,255,255, 0.22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 22px;
        }

        .brand-panel h1 {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .brand-panel p {
            font-size: 13.5px;
            line-height: 1.7;
            opacity: 0.92;
            max-width: 300px;
        }

        .brand-stats {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 22px;
            margin-top: 30px;
        }

        .brand-stats div {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .brand-stats strong {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 20px;
            font-weight: 600;
        }

        .brand-stats span {
            font-size: 11px;
            opacity: 0.85;
        }

        .brand-quote {
            position: relative;
            z-index: 1;
            font-size: 12.5px;
            line-height: 1.6;
            padding: 14px 16px;
            border-radius: 14px;
            background: rgba(255,255,255, 0.14);
            margin-top: 26px;
        }

        .brand-quote i {
            margin-right: 6px;
        }

        /* PANEL KANAN - FORM */
        .form-panel {
            flex: 1;
            min-width: 0;

            padding: 46px 42px;

            text-align: left;
        }

        .form-panel .icon-logo {
            width: 54px;
            height: 54px;

            margin-bottom: 18px;

            border-radius: 16px;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 24px;
            color: white;

            background: linear-gradient(135deg, var(--pink-400), var(--pink-600));
            box-shadow: 0 10px 22px rgba(66,27,156, 0.3);
        }

        h2.form-heading {
            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 24px;
            font-weight: 600;
            color: var(--rose-ink);
            margin-bottom: 6px;
        }

        .subtitle {
            color: var(--rose-muted);
            font-size: 13.5px;
            margin-bottom: 26px;
        }

        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            background: #f5f8fa;
            border: 1px solid #cdfbec;
            color: #48298f;
            padding: 12px 15px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.5;
        }

        .alert-error i {
            margin-top: 2px;
        }

        /* FORM */
        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 600;
            color: var(--rose-ink);
        }

        .input-box {
            position: relative;
        }

        .input-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 15px;
            color: var(--pink-500);
        }

        .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            color: var(--rose-muted);
            cursor: pointer;
            background: none;
            border: none;
            padding: 4px;
        }

        input {
            width: 100%;

            padding: 13px 42px 13px 42px;

            border: 1.5px solid var(--blush-100);
            border-radius: 13px;

            outline: none;

            background: var(--blush-50);
            color: var(--rose-ink);

            font-size: 14px;

            transition: 0.25s;
        }

        input::placeholder {
            color: #a5b6cd;
        }

        input:focus {
            border-color: var(--pink-400);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(109,243,198, 0.14);
        }

        button.submit-btn {
            width: 100%;

            border: none;

            padding: 14px;

            border-radius: 13px;

            cursor: pointer;

            color: white;

            font-family: "Fredoka", "Poppins", sans-serif;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.2px;

            background: linear-gradient(135deg, var(--pink-400), var(--pink-600));

            box-shadow: 0 12px 24px rgba(66,27,156, 0.3);

            transition: 0.25s;

            margin-top: 6px;
        }

        button.submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 28px rgba(66,27,156, 0.4);
        }

        button.submit-btn:active {
            transform: translateY(0);
        }

        .line {
            border: none;
            border-top: 1px solid #e1eaf0;
            margin: 26px 0 16px;
        }

        .footer {
            font-size: 11.5px;
            color: var(--rose-muted);
            line-height: 19px;
            text-align: center;
        }

        .footer b {
            color: var(--pink-600);
        }

        /* RESPONSIVE */
        @media (max-width: 780px) {

            .shell {
                flex-direction: column;
                max-width: 460px;
                border-radius: 26px;
            }

            .brand-panel {
                padding: 32px 30px;
            }

            .brand-panel p {
                max-width: 100%;
            }

            .brand-quote {
                display: none;
            }

            .form-panel {
                padding: 34px 30px;
            }
        }

        @media (max-width: 420px) {
            .brand-stats {
                gap: 16px;
            }
        }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/tema.css">
    <script>try{if(localStorage.getItem('ecosave_tema')==='gelap')document.documentElement.setAttribute('data-tema','gelap')}catch(e){}</script>
</head>

<body>

    <div class="shell">

        <!-- PANEL KIRI: BRAND -->
        <div class="brand-panel">

            <div class="brand-top">
                <div class="brand-badge">
                    <i class="fa-solid fa-recycle"></i>
                </div>

                <h1>EcoSave</h1>
                <p>Ubah sampah jadi tabungan. Setor, pantau saldo, dan lihat dampak lingkunganmu dalam satu sistem yang sederhana.</p>

                <div class="brand-stats">
                    <div>
                        <strong>3</strong>
                        <span>Jenis Sampah</span>
                    </div>
                    <div>
                        <strong>100%</strong>
                        <span>Tercatat Rapi</span>
                    </div>
                    <div>
                        <strong>24/7</strong>
                        <span>Pantau Saldo</span>
                    </div>
                </div>
            </div>

            <div class="brand-quote">
                <i class="fa-solid fa-leaf"></i>
                Sedikit sampah yang dikelola hari ini, sedikit beban bumi esok hari.
            </div>

        </div>

        <!-- PANEL KANAN: FORM -->
        <div class="form-panel">

            <div class="icon-logo">
                <i class="fa-solid fa-right-to-bracket"></i>
            </div>

            <h2 class="form-heading">Selamat Datang</h2>

            <p class="subtitle">
                Masuk untuk mengelola tabungan sampahmu
            </p>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <span>Username atau password salah. Silakan coba lagi.</span>
                </div>
            <?php endif; ?>

            <!-- FORM LOGIN -->
            <form action="proses_login.php" method="POST">

                <!-- USERNAME -->
                <div class="form-group">

                    <label>Username</label>

                    <div class="input-box">

                        <i class="fa-solid fa-user"></i>

                        <input
                            type="text"
                            name="username"
                            placeholder="Masukkan username"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->
                <div class="form-group">

                    <label>Password</label>

                    <div class="input-box">

                        <i class="fa-solid fa-lock"></i>

                        <input
                            type="password"
                            name="password"
                            id="passwordField"
                            placeholder="Masukkan password"
                            required
                        >

                        <button type="button" class="toggle-pass" id="togglePass" aria-label="Tampilkan password">
                            <i class="fa-solid fa-eye"></i>
                        </button>

                    </div>

                </div>


                <!-- TOMBOL LOGIN -->
                <button type="submit" class="submit-btn">
                    <i class="fa-solid fa-right-to-bracket"></i> &nbsp; Login
                </button>

            </form>


            <!-- FOOTER -->
            <hr class="line">

            <div class="footer">
                © 2026 <b>EcoSave</b><br>
                Sistem Informasi Bank Sampah
            </div>

        </div>

    </div>

    <script>
        const togglePass = document.getElementById('togglePass');
        const passwordField = document.getElementById('passwordField');

        if (togglePass && passwordField) {
            togglePass.addEventListener('click', function () {
                const isHidden = passwordField.type === 'password';
                passwordField.type = isHidden ? 'text' : 'password';
                this.innerHTML = isHidden
                    ? '<i class="fa-solid fa-eye-slash"></i>'
                    : '<i class="fa-solid fa-eye"></i>';
            });
        }
    </script>

</body>
</html>
