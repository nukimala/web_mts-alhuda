<?php

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$perHalaman = 9;

// ---------- DATA ----------
// Struktur sama dengan admin Kelola Galeri. Controller cukup mengirim:
//   $data['items']  => tiap baris: id, title, image_path, album, caption, sort_order, is_active
//   $data['albums'] => daftar nama album (opsional, kalau kosong diambil dari items)
// Kalau controller belum mengirim $data['items'], dipakai DUMMY di bawah ini.
if (!isset($data['items'])) {
    $data = [
        'albums' => [
            'Akademik & Literasi',
            'Ekstrakurikuler Pramuka',
            'Kesehatan & Olahraga',
            'Kesenian & Prestasi',
            'Paguyuban & Komite',
            'Seni & Religi',
        ],
        'items' => [
            [
                'id' => 1,
                'title' => 'Aktivitas Belajar dan Memperkaya Khazanah Ilmu',
                'image_path' => 'assets/images/Belajar di Perpustakaan.jpg',
                'album' => 'Akademik & Literasi',
                'caption' => 'Santri tekun mengkaji buku referensi di perpustakaan madrasah.',
                'sort_order' => 1,
                'is_active' => 1,
            ],
            [
                'id' => 2,
                'title' => 'Penampilan Seni Hadrah Santri MTs Al-Huda',
                'image_path' => 'assets/images/Grup Sholawat  MTS Al-Huda.jpg',
                'album' => 'Seni & Religi',
                'caption' => 'Grup hadrah santri membawakan sholawat dalam acara madrasah.',
                'sort_order' => 2,
                'is_active' => 1,
            ],
            [
                'id' => 3,
                'title' => 'Upacara Pembukaan Jambore Cabang Kontingen Regu Penggalang',
                'image_path' => 'assets/images/Mengikuti Upacara Pembukaan Jambore Cabang.jpg',
                'album' => 'Ekstrakurikuler Pramuka',
                'caption' => 'Kontingen Pramuka MTs Al-Huda mengikuti upacara pembukaan jambore cabang.',
                'sort_order' => 3,
                'is_active' => 1,
            ],
            [
                'id' => 4,
                'title' => 'Semarak Senam Bersama Dewan Asatidz dan Seluruh Santri',
                'image_path' => 'assets/images/Senam Bersama.jpg',
                'album' => 'Kesehatan & Olahraga',
                'caption' => 'Senam kebugaran jasmani bersama guru dan seluruh santri.',
                'sort_order' => 4,
                'is_active' => 1,
            ],
            [
                'id' => 5,
                'title' => 'Rapat Pleno Silaturahmi Paguyuban Wali Santri MTs Al-Huda',
                'image_path' => 'assets/images/Pertemuan Wali Santri.jpg',
                'album' => 'Paguyuban & Komite',
                'caption' => 'Sinergi hangat antara pimpinan madrasah dan wali santri.',
                'sort_order' => 5,
                'is_active' => 1,
            ],
            [
                'id' => 6,
                'title' => 'Harmoni Nada & Formasi Korps Drumband Gita Al-Huda',
                'image_path' => 'assets/images/Drumband.jpg',
                'album' => 'Kesenian & Prestasi',
                'caption' => 'Unit korps drumband MTs Al-Huda tampil dengan formasi yang kompak.',
                'sort_order' => 6,
                'is_active' => 1,
            ],
        ],
    ];
}

// Hanya foto aktif, urut berdasarkan sort_order
$items = array_values(array_filter($data['items'], fn($it) => (int) ($it['is_active'] ?? 0) === 1));
usort($items, fn($a, $b) => (int) $a['sort_order'] <=> (int) $b['sort_order']);

// Foto tanpa album ditampilkan sebagai "Umum" (sama seperti di admin)
foreach ($items as &$it) {
    if (empty($it['album']))
        $it['album'] = 'Umum';
}
unset($it);

// Daftar tombol filter: album dari controller (urutan sama dengan dropdown admin), hanya yang punya foto
$jumlah = array_count_values(array_column($items, 'album'));
$daftarAlbum = $data['albums'] ?? array_keys($jumlah);
if (isset($jumlah['Umum']) && !in_array('Umum', $daftarAlbum, true))
    $daftarAlbum[] = 'Umum';
$daftarAlbum = array_values(array_filter($daftarAlbum, fn($a) => !empty($jumlah[$a])));

function gx_h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

?>

<link rel="stylesheet" href="<?= base_url('css/galeri.css') ?>">

