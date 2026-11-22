<?php
/**
 * Dashboard panel admin.
 * Isi halaman ditangkap ke $data['content'], lalu dirender di dalam layout admin.
 */

ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / Beranda
</div>

<div class="page-head">
    <div>
        <h1>Selamat datang, <?= htmlspecialchars((string) ($data['user']['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="desc"><?= date('l, j F Y') ?> &middot; Tahun Ajaran 2026/2027 &middot; Semester Ganjil &middot; Pusat Kendali Terpadu</p>
    </div>
    <div class="actions">
        <a class="btn btn-outline" href="<?= base_url() ?>" target="_blank">Lihat Website ↗</a>
        <a class="btn btn-primary" href="<?= base_url('admin/presensi') ?>">Rekap Presensi</a>
    </div>
</div>

<div class="grid-4" style="margin-bottom:18px">
    <a href="<?= base_url('admin/pengguna') ?>" class="card" style="text-decoration:none; color:inherit; transition:transform .15s ease, box-shadow .15s ease;">
        <div class="stat">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M17 11h5M19.5 8.5v5"/></svg>
            </div>
            <div>
                <small>Pengguna Terdaftar</small>
                <strong><?= number_format((int) $data['stats']['pengguna'], 0, ',', '.') ?></strong>
                <div class="hint"><?= (int) $data['stats']['guru'] ?> guru &middot; <?= (int) $data['stats']['siswa'] ?> siswa</div>
            </div>
        </div>
    </a>

    <a href="<?= base_url('admin/presensi') ?>" class="card" style="text-decoration:none; color:inherit; transition:transform .15s ease, box-shadow .15s ease;">
        <div class="stat">
            <div class="stat-icon amber">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <div>
                <small>Presensi Hari Ini</small>
                <strong><?= number_format((int) $data['stats']['presensi'], 0, ',', '.') ?> guru</strong>
                <div class="hint"><?= (int) $data['stats']['presensi_mobile'] ?> via Mobile App</div>
            </div>
        </div>
    </a>

    <a href="<?= base_url('admin/berita') ?>" class="card" style="text-decoration:none; color:inherit; transition:transform .15s ease, box-shadow .15s ease;">
        <div class="stat">
            <div class="stat-icon blue">
                <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M7 9h7M7 13h10M7 17h5"/></svg>
            </div>
            <div>
                <small>Berita Terpublikasi</small>
                <strong><?= number_format((int) $data['stats']['berita'], 0, ',', '.') ?></strong>
                <div class="hint">Warta resmi aktif di web</div>
            </div>
        </div>
    </a>

    <a href="<?= base_url('admin/kontak') ?>" class="card" style="text-decoration:none; color:inherit; transition:transform .15s ease, box-shadow .15s ease;">
        <div class="stat">
            <div class="stat-icon" style="background:#E9F9EF; color:#15803D;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div>
                <small>Kotak Masuk Pesan</small>
                <strong><?= number_format((int) ($data['stats']['pesan_baru'] ?? 0), 0, ',', '.') ?> baru</strong>
                <div class="hint">Total <?= number_format((int) ($data['stats']['pesan_total'] ?? 0), 0, ',', '.') ?> pesan masuk</div>
            </div>
        </div>
    </a>
</div>

<!-- Kartu Kolaborasi Admin & Mobile App -->
<div class="card" style="margin-bottom:18px;border-left:4px solid var(--green)">
    <div class="card-head">
        <div>
            <h3 style="display:flex;align-items:center;gap:8px">
                <span style="font-size:18px">📱</span> Status Kolaborasi Admin &amp; Aplikasi Mobile MADRASAHKU
            </h3>
            <p class="muted" style="margin:4px 0 0;font-size:12.5px">
                Platform backend dan panel admin ini adalah sumber data tunggal (single source of truth) untuk aplikasi Flutter guru dan siswa.
            </p>
        </div>
        <div class="actions">
            <a href="<?= base_url('api/v1/health') ?>" target="_blank" class="pill green plain" style="text-decoration:none">
                ● REST API v1 Online
            </a>
        </div>
    </div>
    <div class="card-pad" style="padding-top:12px">
        <div class="grid grid-3">
            <div style="background:#F9FAFB;padding:14px;border-radius:10px;border:1px solid var(--border)">
                <div style="font-size:11.5px;font-weight:700;color:var(--gray);text-transform:uppercase;margin-bottom:6px">1. Akun Pengguna</div>
                <div style="font-size:13.5px;font-weight:600;color:var(--text)">Dikelola Operator di Admin</div>
                <p style="font-size:11.5px;color:var(--gray);margin:6px 0 10px;line-height:1.5">
                    Tidak ada pendaftaran mandiri. Guru &amp; siswa dibuat di admin dan langsung bisa login di aplikasi.
                </p>
                <a href="<?= base_url('admin/pengguna') ?>" class="btn btn-outline btn-sm" style="width:100%;text-align:center">Kelola Akun &rarr;</a>
            </div>

            <div style="background:#F9FAFB;padding:14px;border-radius:10px;border:1px solid var(--border)">
                <div style="font-size:11.5px;font-weight:700;color:var(--gray);text-transform:uppercase;margin-bottom:6px">2. Presensi Guru</div>
                <div style="font-size:13.5px;font-weight:600;color:var(--text)">Check-in Mobile &amp; Koreksi Web</div>
                <p style="font-size:11.5px;color:var(--gray);margin:6px 0 10px;line-height:1.5">
                    Guru check-in di ponsel berdasarkan waktu server &amp; geofence. Operator mengoreksi jika izin/dinas.
                </p>
                <a href="<?= base_url('admin/presensi') ?>" class="btn btn-outline btn-sm" style="width:100%;text-align:center">Pantau Presensi &rarr;</a>
            </div>

            <div style="background:#F9FAFB;padding:14px;border-radius:10px;border:1px solid var(--border)">
                <div style="font-size:11.5px;font-weight:700;color:var(--gray);text-transform:uppercase;margin-bottom:6px">3. Parameter Geofence</div>
                <div style="font-size:13.5px;font-weight:600;color:var(--text)">Pengaturan Toleransi &amp; GPS</div>
                <p style="font-size:11.5px;color:var(--gray);margin:6px 0 10px;line-height:1.5">
                    Jam masuk, toleransi terlambat, jam pulang, dan titik koordinat radius madrasah terpusat.
                </p>
                <a href="<?= base_url('admin/pengaturan?tab=aplikasi') ?>" class="btn btn-outline btn-sm" style="width:100%;text-align:center">Setel Parameter &rarr;</a>
            </div>
        </div>
    </div>
</div>

<?php
$data['content'] = ob_get_clean();

require_once __DIR__ . '/../layouts/admin.php';
