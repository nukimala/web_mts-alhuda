<?php
$date = (string) ($data['date'] ?? date('Y-m-d'));
$todayDate = (string) ($data['today_date'] ?? date('Y-m-d'));
$isToday = (bool) ($data['is_today'] ?? ($date === $todayDate));
$prevDate = (string) ($data['prev_date'] ?? date('Y-m-d', strtotime($date . ' -1 day')));
$nextDate = (string) ($data['next_date'] ?? date('Y-m-d', strtotime($date . ' +1 day')));
$dateLabel = (string) ($data['date_label'] ?? date('d F Y', strtotime($date)));
$dateShortLabel = (string) ($data['date_short_label'] ?? date('d F Y', strtotime($date)));
$totalTeachers = (int) ($data['total_teachers'] ?? 0);
$currentStatus = (string) ($data['status'] ?? '');
$currentQ = (string) ($data['q'] ?? '');
$records = $data['records'] ?? [];
$teachers = $data['teachers'] ?? $data['guru'] ?? [];

$statHadir = (int) ($data['stat_hadir'] ?? 0);
$statTerlambat = (int) ($data['stat_terlambat'] ?? 0);
$statIzin = (int) ($data['stat_izin'] ?? 0);
$statBelum = (int) ($data['stat_belum'] ?? 0);

$recordedCount = min($totalTeachers, $statHadir + $statTerlambat + $statIzin);
$recordedPct = $totalTeachers > 0 ? (int) round(($recordedCount / $totalTeachers) * 100) : 0;
$hadirPct = $totalTeachers > 0 ? round(($statHadir / $totalTeachers) * 100, 1) : 0;

$csvBaseUrl = base_url('admin/presensi?date=' . urlencode($date) . '&export=csv');

ob_start();
?>

<style>
.pr-wrap {
    --pr-ink: #0F172A;
    --pr-sub: #475569;
    --pr-body: #334155;
    --pr-green: #15803D;
    --pr-green-deep: #166534;
    --pr-green-soft: #ECFDF5;
    --pr-green-line: #A7F3D0;
    --pr-line: #CBD5E1;
    --pr-line-soft: #E2E8F0;
}

.pr-head-actions .btn,
.pr-date-btn,
.pr-preset-btn,
.pr-row-btn,
.pr-filter-reset {
    min-height: 44px;
}

.pr-today-chip {
    display: inline-flex;
    align-items: center;
    padding: 2px 9px;
    border-radius: 999px;
    background: #DCFCE7;
    color: #166534;
    font-size: 12px;
    font-weight: 700;
    margin-inline-start: 6px;
}

/* Strip Navigasi Tanggal & Progres Harian */
.pr-date-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    padding: 14px 18px;
    margin-bottom: 18px;
    background: #FFFFFF;
    border: 1px solid var(--pr-line-soft);
    border-radius: 14px;
    box-shadow: var(--shadow-xs);
}

.pr-date-nav {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.pr-date-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 13px;
    border-radius: 10px;
    border: 1px solid var(--pr-line);
    background: #FFFFFF;
    color: var(--pr-ink);
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.pr-date-btn:hover {
    border-color: var(--pr-green);
    background: var(--pr-green-soft);
    color: var(--pr-green-deep);
}

.pr-date-btn svg {
    width: 15px;
    height: 15px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.pr-date-form {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 0;
}

.pr-date-input {
    min-height: 44px;
    padding: 8px 12px;
    border: 1px solid var(--pr-line);
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    color: var(--pr-ink);
    background: #FFFFFF;
}

.pr-date-input:focus-visible {
    outline: 2px solid var(--pr-green);
    outline-offset: 2px;
}

.pr-progress-box {
    flex: 1 1 260px;
    max-width: 400px;
}

.pr-progress-meta {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
    font-size: 12.5px;
    color: var(--pr-sub);
    margin-bottom: 6px;
}

.pr-progress-meta strong {
    color: var(--pr-ink);
    font-weight: 800;
}

.pr-progress-track {
    height: 8px;
    border-radius: 999px;
    background: #E2E8F0;
    overflow: hidden;
}

.pr-progress-fill {
    height: 100%;
    border-radius: 999px;
    background: var(--pr-green);
    transition: width 0.2s ease;
}

/* Kartu Statistik Interaktif sebagai Penyaring Cepat */
.pr-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.pr-stat-card {
    appearance: none;
    width: 100%;
    text-align: left;
    padding: 16px 18px;
    border-radius: 14px;
    border: 1.5px solid var(--pr-line-soft);
    background: #FFFFFF;
    box-shadow: var(--shadow-xs);
    display: flex;
    align-items: flex-start;
    gap: 14px;
    cursor: pointer;
    transition: border-color 0.15s ease, background-color 0.15s ease, transform 0.12s ease;
}

.pr-stat-card:hover {
    border-color: var(--pr-green);
    background: #FAFFFC;
}

.pr-stat-card:active {
    transform: translateY(1px);
}

.pr-stat-card[aria-pressed="true"] {
    border-color: var(--pr-green);
    background: var(--pr-green-soft);
    box-shadow: 0 0 0 2px rgba(21, 128, 61, 0.14);
}

.pr-stat-card small {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: var(--pr-sub);
    margin-bottom: 2px;
}

.pr-stat-card strong {
    display: block;
    font-size: 24px;
    font-weight: 800;
    line-height: 1.2;
    color: var(--pr-ink);
}

.pr-stat-card .pr-stat-hint {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: var(--pr-sub);
}

/* Toolbar Pencarian & Filter Instan */
.pr-search-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--pr-line-soft);
    background: #FFFFFF;
}

.pr-search-field {
    flex: 1 1 240px;
    max-width: 360px;
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 12px;
    border: 1px solid var(--pr-line);
    border-radius: 10px;
    background: #FFFFFF;
}

.pr-search-field:focus-within {
    border-color: var(--pr-green);
    box-shadow: 0 0 0 2px rgba(21, 128, 61, 0.12);
}

.pr-search-field svg {
    width: 16px;
    height: 16px;
    stroke: var(--pr-sub);
    fill: none;
    stroke-width: 2;
    flex: 0 0 auto;
}

.pr-search-field input {
    width: 100%;
    min-height: 42px;
    border: 0;
    outline: 0;
    background: transparent;
    font-size: 13.5px;
    color: var(--pr-ink);
}

.pr-select-status {
    min-height: 44px;
    padding: 8px 12px;
    border: 1px solid var(--pr-line);
    border-radius: 10px;
    background: #FFFFFF;
    color: var(--pr-ink);
    font-size: 13.5px;
    font-weight: 600;
}

.pr-filter-meta {
    margin-inline-start: auto;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--pr-sub);
}

