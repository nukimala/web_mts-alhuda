<?php

require_once __DIR__ . '/../Support/Database.php';

class FasilitasController
{
    public function index(): void
    {
        $pdo = Database::connection();

        // Soft-delete wajib difilter di sini. Tanpa `deleted_at IS NULL`,
        // fasilitas yang dihapus admin masih tampil di halaman publik.
        $stmt = $pdo->query("SELECT * FROM fasilitas WHERE is_active = 1 AND deleted_at IS NULL ORDER BY sort_order ASC, name ASC");
        $facilities = $stmt->fetchAll();

        $totalFacilities = count($facilities);
        $layakFacilities = count(array_filter($facilities, static function (array $fac): bool {
            $condition = $fac['condition'] ?? $fac['kondisi'] ?? 'baik';
            return $condition === 'sangat_baik' || $condition === 'baik';
        }));
        $persentaseLayak = $totalFacilities > 0 ? (int) round($layakFacilities / $totalFacilities * 100) : 0;

        $data = [
            'title' => 'Fasilitas & Sarana Prasarana | MTs Al-Huda Gondang',
            'facilities' => $facilities,
            'persentase_layak' => $persentaseLayak,
            'total_fasilitas' => $totalFacilities,
        ];

        require_once __DIR__ . '/../Views/fasilitas/index.php';
    }
}
