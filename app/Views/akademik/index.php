<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$year = $data['year'] ?? [];
$classes = $data['classes'] ?? [];
$selectedClass = $data['selected_class'] ?? null;
$selectedClassId = (int) ($data['selected_class_id'] ?? 0);
$allClassBoards = $data['all_class_boards'] ?? [];
$weeklyBoard = $data['weekly_board'] ?? [];
$flatMergedRows = $data['flat_merged_rows'] ?? [];
$totalWeeklyJp = (int) ($data['total_weekly_jp'] ?? 0);
$hasSchedules = (bool) ($data['has_schedules'] ?? !empty($flatMergedRows));
$groupedSubjects = $data['grouped_subjects'] ?? ['agama' => [], 'umum' => [], 'total' => 0];
$teacherCount = (int) ($data['teacher_count'] ?? 0);
$settings = $data['settings'] ?? [];

$accreditationGrade = trim((string) ($settings['accreditation_grade'] ?? 'A'));
if ($accreditationGrade === '') {
    $accreditationGrade = 'A';
}

$yearName = (string) ($year['name'] ?? '2026/2027');
$semesterLabel = !empty($year['semester'])
    ? 'Semester ' . ucfirst(strtolower((string) $year['semester']))
    : 'Tahun Aktif';

$todayNum = (int) date('N'); // 1 (Senin) .. 7 (Minggu) dalam WIB

$piketList = [
    1 => ['hari' => 'Senin', 'petugas' => 'Subiyatin, S.Si. · Agus Setiawan, S.Pd. · Sodikin Muslimin, S.Pd.'],
    2 => ['hari' => 'Selasa', 'petugas' => 'Farid Kurniawan N, SH · Lilik Rahmawati, S.Pd. · Agus Setiawan, S.Pd.'],
    3 => ['hari' => 'Rabu', 'petugas' => 'Drs. Sarmin · Agus Setiawan, S.Pd. · Agus Budi Irawan, S.Pd.'],
    4 => ['hari' => 'Kamis', 'petugas' => 'Agus Setiawan, S.Pd. · Ulfatul Khumaydah, S.H.I · Agus Budi Irawan, S.Pd.'],
    5 => ['hari' => 'Jumat', 'petugas' => 'Mohammad Musthofa, S.Pd.I · Farid Kurniawan N, SH · Siti Amiroh, S.Pd.I'],
    6 => ['hari' => 'Sabtu', 'petugas' => 'Setyo Jatmiko, S.Pd. · Sodikin Muslimin, S.Pd. · Agus Budi Irawan, S.Pd.'],
];

$allBoardsJson = json_encode($allClassBoards, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<style>
.ak-page {
    --ak-ink: #0F172A;
    --ak-muted: #475569;
    --ak-green: #15803D;
    --ak-green-dark: #0B3D1E;
    --ak-green-deep: #166534;
    --ak-green-soft: #ECFDF5;
    --ak-green-border: #A7F3D0;
    --ak-amber-ink: #92400E;
    --ak-amber-soft: #FFFBEB;
    --ak-amber-border: #FDE68A;
    --ak-surface: #FFFFFF;
    --ak-bg: #F8FAF9;
    --ak-line: #CBD5E1;
    --ak-line-soft: #E2E8F0;
    color: var(--ak-ink);
}

.ak-page a,
.ak-page button {
    cursor: pointer;
}

.ak-page :focus-visible {
    outline: 2px solid var(--ak-green-dark);
    outline-offset: 2px;
}

/* 1. Hero & Real Metrics */
.ak-hero {
    background: linear-gradient(180deg, #F0FDF4 0%, #FFFFFF 100%);
    border-bottom: 1px solid var(--ak-line-soft);
    padding: clamp(36px, 5vw, 60px) 0 clamp(28px, 4vw, 44px);
}

.ak-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--ak-muted);
    margin-bottom: 12px;
}

.ak-breadcrumb a {
    color: var(--ak-green-deep);
    text-decoration: underline;
    text-underline-offset: 3px;
}

.ak-breadcrumb a:hover {
    color: var(--ak-green-dark);
}

.ak-hero-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
    gap: clamp(24px, 4vw, 48px);
    align-items: end;
}

.ak-hero h1 {
    margin: 0 0 12px;
    font-size: clamp(28px, 3.8vw, 42px);
    line-height: 1.12;
    letter-spacing: -0.025em;
    font-weight: 800;
    color: var(--ak-ink);
}

.ak-hero h1 span {
    color: var(--ak-green);
}

.ak-hero-lead {
    margin: 0;
    font-size: clamp(14.5px, 1.4vw, 16px);
    line-height: 1.65;
    color: var(--ak-muted);
    max-width: 62ch;
}

.ak-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.ak-metric-box {
    background: var(--ak-surface);
    border: 1px solid var(--ak-line);
    border-radius: 12px;
    padding: 14px 16px;
}

.ak-metric-val {
    display: block;
    font-size: clamp(20px, 2.4vw, 26px);
    font-weight: 800;
    line-height: 1.15;
    color: var(--ak-green-dark);
    letter-spacing: -0.02em;
}

.ak-metric-lbl {
    display: block;
    margin-top: 4px;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--ak-muted);
}

/* 2. Split Section: Kurikulum & Ritme Harian */
.ak-curriculum-sec {
    padding: clamp(40px, 5vw, 64px) 0;
    background: var(--ak-surface);
    border-bottom: 1px solid var(--ak-line-soft);
}

.ak-split {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
    gap: clamp(28px, 4.5vw, 52px);
    align-items: start;
}

.ak-kicker {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--ak-green-deep);
    margin-bottom: 8px;
}

.ak-h2 {
    margin: 0 0 14px;
    font-size: clamp(22px, 2.6vw, 30px);
    line-height: 1.2;
    letter-spacing: -0.02em;
    font-weight: 800;
    color: var(--ak-ink);
}

