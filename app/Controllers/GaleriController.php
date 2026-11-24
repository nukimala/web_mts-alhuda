
<?php

require_once __DIR__ . '/../Models/Galeri.php';

class GaleriController
{
    public function index(): void
    {
        $galeriModel = new Galeri();

        // Ambil data dari Model
        $items = $galeriModel->getActiveItems();
        $albums = $galeriModel->getActiveAlbums();

        // Kirim data ke halaman View
        $data = [
            'title' => 'Galeri Foto & Dokumentasi Kegiatan | MTs Al-Huda Gondang',
            'items' => $items,
            'albums' => $albums,
        ];

        require_once __DIR__ . '/../Views/galeri/index.php';
    }
}
