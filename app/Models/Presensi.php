<?php

require_once __DIR__ . '/../Support/Database.php';

/**
 * Model Presensi Guru & Sinkronisasi Kehadiran Mobile App.
 *
 * Kompatibel penuh dengan skema lokal (v1: `note`, `method`, `verified_at`)
 * maupun skema produksi/gabungan (v2: `notes`, `keterangan`, `jam_masuk`, `deleted_at`)
 * melalui deteksi kolom ber-cache per request.
 */
class Presensi
{
    /** @var string[] */
    public const VALID_STATUSES = ['hadir', 'terlambat', 'izin', 'sakit', 'dinas', 'alpa'];

    /** @var string[] */
    private const NON_PRESENT_STATUSES = ['izin', 'sakit', 'dinas', 'alpa'];

    /** @var array<string, array<string, bool>> */
    private static array $columnCache = [];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * Cek ketersediaan kolom pada suatu tabel (di-cache di memori per request).
     */
    private function hasColumn(string $table, string $column): bool
    {
        if (!isset(self::$columnCache[$table])) {
            self::$columnCache[$table] = [];
            try {
                $rows = $this->pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    self::$columnCache[$table][(string) $r['Field']] = true;
                }
            } catch (PDOException $e) {
                // Biarkan kosong jika tabel belum ada
            }
        }

