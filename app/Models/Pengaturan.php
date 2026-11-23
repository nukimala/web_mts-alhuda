<?php

require_once __DIR__ . '/../Support/Database.php';

class Pengaturan
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * Ambil semua data pengaturan dari database sebagai pasangan key => value.
     *
     * @return array<string, string|null>
     */
    public function getAll(): array
    {
        $rows = $this->pdo->query("SELECT setting_key, setting_value, group_name FROM pengaturan")->fetchAll(PDO::FETCH_ASSOC);
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        return $settings;
    }

    /**
     * Ambil semua data pengaturan beserta default fallback dan alias kompatibilitas.
     *
     * @return array<string, string|null>
     */
    public function getAllWithDefaults(): array
    {
        $settings = $this->getAll();

        // Sinkronisasi alias untuk view dan fallback default
        $settings['school_name'] = $settings['school_name'] ?? $settings['site_name'] ?? 'MTs Al-Huda Gondang';
        $settings['npsn'] = $settings['npsn'] ?? $settings['site_npsn'] ?? '20512345';
        $settings['foundation_name'] = $settings['foundation_name'] ?? $settings['site_foundation'] ?? 'Yayasan Pendidikan Al-Huda Gondang';
        $settings['phone'] = $settings['phone'] ?? $settings['contact_phone'] ?? '(0358) 123456';
        $settings['email'] = $settings['email'] ?? $settings['contact_email'] ?? 'info@mts-alhuda.sch.id';
        $settings['whatsapp'] = $settings['whatsapp'] ?? $settings['contact_whatsapp'] ?? '0812-3456-7890';
        $settings['school_address'] = $settings['school_address'] ?? $settings['contact_address'] ?? 'Jl. Raya Gondang No. 12, Gondang, Kab. Nganjuk, Jawa Timur';

        // Default Presensi & Mobile App (MADRASAHKU)
        $settings['attendance_check_in_time'] = $settings['attendance_check_in_time'] ?? '06:30';
        $settings['attendance_late_tolerance'] = $settings['attendance_late_tolerance'] ?? '15';
        $settings['attendance_check_out_time'] = $settings['attendance_check_out_time'] ?? '14:00';
        $settings['attendance_lat'] = $settings['attendance_lat'] ?? $settings['contact_map_lat'] ?? '-7.589123';
        $settings['attendance_lng'] = $settings['attendance_lng'] ?? $settings['contact_map_lng'] ?? '112.012345';
        $settings['attendance_radius_meters'] = $settings['attendance_radius_meters'] ?? '100';
        $settings['attendance_mobile_enabled'] = $settings['attendance_mobile_enabled'] ?? '1';

        return $settings;
    }

    /**
     * Ambil pengaturan publik (is_public = 1) untuk frontend website.
     *
     * @return array<string, string|null>
     */
    public function getPublicSettings(): array
    {
        return $this->pdo->query("SELECT setting_key, setting_value FROM pengaturan WHERE is_public = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Ambil satu nilai pengaturan berdasarkan key.
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM pengaturan WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : $default;
    }

    /**
     * Simpan atau perbarui satu nilai pengaturan.
     */
    public function set(string $key, string $value, string $group = 'umum', int $isPublic = 0): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO pengaturan (setting_key, setting_value, group_name, is_public, created_at, updated_at) 
                                     VALUES (?, ?, ?, ?, NOW(), NOW()) 
                                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), is_public = VALUES(is_public), updated_at = NOW()");
        return $stmt->execute([$key, $value, $group, $isPublic]);
    }

    /**
     * Simpan kumpulan data pengaturan form beserta sinkronisasi alias key.
     *
     * @param array<string, mixed> $postData
     * @param array<string, array{0: string, 1: int}> $configKeys
     */
    public function updateBatch(array $postData, array $configKeys): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO pengaturan (setting_key, setting_value, group_name, is_public, created_at, updated_at) 
                                     VALUES (?, ?, ?, ?, NOW(), NOW()) 
                                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), is_public = VALUES(is_public), updated_at = NOW()");

        $aliases = [
            'school_name'     => 'site_name',
            'npsn'            => 'site_npsn',
            'foundation_name' => 'site_foundation',
            'phone'           => 'contact_phone',
            'email'           => 'contact_email',
            'whatsapp'        => 'contact_whatsapp',
            'school_address'  => 'contact_address',
            'attendance_lat'  => 'contact_map_lat',
            'attendance_lng'  => 'contact_map_lng',
        ];

        foreach ($configKeys as $k => $conf) {
            [$group, $isPub] = $conf;
            if (isset($postData[$k])) {
                $val = trim((string) $postData[$k]);
                $stmt->execute([$k, $val, $group, $isPub]);

                // Duplikasi alias agar kompatibel dengan modul lain
                if (isset($aliases[$k])) {
                    $stmt->execute([$aliases[$k], $val, $group, $isPub]);
                }
            }
        }
    }
}
