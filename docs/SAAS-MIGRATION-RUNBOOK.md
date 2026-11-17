# Migrasi SaaS Anugerah — runbook operator S008

Dokumen ini adalah rencana S010, **bukan catatan tindakan produksi**. Rehearsal sintetis MySQL 8.0.31 di Mac lulus pada [S008/A1](evidence/SAAS/S008/A1/README.md). MariaDB 10.11, salinan luar mesin nyata, dan produksi belum diuji S008. S009 harus memeriksa ulang candidate ini secara independen sebelum S010.

Fakta target terakhir dari [deployment existing](DEPLOYMENT.md): Debian 12, PHP `/usr/bin/php8.3`, MariaDB 10.11.14, direktori biasa `/var/www/demo-repair-app/current`, domain `https://demo-repair-app.scannara.asia`, receiver `/home/muzib/deploy-demo-repair-app/cicd`. **Verifikasi kembali di S010**; nama database produksi dibaca privat dari konfigurasi, jangan menebaknya dari nama demo. Versi aktif adalah `.release-revision` dan receipt CI, bukan `.git` instalasi pertama.

## Gerbang sebelum maintenance

- S007 PASS (`sha256:6ba364a204d98ac1d9754182d2a4fd1c54de6f940d7341ad88bef207aea74ad4`) untuk software S006 (`sha256:066bb9e82bd3ac5142a1ef516fc46c862c7e3b1b2cbc28ee4eb0a8ab402996f6`); S009 PASS pada revisi gabungan yang memuat runbook/test S008. Pin SHA commit rilis lengkap, digest arsip, revisi sebelumnya, dan artefak rollback di receipt privat S010.
- S009 wajib membuktikan dump → restore → migrasi → rollback/guard pada **MariaDB 10.11** dengan client yang akan dipakai. `backup.sh` memerlukan flag MySQL 8 `--set-gtid-purged=OFF --column-statistics=0`; jangan menggantinya dengan `mariadb-dump` tanpa uji. Keberhasilan MySQL lokal tidak melepas gerbang ini.
- Owner menetapkan waktu maintenance, menghentikan transaksi dan semua penulis lain, serta menerima cara pemulihan. Sebelum S010, tetapkan tujuan backup di luar VPS, operator pemegang salinan, ruang kosong, retensi minimum 14 hari, dan pemeriksaan harian. Penundaan D-22 khusus demo tidak berlaku untuk migrasi client ini.
- **Gate S010 BACKUP-OFFSITE:** tujuan permanen masih perlu dipastikan. Opsi minimum untuk window migrasi ialah operator menarik dump + upload ke folder privat Mac, memverifikasi checksum dan restore salinan itu; receipt harus menyebut lokasi aktual dan pemegang salinan. Untuk backup harian berkelanjutan, tetap tetapkan tujuan/akses/jadwal; salinan Mac sekali bukan bukti jadwal backup harian.
- Siapkan release teruji menggunakan `deploy/ci-package.sh` di lingkungan GNU tar, dari commit bersih dan lockfile existing; simpan arsip beserta SHA-256. Verifikasi/extract dengan `deploy/ci-archive.py` ke direktori staging kosong, di luar web root. Siapkan juga artefak versi sebelumnya. Jangan merge/push `main` untuk mengambil artefak sebelum gate: workflow existing langsung mencoba auto-deploy.
- Akun dump/restore terpisah, file `.cnf` absolut mode `600`/`400`; akun restore hanya berhak pada scratch/recovery yang ditunjuk. Jangan root/global grants untuk restore. SQL tepercaya tetap dapat menyebut schema lain: argumen target bukan penyaring SQL. Simpan `.env` dan APP_KEY secara privat di luar web root; jangan buat key/password/admin baru atau seed ulang.
- Periksa host/DB yang tepat, InnoDB seluruh tabel, status migrasi lama (tanpa fondasi tenant), kapasitas dump + SQL dekompresi + upload + artefak lama/baru, dan hak deploy/FPM existing. Tidak ada DDL lain selama dump. Jangan pakai `migrate:fresh`, reset/drop, `composer setup/update`, atau cleanup data.

Contoh variabel Bash **di VPS saat S010 saja**. Operator mengisi path/nama yang benar; placeholder tidak boleh dieksekusi apa adanya. Skrip operasional diambil dari revisi yang dipin dan disimpan di luar folder yang akan disinkronkan.

