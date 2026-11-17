# S012/A1 — Pilot tenant RF Samarinda

Verdict: **PASS** untuk pilot dan isolasi tenant pada release package `99f64bf59ff1489825333aa821015a281db611ea`.

## Pemeriksaan lingkungan

Pemeriksaan awal sempat berhenti karena Git HEAD folder live adalah artefak instalasi lama `d047f25…` dan PHP default server adalah 8.2. Sesuai runbook S010, identitas release authoritative adalah `.release-revision`, yang cocok tepat dengan `99f64bf59ff1489825333aa821015a281db611ea`; seluruh perintah Artisan kemudian memakai `/usr/bin/php8.3`. Ini catatan lingkungan, bukan kegagalan produk. Sebelum mutasi: tenant count 1, username target belum ada, marker maintenance/deploy tidak ada, dan backup S010 masih tersimpan.

## Hasil

- `tenant:create` dijalankan interaktif tepat satu kali. Tenant total menjadi 2.
- Pengaturan toko RF Samarinda, kategori `HP`, owner `fahad`, dan teknisi `teknisi-rf` terkonfirmasi pada tenant 2.
- Kredensial teknisi dibuat acak dan hanya disimpan di `/home/muzib/deploy-demo-repair-app/pilot-rf-samarinda-credentials.txt` mode `600`. Evidence hanya menyimpan path, mode, dan SHA-256.
- UI membuat tepat satu produk pilot, satu penjualan, dan satu TTS sintetis. TTS ditetapkan ke teknisi dan diterbitkan nota Rp50.000; stok akhir 4.
- Laporan RF cocok dengan SQL: servis Rp50.000, penjualan Rp20.000, omzet Rp70.000, modal Rp10.000, laba kotor Rp60.000.
- 19 foreign key diperiksa; orphan, tenant ID null/tidak dikenal, dan relasi lintas tenant semuanya 0.
- Scoped query tujuh model utama cocok dengan raw query masing-masing tenant. Akses silang TTS dua arah dan penjualan RF dari sesi Anugerah ditolak 404.
- HTML/PDF/struk/tracking publik memakai branding RF dan tidak memuat branding Anugerah. Token tracking dan isi PDF tidak disimpan.
- Dua browser context terpisah mempertahankan identitas, opsi, dashboard, dan laporan tokonya. Login teknisi berhasil dan diarahkan sesuai peran ke `/tts/baru` tanpa write bisnis.
- Browser: 0 console error, 0 page error, 0 failed request, 0 unexpected HTTP. Laravel log pada jendela pilot: 0 error relevan baru.

## Catatan client aktif

Frozen receipt S010/S011 bukan baseline immutable karena tenant Anugerah sedang dipakai client. Pada pemeriksaan akhir, tenant 1 tetap 13 TTS tetapi hash `service_orders` dan `service_order_items` berbeda dari frozen reference. Perubahan ini tidak dapat diatribusikan ke pilot. Evidence tidak mengklaim tenant 1 unchanged; sebagai gantinya, verifier membuktikan seluruh delta pilot berada pada tenant 2, scoped counts kedua tenant cocok, relasi tenant valid, dan tidak ada row pilot pada query tenant 1.

## Feedback produk

Onboarding dan core flow selesai memakai fitur yang sudah ada. Tidak ditemukan kebutuhan fitur baru, preset, billing, panel super-admin, domain per tenant, database baru, atau backlog spekulatif.

## Batas pemeriksaan

- Model yang diminta ledger: `gpt-6.1-sol high`; model efektif tidak diklaim karena receipt model tidak tersedia.
- Tidak ada uji printer fisik, perubahan source, migrasi, deploy, cache/maintenance, atau rollback data.
- Data pilot dan berkas kredensial teknisi tetap dipertahankan sesuai instruksi.
