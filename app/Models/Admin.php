<?php

require_once __DIR__ . '/../Support/Database.php';

class Admin
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * Ambil statistik ringkasan dashboard panel admin.
     *
     * @return array<string, int>
     */
    public function getDashboardStats(): array
    {
        return [
            'pengguna'        => $this->count('pengguna', 'deleted_at IS NULL'),
            'guru'            => $this->count('pengguna', "role = 'guru' AND deleted_at IS NULL"),
            'siswa'           => $this->count('pengguna', "role = 'siswa' AND deleted_at IS NULL"),
            'berita'          => $this->count('berita', "status = 'published'"),
            'presensi'        => $this->count('presensi_guru', "attendance_date = CURDATE()"),
            'presensi_mobile' => $this->count('presensi_guru', "attendance_date = CURDATE() AND verified_by IS NULL"),
            'jadwal'          => $this->count('jadwal', "is_active = 1"),
            'tugas'           => $this->count('tugas', "is_published = 1"),
            'tokens'          => $this->count('token_api', "revoked_at IS NULL"),
            'pesan_baru'      => $this->count('pesan_kontak', "status = 'baru'"),
            'pesan_total'     => $this->count('pesan_kontak', null),
        ];
    }

    private function count(string $table, ?string $where): int
    {
        try {
            $sql = 'SELECT COUNT(*) FROM `' . $table . '`';
            if ($where !== null) {
                $sql .= ' WHERE ' . $where;
            }

            return (int) $this->pdo->query($sql)->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
}
