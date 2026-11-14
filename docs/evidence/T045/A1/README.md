# T045/A1 — Perbaikan ikon aksi yang hilang

## Kandidat

- Snapshot sumber: `sha256:06ac7156d98a99f5312e7b20d3970acf8c4d02bb66712c61740f35caf7248918`
- Sprite: `public/icons/lucide.svg` (`sha256:1c1f3c741e5b9570d8d10872dd2c5de3096d275f45da09e27ca3add81d03ae10`)
- Guard test: `tests/Feature/ScaffoldTest.php` (`sha256:d861967af200e343800cd781474474d85d74b6383e2c254f02881ff5b102d013`)

## Diagnosis dan perubahan

Empat menu sudah memanggil `<x-ui.icon name="pencil" />`, tetapi sprite bersama belum memiliki simbol `i-pencil`. Satu simbol Lucide `pencil` ditambahkan ke sprite; tidak ada caller, CSS, dependency, data, rute, atau izin yang diubah.

Guard test baru memeriksa seluruh referensi ikon statis di `resources/views` terhadap simbol di sprite. Audit menemukan 31 nama ikon yang dipakai dan tidak menyisakan simbol yang hilang.

## Pemeriksaan

- `php artisan test --filter=ScaffoldTest`: PASS, 6 tes / 32 assertion.
- `php artisan view:cache`: PASS.
- `php vendor/bin/pint --test tests/Feature/ScaffoldTest.php`: PASS memakai PHP 8.3.
- Browser lokal dengan SQLite sintetis: menu daftar TTS menampilkan ikon mata pada **Lihat TTS** dan ikon pensil pada **Ubah TTS**.

Catatan: pemanggilan awal `./vendor/bin/pint` memakai PHP global 8.2 dan berhenti pada pemeriksaan versi; tidak menjalankan atau mengubah aplikasi. Pemeriksaan diulang dengan PHP proyek 8.3 dan lulus.

Tidak ada commit, push, merge, deployment, atau perubahan GitHub Actions.