```bash
set -euo pipefail
umask 077
PHP=/usr/bin/php8.3
BASE=/var/www/demo-repair-app
CURRENT=$BASE/current
CICD=/home/muzib/deploy-demo-repair-app/cicd
OPS=/home/muzib/deploy-demo-repair-app/saas-ops
: "${RELEASE_REV:?40 digit SHA teruji}" "${STAGED_RELEASE:?direktori hasil ci-archive.py}"
: "${LIVE_DB:?nama DB terverifikasi privat}" "${BACKUP_DIR:?direktori baru privat mode 700}"
: "${DUMP_CNF:?file koneksi dump privat}" "${RESTORE_CNF:?file akun scratch privat}"
test -d "$BACKUP_DIR"
test -f "$CURRENT/.release-revision"
test ! -e "$BASE/.deploy-pending"
test ! -e "$CURRENT/storage/framework/down"
exec 9>"$CICD/deploy.lock"
flock -n 9                       # tahan lock yang sama dengan receiver sampai selesai
test ! -e "$BASE/.deploy-pending" # periksa lagi setelah lock
test ! -e "$CURRENT/storage/framework/down"
PREVIOUS_REV=$(cat "$CURRENT/.release-revision")
printf '%s\n' "$PREVIOUS_REV" > "$BACKUP_DIR/previous-revision.txt"
touch "$BASE/.deploy-pending"    # milik window S010 ini; jangan hapus marker orang lain
cd "$CURRENT"
"$PHP" artisan down --retry=60
"$PHP" artisan optimize:clear --except=cache
```

Lock mencegah receiver masuk; marker membuat CI menolak deploy walaupun koneksi operator terputus. Bila salah satu perintah berikut gagal, berhenti dan **pertahankan kedua maintenance/marker**. Jangan memasang trap `artisan up`. Catat pemegang lock dan cara memulihkan sesi; jangan menghapus lock/marker existing secara membabi buta.

## Baseline privat dan backup

Sebelum mengubah kode/schema, simpan inventaris seluruh field lama. Blok PHP berikut dijalankan dari root aplikasi lama, menggunakan PHP terverifikasi. Simpan sebagai file operator privat `$BACKUP_DIR/inventory.php`, mode `600`, bukan file publik. Ia hanya membaca database yang nama eksplisitnya cocok. Tidak mencetak baris, password, token, atau customer; hanya jumlah/hash dan nama kolom. Gunakan `MODE=capture` sebelum dump, `MODE=verify` pada hasil restore, sesudah migrasi, dan saat pemulihan. Salin file baseline ke Mac bersama arsip. Jangan kirim isinya ke chat; receipt cukup hash berkas dan hasil cocok/tidak.

```php
<?php
try {
    require getcwd().'/vendor/autoload.php';
    $app = require getcwd().'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $db = Illuminate\Support\Facades\DB::connection();
    $schema = $db->getSchemaBuilder();
    if ($db->getDatabaseName() !== getenv('EXPECTED_DB')) throw new RuntimeException('Wrong DB');
    $path = getenv('INVENTORY');
    $capture = getenv('MODE') === 'capture';
    if (!$path || ($capture && file_exists($path))) throw new RuntimeException('New private baseline required');
    $before = $capture ? [] : json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $tables = $capture ? array_column($schema->getTables($db->getDatabaseName()), 'name') : array_keys($before);
    $after = [];
    foreach ($tables as $table) {
        if ($table === 'migrations') continue; // migration history intentionally adds one row
        $columns = $capture ? $schema->getColumnListing($table) : $before[$table]['columns'];
        $rows = $db->table($table)->select($columns)->get()->map(function ($row) {
            $row = (array) $row; ksort($row);
            return json_encode($row, JSON_THROW_ON_ERROR);
        })->all();
        sort($rows);
        $after[$table] = ['columns'=>$columns, 'rows'=>count($rows), 'sha256'=>hash('sha256', implode("\n", $rows))];
    }
    ksort($after);
    if ($capture) {
        file_put_contents($path, json_encode($after, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX);
        chmod($path, 0600);
    } elseif ($before !== $after) throw new RuntimeException('Legacy baseline differs; keep maintenance');
    echo $capture ? "BASELINE SAVED\n" : "ALL LEGACY FIELDS IDENTICAL\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Inventory failed; keep maintenance and inspect private inputs.\n");
    exit(1);
}
```

