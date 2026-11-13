# T004 — lanjut A2 melalui worker bawaan Codex

Developer self-check: **PASS** untuk perbaikan F01/F02/F03. Ini kelanjutan logical A2 setelah stop/resume, bukan attempt baru atau penerimaan QA independen. Base `9a7117b952b2232791405a6121a1d36b061baa01`, branch `codex/goal-v1`; berkas final dipin pada `source-manifest.json`. Coordinator menangani ledger dan push.

Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`. Project Delivery, referensi coordination/runtime, kontrak T004 kanonik dan PRD terkait juga dibaca. Sol diminta oleh assignment; worker tidak memiliki receipt identitas backend efektif untuk diklaim. Tidak membuat worker lain atau mengubah TODO/AGENTS.

## Perubahan dan dampak

- F01: manifest sekarang membuka `/mulai` (nama rute `app.start`), yang memerlukan login dan membaca akun terbaru lewat helper `User::activeAccount()` sebelum memilih home. Admin diarahkan `/`, teknisi `/tts/baru`; `/` dan `/dashboard` tetap admin-only. Demotion, promotion dan deactivation sesi diuji. `/tts/baru` tetap 404 sampai T009, sesuai scope.
- F02: layout login menautkan `/manifest.webmanifest`, sama seperti shell aplikasi. Tidak mengubah tampilan/CSS/aset hasil build.
- F03: konfigurasi sementara Livewire memakai middleware `auth`, `admin`, `throttle:60,1`. Guard `admin` membaca izin terkini pada POST, sehingga grant yang dibuat saat admin tidak memberi hak unggah setelah demotion/deactivation. `web` tetap ditambahkan framework; signature controller dan semua nilai bawaan section upload (disk env, rules, directory, preview types, expiry, cleanup) dipertahankan. Section lengkap diperlukan karena merge config Livewire hanya pada tingkat atas. Satu pemakai unggah yang ditemukan adalah Store; tidak mengganti controller/vendor.

## Pemeriksaan aktual

Perintah dan output ringkas aktual tersimpan pada `checks.json`. Final source: **63 tes PASS, 527 assertion** (60 auth/users/settings/profile/dashboard/scaffold + 3 layout), Pint PASS, PHPStan PASS 0 error, Git whitespace PASS. PHPStan pertama gagal karena socket sandbox EPERM; mode `--debug` menghindari worker paralel dan lulus, tanpa mengubah source atau lingkungan global.

Regresi baru memakai grant dari aksi nyata `Store::_startUpload`, demosi/nonaktif lewat `Users::edit/save`, lalu POST file ke endpoint framework. Tamu JSON401, teknisi/demosi/nonaktif403 dan tidak ada file baru; admin valid200; oversize422 tanpa file baru; signature diubah/kedaluwarsa401; permintaan admin ke-61 terkena429. Preview memakai GET ke URL bertanda tangan nyata dan mengembalikan image/png200; URL preview diubah401. Tes parsial awal dipakai ulang dan ditambah assertion, tanpa mengganti fake PNG karena helper existing memang menghasilkan byte PNG yang valid.

Semua feature test memakai phpunit.xml SQLite in-memory; ConcurrentAdminTest existing membuat SQLite privat sementara untuk empat pasangan proses. Tidak mengakses atau mereset MySQL/fixture existing. PHP 8.3 dipakai eksplisit. Log `reproduction-before.log` dari stop lama dipertahankan tanpa perubahan; bukan hasil baru dan tidak dinyatakan PASS.

## Batas bukti

Tidak mengulang build karena aset tidak berubah, full suite domain, browser/install Android nyata, screenshot responsif, atau balapan MySQL. Bukti visual dan sesi HTTP historis tetap referensi pada source lama, tidak diklaim membuktikan revisi ini. QA independen F01/F02/F03 dan penerimaan gabungan tetap gate coordinator; tautan/start manifest sudah dibuktikan dari respons HTML dan routing oleh feature test. Tidak deploy atau push. Shortcut Android lama yang menyimpan start URL `/` dapat perlu dipasang ulang; root tetap sengaja admin-only.
