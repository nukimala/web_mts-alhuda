<?php

require_once __DIR__ . '/../Models/Pengguna.php';

class AdminPenggunaController
{
    private Pengguna $penggunaModel;

    public function __construct()
    {
        $this->penggunaModel = new Pengguna();
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
        $currentUser = $this->guard();

        $role = trim((string) ($_GET['role'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $q = trim((string) ($_GET['q'] ?? ''));

        // 1. Ambil daftar pengguna dan kelas melalui Model
        $users = $this->penggunaModel->getAll($role, $status, $q);
        $classes = $this->penggunaModel->getActiveClasses();

        // 2. Ambil statistik pengguna per role melalui Model
        $stats = $this->penggunaModel->getRoleStats();

        $data = [
            'title' => 'Pengguna & Akun Mobile App | Panel Admin MTs Al-Huda',
            'user' => $currentUser,
            'role' => Auth::displayRole(),
            'active_menu' => 'pengguna',
            'users' => $users,
            'pengguna' => $users,
            'classes' => $classes,
            'stat_guru' => $stats['guru'],
            'stat_siswa' => $stats['siswa'],
            'stat_operator' => $stats['operator'],
            'stat_kepala' => $stats['kepala'],
            'stat_akreditasi' => $stats['akreditasi'],
            'current_role' => $role,
            'current_status' => $status,
            'q' => $q,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/pengguna/index.php';
    }

    public function store(): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? 'guru'));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || $email === '') {
            $_SESSION['_flash_error'] = 'Nama lengkap dan email wajib diisi.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['_flash_error'] = 'Format email tidak valid.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        // Enum `pengguna.role` di database: admin, operator, kepala, guru, siswa.
        // 'akreditasi' tidak ada di enum, dipetakan ke operator agar penyimpanan valid.
        if ($role === 'akreditasi') {
            $role = 'operator';
        }

        if (!in_array($role, ['admin', 'operator', 'kepala', 'guru', 'siswa'], true)) {
            $role = 'guru';
        }

        if ($password === '') {
            $password = '123';
        }

        // Cek keunikan email via Model
        if ($this->penggunaModel->emailExists($email)) {
            $_SESSION['_flash_error'] = 'Email sudah digunakan oleh pengguna lain.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        // Cek keunikan username jika diisi via Model
        if ($username !== '') {
            if ($this->penggunaModel->usernameExists($username)) {
                $_SESSION['_flash_error'] = 'Nama pengguna (username) sudah digunakan.';
                header('Location: ' . base_url('admin/pengguna'));
                return;
            }
        } else {
            $username = explode('@', $email)[0];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $gender = in_array($_POST['gender'] ?? 'L', ['L', 'P'], true) ? (string) $_POST['gender'] : 'L';
        $classId = !empty($_POST['class_id']) && (int) $_POST['class_id'] > 0 ? (int) $_POST['class_id'] : null;

        // Siswa wajib memilih kelas
        if ($role === 'siswa' && $classId === null) {
            $_SESSION['_flash_error'] = 'Kelas wajib dipilih untuk akun siswa.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $userData = [
            'name' => $name,
            'nama' => $name,
            'email' => $email,
            'username' => $username,
            'password_hash' => $passwordHash,
            'role' => $role,
        ];

        $teacherData = null;
        if ($role === 'guru') {
            $teacherData = [
                'nip' => !empty($_POST['nip']) ? trim((string) $_POST['nip']) : null,
                'nuptk' => !empty($_POST['nuptk']) ? trim((string) $_POST['nuptk']) : null,
                'subject_specialty' => !empty($_POST['subject_specialty']) ? trim((string) $_POST['subject_specialty']) : null,
                'gender' => $gender,
            ];
        }

        $studentData = null;
        if ($role === 'siswa') {
            $studentData = [
                'nis' => !empty($_POST['nis']) ? trim((string) $_POST['nis']) : ('S' . date('Y') . sprintf('%04d', rand(1, 9999))),
                'nisn' => !empty($_POST['nisn']) ? trim((string) $_POST['nisn']) : null,
                'class_id' => $classId,
                'gender' => $gender,
            ];
        }

        try {
            $newUserId = $this->penggunaModel->create($userData, $teacherData, $studentData);

            Auth::log('pengguna.create', 'pengguna', $newUserId, "Membuat akun pengguna: {$email} ({$role})");

            $_SESSION['_flash_success'] = "Pengguna '{$name}' ({$role}) berhasil dibuat. Sandi awal: {$password}";
        } catch (Throwable $e) {
            $_SESSION['_flash_error'] = 'Gagal membuat pengguna: ' . $e->getMessage();
        }

        header('Location: ' . base_url('admin/pengguna'));
    }

    public function resetPassword(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $this->penggunaModel->resetPassword($id, '123');

        Auth::log('pengguna.reset_password', 'pengguna', $id, "Mereset kata sandi pengguna ID {$id}");

        $_SESSION['_flash_success'] = "Kata sandi berhasil direset menjadi: 123";
        header('Location: ' . base_url('admin/pengguna'));
    }

    public function toggleStatus(int $id): void
    {
        $currentUser = $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        if ((int) $currentUser['id'] === $id) {
            $_SESSION['_flash_error'] = 'Anda tidak dapat menonaktifkan akun sendiri.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $this->penggunaModel->toggleStatus($id);

        Auth::log('pengguna.toggle_status', 'pengguna', $id, "Mengubah status aktif pengguna ID {$id}");

        $_SESSION['_flash_success'] = 'Status aktif pengguna berhasil diubah.';
        header('Location: ' . base_url('admin/pengguna'));
    }

    public function delete(int $id): void
    {
        $currentUser = $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        if ((int) $currentUser['id'] === $id) {
            $_SESSION['_flash_error'] = 'Anda tidak dapat menghapus akun sendiri.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $this->penggunaModel->softDelete($id);

        Auth::log('pengguna.delete', 'pengguna', $id, "Menghapus (soft delete) pengguna ID {$id}");

        $_SESSION['_flash_success'] = 'Pengguna berhasil dihapus.';
        header('Location: ' . base_url('admin/pengguna'));
    }
}
