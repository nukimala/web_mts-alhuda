<?php

require_once __DIR__ . '/../Models/Pengaturan.php';

class AdminPengaturanController
{
    private Pengaturan $pengaturanModel;

    public function __construct()
    {
        $this->pengaturanModel = new Pengaturan();
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

        return $user;
    }

    public function index(): void
    {
        $user = $this->guard();

        // Ambil data pengaturan beserta nilai default via Model
        $settings = $this->pengaturanModel->getAllWithDefaults();

        $tab = trim((string) ($_GET['tab'] ?? 'identitas'));

        $data = [
            'title' => 'Pengaturan Situs & Aplikasi | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'pengaturan',
            'tab' => $tab,
            'settings' => $settings,
            'pengaturan' => $settings,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/pengaturan/index.php';
    }

    public function update(): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/pengaturan'));
            return;
        }

        $tab = trim((string) ($_POST['current_tab'] ?? 'identitas'));

        $keys = [
            // Identitas
            'school_name' => ['identitas', 1],
            'site_name' => ['identitas', 1],
            'npsn' => ['identitas', 1],
            'site_npsn' => ['identitas', 1],
            'foundation_name' => ['identitas', 1],
            'site_foundation' => ['identitas', 1],
            'accreditation_grade' => ['identitas', 1],
            'school_address' => ['identitas', 1],
            'contact_address' => ['identitas', 1],
            'school_vision' => ['identitas', 1],
            'school_motto' => ['identitas', 1],

            // Kontak
            'phone' => ['kontak', 1],
            'contact_phone' => ['kontak', 1],
            'email' => ['kontak', 1],
            'contact_email' => ['kontak', 1],
            'whatsapp' => ['kontak', 1],
            'contact_whatsapp' => ['kontak', 1],
            'contact_hours' => ['kontak', 1],
            'maps_embed' => ['kontak', 1],

            // Media Sosial
            'facebook_url' => ['sosial', 1],
            'social_facebook' => ['sosial', 1],
            'instagram_url' => ['sosial', 1],
            'social_instagram' => ['sosial', 1],
            'youtube_url' => ['sosial', 1],
            'social_youtube' => ['sosial', 1],

            // SEO
            'meta_title' => ['seo', 1],
            'meta_description' => ['seo', 1],
            'meta_keywords' => ['seo', 1],

            // Email
            'smtp_host' => ['email', 0],
            'smtp_port' => ['email', 0],
            'smtp_user' => ['email', 0],
            'smtp_from_name' => ['email', 0],

            // Keamanan
            'session_timeout' => ['keamanan', 0],
            'max_login_attempts' => ['keamanan', 0],

            // Presensi & Aplikasi Mobile (MADRASAHKU)
            'attendance_check_in_time' => ['presensi', 1],
            'attendance_late_tolerance' => ['presensi', 1],
            'attendance_check_out_time' => ['presensi', 1],
            'attendance_lat' => ['presensi', 1],
            'attendance_lng' => ['presensi', 1],
            'contact_map_lat' => ['presensi', 1],
            'contact_map_lng' => ['presensi', 1],
            'attendance_radius_meters' => ['presensi', 1],
            'attendance_mobile_enabled' => ['presensi', 1],
        ];

        // Simpan pembaruan secara terpusat melalui Model Pengaturan
        $this->pengaturanModel->updateBatch($_POST, $keys);

        Auth::log('pengaturan.update', 'pengaturan', null, "Memperbarui pengaturan situs tab {$tab}");

        $_SESSION['_flash_success'] = 'Seluruh pengaturan berhasil disimpan.';
        header('Location: ' . base_url('admin/pengaturan?tab=' . $tab));
    }
}
