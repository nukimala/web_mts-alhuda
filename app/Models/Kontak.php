<?php

/**
 * Model Kontak & Kotak Masuk Pesan Pengunjung.
 *
 * Memusatkan seluruh logika bisnis, validasi, sanitasi, dan query database
 * untuk tabel `pesan_kontak` serta pengaturan kontak publik (`pengaturan`).
 */
class Kontak
{
    public const STATUS_BARU = 'baru';
    public const STATUS_DIBACA = 'dibaca';
    public const STATUS_DIBALAS = 'dibalas';

    /** @var string[] */
    public const VALID_STATUSES = [
        self::STATUS_BARU,
        self::STATUS_DIBACA,
        self::STATUS_DIBALAS,
    ];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * Mengambil pengaturan saluran kontak resmi untuk halaman publik.
     *
     * @return array<string, string>
     */
    public function getContactSettings(): array
    {
        $settings = [];

        try {
            $rows = $this->pdo
                ->query("SELECT setting_key, setting_value FROM pengaturan WHERE (group_name = 'kontak' OR is_public = 1) AND deleted_at IS NULL")
                ->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
            }
        } catch (PDOException $e) {
            // Fallback ke nilai bawaan jika tabel/kolom belum tersedia.
        }

        $settings['school_address'] = $settings['school_address'] ?? $settings['contact_address'] ?? 'Jl. Letjend Suprapto No. 16, Desa Pandean, Kec. Gondang, Nganjuk, Jawa Timur';
        $settings['phone'] = $settings['phone'] ?? $settings['contact_phone'] ?? '(0358) 123456';
        $settings['whatsapp'] = $settings['whatsapp'] ?? $settings['contact_whatsapp'] ?? '0812-3456-7890';
        $settings['email'] = $settings['email'] ?? $settings['contact_email'] ?? 'info@mtsalhuda.sch.id';
        $settings['service_hours'] = $settings['service_hours'] ?? 'Senin sampai Sabtu: 07.00 - 14.00 WIB (Kecuali hari libur nasional)';

