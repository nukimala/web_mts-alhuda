<?php

/**
 * Controller Halaman Kontak Publik & Penerimaan Pesan Pengunjung.
 *
 * Seluruh validasi, sanitasi, dan query database didelegasikan ke Model `Kontak`.
 */
class KontakController
{
    private Kontak $kontak;

    public function __construct(?Kontak $kontak = null)
    {
        $this->kontak = $kontak ?? new Kontak();
    }

    public function index(): void
    {
        Auth::start();

        $data = [
            'title' => 'Kontak & Layanan Informasi | MTs Al-Huda Gondang',
            'settings' => $this->kontak->getContactSettings(),
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
            'old' => $_SESSION['_old_input'] ?? [],
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error'], $_SESSION['_old_input']);

        require_once __DIR__ . '/../Views/kontak/index.php';
    }

    public function send(): void
    {
        Auth::start();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token sesi tidak valid. Silakan muat ulang halaman.';
            header('Location: ' . base_url('kontak'));
            exit;
        }

        $rawInput = [
            'name' => (string) ($_POST['name'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
            'phone' => (string) ($_POST['phone'] ?? ''),
            'subject' => (string) ($_POST['subject'] ?? ''),
            'message' => (string) ($_POST['message'] ?? ''),
        ];

        $_SESSION['_old_input'] = $rawInput;

        $ip = RateLimiter::clientIp();
        $rate = RateLimiter::hit('kontak_form:' . $ip, 5, 300);
        if (!$rate['allowed']) {
            $minutes = max(1, (int) ceil($rate['retry_after'] / 60));
            $_SESSION['_flash_error'] = "Terlalu banyak pengiriman pesan dari jaringan Anda. Silakan coba lagi dalam {$minutes} menit.";
            header('Location: ' . base_url('kontak'));
            exit;
        }

        $validation = $this->kontak->validateSubmission($rawInput);
        if (!$validation['valid']) {
            $_SESSION['_flash_error'] = $validation['error'];
            header('Location: ' . base_url('kontak'));
            exit;
        }

        try {
            $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            $this->kontak->createMessage($validation['data'], $ip, $userAgent);

            unset($_SESSION['_old_input']);
            $_SESSION['_flash_success'] = 'Terima kasih, pesan Anda telah berhasil dikirim ke pihak MTs Al-Huda. Kami akan segera menindaklanjuti pesan Anda.';
        } catch (PDOException $e) {
            $_SESSION['_flash_error'] = 'Terjadi kesalahan sistem saat mengirim pesan. Silakan hubungi kami via telepon atau WhatsApp.';
        }

        header('Location: ' . base_url('kontak'));
        exit;
    }
}
