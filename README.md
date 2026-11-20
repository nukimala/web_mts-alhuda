# web_mts-alhuda
```
web_mts-alhuda
├─ app
│  ├─ Controllers
│  │  ├─ AdminBeritaController.php
│  │  ├─ AdminController.php
│  │  ├─ AdminFasilitasController.php
│  │  ├─ AdminGaleriController.php
│  │  ├─ AdminJadwalController.php
│  │  ├─ AdminKontakController.php
│  │  ├─ AdminLaporanController.php
│  │  ├─ AdminMenuController.php
│  │  ├─ AdminPengaturanController.php
│  │  ├─ AdminPenggunaController.php
│  │  ├─ AdminPpdbController.php
│  │  ├─ AdminPresensiController.php
│  │  ├─ AkademikController.php
│  │  ├─ Api
│  │  │  ├─ ApiAcademicController.php
│  │  │  ├─ ApiAttendanceController.php
│  │  │  ├─ ApiAuthController.php
│  │  │  ├─ ApiContentController.php
│  │  │  └─ ApiNotificationController.php
│  │  ├─ AuthController.php
│  │  ├─ BeritaController.php
│  │  ├─ ErrorController.php
│  │  ├─ FasilitasController.php
│  │  ├─ GaleriController.php
│  │  ├─ HomeController.php
│  │  ├─ KontakController.php
│  │  ├─ MenuController.php
│  │  ├─ PpdbController.php
│  │  └─ ProfilController.php
│  ├─ helpers.php
│  ├─ Models
│  │  ├─ Admin.php
│  │  ├─ Akademik.php
│  │  ├─ Berita.php
│  │  ├─ Fasilitas.php
│  │  ├─ Galeri.php
│  │  ├─ Kontak.php
│  │  ├─ Menu.php
│  │  ├─ Ppdb.php
│  │  └─ Profil.php
│  ├─ Support
│  │  ├─ ApiAuth.php
│  │  ├─ ApiFormat.php
│  │  ├─ ApiResponse.php
│  │  ├─ ApiRouter.php
│  │  ├─ AuditLog.php
│  │  ├─ Auth.php
│  │  ├─ Csrf.php
│  │  ├─ Database.php
│  │  ├─ RateLimiter.php
│  │  └─ ServerLayout.php
│  └─ Views
│     ├─ admin
│     │  ├─ berita
│     │  │  ├─ create.php
│     │  │  ├─ edit.php
│     │  │  └─ index.php
│     │  ├─ dashboard.php
│     │  ├─ fasilitas
│     │  │  ├─ create.php
│     │  │  ├─ edit.php
│     │  │  └─ index.php
│     │  ├─ galeri
│     │  │  ├─ create.php
│     │  │  ├─ edit.php
│     │  │  └─ index.php
│     │  ├─ ganti-sandi.php
│     │  ├─ jadwal
│     │  │  └─ index.php
│     │  ├─ kontak
│     │  │  └─ index.php
│     │  ├─ laporan
│     │  │  └─ index.php
│     │  ├─ login.php
│     │  ├─ menu
│     │  │  ├─ create.php
│     │  │  ├─ edit.php
│     │  │  └─ index.php
│     │  ├─ pengaturan
│     │  │  └─ index.php
│     │  ├─ pengguna
│     │  │  └─ index.php
│     │  ├─ ppdb
│     │  │  └─ index.php
│     │  └─ presensi
│     │     └─ index.php
│     ├─ akademik
│     │  └─ index.php
│     ├─ berita
│     │  ├─ detail.php
│     │  └─ index.php
│     ├─ errors
│     │  └─ 404.php
│     ├─ fasilitas
│     │  └─ index.php
│     ├─ galeri
│     │  └─ index.php
│     ├─ home
│     │  └─ index.php
│     ├─ kontak
│     │  └─ index.php
│     ├─ layouts
│     │  ├─ admin.php
│     │  ├─ footer.php
│     │  ├─ header.php
│     │  └─ navbar.php
│     ├─ ppdb
│     │  └─ index.php
│     └─ profil
│        └─ index.php
├─ config
│  ├─ database.example.php
│  ├─ database.local.php.example
│  ├─ database.php
│  └─ ftp.local.php.example
├─ database
│  ├─ migrations
│  │  ├─ 001_foundation.sql
│  │  ├─ 002_identity_core.sql
│  │  ├─ 003_identity_security.sql
│  │  ├─ 004_cms_content.sql
│  │  ├─ 005_cms_support.sql
│  │  ├─ 006_academic_core.sql
│  │  ├─ 007_academic_learning.sql
│  │  ├─ 008_teacher_attendance.sql
│  │  ├─ 009_notifications.sql
│  │  ├─ 010_accreditation_structure.sql
│  │  ├─ 011_accreditation_evidence.sql
│  │  ├─ 012_accreditation_documents.sql
│  │  ├─ 013_api_support.sql
│  │  ├─ 014_public_ids.sql
│  │  ├─ 015_madrasahku_integration.sql
│  │  └─ 016_madrasahku_full_schema.sql
│  ├─ mts_alhuda.sql
│  ├─ mts_alhuda_production.sql
│  ├─ README.md
│  └─ seeders
│     ├─ 001_settings.sql
│     └─ demo
│        └─ DEMO-002_demo_content.sql
├─ postman
│  ├─ collections
│  │  ├─ MTs Al-Huda API - Full Test
│  │  │  ├─ .resources
│  │  │  │  └─ definition.yaml
│  │  │  ├─ 01 - Autentikasi
│  │  │  │  ├─ .resources
│  │  │  │  │  └─ definition.yaml
│  │  │  │  ├─ 01-health.request.yaml
│  │  │  │  ├─ 02-settings-public.request.yaml
│  │  │  │  ├─ 03-login.request.yaml
│  │  │  │  ├─ 04-me.request.yaml
│  │  │  │  ├─ 05-refresh-token.request.yaml
│  │  │  │  ├─ 06-me-after-refresh.request.yaml
│  │  │  │  └─ 07-refresh-old-token.request.yaml
│  │  │  ├─ 02 - Konten Publik
│  │  │  │  ├─ .resources
│  │  │  │  │  └─ definition.yaml
│  │  │  │  ├─ 08-posts.request.yaml
│  │  │  │  ├─ 09-post-detail.request.yaml
│  │  │  │  ├─ 10-announcements.request.yaml
│  │  │  │  ├─ 11-agendas.request.yaml
│  │  │  │  ├─ 12-facilities.request.yaml
│  │  │  │  ├─ 13-galleries.request.yaml
│  │  │  │  └─ 14-page-profil.request.yaml
│  │  │  ├─ 03 - Akademik
│  │  │  │  ├─ .resources
│  │  │  │  │  └─ definition.yaml
│  │  │  │  ├─ 15-schedules.request.yaml
│  │  │  │  ├─ 16-assignments.request.yaml
│  │  │  │  ├─ 17-assignment-detail.request.yaml
│  │  │  │  └─ 18-materials.request.yaml
│  │  │  ├─ 04 - Presensi
│  │  │  │  ├─ .resources
│  │  │  │  │  └─ definition.yaml
│  │  │  │  ├─ 19-attendance-today.request.yaml
│  │  │  │  └─ 20-attendance-history.request.yaml
│  │  │  ├─ 05 - Notifikasi
│  │  │  │  ├─ .resources
│  │  │  │  │  └─ definition.yaml
│  │  │  │  ├─ 21-notifications.request.yaml
│  │  │  │  ├─ 22-notification-read.request.yaml
│  │  │  │  └─ 23-notifications-read-all.request.yaml
│  │  │  ├─ 06 - Negatif
│  │  │  │  ├─ .resources
│  │  │  │  │  └─ definition.yaml
│  │  │  │  ├─ 24-me-without-token.request.yaml
│  │  │  │  ├─ 25-me-invalid-token.request.yaml
│  │  │  │  ├─ 26-login-wrong-account.request.yaml
│  │  │  │  ├─ 27-login-validation.request.yaml
│  │  │  │  ├─ 28-endpoint-not-found.request.yaml
│  │  │  │  └─ 29-method-not-allowed.request.yaml
│  │  │  ├─ 07 - Tulis Data (manual)
│  │  │  │  ├─ .resources
│  │  │  │  │  └─ definition.yaml
│  │  │  │  ├─ 30-attendance-check-in.request.yaml
│  │  │  │  ├─ 31-attendance-check-out.request.yaml
│  │  │  │  └─ 32-submit-assignment.request.yaml
│  │  │  └─ 08 - Cleanup
│  │  │     ├─ .resources
│  │  │     │  └─ definition.yaml
│  │  │     ├─ 33-logout.request.yaml
│  │  │     └─ 34-me-after-logout.request.yaml
│  │  └─ Performance Test Sample Collection
│  │     ├─ .request.yaml
│  │     └─ .resources
│  │        └─ definition.yaml
│  ├─ environments
│  │  ├─ MTs Al-Huda Local.environment.yaml
│  │  └─ MTs Al-Huda Online.environment.yaml
│  ├─ globals
│  │  └─ workspace.globals.yaml
│  └─ README.md
├─ public
│  ├─ .htaccess
│  ├─ assets
│  │  └─ images
│  │     ├─ Belajar di Perpustakaan.jpg
│  │     ├─ Drumband.jpg
│  │     ├─ Grup Sholawat  MTS Al-Huda.jpg
│  │     ├─ LOGO_MTS 1.png
│  │     ├─ Mengikuti Upacara Pembukaan Jambore Cabang.jpg
│  │     ├─ Pertemuan Wali Santri.jpg
│  │     └─ Senam Bersama.jpg
│  ├─ css
│  │  ├─ admin.css
│  │  └─ style.css
│  ├─ index.php
│  └─ js
│     └─ script.js
├─ README.md
├─ stitch_sistem_akreditasi_web_terpadu
│  └─ stitch_sistem_akreditasi_web_terpadu
│     ├─ akademik_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ al_huda_academic_system
│     │  └─ DESIGN.md
│     ├─ bank_bukti_akreditasi_admin
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ beranda_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ berita_artikel_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ dashboard_administrator_mts_al_huda_gondang_1
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ dashboard_administrator_mts_al_huda_gondang_2
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ dashboard_kesiapan_akreditasi_admin
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ design.md
│     ├─ detail_berita_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ editor_dka_admin
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ edit_berita_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ fasilitas_sarpras_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ galeri_kegiatan_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ info_ppdb_2027_2028_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ jadwal_pelajaran_admin
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ kelola_berita_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ kelola_pengguna_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ kontak_layanan_informasi_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ login_administrator_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ madrasah_modernity
│     │  └─ DESIGN.md
│     ├─ pengaturan_situs_admin
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ ppdb_informasi_dokumen_admin
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ presensi_guru_admin
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ profil_mts_al_huda_gondang
│     │  ├─ code.html
│     │  └─ screen.png
│     ├─ stitch_prompts.md_1.md
│     └─ stitch_prompts.md_2.md
└─ tools
   ├─ api-test.php
   ├─ build_clean_zip.php
   ├─ build_clean_zip.py
   ├─ build_master_sql.php
   ├─ data_guru_list.php
   ├─ data_guru_map.php
   ├─ data_jadwal_matrix.php
   ├─ data_siswa_list.csv
   ├─ db-check.php
   ├─ db-share.php
   ├─ ftp-connect.php
   ├─ ftp-deploy.php
   ├─ generate_drawio.php
   ├─ migrate.php
   ├─ schema-test.php
   ├─ setup.php
   ├─ set_all_passwords_123.php
   └─ update_class_diagram.php

```