.ak-prose p {
    margin: 0 0 14px;
    font-size: 14.5px;
    line-height: 1.7;
    color: var(--ak-muted);
    max-width: 65ch;
}

.ak-prose p:last-child {
    margin-bottom: 0;
}

.ak-rhythm-panel {
    background: var(--ak-bg);
    border: 1px solid var(--ak-line);
    border-radius: 16px;
    padding: clamp(20px, 3vw, 28px);
}

.ak-rhythm-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 18px;
}

.ak-rhythm-head h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: var(--ak-ink);
}

.ak-timeline {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.ak-timeline li {
    display: grid;
    grid-template-columns: 112px minmax(0, 1fr);
    gap: 14px;
    align-items: start;
    padding-bottom: 14px;
    border-bottom: 1px dashed var(--ak-line);
}

.ak-timeline li:last-child {
    padding-bottom: 0;
    border-bottom: 0;
}

.ak-time-pill {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 8px;
    background: var(--ak-green-soft);
    border: 1px solid var(--ak-green-border);
    color: var(--ak-green-deep);
    font-size: 12.5px;
    font-weight: 800;
    text-align: center;
}

.ak-timeline strong {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: var(--ak-ink);
    margin-bottom: 2px;
}

.ak-timeline span {
    display: block;
    font-size: 13px;
    line-height: 1.5;
    color: var(--ak-muted);
}

/* 3. Seksi Utama: Jadwal Pelajaran Mingguan */
.ak-schedule-sec {
    padding: clamp(44px, 5.5vw, 72px) 0;
    background: var(--ak-bg);
    border-bottom: 1px solid var(--ak-line-soft);
}

.ak-sec-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}

.ak-sec-sub {
    margin: 4px 0 0;
    font-size: 14px;
    color: var(--ak-muted);
}

/* Navigasi Kelas Instan Tanpa Reload (Scrollable Tablist, >= 44px tap target) */
.ak-class-nav {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 6px;
    margin-bottom: 20px;
    scroll-snap-type: inline proximity;
    scroll-padding-inline: 16px;
}

.ak-class-tab {
    position: relative;
    flex: 0 0 auto;
    scroll-snap-align: start;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 44px;
    padding: 8px 16px;
    border-radius: 10px;
    background: var(--ak-surface);
    border: 1.5px solid var(--ak-line);
    color: var(--ak-ink);
    font-size: 13.5px;
    font-weight: 700;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.ak-class-tab:hover {
    border-color: var(--ak-green);
    background: var(--ak-green-soft);
}

.ak-class-tab[aria-selected="true"] {
    background: var(--ak-green-dark);
    border-color: var(--ak-green-dark);
    color: #FFFFFF;
}

.ak-class-tab[aria-selected="true"]::after {
    content: "";
    position: absolute;
    inset: auto 10px 0;
    border-block-end: 3px solid #4ADE80;
}

.ak-class-lvl {
    font-size: 12px;
    font-weight: 600;
    opacity: 0.85;
}

/* Info Bar Rombel & Kontrol Tampilan */
.ak-board-shell {
    background: var(--ak-surface);
    border: 1px solid var(--ak-line);
    border-radius: 16px;
    overflow: hidden;
}

.ak-board-bar {
    padding: 16px 20px;
    background: #F1F5F3;
    border-bottom: 1px solid var(--ak-line);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}

.ak-rombel-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 20px;
    align-items: center;
    font-size: 13.5px;
}

.ak-rombel-title {
    font-size: 16px;
    font-weight: 800;
    color: var(--ak-green-dark);
}

.ak-meta-item {
    color: var(--ak-muted);
}

.ak-meta-item strong {
    color: var(--ak-ink);
    font-weight: 700;
}

.ak-board-controls {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

/* Accessible Tabs (modern-web-guidance `tabs` pattern) */
.ak-tablist {
    display: inline-flex;
    background: var(--ak-surface);
    border: 1px solid var(--ak-line);
    border-radius: 10px;
    padding: 0 4px;
}

.ak-tablist [role="tab"] {
    position: relative;
    min-height: 44px;
    padding: 8px 14px;
    border: 0;
    background: transparent;
    color: var(--ak-muted);
    font-size: 13px;
    font-weight: 700;
}

.ak-tablist [role="tab"]:hover {
    color: var(--ak-ink);
}

.ak-tablist [role="tab"][aria-selected="true"] {
    color: var(--ak-green-deep);
}

.ak-tablist [role="tab"][aria-selected="true"]::after {
    content: "";
    position: absolute;
    inset: auto 8px 0;
    border-block-end: 3px solid var(--ak-green);
}

/* Panel Wrapper (styles inside child, NOT on [role="tabpanel"] per `tabs` guide) */
.ak-panels-grid {
    display: grid;
}

.ak-panels-grid > [role="tabpanel"] {
    grid-area: 1 / 1;
    min-width: 0;
}

.ak-panel-inner {
    padding: 20px;
}

/* 3-State Responsive Grid untuk 6 Hari */
.ak-week-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    align-items: start;
}

.ak-day-card {
    background: var(--ak-surface);
    border: 1px solid var(--ak-line);
    border-radius: 12px;
    overflow: hidden;
}

.ak-day-card.is-today {
    border: 2px solid var(--ak-green);
}

.ak-day-head {
    padding: 12px 16px;
    background: var(--ak-green-dark);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.ak-day-head h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: #FFFFFF;
}

.ak-today-tag {
    padding: 2px 8px;
    border-radius: 6px;
    background: var(--ak-amber-soft);
    color: var(--ak-amber-ink);
    font-size: 12px;
    font-weight: 800;
}

.ak-day-jp {
    font-size: 12px;
    font-weight: 600;
    color: #BBF7D0;
}