```bash
EXPECTED_DB="$LIVE_DB" INVENTORY="$BACKUP_DIR/inventory.json" MODE=capture \
  "$PHP" "$BACKUP_DIR/inventory.php"
# Client/config/path must already have passed S009 on the target engine.
# Remote SSH option: backup.sh CNF DB DIR offsite-alias /approved/remote/dir
# For operator pull, exit 1 below is expected only for this exact Q-04 message:
set +e
bash "$OPS/backup.sh" "$DUMP_CNF" "$LIVE_DB" "$BACKUP_DIR" - - \
  >"$BACKUP_DIR/backup.log" 2>&1
BACKUP_EXIT=$?
set -e
test "$BACKUP_EXIT" = 1
grep -Fxq 'Local archive saved; Q-04 off-VPS destination missing; retention skipped (NOT a successful daily backup)' "$BACKUP_DIR/backup.log"
# Select the ONE completed .sql.gz in this new window directory, not a guessed latest file.
mapfile -t DUMPS < <(find "$BACKUP_DIR" -maxdepth 1 -type f -name "$LIVE_DB-*.sql.gz")
test "${#DUMPS[@]}" = 1
BACKUP_FILE=${DUMPS[0]}
gzip -t "$BACKUP_FILE"
tar -czf "$BACKUP_DIR/uploads.tar.gz" -C "$CURRENT/storage/app/public" .
# Manifest retains relative names; keep private because filenames can be sensitive.
(cd "$CURRENT/storage/app/public"; find . -type f -exec sha256sum {} + | LC_ALL=C sort) > "$BACKUP_DIR/uploads.files.sha256"
(cd "$BACKUP_DIR"; sha256sum "${BACKUP_FILE##*/}" uploads.tar.gz inventory.json inventory.php uploads.files.sha256 previous-revision.txt > SHA256SUMS)
```

Upload existing hanya pada disk public (`storage/app/public`, termasuk `logos/legacy...`). Jangan memindahkan logo lama ke direktori tenant baru saat migrasi: `logo_path` lama harus tetap berlaku. Jika inventaris target menunjukkan lokasi upload lain, sertakan sebelum melanjutkan. Simpan `.env`/APP_KEY terpisah dengan hak privat; arsip DB/upload tidak cukup untuk memulihkan key.

**Di Mac operator**, alias SSH biasa yang telah diverifikasi host key-nya diperlukan; kunci CI bersifat forced-command deploy dan tidak dipakai untuk `scp`. Tidak perlu membuka server backup baru:

```bash
umask 077
: "${MAC_COPY:?folder privat baru di Mac}" "${VPS_BACKUP_DIR:?path persis BACKUP_DIR di VPS}"
mkdir -m 700 "$MAC_COPY"
scp -o BatchMode=yes -o StrictHostKeyChecking=yes -r \
  "repair-vps:$VPS_BACKUP_DIR/." "$MAC_COPY/"
(cd "$MAC_COPY"; shasum -a 256 -c SHA256SUMS)
```

Operator mencatat checksum, ukuran, waktu, host sumber, lokasi Mac aktual dan receipt transfer; berkas privat tetap di luar Git. S008 hanya menyalin antar dua direktori lokal, **bukan** membuktikan langkah SSH/offsite di atas. Tanpa salinan luar VPS dan restore dari salinan tersebut, S010 berhenti sebelum rsync/migrate.

## Restore scratch sebelum migrasi target

DBA memeriksa nama scratch **belum ada** lewat `INFORMATION_SCHEMA.SCHEMATA`, lalu membuat DB kosong dengan charset/collation target dan akun hanya untuk DB itu. Pakai nama eksplisit, misalnya `saas_s010_restore_<window>`; jika sudah ada, pilih nama baru, jangan overwrite. Lakukan pada MariaDB 10.11 yang telah lulus S009. Tarik kembali salinan Mac ke direktori privat scratch atau lakukan restore di host scratch dari salinan Mac; ulangi SHA-256 terhadap `SHA256SUMS` dari sumber.

```bash
: "${SCRATCH_DB:?database BARU kosong}" "${PULLED_BACKUP:?arsip dari Mac terverifikasi}"
bash "$OPS/restore.sh" "$RESTORE_CNF" "$SCRATCH_DB" "$PULLED_BACKUP" "RESTORE:$SCRATCH_DB"
```

`restore.sh` sepenuhnya mendekompresi sebelum import. Gzip rusak tidak menyentuh DB. SQL valid yang gagal di tengah bisa meninggalkan tabel/baris parsial: **jangan retry di DB tersebut**, jangan jalankan migrasi/down untuk memperbaikinya. Simpan receipt kegagalan, tandai scratch tidak boleh dipakai, dan gunakan DB baru untuk restore ulang. Jangan restore backup lama menimpa schema SaaS karena tabel tambahan tidak dihapus oleh dump.

