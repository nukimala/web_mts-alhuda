
<?php

require_once __DIR__ . '/../Support/Database.php';

class Galeri
{
    /**
     * Mengambil seluruh foto galeri yang aktif
     * dan belum dihapus.
     */
    public function getActiveItems(): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT *
            FROM galeri
            WHERE is_active = 1
              AND deleted_at IS NULL
            ORDER BY sort_order ASC, created_at DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil daftar album yang memiliki foto aktif.
     */
    public function getActiveAlbums(): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT DISTINCT album
            FROM galeri
            WHERE is_active = 1
              AND deleted_at IS NULL
              AND album IS NOT NULL
              AND album != ''
            ORDER BY album ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