.ak-day-list {
    list-style: none;
    margin: 0;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ak-slot {
    border: 1px solid var(--ak-line-soft);
    border-radius: 10px;
    padding: 10px 12px;
    background: var(--ak-surface);
}

.ak-slot.cat-agama {
    background: #F7FEFA;
    border-color: var(--ak-green-border);
}

.ak-slot.cat-pembiasaan {
    background: var(--ak-amber-soft);
    border-color: var(--ak-amber-border);
}

.ak-slot-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 4px;
}

.ak-slot-time {
    font-size: 12px;
    font-weight: 800;
    color: var(--ak-muted);
}

.ak-slot.cat-agama .ak-slot-time {
    color: var(--ak-green-deep);
}

.ak-slot.cat-pembiasaan .ak-slot-time {
    color: var(--ak-amber-ink);
}

.ak-slot-badge {
    padding: 1px 7px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    background: #F1F5F9;
    color: var(--ak-ink);
    border: 1px solid var(--ak-line);
}

.ak-slot.cat-agama .ak-slot-badge {
    background: var(--ak-green-soft);
    color: var(--ak-green-deep);
    border-color: var(--ak-green-border);
}

.ak-slot.cat-pembiasaan .ak-slot-badge {
    background: #FEF3C7;
    color: var(--ak-amber-ink);
    border-color: var(--ak-amber-border);
}

.ak-slot-subj {
    font-size: 14px;
    font-weight: 800;
    color: var(--ak-ink);
    line-height: 1.35;
}

.ak-slot-teacher {
    margin-top: 3px;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--ak-muted);
}

.ak-break-row {
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px dashed var(--ak-line);
    background: var(--ak-bg);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 12px;
    font-weight: 700;
    color: var(--ak-muted);
}

/* Panel 2: Responsive Semantic Table (`responsive-table` guide) */
.ak-table-region {
    overflow: auto;
    max-inline-size: 100%;
    max-block-size: min(640px, 80vh);
    container-type: inline-size;
    border: 1px solid var(--ak-line);
    border-radius: 12px;
}

.ak-compact-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
    font-size: 13.5px;
    --label-waktu: "Waktu";
    --label-mapel: "Mata Pelajaran";
    --label-guru: "Guru Pengampu";
    --label-ruang: "Ruangan";
}

.ak-compact-table caption {
    padding: 12px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 700;
    color: var(--ak-muted);
    background: var(--ak-bg);
    border-bottom: 1px solid var(--ak-line);
}

.ak-compact-table thead {
    position: sticky;
    inset-block-start: 0;
    z-index: 2;
}

.ak-compact-table thead th {
    background: var(--ak-green-dark);
    color: #FFFFFF;
    padding: 12px 16px;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.ak-compact-table tbody th,
.ak-compact-table tbody td {
    padding: 11px 16px;
    border-bottom: 1px solid var(--ak-line-soft);
    background: var(--ak-surface);
    vertical-align: top;
}

.ak-compact-table tbody th {
    position: sticky;
    inset-inline-start: 0;
    z-index: 1;
    font-weight: 800;
    color: var(--ak-green-deep);
    background: #F7FEFA;
    border-right: 1px solid var(--ak-line-soft);
}

@container (width < 640px) {
    .ak-compact-table,
    .ak-compact-table thead,
    .ak-compact-table tbody,
    .ak-compact-table tr,
    .ak-compact-table th,
    .ak-compact-table td {
        display: block;
    }

    .ak-compact-table thead {
        display: none;
    }

    .ak-compact-table tbody tr {
        margin: 12px;
        border: 1px solid var(--ak-line);
        border-radius: 10px;
        overflow: hidden;
    }

    .ak-compact-table tbody th {
        position: sticky;
        inset-block-start: 0;
        border-right: 0;
        border-bottom: 1px solid var(--ak-line);
    }

    .ak-compact-table td:nth-child(2)::before {
        content: var(--label-waktu) ": ";
        font-weight: 700;
        color: var(--ak-muted);
    }

    .ak-compact-table td:nth-child(3)::before {
        content: var(--label-mapel) ": ";
        font-weight: 700;
        color: var(--ak-muted);
    }

    .ak-compact-table td:nth-child(4)::before {
        content: var(--label-guru) ": ";
        font-weight: 700;
        color: var(--ak-muted);
    }

    .ak-compact-table td:nth-child(5)::before {
        content: var(--label-ruang) ": ";
        font-weight: 700;
        color: var(--ak-muted);
    }
}

.ak-empty-state {
    padding: 48px 20px;
    text-align: center;
}

.ak-empty-state svg {
    width: 36px;
    height: 36px;
    stroke: var(--ak-muted);
    margin: 0 auto 10px;
    display: block;
}

.ak-empty-state b {
    display: block;
    font-size: 16px;
    color: var(--ak-ink);
    margin-bottom: 6px;
}

.ak-empty-state p {
    margin: 0 auto 16px;
    font-size: 14px;
    color: var(--ak-muted);
    max-width: 48ch;
}

/* 4. Seksi Komposisi Mata Pelajaran (2-Column Directory) */
.ak-subjects-sec {
    padding: clamp(44px, 5vw, 68px) 0;
    background: var(--ak-surface);
    border-bottom: 1px solid var(--ak-line-soft);
}

.ak-subj-columns {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
    margin-top: 24px;
}

.ak-subj-group {
    background: var(--ak-bg);
    border: 1px solid var(--ak-line);
    border-radius: 16px;
    padding: clamp(20px, 3vw, 28px);
}

.ak-subj-group-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-bottom: 14px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--ak-line);
}

.ak-subj-group-head h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    color: var(--ak-ink);
}

.ak-subj-count {
    padding: 4px 10px;
    border-radius: 8px;
    background: var(--ak-green-soft);
    border: 1px solid var(--ak-green-border);
    color: var(--ak-green-deep);
    font-size: 12px;
    font-weight: 800;
}

