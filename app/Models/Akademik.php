<?php

require_once __DIR__ . '/../Support/Database.php';

/**
 * Model Akademik & Jadwal Pelajaran.
 *
 * Kompatibel penuh dengan skema lokal (v1) maupun skema produksi/gabungan (v2)
 * melalui deteksi kolom tabel ber-cache per request.
 */
class Akademik
{
    private const DAY_NAMES = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

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
     * Ambil tahun ajaran aktif saat ini.
     *
     * @return array<string, mixed>|null
     */
    public function getActiveYear(): ?array
    {
        $where = $this->hasColumn('tahun_akademik', 'deleted_at') ? 'WHERE deleted_at IS NULL' : '';
        $stmt = $this->pdo->query(
            "SELECT * FROM tahun_akademik
             {$where}
             ORDER BY is_active DESC, start_date DESC, id DESC
             LIMIT 1"
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }

    /**
     * Ambil semua tahun akademik untuk opsi filter.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllAcademicYears(): array
    {
        $where = $this->hasColumn('tahun_akademik', 'deleted_at') ? 'WHERE deleted_at IS NULL' : '';
        $stmt = $this->pdo->query(
            "SELECT * FROM tahun_akademik
             {$where}
             ORDER BY is_active DESC, start_date DESC, name DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil daftar kelas aktif untuk tahun akademik tertentu beserta info wali kelas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getClassesByYear(int $yearId = 0): array
    {
        $nameExpr = $this->hasColumn('kelas', 'nama_kelas')
            ? "COALESCE(NULLIF(k.name, ''), k.nama_kelas)"
            : "k.name";
        $roomExpr = $this->hasColumn('kelas', 'ruangan')
            ? "COALESCE(NULLIF(k.room, ''), NULLIF(k.ruangan, ''), 'Ruang Kelas')"
            : "COALESCE(NULLIF(k.room, ''), 'Ruang Kelas')";
        $homeroomFk = $this->hasColumn('kelas', 'wali_kelas_id')
            ? "COALESCE(k.homeroom_teacher_id, k.wali_kelas_id)"
            : "k.homeroom_teacher_id";
        $guruSoftDel = $this->hasColumn('guru', 'deleted_at') ? 'AND g.deleted_at IS NULL' : '';
        $userSoftDel = $this->hasColumn('pengguna', 'deleted_at') ? 'AND u.deleted_at IS NULL' : '';
        $kelasSoftDel = $this->hasColumn('kelas', 'deleted_at') ? 'AND k.deleted_at IS NULL' : '';

        $homeroomNameExpr = $this->hasColumn('guru', 'nama_lengkap')
            ? "COALESCE(NULLIF(g.nama_lengkap, ''), u.name)"
            : "u.name";
        $prefixSelect = $this->hasColumn('guru', 'gelar_depan') ? 'g.gelar_depan AS homeroom_prefix,' : 'NULL AS homeroom_prefix,';
        $suffixSelect = $this->hasColumn('guru', 'gelar_belakang') ? 'g.gelar_belakang AS homeroom_suffix' : 'NULL AS homeroom_suffix';

        $baseSelect = "
            SELECT
                k.*,
                {$nameExpr} AS class_display_name,
                {$roomExpr} AS room_display,
                {$homeroomNameExpr} AS homeroom_name,
                {$prefixSelect}
                {$suffixSelect}
            FROM kelas k
            LEFT JOIN guru g ON g.id = {$homeroomFk} {$guruSoftDel}
            LEFT JOIN pengguna u ON u.id = g.user_id {$userSoftDel}
            WHERE k.is_active = 1 {$kelasSoftDel}
        ";

        if ($yearId > 0) {
            $stmt = $this->pdo->prepare($baseSelect . " AND k.academic_year_id = ? ORDER BY k.level ASC, k.sort_order ASC, k.name ASC");
            $stmt->execute([$yearId]);
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($classes)) {
                return $this->enrichClasses($classes);
            }
        }

        $rows = $this->pdo
            ->query($baseSelect . " ORDER BY k.level ASC, k.sort_order ASC, k.name ASC")
            ->fetchAll(PDO::FETCH_ASSOC);

        return $this->enrichClasses($rows);
    }

    /**
     * Validasi dan tentukan kelas terpilih dari daftar kelas aktif.
     *
     * @param array<int, array<string, mixed>> $classes
     * @return array<string, mixed>|null
     */
    public function resolveSelectedClass(array $classes, int $requestedClassId = 0): ?array
    {
        if (empty($classes)) {
            return null;
        }

        if ($requestedClassId > 0) {
            foreach ($classes as $classRow) {
                if ((int) ($classRow['id'] ?? 0) === $requestedClassId) {
                    return $classRow;
                }
            }
        }

        return $classes[0];
    }

    /**
     * Ambil daftar jadwal pelajaran aktif per kelas dan tahun akademik.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSchedulesByClass(int $classId, int $yearId = 0): array
    {
        if ($classId <= 0) {
            return [];
        }

        $subNameExpr = $this->hasColumn('mata_pelajaran', 'nama_mapel')
            ? "COALESCE(NULLIF(sub.name, ''), sub.nama_mapel)"
            : "sub.name";
        $subCodeExpr = $this->hasColumn('mata_pelajaran', 'kode_mapel')
            ? "COALESCE(NULLIF(sub.code, ''), sub.kode_mapel)"
            : "sub.code";
        $subGroupExpr = $this->hasColumn('mata_pelajaran', 'kelompok')
            ? "COALESCE(NULLIF(sub.group_name, ''), sub.kelompok, 'umum')"
            : "COALESCE(sub.group_name, 'umum')";
        $roomExpr = $this->hasColumn('jadwal', 'ruangan')
            ? "COALESCE(NULLIF(s.room, ''), NULLIF(s.ruangan, ''), 'Ruang Kelas')"
            : "COALESCE(NULLIF(s.room, ''), 'Ruang Kelas')";

        $teacherFullExpr = $this->hasColumn('guru', 'nama_lengkap') ? 't.nama_lengkap AS teacher_full_name,' : 'NULL AS teacher_full_name,';
        $prefixSelect = $this->hasColumn('guru', 'gelar_depan') ? 't.gelar_depan,' : 'NULL AS gelar_depan,';
        $suffixSelect = $this->hasColumn('guru', 'gelar_belakang') ? 't.gelar_belakang,' : 'NULL AS gelar_belakang,';

        $csSoftDel = $this->hasColumn('mata_pelajaran_kelas', 'deleted_at') ? 'AND cs.deleted_at IS NULL' : '';
        $subSoftDel = $this->hasColumn('mata_pelajaran', 'deleted_at') ? 'AND sub.deleted_at IS NULL' : '';
        $guruSoftDel = $this->hasColumn('guru', 'deleted_at') ? 'AND t.deleted_at IS NULL' : '';
        $userSoftDel = $this->hasColumn('pengguna', 'deleted_at') ? 'AND u.deleted_at IS NULL' : '';
        $jadwalSoftDel = $this->hasColumn('jadwal', 'deleted_at') ? 'AND s.deleted_at IS NULL' : '';

        $hasJadwalKelasId = $this->hasColumn('jadwal', 'kelas_id');
        $classFilter = $hasJadwalKelasId ? '(cs.class_id = ? OR s.kelas_id = ?)' : 'cs.class_id = ?';

        $sql = "SELECT
                    s.*,
                    cs.class_id,
                    {$subNameExpr} AS subject_name,
                    {$subCodeExpr} AS subject_code,
                    {$subGroupExpr} AS subject_group,
                    {$roomExpr} AS room_display,
                    {$prefixSelect}
                    {$teacherFullExpr}
                    {$suffixSelect}
                    u.name AS user_name
                FROM jadwal s
                JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id {$csSoftDel}
                JOIN mata_pelajaran sub ON sub.id = cs.subject_id {$subSoftDel}
                LEFT JOIN guru t ON t.id = s.teacher_id {$guruSoftDel}
                LEFT JOIN pengguna u ON u.id = t.user_id {$userSoftDel}
                WHERE {$classFilter}
                  AND s.is_active = 1
                  {$jadwalSoftDel}
                  AND (s.academic_year_id = ? OR ? = 0)
                ORDER BY s.day_of_week ASC, s.start_time ASC";

        $params = $hasJadwalKelasId ? [$classId, $classId, $yearId, $yearId] : [$classId, $yearId, $yearId];

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['room'] = $row['room_display'];
            $baseName = !empty($row['teacher_full_name'])
                ? (string) $row['teacher_full_name']
                : (!empty($row['user_name']) ? (string) $row['user_name'] : 'Guru Pengampu');
            $row['teacher_name'] = $this->formatPersonName(
                $row['gelar_depan'] ?? null,
                $baseName,
                $row['gelar_belakang'] ?? null
            );
        }
        unset($row);

        return $rows;
    }

    /**
     * Ambil pengaturan publik website.
     *
     * @return array<string, string|null>
     */
    public function getPublicSettings(): array
    {
        try {
            $softDel = $this->hasColumn('pengaturan', 'deleted_at') ? 'AND deleted_at IS NULL' : '';
            return $this->pdo
                ->query("SELECT setting_key, setting_value FROM pengaturan WHERE is_public = 1 {$softDel}")
                ->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Ambil semua mata pelajaran aktif.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllSubjects(): array
    {
        $softDel = $this->hasColumn('mata_pelajaran', 'deleted_at') ? 'AND deleted_at IS NULL' : '';
        return $this->pdo
            ->query("SELECT * FROM mata_pelajaran WHERE is_active = 1 {$softDel} ORDER BY sort_order ASC, name ASC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil semua guru aktif.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllTeachers(): array
    {
        $nameExpr = $this->hasColumn('guru', 'nama_lengkap')
            ? "COALESCE(NULLIF(t.nama_lengkap, ''), u.name)"
            : "u.name";
        $guruWhere = [];
        if ($this->hasColumn('guru', 'deleted_at')) {
            $guruWhere[] = 't.deleted_at IS NULL';
        }
        if ($this->hasColumn('guru', 'is_active')) {
            $guruWhere[] = 't.is_active = 1';
        }
        if ($this->hasColumn('pengguna', 'deleted_at')) {
            $guruWhere[] = 'u.deleted_at IS NULL';
        }
        $guruWhere[] = 'u.is_active = 1';

        $whereSql = implode(' AND ', $guruWhere);

        $sql = "SELECT t.id, {$nameExpr} AS name, t.nip
                FROM guru t
                JOIN pengguna u ON u.id = t.user_id
                WHERE {$whereSql}
                ORDER BY name ASC";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pastikan relasi mata pelajaran dan kelas ada di tabel `mata_pelajaran_kelas`.
     */
    public function getOrCreateClassSubject(int $classId, int $subjectId, int $teacherId): int
    {
        $hasSoftDel = $this->hasColumn('mata_pelajaran_kelas', 'deleted_at');
        $selectCols = $hasSoftDel ? 'id, deleted_at' : 'id';

        $stmt = $this->pdo->prepare(
            "SELECT {$selectCols} FROM mata_pelajaran_kelas WHERE class_id = ? AND subject_id = ? LIMIT 1"
        );
        $stmt->execute([$classId, $subjectId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing !== false) {
            $id = (int) $existing['id'];
            if ($hasSoftDel && !empty($existing['deleted_at'])) {
                $this->pdo
                    ->prepare("UPDATE mata_pelajaran_kelas SET deleted_at = NULL, is_active = 1, teacher_id = ?, updated_at = NOW() WHERE id = ?")
                    ->execute([$teacherId, $id]);
            }
            return $id;
        }

        $columns = ['class_id', 'subject_id', 'teacher_id', 'weekly_hours', 'is_active'];
        $values = [$classId, $subjectId, $teacherId, 2, 1];

        if ($this->hasColumn('mata_pelajaran_kelas', 'kelas_id')) {
            $columns[] = 'kelas_id';
            $values[] = $classId;
        }
        if ($this->hasColumn('mata_pelajaran_kelas', 'mata_pelajaran_id')) {
            $columns[] = 'mata_pelajaran_id';
            $values[] = $subjectId;
        }
        if ($this->hasColumn('mata_pelajaran_kelas', 'guru_id')) {
            $columns[] = 'guru_id';
            $values[] = $teacherId;
        }
        if ($this->hasColumn('mata_pelajaran_kelas', 'jam_mingguan')) {
            $columns[] = 'jam_mingguan';
            $values[] = 2;
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $colList = implode(', ', $columns);

        $insert = $this->pdo->prepare(
            "INSERT INTO mata_pelajaran_kelas ({$colList}, created_at, updated_at) VALUES ({$placeholders}, NOW(), NOW())"
        );
        $insert->execute($values);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Cek apakah ada jadwal guru yang bentrok pada jam dan hari yang sama.
     */
    public function hasTeacherClash(int $teacherId, int $yearId, int $dayOfWeek, string $startTime, string $endTime): bool
    {
        $softDel = $this->hasColumn('jadwal', 'deleted_at') ? 'AND deleted_at IS NULL' : '';
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM jadwal
             WHERE teacher_id = ?
               AND academic_year_id = ?
               AND day_of_week = ?
               AND is_active = 1
               {$softDel}
               AND (start_time < ? AND end_time > ?)"
        );
        $stmt->execute([$teacherId, $yearId, $dayOfWeek, $endTime, $startTime]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cek apakah ada jadwal kelas yang bentrok pada jam dan hari yang sama.
     */
    public function hasClassClash(int $classId, int $yearId, int $dayOfWeek, string $startTime, string $endTime): bool
    {
        $softDel = $this->hasColumn('jadwal', 'deleted_at') ? 'AND s.deleted_at IS NULL' : '';
        $hasJadwalKelasId = $this->hasColumn('jadwal', 'kelas_id');
        $classFilter = $hasJadwalKelasId ? '(cs.class_id = ? OR s.kelas_id = ?)' : 'cs.class_id = ?';
        $params = $hasJadwalKelasId
            ? [$classId, $classId, $yearId, $dayOfWeek, $endTime, $startTime]
            : [$classId, $yearId, $dayOfWeek, $endTime, $startTime];

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM jadwal s
             JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id
             WHERE {$classFilter}
               AND s.academic_year_id = ?
               AND s.day_of_week = ?
               AND s.is_active = 1
               {$softDel}
               AND (s.start_time < ? AND s.end_time > ?)"
        );
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Tambah jadwal baru secara adaptif terhadap skema v1 maupun v2.
     *
     * @param array<string, mixed> $payload
     */
    public function addSchedule(array $payload): int
    {
        $classSubjectId = (int) $payload['class_subject_id'];
        $classId = isset($payload['class_id']) ? (int) $payload['class_id'] : 0;
        $subjectId = isset($payload['subject_id']) ? (int) $payload['subject_id'] : 0;

        if (($this->hasColumn('jadwal', 'kelas_id') || $this->hasColumn('jadwal', 'subject_id')) && ($classId <= 0 || $subjectId <= 0)) {
            $csStmt = $this->pdo->prepare("SELECT class_id, subject_id FROM mata_pelajaran_kelas WHERE id = ? LIMIT 1");
            $csStmt->execute([$classSubjectId]);
            $csRow = $csStmt->fetch(PDO::FETCH_ASSOC);
            if ($csRow !== false) {
                $classId = $classId > 0 ? $classId : (int) $csRow['class_id'];
                $subjectId = $subjectId > 0 ? $subjectId : (int) $csRow['subject_id'];
            }
        }

        $teacherId = (int) $payload['teacher_id'];
        $yearId = (int) $payload['academic_year_id'];
        $dayOfWeek = max(1, min(7, (int) $payload['day_of_week']));
        $hari = !empty($payload['hari']) && in_array($payload['hari'], self::DAY_NAMES, true)
            ? (string) $payload['hari']
            : (self::DAY_NAMES[$dayOfWeek] ?? 'Senin');

        $startTime = (string) $payload['start_time'];
        $endTime = (string) $payload['end_time'];
        $room = trim((string) ($payload['room'] ?? ''));
        $roomValue = $room !== '' ? $room : 'Ruang Kelas';

        $columns = ['class_subject_id', 'teacher_id', 'academic_year_id', 'day_of_week', 'start_time', 'end_time', 'room', 'is_active'];
        $values = [$classSubjectId, $teacherId, $yearId, $dayOfWeek, $startTime, $endTime, $roomValue, 1];

        if ($this->hasColumn('jadwal', 'kelas_id')) {
            $columns[] = 'kelas_id';
            $values[] = $classId;
        }
        if ($this->hasColumn('jadwal', 'subject_id')) {
            $columns[] = 'subject_id';
            $values[] = $subjectId;
        }
        if ($this->hasColumn('jadwal', 'mata_pelajaran_id')) {
            $columns[] = 'mata_pelajaran_id';
            $values[] = $subjectId;
        }
        if ($this->hasColumn('jadwal', 'guru_id')) {
            $columns[] = 'guru_id';
            $values[] = $teacherId;
        }
        if ($this->hasColumn('jadwal', 'hari')) {
            $columns[] = 'hari';
            $values[] = $hari;
        }
        if ($this->hasColumn('jadwal', 'jam_mulai')) {
            $columns[] = 'jam_mulai';
            $values[] = $startTime;
        }
        if ($this->hasColumn('jadwal', 'jam_selesai')) {
            $columns[] = 'jam_selesai';
            $values[] = $endTime;
        }
        if ($this->hasColumn('jadwal', 'ruangan')) {
            $columns[] = 'ruangan';
            $values[] = $roomValue;
        }

        $colList = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $stmt = $this->pdo->prepare("INSERT INTO jadwal ({$colList}, created_at, updated_at) VALUES ({$placeholders}, NOW(), NOW())");
        $stmt->execute($values);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Hapus jadwal berdasarkan ID (soft delete jika kolom `deleted_at` ada, atau hard delete pada skema v1).
     */
    public function deleteSchedule(int $id): bool
    {
        if ($this->hasColumn('jadwal', 'deleted_at')) {
            $stmt = $this->pdo->prepare("UPDATE jadwal SET deleted_at = NOW(), is_active = 0, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
            return $stmt->execute([$id]);
        }

        $stmt = $this->pdo->prepare("DELETE FROM jadwal WHERE id = ?");

        return $stmt->execute([$id]);
    }

    /**
     * Susun jadwal kelas menjadi papan mingguan terstruktur:
     * menggabungkan jam pelajaran berurutan untuk mapel & guru yang sama,
     * serta menyisipkan penanda istirahat otomatis bila ada jeda >= 10 menit.
     *
     * @param array<int, array<string, mixed>> $schedules
     * @return array<int, array{day_num: int, day_name: string, items: array<int, array<string, mixed>>, lesson_count: int}>
     */
    public function buildWeeklyBoard(array $schedules): array
    {
        $routineCodes = ['NRY', 'UPC', 'UPC2', 'THL'];
        $rawByDay = [];
        for ($d = 1; $d <= 6; $d++) {
            $rawByDay[$d] = [];
        }

        foreach ($schedules as $sch) {
            $d = (int) ($sch['day_of_week'] ?? 0);
            if ($d < 1 || $d > 7) {
                continue;
            }
            if (!isset($rawByDay[$d])) {
                $rawByDay[$d] = [];
            }
            $rawByDay[$d][] = $sch;
        }

        $board = [];
        foreach ($rawByDay as $dayNum => $rows) {
            $merged = [];
            foreach ($rows as $row) {
                $code = strtoupper(trim((string) ($row['subject_code'] ?? '')));
                $isRoutine = in_array($code, $routineCodes, true);
                $category = $isRoutine ? 'pembiasaan' : (string) ($row['subject_group'] ?? 'umum');

                $lastIdx = count($merged) - 1;
                if (
                    $lastIdx >= 0
                    && $merged[$lastIdx]['subject_name'] === ($row['subject_name'] ?? '')
                    && $merged[$lastIdx]['teacher_name'] === ($row['teacher_name'] ?? '')
                    && $merged[$lastIdx]['end_raw'] === ($row['start_time'] ?? '')
                ) {
                    $merged[$lastIdx]['end_raw'] = (string) $row['end_time'];
                    $merged[$lastIdx]['end_label'] = date('H.i', strtotime((string) $row['end_time']));
                    $merged[$lastIdx]['jp']++;
                    continue;
                }

                $merged[] = [
                    'type' => 'lesson',
                    'start_raw' => (string) $row['start_time'],
                    'end_raw' => (string) $row['end_time'],
                    'start_label' => date('H.i', strtotime((string) $row['start_time'])),
                    'end_label' => date('H.i', strtotime((string) $row['end_time'])),
                    'subject_name' => (string) ($row['subject_name'] ?? '-'),
                    'subject_code' => $code,
                    'category' => $category,
                    'teacher_name' => (string) ($row['teacher_name'] ?? '-'),
                    'room' => (string) ($row['room'] ?? 'Ruang Kelas'),
                    'jp' => 1,
                ];
            }

            $timeline = [];
            $lessonCount = 0;
            foreach ($merged as $i => $block) {
                if ($i > 0) {
                    $prevEnd = strtotime('1970-01-01 ' . $merged[$i - 1]['end_raw']);
                    $currStart = strtotime('1970-01-01 ' . $block['start_raw']);
                    $gapMinutes = ($currStart - $prevEnd) / 60;

                    if ($gapMinutes >= 10) {
                        $crossesNoon = $merged[$i - 1]['end_raw'] >= '11:30:00' && $block['start_raw'] >= '12:15:00';
                        $timeline[] = [
                            'type' => 'break',
                            'start_label' => $merged[$i - 1]['end_label'],
                            'end_label' => $block['start_label'],
                            'label' => $crossesNoon ? 'Jamaah Sholat Dhuhur & Istirahat' : 'Istirahat',
                        ];
                    }
                }

                if ($block['category'] !== 'pembiasaan') {
                    $lessonCount += (int) $block['jp'];
                }
                $timeline[] = $block;
            }

            $board[$dayNum] = [
                'day_num' => $dayNum,
                'day_name' => self::DAY_NAMES[$dayNum] ?? ('Hari ' . $dayNum),
                'items' => $timeline,
                'lesson_count' => $lessonCount,
            ];
        }

        return $board;
    }

    /**
     * Ambil dan susun papan jadwal mingguan untuk seluruh kelas aktif sekaligus
     * dalam satu query database agar perpindahan antar kelas di UI berlangsung instan
     * tanpa perlu memuat ulang halaman.
     *
     * @param array<int, array<string, mixed>> $classes
     * @return array<int, array<string, mixed>> Diindeks berdasarkan class_id
     */
    public function getAllClassBoardsByYear(array $classes, int $yearId = 0): array
    {
        if (empty($classes)) {
            return [];
        }

        $subNameExpr = $this->hasColumn('mata_pelajaran', 'nama_mapel')
            ? "COALESCE(NULLIF(sub.name, ''), sub.nama_mapel)"
            : "sub.name";
        $subCodeExpr = $this->hasColumn('mata_pelajaran', 'kode_mapel')
            ? "COALESCE(NULLIF(sub.code, ''), sub.kode_mapel)"
            : "sub.code";
        $subGroupExpr = $this->hasColumn('mata_pelajaran', 'kelompok')
            ? "COALESCE(NULLIF(sub.group_name, ''), sub.kelompok, 'umum')"
            : "COALESCE(sub.group_name, 'umum')";
        $roomExpr = $this->hasColumn('jadwal', 'ruangan')
            ? "COALESCE(NULLIF(s.room, ''), NULLIF(s.ruangan, ''), 'Ruang Kelas')"
            : "COALESCE(NULLIF(s.room, ''), 'Ruang Kelas')";

        $teacherFullExpr = $this->hasColumn('guru', 'nama_lengkap') ? 't.nama_lengkap AS teacher_full_name,' : 'NULL AS teacher_full_name,';
        $prefixSelect = $this->hasColumn('guru', 'gelar_depan') ? 't.gelar_depan,' : 'NULL AS gelar_depan,';
        $suffixSelect = $this->hasColumn('guru', 'gelar_belakang') ? 't.gelar_belakang,' : 'NULL AS gelar_belakang,';

        $csSoftDel = $this->hasColumn('mata_pelajaran_kelas', 'deleted_at') ? 'AND cs.deleted_at IS NULL' : '';
        $subSoftDel = $this->hasColumn('mata_pelajaran', 'deleted_at') ? 'AND sub.deleted_at IS NULL' : '';
        $guruSoftDel = $this->hasColumn('guru', 'deleted_at') ? 'AND t.deleted_at IS NULL' : '';
        $userSoftDel = $this->hasColumn('pengguna', 'deleted_at') ? 'AND u.deleted_at IS NULL' : '';
        $jadwalSoftDel = $this->hasColumn('jadwal', 'deleted_at') ? 'AND s.deleted_at IS NULL' : '';
        $resolvedClassExpr = $this->hasColumn('jadwal', 'kelas_id') ? 'COALESCE(s.kelas_id, cs.class_id)' : 'cs.class_id';

        $sql = "SELECT
                    s.*,
                    {$resolvedClassExpr} AS resolved_class_id,
                    {$subNameExpr} AS subject_name,
                    {$subCodeExpr} AS subject_code,
                    {$subGroupExpr} AS subject_group,
                    {$roomExpr} AS room_display,
                    {$prefixSelect}
                    {$teacherFullExpr}
                    {$suffixSelect}
                    u.name AS user_name
                FROM jadwal s
                JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id {$csSoftDel}
                JOIN mata_pelajaran sub ON sub.id = cs.subject_id {$subSoftDel}
                LEFT JOIN guru t ON t.id = s.teacher_id {$guruSoftDel}
                LEFT JOIN pengguna u ON u.id = t.user_id {$userSoftDel}
                WHERE s.is_active = 1
                  {$jadwalSoftDel}
                  AND (s.academic_year_id = ? OR ? = 0)
                ORDER BY s.day_of_week ASC, s.start_time ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$yearId, $yearId]);
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $groupedRaw = [];
        foreach ($allRows as $row) {
            $cid = (int) ($row['resolved_class_id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $row['room'] = $row['room_display'];
            $baseName = !empty($row['teacher_full_name'])
                ? (string) $row['teacher_full_name']
                : (!empty($row['user_name']) ? (string) $row['user_name'] : 'Guru Pengampu');
            $row['teacher_name'] = $this->formatPersonName(
                $row['gelar_depan'] ?? null,
                $baseName,
                $row['gelar_belakang'] ?? null
            );
            $groupedRaw[$cid][] = $row;
        }

        $result = [];
        foreach ($classes as $c) {
            $cid = (int) ($c['id'] ?? 0);
            $classScheds = $groupedRaw[$cid] ?? [];
            $board = $this->buildWeeklyBoard($classScheds);

            $totalJp = 0;
            $flatRows = [];
            foreach ($board as $dNum => $dInfo) {
                $totalJp += (int) ($dInfo['lesson_count'] ?? 0);
                foreach ($dInfo['items'] as $item) {
                    if (($item['type'] ?? '') === 'lesson') {
                        $flatRows[] = array_merge($item, [
                            'day_num' => $dNum,
                            'day_name' => $dInfo['day_name'],
                        ]);
                    }
                }
            }

            $result[$cid] = [
                'id' => $cid,
                'name' => (string) ($c['name'] ?? ''),
                'level' => (int) ($c['level'] ?? 7),
                'homeroom_teacher_name' => (string) ($c['homeroom_teacher_name'] ?? ''),
                'room' => (string) ($c['room'] ?? 'Ruang Kelas'),
                'total_jp' => $totalJp,
                'has_schedules' => !empty($classScheds),
                'weekly_board' => array_values($board),
                'flat_rows' => $flatRows,
            ];
        }

        return $result;
    }

    /**
     * Kelompokkan mata pelajaran aktif ke Rumpun PAI & Kepesantrenan vs Rumpun Umum & Muatan Lokal
     * (tidak termasuk kegiatan pembiasaan upacara/nariyahan/tahlilan).
     *
     * @return array{agama: array<int, array<string, mixed>>, umum: array<int, array<string, mixed>>, total: int}
     */
    public function getGroupedSubjects(): array
    {
        $all = $this->getAllSubjects();
        $routineCodes = ['UPC', 'UPC2', 'NRY', 'THL'];

        $agama = [];
        $umum = [];

        foreach ($all as $sub) {
            $code = strtoupper(trim((string) ($sub['code'] ?? $sub['kode_mapel'] ?? '')));
            if (in_array($code, $routineCodes, true)) {
                continue;
            }

            $group = strtolower(trim((string) ($sub['group_name'] ?? $sub['kelompok'] ?? 'umum')));
            $item = [
                'code' => $code,
                'name' => (string) ($sub['name'] ?? $sub['nama_mapel'] ?? ''),
                'group' => $group,
            ];

            if ($group === 'agama') {
                $agama[] = $item;
            } else {
                $umum[] = $item;
            }
        }

        return [
            'agama' => $agama,
            'umum' => $umum,
            'total' => count($agama) + count($umum),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $classes
     * @return array<int, array<string, mixed>>
     */
    private function enrichClasses(array $classes): array
    {
        foreach ($classes as &$c) {
            $c['name'] = $c['class_display_name'] ?? $c['name'] ?? '';
            $c['room'] = $c['room_display'] ?? $c['room'] ?? 'Ruang Kelas';
            $c['homeroom_teacher_name'] = !empty($c['homeroom_name'])
                ? $this->formatPersonName($c['homeroom_prefix'] ?? null, (string) $c['homeroom_name'], $c['homeroom_suffix'] ?? null)
                : null;
        }
        unset($c);

        return $classes;
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
}