        return $settings;
    }

    /**
     * Validasi dan normalisasi input formulir kontak pengunjung.
     *
     * @param array<string, mixed> $input
     * @return array{valid: bool, error: ?string, data: array<string, ?string>}
     */
    public function validateSubmission(array $input): array
    {
        $nama = $this->cleanSingleLine((string) ($input['name'] ?? $input['nama'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $telepon = $this->cleanSingleLine((string) ($input['phone'] ?? $input['telepon'] ?? ''));
        $subjek = $this->cleanSingleLine((string) ($input['subject'] ?? $input['subjek'] ?? ''));
        $pesan = trim((string) ($input['message'] ?? $input['pesan'] ?? ''));

        $normalized = [
            'nama' => $nama,
            'email' => $email,
            'telepon' => $telepon !== '' ? $telepon : null,
            'subjek' => $subjek !== '' ? $subjek : 'Pertanyaan Umum',
            'pesan' => $pesan,
        ];

        if ($nama === '' || mb_strlen($nama) < 2) {
            return ['valid' => false, 'error' => 'Nama lengkap wajib diisi minimal 2 karakter.', 'data' => $normalized];
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Alamat email tidak valid.', 'data' => $normalized];
        }

        if ($telepon !== '' && !preg_match('/^[0-9+\-\s().]{6,30}$/', $telepon)) {
            return ['valid' => false, 'error' => 'Format nomor telepon / WhatsApp tidak valid.', 'data' => $normalized];
        }

        if ($pesan === '' || mb_strlen($pesan) < 5) {
            return ['valid' => false, 'error' => 'Isi pesan wajib diisi minimal 5 karakter.', 'data' => $normalized];
        }

        if (mb_strlen($pesan) > 5000) {
            return ['valid' => false, 'error' => 'Isi pesan maksimal 5.000 karakter.', 'data' => $normalized];
        }

        $normalized['nama'] = mb_substr($nama, 0, 100);
        $normalized['email'] = mb_substr($email, 0, 100);
        $normalized['telepon'] = $telepon !== '' ? mb_substr($telepon, 0, 30) : null;
        $normalized['subjek'] = mb_substr($normalized['subjek'], 0, 150);

        return ['valid' => true, 'error' => null, 'data' => $normalized];
    }

    /**
     * Simpan pesan kontak baru ke database.
     *
     * @param array<string, ?string> $data
     */
    public function createMessage(array $data, ?string $ipAddress = null, ?string $userAgent = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO pesan_kontak
                (nama, email, telepon, subjek, pesan, status, ip_address, user_agent, created_at, updated_at)
            VALUES
                (:nama, :email, :telepon, :subjek, :pesan, :status, :ip_address, :user_agent, NOW(), NOW())
        ");

        $stmt->execute([
            ':nama' => $data['nama'],
            ':email' => $data['email'],
            ':telepon' => $data['telepon'] ?? null,
            ':subjek' => $data['subjek'] ?: 'Pertanyaan Umum',
            ':pesan' => $data['pesan'],
            ':status' => self::STATUS_BARU,
            ':ip_address' => $ipAddress !== null ? mb_substr($ipAddress, 0, 45) : null,
            ':user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Hitung jumlah pesan per status (mengabaikan pesan yang sudah dihapus/soft-delete).
     *
     * @return array{semua: int, baru: int, dibaca: int, dibalas: int}
     */
    public function getCounts(): array
    {
        $counts = [
            'semua' => 0,
            self::STATUS_BARU => 0,
            self::STATUS_DIBACA => 0,
            self::STATUS_DIBALAS => 0,
        ];

        $rows = $this->pdo
            ->query("SELECT status, COUNT(*) AS total FROM pesan_kontak WHERE deleted_at IS NULL GROUP BY status")
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $st = (string) ($row['status'] ?? '');
            $cnt = (int) ($row['total'] ?? 0);
            if (array_key_exists($st, $counts)) {
                $counts[$st] = $cnt;
            }
            $counts['semua'] += $cnt;
        }

        return $counts;
    }

    /**
     * Ambil daftar pesan masuk dengan filter status, pencarian, dan paginasi.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, limit: int, total_pages: int}
     */
    public function paginateMessages(string $status = 'semua', string $search = '', int $page = 1, int $limit = 15): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));

        $where = ['p.deleted_at IS NULL'];
        $params = [];

        if (in_array($status, self::VALID_STATUSES, true)) {
            $where[] = 'p.status = :status';
            $params[':status'] = $status;
        }

        $search = trim($search);
        if ($search !== '') {
            $where[] = '(p.nama LIKE :q OR p.email LIKE :q OR p.telepon LIKE :q OR p.subjek LIKE :q OR p.pesan LIKE :q)';
            $params[':q'] = '%' . $search . '%';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM pesan_kontak p {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $limit;

        $stmt = $this->pdo->prepare("
            SELECT
                p.*,
                COALESCE(NULLIF(u.name, ''), u.nama) AS dibalas_oleh_nama
            FROM pesan_kontak p
            LEFT JOIN pengguna u ON u.id = p.dibalas_oleh
            {$whereSql}
            ORDER BY (p.status = 'baru') DESC, p.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Ambil satu pesan berdasarkan ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                p.*,
                COALESCE(NULLIF(u.name, ''), u.nama) AS dibalas_oleh_nama
            FROM pesan_kontak p
            LEFT JOIN pengguna u ON u.id = p.dibalas_oleh
            WHERE p.id = :id AND p.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * Tandai pesan sebagai sudah dibaca (hanya jika status saat ini 'baru').
     */
    public function markAsRead(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE pesan_kontak
            SET status = :dibaca, updated_at = NOW()
            WHERE id = :id AND status = :baru AND deleted_at IS NULL
        ");
        $stmt->execute([
            ':dibaca' => self::STATUS_DIBACA,
            ':baru' => self::STATUS_BARU,
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Tandai pesan sebagai sudah dibalas serta simpan catatan balasan admin jika ada.
     */
    public function markAsReplied(int $id, ?int $adminId, ?string $replyNote = null): bool
    {
        $note = $replyNote !== null ? trim($replyNote) : null;
        if ($note === '') {
            $note = null;
        }

        $stmt = $this->pdo->prepare("
            UPDATE pesan_kontak
            SET
                status = :dibalas,
                balasan = COALESCE(:balasan, balasan),
                dibalas_oleh = :uid,
                dibalas_pada = NOW(),
                updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([
            ':dibalas' => self::STATUS_DIBALAS,
            ':balasan' => $note,
            ':uid' => $adminId,
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Hapus pesan secara lembut (soft delete) agar jejak audit tetap aman.
     */
    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE pesan_kontak
            SET deleted_at = NOW(), updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function cleanSingleLine(string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', $value);
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }
}