<main>
    <!-- Hero: disamakan dengan halaman Berita -->
    <section class="page-hero" style="background: linear-gradient(135deg, #0B3D1E 0%, #15803D 100%); color: #fff; padding: 48px 0 40px;">
        <div class="container">
            <div class="section-label" style="color: #4ADE80; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; font-size: 11.5px; margin-bottom: 8px;">
                Galeri Foto &amp; Dokumentasi
            </div>
            <h1 style="color: #ffffff; font-size: 32px; font-weight: 800; margin-bottom: 12px;">Momen &amp; <span style="color: #4ADE80;">Kegiatan</span> Madrasah</h1>
            <p style="color: rgba(255,255,255,.85); max-width: 640px; font-size: 14.5px; text-wrap: balance;">
                Dokumentasi berbagai kegiatan belajar, ibadah, dan pengembangan diri siswa MTs Al-Huda Gondang.
            </p>
        </div>
    </section>

    <section class="page-section gx-section">
        <div class="container">

            <div class="gx-filter" role="group" aria-label="Filter album galeri">
                <button type="button" class="gx-chip active" data-filter="semua">Semua <span class="gx-n" id="gxBadge"><?= count(
                    $items,
                ) ?></span></button>
                <?php foreach ($daftarAlbum as $alb): ?>
                    <button type="button" class="gx-chip" data-filter="<?= gx_h($alb) ?>"><?= gx_h(
                        $alb,
                    ) ?> <span class="gx-n"></span></button>
                <?php endforeach; ?>
            </div>

            <div class="gx-grid" id="gxGrid">
                <?php foreach ($items as $it):
                    $src = base_url($it['image_path']);
                    ?>
                    <button type="button" class="gx-card" data-album="<?= gx_h($it['album']) ?>"
                            data-src="<?= gx_h($src) ?>"
                            data-judul="<?= gx_h($it['title']) ?>"
                            data-caption="<?= gx_h($it['caption'] ?? '') ?>"
                            data-label="<?= gx_h($it['album']) ?>"
                            aria-label="Perbesar foto: <?= gx_h($it['title']) ?>">
                        <img src="<?= gx_h($src) ?>" alt="<?= gx_h($it['title']) ?>" loading="lazy">
                        <span class="gx-badge"><?= gx_h($it['album']) ?></span>
                        <span class="gx-info">
                            <span class="gx-title" style="display:-webkit-box"><?= gx_h($it['title']) ?></span>
                            <?php if (!empty($it['caption'])): ?>
                                <span class="gx-cap" style="display:block"><?= gx_h($it['caption']) ?></span>
                            <?php endif; ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>

            <p class="gx-empty" id="gxEmpty">Belum ada foto pada album ini.</p>

            <div class="gx-foot">
                <span id="gxInfo"></span>
                <nav class="gx-pg" id="gxPg" aria-label="Halaman galeri"></nav>
            </div>
        </div>
    </section>
</main>

<dialog class="gx-dialog" id="gxDialog">
    <img id="gxImg" alt="">
    <div class="gx-meta">
        <div>
            <span class="gx-tag" id="gxTag"></span>
            <h3 id="gxTitle"></h3>
            <p id="gxCap"></p>
        </div>
        <button type="button" class="gx-close" id="gxClose">Tutup</button>
    </div>
</dialog>

<script>
(function () {
    const PER = <?= (int) $perHalaman ?>;
    const cards = [...document.querySelectorAll('.gx-card')];
    const chips = [...document.querySelectorAll('.gx-chip')];
    const info  = document.getElementById('gxInfo');
    const pg    = document.getElementById('gxPg');
    const empty = document.getElementById('gxEmpty');
    const grid  = document.getElementById('gxGrid');
    let album = 'semua', page = 1;

    function pageList(cur, total) {
        if (total <= 7) return [...Array(total)].map((_, i) => i + 1);
        const s = new Set([1, total, cur - 1, cur, cur + 1].filter(n => n >= 1 && n <= total));
        const arr = [...s].sort((a, b) => a - b), out = [];
        arr.forEach((n, i) => { if (i && n - arr[i - 1] > 1) out.push('…'); out.push(n); });
        return out;
    }

    function render(scroll) {
        const list  = cards.filter(c => album === 'semua' || c.dataset.album === album);
        const total = list.length;
        const pages = Math.max(1, Math.ceil(total / PER));
        page = Math.min(page, pages);
        const start = (page - 1) * PER, end = Math.min(start + PER, total);

        cards.forEach(c => c.classList.add('is-hidden'));
        list.slice(start, end).forEach(c => c.classList.remove('is-hidden'));

        chips.forEach(ch => {
            const on = ch.dataset.filter === album;
            ch.classList.toggle('active', on);
            const n = ch.querySelector('.gx-n');
            if (on) n.textContent = total;
        });

        empty.style.display = total ? 'none' : 'block';
        info.textContent = total ? 'Menampilkan ' + (start + 1) + '–' + end + ' dari ' + total + ' dokumentasi' : '';

        pg.innerHTML = '';
        pg.style.display = total ? 'flex' : 'none';
        const mk = (txt, p, opts = {}) => {
            const b = document.createElement('button');
            b.type = 'button'; b.textContent = txt;
            if (opts.disabled) b.disabled = true;
            if (opts.active) { b.classList.add('active'); b.setAttribute('aria-current', 'page'); }
            b.addEventListener('click', () => { page = p; render(true); });
            pg.appendChild(b);
        };
        mk('‹ Sebelumnya', page - 1, { disabled: page === 1 });
        pageList(page, pages).forEach(n => {
            if (n === '…') { const s = document.createElement('span'); s.className = 'gx-dots'; s.textContent = '…'; pg.appendChild(s); }
            else mk(n, n, { active: n === page });
        });
        mk('Selanjutnya ›', page + 1, { disabled: page === pages });

        if (scroll) grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    chips.forEach(ch => ch.addEventListener('click', () => { album = ch.dataset.filter; page = 1; render(false); }));
    render(false);

    // Lightbox
    const dlg = document.getElementById('gxDialog');
    cards.forEach(c => c.addEventListener('click', () => {
        const img = document.getElementById('gxImg');
        img.src = c.dataset.src; img.alt = c.dataset.judul;
        document.getElementById('gxTag').textContent   = c.dataset.label;
        document.getElementById('gxTitle').textContent = c.dataset.judul;
        document.getElementById('gxCap').textContent   = c.dataset.caption;
        dlg.showModal();
    }));
    document.getElementById('gxClose').addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php';
