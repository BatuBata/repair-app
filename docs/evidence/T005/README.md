# T005 — developer A1

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery worker rules dan `design-taste-frontend/references/app-screens.md` juga dibaca; modul memakai pola `mockup/tts.html`, `mockup/tts-baru.html`, `DESIGN.md`, dan kontrak guard T004. Tidak ada worker tambahan, ledger pusat, perubahan model/skema, dependensi, shared CSS, atau desain baru.

## Hasil dan route

- `settings.store` → `/pengaturan/toko` dan `settings.categories` → `/pengaturan/kategori`, keduanya `auth` + `admin`; `/pengaturan/pengguna` tetap `users.index`, `/profil` tetap `profile.edit`.
- Semua aksi, `boot`, `mount`, dan `render` memanggil `User::activeAccount(admin: true)`. ID kategori dan jalur logo terkunci; nama kategori unik; kategori dinonaktifkan tanpa menghapus relasi TTS. Logo raster (JPG/PNG/GIF/WebP), maksimal 2 MB, disimpan dengan nama acak di disk `public`; file lama tidak dihapus dan upload baru dibersihkan bila simpan DB gagal.
- Kolom awal nama/alamat/telepon tetap boleh kosong agar sesuai data seed awal. Default hari garansi dibatasi rentang kolom unsigned DB. Pesan validasi baru memakai Bahasa Indonesia.

## Pemeriksaan

| Perintah / cara | Hasil aktual |
|---|---|
| `/opt/homebrew/opt/php@8.3/bin/php artisan test tests/Feature/Settings/SettingsTest.php tests/Feature/Domain/DomainTest.php` (SQLite in-memory, APP_KEY acak, tanpa MySQL) | **24 lulus, 366 assertion**, 0,660 detik; `tests-final.log`. Mencakup update/blank/range, file valid, spoof, SVG, file >2 MB, penyimpanan gagal tanpa menghapus logo lama, kategori create/edit/nonaktif, nama unik, relasi TTS lama, guest/teknisi, akun kedaluwarsa, ID invalid, serta properti terkunci. |
| `php vendor/bin/pint --test app/Livewire/Settings routes/modules/settings.php tests/Feature/Settings/SettingsTest.php` | **Lulus**; `pint-final.log`. |
| `php vendor/bin/phpstan analyse --memory-limit=512M --no-progress` | **Lulus, 0 error**; `phpstan-final.log`. |
| `php artisan route:list --path=pengaturan --json` | Route Toko, Kategori, Pengguna terdaftar; ketiganya middleware `Authenticate` + `EnsureAdmin`; `routes.json`. |
| `npm run build` untuk menjalankan preview lokal | **Lulus, selesai 1,02 detik**. Warning yang muncul: `fontaine` opsional tidak terpasang dan URL font IBM Plex diselesaikan saat runtime. Tidak ada perubahan asset/CSS setelah build. |
| Orca Browser preview port 8085, akun sintetis dan SQLite privat di `/tmp`; screenshot diambil dan setiap state dibuka untuk inspeksi | Toko dan Kategori diperiksa pada **360/768/1280 px**. Pengukuran runtime: dokumen tidak overflow pada 360, 768, 1280; input 16 px pada 360/768 dan 14 px pada 1280; actionbar 48 px, navigasi bawah 64 px, dan aksi baris 44 px pada mobile. Logo PNG uji menampilkan preview. |

Percobaan awal A1 menemukan null `warranty_terms` dari DB pada properti string, preview sementara dipanggil untuk unggahan yang ditolak, dan validasi framework berbahasa Inggris. Ketiganya diperbaiki; seluruh suite terarah final di atas lulus. Build awal browser juga gagal karena manifest belum ada; build di atas membuat manifest untuk preview.

Screenshot final: `store-form-{360,768,1280}.png`, `store-error-{360,768,1280}.png`, `store-preview-360.png`, `categories-list-{360,768,1280}.png`, `categories-form-{360,768,1280}.png`, dan `categories-error-{360,768,1280}.png`. Bukti hanya memakai data serta gambar sintetis/aset publik proyek.

QA independen/integrasi T008, suite penuh di luar kasus Settings dan Domain, uji Android fisik, dan produksi/deploy belum dilakukan. Error screenshot 768 diambil saat konten form digulir ke input bermasalah; posisi tetap actionbar sudah diukur langsung di browser.