Siapkan checkout scratch privat dari kode lama, `.env` scratch dengan DB eksplisit dan key salinan yang aman (bukan `.env` live), cache config terpisah, storage hasil extract arsip. Jangan menunjuk server HTTP scratch ke publik. Dari scratch root jalankan inventory `MODE=verify EXPECTED_DB="$SCRATCH_DB"` dengan baseline yang sama; verifikasi jumlah tabel/migration lama, relasi foreign key, dan `shasum -c uploads.files.sha256` di hasil extract. Setelah itu uji candidate SaaS terhadap scratch dan ulangi inventaris. Setiap field lama, termasuk password hash/token, nomor, nilai stok, qty/harga/modal/biaya, creator/teknisi dan identitas toko harus identik.

Verifikasi eksplisit sesudah migrasi: hanya satu tenant aktif id `1`, namanya sama dengan `store_settings.name` Anugerah; delapan tabel `users/categories/service_orders/products/sales/expenses/number_sequences/store_settings` memiliki tenant_id wajib, foreign key ke tenants, dan **semua** baris `tenant_id=1`. Parent-child TTS/item, sale/item/product, stock/product/creator harus tetap tersambung; key kategori+tenant, nomor TTS/JL+tenant, setting+tenant, dan counter+tenant harus benar. Counter lama tetap; probe nomor selanjutnya hanya dalam transaksi yang dibatalkan.

Bandingkan jumlah TTS tiap status, sale, item, akun aktif/nonaktif, stock tiap produk, daftar nomor+counter, jumlah item×harga dan item×modal servis/penjualan, pengeluaran serta modal stok sebelum/sesudah. Hash semua field membuktikan kesamaan ini; tambahkan laporan aplikasi pada rentang hari/bulan yang sama agar jalur query tenant juga teruji. Jangan memakai angka fixture S008 sebagai ekspektasi client nyata.

## Terapkan release dan migrasi dengan maintenance tetap aktif

Untuk target CI existing gunakan sinkronisasi dan panggilan Artisan yang sama dengan receiver. **Jangan memanggil receiver `ci-remote.sh` untuk migrasi ini:** ia tidak mengambil backup dan membuka aplikasi sebelum verifikasi bisnis. `deploy.sh` boleh dipakai pada checkout Git bersih dengan HEAD tepat; target existing memiliki `.git` instalasi lama, sehingga guard itu tidak cocok. Jangan menghapus guard atau mengaku HEAD live sama dengan release. Jalur manual ini memakai alat existing tanpa mengganti sistem deploy.

Setelah scratch, salinan luar VPS, dan seluruh gerbang lulus, dari sesi yang masih memegang lock:

```bash
cd "$CURRENT"
test -f storage/framework/down
test -f "$BASE/.deploy-pending"
test "$(cat "$STAGED_RELEASE/.release-revision")" = "$RELEASE_REV"
"$PHP" /usr/local/bin/composer --working-dir="$STAGED_RELEASE" check-platform-reqs --no-dev
sha256sum .env > "$BACKUP_DIR/environment.before.sha256"
rsync -rlt --checksum --delete-delay --omit-dir-times --no-perms --no-owner --no-group \
  --exclude='/.env' --exclude='/.git' --exclude='/storage' \
  --exclude='/public/storage' --exclude='/bootstrap/cache' --exclude='/.release-revision' \
  "$STAGED_RELEASE/" "$CURRENT/"
gzip -t "$BACKUP_FILE"
"$PHP" artisan migrate --force --no-interaction
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
"$PHP" artisan migrate:status
"$PHP" artisan route:list
sha256sum -c "$BACKUP_DIR/environment.before.sha256"
EXPECTED_DB="$LIVE_DB" INVENTORY="$BACKUP_DIR/inventory.json" MODE=verify \
  "$PHP" "$BACKUP_DIR/inventory.php"
(cd storage/app/public; sha256sum -c "$BACKUP_DIR/uploads.files.sha256")
```

Simpan stdout/exit/log privat. Tidak ada `artisan up` di blok ini. FPM mengikuti pemeriksaan opcode existing dua detik; verifikasi perilaku aktual atau reload service hanya melalui izin host yang berlaku. Jangan chmod/chgrp massal; rsync mempertahankan storage/config/hak existing. Verifikasi kembali hash artefak dan file terpasang terhadap manifest release (abaikan cache/runtime yang dikecualikan) sebelum menyatakan versi terpasang.

## Kegagalan dan batas rollback

