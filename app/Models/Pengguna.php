<?php

require_once __DIR__ . '/../Support/Database.php';

class Pengguna
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * Ambil daftar pengguna beserta relasi guru/siswa/kelas sesuai filter.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(string $role = '', string $status = '', string $q = ''): array
    {
        $where = ['u.deleted_at IS NULL'];
        $params = [];

        if ($role !== '' && in_array($role, ['admin', 'operator', 'kepala', 'guru', 'siswa', 'akreditasi'], true)) {
            $where[] = 'u.role = ?';
            $params[] = $role;
        }

        if ($status === 'active') {
            $where[] = 'u.is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'u.is_active = 0';
        }

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR t.nip LIKE ? OR s.nis LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT u.*, 
                       t.id AS teacher_id, t.nip, t.subject_specialty,
                       s.id AS student_id, s.nis, s.nisn, s.class_id,
                       c.name AS class_name
                FROM pengguna u
                LEFT JOIN guru t ON t.user_id = u.id
                LEFT JOIN siswa s ON s.user_id = u.id
                LEFT JOIN kelas c ON c.id = s.class_id
                WHERE {$whereSql}
                ORDER BY u.role ASC, u.name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil data satu pengguna berdasarkan ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT u.*, 
                       t.id AS teacher_id, t.nip, t.nuptk, t.subject_specialty,
                       s.id AS student_id, s.nis, s.nisn, s.class_id,
                       c.name AS class_name
                FROM pengguna u
                LEFT JOIN guru t ON t.user_id = u.id
                LEFT JOIN siswa s ON s.user_id = u.id
                LEFT JOIN kelas c ON c.id = s.class_id
                WHERE u.id = ? AND u.deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Ambil data kelas aktif untuk dropdown siswa.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveClasses(): array
    {
        $stmt = $this->pdo->query("SELECT id, name, level FROM kelas WHERE is_active = 1 ORDER BY level ASC, name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil statistik jumlah pengguna aktif per role.
     *
     * @return array<string, int>
     */
    public function getRoleStats(): array
    {
        $statGuru = (int) $this->pdo->query("SELECT COUNT(*) FROM pengguna WHERE role = 'guru' AND deleted_at IS NULL")->fetchColumn();
        $statSiswa = (int) $this->pdo->query("SELECT COUNT(*) FROM pengguna WHERE role = 'siswa' AND deleted_at IS NULL")->fetchColumn();
        $statOperator = (int) $this->pdo->query("SELECT COUNT(*) FROM pengguna WHERE role = 'operator' AND deleted_at IS NULL")->fetchColumn();
        $statKepala = (int) $this->pdo->query("SELECT COUNT(*) FROM pengguna WHERE role = 'kepala' AND deleted_at IS NULL")->fetchColumn();
        $statAkreditasi = (int) $this->pdo->query("SELECT COUNT(*) FROM pengguna WHERE role = 'akreditasi' AND deleted_at IS NULL")->fetchColumn();

        return [
            'guru' => $statGuru,
            'siswa' => $statSiswa,
            'operator' => $statOperator,
            'kepala' => $statKepala,
            'akreditasi' => $statAkreditasi,
        ];
    }

    /**
     * Cek apakah email sudah digunakan oleh pengguna lain.
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE email = ? AND id != ?");
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE email = ?");
            $stmt->execute([$email]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cek apakah nama pengguna (username) sudah digunakan.
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE username = ? AND id != ?");
            $stmt->execute([$username, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE username = ?");
            $stmt->execute([$username]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Tambah pengguna baru beserta relasi data guru atau siswa dalam satu transaksi.
     *
     * @param array<string, mixed> $userData
     * @param array<string, mixed>|null $teacherData
     * @param array<string, mixed>|null $studentData
     * @return int ID pengguna baru
     * @throws Throwable
     */
    public function create(array $userData, ?array $teacherData = null, ?array $studentData = null): int
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("INSERT INTO pengguna (name, nama, email, username, password_hash, role, is_active, must_change_password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, 0, NOW(), NOW())");
            $stmt->execute([
                $userData['name'],
                $userData['nama'] ?? $userData['name'],
                $userData['email'],
                $userData['username'],
                $userData['password_hash'],
                $userData['role'],
            ]);

            $newUserId = (int) $this->pdo->lastInsertId();

            if ($userData['role'] === 'guru' && $teacherData !== null) {
                $tStmt = $this->pdo->prepare("INSERT INTO guru (user_id, nama_lengkap, nip, nuptk, gender, jenis_kelamin, subject_specialty, bidang_studi, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $tStmt->execute([
                    $newUserId,
                    $userData['name'],
                    $teacherData['nip'] ?? null,
                    $teacherData['nuptk'] ?? null,
                    $teacherData['gender'] ?? 'L',
                    $teacherData['gender'] ?? 'L',
                    $teacherData['subject_specialty'] ?? null,
                    $teacherData['subject_specialty'] ?? null,
                ]);
            }

            if ($userData['role'] === 'siswa' && $studentData !== null) {
                $sStmt = $this->pdo->prepare("INSERT INTO siswa (user_id, nama_lengkap, nis, nisn, class_id, kelas_id, gender, jenis_kelamin, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
                $sStmt->execute([
                    $newUserId,
                    $userData['name'],
                    $studentData['nis'],
                    $studentData['nisn'] ?? null,
                    $studentData['class_id'],
                    $studentData['class_id'],
                    $studentData['gender'] ?? 'L',
                    $studentData['gender'] ?? 'L',
                ]);
            }

            $this->pdo->commit();
            return $newUserId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Reset kata sandi pengguna ke nilai bawaan.
     */
    public function resetPassword(int $id, string $plainPassword = '123'): bool
    {
        $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("UPDATE pengguna SET password_hash = ?, must_change_password = 0, failed_login_attempts = 0, locked_until = NULL, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$passwordHash, $id]);
    }

    /**
     * Toggle status aktif pengguna (aktif <-> nonaktif).
     */
    public function toggleStatus(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE pengguna SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$id]);
    }

    /**
     * Soft delete akun pengguna.
     */
    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE pengguna SET deleted_at = NOW(), is_active = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