/* Region Tabel Responsif (`responsive-table` guide) */
.pr-table-region {
    overflow: auto;
    max-inline-size: 100%;
    max-block-size: min(720px, 82vh);
    container-type: inline-size;
}

.pr-table {
    --lbl-masuk: "Jam Masuk";
    --lbl-pulang: "Jam Pulang";
    --lbl-status: "Status";
    --lbl-sumber: "Sumber";
    --lbl-ket: "Keterangan";
}

.pr-table caption {
    padding: 10px 18px;
    text-align: left;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--pr-sub);
    background: #F8FAFC;
    border-bottom: 1px solid var(--pr-line-soft);
}

.pr-table thead {
    position: sticky;
    inset-block-start: 0;
    z-index: 2;
}

.pr-table thead th {
    background: #F8FAFC;
    color: var(--pr-sub);
    font-size: 12px;
}

.pr-table tbody th {
    font-weight: 700;
    text-align: left;
    padding: 12px 16px;
    border-bottom: 1px solid #F1F5F9;
    background: #FFFFFF;
    vertical-align: middle;
}

.pr-table tbody tr:hover th,
.pr-table tbody tr:hover td {
    background: #F8FAFC;
}

.pr-source-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.25;
}

.pr-source-badge svg {
    width: 14px;
    height: 14px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    flex: 0 0 auto;
}

.pr-source-badge.mobile {
    background: #DCFCE7;
    color: #166534;
}

.pr-source-badge.operator {
    background: #FFFBEB;
    color: #92400E;
    border: 1px solid #FDE68A;
}

.pr-source-badge.system {
    background: #F1F5F9;
    color: #334155;
}

.pr-sub-meta {
    display: block;
    margin-top: 3px;
    font-size: 12px;
    color: var(--pr-sub);
}

.pr-empty-val {
    font-size: 12.5px;
    color: var(--pr-sub);
}

.pr-row-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 8px;
    border: 1px solid var(--pr-line);
    background: #FFFFFF;
    color: var(--pr-ink);
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    transition: border-color 0.15s ease, background-color 0.15s ease, color 0.15s ease;
}

.pr-row-btn:hover {
    border-color: var(--pr-green);
    background: var(--pr-green-soft);
    color: var(--pr-green-deep);
}

.pr-row-btn svg {
    width: 14px;
    height: 14px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

@container (width < 680px) {
    .pr-table,
    .pr-table thead,
    .pr-table tbody,
    .pr-table tr,
    .pr-table th,
    .pr-table td {
        display: block;
    }

    .pr-table thead {
        display: none;
    }

    .pr-table tbody tr {
        margin: 12px;
        border: 1px solid var(--pr-line);
        border-radius: 12px;
        overflow: hidden;
    }

    .pr-table tbody th {
        background: #F8FAFC;
        border-bottom: 1px solid var(--pr-line-soft);
    }

    .pr-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
    }

    .pr-table tbody td:nth-child(2)::before { content: var(--lbl-masuk); font-weight: 700; color: var(--pr-sub); }
    .pr-table tbody td:nth-child(3)::before { content: var(--lbl-pulang); font-weight: 700; color: var(--pr-sub); }
    .pr-table tbody td:nth-child(4)::before { content: var(--lbl-status); font-weight: 700; color: var(--pr-sub); }
    .pr-table tbody td:nth-child(5)::before { content: var(--lbl-sumber); font-weight: 700; color: var(--pr-sub); }
    .pr-table tbody td:nth-child(6)::before { content: var(--lbl-ket); font-weight: 700; color: var(--pr-sub); }
}

/* Native Modal `<dialog>` (`dialog` & `prevent-page-scroll-under-modal-dialog` guides) */
.pr-dialog {
    width: min(540px, calc(100vw - 28px));
    max-height: calc(100vh - 32px);
    margin: auto;
    padding: 0;
    border: 1px solid var(--pr-line);
    border-radius: 16px;
    background: #FFFFFF;
    color: var(--pr-ink);
    box-shadow: var(--shadow);
}

.pr-dialog:modal {
    overscroll-behavior: contain;
}

.pr-dialog::backdrop {
    overflow: hidden;
    overscroll-behavior: contain;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(2px);
}

.pr-dialog-head {
    padding: 16px 20px;
    border-bottom: 1px solid var(--pr-line-soft);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.pr-dialog-title {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: var(--pr-ink);
}

.pr-dialog-close {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    border: 1px solid var(--pr-line-soft);
    background: #FFFFFF;
    color: var(--pr-sub);
    display: inline-grid;
    place-items: center;
    cursor: pointer;
}

.pr-dialog-close:hover {
    border-color: var(--pr-green);
    color: var(--pr-ink);
    background: var(--pr-green-soft);
}

.pr-dialog-body {
    padding: 20px;
}

.pr-dialog-body input,
.pr-dialog-body select {
    min-height: 44px;
    font-size: max(14px, 1em);
}

.pr-dialog-body input:user-invalid,
.pr-dialog-body select:user-invalid {
    border-color: #DC2626;
}

.pr-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 6px;
}

