<?php

/**
 * Controller Panel Admin untuk Manajemen Pesan Kontak & Kotak Masuk Pengunjung.
 *
 * Seluruh logika query, paginasi, dan mutasi data didelegasikan ke Model `Kontak`.
 */
class AdminKontakController
{
    private Kontak $kontak;

    public function __construct(?Kontak $kontak = null)
    {
        $this->kontak = $kontak ?? new Kontak();
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

        return $user ?? [];
    }

    public function index(): void
    {
        $user = $this->guard();

        $status = trim((string) ($_GET['status'] ?? 'semua'));
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $counts = $this->kontak->getCounts();
        $result = $this->kontak->paginateMessages($status, $q, $page, 15);

        $data = [
            'title' => 'Kotak Masuk Pesan | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'kontak',
            'sub_menu' => '',
            'messages' => $result['items'],
            'counts' => $counts,
            'status' => $status,
            'q' => $q,
            'page' => $result['page'],
            'total_pages' => $result['total_pages'],
            'total' => $result['total'],
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/kontak/index.php';
    }

    public function markRead(int $id): void
    {
        $user = $this->guard();

        if (!$this->verifyPostCsrf()) {
            return;
        }

        if ($id <= 0 || $this->kontak->findById($id) === null) {
            $_SESSION['_flash_error'] = 'Pesan tidak ditemukan.';
            $this->redirectInbox();
            return;
        }

        $changed = $this->kontak->markAsRead($id);
        if ($changed) {
            AuditLog::write(
                isset($user['id']) ? (int) $user['id'] : null,
                'kontak.mark_read',
                'pesan_kontak',
                $id,
                'Menandai pesan kontak sebagai sudah dibaca.',
                RateLimiter::clientIp()
            );
            $_SESSION['_flash_success'] = 'Status pesan berhasil diubah menjadi sudah dibaca.';
        } else {
            $_SESSION['_flash_success'] = 'Pesan sudah berstatus dibaca atau dibalas.';
        }

        $this->redirectInbox();
    }

    public function markReplied(int $id): void
    {
        $user = $this->guard();

        if (!$this->verifyPostCsrf()) {
            return;
        }

        if ($id <= 0 || $this->kontak->findById($id) === null) {
            $_SESSION['_flash_error'] = 'Pesan tidak ditemukan.';
            $this->redirectInbox();
            return;
        }

        $replyNote = isset($_POST['balasan']) ? (string) $_POST['balasan'] : null;
        $adminId = isset($user['id']) ? (int) $user['id'] : null;

        $this->kontak->markAsReplied($id, $adminId, $replyNote);

        AuditLog::write(
            $adminId,
            'kontak.mark_replied',
            'pesan_kontak',
            $id,
            'Menandai pesan kontak sebagai sudah dibalas.',
            RateLimiter::clientIp()
        );

        $_SESSION['_flash_success'] = 'Pesan telah ditandai sebagai sudah dibalas.';
        $this->redirectInbox();
    }

    public function delete(int $id): void
    {
        $user = $this->guard();

        if (!$this->verifyPostCsrf()) {
            return;
        }

        if ($id <= 0 || !$this->kontak->softDelete($id)) {
            $_SESSION['_flash_error'] = 'Pesan tidak ditemukan atau sudah dihapus.';
            $this->redirectInbox();
            return;
        }

        AuditLog::write(
            isset($user['id']) ? (int) $user['id'] : null,
            'kontak.delete',
            'pesan_kontak',
            $id,
            'Menghapus pesan kontak dari kotak masuk.',
            RateLimiter::clientIp()
        );

        $_SESSION['_flash_success'] = 'Pesan kontak berhasil dihapus.';
        $this->redirectInbox();
    }

    private function verifyPostCsrf(): bool
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $_SESSION['_flash_error'] = 'Metode permintaan tidak valid.';
            $this->redirectInbox();
            return false;
        }

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            $this->redirectInbox();
            return false;
        }

        return true;
    }

    private function redirectInbox(): void
    {
        $query = [];
        if (!empty($_POST['redirect_status'])) {
            $query['status'] = (string) $_POST['redirect_status'];
        }
        if (!empty($_POST['redirect_q'])) {
            $query['q'] = (string) $_POST['redirect_q'];
        }
        if (!empty($_POST['redirect_page']) && (int) $_POST['redirect_page'] > 1) {
            $query['page'] = (int) $_POST['redirect_page'];
        }

        $target = base_url('admin/kontak');
        if ($query !== []) {
            $target .= '?' . http_build_query($query);
        }

        header('Location: ' . $target);
        exit;
    }
}