.ak-subj-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.ak-subj-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    background: var(--ak-surface);
    border: 1px solid var(--ak-line-soft);
}

.ak-subj-code {
    flex: 0 0 auto;
    min-width: 42px;
    text-align: center;
    padding: 3px 6px;
    border-radius: 6px;
    background: #F1F5F9;
    color: var(--ak-ink);
    font-size: 12px;
    font-weight: 800;
}

.ak-subj-group.agama .ak-subj-code {
    background: var(--ak-green-soft);
    color: var(--ak-green-deep);
}

.ak-subj-name {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--ak-ink);
    line-height: 1.3;
}

/* 5. Seksi Guru Piket & Pengesahan Kurikulum */
.ak-piket-sec {
    padding: clamp(40px, 5vw, 64px) 0;
    background: var(--ak-bg);
}

.ak-piket-card {
    background: var(--ak-surface);
    border: 1px solid var(--ak-line);
    border-radius: 16px;
    padding: clamp(20px, 3.2vw, 32px);
}

.ak-piket-grid {
    list-style: none;
    margin: 20px 0 0;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px 20px;
}

.ak-piket-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid var(--ak-line-soft);
    background: var(--ak-bg);
}

.ak-piket-row.is-today {
    background: var(--ak-green-soft);
    border-color: var(--ak-green);
}

.ak-piket-day {
    flex: 0 0 78px;
    font-size: 13.5px;
    font-weight: 800;
    color: var(--ak-green-deep);
}

.ak-piket-names {
    font-size: 13.5px;
    line-height: 1.5;
    color: var(--ak-ink);
    font-weight: 600;
}

.ak-signoff {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--ak-line-soft);
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 13px;
    color: var(--ak-muted);
}

.ak-signoff strong {
    color: var(--ak-ink);
}

/* Breakpoints: Tablet (640-1039px) & Phone (<640px) */
@media (max-width: 1039px) {
    .ak-hero-grid,
    .ak-split,
    .ak-subj-columns {
        grid-template-columns: 1fr;
    }

    .ak-week-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 680px) {
    .ak-week-grid,
    .ak-subj-list,
    .ak-piket-grid {
        grid-template-columns: 1fr;
    }

    .ak-panel-inner {
        padding: 14px;
    }

    .ak-timeline li {
        grid-template-columns: 1fr;
        gap: 6px;
    }

    .ak-time-pill {
        justify-self: start;
    }
}

@media (prefers-reduced-motion: reduce) {
    .ak-page *,
    .ak-page *::before,
    .ak-page *::after {
        transition: none !important;
        animation: none !important;
        scroll-behavior: auto !important;
    }
}

@media print {
    .site-header,
    .site-footer,
    .ak-hero,
    .ak-curriculum-sec,
    .ak-class-nav,
    .ak-board-controls,
    .ak-subjects-sec,
    .ak-piket-sec {
        display: none !important;
    }

    .ak-schedule-sec {
        padding: 0 !important;
        background: #FFFFFF !important;
    }

    .ak-board-shell {
        border: 1px solid #000 !important;
    }

    .ak-week-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 12px !important;
    }

    .ak-day-card {
        break-inside: avoid;
    }
}
</style>

