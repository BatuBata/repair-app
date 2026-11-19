# T093 — rilis revisi main tertahan

Otorisasi user19Nov2026: `push and deploy`, D-56, target existing https://demo-repair-app.scannara.asia /var/www/demo-repair-app/current melalui GitHub CI/CD D-23. Backup tetap ditunda sesuai keputusan user D-22; tidak menjalankan seed/reset/APP_KEY baru atau mengubah environment privat.

Preflight: 21commit lokal sejak remote e3cf94ea9aa195ff8397bee54ff5e42401e74536, targetserver marker sama dan tidak ada maintenance/deploypending. Empat migrasi: service discount0; statusPengecekan+nullablecatatan/timestamps; kategoriPengeluaran enum→varchar50+mastertenant; sale discount0. Forward migrations tidak menghapus baris existing; tidak melakukan rollbackDB otomatis.

Pemeriksaan rilis: PintseluruhsourcePASS; PHPStanawal1error karenaPHPDocstatusliteralstring sementaracastenum; diperbaiki satuaksesstatus memakai getAttribute sepertiManageServiceOrder. PHPStanfinal0error,PintdirtyPASS,CancelledTrackingTest3PASS78assert387ms; reviewindependenone-linePASS. Kode/runtime lain tetap candidate QA T063–T092, termasuk UI/data/tenant dan MySQLmigration proofs. PemeriksaanCI penuh actualMariaDB dan20deploysafety akan dijalankan GitHub pada pushfinal.

Status saat dokumen persiapan: menunggu push/CI/deploy; receipt serta QA server nyata dicatat di deploy-receipt.json dan independent-qa.md setelah selesai. Tidak menyatakan rilis PASS dari persiapan ini.