.pr-preset-btn {
    padding: 6px 11px;
    border-radius: 8px;
    border: 1px solid var(--pr-line);
    background: #F8FAFC;
    color: var(--pr-body);
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.pr-preset-btn:hover {
    border-color: var(--pr-green);
    background: var(--pr-green-soft);
    color: var(--pr-green-deep);
}

.pr-nonpresent-note {
    padding: 12px 14px;
    margin-bottom: 16px;
    border-radius: 10px;
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    color: #1E40AF;
    font-size: 12.5px;
    font-weight: 600;
    line-height: 1.5;
}

.pr-time-err {
    margin-top: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #B91C1C;
}

.pr-dialog-foot {
    padding: 14px 20px;
    background: #F8FAFC;
    border-top: 1px solid var(--pr-line-soft);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.pr-dialog-foot .btn {
    min-height: 44px;
}

@media (max-width: 1120px) {
    .pr-stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .pr-stats-grid {
        grid-template-columns: 1fr;
    }

    .pr-filter-meta {
        margin-inline-start: 0;
        width: 100%;
        justify-content: space-between;
    }
}

@media (prefers-reduced-motion: reduce) {
    .pr-wrap *,
    .pr-wrap *::before,
    .pr-wrap *::after {
        transition: none !important;
        animation: none !important;
    }
}
</style>

<div class="pr-wrap">
    <div class="breadcrumb">
        <a href="<?= base_url('admin') ?>">Panel Admin</a>
        <span aria-hidden="true">/</span>
        <b>Presensi Guru</b>
    </div>

    <div class="page-head">
        <div>
            <h1>Rekap &amp; Koreksi Presensi Guru</h1>
            <p>
                <b id="prHeaderDateLabel"><?= e($dateLabel) ?></b>
                <span id="prTodayChip" class="pr-today-chip" <?= !$isToday ? 'hidden' : '' ?>>Hari Ini</span>
                &middot; <?= $totalTeachers ?> guru aktif &middot; Terintegrasi aplikasi MADRASAHKU
            </p>
        </div>
        <div class="actions pr-head-actions">
            <a href="<?= e($csvBaseUrl) ?>" id="btnExportCsv" class="btn btn-outline">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 21h16"/></svg>
                Unduh CSV
            </a>
            <button type="button" id="btnOpenNewPresensi" class="btn btn-primary">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Catat / Koreksi Presensi
            </button>
        </div>
    </div>

    <?php if (!empty($data['error'])): ?>
        <div class="notice error" role="alert">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
            <div><?= e($data['error']) ?></div>
        </div>
    <?php endif; ?>

    <!-- Navigasi Tanggal Cepat & Ringkasan Progres Harian -->
    <div class="pr-date-bar" id="prDateBar">
        <div class="pr-date-nav">
            <a href="<?= base_url('admin/presensi?date=' . urlencode($prevDate)) ?>"
               id="prPrevDateBtn"
               data-nav-date="<?= e($prevDate) ?>"
               class="pr-date-btn"
               aria-label="Lihat tanggal sebelumnya">
                <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                Sebelumnya
            </a>

            <form method="get" action="<?= base_url('admin/presensi') ?>" id="prDateForm" class="pr-date-form" aria-label="Pilih tanggal rekap presensi">
                <label for="prDatePicker" style="position:absolute;clip-path:inset(50%);width:1px;height:1px;overflow:hidden;">Tanggal Presensi</label>
                <input type="date" id="prDatePicker" name="date" value="<?= e($date) ?>" class="pr-date-input">
                <noscript><button type="submit" class="pr-date-btn">Tampilkan</button></noscript>
            </form>

            <a href="<?= base_url('admin/presensi?date=' . urlencode($nextDate)) ?>"
               id="prNextDateBtn"
               data-nav-date="<?= e($nextDate) ?>"
               class="pr-date-btn"
               aria-label="Lihat tanggal berikutnya">
                Berikutnya
                <svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </a>

            <a href="<?= base_url('admin/presensi?date=' . urlencode($todayDate)) ?>"
               id="prBackTodayBtn"
               data-nav-date="<?= e($todayDate) ?>"
               class="btn btn-outline"
               style="min-height:44px;"
               <?= $isToday ? 'hidden' : '' ?>>
                Kembali ke Hari Ini
            </a>
        </div>

        <div class="pr-progress-box">
            <div class="pr-progress-meta">
                <span>Progres pencatatan harian</span>
                <strong id="prProgressText"><?= $recordedCount ?> / <?= $totalTeachers ?> guru (<?= $recordedPct ?>%)</strong>
            </div>
            <div class="pr-progress-track" id="prProgressBar" role="progressbar" aria-label="Progres pencatatan presensi harian" aria-valuenow="<?= $recordedPct ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="pr-progress-fill" id="prProgressFill" style="width: <?= $recordedPct ?>%;"></div>
            </div>
        </div>
    </div>

    <!-- 4 Kartu Statistik (Klik untuk Filter Instan) -->
    <div class="pr-stats-grid" role="group" aria-label="Ringkasan status presensi, klik kartu untuk menyaring daftar">
        <button type="button" class="pr-stat-card" data-card-filter="hadir" aria-pressed="<?= $currentStatus === 'hadir' ? 'true' : 'false' ?>">
            <div class="stat-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></div>
            <div>
                <small>Hadir Tepat Waktu</small>
                <strong id="prStatHadir"><?= $statHadir ?></strong>
                <span class="pr-stat-hint" id="prStatHadirHint"><?= $hadirPct ?>% dari seluruh guru</span>
            </div>
        </button>

        <button type="button" class="pr-stat-card" data-card-filter="terlambat" aria-pressed="<?= $currentStatus === 'terlambat' ? 'true' : 'false' ?>">
            <div class="stat-icon amber" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
            <div>
                <small>Terlambat</small>
                <strong id="prStatTerlambat"><?= $statTerlambat ?></strong>
                <span class="pr-stat-hint">Lewat batas jam masuk</span>
            </div>
        </button>

        <button type="button" class="pr-stat-card" data-card-filter="izin_group" aria-pressed="<?= $currentStatus === 'izin_group' ? 'true' : 'false' ?>">
            <div class="stat-icon blue" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg></div>
            <div>
                <small>Izin / Sakit / Dinas</small>
                <strong id="prStatIzin"><?= $statIzin ?></strong>
                <span class="pr-stat-hint">Keterangan resmi tercatat</span>
            </div>
        </button>

        <button type="button" class="pr-stat-card" data-card-filter="belum_group" aria-pressed="<?= in_array($currentStatus, ['belum', 'belum_group'], true) ? 'true' : 'false' ?>">
            <div class="stat-icon red" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg></div>
            <div>
                <small>Belum Check-in / Alpa</small>
                <strong id="prStatBelum"><?= $statBelum ?></strong>
                <span class="pr-stat-hint">Klik untuk saring guru ini</span>
            </div>
        </button>
    </div>

    <div class="card">
        <search aria-label="Saring daftar presensi guru">
            <div class="pr-search-bar">
                <div class="pr-search-field">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <label for="prSearchInput" style="position:absolute;clip-path:inset(50%);width:1px;height:1px;overflow:hidden;">Cari nama atau NIP guru</label>
                    <input type="search"
                           id="prSearchInput"
                           value="<?= e($currentQ) ?>"
                           placeholder="Cari nama atau NIP guru..."
                           autocorrect="off"
                           autocapitalize="none"
                           spellcheck="false"
                           enterkeyhint="search">
                </div>

                <div>
                    <label for="prStatusSelect" style="position:absolute;clip-path:inset(50%);width:1px;height:1px;overflow:hidden;">Status kehadiran</label>
                    <select id="prStatusSelect" class="pr-select-status">
                        <option value="" <?= $currentStatus === '' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="hadir" <?= $currentStatus === 'hadir' ? 'selected' : '' ?>>Hadir Tepat Waktu</option>
                        <option value="terlambat" <?= $currentStatus === 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                        <option value="izin_group" <?= $currentStatus === 'izin_group' ? 'selected' : '' ?>>Semua Izin / Sakit / Dinas</option>
                        <option value="izin" <?= $currentStatus === 'izin' ? 'selected' : '' ?>>Izin</option>
                        <option value="sakit" <?= $currentStatus === 'sakit' ? 'selected' : '' ?>>Sakit</option>
                        <option value="dinas" <?= $currentStatus === 'dinas' ? 'selected' : '' ?>>Dinas Luar</option>
                        <option value="alpa" <?= $currentStatus === 'alpa' ? 'selected' : '' ?>>Alpa</option>
                        <option value="belum" <?= $currentStatus === 'belum' ? 'selected' : '' ?>>Belum Check-in</option>
                        <option value="belum_group" <?= $currentStatus === 'belum_group' ? 'selected' : '' ?>>Belum Check-in &amp; Alpa</option>
                    </select>
                </div>

                <button type="button" id="prResetFilterBtn" class="btn btn-ghost pr-filter-reset" <?= ($currentStatus === '' && $currentQ === '') ? 'hidden' : '' ?>>
                    Reset Filter
                </button>

                <div class="pr-filter-meta" aria-live="polite">
                    <span id="prCountBadge">Menampilkan <?= count($records) ?> dari <?= count($records) ?> guru</span>
                </div>
            </div>
        </search>

        <div tabindex="0" role="region" aria-labelledby="presensi-table-caption" class="pr-table-region">
            <table class="data pr-table">
                <caption id="presensi-table-caption">
                    Daftar kehadiran guru aktif pada <?= e($dateLabel) ?>
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Guru Pengampu</th>
                        <th scope="col">Jam Masuk</th>
                        <th scope="col">Jam Pulang</th>
                        <th scope="col">Status</th>
                        <th scope="col">Sumber Pencatatan</th>
                        <th scope="col">Keterangan</th>
                        <th scope="col" style="width: 118px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="prTableBody">
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="7" style="padding: 36px 16px; text-align: center; color: var(--pr-sub);">
                            <b>Belum ada data guru aktif yang terdaftar pada sistem.</b>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                        <?php
                        $st = (string) ($r['status'] ?? '');
                        $filterKey = $st !== '' ? $st : 'belum';
                        $noteText = trim((string) ($r['note'] ?? ''));
                        $teacherName = (string) ($r['teacher_name'] ?? '-');
                        $nip = trim((string) ($r['nip'] ?? ''));
                        $searchBlob = mb_strtolower($teacherName . ' ' . $nip, 'UTF-8');
                        $hasRecord = !empty($r['id']);
                        $checkInVal = !empty($r['check_in_at']) ? date('H:i', strtotime((string) $r['check_in_at'])) : '';
                        $checkOutVal = !empty($r['check_out_at']) ? date('H:i', strtotime((string) $r['check_out_at'])) : '';
                        $distanceMeters = isset($r['distance_meters']) && $r['distance_meters'] !== null ? (int) round((float) $r['distance_meters']) : null;

                        $rowPayload = json_encode([
                            'teacher_id' => (int) ($r['teacher_id'] ?? 0),
                            'teacher_name' => $teacherName,
                            'status' => $st !== '' ? $st : 'hadir',
                            'has_record' => $hasRecord,
                            'check_in' => $checkInVal,
                            'check_out' => $checkOutVal,
                            'note' => $noteText,
                        ], JSON_UNESCAPED_UNICODE);
                        ?>
                        <tr data-presensi-row
                            data-status="<?= e($filterKey) ?>"
                            data-search="<?= e($searchBlob) ?>">
                            <th scope="row">
                                <div class="cell-flex">
                                    <div class="avatar" aria-hidden="true"><?= e(strtoupper(mb_substr($teacherName, 0, 2))) ?></div>
                                    <div>
                                        <div class="t-title"><?= e($teacherName) ?></div>
                                        <div class="t-sub"><?= $nip !== '' ? 'NIP: ' . e($nip) : 'Belum ada NIP' ?></div>
                                    </div>
                                </div>
                            </th>
                            <td>
                                <?php if ($checkInVal !== ''): ?>
                                    <b><?= e(str_replace(':', '.', $checkInVal)) ?> WIB</b>
                                <?php else: ?>
                                    <span class="pr-empty-val">Belum tercatat</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($checkOutVal !== ''): ?>
                                    <b><?= e(str_replace(':', '.', $checkOutVal)) ?> WIB</b>
                                <?php else: ?>
                                    <span class="pr-empty-val">Belum tercatat</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($st === 'hadir'): ?>
                                    <span class="pill green">Hadir</span>
                                <?php elseif ($st === 'terlambat'): ?>
                                    <span class="pill amber">Terlambat</span>
                                <?php elseif (in_array($st, ['izin', 'sakit', 'dinas'], true)): ?>
                                    <span class="pill blue"><?= e(ucfirst($st)) ?></span>
                                <?php elseif ($st === 'alpa'): ?>
                                    <span class="pill red">Alpa</span>
                                <?php else: ?>
                                    <span class="pill gray" style="color:#334155;">Belum Check-in</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (($r['method'] ?? '') === 'self'): ?>
                                    <span class="pr-source-badge mobile">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M12 18h.01"/></svg>
                                        Mobile App
                                    </span>
                                    <?php if ($distanceMeters !== null): ?>
                                        <span class="pr-sub-meta">Jarak GPS: <?= $distanceMeters ?> m</span>
                                    <?php endif; ?>
                                <?php elseif (($r['method'] ?? '') === 'operator'): ?>
                                    <span class="pr-source-badge operator">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                                        Operator Web
                                    </span>
                                    <?php if (!empty($r['verified_by_name'])): ?>
                                        <span class="pr-sub-meta">Oleh: <?= e($r['verified_by_name']) ?></span>
                                    <?php endif; ?>
                                <?php elseif (($r['method'] ?? '') === 'system'): ?>
                                    <span class="pr-source-badge system">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                        Sistem Otomatis
                                    </span>
                                <?php else: ?>
                                    <span class="pr-empty-val">Belum tercatat</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($noteText !== ''): ?>
                                    <span style="color: var(--pr-body); font-size: 13px;"><?= e($noteText) ?></span>
                                <?php else: ?>
                                    <span class="pr-empty-val">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button"
                                        class="pr-row-btn"
                                        data-edit-presensi="<?= e((string) $rowPayload) ?>"
                                        aria-label="<?= $hasRecord ? 'Koreksi' : 'Catat' ?> presensi <?= e($teacherName) ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                    <?= $hasRecord ? 'Koreksi' : 'Catat' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="prNoFilterMatchRow" hidden>
                        <td colspan="7" style="padding: 36px 16px; text-align: center; color: var(--pr-sub);">
                            <b>Tidak ada guru yang cocok dengan filter atau kata kunci pencarian.</b>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Native <dialog> Koreksi Presensi -->
    <dialog id="dialogKoreksiPresensi" class="pr-dialog" closedby="any" aria-labelledby="dialog-koreksi-title">
        <div class="pr-dialog-head">
            <h1 id="dialog-koreksi-title" class="pr-dialog-title">Pencatatan &amp; Koreksi Presensi</h1>
            <button type="button" id="btnCloseDialog" class="pr-dialog-close" aria-label="Tutup dialog koreksi">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="formKoreksiPresensi" method="post" action="<?= base_url('admin/presensi/correct') ?>">
            <?= Csrf::field() ?>
            <input type="hidden" id="kor_attendance_date" name="attendance_date" value="<?= e($date) ?>">

            <div class="pr-dialog-body">
                <div class="field">
                    <label for="kor_teacher_id">Nama Guru <span class="req" aria-hidden="true">*</span></label>
                    <select id="kor_teacher_id" name="teacher_id" required>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= (int) $t['teacher_id'] ?>"><?= e($t['teacher_name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-row" style="margin-bottom: 16px;">
                    <div class="field" style="margin-bottom: 0;">
                        <label for="kor_date_display">Tanggal Presensi</label>
                        <input type="text" id="kor_date_display" value="<?= e($dateShortLabel) ?>" readonly style="background: #F8FAFC; color: var(--pr-sub);">
                    </div>

                    <div class="field" style="margin-bottom: 0;">
                        <label for="kor_status">Status Kehadiran <span class="req" aria-hidden="true">*</span></label>
                        <select id="kor_status" name="status" required autofocus>
                            <option value="hadir">Hadir (Tepat Waktu)</option>
                            <option value="terlambat">Terlambat</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="dinas">Dinas Luar / Pelatihan</option>
                            <option value="alpa">Alpa / Tanpa Keterangan</option>
                        </select>
                    </div>
                </div>

                <div id="kor_nonpresent_notice" class="pr-nonpresent-note" hidden>
                    Jam masuk dan jam pulang dikosongkan otomatis karena status yang dipilih adalah ketidakhadiran di madrasah (Izin, Sakit, Dinas Luar, atau Alpa).
                </div>

                <div id="kor_time_section">
                    <div class="field-row">
                        <div class="field">
                            <label for="kor_check_in">Jam Masuk (WIB)</label>
                            <input type="time" id="kor_check_in" name="check_in_time" value="07:00">
                            <div class="pr-presets" aria-label="Pilihan cepat jam masuk">
                                <button type="button" class="pr-preset-btn" data-time-target="kor_check_in" data-time-val="06:45">06.45</button>
                                <button type="button" class="pr-preset-btn" data-time-target="kor_check_in" data-time-val="07:00">07.00</button>
                                <button type="button" class="pr-preset-btn" data-time-target="kor_check_in" data-time-val="now">Sekarang</button>
                            </div>
                        </div>

                        <div class="field">
                            <label for="kor_check_out">Jam Pulang (WIB)</label>
                            <input type="time" id="kor_check_out" name="check_out_time" aria-describedby="kor_time_err">
                            <div class="pr-presets" aria-label="Pilihan cepat jam pulang">
                                <button type="button" class="pr-preset-btn" data-time-target="kor_check_out" data-time-val="13:05">13.05</button>
                                <button type="button" class="pr-preset-btn" data-time-target="kor_check_out" data-time-val="14:00">14.00</button>
                                <button type="button" class="pr-preset-btn" data-time-target="kor_check_out" data-time-val="now">Sekarang</button>
                            </div>
                        </div>
                    </div>
                    <div id="kor_time_err" class="pr-time-err" role="status" aria-live="polite" hidden></div>
                </div>

                <div class="field" style="margin-bottom: 0;">
                    <label for="kor_note">Catatan / Alasan Koreksi</label>
                    <input type="text"
                           id="kor_note"
                           name="note"
                           maxlength="255"
                           placeholder="Contoh: Mengikuti MGMP Kemenag atau izin sakit disertai surat">
                </div>
            </div>

            <div class="pr-dialog-foot">
                <button type="button" id="btnCancelDialog" class="btn btn-outline">Batal</button>
                <button type="submit" id="btnSubmitPresensi" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </dialog>
</div>

<script>
(function () {
    var BASE_PRESENSI_URL = <?= json_encode(base_url('admin/presensi'), JSON_UNESCAPED_UNICODE) ?>;
    var CURRENT_DATE = <?= json_encode($date, JSON_UNESCAPED_UNICODE) ?>;

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function createSourceIcon(type) {
        var ns = 'http://www.w3.org/2000/svg';
        var svg = document.createElementNS(ns, 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('aria-hidden', 'true');

        if (type === 'self') {
            var r1 = document.createElementNS(ns, 'rect');
            r1.setAttribute('x', '7'); r1.setAttribute('y', '2');
            r1.setAttribute('width', '10'); r1.setAttribute('height', '20'); r1.setAttribute('rx', '2');
            var p1 = document.createElementNS(ns, 'path');
            p1.setAttribute('d', 'M12 18h.01');
            svg.appendChild(r1); svg.appendChild(p1);
        } else if (type === 'operator') {
            var r2 = document.createElementNS(ns, 'rect');
            r2.setAttribute('x', '2'); r2.setAttribute('y', '3');
            r2.setAttribute('width', '20'); r2.setAttribute('height', '14'); r2.setAttribute('rx', '2');
            var p2 = document.createElementNS(ns, 'path');
            p2.setAttribute('d', 'M8 21h8M12 17v4');
            svg.appendChild(r2); svg.appendChild(p2);
        } else if (type === 'edit') {
            var pe1 = document.createElementNS(ns, 'path');
            pe1.setAttribute('d', 'M12 20h9');
            var pe2 = document.createElementNS(ns, 'path');
            pe2.setAttribute('d', 'M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z');
            svg.appendChild(pe1); svg.appendChild(pe2);
        } else {
            var c = document.createElementNS(ns, 'circle');
            c.setAttribute('cx', '12'); c.setAttribute('cy', '12'); c.setAttribute('r', '9');
            var p3 = document.createElementNS(ns, 'path');
            p3.setAttribute('d', 'M12 7v5l3 2');
            svg.appendChild(c); svg.appendChild(p3);
        }
        return svg;
    }

    // 1. Penyaringan Instan (Kartu Statistik + Dropdown Status + Pencarian Nama/NIP)
    var tableBody = document.getElementById('prTableBody');
    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-presensi-row]'));
    var searchInput = document.getElementById('prSearchInput');
    var statusSelect = document.getElementById('prStatusSelect');
    var resetBtn = document.getElementById('prResetFilterBtn');
    var countBadge = document.getElementById('prCountBadge');
    var noMatchRow = document.getElementById('prNoFilterMatchRow');
    var statCards = Array.prototype.slice.call(document.querySelectorAll('[data-card-filter]'));
    var exportBtn = document.getElementById('btnExportCsv');

    function matchesStatus(rowStatus, selectedFilter) {
        if (!selectedFilter) return true;
        if (selectedFilter === 'izin_group') {
            return rowStatus === 'izin' || rowStatus === 'sakit' || rowStatus === 'dinas';
        }
        if (selectedFilter === 'belum_group') {
            return rowStatus === 'belum' || rowStatus === 'alpa';
        }
        return rowStatus === selectedFilter;
    }

    function applyFilters() {
        var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var st = statusSelect ? statusSelect.value : '';
        var visible = 0;

        rows.forEach(function (tr) {
            var rowSt = tr.getAttribute('data-status') || 'belum';
            var blob = tr.getAttribute('data-search') || '';
            var ok = matchesStatus(rowSt, st) && (q === '' || blob.indexOf(q) !== -1);
            tr.hidden = !ok;
            if (ok) visible++;
        });

        if (noMatchRow) {
            noMatchRow.hidden = (rows.length === 0 || visible > 0);
        }

        if (countBadge) {
            countBadge.textContent = 'Menampilkan ' + visible + ' dari ' + rows.length + ' guru';
        }

        if (resetBtn) {
            resetBtn.hidden = (q === '' && st === '');
        }

        statCards.forEach(function (card) {
            var cf = card.getAttribute('data-card-filter');
            var active = (st === cf) || (cf === 'belum_group' && st === 'belum');
            card.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        // Sinkronkan URL & tautan ekspor CSV dengan tanggal + filter aktif tanpa reload halaman
        var params = new URLSearchParams();
        params.set('date', CURRENT_DATE);
        if (st) params.set('status', st);
        if (q) params.set('q', q);

        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', BASE_PRESENSI_URL + '?' + params.toString());
        }

        if (exportBtn) {
            var csvParams = new URLSearchParams(params.toString());
            csvParams.set('export', 'csv');
            exportBtn.href = BASE_PRESENSI_URL + '?' + csvParams.toString();
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }
    if (statusSelect) {
        statusSelect.addEventListener('change', applyFilters);
    }
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            if (statusSelect) statusSelect.value = '';
            applyFilters();
        });
    }

    statCards.forEach(function (card) {
        card.addEventListener('click', function () {
            if (!statusSelect) return;
            var targetFilter = card.getAttribute('data-card-filter') || '';
            statusSelect.value = (statusSelect.value === targetFilter) ? '' : targetFilter;
            applyFilters();
        });
    });

    // 2. Pergantian Tanggal Otomatis & Instan via Fetch JSON (Tanpa Tombol Tampilkan & Tanpa Reload Penuh)
    var datePicker = document.getElementById('prDatePicker');
    var dateForm = document.getElementById('prDateForm');
    var prevDateBtn = document.getElementById('prPrevDateBtn');
    var nextDateBtn = document.getElementById('prNextDateBtn');
    var backTodayBtn = document.getElementById('prBackTodayBtn');
    var activeDateController = null;

    function buildRecordRow(r) {
        var tr = el('tr');
        tr.setAttribute('data-presensi-row', '');
        tr.setAttribute('data-status', r.filter_key || 'belum');
        tr.setAttribute('data-search', ((r.teacher_name || '') + ' ' + (r.nip || '')).toLowerCase());

        var th = el('th');
        th.setAttribute('scope', 'row');
        var flex = el('div', 'cell-flex');
        var av = el('div', 'avatar', r.initials || 'GR');
        av.setAttribute('aria-hidden', 'true');
        var infoWrap = el('div');
        infoWrap.appendChild(el('div', 't-title', r.teacher_name || '-'));
        infoWrap.appendChild(el('div', 't-sub', r.nip ? ('NIP: ' + r.nip) : 'Belum ada NIP'));
        flex.appendChild(av);
        flex.appendChild(infoWrap);
        th.appendChild(flex);
        tr.appendChild(th);

        var tdIn = el('td');
        if (r.check_in) {
            tdIn.appendChild(el('b', '', r.check_in.replace(':', '.') + ' WIB'));
        } else {
            tdIn.appendChild(el('span', 'pr-empty-val', 'Belum tercatat'));
        }
        tr.appendChild(tdIn);

        var tdOut = el('td');
        if (r.check_out) {
            tdOut.appendChild(el('b', '', r.check_out.replace(':', '.') + ' WIB'));
        } else {
            tdOut.appendChild(el('span', 'pr-empty-val', 'Belum tercatat'));
        }
        tr.appendChild(tdOut);

        var tdStatus = el('td');
        var st = r.status || '';
        if (st === 'hadir') {
            tdStatus.appendChild(el('span', 'pill green', 'Hadir'));
        } else if (st === 'terlambat') {
            tdStatus.appendChild(el('span', 'pill amber', 'Terlambat'));
        } else if (st === 'izin' || st === 'sakit' || st === 'dinas') {
            tdStatus.appendChild(el('span', 'pill blue', st.charAt(0).toUpperCase() + st.slice(1)));
        } else if (st === 'alpa') {
            tdStatus.appendChild(el('span', 'pill red', 'Alpa'));
        } else {
            var pGray = el('span', 'pill gray', 'Belum Check-in');
            pGray.style.color = '#334155';
            tdStatus.appendChild(pGray);
        }
        tr.appendChild(tdStatus);

        var tdMethod = el('td');
        if (r.method === 'self') {
            var bSelf = el('span', 'pr-source-badge mobile');
            bSelf.appendChild(createSourceIcon('self'));
            bSelf.appendChild(document.createTextNode('Mobile App'));
            tdMethod.appendChild(bSelf);
            if (r.distance_meters !== null && r.distance_meters !== undefined) {
                tdMethod.appendChild(el('span', 'pr-sub-meta', 'Jarak GPS: ' + r.distance_meters + ' m'));
            }
        } else if (r.method === 'operator') {
            var bOp = el('span', 'pr-source-badge operator');
            bOp.appendChild(createSourceIcon('operator'));
            bOp.appendChild(document.createTextNode('Operator Web'));
            tdMethod.appendChild(bOp);
            if (r.verified_by_name) {
                tdMethod.appendChild(el('span', 'pr-sub-meta', 'Oleh: ' + r.verified_by_name));
            }
        } else if (r.method === 'system') {
            var bSys = el('span', 'pr-source-badge system');
            bSys.appendChild(createSourceIcon('system'));
            bSys.appendChild(document.createTextNode('Sistem Otomatis'));
            tdMethod.appendChild(bSys);
        } else {
            tdMethod.appendChild(el('span', 'pr-empty-val', 'Belum tercatat'));
        }
        tr.appendChild(tdMethod);

        var tdNote = el('td');
        if (r.note) {
            var spNote = el('span', '', r.note);
            spNote.style.color = 'var(--pr-body)';
            spNote.style.fontSize = '13px';
            tdNote.appendChild(spNote);
        } else {
            tdNote.appendChild(el('span', 'pr-empty-val', '-'));
        }
        tr.appendChild(tdNote);

        var tdAct = el('td');
        var btn = el('button', 'pr-row-btn');
        btn.type = 'button';
        var payload = JSON.stringify({
            teacher_id: r.teacher_id,
            teacher_name: r.teacher_name,
            status: st || 'hadir',
            has_record: Boolean(r.has_record),
            check_in: r.check_in || '',
            check_out: r.check_out || '',
            note: r.note || ''
        });
        btn.setAttribute('data-edit-presensi', payload);
        btn.setAttribute('aria-label', (r.has_record ? 'Koreksi ' : 'Catat ') + 'presensi ' + (r.teacher_name || ''));
        btn.appendChild(createSourceIcon('edit'));
        btn.appendChild(document.createTextNode(r.has_record ? 'Koreksi' : 'Catat'));
        tdAct.appendChild(btn);
        tr.appendChild(tdAct);

        return tr;
    }

    function loadDateRecords(targetDate) {
        if (!targetDate || !/^\d{4}-\d{2}-\d{2}$/.test(targetDate) || targetDate === CURRENT_DATE) {
            return;
        }

        if (!window.fetch) {
            window.location.href = BASE_PRESENSI_URL + '?date=' + encodeURIComponent(targetDate);
            return;
        }

        if (activeDateController) {
            activeDateController.abort();
        }
        activeDateController = typeof AbortController !== 'undefined' ? new AbortController() : null;

        var dateBar = document.getElementById('prDateBar');
        if (dateBar) dateBar.style.opacity = '0.65';

        var url = BASE_PRESENSI_URL + '?date=' + encodeURIComponent(targetDate) + '&format=json';
        var fetchOpts = activeDateController ? { signal: activeDateController.signal, headers: { 'Accept': 'application/json' } } : { headers: { 'Accept': 'application/json' } };

        fetch(url, fetchOpts)
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                if (!data || !data.ok) throw new Error('Invalid payload');

                CURRENT_DATE = data.date;
                if (datePicker && datePicker.value !== data.date) {
                    datePicker.value = data.date;
                }

                var headerLbl = document.getElementById('prHeaderDateLabel');
                if (headerLbl) headerLbl.textContent = data.date_label;

                var todayChip = document.getElementById('prTodayChip');
                if (todayChip) todayChip.hidden = !data.is_today;

                if (prevDateBtn) {
                    prevDateBtn.setAttribute('data-nav-date', data.prev_date);
                    prevDateBtn.href = BASE_PRESENSI_URL + '?date=' + encodeURIComponent(data.prev_date);
                }
                if (nextDateBtn) {
                    nextDateBtn.setAttribute('data-nav-date', data.next_date);
                    nextDateBtn.href = BASE_PRESENSI_URL + '?date=' + encodeURIComponent(data.next_date);
                }
                if (backTodayBtn) {
                    backTodayBtn.hidden = Boolean(data.is_today);
                }

                var progText = document.getElementById('prProgressText');
                if (progText) {
                    progText.textContent = data.recorded_count + ' / ' + data.total_teachers + ' guru (' + data.recorded_pct + '%)';
                }
                var progBar = document.getElementById('prProgressBar');
                if (progBar) progBar.setAttribute('aria-valuenow', String(data.recorded_pct));
                var progFill = document.getElementById('prProgressFill');
                if (progFill) progFill.style.width = data.recorded_pct + '%';

                var sHadir = document.getElementById('prStatHadir');
                if (sHadir) sHadir.textContent = String(data.stat_hadir);
                var sHadirHint = document.getElementById('prStatHadirHint');
                if (sHadirHint) sHadirHint.textContent = data.hadir_pct + '% dari seluruh guru';
                var sLambat = document.getElementById('prStatTerlambat');
                if (sLambat) sLambat.textContent = String(data.stat_terlambat);
                var sIzin = document.getElementById('prStatIzin');
                if (sIzin) sIzin.textContent = String(data.stat_izin);
                var sBelum = document.getElementById('prStatBelum');
                if (sBelum) sBelum.textContent = String(data.stat_belum);

                var cap = document.getElementById('presensi-table-caption');
                if (cap) cap.textContent = 'Daftar kehadiran guru aktif pada ' + data.date_label;

                var korHiddenDate = document.getElementById('kor_attendance_date');
                if (korHiddenDate) korHiddenDate.value = data.date;
                var korDisplayDate = document.getElementById('kor_date_display');
                if (korDisplayDate) korDisplayDate.value = data.date_short_label;

                if (tableBody) {
                    tableBody.textContent = '';
                    var list = data.records || [];
                    if (!list.length) {
                        var emptyTr = el('tr');
                        var emptyTd = el('td');
                        emptyTd.colSpan = 7;
                        emptyTd.style.padding = '36px 16px';
                        emptyTd.style.textAlign = 'center';
                        emptyTd.style.color = 'var(--pr-sub)';
                        emptyTd.appendChild(el('b', '', 'Belum ada data guru aktif yang terdaftar pada sistem.'));
                        emptyTr.appendChild(emptyTd);
                        tableBody.appendChild(emptyTr);
                        rows = [];
                        noMatchRow = null;
                    } else {
                        var newRows = [];
                        list.forEach(function (item) {
                            var trNode = buildRecordRow(item);
                            tableBody.appendChild(trNode);
                            newRows.push(trNode);
                        });
                        var nmTr = el('tr');
                        nmTr.id = 'prNoFilterMatchRow';
                        nmTr.hidden = true;
                        var nmTd = el('td');
                        nmTd.colSpan = 7;
                        nmTd.style.padding = '36px 16px';
                        nmTd.style.textAlign = 'center';
                        nmTd.style.color = 'var(--pr-sub)';
                        nmTd.appendChild(el('b', '', 'Tidak ada guru yang cocok dengan filter atau kata kunci pencarian.'));
                        nmTr.appendChild(nmTd);
                        tableBody.appendChild(nmTr);

                        rows = newRows;
                        noMatchRow = nmTr;
                    }
                }

                applyFilters();
                if (dateBar) dateBar.style.opacity = '';
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                window.location.href = BASE_PRESENSI_URL + '?date=' + encodeURIComponent(targetDate);
            });
    }

    if (datePicker) {
        datePicker.addEventListener('change', function () {
            if (datePicker.value) {
                loadDateRecords(datePicker.value);
            }
        });
    }

    if (dateForm) {
        dateForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            if (datePicker && datePicker.value) {
                loadDateRecords(datePicker.value);
            }
        });
    }

    [prevDateBtn, nextDateBtn, backTodayBtn].forEach(function (navEl) {
        if (!navEl) return;
        navEl.addEventListener('click', function (ev) {
            var targetDate = navEl.getAttribute('data-nav-date');
            if (targetDate) {
                ev.preventDefault();
                loadDateRecords(targetDate);
            }
        });
    });

    if (rows.length > 0 && (statusSelect.value !== '' || (searchInput && searchInput.value.trim() !== ''))) {
        applyFilters();
    }

    // 3. Dialog Native `<dialog>` untuk Pencatatan & Koreksi Presensi
    var dialog = document.getElementById('dialogKoreksiPresensi');
    var form = document.getElementById('formKoreksiPresensi');
    var teacherSelect = document.getElementById('kor_teacher_id');
    var korStatus = document.getElementById('kor_status');
    var timeSection = document.getElementById('kor_time_section');
    var nonPresentNotice = document.getElementById('kor_nonpresent_notice');
    var checkInInput = document.getElementById('kor_check_in');
    var checkOutInput = document.getElementById('kor_check_out');
    var noteInput = document.getElementById('kor_note');
    var timeErr = document.getElementById('kor_time_err');
    var dialogTitle = document.getElementById('dialog-koreksi-title');
    var submitBtn = document.getElementById('btnSubmitPresensi');

    function isNonPresent(st) {
        return st === 'izin' || st === 'sakit' || st === 'dinas' || st === 'alpa';
    }

    function clearTimeValidity() {
        if (checkOutInput) {
            checkOutInput.setCustomValidity('');
            checkOutInput.removeAttribute('aria-invalid');
        }
        if (timeErr) {
            timeErr.textContent = '';
            timeErr.hidden = true;
        }
    }

    function validateTimeOrder() {
        clearTimeValidity();
        if (!checkInInput || !checkOutInput || isNonPresent(korStatus.value)) {
            return true;
        }
        var inVal = checkInInput.value;
        var outVal = checkOutInput.value;
        if (inVal && outVal && outVal < inVal) {
            var msg = 'Jam pulang tidak boleh lebih awal daripada jam masuk (' + inVal.replace(':', '.') + ' WIB).';
            checkOutInput.setCustomValidity(msg);
            checkOutInput.setAttribute('aria-invalid', 'true');
            if (timeErr) {
                timeErr.textContent = msg;
                timeErr.hidden = false;
            }
            return false;
        }
        return true;
    }

    function syncTimeVisibility() {
        var hideTimes = isNonPresent(korStatus.value);
        if (timeSection) timeSection.hidden = hideTimes;
        if (nonPresentNotice) nonPresentNotice.hidden = !hideTimes;

        if (hideTimes) {
            if (checkInInput) checkInInput.value = '';
            if (checkOutInput) checkOutInput.value = '';
            clearTimeValidity();
        } else if (checkInInput && !checkInInput.value) {
            checkInInput.value = '07:00';
        }
    }

    function openPresensiDialog(payload) {
        if (!dialog) return;

        clearTimeValidity();
        if (submitBtn) submitBtn.disabled = false;

        if (payload) {
            if (dialogTitle) {
                dialogTitle.textContent = (payload.has_record ? 'Koreksi Presensi: ' : 'Catat Presensi: ') + (payload.teacher_name || 'Guru');
            }
            if (teacherSelect) teacherSelect.value = String(payload.teacher_id || '');
            if (korStatus) korStatus.value = payload.status || 'hadir';
            if (checkInInput) checkInInput.value = payload.check_in || (isNonPresent(korStatus.value) ? '' : '07:00');
            if (checkOutInput) checkOutInput.value = payload.check_out || '';
            if (noteInput) noteInput.value = payload.note || '';
        } else {
            if (dialogTitle) dialogTitle.textContent = 'Catat / Koreksi Presensi Guru';
            if (teacherSelect && teacherSelect.options.length > 0) teacherSelect.selectedIndex = 0;
            if (korStatus) korStatus.value = 'hadir';
            if (checkInInput) checkInInput.value = '07:00';
            if (checkOutInput) checkOutInput.value = '';
            if (noteInput) noteInput.value = '';
        }

        syncTimeVisibility();
        dialog.showModal();
    }

    var btnOpenNew = document.getElementById('btnOpenNewPresensi');
    if (btnOpenNew) {
        btnOpenNew.addEventListener('click', function () {
            openPresensiDialog(null);
        });
    }

    if (tableBody) {
        tableBody.addEventListener('click', function (ev) {
            var btn = ev.target && ev.target.closest ? ev.target.closest('[data-edit-presensi]') : null;
            if (!btn) return;
            var raw = btn.getAttribute('data-edit-presensi') || '{}';
            try {
                openPresensiDialog(JSON.parse(raw));
            } catch (err) {
                openPresensiDialog(null);
            }
        });
    }

    ['btnCloseDialog', 'btnCancelDialog'].forEach(function (id) {
        var closeBtn = document.getElementById(id);
        if (closeBtn && dialog) {
            closeBtn.addEventListener('click', function () {
                dialog.close();
            });
        }
    });

    if (dialog) {
        dialog.addEventListener('close', function () {
            dialog.returnValue = '';
            clearTimeValidity();
        });

        // Fallback untuk browser yang belum mendukung atribut deklaratif closedby="any"
        if (!('closedBy' in HTMLDialogElement.prototype)) {
            var isOutside = function (ev) {
                var r = dialog.getBoundingClientRect();
                return ev.clientX < r.left || ev.clientX > r.right || ev.clientY < r.top || ev.clientY > r.bottom;
            };
            var pressedOutside = false;
            dialog.addEventListener('pointerdown', function (ev) {
                pressedOutside = (ev.target === dialog) && isOutside(ev);
            });
            dialog.addEventListener('pointerup', function (ev) {
                if (pressedOutside && ev.target === dialog && isOutside(ev)) {
                    dialog.close();
                }
                pressedOutside = false;
            });
        }
    }

    if (korStatus) {
        korStatus.addEventListener('change', syncTimeVisibility);
    }

    Array.prototype.slice.call(document.querySelectorAll('[data-time-target]')).forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = btn.getAttribute('data-time-target');
            var val = btn.getAttribute('data-time-val');
            var input = document.getElementById(targetId);
            if (!input) return;

            if (val === 'now') {
                var now = new Date();
                var hh = String(now.getHours()).padStart(2, '0');
                var mm = String(now.getMinutes()).padStart(2, '0');
                input.value = hh + ':' + mm;
            } else {
                input.value = val;
            }
            validateTimeOrder();
        });
    });

    if (checkInInput) {
        checkInInput.addEventListener('input', clearTimeValidity);
        checkInInput.addEventListener('blur', validateTimeOrder);
    }
    if (checkOutInput) {
        checkOutInput.addEventListener('input', clearTimeValidity);
        checkOutInput.addEventListener('blur', validateTimeOrder);
    }

    if (form) {
        form.addEventListener('submit', function (ev) {
            if (!validateTimeOrder()) {
                ev.preventDefault();
                if (checkOutInput) checkOutInput.focus();
                return;
            }
            if (submitBtn) {
                submitBtn.disabled = true;
            }
        });
    }
})();
</script>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
