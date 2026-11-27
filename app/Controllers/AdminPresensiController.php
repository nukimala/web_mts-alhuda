<?php

require_once __DIR__ . '/../Models/Presensi.php';

/**
 * Controller Panel Admin untuk Rekap & Koreksi Presensi Guru.
 *
 * Seluruh validasi, normalisasi tanggal/jam, dan query didelegasikan ke Model `Presensi`.
 */
class AdminPresensiController
{
    private Presensi $presensiModel;

    public function __construct(?Presensi $presensiModel = null)
    {
        $this->presensiModel = $presensiModel ?? new Presensi();
    }

    private function guard(): array
    {
        Auth::start();
        if (!Auth::check()) {
            header('Location: ' . base_url('admin/login'));
            exit;
        }

        $user = Auth::user();
        if ($user !== null && (int) ($user['must_change_password'] ?? 0) === 1) {
            header('Location: ' . base_url('admin/ganti-sandi'));
            exit;
        }

        $role = (string) ($user['role'] ?? '');
        if (!in_array($role, ['admin', 'operator', 'kepala'], true)) {
            $_SESSION['_flash_error'] = 'Anda tidak memiliki hak akses untuk mengelola presensi guru.';
            header('Location: ' . base_url('admin'));
            exit;
        }

        return $user ?? [];
    }

    public function index(): void
    {
        $user = $this->guard();

        $todayDate = date('Y-m-d');
        $date = $this->presensiModel->normalizeDate((string) ($_GET['date'] ?? $todayDate));
        $status = trim((string) ($_GET['status'] ?? ''));
        $q = trim((string) ($_GET['q'] ?? ''));

        if (isset($_GET['export']) && strtolower((string) $_GET['export']) === 'csv') {
            $exportRecords = $this->presensiModel->getAttendanceRecords($date, $status, $q);
            $csvRows = $this->presensiModel->buildCsvRows($date, $exportRecords);

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="rekap-presensi-guru-' . $date . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $out = fopen('php://output', 'w');
            if ($out !== false) {
                fwrite($out, "\xEF\xBB\xBF");
                foreach ($csvRows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }
            exit;
        }

        $teachers = $this->presensiModel->getTeachers();
        $totalTeachers = count($teachers);
        // Ambil seluruh baris tanggal terpilih agar penyaringan status & pencarian berlangsung instan di sisi klien
        $records = $this->presensiModel->getAttendanceRecords($date, '', '');
        $stats = $this->presensiModel->getDailyStats($date, $totalTeachers);

        $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
        $nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
        $dateLabel = $this->presensiModel->formatIndonesianDate($date, true);
        $dateShortLabel = $this->presensiModel->formatIndonesianDate($date, false);

        if (isset($_GET['format']) && strtolower((string) $_GET['format']) === 'json') {
            $recordedCount = min($totalTeachers, $stats['stat_hadir'] + $stats['stat_terlambat'] + $stats['stat_izin']);
            $recordedPct = $totalTeachers > 0 ? (int) round(($recordedCount / $totalTeachers) * 100) : 0;
            $hadirPct = $totalTeachers > 0 ? round(($stats['stat_hadir'] / $totalTeachers) * 100, 1) : 0;

            $cleanRecords = [];
            foreach ($records as $r) {
                $st = (string) ($r['status'] ?? '');
                $teacherName = (string) ($r['teacher_name'] ?? '-');
                $nip = trim((string) ($r['nip'] ?? ''));
                $checkInVal = !empty($r['check_in_at']) ? date('H:i', strtotime((string) $r['check_in_at'])) : '';
                $checkOutVal = !empty($r['check_out_at']) ? date('H:i', strtotime((string) $r['check_out_at'])) : '';
                $distanceMeters = isset($r['distance_meters']) && $r['distance_meters'] !== null
                    ? (int) round((float) $r['distance_meters'])
                    : null;

                $cleanRecords[] = [
                    'has_record' => !empty($r['id']),
                    'teacher_id' => (int) ($r['teacher_id'] ?? 0),
                    'teacher_name' => $teacherName,
                    'initials' => strtoupper(mb_substr($teacherName, 0, 2)),
                    'nip' => $nip,
                    'status' => $st,
                    'filter_key' => $st !== '' ? $st : 'belum',
                    'check_in' => $checkInVal,
                    'check_out' => $checkOutVal,
                    'method' => (string) ($r['method'] ?? ''),
                    'verified_by_name' => (string) ($r['verified_by_name'] ?? ''),
                    'distance_meters' => $distanceMeters,
                    'note' => trim((string) ($r['note'] ?? '')),
                ];
            }

            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'ok' => true,
                'date' => $date,
                'is_today' => ($date === $todayDate),
                'prev_date' => $prevDate,
                'next_date' => $nextDate,
                'date_label' => $dateLabel,
                'date_short_label' => $dateShortLabel,
                'total_teachers' => $totalTeachers,
                'stat_hadir' => $stats['stat_hadir'],
                'stat_terlambat' => $stats['stat_terlambat'],
                'stat_izin' => $stats['stat_izin'],
                'stat_belum' => $stats['stat_belum'],
                'recorded_count' => $recordedCount,
                'recorded_pct' => $recordedPct,
                'hadir_pct' => $hadirPct,
                'records' => $cleanRecords,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = [
            'title' => 'Rekap Presensi Guru | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'presensi',
            'date' => $date,
            'today_date' => $todayDate,
            'is_today' => ($date === $todayDate),
            'prev_date' => $prevDate,
            'next_date' => $nextDate,
            'date_label' => $dateLabel,
            'date_short_label' => $dateShortLabel,
            'status' => $status,
            'q' => $q,
            'guru' => $teachers,
            'teachers' => $teachers,
            'records' => $records,
            'total_teachers' => $totalTeachers,
            'stat_hadir' => $stats['stat_hadir'],
            'stat_terlambat' => $stats['stat_terlambat'],
            'stat_izin' => $stats['stat_izin'],
            'stat_belum' => $stats['stat_belum'],
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/presensi/index.php';
    }

    public function correct(): void
    {
        $currentUser = $this->guard();

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . base_url('admin/presensi'));
            exit;
        }

        $fallbackDate = $this->presensiModel->normalizeDate((string) ($_POST['attendance_date'] ?? date('Y-m-d')));

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/presensi?date=' . $fallbackDate));
            exit;
        }

        $verifiedBy = isset($currentUser['id']) ? (int) $currentUser['id'] : null;
        $validation = $this->presensiModel->validateCorrectionInput($_POST, $verifiedBy);

        if (!$validation['valid']) {
            $_SESSION['_flash_error'] = $validation['error'];
            header('Location: ' . base_url('admin/presensi?date=' . $validation['data']['date']));
            exit;
        }

        $payload = $validation['data'];

        try {
            $this->presensiModel->saveOrUpdate($payload);

            Auth::log(
                'attendance.correct',
                'presensi_guru',
                (int) $payload['teacher_id'],
                "Koreksi manual presensi guru ID {$payload['teacher_id']} tanggal {$payload['date']} ({$payload['status']})"
            );

            $_SESSION['_flash_success'] = 'Data presensi guru berhasil dicatat/diperbarui.';
        } catch (PDOException $e) {
            $_SESSION['_flash_error'] = 'Terjadi kesalahan saat menyimpan data presensi.';
        }

        header('Location: ' . base_url('admin/presensi?date=' . $payload['date']));
        exit;
    }
}