        return isset(self::$columnCache[$table][$column]);
    }

    /**
     * Normalisasi dan validasi string tanggal YYYY-MM-DD.
     */
    public function normalizeDate(string $rawDate): string
    {
        $rawDate = trim($rawDate);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rawDate, $m)) {
            if (checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return $rawDate;
            }
        }

        return date('Y-m-d');
    }

    /**
     * Format tanggal ke Bahasa Indonesia (contoh: Jumat, 27 November 2026).
     */
    public function formatIndonesianDate(string $date, bool $withDayName = true): string
    {
        $clean = $this->normalizeDate($date);
        $ts = strtotime($clean);
        if ($ts === false) {
            return $clean;
        }

        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $dayNum = (int) date('d', $ts);
        $monthLabel = $months[(int) date('n', $ts)] ?? date('F', $ts);
        $yearNum = date('Y', $ts);

        if (!$withDayName) {
            return "{$dayNum} {$monthLabel} {$yearNum}";
        }

        $dayLabel = $days[(int) date('N', $ts)] ?? date('l', $ts);

        return "{$dayLabel}, {$dayNum} {$monthLabel} {$yearNum}";
    }

    /**
     * Ekspresi SQL nama guru dan klausa aktif guru+pengguna yang adaptif.
     *
     * @return array{nameExpr: string, activeWhere: string}
     */
    private function teacherSqlParts(): array
    {
        $nameExpr = $this->hasColumn('guru', 'nama_lengkap')
            ? "COALESCE(NULLIF(t.nama_lengkap, ''), u.name)"
            : "u.name";

        $conds = [];
        if ($this->hasColumn('guru', 'deleted_at')) {
            $conds[] = 't.deleted_at IS NULL';
        }
        if ($this->hasColumn('guru', 'is_active')) {
            $conds[] = 't.is_active = 1';
        }
        if ($this->hasColumn('pengguna', 'deleted_at')) {
            $conds[] = 'u.deleted_at IS NULL';
        }
        $conds[] = 'u.is_active = 1';

        return [
            'nameExpr' => $nameExpr,
            'activeWhere' => implode(' AND ', $conds),
        ];
    }

    /**
     * Ambil semua data guru aktif untuk dropdown dan referensi.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTeachers(): array
    {
        $parts = $this->teacherSqlParts();
        $prefixSelect = $this->hasColumn('guru', 'gelar_depan') ? 't.gelar_depan,' : 'NULL AS gelar_depan,';
        $suffixSelect = $this->hasColumn('guru', 'gelar_belakang') ? 't.gelar_belakang,' : 'NULL AS gelar_belakang,';

        $sql = "SELECT
                    t.id AS teacher_id,
                    t.nip,
                    {$prefixSelect}
                    {$suffixSelect}
                    {$parts['nameExpr']} AS raw_teacher_name,
                    u.email
                FROM guru t
                JOIN pengguna u ON u.id = t.user_id
                WHERE {$parts['activeWhere']}
                ORDER BY raw_teacher_name ASC";

        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['teacher_name'] = $this->formatPersonName(
                $row['gelar_depan'] ?? null,
                (string) ($row['raw_teacher_name'] ?? ''),
                $row['gelar_belakang'] ?? null
            );
        }
        unset($row);

        return $rows;
    }

    /**
     * Cek apakah guru aktif dengan ID tertentu terdaftar.
     */
    public function teacherExists(int $teacherId): bool
    {
        if ($teacherId <= 0) {
            return false;
        }

        $parts = $this->teacherSqlParts();

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM guru t
             JOIN pengguna u ON u.id = t.user_id
             WHERE t.id = ? AND {$parts['activeWhere']}"
        );
        $stmt->execute([$teacherId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Ambil rekaman presensi harian seluruh guru aktif berdasarkan tanggal dan filter.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAttendanceRecords(string $date, string $status = '', string $q = ''): array
    {
        $date = $this->normalizeDate($date);
        $status = trim($status);
        $q = trim($q);

        $parts = $this->teacherSqlParts();

        if ($this->hasColumn('presensi_guru', 'note')) {
            $noteExpr = 'a.note';
        } elseif ($this->hasColumn('presensi_guru', 'notes') && $this->hasColumn('presensi_guru', 'keterangan')) {
            $noteExpr = "COALESCE(NULLIF(a.notes, ''), a.keterangan)";
        } elseif ($this->hasColumn('presensi_guru', 'notes')) {
            $noteExpr = 'a.notes';
        } else {
            $noteExpr = 'NULL';
        }

        if ($this->hasColumn('presensi_guru', 'method')) {
            $methodExpr = 'a.method';
        } else {
            $methodExpr = "CASE
                WHEN a.id IS NULL THEN NULL
                WHEN a.verified_by IS NOT NULL THEN 'operator'
                ELSE 'self'
            END";
        }

        $attSoftDel = $this->hasColumn('presensi_guru', 'deleted_at') ? 'AND a.deleted_at IS NULL' : '';
        $prefixSelect = $this->hasColumn('guru', 'gelar_depan') ? 't.gelar_depan,' : 'NULL AS gelar_depan,';
        $suffixSelect = $this->hasColumn('guru', 'gelar_belakang') ? 't.gelar_belakang,' : 'NULL AS gelar_belakang,';
        $distSelect = $this->hasColumn('presensi_guru', 'distance_meters') ? 'a.distance_meters,' : 'NULL AS distance_meters,';

        $sql = "SELECT
                    a.id,
                    a.attendance_date,
                    a.attendance_type,
                    a.check_in_at,
                    a.check_out_at,
                    a.status,
                    a.verified_by,
                    {$distSelect}
                    {$noteExpr} AS note,
                    {$methodExpr} AS method,
                    op.name AS verified_by_name,
                    t.id AS teacher_id,
                    t.nip,
                    {$prefixSelect}
                    {$suffixSelect}
                    {$parts['nameExpr']} AS raw_teacher_name,
                    u.email
                FROM guru t
                JOIN pengguna u ON u.id = t.user_id
                LEFT JOIN presensi_guru a
                    ON a.teacher_id = t.id
                   AND a.attendance_date = ?
                   AND a.attendance_type = 'daily'
                   {$attSoftDel}
                LEFT JOIN pengguna op ON op.id = a.verified_by
                WHERE {$parts['activeWhere']}";

        $params = [$date];

        if ($status === 'belum') {
            $sql .= ' AND a.id IS NULL';
        } elseif ($status === 'belum_group') {
            $sql .= " AND (a.id IS NULL OR a.status = 'alpa')";
        } elseif ($status === 'izin_group') {
            $sql .= " AND a.status IN ('izin', 'sakit', 'dinas')";
        } elseif ($status !== '' && in_array($status, self::VALID_STATUSES, true)) {
            $sql .= ' AND a.status = ?';
            $params[] = $status;
        }

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $sql .= " AND ({$parts['nameExpr']} LIKE ? OR t.nip LIKE ?)";
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY raw_teacher_name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['teacher_name'] = $this->formatPersonName(
                $row['gelar_depan'] ?? null,
                (string) ($row['raw_teacher_name'] ?? ''),
                $row['gelar_belakang'] ?? null
            );
        }
        unset($row);

        return $rows;
    }

    /**
     * Ambil statistik presensi harian pada tanggal tertentu dalam satu query agregat.
     *
     * @return array{stat_hadir: int, stat_terlambat: int, stat_izin: int, stat_belum: int}
     */
    public function getDailyStats(string $date, int $totalTeachers): array
    {
        $date = $this->normalizeDate($date);
        $parts = $this->teacherSqlParts();
        $attSoftDel = $this->hasColumn('presensi_guru', 'deleted_at') ? 'AND a.deleted_at IS NULL' : '';

        $stmt = $this->pdo->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END), 0) AS stat_hadir,
                COALESCE(SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END), 0) AS stat_terlambat,
                COALESCE(SUM(CASE WHEN a.status IN ('izin', 'sakit', 'dinas') THEN 1 ELSE 0 END), 0) AS stat_izin
             FROM presensi_guru a
             JOIN guru t ON t.id = a.teacher_id
             JOIN pengguna u ON u.id = t.user_id
             WHERE a.attendance_date = ?
               AND a.attendance_type = 'daily'
               {$attSoftDel}
               AND {$parts['activeWhere']}"
        );
        $stmt->execute([$date]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $statHadir = (int) ($row['stat_hadir'] ?? 0);
        $statTerlambat = (int) ($row['stat_terlambat'] ?? 0);
        $statIzin = (int) ($row['stat_izin'] ?? 0);
        $statBelum = max(0, $totalTeachers - ($statHadir + $statTerlambat + $statIzin));

        return [
            'stat_hadir' => $statHadir,
            'stat_terlambat' => $statTerlambat,
            'stat_izin' => $statIzin,
            'stat_belum' => $statBelum,
        ];
    }

    /**
     * Validasi dan normalisasi input form koreksi manual presensi.
     *
     * @param array<string, mixed> $input
     * @return array{valid: bool, error: ?string, data: array<string, mixed>}
     */
    public function validateCorrectionInput(array $input, ?int $verifiedBy = null): array
    {
        $teacherId = (int) ($input['teacher_id'] ?? 0);
        $rawDate = trim((string) ($input['attendance_date'] ?? $input['date'] ?? ''));
        $date = $this->normalizeDate($rawDate);
        $status = trim((string) ($input['status'] ?? 'hadir'));
        $checkInTime = trim((string) ($input['check_in_time'] ?? ''));
        $checkOutTime = trim((string) ($input['check_out_time'] ?? ''));
        $note = trim((string) ($input['note'] ?? ''));

        $payload = [
            'teacher_id' => $teacherId,
            'date' => $date,
            'status' => $status,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'note' => mb_substr($note, 0, 255),
            'verified_by' => $verifiedBy,
        ];

        if ($teacherId <= 0 || !$this->teacherExists($teacherId)) {
            return ['valid' => false, 'error' => 'Guru yang dipilih tidak ditemukan atau sudah tidak aktif.', 'data' => $payload];
        }

        if ($rawDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
            return ['valid' => false, 'error' => 'Format tanggal presensi tidak valid.', 'data' => $payload];
        }

        if (!in_array($status, self::VALID_STATUSES, true)) {
            return ['valid' => false, 'error' => 'Status presensi tidak dikenal.', 'data' => $payload];
        }

        $cleanIn = $this->normalizeTime($checkInTime);
        if ($checkInTime !== '' && $cleanIn === null) {
            return ['valid' => false, 'error' => 'Format jam masuk tidak valid (gunakan HH:MM).', 'data' => $payload];
        }

        $cleanOut = $this->normalizeTime($checkOutTime);
        if ($checkOutTime !== '' && $cleanOut === null) {
            return ['valid' => false, 'error' => 'Format jam pulang tidak valid (gunakan HH:MM).', 'data' => $payload];
        }

        if ($cleanIn !== null && $cleanOut !== null && $cleanOut < $cleanIn) {
            return ['valid' => false, 'error' => 'Jam pulang tidak boleh lebih awal dari jam masuk.', 'data' => $payload];
        }

        $payload['check_in_time'] = $cleanIn ?? '';
        $payload['check_out_time'] = $cleanOut ?? '';

        return ['valid' => true, 'error' => null, 'data' => $payload];
    }

    /**
     * Simpan atau perbarui catatan presensi harian (koreksi manual operator)
     * secara adaptif terhadap skema v1 maupun v2.
     *
     * @param array<string, mixed> $data
     */
    public function saveOrUpdate(array $data): bool
    {
        $teacherId = (int) $data['teacher_id'];
        $date = $this->normalizeDate((string) $data['date']);
        $status = in_array($data['status'] ?? '', self::VALID_STATUSES, true) ? (string) $data['status'] : 'hadir';
        $checkInTime = trim((string) ($data['check_in_time'] ?? ''));
        $checkOutTime = trim((string) ($data['check_out_time'] ?? ''));
        $note = trim((string) ($data['note'] ?? ''));
        $verifiedBy = isset($data['verified_by']) && (int) $data['verified_by'] > 0 ? (int) $data['verified_by'] : null;

        $jamMasuk = null;
        $checkInAt = null;
        if ($checkInTime !== '') {
            $jamMasuk = strlen($checkInTime) === 5 ? ($checkInTime . ':00') : $checkInTime;
            $checkInAt = $date . ' ' . $jamMasuk;
        } elseif (!in_array($status, self::NON_PRESENT_STATUSES, true)) {
            $jamMasuk = '07:00:00';
            $checkInAt = $date . ' 07:00:00';
        }

        $jamPulang = null;
        $checkOutAt = null;
        if ($checkOutTime !== '' && !in_array($status, self::NON_PRESENT_STATUSES, true)) {
            $jamPulang = strlen($checkOutTime) === 5 ? ($checkOutTime . ':00') : $checkOutTime;
            $checkOutAt = $date . ' ' . $jamPulang;
        }

        $noteValue = $note !== '' ? mb_substr($note, 0, 255) : null;

        $existingStmt = $this->pdo->prepare(
            "SELECT id FROM presensi_guru
             WHERE teacher_id = ? AND attendance_date = ? AND attendance_type = 'daily'
             LIMIT 1"
        );
        $existingStmt->execute([$teacherId, $date]);
        $existingId = $existingStmt->fetchColumn();

        if ($existingId !== false) {
            $sets = [
                'check_in_at = ?',
                'check_out_at = ?',
                'status = ?',
                'verified_by = ?',
            ];
            $vals = [$checkInAt, $checkOutAt, $status, $verifiedBy];

            if ($this->hasColumn('presensi_guru', 'guru_id')) {
                $sets[] = 'guru_id = ?';
                $vals[] = $teacherId;
            }
            if ($this->hasColumn('presensi_guru', 'tanggal')) {
                $sets[] = 'tanggal = ?';
                $vals[] = $date;
            }
            if ($this->hasColumn('presensi_guru', 'jam_masuk')) {
                $sets[] = 'jam_masuk = ?';
                $vals[] = $jamMasuk;
            }
            if ($this->hasColumn('presensi_guru', 'jam_pulang')) {
                $sets[] = 'jam_pulang = ?';
                $vals[] = $jamPulang;
            }
            if ($this->hasColumn('presensi_guru', 'note')) {
                $sets[] = 'note = ?';
                $vals[] = $noteValue;
            }
            if ($this->hasColumn('presensi_guru', 'notes')) {
                $sets[] = 'notes = ?';
                $vals[] = $noteValue;
            }
            if ($this->hasColumn('presensi_guru', 'keterangan')) {
                $sets[] = 'keterangan = ?';
                $vals[] = $noteValue;
            }
            if ($this->hasColumn('presensi_guru', 'method')) {
                // Nilai enum yang sah pada skema v1 adalah 'operator' (bukan 'manual')
                $sets[] = "method = 'operator'";
            }
            if ($this->hasColumn('presensi_guru', 'verified_at')) {
                $sets[] = 'verified_at = NOW()';
            }
            if ($this->hasColumn('presensi_guru', 'deleted_at')) {
                $sets[] = 'deleted_at = NULL';
            }
            $sets[] = 'updated_at = NOW()';

            $vals[] = (int) $existingId;
            $setSql = implode(', ', $sets);

            $stmt = $this->pdo->prepare("UPDATE presensi_guru SET {$setSql} WHERE id = ?");

            return $stmt->execute($vals);
        }

        $cols = ['teacher_id', 'attendance_date', 'attendance_type', 'check_in_at', 'check_out_at', 'status', 'verified_by'];
        $vals = [$teacherId, $date, 'daily', $checkInAt, $checkOutAt, $status, $verifiedBy];

        if ($this->hasColumn('presensi_guru', 'guru_id')) {
            $cols[] = 'guru_id';
            $vals[] = $teacherId;
        }
        if ($this->hasColumn('presensi_guru', 'tanggal')) {
            $cols[] = 'tanggal';
            $vals[] = $date;
        }
        if ($this->hasColumn('presensi_guru', 'jam_masuk')) {
            $cols[] = 'jam_masuk';
            $vals[] = $jamMasuk;
        }
        if ($this->hasColumn('presensi_guru', 'jam_pulang')) {
            $cols[] = 'jam_pulang';
            $vals[] = $jamPulang;
        }
        if ($this->hasColumn('presensi_guru', 'note')) {
            $cols[] = 'note';
            $vals[] = $noteValue;
        }
        if ($this->hasColumn('presensi_guru', 'notes')) {
            $cols[] = 'notes';
            $vals[] = $noteValue;
        }
        if ($this->hasColumn('presensi_guru', 'keterangan')) {
            $cols[] = 'keterangan';
            $vals[] = $noteValue;
        }
        if ($this->hasColumn('presensi_guru', 'method')) {
            $cols[] = 'method';
            $vals[] = 'operator';
        }

        $extraCols = 'created_at, updated_at';
        $extraVals = 'NOW(), NOW()';
        if ($this->hasColumn('presensi_guru', 'verified_at')) {
            $extraCols = 'verified_at, ' . $extraCols;
            $extraVals = 'NOW(), ' . $extraVals;
        }

        $colList = implode(', ', $cols);
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO presensi_guru ({$colList}, {$extraCols}) VALUES ({$placeholders}, {$extraVals})"
        );

        return $stmt->execute($vals);
    }

    /**
     * Siapkan baris data rekap presensi harian untuk ekspor CSV.
     *
     * @return array<int, array<int, string>>
     */
    public function buildCsvRows(string $date, array $records): array
    {
        $rows = [
            ['No', 'Nama Guru', 'NIP', 'Tanggal', 'Status Kehadiran', 'Jam Masuk (WIB)', 'Jam Pulang (WIB)', 'Sumber Pencatatan', 'Diverifikasi Oleh', 'Keterangan'],
        ];

        $statusLabels = [
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'dinas' => 'Dinas Luar',
            'alpa' => 'Alpa',
        ];

        $methodLabels = [
            'self' => 'Mobile App',
            'operator' => 'Operator Web',
            'system' => 'Sistem Otomatis',
        ];

        foreach ($records as $idx => $r) {
            $st = (string) ($r['status'] ?? '');
            $statusLabel = $st !== '' ? ($statusLabels[$st] ?? ucfirst($st)) : 'Belum Check-in';
            $inLabel = !empty($r['check_in_at']) ? date('H:i', strtotime((string) $r['check_in_at'])) : '-';
            $outLabel = !empty($r['check_out_at']) ? date('H:i', strtotime((string) $r['check_out_at'])) : '-';
            $methodKey = (string) ($r['method'] ?? '');
            $methodLabel = $methodKey !== '' ? ($methodLabels[$methodKey] ?? ucfirst($methodKey)) : '-';

            $rows[] = [
                (string) ($idx + 1),
                (string) ($r['teacher_name'] ?? '-'),
                !empty($r['nip']) ? (string) $r['nip'] : '-',
                $date,
                $statusLabel,
                $inLabel,
                $outLabel,
                $methodLabel,
                !empty($r['verified_by_name']) ? (string) $r['verified_by_name'] : '-',
                !empty($r['note']) ? (string) $r['note'] : '-',
            ];
        }

        return $rows;
    }

    private function formatPersonName(?string $prefix, string $name, ?string $suffix): string
    {
        $prefix = trim((string) $prefix);
        $name = trim($name);
        $suffix = trim((string) $suffix);

        $full = $prefix !== '' && stripos($name, $prefix) !== 0 ? ($prefix . ' ' . $name) : $name;
        if ($suffix !== '' && stripos($full, $suffix) === false) {
            $full = rtrim($full, ',') . ', ' . ltrim($suffix, ', ');
        }

        return $full;
    }

    private function normalizeTime(string $time): ?string
    {
        $time = trim($time);
        if ($time === '') {
            return null;
        }

        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', $time, $m)) {
            return $m[1] . ':' . $m[2];
        }

        return null;
    }
}
