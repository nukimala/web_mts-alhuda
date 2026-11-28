<?php

require_once __DIR__ . '/../Support/Database.php';
require_once __DIR__ . '/../Models/Pengaturan.php';

class HomeController
{
    private Pengaturan $pengaturanModel;

    public function __construct()
    {
        $this->pengaturanModel = new Pengaturan();
    }

    public function index(): void
    {
        $pdo = Database::connection();

        // 1. Berita terbaru terbit
        $beritaStmt = $pdo->query("SELECT p.*, u.name as author_name 
                                  FROM berita p 
                                  LEFT JOIN pengguna u ON u.id = p.author_id 
                                  WHERE p.status = 'published' AND p.deleted_at IS NULL 
                                  ORDER BY p.is_featured DESC, p.published_at DESC 
                                  LIMIT 3");
        $latestPosts = $beritaStmt->fetchAll();

        // 2. Fasilitas unggulan
        $facStmt = $pdo->query("SELECT * FROM fasilitas WHERE is_active = 1 AND deleted_at IS NULL ORDER BY sort_order ASC LIMIT 4");
        $facilities = $facStmt->fetchAll();

        // 3. Pengaturan madrasah via Model
        $settingsRaw = $this->pengaturanModel->getPublicSettings();

        $data = [
            'title' => 'MTs Al-Huda Gondang | Madrasah Berakhlak Mulia & Berprestasi',
            'latest_posts' => $latestPosts,
            'facilities' => $facilities,
            'settings' => $settingsRaw,
        ];

        require_once __DIR__ . '/../Views/home/index.php';
    }
}
