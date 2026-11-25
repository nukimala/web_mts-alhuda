<?php

require_once __DIR__ . '/../Support/Database.php';

class PpdbController
{
    public function index(): void
    {
        $pdo = Database::connection();

        $settings = $pdo->query("SELECT setting_key, setting_value FROM pengaturan WHERE group_name = 'ppdb' OR is_public = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
        $documents = $pdo->query("SELECT * FROM dokumen_ppdb WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC")->fetchAll();

        $data = [
            'title' => 'Penerimaan Peserta Didik Baru (PPDB) | MTs Al-Huda Gondang',
            'settings' => $settings,
            'documents' => $documents,
        ];

        require_once __DIR__ . '/../Views/ppdb/index.php';
    }

    public function download(int $id): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT * FROM dokumen_ppdb WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            http_response_code(404);
            (new ErrorController())->notFound();
            return;
        }

        // Tingkatkan counter unduhan
        try {
            $updateStmt = $pdo->prepare("UPDATE dokumen_ppdb SET download_count = download_count + 1, jumlah_unduh = jumlah_unduh + 1 WHERE id = ?");
            $updateStmt->execute([$id]);
        } catch (\Throwable $e) {
            // Kolom fleksibel
        }

        $filePath = $doc['file_path'] ?? '';
        $fullPath = __DIR__ . '/../../public/' . ltrim($filePath, '/');

        if (file_exists($fullPath) && is_file($fullPath)) {
            $filename = basename($fullPath);
            $mimeType = (!empty($doc['mime_type']) && $doc['mime_type'] !== 'FILE') 
                ? $doc['mime_type'] 
                : (mime_content_type($fullPath) ?: 'application/octet-stream');
            
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fullPath));
            readfile($fullPath);
            exit;
        }

        http_response_code(404);
        (new ErrorController())->notFound();
    }

    public function informasi(): void
    {
        $this->index();
    }
}
