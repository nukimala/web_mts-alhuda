<?php

/**
 * Zona waktu aplikasi.
 *
 * PHP di mesin ini default ke Europe/Berlin, sementara MySQL menyimpan WIB.
 * Tanpa ini, nilai waktu yang dibuat PHP (mis. locked_until) akan meleset
 * 7 jam dari nilai yang dibuat MySQL (mis. NOW()), dan perbandingan di SQL
 * jadi salah tanpa terlihat.
 *
 * Semua waktu di sistem ini WIB. Tidak ada pengecualian.
 */
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Jakarta');

if (!function_exists('base_path')) {
    function base_path(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

        if ($dir === '.' || $dir === '/') {
            $dir = '';
        }

        if (substr($dir, -7) === '/public') {
            $dir = substr($dir, 0, -7);
        } elseif ($dir === 'public') {
            $dir = '';
        }

        return $dir;
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        $base = base_path();

        if ($path === '') {
            return $base === '' ? '/' : $base;
        }

        return ($base === '' ? '' : $base) . '/' . $path;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return base_url($path);
    }
}

if (!function_exists('current_route')) {
    function current_route(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $uri = rawurldecode($uri);

        $base = base_path();
        if ($base !== '' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }

        $route = trim($uri, '/');

        if (strpos($route, 'public/') === 0) {
            $route = substr($route, 7);
        } elseif ($route === 'public') {
            $route = '';
        }

        return $route;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return base_url($path);
    }
}

if (!function_exists('e')) {
    /**
     * Escape nilai sebelum dicetak di HTML.
     *
     * Semua data dari database atau input pengguna wajib lewat sini.
     * Tanpa ini, judul berita berisi <script> akan ikut dijalankan browser.
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('doc_type_badge')) {
    function doc_type_badge(?string $mime, string $path = ''): array
    {
        $mime = strtolower((string) $mime);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (strpos($mime, 'pdf') !== false || $ext === 'pdf') {
            return [
                'type'   => 'PDF',
                'color'  => '#DC2626',
                'bg'     => '#FEE2E2',
                'border' => '#FECACA',
                'icon'   => 'pdf',
            ];
        }

        if (
            strpos($mime, 'word') !== false || 
            strpos($mime, 'wordprocessingml') !== false || 
            in_array($ext, ['doc', 'docx'], true)
        ) {
            return [
                'type'   => 'DOCX',
                'color'  => '#2563EB',
                'bg'     => '#DBEAFE',
                'border' => '#BFDBFE',
                'icon'   => 'word',
            ];
        }

        if (
            strpos($mime, 'excel') !== false || 
            strpos($mime, 'spreadsheetml') !== false || 
            in_array($ext, ['xls', 'xlsx'], true)
        ) {
            return [
                'type'   => 'EXCEL',
                'color'  => '#16A34A',
                'bg'     => '#DCFCE7',
                'border' => '#BBF7D0',
                'icon'   => 'excel',
            ];
        }

        if (
            strpos($mime, 'image/') === 0 || 
            in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)
        ) {
            return [
                'type'   => 'GAMBAR',
                'color'  => '#D97706',
                'bg'     => '#FEF3C7',
                'border' => '#FDE68A',
                'icon'   => 'image',
            ];
        }

        return [
            'type'   => $ext !== '' ? strtoupper($ext) : 'BERKAS',
            'color'  => '#475569',
            'bg'     => '#F1F5F9',
            'border' => '#E2E8F0',
            'icon'   => 'file',
        ];
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);
        $value = $bytes / (1024 ** $power);
        return round($value, $power > 1 ? 1 : 0) . ' ' . $units[$power];
    }
}
