<?php
/**
 * Dashboard panel admin.
 *
 * Mengagregasi data dari database: CMS, Akreditasi, dan Ekosistem Mobile App MADRASAHKU.
 */

require_once __DIR__ . '/../Models/Admin.php';

class AdminController
{
    private Admin $adminModel;

    public function __construct()
    {
        $this->adminModel = new Admin();
    }

    public function dashboard(): void
    {
        if (!Auth::check()) {
            header('Location: ' . base_url('admin/login'));
            return;
        }

        // Kata sandi sementara belum diganti — kunci akses ke halaman lain.
        $user = Auth::user();
        if ($user !== null && (int) $user['must_change_password'] === 1) {
            header('Location: ' . base_url('admin/ganti-sandi'));
            return;
        }

        $data = [
            'title' => 'Beranda Panel Admin | MTs Al-Huda Gondang',
            'user'  => $user,
            'role'  => Auth::displayRole(),
            'stats' => $this->adminModel->getDashboardStats(),
            'flash' => $_SESSION['_flash_success'] ?? null,
        ];

        unset($_SESSION['_flash_success']);

        require_once __DIR__ . '/../Views/admin/dashboard.php';
    }
}