| Titik gagal | Tindakan |
|---|---|
| Backup/copy/checksum/restore scratch | Berhenti sebelum sinkronisasi/migrasi live; diagnosis. Bila membatalkan window, kode/schema lama tetap, owner menyetujui buka kembali setelah verifikasi. |
| Dump/restore parsial | Jangan gunakan arsip gagal atau DB parsial. Arsip utuh dan salinan lama tetap dipertahankan; gunakan scratch baru. |
| Migrasi/backfill live gagal | Tahan maintenance; simpan log dan snapshot kondisi gagal secara privat. MySQL/MariaDB DDL tidak dianggap satu transaksi: tenants/kolom/backfill awal bisa sudah menetap walau migration history belum ditulis. **Jangan retry `migrate` atau `down` secara buta**. Pilih perbaikan maju yang direview atau restore ke DB recovery kosong. |
| Schema SaaS selesai tetapi verifikasi/aplikasi gagal | Utamakan perbaikan maju teruji. Bila harus kembali ke versi lama, pulihkan backup pre-SaaS + upload/key yang cocok ke DB recovery baru, validasi baseline, pasangkan artefak kode lama, ubah konfigurasi DB secara privat, cache ulang. Jangan rollback kode saja: model lama tidak kompatibel dengan schema tenant baru. |
| Tenant kedua sudah dibuat | `down()` fondasi sengaja menolak sebelum perubahan. Jangan melewati guard. Restore pre-SaaS akan kehilangan tenant kedua dan semua transaksi baru; perlu keputusan owner/rekonsiliasi atau perbaikan maju. Jangan buka tenant kedua sebelum migrasi Anugerah diterima. |

Rehearsal membuktikan `migrate:rollback --path=database/migrations/2026_11_17_000000_add_tenant_foundation.php --force` **hanya pada single-tenant scratch** mengembalikan field/schema lama, dan reapply tetap identik. Ini bukti pengembang, bukan jalur rollback live otomatis. Di live, pemulihan yang direkomendasikan ialah DB recovery kosong dari backup terverifikasi, dengan DB gagal dipertahankan untuk diagnosis. Jangan menghapus database lama/row/fixture. Perpindahan DB/key/upload harus dicatat; `config:cache`, route/view cache, dan versi kode harus sesuai database yang dipilih.

Sebelum restore pemulihan, hitung interval transaksi sejak backup dan pertahankan data sesudahnya. Bila aplikasi belum dibuka dan semua penulis berhenti, semestinya tidak ada transaksi bisnis hilang; buktikan, jangan asumsikan. Bila sudah ada transaksi, owner menentukan rekonsiliasi sebelum pemulihan yang dapat menghilangkannya. Salinan pre-migration tidak boleh menjadi alasan menimpa transaksi baru.

## Kriteria membuka kembali dan handoff

Semua hash/jumlah/relasi/angka/identitas/upload cocok, migration status sukses, key/secret tetap, schema constraints benar, artefak terpasang cocok SHA, dan tidak ada error baru. Pertahankan baseline DB sampai pemeriksaan ini selesai, karena login/sesi akan mengubah tabel runtime.

Smoke internal dapat dilakukan dengan SSH tunnel ke HTTP lokal privat atau bypass maintenance Laravel yang disimpan privat (jangan URL bypass/token di receipt). Sebelum membuka umum, tester S011 memeriksa login admin/teknisi existing, TTS lama/detail/tracking guest, PDF dan logo legacy, stok/penjualan lama, laporan pada periode baseline. Transaksi baru hanya fixture yang disetujui owner, tidak dihapus otomatis; catat nomor/selisih total yang diharapkan. Jika smoke membutuhkan akses sementara, gunakan bypass terbatas tanpa mematikan maintenance umum.

Setelah semua kriteria dan owner/operator menyetujui pembukaan:

```bash
cd "$CURRENT"
printf '%s\n' "$RELEASE_REV" > .release-revision
"$PHP" artisan up
curl --fail --silent --show-error --output /dev/null https://demo-repair-app.scannara.asia/up
curl --fail --silent --show-error --output /dev/null https://demo-repair-app.scannara.asia/login
# Hanya marker milik window S010 ini; jika HTTPS gagal, down lagi dan pertahankan marker.
rm -- "$BASE/.deploy-pending"
```

Lepas lock sesudah receipt S010 mencatat target, SHA sumber/artefak/backup/upload, waktu dan pelaksana, hasil restore+baseline+smoke, lokasi salinan luar VPS, serta keputusan buka/pulih. Handoff S011 mem-pin SHA terpasang yang sama. HTTP 200 sendiri tidak membuktikan migrasi atau penerimaan client. Uji HP/printer fisik dan pilot tenant kedua berada pada gerbang masing-masing; tidak diklaim oleh S008.
