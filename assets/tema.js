/* EcoSave – perilaku tema bersama: tombol menu, mode gelap, sidebar responsif */
(function () {
    var KEY_TEMA = 'ecosave_tema';
    var KEY_SIDEBAR = 'ecosave_sidebar';
    var root = document.documentElement;
    var body = document.body;

    function baca(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
    function tulis(k, v) { try { localStorage.setItem(k, v); } catch (e) { /* abaikan */ } }
    function mobile() { return window.matchMedia('(max-width: 900px)').matches; }

    var sidebar = document.querySelector('.sidebar');
    var main = document.querySelector('.main');
    if (!sidebar || !main) return;

    /* ---- cari / buat topbar ---- */
    var bar = null;
    for (var i = 0; i < main.children.length; i++) {
        var c = main.children[i];
        if (c.classList.contains('topbar') || c.classList.contains('header')) { bar = c; break; }
    }
    if (!bar) {
        bar = document.createElement('div');
        bar.className = 'topbar';
        main.insertBefore(bar, main.firstChild);
    }

    function tombol(ikon, label) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn-ikon';
        b.setAttribute('aria-label', label);
        b.title = label;
        b.innerHTML = '<i class="fa-solid ' + ikon + '"></i>';
        return b;
    }

    /* kiri: tombol menu + judul. kanan: toggle tema + profil */
    var profil = bar.querySelector(':scope > .profile');
    var kiri = document.createElement('div');
    kiri.className = 'tb-kiri';
    var burger = tombol('fa-bars', 'Buka / tutup menu');
    var judul = document.createElement('div');
    judul.className = 'tb-judul';
    Array.prototype.slice.call(bar.children).forEach(function (el) {
        if (el !== profil) judul.appendChild(el);
    });
    /* halaman tanpa judul sendiri: pakai <title> */
    if (!judul.children.length) {
        judul.innerHTML = '<h1></h1>';
        judul.firstChild.textContent = document.title.replace(/\s*[-–]\s*EcoSave.*/i, '');
    }
    kiri.appendChild(burger);
    kiri.appendChild(judul);

    var kanan = document.createElement('div');
    kanan.className = 'tb-kanan';
    var toggle = tombol('fa-moon', 'Ganti mode terang / gelap');
    kanan.appendChild(toggle);
    if (profil) kanan.appendChild(profil);

    bar.appendChild(kiri);
    bar.appendChild(kanan);

    /* ---- overlay untuk mobile ---- */
    var overlay = document.createElement('div');
    overlay.className = 'tema-overlay';
    body.appendChild(overlay);

    /* ---- sidebar ---- */
    if (!mobile() && baca(KEY_SIDEBAR) === 'tutup') body.classList.add('sidebar-tutup');

    burger.addEventListener('click', function () {
        if (mobile()) {
            body.classList.toggle('sidebar-buka');
        } else {
            var tutup = body.classList.toggle('sidebar-tutup');
            tulis(KEY_SIDEBAR, tutup ? 'tutup' : 'buka');
        }
    });
    overlay.addEventListener('click', function () { body.classList.remove('sidebar-buka'); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') body.classList.remove('sidebar-buka');
    });
    window.addEventListener('resize', function () {
        if (!mobile()) body.classList.remove('sidebar-buka');
    });

    /* ---- mode gelap ---- */
    function terapkan(gelap) {
        if (gelap) root.setAttribute('data-tema', 'gelap');
        else root.removeAttribute('data-tema');
        toggle.innerHTML = '<i class="fa-solid ' + (gelap ? 'fa-sun' : 'fa-moon') + '"></i>';
    }
    terapkan(baca(KEY_TEMA) === 'gelap');
    toggle.addEventListener('click', function () {
        var gelap = root.getAttribute('data-tema') !== 'gelap';
        tulis(KEY_TEMA, gelap ? 'gelap' : 'terang');
        terapkan(gelap);
    });
})();
