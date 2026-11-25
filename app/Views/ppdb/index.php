<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$settings = $data['settings'] ?? [];
$documents = $data['documents'] ?? [];
$ppdbTitle = $settings['ppdb_title'] ?? 'Penerimaan Peserta Didik Baru (PPDB)';
$ppdbStatus = $settings['ppdb_status'] ?? 'Buka';
$ppdbQuota = $settings['ppdb_quota'] ?? '3 Rombel (Rasio Efektif)';
$ppdbContact = $settings['ppdb_contact'] ?? '(0358) 123456 / 0812-3456-7890';
?>

<main>
    <section class="page-hero-clean">
        <div class="container">
            <div class="hero-label">Penerimaan Siswa &amp; Santri Baru</div>
            <h1><?= htmlspecialchars($ppdbTitle) ?></h1>
            <p>
                Selamat datang calon santri MTs Al-Huda Gondang. Kami membuka kesempatan belajar bagi putra-putri lulusan SD/MI untuk menimba ilmu agama dan ilmu pengetahuan umum dalam lingkungan yang asri dan berakhlak mulia.
            </p>
        </div>
    </section>

    <section style="padding: 56px 0 72px; background: #FFFFFF;">
        <div class="container">
            <!-- STATUS PPDB BANNER -->
            <div style="background: #F8FAFC; border: 1px solid var(--border); border-left: 4px solid #15803D; border-radius: 12px; padding: 22px 28px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <span style="font-size: 11.5px; font-weight: 700; color: #15803D; text-transform: uppercase; letter-spacing: 0.06em; display: block; margin-bottom: 4px;">
                        Status Pendaftaran
                    </span>
                    <strong style="font-size: 18px; color: #0F172A;">
                        Pendaftaran Sedang Berlangsung &middot; Kuota: <?= htmlspecialchars($ppdbQuota) ?>
                    </strong>
                </div>
                <div>
                    <a href="<?= url('kontak') ?>" class="btn btn-primary" style="font-size: 13.5px;">
                        Hubungi Panitia PPDB
                    </a>
                </div>
            </div>

            <!-- DUA KOLOM: PERSYARATAN & ALUR -->
            <div class="visi-misi-v2-grid">
                <!-- PERSYARATAN -->
                <article class="visi-v2-card">
                    <span class="vm-tag green">Persyaratan Pendaftaran</span>
                    <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 16px;">
                        Berkas Kelengkapan Calon Santri
                    </h3>
                    <ul style="padding-left: 20px; margin: 0; font-size: 14px; color: #334155; line-height: 1.75;">
                        <li style="margin-bottom: 8px;">Mengisi formulir pendaftaran resmi madrasah.</li>
                        <li style="margin-bottom: 8px;">Fotokopi Ijazah / Surat Keterangan Lulus (SKL) SD/MI yang telah dilegalisir (2 lembar).</li>
                        <li style="margin-bottom: 8px;">Fotokopi Kartu Keluarga (KK) dan Akta Kelahiran calon siswa (2 lembar).</li>
                        <li style="margin-bottom: 8px;">Fotokopi KTP kedua orang tua / wali santri (2 lembar).</li>
                        <li style="margin-bottom: 8px;">Pas foto resmi ukuran 3x4 berpakaian rapi (3 lembar).</li>
                        <li>Fotokopi Kartu Indonesia Pintar (KIP) / PKH bila memiliki.</li>
                    </ul>
                </article>

                <!-- ALUR PENDAFTARAN -->
                <article class="misi-v2-card">
                    <span class="vm-tag blue">Alur Pendaftaran</span>
                    <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin: 0 0 16px;">
                        Tahapan Registrasi Santri Baru
                    </h3>
                    <ol class="misi-v2-list">
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">1</span>
                            <div>
                                <strong style="color: #0F172A; display: block;">Pengambilan &amp; Pengisian Formulir</strong>
                                <span>Mengambil formulir langsung di kantor tata usaha madrasah atau mengunduh dokumen formulir di bawah ini.</span>
                            </div>
                        </li>
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">2</span>
                            <div>
                                <strong style="color: #0F172A; display: block;">Penyerahan &amp; Verifikasi Berkas</strong>
                                <span>Menyerahkan berkas kelengkapan kepada panitia PPDB di MTs Al-Huda Gondang untuk pemeriksaan administrasi.</span>
                            </div>
                        </li>
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">3</span>
                            <div>
                                <strong style="color: #0F172A; display: block;">Observasi Minat &amp; Bimbingan Baca Quran</strong>
                                <span>Pemetaan awal kemampuan membaca Al-Qur'an dan wawancara minat santri bersama orang tua/wali.</span>
                            </div>
                        </li>
                        <li class="misi-v2-item">
                            <span class="misi-v2-num">4</span>
                            <div>
                                <strong style="color: #0F172A; display: block;">Daftar Ulang &amp; Masa Ta'aruf Siswa Madrasah (MATSAMA)</strong>
                                <span>Pemberkasan akhir, pembagian seragam, dan pengenalan lingkungan belajar madrasah.</span>
                            </div>
                        </li>
                    </ol>
                </article>
            </div>

            <!-- UNDUHAN DOKUMEN PPDB RESMI -->
            <div class="doc-card" style="margin-top: 40px; margin-bottom: 0;">
                <div class="doc-card-header" style="align-items: flex-start;">
                    <div>
                        <div class="section-pretitle">Berkas Resmi</div>
                        <h2 class="doc-card-title">Unduhan Formulir &amp; Brosur PPDB</h2>
                        <p style="margin: 6px 0 0; font-size: 13.5px; color: #64748B;">
                            Silakan unduh dokumen panduan dan formulir pendaftaran resmi di bawah ini untuk kelengkapan administrasi calon santri.
                        </p>
                    </div>
                    <?php if (!empty($documents)): ?>
                        <div style="font-size: 12.5px; color: #15803D; background: #DCFCE7; border: 1px solid #BBF7D0; font-weight: 700; padding: 6px 14px; border-radius: 999px; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?= count($documents) ?> Berkas Resmi
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($documents)): ?>
                    <div style="padding: 32px 24px; background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 12px; text-align: center; color: #64748B; font-size: 14px;">
                        <div style="font-weight: 700; color: #0F172A; margin-bottom: 4px;">Belum Ada Berkas Digital yang Diunggah</div>
                        Formulir cetak dan brosur dapat diambil langsung di sekretariat pendaftaran madrasah setiap hari kerja (07.00 - 14.00 WIB).
                    </div>
                <?php else: ?>
                    <div class="ppdb-doc-grid">
                        <?php foreach ($documents as $doc): ?>
                            <?php 
                                $docInfo = doc_type_badge($doc['mime_type'] ?? '', $doc['file_path'] ?? '');
                                $fileSizeFormatted = !empty($doc['file_size']) ? format_bytes((int)$doc['file_size']) : '';
                            ?>
                            <div class="ppdb-doc-item">
                                <div class="ppdb-doc-top">
                                    <div class="ppdb-doc-icon-box" style="background: <?= $docInfo['bg'] ?>; color: <?= $docInfo['color'] ?>; border: 1px solid <?= $docInfo['border'] ?>;">
                                        <?php if ($docInfo['icon'] === 'pdf'): ?>
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                            </svg>
                                        <?php elseif ($docInfo['icon'] === 'word'): ?>
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                                <line x1="9" y1="15" x2="15" y2="15"></line>
                                                <line x1="12" y1="12" x2="12" y2="18"></line>
                                            </svg>
                                        <?php elseif ($docInfo['icon'] === 'excel'): ?>
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                                <path d="M8 13h8M8 17h8"></path>
                                            </svg>
                                        <?php else: ?>
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <span class="ppdb-type-badge" style="background: <?= $docInfo['bg'] ?>; color: <?= $docInfo['color'] ?>; border: 1px solid <?= $docInfo['border'] ?>;">
                                            <?= $docInfo['type'] ?>
                                        </span>
                                        <?php if ($fileSizeFormatted !== ''): ?>
                                            <span style="font-size: 11.5px; font-weight: 600; color: #64748B;">
                                                <?= $fileSizeFormatted ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="ppdb-doc-content">
                                    <h3 class="ppdb-doc-name" title="<?= htmlspecialchars($doc['title']) ?>">
                                        <?= htmlspecialchars($doc['title']) ?>
                                    </h3>
                                    <?php if (!empty($doc['description'])): ?>
                                        <p class="ppdb-doc-desc">
                                            <?= htmlspecialchars($doc['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="ppdb-doc-bottom">
                                    <div class="ppdb-doc-counter">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                                        </svg>
                                        <span><?= number_format((int)($doc['download_count'] ?? 0), 0, ',', '.') ?>x diunduh</span>
                                    </div>
                                    <a href="<?= base_url('ppdb/download/' . $doc['id']) ?>" target="_blank" download class="btn-doc-download">
                                        <span>Unduh Berkas</span>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M5 12h14M12 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <style>
            .ppdb-doc-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
                gap: 20px;
                align-items: stretch;
            }

            .ppdb-doc-item {
                background: #FFFFFF;
                border: 1px solid var(--border, #E2E8F0);
                border-radius: 14px;
                padding: 22px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            }

            .ppdb-doc-item:hover {
                border-color: #15803D;
                transform: translateY(-2px);
                box-shadow: 0 10px 24px -6px rgba(21, 128, 61, 0.12), 0 4px 8px -2px rgba(15, 23, 42, 0.04);
            }

            .ppdb-doc-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 14px;
            }

            .ppdb-doc-icon-box {
                width: 44px;
                height: 44px;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .ppdb-type-badge {
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.04em;
                padding: 3px 8px;
                border-radius: 6px;
                text-transform: uppercase;
            }

            .ppdb-doc-content {
                flex-grow: 1;
                margin-bottom: 18px;
            }

            .ppdb-doc-name {
                font-size: 15px;
                font-weight: 700;
                color: #0F172A;
                line-height: 1.45;
                margin: 0 0 6px;
                word-break: break-word;
            }

            .ppdb-doc-desc {
                font-size: 13px;
                color: #64748B;
                line-height: 1.5;
                margin: 0;
                word-break: break-word;
            }

            .ppdb-doc-bottom {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding-top: 14px;
                border-top: 1px solid #F1F5F9;
                gap: 12px;
                margin-top: auto;
            }

            .ppdb-doc-counter {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 12px;
                color: #64748B;
                font-weight: 600;
                white-space: nowrap;
            }

            .btn-doc-download {
                background: #F0FDF4;
                color: #15803D;
                border: 1.5px solid #BBF7D0;
                border-radius: 8px;
                padding: 7px 14px;
                font-size: 12.5px;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                text-decoration: none;
                white-space: nowrap;
                transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
            }

            .btn-doc-download:hover {
                background: #15803D;
                color: #FFFFFF;
                border-color: #15803D;
                box-shadow: 0 2px 8px rgba(21, 128, 61, 0.25);
            }

            .btn-doc-download svg {
                transition: transform 0.2s ease;
            }

            .btn-doc-download:hover svg {
                transform: translateX(2px);
            }

            @media (max-width: 640px) {
                .ppdb-doc-grid {
                    grid-template-columns: 1fr;
                }
            }
            </style>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
