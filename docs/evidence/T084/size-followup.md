# T084 — koreksi ukuran ×

User meminta tombol lebih kecil. Base `4cf8a27`, hanya CSS visual lingkaran/ikon 28→18px; area tekan tetap 44×44px. Seluruh caller memakai aturan bersama. Tidak mengubah tombol, label, handler atau data.

Pin SHA-256 resources/css/layout.css: `1e1fcd969af584f184e11b609f43a1b226cd19ca06ba0c8eb21eb6cdd57b2aca`.

`npm run build` PASS386ms, log /private/tmp/repair-notice-size-build.log; git diff --check PASS. Reviewer independen /root/t084_dismiss_review memeriksa diff aktual dan hash: PASS, target44px dan kontrak tombol/handler tetap. Tidak ada temuan.

Browser ukuran baru dan suite backend tidak diulang untuk perubahan dua nilai CSS. Bukti perilaku dismiss sebelumnya tetap di README.md dan independent-qa.md (snapshot sebelumnya). Tidak push/deploy.
