# T081 — QA independen

**PASS** untuk perubahan tampilan yang ditugaskan. Pemeriksa terpisah dari implementer; Ponytail **full** dibaca dari `/Users/muzib/.codex/plugins/cache/ponytail/ponytail/5.3.3/skills/ponytail/SKILL.md`, bersama kontrak T081, AGENTS dan panduan QA project-delivery. Model diwariskan runtime; tidak ada klaim model efektif dari `model_hint`.

Input: base `3a8ed3ee0d7c5bb245a3e97dd44703f18f3204cf`, candidate `sha256:39d4fce58c7bf94d0f6ea583d001ff486db8b1651908d2df007afcf7f67665be`. Ketiga SHA-256 source cocok dengan `source-manifest.json` sebelum pemeriksaan. Diff hanya tiga Blade view; route, backend, guard tenant/role, form pembayaran/pengambilan dan editor pengecekan tidak diubah.

Pemeriksaan aktual:

- Render HTTP Laravel terautentikasi melalui tes sementara `/private/tmp/T081LayoutQaTest.php`, SQLite `:memory:`, fixture sintetis. Perintah: `/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpunit --configuration phpunit.xml /private/tmp/T081LayoutQaTest.php`. Hasil akhir **1 PASS, 58 assertions, 384 ms**; enam konteks: Diambil admin/teknisi, Selesai admin, Pengecekan admin/teknisi dan sukses admin.
- Diambil admin: tidak ada `current-work` atau `actionbar`; satu `Lihat nota` pada header nota, berdampingan dengan `Koreksi nota`, masing-masing berikon dan URL cetak benar. Menu header tidak dibungkus `desktop-only`; form pembayaran tetap ada.
- Diambil teknisi: layout satu kolom, tidak ada workcard/nota/koreksi atau nilai jual/modal dan deskripsi rincian privat. Catatan tetap tersedia tanpa form edit.
- Selesai admin: kartu siap diambil, form `collect-order` dan actionbar tetap ada; tidak mendapat tombol `Lihat nota` baru. Pengecekan admin mempertahankan textarea/form `saveInspection` dan tidak menggandakan catatan baca-saja. Pengecekan teknisi mempertahankan catatan baca-saja. Halaman sukses mempertahankan kartu sukses tanpa catatan baca-saja atau nota.
- DOM (struktur HTML) membuktikan catatan baca-saja satu bagian tepat setelah kartu Penanganan; isi hasil dan rencana tetap lengkap, newline dipertahankan dan teks `<b>` tidak menjadi elemen HTML.
- Meninjau screenshot akhir coordinator: `desktop.jpg` (1440), `mobile-note.jpg` dan `mobile-notes-bottom.jpg` (375). Kartu selesai hilang; kedua aksi nota muat berdampingan; catatan terletak setelah Penanganan pada desktop dan HP, dengan isi lengkap. Browser/resource coordinator tidak diambil alih.

Percobaan awal harness sementara dikoreksi karena XPath atribut `wire:` memerlukan selektor berdasarkan nama, fixture membutuhkan konteks tenant sebelum update, dan judul kartu tahap teknisi pengecekan bukan duplikasi bagian catatan. Tidak ada perubahan kode aplikasi untuk mencapai PASS.

Tidak mengulang suite penuh yang telah dijalankan coordinator; tidak menguji printer/Android nyata, melakukan QA backend menyeluruh, mengakses DB operator/MySQL/produksi, push atau deploy. Batas risiko: review visual memakai screenshot browser coordinator, bukan pengoperasian browser independen.