<main class="ak-page">
    <!-- 1. HERO & DATA AKADEMIK NYATA -->
    <section class="ak-hero">
        <div class="container">
            <nav class="ak-breadcrumb" aria-label="Breadcrumb">
                <a href="<?= url('/') ?>">Beranda</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Akademik &amp; Jadwal</span>
            </nav>

            <div class="ak-hero-grid">
                <div>
                    <h1>Program Akademik &amp; <span>Jadwal Pelajaran</span></h1>
                    <p class="ak-hero-lead">
                        Penyelenggaraan pembelajaran terpadu MTs Al-Huda Gondang yang memadukan Kurikulum Merdeka Kementerian Agama dengan pendalaman kitab kuning, tahfidz, dan pembiasaan ibadah harian.
                    </p>
                </div>

                <div class="ak-metrics" aria-label="Ringkasan data akademik aktif">
                    <div class="ak-metric-box">
                        <span class="ak-metric-val">Peringkat <?= e($accreditationGrade) ?></span>
                        <span class="ak-metric-lbl">Akreditasi BAN-PDM</span>
                    </div>
                    <div class="ak-metric-box">
                        <span class="ak-metric-val"><?= count($classes) ?> Rombel</span>
                        <span class="ak-metric-lbl">Kelas VII s.d. IX Aktif</span>
                    </div>
                    <div class="ak-metric-box">
                        <span class="ak-metric-val"><?= (int) ($groupedSubjects['total'] ?? 0) ?> Mapel</span>
                        <span class="ak-metric-lbl">Nasional &amp; Kepesantrenan</span>
                    </div>
                    <div class="ak-metric-box">
                        <span class="ak-metric-val"><?= $teacherCount ?> Pendidik</span>
                        <span class="ak-metric-lbl">Dewan Guru Pengampu</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. POLA KURIKULUM & RITME BELAJAR HARIAN -->
    <section class="ak-curriculum-sec">
        <div class="container ak-split">
            <div class="ak-prose">
                <div class="ak-kicker">Standar Penyelenggaraan</div>
                <h2 class="ak-h2">Integrasi Kurikulum Kemenag &amp; Tradisi Kepesantrenan</h2>
                <p>
                    Struktur pembelajaran Fase D (Kelas VII sampai Kelas IX) di MTs Al-Huda Gondang menggabungkan mata pelajaran wajib nasional dengan rumpun Pendidikan Agama Islam serta muatan khas pesantren salafiyah seperti <em>Ta'lim Muta'alim</em>, <em>Mabadiul Fiqiyah</em>, <em>Aswaja</em>, <em>Baca Tulis Al-Qur'an (BTA)</em>, dan <em>Tahfidzul Qur'an</em>.
                </p>
                <p>
                    Kegiatan belajar berlangsung enam hari efektif (Senin sampai Sabtu) dengan penguatan literasi sains, praktik laboratorium komputer (TIK), serta pembinaan akhlak santri secara berkesinambungan.
                </p>
            </div>

            <aside class="ak-rhythm-panel" aria-labelledby="ritme-harian-heading">
                <div class="ak-rhythm-head">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#15803D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"></circle>
                        <polyline points="12 7 12 12 15.5 14"></polyline>
                    </svg>
                    <h3 id="ritme-harian-heading">Ritme Kegiatan Harian Santri</h3>
                </div>
                <ul class="ak-timeline">
                    <li>
                        <span class="ak-time-pill">06.45 - 07.00</span>
                        <div>
                            <strong>Jam Ke-0: Pembiasaan Pagi</strong>
                            <span>Pembacaan Sholawat Nariyah, Asmaul Husna, serta Tahlil &amp; Surat Yasin khusus hari Jumat.</span>
                        </div>
                    </li>
                    <li>
                        <span class="ak-time-pill">07.00 - 12.05</span>
                        <div>
                            <strong>KBM Terpadu &amp; Istirahat Dhuha</strong>
                            <span>Pembelajaran tatap muka di kelas/laboratorium diselingi jeda istirahat dan pembiasaan Sholat Dhuha.</span>
                        </div>
                    </li>
                    <li>
                        <span class="ak-time-pill">12.05 - 13.05</span>
                        <div>
                            <strong>Jamaah Dhuhur &amp; Sesi Siang</strong>
                            <span>Sholat Dhuhur berjamaah di musholla madrasah dilanjutkan jam pelajaran penutup (Senin s.d. Kamis).</span>
                        </div>
                    </li>
                </ul>
            </aside>
        </div>
    </section>

    <!-- 3. PAPAN JADWAL PELAJARAN MINGGUAN -->
    <section id="jadwal" class="ak-schedule-sec">
        <div class="container">
            <div class="ak-sec-top">
                <div>
                    <div class="ak-kicker">Transparansi Akademik</div>
                    <h2 class="ak-h2" style="margin-bottom: 0;">Jadwal Pembelajaran Mingguan</h2>
                    <p class="ak-sec-sub">
                        Tahun Ajaran <?= e($yearName) ?> (<?= e($semesterLabel) ?>) · Pilih rombongan belajar untuk melihat jadwal lengkap Senin sampai Sabtu
                    </p>
                </div>
            </div>

            <?php if (!empty($classes)): ?>
                <div class="ak-class-nav" role="tablist" focusgroup="tablist nomemory" aria-label="Pilih rombongan belajar">
                    <?php foreach ($classes as $c): ?>
                        <?php $isCurrent = (int) $c['id'] === $selectedClassId; ?>
                        <button type="button"
                                role="tab"
                                class="ak-class-tab"
                                data-class-id="<?= (int) $c['id'] ?>"
                                aria-selected="<?= $isCurrent ? 'true' : 'false' ?>"
                                <?= $isCurrent ? 'focusgroupstart' : '' ?>>
                            <span>Kelas <?= e($c['name'] ?? '') ?></span>
                            <span class="ak-class-lvl">(Tk. <?= (int) ($c['level'] ?? 7) ?>)</span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="ak-board-shell" id="akBoardShell" aria-live="polite">
                <?php if ($selectedClass !== null): ?>
                    <div class="ak-board-bar">
                        <div class="ak-rombel-meta" id="akRombelMeta">
                            <span class="ak-rombel-title" id="akMetaTitle">Rombel Kelas <?= e($selectedClass['name'] ?? '') ?></span>
                            <span class="ak-meta-item" id="akMetaWaliWrap" <?= empty($selectedClass['homeroom_teacher_name']) ? 'hidden' : '' ?>>
                                Wali Kelas: <strong id="akMetaWali"><?= e($selectedClass['homeroom_teacher_name'] ?? '') ?></strong>
                            </span>
                            <span class="ak-meta-item" id="akMetaRoomWrap" <?= empty($selectedClass['room']) ? 'hidden' : '' ?>>
                                Ruang: <strong id="akMetaRoom"><?= e($selectedClass['room'] ?? '') ?></strong>
                            </span>
                            <span class="ak-meta-item" id="akMetaJpWrap" <?= $totalWeeklyJp <= 0 ? 'hidden' : '' ?>>
                                Beban Belajar: <strong id="akMetaJp"><?= $totalWeeklyJp ?> JP / Minggu</strong>
                            </span>
                        </div>

                        <div class="ak-board-controls" id="akBoardControls" <?= !$hasSchedules ? 'hidden' : '' ?>>
                            <div class="ak-tablist" role="tablist" focusgroup="tablist nomemory" aria-label="Mode tampilan jadwal">
                                <button type="button" role="tab" id="tab-board" aria-selected="true" focusgroupstart>
                                    Papan Mingguan
                                </button>
                                <button type="button" role="tab" id="tab-table" aria-selected="false">
                                    Tabel Ringkas
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="ak-empty-state" id="akEmptyStateWrap" <?= $hasSchedules ? 'hidden' : '' ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <b>Jadwal pelajaran untuk rombongan belajar ini belum tersedia.</b>
                    <p>Silakan pilih rombongan belajar lainnya di bagian atas atau hubungi Wakil Kepala Bidang Kurikulum untuk informasi pembaruan jadwal.</p>
                </div>

                <div class="ak-panels-grid" id="akPanelsWrap" <?= !$hasSchedules ? 'hidden' : '' ?>>
                    <!-- PANEL 1: PAPAN MINGGUAN 6 HARI -->
                    <div role="tabpanel" id="panel-board" aria-labelledby="tab-board" tabindex="0">
                        <div class="ak-panel-inner">
                            <div class="ak-week-grid" id="akWeekGrid">
                                <?php foreach ($weeklyBoard as $dNum => $dInfo): ?>
                                    <?php $isToday = ($dNum === $todayNum); ?>
                                    <article class="ak-day-card <?= $isToday ? 'is-today' : '' ?>" data-day-card="<?= (int) $dNum ?>">
                                        <header class="ak-day-head">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <h3><?= e($dInfo['day_name']) ?></h3>
                                                <?php if ($isToday): ?>
                                                    <span class="ak-today-tag">Hari Ini</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="ak-day-jp"><?= (int) $dInfo['lesson_count'] ?> JP</span>
                                        </header>

                                        <?php if (empty($dInfo['items'])): ?>
                                            <div style="padding: 20px 16px; font-size: 13px; color: var(--ak-muted);">
                                                Tidak ada jadwal tatap muka pada hari ini.
                                            </div>
                                        <?php else: ?>
                                            <ul class="ak-day-list">
                                                <?php foreach ($dInfo['items'] as $item): ?>
                                                    <?php if (($item['type'] ?? '') === 'break'): ?>
                                                        <li class="ak-break-row">
                                                            <span><?= e($item['label']) ?></span>
                                                            <span><?= e($item['start_label']) ?> - <?= e($item['end_label']) ?></span>
                                                        </li>
                                                    <?php else: ?>
                                                        <?php
                                                        $cat = (string) ($item['category'] ?? 'umum');
                                                        $catClass = $cat === 'agama' ? 'cat-agama' : ($cat === 'pembiasaan' ? 'cat-pembiasaan' : 'cat-umum');
                                                        $badgeLabel = $cat === 'pembiasaan'
                                                            ? 'Pembiasaan'
                                                            : ((int) $item['jp'] . ' JP');
                                                        ?>
                                                        <li class="ak-slot <?= $catClass ?>">
                                                            <div class="ak-slot-top">
                                                                <span class="ak-slot-time"><?= e($item['start_label']) ?> - <?= e($item['end_label']) ?> WIB</span>
                                                                <span class="ak-slot-badge"><?= e($badgeLabel) ?></span>
                                                            </div>
                                                            <div class="ak-slot-subj"><?= e($item['subject_name']) ?></div>
                                                            <div class="ak-slot-teacher"><?= e($item['teacher_name']) ?></div>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- PANEL 2: TABEL RINGKAS RESPONSIF -->
                    <div role="tabpanel" id="panel-table" aria-labelledby="tab-table" hidden="until-found">
                        <div class="ak-panel-inner">
                            <div tabindex="0" role="region" aria-labelledby="jadwal-table-caption" class="ak-table-region">
                                <table class="ak-compact-table">
                                    <caption id="jadwal-table-caption">
                                        Daftar jadwal tergabung Kelas <?= e($selectedClass['name'] ?? '') ?> Tahun Ajaran <?= e($yearName) ?>
                                    </caption>
                                    <thead>
                                        <tr>
                                            <th scope="col" style="width: 110px;">Hari</th>
                                            <th scope="col" style="width: 155px;">Waktu</th>
                                            <th scope="col">Mata Pelajaran</th>
                                            <th scope="col">Guru Pengampu</th>
                                            <th scope="col" style="width: 145px;">Ruangan</th>
                                        </tr>
                                    </thead>
                                    <tbody id="akCompactTbody">
                                        <?php foreach ($flatMergedRows as $row): ?>
                                            <tr>
                                                <th scope="row"><?= e($row['day_name']) ?></th>
                                                <td><strong><?= e($row['start_label']) ?> - <?= e($row['end_label']) ?> WIB</strong></td>
                                                <td>
                                                    <strong><?= e($row['subject_name']) ?></strong>
                                                    <?php if ($row['category'] !== 'pembiasaan'): ?>
                                                        <span style="font-size: 12px; color: var(--ak-muted); margin-left: 4px;">(<?= (int) $row['jp'] ?> JP)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= e($row['teacher_name']) ?></td>
                                                <td><?= e($row['room']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. DIREKTORI MATA PELAJARAN KURIKULUM (DINAMIS DARI DATABASE) -->
    <?php if (!empty($groupedSubjects['agama']) || !empty($groupedSubjects['umum'])): ?>
        <section class="ak-subjects-sec">
            <div class="container">
                <div class="ak-kicker">Komposisi Kurikulum</div>
                <h2 class="ak-h2">Daftar Mata Pelajaran Resmi</h2>
                <p class="ak-sec-sub">
                    Seluruh mata pelajaran aktif yang diajarkan pada jenjang MTs Al-Huda Gondang berdasarkan kelompok kurikulum.
                </p>

                <div class="ak-subj-columns">
                    <div class="ak-subj-group agama">
                        <div class="ak-subj-group-head">
                            <h3>Rumpun PAI &amp; Kepesantrenan</h3>
                            <span class="ak-subj-count"><?= count($groupedSubjects['agama']) ?> Mapel</span>
                        </div>
                        <ul class="ak-subj-list">
                            <?php foreach ($groupedSubjects['agama'] as $sub): ?>
                                <li class="ak-subj-item">
                                    <span class="ak-subj-code"><?= e($sub['code']) ?></span>
                                    <span class="ak-subj-name"><?= e($sub['name']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="ak-subj-group umum">
                        <div class="ak-subj-group-head">
                            <h3>Rumpun Umum, Sains &amp; Muatan Lokal</h3>
                            <span class="ak-subj-count"><?= count($groupedSubjects['umum']) ?> Mapel</span>
                        </div>
                        <ul class="ak-subj-list">
                            <?php foreach ($groupedSubjects['umum'] as $sub): ?>
                                <li class="ak-subj-item">
                                    <span class="ak-subj-code"><?= e($sub['code']) ?></span>
                                    <span class="ak-subj-name"><?= e($sub['name']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- 5. JADWAL GURU PIKET HARIAN -->
    <section class="ak-piket-sec">
        <div class="container">
            <div class="ak-piket-card">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#15803D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <polyline points="16 11 18 13 22 9"></polyline>
                    </svg>
                    <h2 class="ak-h2" style="margin: 0; font-size: clamp(19px, 2.2vw, 24px);">Jadwal Guru Piket Mingguan</h2>
                </div>
                <p class="ak-sec-sub">
                    Petugas pendamping ketertiban pagi, layanan izin santri, dan koordinasi pergantian jam pelajaran.
                </p>

                <ul class="ak-piket-grid">
                    <?php foreach ($piketList as $dNum => $pk): ?>
                        <?php $isTodayPiket = ($dNum === $todayNum); ?>
                        <li class="ak-piket-row <?= $isTodayPiket ? 'is-today' : '' ?>">
                            <span class="ak-piket-day">
                                <?= e($pk['hari']) ?>
                                <?php if ($isTodayPiket): ?>
                                    <span style="display: block; font-size: 12px; color: var(--ak-amber-ink);">Hari Ini</span>
                                <?php endif; ?>
                            </span>
                            <span class="ak-piket-names"><?= e($pk['petugas']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="ak-signoff">
                    <span>Berlaku efektif mulai <strong>2 November 2026</strong></span>
                    <span>Mengetahui: Kepala Madrasah <strong>M. Abdul Haris Jauhari, S.Pd.I</strong> &middot; Waka Kurikulum <strong>Siti Amiroh, S.Pd.I</strong></span>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    var ALL_BOARDS = <?= $allBoardsJson ?: '{}' ?>;
    var TODAY_NUM = <?= (int) $todayNum ?>;
    var YEAR_NAME = <?= json_encode($yearName, JSON_UNESCAPED_UNICODE) ?>;
    var BASE_URL = <?= json_encode(url('akademik'), JSON_UNESCAPED_UNICODE) ?>;

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function renderClassBoard(classId) {
        var info = ALL_BOARDS[classId];
        if (!info) return;

        // 1. Update Rombel Meta Bar
        var metaTitle = document.getElementById('akMetaTitle');
        if (metaTitle) metaTitle.textContent = 'Rombel Kelas ' + info.name;

        var waliWrap = document.getElementById('akMetaWaliWrap');
        var waliText = document.getElementById('akMetaWali');
        if (waliWrap && waliText) {
            if (info.homeroom_teacher_name) {
                waliText.textContent = info.homeroom_teacher_name;
                waliWrap.hidden = false;
            } else {
                waliWrap.hidden = true;
            }
        }

        var roomWrap = document.getElementById('akMetaRoomWrap');
        var roomText = document.getElementById('akMetaRoom');
        if (roomWrap && roomText) {
            if (info.room) {
                roomText.textContent = info.room;
                roomWrap.hidden = false;
            } else {
                roomWrap.hidden = true;
            }
        }

        var jpWrap = document.getElementById('akMetaJpWrap');
        var jpText = document.getElementById('akMetaJp');
        if (jpWrap && jpText) {
            if (info.total_jp > 0) {
                jpText.textContent = info.total_jp + ' JP / Minggu';
                jpWrap.hidden = false;
            } else {
                jpWrap.hidden = true;
            }
        }

        var emptyWrap = document.getElementById('akEmptyStateWrap');
        var panelsWrap = document.getElementById('akPanelsWrap');
        var controlsWrap = document.getElementById('akBoardControls');

        if (!info.has_schedules) {
            if (emptyWrap) emptyWrap.hidden = false;
            if (panelsWrap) panelsWrap.hidden = true;
            if (controlsWrap) controlsWrap.hidden = true;
            return;
        }

        if (emptyWrap) emptyWrap.hidden = true;
        if (panelsWrap) panelsWrap.hidden = false;
        if (controlsWrap) controlsWrap.hidden = false;

        // 2. Render Papan Mingguan
        var weekGrid = document.getElementById('akWeekGrid');
        if (weekGrid) {
            weekGrid.textContent = '';
            (info.weekly_board || []).forEach(function (dInfo) {
                var dNum = Number(dInfo.day_num);
                var isToday = (dNum === TODAY_NUM);
                var article = el('article', 'ak-day-card' + (isToday ? ' is-today' : ''));
                article.setAttribute('data-day-card', String(dNum));

                var header = el('header', 'ak-day-head');
                var titleWrap = el('div');
                titleWrap.style.display = 'flex';
                titleWrap.style.alignItems = 'center';
                titleWrap.style.gap = '8px';
                titleWrap.appendChild(el('h3', '', dInfo.day_name));
                if (isToday) {
                    titleWrap.appendChild(el('span', 'ak-today-tag', 'Hari Ini'));
                }
                header.appendChild(titleWrap);
                header.appendChild(el('span', 'ak-day-jp', (dInfo.lesson_count || 0) + ' JP'));
                article.appendChild(header);

                var items = dInfo.items || [];
                if (!items.length) {
                    var emptyDay = el('div', '', 'Tidak ada jadwal tatap muka pada hari ini.');
                    emptyDay.style.padding = '20px 16px';
                    emptyDay.style.fontSize = '13px';
                    emptyDay.style.color = 'var(--ak-muted)';
                    article.appendChild(emptyDay);
                } else {
                    var ul = el('ul', 'ak-day-list');
                    items.forEach(function (item) {
                        if (item.type === 'break') {
                            var brk = el('li', 'ak-break-row');
                            brk.appendChild(el('span', '', item.label));
                            brk.appendChild(el('span', '', item.start_label + ' - ' + item.end_label));
                            ul.appendChild(brk);
                        } else {
                            var cat = item.category || 'umum';
                            var catClass = cat === 'agama' ? 'cat-agama' : (cat === 'pembiasaan' ? 'cat-pembiasaan' : 'cat-umum');
                            var badgeLabel = cat === 'pembiasaan' ? 'Pembiasaan' : (item.jp + ' JP');

                            var li = el('li', 'ak-slot ' + catClass);
                            var top = el('div', 'ak-slot-top');
                            top.appendChild(el('span', 'ak-slot-time', item.start_label + ' - ' + item.end_label + ' WIB'));
                            top.appendChild(el('span', 'ak-slot-badge', badgeLabel));
                            li.appendChild(top);
                            li.appendChild(el('div', 'ak-slot-subj', item.subject_name));
                            li.appendChild(el('div', 'ak-slot-teacher', item.teacher_name));
                            ul.appendChild(li);
                        }
                    });
                    article.appendChild(ul);
                }

                weekGrid.appendChild(article);
            });
        }

        // 3. Render Tabel Ringkas
        var caption = document.getElementById('jadwal-table-caption');
        if (caption) {
            caption.textContent = 'Daftar jadwal tergabung Kelas ' + info.name + ' Tahun Ajaran ' + YEAR_NAME;
        }

        var tbody = document.getElementById('akCompactTbody');
        if (tbody) {
            tbody.textContent = '';
            (info.flat_rows || []).forEach(function (row) {
                var tr = el('tr');
                var th = el('th', '', row.day_name);
                th.setAttribute('scope', 'row');
                tr.appendChild(th);

                var tdTime = el('td');
                tdTime.appendChild(el('strong', '', row.start_label + ' - ' + row.end_label + ' WIB'));
                tr.appendChild(tdTime);

                var tdSubj = el('td');
                tdSubj.appendChild(el('strong', '', row.subject_name));
                if (row.category !== 'pembiasaan') {
                    var jpSpan = el('span', '', '(' + row.jp + ' JP)');
                    jpSpan.style.fontSize = '12px';
                    jpSpan.style.color = 'var(--ak-muted)';
                    jpSpan.style.marginLeft = '4px';
                    tdSubj.appendChild(jpSpan);
                }
                tr.appendChild(tdSubj);

                tr.appendChild(el('td', '', row.teacher_name));
                tr.appendChild(el('td', '', row.room));
                tbody.appendChild(tr);
            });
        }
    }

    // 1. Instant Class Selector Tabs (Zero Page Reload)
    var classNav = document.querySelector('.ak-class-nav');
    if (classNav) {
        var classTabs = Array.prototype.slice.call(classNav.querySelectorAll('[role="tab"]'));

        function selectClassTab(targetTab, moveFocus) {
            var cid = targetTab.getAttribute('data-class-id');
            classTabs.forEach(function (t) {
                var active = (t === targetTab);
                t.setAttribute('aria-selected', active ? 'true' : 'false');
                t.toggleAttribute('focusgroupstart', active);
                t.tabIndex = active ? 0 : -1;
            });

            renderClassBoard(cid);

            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', BASE_URL + '?kelas=' + encodeURIComponent(cid));
            }

            if (typeof targetTab.scrollIntoView === 'function') {
                var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                targetTab.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: reduced ? 'auto' : 'smooth' });
            }

            if (moveFocus) {
                targetTab.focus();
            }
        }

        classTabs.forEach(function (tab, idx) {
            tab.tabIndex = tab.getAttribute('aria-selected') === 'true' ? 0 : -1;

            tab.addEventListener('click', function () {
                selectClassTab(tab, false);
            });

            tab.addEventListener('keydown', function (e) {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft' && e.key !== 'Home' && e.key !== 'End') {
                    return;
                }
                e.preventDefault();
                var nextIdx = idx;
                if (e.key === 'ArrowRight') nextIdx = (idx + 1) % classTabs.length;
                if (e.key === 'ArrowLeft') nextIdx = (idx - 1 + classTabs.length) % classTabs.length;
                if (e.key === 'Home') nextIdx = 0;
                if (e.key === 'End') nextIdx = classTabs.length - 1;
                selectClassTab(classTabs[nextIdx], true);
            });
        });
    }

    // 2. Accessible View Mode Tabs: Papan Mingguan <-> Tabel Ringkas
    var tabList = document.querySelector('.ak-tablist');
    if (tabList) {
        var tabs = Array.prototype.slice.call(tabList.querySelectorAll('[role="tab"]'));
        var panels = [
            document.getElementById('panel-board'),
            document.getElementById('panel-table')
        ];

        function activateTab(selectedTab, moveFocus) {
            tabs.forEach(function (tab, idx) {
                var isSelected = (tab === selectedTab);
                tab.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                tab.toggleAttribute('focusgroupstart', isSelected);
                tab.tabIndex = isSelected ? 0 : -1;

                var panel = panels[idx];
                if (panel) {
                    if (isSelected) {
                        panel.removeAttribute('hidden');
                        panel.setAttribute('tabindex', '0');
                    } else {
                        panel.hidden = 'until-found';
                        panel.removeAttribute('tabindex');
                    }
                }
            });

            if (moveFocus) {
                selectedTab.focus();
            }
        }

        tabs.forEach(function (tab, idx) {
            tab.tabIndex = idx === 0 ? 0 : -1;

            tab.addEventListener('click', function () {
                activateTab(tab, false);
            });

            tab.addEventListener('keydown', function (e) {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft' && e.key !== 'Home' && e.key !== 'End') {
                    return;
                }
                e.preventDefault();
                var nextIdx = idx;
                if (e.key === 'ArrowRight') nextIdx = (idx + 1) % tabs.length;
                if (e.key === 'ArrowLeft') nextIdx = (idx - 1 + tabs.length) % tabs.length;
                if (e.key === 'Home') nextIdx = 0;
                if (e.key === 'End') nextIdx = tabs.length - 1;
                activateTab(tabs[nextIdx], true);
            });

            if (panels[idx]) {
                panels[idx].addEventListener('beforematch', function () {
                    activateTab(tab, false);
                });
            }
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
